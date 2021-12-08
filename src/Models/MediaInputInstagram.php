<?php

/**
 * For Instagram add the "Instagram Basic Display" product
 * Get Valid OAuth Redirect URIs from ModelAdmin
 *
 */

namespace Restruct\Silverstripe\MediaStream {


    use GuzzleHttp\Exception\GuzzleException;
    use Restruct\Silverstripe\MediaStream\Facebook\AccessTokenHandler;
    use Restruct\Silverstripe\MediaStream\Facebook\InstagramAccessTokenHandler;
    use Restruct\Silverstripe\MediaStream\Facebook\InstagramFeed;
    use Restruct\Silverstripe\MediaStream\Providers\ProviderInterface;
    use SilverStripe\Control\Controller;
    use SilverStripe\Control\Director;
    use SilverStripe\Core\Convert;
    use SilverStripe\Dev\Debug;
    use SilverStripe\Forms\DropdownField;
    use SilverStripe\Forms\LiteralField;
    use SilverStripe\Forms\ReadonlyField;
    use SilverStripe\Forms\RequiredFields;
    use SilverStripe\ORM\FieldType\DBField;
    use SilverStripe\ORM\ValidationException;

    /**
     * @property mixed|null $AppID
     * @property mixed|null $AppSecret
     * @property mixed      $UserID
     * @property mixed|null $AccessToken
     */
    class MediaInputInstagram extends MediaInput implements ProviderInterface
    {

        public const POSTS_AND_COMMENTS = 0;

        public const POSTS_ONLY = 1;

        private static $table_name = 'InstagramMedia';

        private static $db = [
            'AppID'       => 'Varchar(400)',
            'AppSecret'   => 'Varchar(400)',
            'AccessToken' => 'Varchar(400)',
            'UserID'      => 'Varchar(400)',
        ];

        private static $singular_name = 'Instagram Media';

        private static $plural_name = 'Instagram Media';

        protected $type = 'Instagram';

        private static $summary_fields = [];

        /**
         * @return \SilverStripe\Forms\FieldList
         */
        public function getCMSFields()
        {

            $oAccessTokenHandler = InstagramAccessTokenHandler::create($this);

            $fields = parent::getCMSFields();

            $fields->addFieldsToTab('Root.Main', [
                ReadonlyField::create('AccessToken'),
                ReadonlyField::create('TokenExpiryDate'),
                ReadonlyField::create('LastSynced'),
                ReadonlyField::create('UserID'),
            ]);

            $fields->addFieldToTab('Root.Main', new LiteralField('sf_html_2', '<p>You\'ll need to add the following redirect URI <strong><code> ' . $oAccessTokenHandler->getRedirectUri() . ' </code> </strong>in the settings for the Instagram App.</p>'));

            if ( $this->AppID && $this->AppSecret ) {

                $fields->addFieldToTab('Root.Main', new LiteralField('sf_html_3', '<p><a href="' . $oAccessTokenHandler->getAuthURL() . '"><button type="button">Authorize App to get Access Token</a></button>'), 'Label');
            }

            $aData = Convert::raw2sql(filter_input_array(INPUT_GET));

            if ( !empty($aData[ 'code' ]) ) {
                $code = $aData[ 'code' ];

                try {

                    $aAccessToken = $oAccessTokenHandler->fetchAccessToken($code);
                    if ( empty($aAccessToken[ 'error_type' ]) ) {


                        $short_lived_access_token = $aAccessToken[ 'access_token' ];
                        $user_id = $aAccessToken[ 'user_id' ];

                        $oAccessTokenHandler->getLongLivedToken($short_lived_access_token);
                        $this->UserID = $user_id;
                        $this->write();
                    }
                    Controller::curr()->redirect($oAccessTokenHandler->getRedirectUri());


                } catch ( GuzzleException $e ) {
                } catch ( ValidationException $e ) {
                }

            }


            return $fields;
        }

        /**
         * @param int $limit
         *
         * @return bool|void
         * @throws ValidationException
         * @throws \JsonException
         */
        public function fetchUpdates($limit=100)
        {
            return InstagramFeed::create($this)->fetchUpdates($limit);
        }


        /**
         * @param $post
         *
         * @return mixed
         */
        public function getPostContent($post)
        {
            // TODO: Implement getPostContent() method.
        }

        /**
         * @param $post
         *
         * @return mixed
         */
        public function getPostCreated($post)
        {
            // TODO: Implement getPostCreated() method.
        }

        /**
         * @param $post
         *
         * @return mixed
         */
        public function getPostUrl($post)
        {
            // TODO: Implement getPostUrl() method.
        }

        /**
         * @param $post
         *
         * @return mixed
         */
        public function getUserName($post)
        {
            // TODO: Implement getUserName() method.
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
         * @return string|void|null
         */
        public function getToken()
        {
            return InstagramAccessTokenHandler::create($this)->getAccessToken();
        }

    }
}
