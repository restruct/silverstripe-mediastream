<?php

namespace Restruct\Silverstripe\MediaStream\Facebook {

    use GuzzleHttp\Client;
    use Restruct\Silverstripe\MediaStream\Feed;
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

        protected $media;

        public function __construct($media = null)
        {

            if ( null !== $media ) {
                $this->media = $media;
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

        abstract public function getMedia();


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
         * @throws \JsonException
         */
        protected function getCurlResults(string $requestUrl, int $timeout = 10, array $aPostFields = [])
        {

            return Feed::getCurlResults($requestUrl, $timeout, $aPostFields);
        }

    }
}
