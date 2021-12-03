<?php

/**
 *
 * Create an FB APP https://developers.facebook.com/apps/
 * Debug the USER Access token https://developers.facebook.com/tools/accesstoken/
 *
 *
 * Get the USER access token first the APP Access token
 *
 */

namespace Restruct\Silverstripe\MediaStream;

use Restruct\Silverstripe\MediaStream\Facebook\AccessTokenHandler;
use Restruct\Silverstripe\MediaStream\Facebook\FacebookAccessTokenHandler;
use Restruct\Silverstripe\MediaStream\Facebook\FacebookFeed;
use Restruct\Silverstripe\MediaStream\Providers\ProviderInterface;
use SilverStripe\Dev\Debug;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\FieldType\DBField;

/**
 * @property mixed|null $AppSecret
 * @property mixed|null $AppID
 * @property mixed|null $AccessToken
 * @property int        $Type
 * @property int        $PageID
 */
class FacebookMedia extends MediaStream implements ProviderInterface
{

    public const POSTS_AND_COMMENTS = 0;

    public const POSTS_ONLY = 1;

    private static $table_name = 'FacebookMedia';

    private static $db = [
        'PageID'      => 'Varchar(100)',
        'AppID'       => 'Varchar(400)',
        'AppSecret'   => 'Varchar(400)',
        'AccessToken' => 'Varchar(400)',
        'Type'        => 'Int',
    ];

    private static $singular_name = 'Facebook Media Stream';

    private static $plural_name = 'Facebook Media Stream';

    private static $summary_fields = [
        'Name',
        'Enabled',
        'PageID',
    ];

    private static $facebook_types = [
        self::POSTS_AND_COMMENTS => 'Page Posts and Comments',
        self::POSTS_ONLY         => 'Page Posts Only',
    ];

    private $type = 'facebook';

    public function getCMSFields()
    {

        $fields = parent::getCMSFields();
        $fields->addFieldToTab('Root.Main', LiteralField::create('sf_html_1', '<h4>To get the necessary Facebook API credentials you\'ll need to create a <a href="https://developers.facebook.com/apps" target="_blank">Facebook App.</a></h4><p>&nbsp;</p>'), 'Label');
        $fields->replaceField('Type', DropdownField::create('Type', 'Facebook Type', $this->config()->facebook_types));

        return $fields;
    }

    /**
     * @return string
     */
    public function getType()
    {

        return $this->type;
    }

    public function getRequestType()
    {

        if ( $this->Type === self::POSTS_AND_COMMENTS ) {
            return 'feed';
        }

        return 'posts';
    }

    /**
     * @param $post
     *
     * @return DBField
     */
    public function getPostContent($post)
    {

        $text = $post[ 'message' ] ?? '';

        return DBField::create_field('HTMLText', $text);
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
     * Get the primary image for the post
     *
     * @param $post
     *
     * @return mixed
     */
    public function getImage($post)
    {

        return $post[ 'full_picture' ] ?? false;
    }

    /**
     * @param int $limit
     *
     * @return \SilverStripe\ORM\ArrayList|void
     * @throws \Facebook\Exception\SDKException
     */
    public function fetchUpdates($limit = 100)
    {
        return FacebookFeed::create($this)->fetchUpdates($limit);
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


}
