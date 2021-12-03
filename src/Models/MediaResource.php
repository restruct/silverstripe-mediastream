<?php

namespace Restruct\Silverstripe\MediaStream;

use SilverStripe\Assets\Image;
use Page;
use SilverStripe\ORM\DataObject;

/**
 * @method Image Image()
 * @property string $URL
 */
class MediaResource extends DataObject
{
    private static $table_name = 'MediaResource';

    private static $default_sort = 'Sort';

    private static $db = [
        'Name' => 'Varchar(255)',
        'Sort' => 'Int',
    ];

    private static $has_one = [
        'Image'       => Image::class,
        'MediaUpdate' => MediaUpdate::class,
    ];
    private static $owns = [
        'Image',
    ];

    private static $cascade_deletes = [
        'Image',
    ];

    public function getCMSFields()
    {
        $f = parent::getCMSFields();
        $f->removeByName([
            'Sort',
            'MediaUpdateID',
        ]);

        return $f;
    }

    private static $summary_fields = [
        'Thumbnail',
        'Name',
    ];

    public function getThumbnail()
    {
        $image = $this->Image();
        if ( $image && $image->ID ) {
            return $image->CMSThumbnail();
        }
    }

    /**
     * @param array $aImageData
     *
     * @return DataObject|null
     */
    public static function find(array $aImageData)
    {

        $item = static::get()->filter($aImageData)->first();

        if ( $item ) {
            return $item;
        }

        return null;

    }


}
