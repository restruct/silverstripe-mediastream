<?php

namespace Restruct\Silverstripe\MediaStream {

    use SilverStripe\Assets\Image;
    use SilverStripe\Assets\Storage\AssetStore;
    use SilverStripe\Control\Controller;
    use SilverStripe\Core\Config\Configurable;
    use SilverStripe\Core\Injector\Injectable;
    use Exception;

    abstract class Feed
    {
        use Injectable;
        use Configurable;

        protected $type;

        protected $siteConfig;



        /**
         * In this case: auto-wrap links (@TODO: maybe also include responsive embed code for Youtube links)?
         */
        protected function parseText($rawText)
        {
            $rawText = nl2br($rawText);

            return preg_replace('/https?:\/\/[\w\-\.!~#?&=+\*\'"(),\/]+/', '<a href="$0">$0</a>', $rawText);
        }

        /**
         * @param array $post
         *
         * @return string|null
         * @throws \Exception
         */
        protected static function getCreatedTimestamp(array $post = []): ?string
        {
            $dateFormat = 'Y-m-d H:i:s';
            if ( !empty($post) ) {
                $time = null;
                if ( !empty($post[ 'created_time' ]) ) {
                    $time = $post[ 'created_time' ];
                }
                if ( !empty($post[ 'timestamp' ]) ) {
                    $time = $post[ 'timestamp' ];
                }

                if ( $time ) {
                    return is_object($time) ? $time->format($dateFormat) : date($dateFormat, $time);

                }

            }

            return null;

        }



        abstract public function getPostCreated($post);

        abstract public function getPostUrl($post);

        abstract public function getUserName($post);

        abstract public function getImage($post);


    }
}
