<?php

namespace Restruct\Silverstripe\MediaStream {

    use SilverStripe\Admin\ModelAdmin;

    class MediaInputAdmin extends ModelAdmin
    {

        /**
         * @var string[]
         */
        private static $managed_models = [
            MediaInput::class,
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
}
