<?php

namespace Restruct\Silverstripe\MediaStream {

    use SilverStripe\Dev\BuildTask;
    use SilverStripe\Dev\Debug;

    class SyncJob extends BuildTask
    {

        /**
         * Make the completed job queue another of itself after x seconds
         */
        private static $regenerate_time = 3600; // default every hour

        private $jobStartedTimeStamp;

        /**
         * @var array
         */
        private $mediaInputs;

        /**
         * @return string
         */
        public function getTitle()
        {
            return 'Sync (Social) MediaStream';
        }

        public function run($request)
        {

            $oMediaStream = MediaStream::get()->filter('Enabled', true);

            /** @var MediaStream $item */
            foreach ( $oMediaStream as $item ) {

                echo( "SYNCING: {$item->Name} <br>" );
                echo( $item->fetchUpdates() ?: 'OK' );
            }

        }
    }
}
