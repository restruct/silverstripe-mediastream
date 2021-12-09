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

namespace Restruct\Silverstripe\MediaStream {

    use Restruct\Silverstripe\MediaStream\Facebook\FacebookAccessTokenHandler;
    use Restruct\Silverstripe\MediaStream\Facebook\FacebookFeed;
    use SilverStripe\ORM\FieldType\DBField;

    /**
     * @property mixed|null $AppSecret
     * @property mixed|null $AppID
     * @property mixed|null $AccessToken
     * @property int        $Type
     * @property int        $PageID
     */
    class MediaInputTwitter extends MediaInput
    {

        public const POSTS_AND_COMMENTS = 0;

        public const POSTS_ONLY = 1;

        private static $table_name = 'MediaInputTwitter';

        private static $db = [
            'Username'          => 'Varchar(255)',
            'AppConsumerKey'    => 'Varchar(255)',
            'AppConsumerSecret' => 'Varchar(255)',
            'AppAccessToken'    => 'Varchar(255)',
            'AppAccessSecret'   => 'Varchar(255)',
            'IncludeRTs'        => 'Boolean',
            'ExcludeReplies'    => 'Boolean',
        ];

        private static $singular_name = 'Twitte Media';

        private static $plural_name = 'Twitte Media';

        private static $summary_fields = [];

        protected $type = 'twitter';

        public function getCMSFields()
        {

            $fields = parent::getCMSFields();

            return $fields;
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
         * @return bool|void
         * @throws \Facebook\Exception\SDKException
         */
        public function fetchUpdates($limit = 100)
        {
            return FacebookFeed::create($this)->fetchUpdates();
        }

        public function getToken()
        {
            return FacebookAccessTokenHandler::create($this)->getAccessToken();
        }


    }
}
