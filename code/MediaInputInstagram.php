<?php

namespace Restruct\Silverstripe\MediaStream;

use SS_Cache;


class MediaInputInstagram
    implements MediaInputInterface {
	
	static $mediaInputType = "Instagram";
	
	private $consumerKey, // app ID
			$consumerSecret, // app secret
			$accessToken, // 
			$accessSecret; // not used for FB
	
	private $querystring; // extra field
	
	function setCredentials($consumerKey,$consumerSecret,$accessToken,$accessSecret=false) {
		$this->consumerKey = $consumerKey;
		$this->consumerSecret = $consumerSecret;
		$this->accessToken = $accessToken;
		$this->accessSecret = $accessSecret;
	}
	
	static function oauthInstructions(){
		return '<p>To get an access token: https://instagram.com/oauth/authorize/?client_id=CLIENT-ID&redirect_uri=REDIR-URI&scope=public_content&response_type=token<br />
			To get the userID for a user-handle: https://api.instagram.com/v1/users/search/?q=HANDLE&client_id=CLIENT-ID</p>';
	}
	
	function getConnection() {
		if(strpos($this->querystring, '?')===false){ $addmark = '?'; } else { $addmark = '&'; }
		return $this->querystring. "{$addmark}access_token=".$this->accessToken;
	}
	
	// Custom Query
	function fetchUpdates($query) {
		$this->querystring = $query;
		
		// get a reference to the cache for this module
		$cache = SS_Cache::factory('mediastreamcache_'.strtolower(get_class()), 'Output',
				array('automatic_serialization' => true));
		
		$query = $this->getConnection();

		$updates = array();
		if (!empty($query)) {
			// we try & get the items from the cache, only get & update then once per ~hour
			$cachekey = md5($query);
			if (!($updates = $cache->load($cachekey)) || true) { // testing
                $raw_response = MediaInput::curlGet($query);
				$response = json_decode($raw_response);
//				Debug::dump($response);
				// Check if OK
				if ($response && $response->meta && $response->meta->code != 200) { // Log error
//					user_error("MediaInputInstagram API error: {$response->meta->error_type} ({$response->meta->code}) {$response->meta->error_message}", E_USER_WARNING);
                    $curClass = get_class();
                    print "ERROR ($curClass)<br>";
                    return user_error("$curClass API error: {$response->meta->error_type} ({$response->meta->code}) {$response->meta->error_message} " . print_r($response, true), E_USER_ERROR); //E_USER_WARNING);
				}
				// Parse all updates
				if ($response && $response->data) {
					foreach ($response->data as $update) {
						// skip links & events, we need something to show...
						if(!property_exists($update, 'images')) continue;
						//Debug::dump($update);
						$updates[] = $this->getOrCreateMediaUpdate($update);
					}
				} else {
                    $curClass = get_class();
                    print "ERROR ($curClass)<br><br>API response: ".print_r($response, true);
//                    return user_error("ERROR updating $curClass, raw_response: [ " . print_r($raw_response, true) . ' ]', E_USER_ERROR); //E_USER_WARNING);
                    return;
                }
				$cache->save($updates, $cachekey);
//				Debug::dump($updates);
//				Debug::dump('updated');
			}
		}
        // clear memory
        unset($cache);

        return;
		//return $tweets;
	}
	
	/**
	 * Converts an update into a mediaupdate
	 */
	public function getOrCreateMediaUpdate($update) {
		//Debug::dump($update['id']);
		//Debug::dump($update);
		
		// Find or create MediaUpdates for status updates/posts
		$mediaupdate = MediaUpdate::get()
				->filter(array('Type'=>self::$mediaInputType,'UniqueID'=>$update->id))
				->first();

		// TMP fix duplicates
		$firstskipped = false;
		foreach(MediaUpdate::get()->filter(array('Type'=>self::$mediaInputType,'UniqueID'=>$update->id)) as $upd) {
		    if( ! $firstskipped){
		        $firstskipped = true;
		        continue;
            } else {
                $upd->delete();
            }
        }

//		Debug::dump($mediaupdate);
		// if not exists, create (or update as well?)
		if(!$mediaupdate) {
            $mediaupdate = new MediaUpdate();
        }
        // store raw input
        $mediaupdate->RawInput = json_encode($update);
        // fill contents
        $mediaupdate->TimeStamp = $update->created_time;
//			$mediaupdate->Title = $this->parseText($update->caption->text);
        $mediaupdate->Title = '';
        $mediaupdate->Content = $this->parseText($update->caption->text);
        $mediaupdate->Type = self::$mediaInputType;
        $mediaupdate->MediaType = $update->type;
        $mediaupdate->OriginURL = $update->link;
        $mediaupdate->UniqueID = $update->id;

        // Set youtube embed if type = video (not always youtube...
        if($update->type=="video"){
            $mediaupdate->Content = $this->parseVideo($update->videos->standard_resolution) . $mediaupdate->Content;
        }

        // set image if update has one
        if(in_array($update->type, ["image", "video", "carousel"]) && property_exists($update, 'images')){
            $mediaupdate->ImageURL = $update->images->standard_resolution->url;
        }

//        // set image if carousel
//        if($update->type=="carousel" && property_exists($update, 'images')){
//            $mediaupdate->ImageURL = $update->images->standard_resolution->url;
//        }

        //Debug::dump("Found: ".$mediaupdate->UniqueID);
        $mediaupdate->write();
			
//		} else {
////			Debug::dump("Found: ".$mediaupdate->UniqueID);
//		}
		return $mediaupdate;
	}
	
	////// HELPERS

	/**
	 * In this case: auto-wrap links (@TODO: maybe also include responsive embed code for Youtube links)?
	 */
	protected function parseText($rawText) {
		$rawText = nl2br($rawText);
		return preg_replace('/https?:\/\/[\w\-\.!~#?&=+\*\'"(),\/]+/','<a href="$0">$0</a>',$rawText);
	}
	
	protected function parseVideo($source){
		// stdclass obj with url, height & width props
		//print('<!--'.print_r($source).'-->');
		if(property_exists($source,'url')) {
			return '<video controls style="max-width:100%; height:auto;">
				<source src="' . $source->url . '" type="video/mp4">
			</video>';
		}
	}
	
}
