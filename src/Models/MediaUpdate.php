<?php

namespace Restruct\Silverstripe\MediaStream;

use SilverStripe\Assets\Image;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\DataObject;

/**
 * @method DataList Images()
 */
class MediaUpdate extends DataObject
{

    private static $table_name = 'MediaUpdate';

    private static $db = [
        'Title'     => 'Varchar(1024)', // Optional, for reference
        'Content'   => 'HTMLText',
        'TimeStamp' => 'DBDatetime',
        'Type'      => 'Varchar(512)', // eg 'Facebook', 'Twitter', 'Block'
        'MediaType' => 'Varchar(50)', // eg image, video, etc (currently only implemented for Instagram)
        'OriginURL' => 'Varchar(512)',
        'ImageURL'  => 'Varchar(512)',
        'UniqueID'  => 'Varchar(512)', // identifier
        'RawInput'  => 'Text', // Raw Update input (eg JSON from API)
    ];

    private static $default_sort = 'TimeStamp DESC';

    private static $has_one = [
        'MediaStream' => MediaStream::class,
    ];

    private static $many_many = [
        'Images' => Image::class,
    ];

    private static $casting = [
        'DisplayDateTime' => 'DBDatetime',
    ];

    private static $summary_fields = [
        'Type'            => 'Type',
        'PreviewPicture'  => 'Picture',
        'TimeStamp'       => 'Date',
        'Content.Summary' => 'Summary',
        //'OriginLink' => 'Link',
        'UniqueID'        => 'ID (per type)',
    ];

    // Just a little helper to
    public function LocalTimeStamp()
    {
        // for Translate date
        return DBField::create_field('LocalDatetime', $this->TimeStamp);
    }

    public function PreviewPicture()
    {
        if ( $this->ImageURL ) {
            return DBField::create_field('HTMLText', "<img src=\"{$this->ImageURL}\" style=\"max-height:60px;height:auto;width:auto;\" />");
        }
    }

    public function getDisplayDateTime()
    {

    }

}
