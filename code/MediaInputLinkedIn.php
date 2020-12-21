<?php

namespace Restruct\Silverstripe\MediaStream;




// Don't get any of the LinkedIn API, all examples are dead links... scr*w them.

// Maybe some day I will implement this;
// https://github.com/linkedin/api-get-started/blob/master/php/tutorial.php
// https://www.drupal.org/node/2358227

/**
You need to add the ID of the page

Generate a new application https://www.linkedin.com/developer/apps/new
 * Get access token: https://developer.linkedin.com/docs/oauth2
 * https://github.com/sunnysideup/silverstripe-social_integration/blob/master/code/control/LinkedinCallback.php
 * https://github.com/joomla-framework/linkedin-api/blob/master/src/Companies.php
 * 
 * https://api.linkedin.com/v1/companies?is-company-admin=true&format=json&oauth2_access_token=AQXzBpCaxpa7bgAVdRPZv30f9nI2s4_KC8TZI9RXvhn5ITwinF4QsSJH73eY4D7KM10mkWVpctxT4CIrr-o2zOkpQqbhYZgpK8pZFwTxAIevTU4og-v1hsCGnj-bgRpZYQEYn91wJA2e4Jk3jrRBmRW61TJ49CNpUe4UXwwGeIAfGmg3aVE
 * 
 * https://api.linkedin.com/v1/companies/1584435/updates/?format=json&oauth2_access_token=AQXzBpCaxpa7bgAVdRPZv30f9nI2s4_KC8TZI9RXvhn5ITwinF4QsSJH73eY4D7KM10mkWVpctxT4CIrr-o2zOkpQqbhYZgpK8pZFwTxAIevTU4og-v1hsCGnj-bgRpZYQEYn91wJA2e4Jk3jrRBmRW61TJ49CNpUe4UXwwGeIAfGmg3aVE

Get updates: http://api.linkedin.com/v1/companies/1064689/updates/
 * https://api.linkedin.com/v1/companies/1337/updates?start=20&count=10&format=json
 * http://stackoverflow.com/questions/27892989/linkedin-get-companies-updates-queries

For more details see https://developer.linkedin.com/docs/rest-api & https://developer.linkedin.com/docs/oauth2
 * 
 * 
 * Example result;
 * {
  "_total": 2,
  "values": [
    {
      "isCommentable": false,
      "isLikable": false,
      "timestamp": 1431983246118,
      "updateContent": {
        "company": {
          "id": 1584435,
          "name": "Restruct Web"
        },
        "companyStatusUpdate": {"share": {
          "comment": "Update with image",
          "content": {
            "description": "restruct.nl",
            "eyebrowUrl": "https://restruct.nl/soon2/icons/restruct_logo.png",
            "shortenedUrl": "https://restruct.nl/soon2/icons/restruct_logo.png",
            "submittedImageUrl": "https://restruct.nl/soon2/icons/restruct_logo.png",
            "submittedUrl": "https://restruct.nl/soon2/icons/restruct_logo.png",
            "thumbnailUrl": "https://media.licdn.com/media-proxy/ext?w=80&h=100&hash=okbcyj4MpprH7bKPR83AckaNJqA%3D&url=https%3A%2F%2Frestruct.nl%2Fsoon2%2Ficons%2Frestruct_logo.png",
            "title": "restruct.nl"
          },
          "id": "s6006173057050230784",
          "source": {
            "serviceProvider": {"name": "LINKEDIN"},
            "serviceProviderShareId": "s6006173057050230784"
          },
          "timestamp": 1431983246118,
          "visibility": {"code": "anyone"}
        }}
      },
      "updateKey": "UPDATE-c1584435-6006173056987320320",
      "updateType": "CMPY"
    },
    {
      "isCommentable": false,
      "isLikable": false,
      "timestamp": 1431983032724,
      "updateContent": {
        "company": {
          "id": 1584435,
          "name": "Restruct Web"
        },
        "companyStatusUpdate": {"share": {
          "comment": "Test",
          "id": "s6006172162048675840",
          "source": {
            "serviceProvider": {"name": "LINKEDIN"},
            "serviceProviderShareId": "s6006172162048675840"
          },
          "timestamp": 1431983032724,
          "visibility": {"code": "anyone"}
        }}
      },
      "updateKey": "UPDATE-c1584435-6006172162036084736",
      "updateType": "CMPY"
    }
  ]
}
 */

// Require third party lib
//require_once __DIR__ . "/../thirdparty/linkedinoauth/LinkedIn.OAuth2.class.php";

//class MediaInputLinkedIn implements MediaInputInterface {
//	
//	static $mediaInputType = "LinkedIn";
//	
//	private $consumerKey, // app ID
//			$consumerSecret, // app secret
//			$accessToken, // not used, token is re-generated on every request 
//			$accessSecret; // not used for LinkedIn
//	
//	function setCredentials($consumerKey,$consumerSecret,$accessToken,$accessSecret=false) {
//		$this->consumerKey = $consumerKey;
//		$this->consumerSecret = $consumerSecret;
//		$this->accessToken = $accessToken;
//		$this->accessSecret = $accessSecret;
//	}
//	
//	/**
//	 * We use this app to get/refresh the access token, as they're short-lived by default on FB
//	 */
//	function getConnection() {
//		$consumerKey = $this->consumerKey;
//		$consumerSecret = $this->consumerSecret;
//		//$accessToken = $this->accessToken;
//		//$accessSecret = $this->accessSecret;
//		//return new TwitterOAuth($consumerKey, $consumerSecret, $accessToken, $accessSecret);
//		$ObjLinkedIn = new LinkedInOAuth2();
//		$arrAccess_token = $ObjLinkedIn->getAccessToken($consumerKey, $consumerSecret, "", "");
//		Debug::dump($arrAccess_token);
//		
//        $strAccess_token = $arrAccess_token["access_token"];
//		
//		//Debug::dump($token);
//		return $strAccess_token;
//	}
//	
//	// Custom Query
//	function fetchUpdates($query) {
//		
//		// get a reference to the cache for this module
//		$cache = SS_Cache::factory('mediastreamcache'.strtolower(get_class()), 'Output',
//				array('automatic_serialization' => true));
//		
//		$updates = array();
//		if (!empty($query)) {
//			
//			// we try & get the items from the cache, only get & update then once per ~hour
//			// Facebook: graph.fb.etc/[account]/feed (all) or graph.fb.etc/[account]/posts (only own posts)
//			$cachekey = md5($query);
//			if (!($updates = $cache->load($cachekey))) {
//				$accesstoken = $this->getConnection();
////				if(strpos($query, '?')===false){ $addmark = '?'; } else { $addmark = '&'; }
////				$response = json_decode(file_get_contents($query. "{$addmark}access_token=$accesstoken"));
////				
////				// Parse all updates
////				if ($response && $response->data) {
////					//Debug::dump($response);
////					foreach ($response->data as $update) {
////						// skip links & events, we need something to show...
////						if(!property_exists($update, 'message')) continue;
////						//Debug::dump($update);
////						$updates[] = $this->getOrCreateMediaUpdate($update);
////					}
////				}
////				$cache->save($updates, $cachekey);
//				//Debug::dump('updated');
//			}
//		}
//        // clear memory
//        unset($cache);
//
//		return;
//		//return $tweets;
//	}
//	
//	/**
//	 * Converts an update into a mediaupdate
//	 */
//	public function getOrCreateMediaUpdate($update) {
//		//Debug::dump($update['id']);
//		
//		// Find or create MediaUpdates for status updates/posts
//		$mediaupdate = MediaUpdate::get()
//				->filter(array('Type'=>self::$mediaInputType,'UniqueID'=>$update->id))
//				->first();
//		// if not exists, create (or update as well?)
//		if(!$mediaupdate){ 
//			$mediaupdate = new MediaUpdate();
//          // store raw input
//          $mediaupdate->RawInput = json_encode($update);
//          // fill contents
//			$mediaupdate->TimeStamp = $update->created_time;
//			if(property_exists($update, 'name')){
//				$mediaupdate->Title = $update->name;
//			}
//			$mediaupdate->Content = $this->parseText($update->message);
//			$mediaupdate->Type = self::$mediaInputType;
//			$mediaupdate->OriginURL = "https://www.facebook.com/goflex.nl/posts/{$update->id}";
//			$mediaupdate->UniqueID = $update->id;
//			//Debug::dump($tweet);
//			
//			// Set youtube embed if type = video (not always youtube...
//			if($update->type=="video" && strpos($update->source, 'youtu')!==false){
//				$mediaupdate->Content = $this->parseVideo($update->source);
//			}
//			
//			// set image if update has one
//			if($update->type=="photo" && property_exists($update, 'object_id')){
//				// get img url from graph
//				$accesstoken = $this->getConnection();
//				$imgobj = json_decode(file_get_contents("https://graph.facebook.com/{$update->object_id}?access_token=$accesstoken"));
//				if(property_exists($imgobj, 'source')) {
//					$mediaupdate->ImageURL = $imgobj->source;
//				}
//			}
////			
//			$mediaupdate->write();
//			
//		}
//		return $mediaupdate;
//	}
//	
//	////// HELPERS
//	
//	/**
//	 * In this case: auto-wrap links (@TODO: maybe also include responsive embed code for Youtube links)?
//	 */
//	protected function parseText($rawText) {
//		$rawText = nl2br($rawText);
//		return preg_replace('/https?:\/\/[\w\-\.!~#?&=+\*\'"(),\/]+/','<a href="$0">$0</a>',$rawText);
//	}
//	
//	protected function parseVideo($source){
//		$source = str_replace('autoplay=1', '', $source);
//		return '<div class="embed-responsive embed-responsive-16by9">
//			<iframe src="'.$source.'" class="embed-responsive-item" allowfullscreen></iframe>
//		</div>';
//	}
//	
//}
