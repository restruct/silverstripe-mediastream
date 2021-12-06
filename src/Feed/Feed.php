<?php

namespace Restruct\Silverstripe\MediaStream {

    use SilverStripe\Assets\Image;
    use SilverStripe\Assets\Storage\AssetStore;
    use SilverStripe\Control\Controller;
    use SilverStripe\Core\Config\Configurable;
    use SilverStripe\Core\Injector\Injectable;
    use Exception;
    use SilverStripe\Dev\Debug;

    abstract class Feed
    {
        use Injectable;
        use Configurable;

        protected $type;

        protected $siteConfig;


        /**
         * @param $post
         *
         * @return array
         * @throws Exception
         */
        protected function getPostData($post): array
        {

            $media_type = !empty($post[ 'media_type' ]) ? ucfirst(strtolower($post[ 'media_type' ])) : 'Image';

            return [
                'UniqueID'  => $post[ 'id' ],
                'Title'     => $post[ 'name' ],
                'Type'      => $this->getType(),
                'Content'   => $this->getPostContent($post),
                'TimeStamp' => self::getCreatedTimestamp($post),
                'OriginURL' => $this->getPostUrl($post),
                'HasMedia' => isset($post['media_url']),
                'MediaType' => $media_type,
                'RawInput'  => json_encode($post),
                'UserName'  => $this->getUserName($post),
            ];
        }

        /**
         * @param MediaStream $mediaStream
         * @param array       $aData
         *
         * @return MediaUpdate|\SilverStripe\ORM\DataObject|null
         * @throws \SilverStripe\ORM\ValidationException
         */
        protected function getOrCreateMediaUpdate(MediaStream $mediaStream, array $aData)
        {
            $this->getSetup();
            $oMediaUpdates = MediaUpdate::get()->filter([
                'MediaStreamID' => $mediaStream->ID,
                'Type'          => $this->getType(),
                'TimeStamp'     => $aData[ 'TimeStamp' ],
            ]);

            if ( count($oMediaUpdates) && $oMediaUpdate = $oMediaUpdates->first() ) {
                $oOtherMediaUpdates = $oMediaUpdates->exclude('ID', $oMediaUpdate->ID);
                foreach ( $oOtherMediaUpdates as $oOtherMediaUpdate ) {
                    $oOtherMediaUpdate->delete();
                }

            } else {
                $oMediaUpdate = MediaUpdate::create($aData);
            }

            $oMediaUpdate->update($aData);
            $oMediaUpdate->write();
            $aCurrentImagesIds = $oMediaUpdate->Images()->column();

            $aImagesIds = [];


            Debug::show($aData);

            return;

            if ( !empty($aData[ 'ImageURL' ]) ) {

                $imageURL = $aData[ 'ImageURL' ];

                $basename = self::getImageFilename($imageURL);

                $dirPath = Controller::join_links('MediaStream', $this->getType());
                $filename = Controller::join_links($dirPath, $basename);
                $file_path = sprintf('%s/%s', ASSETS_PATH, $filename);

                try {

                    if (1==2&& copy($imageURL, $file_path) ) {

                        $oImage = $oMediaUpdate->Images()->find('Name', $basename);

                        if ( !$oImage ) {
                            $oImage = Image::create();
                        }

                        $oImage->Title = $basename;
                        $oImage->setFilename($filename);
                        $oImage->write();
                        $oImage->publishRecursive();

                        //Debug::show($oImage);

                        $oImage->setFromLocalFile($file_path, $basename, null, null, [
                            'conflict' => AssetStore::CONFLICT_OVERWRITE,
                        ]);

                        $oMediaUpdate->Images()->add($oImage);
                        $aImagesIds[]=$oImage->ID;
                    }

                } catch ( Exception $exception ) {

                    //Debug::show($exception->getMessage());
                    //Debug::show($exception->getTrace());
                }

            }

            $aExcluded = array_filter(array_diff($aCurrentImagesIds, $aImagesIds));
            if ( count($aExcluded) ) {
                $oMediaUpdate->getManyManyComponents('Images')->removeMany($aExcluded);
            }

            return $oMediaUpdate;
        }


        private static function getImageFilename($url)
        {

            $stripedImageURL = preg_replace('/\?.*/', '', $url);
            $path_parts = pathinfo($stripedImageURL);

            if ( $path_parts[ 'extension' ] !== 'php' ) {

                return $path_parts[ 'basename' ];

            }

            $parts = parse_url($url);
            parse_str($parts[ 'query' ], $query);

            return $query[ 'd' ] . '.jpg';
        }

        /**
         * @return mixed
         */
        public function getType()
        {
            return $this->type;
        }

        /**
         * In this case: auto-wrap links (@TODO: maybe also include responsive embed code for Youtube links)?
         */
        protected function parseText($rawText)
        {
            $rawText = nl2br($rawText);
            $text = html_entity_decode($rawText, 0, 'UTF-8');

            return preg_replace('/https?:\/\/[\w\-\.!~#?&=+\*\'"(),\/]+/', '<a href="$0">$0</a>', $text);
        }

        /**
         * @param array $post
         *
         * @return string|null
         * @throws \Exception
         */
        protected static function getCreatedTimestamp(array $post = []): ?string
        {
            $dateFormat = 'Y-m-d H:i:s';
            if ( !empty($post) ) {
                $time = null;
                if ( !empty($post[ 'created_time' ]) ) {
                    $time = $post[ 'created_time' ];
                }
                if ( !empty($post[ 'timestamp' ]) ) {
                    $time = $post[ 'timestamp' ];
                }

                if ( $time ) {
                    return is_object($time) ? $time->format($dateFormat) : date($dateFormat, $time);

                }

            }

            return null;

        }

        /**
         * @param string $requestUrl
         * @param int    $timeout
         * @param array  $aPostFields
         *
         * @return mixed
         * @throws \JsonException
         */
        public static function getCurlResults(string $requestUrl, int $timeout = 10, array $aPostFields = [])
        {

            $aHeaders = [];
            $aHeaders[ "User-Agent" ] = "Curl/1.0";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $requestUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeaders);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            if ( !empty($aPostFields) ) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $aPostFields);
            }
            $result = curl_exec($ch);
            if ( curl_errno($ch) ) {
                echo 'Error:' . curl_error($ch);
            }
            curl_close($ch);

            return json_decode($result, true);
        }

        protected function getSetup(): void
        {
            $dirPath = Controller::join_links('MediaStream', $this->getType());
            $absFilePath = sprintf('%s/%s', ASSETS_PATH, $dirPath);

            if ( !file_exists($absFilePath) && !mkdir($absFilePath, 0777, true) && !is_dir($absFilePath) ) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $absFilePath));
            }
        }

        public static function Guid()
        {
            mt_srand((double)microtime() * 10000);
            $charid = strtolower(md5(uniqid(mt_rand(), true)));
            $hyphen = chr(45);// "-"

            return substr($charid, 0, 8) . $hyphen . substr($charid, 8, 4) . $hyphen . substr($charid, 12, 4) . $hyphen . substr($charid, 16, 4) . $hyphen . substr($charid, 20, 12);
        }


        /**
         * @param $source
         *
         * @return string
         */
        public static function parseVideo($source)
        {

            $source = str_replace('autoplay=1', '', $source);

            return '<div class="embed-responsive embed-responsive-16by9"><iframe src="' . $source . '" class="embed-responsive-item" allowfullscreen></iframe></div>';
        }

        abstract public function getPostCreated($post);

        abstract public function getPostUrl($post);

        abstract public function getUserName($post);

        abstract public function getImage($post);


    }
}
