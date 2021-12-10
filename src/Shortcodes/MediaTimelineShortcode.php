<?php

namespace Restruct\Silverstripe\MediaStream\Shortcodes {

    use SilverStripe\Forms\FieldList;
    use SilverStripe\Forms\NumericField;
    use SilverStripe\View\ArrayData;
    use SilverStripe\View\Parsers\ShortcodeParser;
    use SilverStripe\View\ViewableData;

    class MediaTimelineShortcode
        extends ViewableData
    {
        /**
         * @config string
         */
        private static $shortcode = 'mediatimeline';

        private static $shortcode_close_parent = true;

        public function singular_name()
        {
            return 'Social media timeline';
        }

        public static $options = [
            'limit' => NumericField::class,
        ];

        private static $placeholder_settings = [
            'width' => '800',
            'height' => '250',
        ];

        private static $labels = [
            'limit' => 'Max amount',
        ];

        private static $defaults = [
            'limit' => null,
        ];


        // Shortcode stuff
        public static function shortcode_attribute_fields()
        {
            $fields = FieldList::create();
            foreach ( self::$options as $name => $fieldClass ) {
                $fields->push($fieldClass::create(
                    $name,
                    ( isset(self::$labels[ $name ]) ? self::$labels[ $name ] : null ),
                    ( isset(self::$defaults[ $name ]) ? self::$defaults[ $name ] : null )
                ));
            }
            return $fields;
        }

        /**
         * Parse the shortcode and render as a string, probably with a template
         *
         * @param array           $attributes the list of attributes of the shortcode
         * @param string          $content    the shortcode content
         * @param ShortcodeParser $parser     the ShortcodeParser instance
         * @param string          $shortcode  the raw shortcode being parsed
         *
         * @return String
         **/
        public static function parse_shortcode($attributes, $content, $parser, $shortcode)
        {
            $arrData = new ArrayData([
                'MediaUpdates' => MediaUpdate::get()->sort('TimeStamp DESC')->limit(80),
            ]);

            return "<div class=\"shortcode-mediatimeline\">" . $arrData->renderWith('MediaUpdatesTimeLine') . "</div>";
        }
    }
}