<?php

namespace Restruct\Silverstripe\MediaStream\Facebook {

    use Facebook\Exception\ResponseException;
    use Facebook\Exception\SDKException;
    use Facebook\Facebook;
    use Restruct\Silverstripe\MediaStream\FacebookMedia;
    use Restruct\Silverstripe\MediaStream\Feed;
    use DateTime;
    use Exception;
    use Restruct\Silverstripe\MediaStream\InstagramMedia;
    use Restruct\Silverstripe\MediaStream\MediaUpdate;
    use Restruct\Silverstripe\MediaStream\Providers\ProviderInterface;
    use SilverStripe\Dev\Debug;
    use SilverStripe\ORM\ArrayList;

    class InstagramFeed extends Feed implements ProviderInterface
    {

        private $endPoint = 'https://graph.instagram.com';

        private $version = 'v12.0';

        private static $fetch_fields = [
            'id',
            'username',
            'media_type',
            'media_url',
            'permalink',
            'thumbnail_url',
            'timestamp',
            'caption',
        ];

        /**
         * @var InstagramMedia
         */
        private $instagramMedia;

        /**
         * @param InstagramMedia $instagramMedia
         */
        public function __construct(InstagramMedia $instagramMedia)
        {
            $this->type = 'Instagram';
            $this->instagramMedia = $instagramMedia;
        }

        /**
         * @param int $limit
         *
         * @return ArrayList|void
         * @throws \JsonException
         * @throws \SilverStripe\ORM\ValidationException
         * @throws Exception
         */
        public function fetchUpdates($limit = 80)
        {

            $url = sprintf('%s/%s/%s?%s', $this->getEndPoint(), $this->instagramMedia->UserID, 'media', $this->getQueryParameters());

            $aResultData = static::getCurlResults($url);
            if ( !empty($aResultData[ 'data' ]) ) {
                $aResult = $aResultData[ 'data' ];
                $oUpdates = ArrayList::create();

                foreach ( $aResult as $post ) {
                    $aData = $this->getPostData($post);
                    $media_type = $aData[ 'MediaType' ];
                    $image = ( $media_type === 'Video' ) ? $this->getThumbnailImage($post) : $this->getImage($post);
                    $aData[ 'ImageURL' ] = $image;
                    $aData[ 'MediaStreamID' ] = $this->instagramMedia->ID;

                    if ( $aData[ 'MediaType' ] === "Video" ) {
                        $media_url = $post[ 'media_url' ];
                        $aData[ 'Content' ] = static::parseVideo($media_url) . $this->getPostContent($post);
                    }

                    $oUpdates->push($this->getOrCreateMediaUpdate($this->instagramMedia, $aData));

                }

                if ( !$this->instagramMedia->LiveStream ) {
                    $date = new DateTime();
                    $dateTime = $date->format('Y-m-d H:i:s');
                    $this->instagramMedia->LastSynced = strtotime($dateTime);
                    $this->instagramMedia->write();
                }

                return $oUpdates;

            }

        }

        /**
         * @return InstagramMedia
         */
        public function getInstagramMedia(): InstagramMedia
        {
            return $this->instagramMedia;
        }

        /**
         * @return string
         */
        public function getVersion(): string
        {
            return $this->version;
        }

        /**
         * @return string
         */
        private function getQueryParameters()
        {

            $aQueryParameters = [
                'date_format'  => 'U',
                'fields'       => implode(',', static::getFetchFields()),
                'access_token' => $this->getToken(),
            ];
            $aQueryParameters[ 'limit' ] = 100;

            if ( $this->instagramMedia->LastSynced ) {
                $aQueryParameters[ 'since' ] = $this->instagramMedia->LastSynced;
            }

            return http_build_query($aQueryParameters);
        }

        /**
         * @param $post
         *
         * @return null
         */
        public function getMediaURL($post)
        {

            return $post[ 'media_url' ] ?? null;
        }

        /**
         * @param $post
         *
         * @return null
         */
        public function getPostUrl($post)
        {
            return $post[ 'permalink' ] ?? null;
        }

        /**
         * @param $post
         *
         * @return array|string|string[]|null
         */
        public function getPostContent($post)
        {
            $text = $post[ 'caption' ] ?? '';

            return $this->parseText($text);
        }


        /**
         * @param $post
         *
         * @return null
         */
        public function getThumbnailImage($post)
        {
            return $post[ 'thumbnail_url' ] ?? null;
        }


        /**
         * @return string
         */
        public function getEndPoint(): string
        {
            return $this->endPoint;
        }

        /**
         * @return string[]
         */
        public static function getFetchFields(): array
        {
            return self::$fetch_fields;
        }

        /**
         * @return string
         */
        public function getToken()
        {
            return $this->instagramMedia->getToken();
        }

        /**
         * @param $post
         *
         * @return null
         */
        public function getUserName($post)
        {
            return $post[ 'username' ] ?? null;
        }

        /**
         * @param $post
         *
         * @return mixed
         */
        public function getImage($post)
        {
            // TODO: Implement getImage() method.
        }

        /**
         * @param $post
         *
         * @return mixed
         */
        public function getPostCreated($post)
        {
            return $post[ 'timestamp' ] ?? null;
        }
    }
}
