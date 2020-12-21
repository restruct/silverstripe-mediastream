<?php

namespace Restruct\Silverstripe\MediaStream;

use SS_Cache;


// Adapted partly from: https://github.com/xeraa/silverstripe-widget_facebookfeed/blob/master/code/FacebookFeedWidget.php

/**
You need to add the ID of the Facebook account you want to display in the CMS. If the URL of your account is https://www.facebook.com/pages/silverstripe/44641219945?ref=ts&v=wall, it's 44641219945. If it's https://www.facebook.com/goflex.nl, it's 'goflex.nl'
You need a Facebook access_token for 
https://developers.facebook.com/apps allows you to generate a new application.
Generate your token: https://graph.facebook.com/oauth/access_token?client_id=YOUR_APP_ID&client_secret=YOUR_APP_SECRET&grant_type=client_credentials
For more details see https://developers.facebook.com/docs/authentication/.
 */

// Require third party lib

class MediaInputFacebook implements MediaInputInterface {
	
	static $mediaInputType = "Facebook";
	
	private $consumerKey, // app ID
			$consumerSecret, // app secret
			$accessToken, // not used, token is re-generated on every request for FB
			$accessSecret; // not used for FB
	
	function setCredentials($consumerKey,$consumerSecret,$accessToken,$accessSecret=false) {
		$this->consumerKey = $consumerKey;
		$this->consumerSecret = $consumerSecret;
		$this->accessToken = $accessToken;
		$this->accessSecret = $accessSecret;
	}
	
	static function oauthInstructions(){
		return '<p>...</p>';
	}
	
	
	/**
	 * We use this app to get/refresh the access token, as they're short-lived by default on FB
	 */
	function getConnection() {
//		$consumerKey = $this->consumerKey;
//		$consumerSecret = $this->consumerSecret;
		//$accessToken = $this->accessToken;
		//$accessSecret = $this->accessSecret;
		
//		$token = file_get_contents("https://graph.facebook.com/oauth/access_token?client_id={$this->consumerKey}&client_secret={$this->consumerSecret}&grant_type=client_credentials");
		
		$token = MediaInput::curlGet("https://graph.facebook.com/oauth/access_token?client_id={$this->consumerKey}&client_secret={$this->consumerSecret}&grant_type=client_credentials");
		// {"access_token":"364224363772629|BEJmJmGViip1efUAmWvIdTXabTk","token_type":"bearer"}
//		return str_replace('access_token=', '', $token);
//        Debug::dump("https://graph.facebook.com/oauth/access_token?client_id={$this->consumerKey}&client_secret={$this->consumerSecret}&grant_type=client_credentials");
//        Debug::dump($token);
//        return $token;
        if($token->access_token) return $token->access_token;
		
		// Turns out FB also allows just sending app key & secret instead of an access token (saves a query)
		return "{$this->consumerKey}|{$this->consumerSecret}";
	}
	
	// Custom Query
	function fetchUpdates($query) {
		// get a reference to the cache for this module
		$cache = SS_Cache::factory('mediastreamcache_'.strtolower(get_class()), 'Output',
				array('automatic_serialization' => true));
		
		$updates = array();
		if (!empty($query)) {

			// we try & get the items from the cache, only get & update then once per ~hour
			// Facebook: graph.fb.etc/[account]/feed (all) or graph.fb.etc/[account]/posts (only own posts)
			$cachekey = md5($query);
			if (!($updates = $cache->load($cachekey))) {
				$accesstoken = $this->getConnection();
				if($accesstoken===false){ return; } // eg try again later...

				if(strpos($query, '?')===false){ $addmark = '?'; } else { $addmark = '&'; }
//				$response = json_decode(file_get_contents($query. "{$addmark}access_token=$accesstoken"));
                $raw_response = MediaInput::curlGet($query. "{$addmark}access_token=$accesstoken");
				$response = json_decode($raw_response);

//                Debug::dump($response);
                // Parse all updates
                if ($response && $response->data) {
					foreach ($response->data as $update) {
						// skip links & events, we need something to show...
						if(!property_exists($update, 'message')) continue;
						//Debug::dump($update);
						$updates[] = $this->getOrCreateMediaUpdate($update);
					}
				} else {
                    var_dump($response);
                    $curClass = get_class();
                    print "ERROR ($curClass)<br>";
                    return user_error("ERROR updating $curClass, raw_response: [ " . print_r($raw_response, true) . ' ]', E_USER_ERROR); //E_USER_WARNING);
                }
				$cache->save($updates, $cachekey);
				//Debug::dump('updated');
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
//		Debug::dump($update);
//		if(strpos($update->message, 'Vos')){
//			Debug::dump($update);
//		}
		// Find or create MediaUpdates for status updates/posts
		$existingupdates = MediaUpdate::get()
//				->filter(array('Type'=>self::$mediaInputType,'UniqueID'=>$update->id))
				// changing to creation time to prevent multiple updates of the same event (e.g. creating album (auto update), and then sharing it... (another update))
				->filter(array('Type'=>self::$mediaInputType,'TimeStamp'=>$update->created_time));
				//->first();
		// if not exists, create (or update as well?)
		if($existingupdates->count()==0){ 
			$mediaupdate = new MediaUpdate();
		} else if ($existingupdates->count() > 1) {
			foreach($existingupdates as $existingupdate) { 
				$existingupdate->delete();
			}
			$mediaupdate = new MediaUpdate();
		} else {
			// update existing
			$mediaupdate = $existingupdates->first();
			//Debug::dump("Found: ".$mediaupdate->UniqueID);
		}
		// store raw input
		$mediaupdate->RawInput = json_encode($update);
		// fill contents
		$mediaupdate->TimeStamp = $update->created_time;
		if(property_exists($update, 'name')){
			$mediaupdate->Title = $update->name;
		}
		$mediaupdate->Content = $this->parseText($update->message);
		$mediaupdate->Type = self::$mediaInputType;
		if(strpos($update->id, '_')!==false){
			$idarr = explode('_', $update->id);
			$mediaupdate->OriginURL = "https://www.facebook.com/{$idarr[0]}/posts/{$idarr[1]}";
		} else {
			// We don't know the right way if the pageID isn't included, so set none...
		}
		$mediaupdate->UniqueID = $update->id;

		// Set youtube embed if type = video (not always youtube...
		if($update->type=="video" && strpos($update->source, 'youtu')!==false){
			$mediaupdate->Content = $this->parseVideo($update->source);
		}

		// set image if update has one
		if($update->type=="photo" && property_exists($update, 'object_id')){
			// get img url from graph
			$accesstoken = $this->getConnection();
//				$imgobj = json_decode(file_get_contents("https://graph.facebook.com/{$update->object_id}?access_token=$accesstoken"));
			$imgobj = json_decode(MediaInput::curlGet("https://graph.facebook.com/{$update->object_id}?access_token=$accesstoken"));
			if(property_exists($imgobj, 'source')) {
				$mediaupdate->ImageURL = $imgobj->source;
			}
		}
//			
		$mediaupdate->write();
		
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
		$source = str_replace('autoplay=1', '', $source);
		return '<div class="embed-responsive embed-responsive-16by9">
			<iframe src="'.$source.'" class="embed-responsive-item" allowfullscreen></iframe>
		</div>';
	}
	
}
