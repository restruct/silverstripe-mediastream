<?php

namespace Restruct\Silverstripe\MediaStream;

use Exception;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;

/**
 * @property mixed|null $Limit
 * @property string     $TokenExpiryDate
 * @property mixed|null $LastSynced
 * @property mixed|null $Name
 * @property mixed|null $LiveStream
 */
class MediaStream extends DataObject
{

    private static $table_name = "MediaStream";

    private static $db = [
        'Name'            => 'Varchar(100)',
        'Enabled'         => 'Boolean',
        'LiveStream'      => 'Boolean',
        'Limit'           => 'Int',
        'UpdateFrequency' => 'Enum("1 hour,3 hours,6 hours,12 hours,1 day,5 day","1 hour")',
        'LastSynced'      => 'DBDatetime',
        'TokenExpiryDate' => 'DBDatetime',
    ];

    private static $has_many = [
        'MediaUpdates' => MediaUpdate::class,
    ];

    private static $summary_fields = [
        'Name',
        'Enabled',
        'Limit',
        'UpdateFrequency',
        'LastSynced',
    ];

    /**
     * Then length of time it takes for the cache to expire
     *
     * @var int
     */
    private static $default_cache_lifetime = 900; // 15 minutes (900 seconds)

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {

        $fields = parent::getCMSFields();

        return $fields;
    }

    public function getThumbnailImage($post)
    {
        return $post[ 'media_url' ];
    }

    /**
     * @param $post
     *
     * @return mixed
     */
    public function getMediaURI($post)
    {
        $media_type = $post[ 'media_type' ] ?? '';

        return ( $media_type === 'VIDEO' ) ? $post[ 'thumbnail_url' ] : $this->getImage($post);
    }


    /**
     * @param $url
     * @param $width
     * @param $height
     */
    public function ImageResizer($url, $width, $height)
    {

        header('Content-type: image/jpeg');

        [ $width_orig, $height_orig ] = getimagesize($url);

        $ratio_orig = $width_orig / $height_orig;

        if ( $width / $height > $ratio_orig ) {
            $width = $height * $ratio_orig;
        } else {
            $height = $width / $ratio_orig;
        }

        // This resamples the image
        $image_p = imagecreatetruecolor($width, $height);
        $image = imagecreatefromjpeg($url);
        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

        // Output the image
        imagejpeg($image_p, null, 100);
        imagedestroy($image_p);
    }


    public function fetchUpdates($limit=80)
    {
        user_error('Please implement method fetchUpdates in your sub_class');
    }

}
