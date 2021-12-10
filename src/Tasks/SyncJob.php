<?php

namespace Restruct\Silverstripe\MediaStream\Tasks;

use Restruct\Silverstripe\MediaStream\Model\MediaInput;
use SilverStripe\Dev\BuildTask;

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
        return 'Sync (Social) Mediastream';
    }

    public function run($request)
    {

        $oMediaStream = MediaInput::get()->filter('Enabled', true);

        /** @var MediaInput $item */
        foreach ( $oMediaStream as $item ) {

            echo( "SYNCING: {$item->Name} <br>" );
            echo( $item->fetchUpdates() ?: 'OK' );
        }


        return true;

    }
}
