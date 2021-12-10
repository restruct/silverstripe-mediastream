<?php

namespace Restruct\Silverstripe\MediaStream\Extensions;

use Restruct\Silverstripe\MediaStream\Model\MediaUpdate;
use SilverStripe\Core\Extension;
use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;

class PageControllerExtension
    extends Extension
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
//            'MediaUpdates' => MediaUpdate::get()->sort('TimeStamp DESC')->limit(80),
            'MediaUpdates' => MediaUpdate::get_updates(80),
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

         return MediaUpdate::get_updates($limit);
    }

    public function loadmediaupdates()
    {
        return $this->owner->renderWith(SSViewer::fromString('<% include MediaUpdatesTimeLine %>'));
    }
}
