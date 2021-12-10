<?php

namespace Restruct\Silverstripe\MediaStream\Admin;

use Restruct\Silverstripe\MediaStream\Model\MediaInputFacebook;
use Restruct\Silverstripe\MediaStream\Model\MediaInputInstagram;
use Restruct\Silverstripe\MediaStream\Model\MediaUpdate;
use SilverStripe\Admin\ModelAdmin;

class MediaInputAdmin extends ModelAdmin
{

    /**
     * @var string[]
     */
    private static $managed_models = [
        MediaUpdate::class,
        MediaInputFacebook::class,
        MediaInputInstagram::class,
    ];

    /**
     * @var string
     */
    private static $url_segment = 'media-stream';

    /**
     * @var string
     */
    private static $menu_title = 'Media Stream';

}

