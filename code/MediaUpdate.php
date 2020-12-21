<?php

namespace Restruct\Silverstripe\MediaStream;



use Restruct\Silverstripe\MediaStream\MediaInput;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\DataObject;



class MediaUpdate extends DataObject{
	
	private static $db = array(
		'Title' => 'Varchar(1024)', // Optional, for reference
		'Content' => 'HTMLText',
		'TimeStamp' => 'SS_DateTime',
		'Type' => 'Varchar(512)', // eg 'Facebook', 'Twitter', 'Block'
        'MediaType' => 'Varchar(50)', // eg image, video, etc (currently only implemented for Instagram)
		'OriginURL' => 'Varchar(512)',
		'ImageURL' => 'Varchar(512)',
		'UniqueID' => 'Varchar(512)', // identifier
		'RawInput' => 'Text', // Raw Update input (eg JSON from API)
	);
	
	private static $default_sort = 'TimeStamp DESC';
	
	private static $has_one = array(
		'MediaInput' => MediaInput::class,
		'InternalLink' => SiteTree::class, // eg a newsitem
	);
	
//	private static $has_many = array(
//		'OpenDays' => 'OpenDay',
//	);

	private static $summary_fields = array(
		'Type' => 'Type',
		'PreviewPicture' => 'Picture',
		'TimeStamp' => 'Date',
		'Content.Summary' => 'Summary',
		//'OriginLink' => 'Link',
		'UniqueID' => 'ID (per type)',
	);

	// Just a little helper to
	public function LocalTimeStamp(){
		// for Translate date
		return DBField::create_field('LocalDatetime', $this->TimeStamp);
	}

	public function PreviewPicture(){
		if($this->ImageURL){
			return DBField::create_field('HTMLText', "<img src=\"{$this->ImageURL}\" style=\"max-height:60px;height:auto;width:auto;\" />");
		}
	}
	
//	private static $searchable_fields = array(
////		'College.Name:PartialMatch',
//		'Name' => array('title'=>'College'),
//		'Name' => array('title'=>'Location'),
//		'StreetAddress' => array('title'=>'Address'),
////		'Address' => array('title'=>'Address'),
//		'City' => array('title'=>'City'),
//	);
	
//	public function getCMSFields(){
//		$fields = parent::getCMSFields();
//		
//		// remove empty locations
//		//foreach(Location::get() as $loc){ if(empty($loc->StreetAddress)){ $loc->delete(); }}
//		
//		$fields->removeByName(array('GPS'));
//		
//		$fields->insertBefore($fields->dataFieldByName('CollegeID'), 'Name');
//		$fields->insertBefore(new LiteralField('Info',
//				'<p class="message info">Fill out as many details as possible and click "search"
//					to pinpoint the location on a map.</p>'), 
//				'CollegeID');
//		
//		$fields->dataFieldByName('Name')->setRightTitle('Optional location/building name');
//		$fields->addFieldToTab('Root.Main', $llfield = new LatLongField('GPS','Position (lat.,long.)'));
//		$llfield->setRightTitle('Click "Search" to lookup the entered address on the map');
//		$llfield->setInputSelector("$('#Form_ItemEditForm_StreetAddress').val()
//						+ ' ' + $('#Form_ItemEditForm_Postcode').val()
//						+ ' ' + $('#Form_ItemEditForm_City').val()");
//		
//		return $fields;
//	}
	
}
