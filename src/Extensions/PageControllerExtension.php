<?php

namespace Restruct\Silverstripe\MediaStream;

use BlocksPageController;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;

class PageControllerExtension extends Extension
{

    private static $allowed_actions = [
//        'syncmediaupdates',
//        'syncmediaupdates' => 'ADMIN',
        'loadmediaupdates',
    ];

    /**
     * @param      $arguments
     * @param null $content
     * @param null $parser
     * @param      $tagName
     *
     * @return string
     */
    public static function MediaTimelineShortCodeHandler($arguments, $content = null, $parser = null, $tagName)
    {
        $arrData = new ArrayData([
            'MediaUpdates' => MediaUpdate::get()->sort('TimeStamp DESC')->limit(80),
        ]);

        return "<div class=\"shortcode-mediatimeline\">" . $arrData->renderWith('MediaUpdatesTimeLine') . "</div>";
    }

    /**
     * @param int $limit
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function MediaUpdates($limit = 20)
    {

         return MediaUpdate::get()->sort('TimeStamp DESC')->limit($limit);
    }

    public function loadmediaupdates()
    {
        return $this->owner->renderWith(SSViewer::fromString('<% include MediaUpdatesTimeLine %>'));
    }
}
