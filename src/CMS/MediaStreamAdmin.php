<?php

namespace Restruct\Silverstripe\MediaStream {

    use SilverStripe\Admin\ModelAdmin;

    class MediaStreamAdmin extends ModelAdmin
    {

        /**
         * @var string[]
         */
        private static $managed_models = [
            MediaUpdate::class,
            FacebookMedia::class,
            InstagramMedia::class,
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
}
