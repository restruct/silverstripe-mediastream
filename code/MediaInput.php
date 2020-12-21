<?php

namespace Restruct\Silverstripe\MediaStream;






use SilverStripe\Core\ClassInfo;
use Restruct\Silverstripe\MediaStream\MediaInputInterface;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\ORM\DataObject;



/* to actually get the updates, add something like this to your page's index()/init;
 * foreach(MediaInput::get() as $medinput){ $medinput->fetchUpdates(); }
 */

class MediaInput extends DataObject {
	
	protected $inputService;
	
	private static $db = array(
		'Name' => 'Varchar(512)',
		// implemented types (allows extra knowledge on how to handle specific platforms)
		// todo: LinkedIn,Internal,CustomRSS,CustomJSON,CustomXML
		//'Type' => "Enum('Twitter,Facebook')", 
		'Type' => "Varchar()", // Service
		'QueryURL' => 'Varchar(1024)',
		'oAuthConsumerKey' => 'Varchar(1024)',
		'oAuthConsumerSecret' => 'Varchar(1024)',
		'oAuthAccessToken' => 'Varchar(1024)',
		'oAuthAccessSecret' => 'Varchar(1024)',
		'oAuthAccessTokenExpiry' => 'SS_DateTime',
		// probably we should add a mapping to MediaUpdate fields or a template field to render into
	);
	
	static function oauthInstructions(){
		return '<p>...</p>';
	}
	
	public function getCMSFields(){
		$fields = parent::getCMSFields();
		
		$fields->removeByName(array('oAuthAccessTokenExpiry'));
		//$fields->insertBefore($fields->dataFieldByName('CollegeID'), 'Name');
		
		// Get classes implementing MediaInputInterface
		$allclasses = array_keys(ClassInfo::allClasses());
		$classlist = array_filter(
			$allclasses,  
			function ($className) {
				// A bit of a hack, limit to only classes starting with "MediaInput" 
				// (prevents bug where a certain class's file couldn't be found)
				if(stripos($className, MediaInput::class)===false) return false;
				return in_array(MediaInputInterface::class, class_implements($className));
			}
		);
		$classsource = array();
		foreach($classlist as $class){
			$classsource[$class::$mediaInputType] = $class::$mediaInputType;
		}
		// Replace textfield with dropdown
		$fields->replaceField('Type', 
				$drd = new DropdownField('Type','Type',$classsource));
		
		$mediatype = "MediaInput{$this->Type}";
		if(ClassInfo::exists($mediatype)){
			$fields->addFieldToTab('Root.Main', 
				new LiteralField('instructions', $mediatype::oauthInstructions()));
		}
		
		return $fields;
	}
	
	public function validate() {
		if( !$this->Type ||
				!$this->QueryURL ||
				!$this->oAuthConsumerKey ||
				!$this->oAuthConsumerSecret ||
				!$this->oAuthAccessToken ||
				!$this->oAuthAccessSecret){
			return new ValidationResult(false, 
					"All fields need to be set.");
		}
		return parent::validate();
	}
	
	public function setInputService(){
		
		$classname = "MediaInput{$this->Type}";
		if(class_exists($classname)){
			$this->inputService = new $classname;
		}
//		switch($this->Type){
//			case "Twitter":
//				$this->inputService = new MediaInputTwitter();
//				break;
//			case "Facebook":
//				$this->inputService = new MediaInputFacebook();
//				break;
////			case "LinkedIn":
////				$this->inputService = new MediaInputLinkedIn();
////			case "Internal":
////				return "";
////			case "CustomRSS":
////				return "";
////			case "CustomJSON":
////				return "";
////			case "CustomXML":
////				return "";
//		}
	}
	
	public function fetchUpdates(){
		$this->setInputService();
		$this->inputService->setCredentials(
			$this->oAuthConsumerKey,
			$this->oAuthConsumerSecret,
			$this->oAuthAccessToken,
			$this->oAuthAccessSecret);
		$updates = $this->inputService->fetchUpdates($this->QueryURL);
		//Debug::dump($updates);
			
//		// Now write updates to db if new (moved to MediaInput[Twitter])
//		foreach($updates as $update){
//			// check for existing
//			$mediaupdate = MediaUpdate::get()
//					->filter(array('Type'=>$this->Type,'UniqueID'=>$update->ID))
//					->first();
//			// if not exists, create (update as well?)
//			if(!$mediaupdate){ 
//				$mediaupdate = new MediaUpdate();
//				$mediaupdate->Name = "{$this->Type} {$update->ID}";
//				$mediaupdate->DateTime = $update->Date; //new DateTime($update->Date);
//				$mediaupdate->Content = $update->Content;
//				$mediaupdate->Type = $this->Type;
//				$mediaupdate->OriginLink = $update->Link;
//				$mediaupdate->UniqueID = $update->ID;
//				$mediaupdate->write();
//				//Debug::dump($update);
//			}
//		}
	}
	
//	public function onAfterWrite() {
//		parent::onAfterWrite();
//		$this->fetchUpdates();
//	}
	
	public static function curlGet($requestUrl, $timeout=10){
		$ch = curl_init();
		$headers["Content-Length"] = strlen($postString);
		$headers["User-Agent"] = "Curl/1.0";

		curl_setopt($ch, CURLOPT_URL, $requestUrl);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//		curl_setopt($ch, CURLOPT_USERPWD, 'admin:');
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0); // connect timeout unlimited
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); //timeout in seconds (for whole execution)
		$response = curl_exec($ch);
		curl_close($ch);
		
		return $response;
	}
}