<?php

namespace Restruct\Silverstripe\MediaStream\Facebook {

    use GuzzleHttp\Client;
    use Restruct\Silverstripe\MediaStream\Feed;
    use Restruct\Silverstripe\MediaStream\MediaInput;
    use Restruct\Silverstripe\MediaStream\MediaStream;
    use SilverStripe\Core\Injector\Injectable;
    use SilverStripe\Dev\Debug;
    use Exception;

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
