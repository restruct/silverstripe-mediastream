<?php

namespace Restruct\Silverstripe\MediaStream;




use SilverStripe\Core\Config\Config;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;



/**
 * A job for queuedjobs module, if installed this jobs can be scheduled to run the synclogtask
 *
 * Note: it may be necessary to flush the cli cache before calling jobs from the CLI/cron;
 * /usr/bin/php /Users/mic/Sites/goflex.nl/site/framework/cli-script.php / flush=all
 */
class MediaInput_SyncJob
    extends AbstractQueuedJob
{

    /**
     * Make the completed job queue another of itself after x seconds
     */
    private static $regenerate_time = 3600; // default every hour

    /**
     * @return string
     */
    public function getTitle()
    {
        return _t(
            'MediaInput_SyncJob.Title',
            self::class . ' | Sync (Social)MediaStream'
        );
    }

    public function __construct()
    {
        parent::__construct(); // not necessary but prevents code warning in IDE
    }

    public function getSignature()
    {
        // allow scheduling another run of this job (else jobData is identical and the job will be considered to already exist and not be added)
        return $this->randomSignature();
        // return parent::getSignature(); // equals md5(get_class($this) . serialize($this->jobData));
    }

    // This is only executed once for every job. If you want to run something on every job restart, use the prepareForRestart method
    public function setup()
    {
        parent::setup();

        // we're saving the jobStartedTimeStamp to change the jobdata
        $this->jobStartedTimeStamp = time();
        $this->mediaInputs = MediaInput::get()->toArray();
        $this->totalSteps = count($this->mediaInputs);

        // schedule the next run
        $regenerateTime = Config::inst()->get(get_class(), 'regenerate_time');
        $this->addMessage("setup(): scheduling followup run in: " . $regenerateTime/60 . ' mins');
        if ($regenerateTime) {
            if ($regenerateTime < 60) $regenerateTime = self::$regenerate_time; // fallback to default if less than 1 minute
//            $nextJob = Injector::inst()->create(self::class);
            singleton(QueuedJobService::class)->queueJob(new self(), date('Y-m-d H:i:00', time() + $regenerateTime));
        }
    }

    // Typically, the setup() method is used to generate a list of IDs of data objects that are going to have some processing done to them,
    // then each call to process() processes just one of these object
    public function process()
    {
        // run sync one by one
        $this->currentStep = ((int) $this->currentStep +1);
//        foreach($this->mediaInputs as $medinput){
        $mediaInput = $this->mediaInputs[$this->currentStep -1]; // zero-based array
            $this->addMessage("SYNCING: {$mediaInput->Name}");
            $this->addMessage($mediaInput->fetchUpdates() ?: 'OK');
//        }

        if($this->currentStep == $this->totalSteps){
            $this->isComplete = true;
        }
    }
}