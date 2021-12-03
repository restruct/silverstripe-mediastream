<?php

namespace Restruct\Silverstripe\MediaStream\Facebook {

    use GuzzleHttp\Client;
    use Restruct\Silverstripe\MediaStream\FacebookMedia;
    use Restruct\Silverstripe\MediaStream\MediaStream;
    use SilverStripe\Core\Injector\Injectable;
    use SilverStripe\Dev\Debug;
    use Exception;

    class FacebookAccessTokenHandler extends AccessTokenHandler
    {

        private $baseApiURL = 'https://graph.facebook.com';

        /**
         * @var FacebookMedia
         */
        protected $media;

        public function __construct($media = null)
        {
            parent::__construct($media);

            $this->setClient(new Client([
                'base_uri' => $this->getBaseApiURL(),
            ]));

            if ( null !== $media ) {
                $this->media = $media;
            }
        }

        /**
         * @return string
         */
        public function getBaseApiURL(): string
        {
            return $this->baseApiURL;
        }

        /**
         * @return string|void
         */
        public function getAccessToken()
        {
            if ( $this->media->AccessToken ) {
                return $this->getLongLivedToken();
            }

            return $this->media->AccessToken;

        }

        /**
         * @return mixed|void|null
         */
        private function getLongLivedToken()
        {

            try {

                $url = sprintf("%s/oauth/access_token?%s", $this->getBaseApiURL(), http_build_query([
                    'client_id'         => $this->media->AppID,
                    'client_secret'     => $this->getMedia()->AppSecret,
                    'grant_type'        => 'fb_exchange_token',
                    'fb_exchange_token' => $this->getMedia()->AccessToken,
                ]));

                $aResult = $this->getCurlResults($url);
                $access_token = $aResult[ 'access_token' ];

                return $this->getPageAccessToken($access_token);

            } catch ( \Exception $e ) {
                //Debug::show($e->getMessage());
            }

        }

        /**
         * @param $access_token
         *
         * If you used a long-lived User access token, the Page access token has no expiration date.
         *
         * @return mixed|void|null
         */
        private function getPageAccessToken($access_token)
        {
            $app_secret_proof = hash_hmac('sha256', $access_token, $this->media->AppSecret);

            $aData = [
                'fields'          => 'access_token',
                'appsecret_proof' => $app_secret_proof,
                'access_token'    => $access_token,
            ];

            try {

                $url = sprintf("%s/%s?%s", $this->getBaseApiURL(), $this->media->PageID, http_build_query($aData));

                $aResult = $this->getCurlResults($url);
                $this->media->AccessToken = $aResult[ 'access_token' ];
                $this->media->write();

                return $this->media->AccessToken;

            } catch ( Exception $e ) {
                //Debug::show($e->getMessage());
            }

        }

        /**
         * @return FacebookMedia
         */
        public function getMedia(): FacebookMedia
        {
            return $this->media;
        }

    }
}
