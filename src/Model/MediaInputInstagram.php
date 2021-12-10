<?php

/**
 * For Instagram add the "Instagram Basic Display" product
 * Get Valid OAuth Redirect URIs from ModelAdmin
 *
 * https://developers.facebook.com/docs/instagram-basic-display-api/overview
 *
 */

namespace Restruct\Silverstripe\MediaStream\Model {

    use GuzzleHttp\Exception\GuzzleException;
    use Restruct\Silverstripe\MediaStream\AccessTokens\InstagramAccessTokenHandler;
    use SilverStripe\Control\Controller;
    use SilverStripe\Core\Convert;
    use SilverStripe\Forms\LiteralField;
    use SilverStripe\ORM\ArrayList;
    use SilverStripe\ORM\ValidationException;
    use DateTime;

    /**
     * @property mixed|null $AppID
     * @property mixed|null $AppSecret
     * @property mixed      $UserID
     * @property mixed|null $AccessToken
     */
    class MediaInputInstagram extends MediaInput
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
            'children',
            'caption',
        ];

        public const POSTS_AND_COMMENTS = 0;

        public const POSTS_ONLY = 1;

        private static $table_name = 'MediaInputInstagram';

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

//            $fields->addFieldsToTab('Root.Main', [
//                ReadonlyField::create('AccessToken'),
//                ReadonlyField::create('TokenExpiryDate'),
//                ReadonlyField::create('LastSynced'),
//                ReadonlyField::create('UserID'),
//            ]);

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
         */
        public function fetchUpdates($limit = 80)
        {
            parent::fetchUpdates();
            $url = sprintf('%s/%s/%s?%s', $this->getEndPoint(), $this->UserID, 'media', $this->getQueryParameters());

            $aResultData = static::getCurlResults($url);
            var_dump($aResultData);die();
            if ( !empty($aResultData[ 'data' ]) ) {
                $aResult = $aResultData[ 'data' ];
                $oUpdates = ArrayList::create();

                foreach ( $aResult as $post ) {
                    $aData = $this->getPostData($post);
                    $media_type = $aData[ 'MediaType' ];
                    $image = ( $media_type === 'Video' ) ? $this->getThumbnailImage($post) : $this->getImage($post);
                    $aData[ 'ImageURL' ] = $image;
                    $aData[ 'MediaStreamID' ] = $this->ID;

                    if ( !empty($post[ 'children' ]) ) {
                        $this->getMediaObects($post[ 'children' ][ 'data' ]);
                    }

                    if ( $aData[ 'MediaType' ] === "Video" ) {
                        $media_url = $post[ 'media_url' ];
                        $aData[ 'Content' ] = static::parseVideo($media_url) . $this->getPostContent($post);
                    }

                    $oUpdates->push($this->getOrCreateMediaUpdate($this, $aData));

                }

                $date = new DateTime();
                $dateTime = $date->format('Y-m-d H:i:s');
                $this->LastSynced = strtotime($dateTime);
                $this->write();

                return true;
            }

        }

        public function getMediaObects($aMediaIds)
        {
            if ( !empty($aMediaIds) ) {
                foreach ( $aMediaIds as $aMediaId ) {
                    $id = $aMediaId['id'];

                    Debug::show($id);
                }
            }


            return true;
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

            if ( $this->LastSynced ) {
                //$aQueryParameters[ 'since' ] = $this->LastSynced;
            }

            return http_build_query($aQueryParameters);
        }


        public function getImage($post)
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
        public function getPostCreated($post)
        {
            return $post[ 'timestamp' ] ?? null;
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
         * @return string|void|null
         */
        public function getToken()
        {
            return InstagramAccessTokenHandler::create($this)->getAccessToken();
        }

    }
}
