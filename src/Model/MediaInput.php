<?php

namespace Restruct\Silverstripe\MediaStream\Model;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\InterventionBackend;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Storage\AssetStore;
use Exception;

/**
 * @property mixed|null $Limit
 * @property string     $TokenExpiryDate
 * @property mixed|null $LastSynced
 * @property mixed|null $Name
 * @property mixed|null $LiveStream
 */
class MediaInput extends DataObject
{
    protected $input_type;

    /**
     * @var CacheInterface
     */
    protected $cache;

    private static $table_name = "MediaInput";

    private static $db = [
        'Name'            => 'Varchar(100)',
        'Enabled'         => 'Boolean',
        'Limit'           => 'Int',
        'LastSynced'      => 'DBDatetime',
        'TokenExpiryDate' => 'DBDatetime',
    ];

    private static $has_many = [
        'MediaUpdates' => MediaUpdate::class,
    ];

    private static $summary_fields = [
        'Name',
        'Enabled',
        'Limit',
        'LastSynced',
    ];

    /**
     * Then length of time it takes for the cache to expire
     *
     * @var int
     */
    private static $default_cache_lifetime = 900; // 15 minutes (900 seconds)

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
            'HasMedia'  => isset($post[ 'media_url' ]),
            'MediaType' => $media_type,
            'RawInput'  => json_encode($post),
            'UserName'  => $this->getUserName($post),
        ];
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
     * @param MediaInput $mediaStream
     * @param array      $aData
     *
     * @return MediaUpdate|\SilverStripe\ORM\DataObject|null
     * @throws \SilverStripe\ORM\ValidationException
     */
    protected function getOrCreateMediaUpdate(MediaInput $mediaStream, array $aData)
    {

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

        $this->doProcessPostMediaAssets($oMediaUpdate, $aData);

        return $oMediaUpdate;
    }

    protected function saveImage($oMediaUpdate, $url)
    {
        $basename = self::getImageFilename($url);

        $dirPath = Controller::join_links('MediaInputs', $this->getType());
        $oFolder = Folder::find_or_make($dirPath);
        $filename = Controller::join_links($dirPath, $basename);
        $file_path = sprintf('%s/%s', ASSETS_PATH, $filename);

        try {

            if ( $oFolder && copy($url, $file_path) ) {

                $oImage = $oMediaUpdate->Images()->find('Title', $basename);

                if ( !$oImage ) {
                    $oImage = Image::create();
                }

                $oImage->setFromLocalFile($file_path, $basename, null, null, [
                    'conflict' => AssetStore::CONFLICT_OVERWRITE,
                ]);

                $oImage->Title = $basename;
                $oImage->ParentID = $oFolder->ID;
                $oImage->setFilename($filename);
                $oImage->write();
                $oImage->publishRecursive();

                $oMediaUpdate->Images()->add($oImage);

                return $oImage;
            }

        } catch ( Exception $exception ) {

            Debug::show($exception->getMessage());
            //Debug::show($exception->getTrace());
        }
    }

    /**
     * @param $url
     *
     * @return mixed|string
     */
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
        return $this->input_type;
    }

    public function fetchUpdates()
    {
        $dirPath = Controller::join_links('MediaInputs', $this->getType());
        $absFilePath = sprintf('%s/%s', ASSETS_PATH, $dirPath);

        if ( !file_exists($absFilePath) && !mkdir($absFilePath, 0777, true) && !is_dir($absFilePath) ) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $absFilePath));
        }

    }

    /**
     * @param string $requestUrl
     * @param int    $timeout
     * @param array  $aPostFields
     *
     * @return mixed
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
     * Gets the cache used by this provider
     *
     * @return CacheInterface
     */
    public function getCache()
    {
        if ( !$this->cache ) {
            /** @var CacheInterface $cache */
            $cache = Injector::inst()->get(CacheInterface::class . $this->getCacheKeyName());
            $this->setCache($cache);
        }

        return $this->cache;
    }

    /**
     * @param CacheInterface $cache
     *
     * @return $this
     */
    public function setCache($cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * returns a cache key name that is based on the current MediaInput classname
     *
     * @return string
     */
    protected function getCacheKeyName()
    {
        return '.MediaStreamInputCache_' . $this->getType();
    }


    public function getPostCreated($post)
    {
        user_error(sprintf('Please implement method %s in your sub_class', __FUNCTION__));
    }

    public function getUserName($post)
    {
        user_error(sprintf('Please implement method %s in your sub_class', __FUNCTION__));
    }

    public function getPostUrl($post)
    {
        user_error(sprintf('Please implement method %s in your sub_class', __FUNCTION__));
    }

    public function getImage($post)
    {
        user_error(sprintf('Please implement method %s in your sub_class', __FUNCTION__));
    }

    public function getPostContent($post)
    {
        user_error(sprintf('Please implement method %s in your sub_class', __FUNCTION__));
    }

}
