<?php

/**
 *
 * Create an FB APP https://developers.facebook.com/apps/
 * Debug the USER Access token https://developers.facebook.com/tools/accesstoken/
 *
 * https://developers.facebook.com/docs/graph-api/guides/explorer
 *
 * Get the USER access token first the APP Access token
 *
 */

namespace Restruct\Silverstripe\MediaStream\Model;

use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use DateTime;
use Exception;
use Restruct\Silverstripe\MediaStream\AccessTokens\FacebookAccessTokenHandler;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Debug;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\LiteralField;
use Psr\SimpleCache\CacheInterface;

/**
 * @property mixed|null $AppSecret
 * @property mixed|null $AppID
 * @property mixed|null $AccessToken
 * @property int        $DisplayType
 * @property int        $PageID
 */
class MediaInputFacebook extends MediaInput
{

    private $endPoint = 'https://graph.facebook.com';

    private $version = 'v12.0';

    public const POSTS_AND_COMMENTS = 0;

    public const POSTS_ONLY = 1;

    private static $fetch_fields = [
        'from',
        'message',
        'message_tags',
        'story',
        'story_tags',
        'full_picture',
        'actions',
        'privacy',
        'status_type',
        'created_time',
        'updated_time',
        'children',
        'attachments',
    ];

    private static $table_name = 'MediaInputFacebook';

    private static $db = [
        'PageID'      => 'Varchar(100)',
        'AppID'       => 'Varchar(400)',
        'AppSecret'   => 'Varchar(400)',
        'AccessToken' => 'Varchar(400)',
        'DisplayType' => 'Int',
    ];

    private static $singular_name = 'Facebook Input';

    private static $plural_name = 'Facebook Input';

    private static $summary_fields = [
        'Name',
        'Enabled',
        'PageID',
    ];

    private static $facebook_types = [
        self::POSTS_AND_COMMENTS => 'Page Posts and Comments',
        self::POSTS_ONLY         => 'Page Posts Only',
    ];

    protected $input_type = 'Facebook';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab('Root.Main',
            LiteralField::create(
                'sf_html_1',
                '<h4>To get the necessary Facebook API credentials you\'ll need to create a <a href="https://developers.facebook.com/apps" target="_blank">Facebook App.</a></h4><p>&nbsp;</p>'
            ),
            'Label'
        );

        $fields->addFieldToTab('Root.Main', DropdownField::create('DisplayType', 'Display Type')->setSource(static::$facebook_types), 'Enabled');

        return $fields;
    }

    public function getRequestType()
    {

        if ( $this->DisplayType === self::POSTS_AND_COMMENTS ) {
            return 'feed';
        }

        return 'posts';
    }



    /**
     * @param int $limit
     *
     * @return bool|void
     * @throws FacebookSDKException
     */
    public function fetchUpdates($limit = 100)
    {
        parent::fetchUpdates();

        $fb = new Facebook([
            'app_id'                => $this->AppID,
            'app_secret'            => $this->AppSecret,
            'default_graph_version' => $this->getVersion(),
            'default_access_token'  => $this->getToken(),
        ]);

        $query = sprintf('/%s/%s?%s', $this->PageID, $this->getRequestType(), $this->getQueryParameters());
        // get a reference to the cache for this module
        $cache = $this->getCache();

        // we try & get the items from the cache, only get & update then once per ~hour
        $cachekey = md5($query);
        $updates = [];

        try {

            if ( !( $updates = $cache->get($cachekey) ) ) {
                $response = $fb->get($query, $this->AccessToken);
                $aGraphEdgeResponse = $response->getGraphEdge()->asArray();
                if ( $aGraphEdgeResponse ) {

                    foreach ( $aGraphEdgeResponse as $post ) {

                        $aData = $this->getPostData($post);
                        $aData[ 'MediaStreamID' ] = $this->ID;
                        $aData[ 'ImageURL' ] = $this->getImage($post);

                        if ( !empty($post[ 'attachments' ]) ) {
                            $aData[ 'attachments' ] = $post[ 'attachments' ];
                        }

                        $updates[] = $this->getOrCreateMediaUpdate($this, $aData);

                    }

                } else {
                    var_dump($response);
                    $curClass = $this->getType();
                    print "ERROR ($curClass)<br>";

                    return user_error("ERROR updating $curClass, raw_response: [ " . print_r($aGraphEdgeResponse, true) . ' ]', E_USER_ERROR);
                }

                $cache->set($updates, $cachekey);
            }

            $date = new DateTime();
            $dateTime = $date->format('Y-m-d H:i:s');
            $this->LastSynced = strtotime($dateTime);
            $this->write();

            // clear memory
            unset($cache);

            return true;

        } catch ( FacebookResponseException $e ) {
            echo 'Graph returned an error: ' . $e->getMessage();

        } catch ( FacebookSDKException $e ) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();

        } catch ( Exception $e ) {
            echo 'Error: ' . $e->getMessage();

        }


    }


    /**
     * @param MediaUpdate $oMediaUpdate
     * @param array       $aData
     */
    protected function doProcessPostMediaAssets(MediaUpdate $oMediaUpdate, array $aData): void
    {


        if ( !empty($aData[ 'ImageURL' ]) ) {

            $imageURL = $aData[ 'ImageURL' ];

            $oImage = $this->saveImage($oMediaUpdate, $imageURL);

            foreach ( $aData[ 'attachments' ] as $attachment ) {
                if ( !empty($subattachments = $attachment[ 'subattachments' ]) ) {
                    foreach ( $subattachments as $subattachment ) {
                        $image_url = $subattachment[ 'media' ][ 'image' ][ 'src' ];
                        $oImage = $this->saveImage($oMediaUpdate, $image_url);

                    }

                }

            }

        }


    }


    /**
     * @return string
     */
    private function getQueryParameters(): string
    {

        $aQueryParameters = [
            'date_format'  => 'U',
            'fields'       => implode(',', self::$fetch_fields),
            'access_token' => $this->getToken(),
        ];
        $aQueryParameters[ 'limit' ] = 100;

        if ( $this->LastSynced ) {
            //$aQueryParameters[ 'since' ] = $this->LastSynced;
        }

        return http_build_query($aQueryParameters);
    }

    /**
     * @return string
     */
    public function getEndPoint(): string
    {
        return $this->endPoint;
    }


    public function getThumbnailImage($post)
    {
        return $post[ 'media_url' ];
    }

    /**
     * @param $post
     *
     * @return mixed
     */
    public function getMediaURI($post)
    {
        $media_type = $post[ 'media_type' ] ?? '';

        return ( $media_type === 'VIDEO' ) ? $post[ 'thumbnail_url' ] : $this->getImage($post);
    }


    /**
     * @param $post
     *
     * @return array|string|string[]|null
     */
    public function getPostContent($post)
    {

        $text = $post[ 'message' ] ?? '';

        return $this->parseText($text);
    }

    /**
     * Get the post URL from a post
     *
     * @param $post
     *
     * @return mixed
     */
    public function getPostUrl($post)
    {
        if ( !empty($post[ 'actions' ]) ) {
            $actions = $post[ 'actions' ];
            $action = reset($actions);

            return $action[ 'link' ];
        }

        return '#';


    }

    /**
     * Get the primary image for the post
     *
     * @param $post
     *
     * @return mixed
     */
    public function getImage($post)
    {

        return $post[ 'full_picture' ] ?? null;
    }


    /**
     * Get the user who made the post
     *
     * @param $post
     *
     * @return mixed
     */
    public function getUserName($post)
    {

        return $post[ 'from' ][ 'name' ];
    }


    /**
     * Get the creation time from a post
     *
     * @param $post
     *
     * @return mixed
     */
    public function getPostCreated($post)
    {

        return $post[ 'created_time' ];
    }


    /**
     * @return string[]
     */
    public static function getFacebookTypes(): array
    {
        return self::$facebook_types;
    }

    public function getToken()
    {
        return FacebookAccessTokenHandler::create($this)->getAccessToken();
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

}
