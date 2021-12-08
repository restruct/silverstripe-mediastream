<?php

/**
 * https://developers.facebook.com/docs/instagram-basic-display-api/guides/getting-access-tokens-and-permissions/
 * https://developers.facebook.com/docs/instagram-basic-display-api/guides/long-lived-access-tokens
 */

namespace Restruct\Silverstripe\MediaStream\Facebook {

    use GuzzleHttp\Client;
    use Restruct\Silverstripe\MediaStream\InstagramMedia;
    use Restruct\Silverstripe\MediaStream\MediaInputAdmin;
    use Restruct\Silverstripe\MediaStream\MediaInputInstagram;
    use Restruct\Silverstripe\MediaStream\MediaStream;
    use Restruct\Silverstripe\MediaStream\MediaStreamAdmin;
    use SilverStripe\Control\Controller;
    use SilverStripe\Control\Director;
    use SilverStripe\Control\Email\Email;
    use SilverStripe\Core\Injector\Injectable;
    use SilverStripe\Dev\Debug;
    use DateTime;
    use Exception;
    use SilverStripe\SiteConfig\SiteConfig;

    /**
     * @property $ID
     */
    class InstagramAccessTokenHandler extends AccessTokenHandler
    {


        private $baseApiURL = 'https://api.instagram.com/oauth';
        private $baseGraphURL = 'https://graph.instagram.com';

        /**
         * @var MediaInputInstagram
         */
        protected $mediaInput;

        /**
         * @return string|void
         */
        public function getAccessToken()
        {

            $expires = $this->mediaInput->dbObject('TokenExpiryDate');

            $diff = $expires ? (int)$expires->TimeDiffIn('days') : 0;
            if ( $diff < 6 ) {
                return $this->refreshLongLivedToken();
            }

            return $this->mediaInput->AccessToken;

        }

        /**
         * @param $accessToken
         *
         * @return mixed|void|null
         */
        public function getLongLivedToken($accessToken)
        {
            $aOptions = [
                'grant_type'    => 'ig_exchange_token',
                'client_secret' => $this->mediaInput->AppSecret,
                'access_token'  => $accessToken,
            ];

            return $this->processToken('access_token', $aOptions);
        }

        /**
         * @return mixed|void|null
         */
        private function refreshLongLivedToken()
        {

            $aOptions = [
                'grant_type'   => 'ig_refresh_token',
                'access_token' => $this->mediaInput->AccessToken,
            ];

            return $this->processToken('refresh_access_token', $aOptions);
        }


        /**
         * @param string $endpoint
         * @param array  $aOptions
         *
         * @return mixed|void|null
         */
        private function processToken(string $endpoint, array $aOptions)
        {

            $uri = Controller::join_links($this->getBaseGraphURL(), $endpoint . '?' . http_build_query($aOptions));

            try {

                $aResult = $this->getCurlResults($uri);

                $date = new DateTime('+' . $aResult[ 'expires_in' ] . ' seconds');
                $date = $date->format('Y-m-d H:i:s');

                $this->mediaInput->AccessToken = $aResult[ 'access_token' ];
                $this->mediaInput->TokenExpiryDate = $date;
                $this->mediaInput->write();

                return $this->mediaInput->AccessToken;

            } catch ( Exception $e ) {
                //Debug::show($e->getMessage());
            }

        }

        /**
         * Construct redirect URI using current class name - used during OAuth flow.
         *
         * @return string
         */
        public function getRedirectUri(): string
        {

            $class_name = static::sanitiseClassName($this->mediaInput->ClassName);

            return Controller::join_links(static::getAbsoluteLink(), $class_name, 'EditForm/field', $class_name, 'item', $this->mediaInput->ID, 'edit');

        }

        /**
         * @return string
         */
        public function getAuthURL(): string
        {

            return $this->getBaseApiURL() . '/authorize?' . http_build_query([
                    'app_id'        => $this->mediaInput->AppID,
                    'redirect_uri'  => $this->getRedirectUri(),
                    'scope'         => 'user_profile,user_media',
                    'response_type' => 'code',
                ]);
        }

        /**
         * @param $accessCode
         *
         * @return false|mixed
         */
        public function fetchAccessToken($accessCode)
        {

            try {

                return $this->getCurlResults(sprintf("%s/access_token", $this->getBaseApiURL()), 10, [
                    'app_id'       => $this->mediaInput->AppID,
                    'app_secret'   => $this->mediaInput->AppSecret,
                    'grant_type'   => 'authorization_code',
                    'redirect_uri' => $this->getRedirectUri(),
                    'code'         => $accessCode,

                ]);


            } catch ( \Exception $e ) {
                //Debug::show($e->getMessage());
            }


            return false;


        }


        /**
         * @return string
         */
        protected static function getAbsoluteLink(): string
        {
            return Controller::join_links(Director::absoluteBaseURL(), MediaInputAdmin::create()->Link(), '/');
        }

        /**
         * @return string
         */
        public function getBaseApiURL(): string
        {
            return $this->baseApiURL;
        }


        /**
         * @return string
         */
        public function getBaseGraphURL(): string
        {
            return $this->baseGraphURL;
        }


        public function getBaseURL(){
            return $this->baseGraphURL;
        }


    }
}
