<?php

namespace Restruct\Silverstripe\MediaStream\AccessTokens {

    use GuzzleHttp\Client;
    use Restruct\Silverstripe\MediaStream\Model\MediaInput;
    use SilverStripe\Core\Injector\Injectable;

    abstract class AccessTokenHandler
    {
        use Injectable;

        /**
         * @var Client
         */
        protected $client;

        /**
         * @var MediaInput
         */
        protected $mediaInput;

        public function __construct($mediaInput = null)
        {
            $this->setClient(new Client([
                'base_uri' => $this->getBaseURL(),
            ]));

            if ( null !== $mediaInput ) {
                $this->mediaInput = $mediaInput;
            }
        }

        /**
         * @return Client
         */
        public function getClient(): Client
        {
            return $this->client;
        }

        /**
         * @param Client $client
         */
        public function setClient(Client $client): void
        {
            $this->client = $client;
        }

        protected static function sanitiseClassName($class)
        {
            return str_replace('\\', '-', $class);
        }

        /**
         * @param string $requestUrl
         * @param int    $timeout
         * @param array  $aPostFields
         *
         * @return mixed
         */
        protected function getCurlResults(string $requestUrl, int $timeout = 10, array $aPostFields = [])
        {
            return MediaInput::getCurlResults($requestUrl, $timeout, $aPostFields);
        }

    }
}
