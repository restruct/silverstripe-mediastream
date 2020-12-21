<?php

namespace Restruct\Silverstripe\MediaStream;

use TwitterOAuth;
use SS_Cache;

use SilverStripe\Core\Convert;



// (Original author Damian Mooyman)

// Require third party lib
require_once __DIR__ . "/../thirdparty/twitteroauth/twitteroauth/twitteroauth.php";

class MediaInputTwitter implements MediaInputInterface {
	
	static $mediaInputType = "Twitter";
	
	private $consumerKey, 
			$consumerSecret, 
			$accessToken, 
			$accessSecret;
	
	function setCredentials($consumerKey,$consumerSecret,$accessToken,$accessSecret) {
		$this->consumerKey = $consumerKey;
		$this->consumerSecret = $consumerSecret;
		$this->accessToken = $accessToken;
		$this->accessSecret = $accessSecret;
	}
	
	static function oauthInstructions(){
		return '<p>...</p>';
	}
	
	/**
	 * Generate a new TwitterOAuth connection
	 * 
	 * @return TwitterOAuth
	 */
	function getConnection() {
		$consumerKey = $this->consumerKey;
		$consumerSecret = $this->consumerSecret;
		$accessToken = $this->accessToken;
		$accessSecret = $this->accessSecret;
		return new TwitterOAuth($consumerKey, $consumerSecret, $accessToken, $accessSecret);
	}
	
	// Gets latest updates of user
//	function getUpdates($user, $count=30) {
//		// Check user
//		if (empty($user)) return null;
//		// Call rest api
//		$arguments = http_build_query(array(
//			'screen_name' => $user,
//			'count' => $count,
//			'include_rts' => true
//		));
//		$connection = $this->getConnection();
//		$response = $connection->get("https://api.twitter.com/1.1/statuses/user_timeline.json?$arguments");
//		// Parse all tweets
//		$tweets = array();
//		if ($response && is_array($response)) {
//			foreach ($response as $tweet) {
//				$tweets[] = $this->parseTweet($tweet);
//			}
//		}
//		return $tweets;
//	}
	
	// Custom Query
	function fetchUpdates($query) {
		
		// get a reference to the cache for this module (each input their own as an attempt to prevent memory exhaustion)
		$cache = SS_Cache::factory('mediastreamcache_'.strtolower(get_class()), 'Output',
				array('automatic_serialization' => true));
		
		$tweets = array();
		if (!empty($query)) {
			
			// we try & get the items from the cache, only get & update then once per ~hour
			$cachekey = md5($query);
			if (!($tweets = $cache->load($cachekey))) {
				$connection = $this->getConnection();
				$response = $connection->get($query);
				
				// Parse all tweets
				if ($response) {
					foreach ($response as $tweet) {
						$tweets[] = $this->getOrCreateMediaUpdate($tweet);
					}
				} else {
				    $curClass = get_class();
                    print "ERROR ($curClass)<br>";
                    return user_error("ERROR updating $curClass: " . print_r($response, true), E_USER_ERROR); //E_USER_WARNING);
                }
				$cache->save($tweets, $cachekey);
				//Debug::dump('updated');
			}
		}
		// clear memory
		unset($cache);

		return;
		//return $tweets;
	}
	
	/**
	 * Converts a tweet response into a simple associative array of fields
	 * 
	 * @param stdObject $tweet Tweet object
	 * @return array Array of fields with Date, User, and Content as keys
	 */
	public function getOrCreateMediaUpdate($update) {
		$profileLink = "https://twitter.com/" . Convert::raw2url($update->user->screen_name);
		$updateID = $update->id_str;
//		$normalized = array(
//			'ID' => $updateID,
//			'Date' => $update->created_at,
//			//'TimeAgo' => self::determine_time_ago($update->created_at),
//			'Name' => $update->user->name,
//			'User' => $update->user->screen_name,
//			'AvatarUrl' => $update->user->profile_image_url,
//			'Content' => $this->parseText($update),
//			'Link' => "{$profileLink}/status/{$updateID}",
//			'ProfileLink' => $profileLink,
////			'ReplyLink' => "https://twitter.com/intent/tweet?in_reply_to={$updateID}",
////			'RetweetLink' => "https://twitter.com/intent/retweet?tweet_id={$updateID}",
////			'FavouriteLink' => "https://twitter.com/intent/favorite?tweet_id={$updateID}"
//		);
		//foreach($updates as $update){
		// Now find or create MediaUpdates for tweets
		$mediaupdate = MediaUpdate::get()
				->filter(array('Type'=>self::$mediaInputType,'UniqueID'=>$update->id_str))
				->first();
		// if not exists, create (or update as well?)
		if(!$mediaupdate){ 
			$mediaupdate = new MediaUpdate();
			// store raw input
			$mediaupdate->RawInput = json_encode($update);
			// fill contents
//			$mediaupdate->DateTime = $update->created_at; //new DateTime($update->Date);
			$mediaupdate->TimeStamp = $update->created_at; //new DateTime($update->Date);
//			$mediaupdate->Title = $this->parseText($update);
			$mediaupdate->Content = $this->parseText($update);
			$mediaupdate->Type = self::$mediaInputType;
			$mediaupdate->OriginURL = "{$profileLink}/status/{$updateID}";
			$mediaupdate->UniqueID = $updateID;
//			$mediaupdate->Name = self::$mediaInputType . ": "
//					. DBField::create_field('HTMLText', $mediaupdate->Content)->LimitWordCount(6);
			//Debug::dump($update);
			
			// set image if tweet has one
			if(property_exists($update->entities, 'media')){
				foreach ($update->entities->media as $img) {
					$mediaupdate->ImageURL = $img->media_url;
					break; // jus set one image
				}
			}
			
			$mediaupdate->write();
			
		}
		return $mediaupdate;
	}
	
	////// HELPERS
	
	/**
	 * Parse the tweet object into a HTML block
	 * 
	 * @param stdObject $tweet Tweet object
	 * @return string HTML text
	 */
	protected function parseText($tweet) {
		$rawText = $tweet->text;
		// tokenise into words for parsing (multibyte safe)
		//$tokens = preg_split('/(?<!^)(?!$)/u', $rawText);
		$replacements = array();
		
		// Inject links
		foreach ($tweet->entities->urls as $url) {
			//$this->injectLink($rawText, $url, $url->expanded_url, $url->display_url); //expanded_url);
			$startPos = $url->indices[0];
			$endPos = $url->indices[1];
			$placeholder = substr($rawText, $startPos, $endPos-$startPos);
			$replacement = sprintf("<a href='%s' target='_blank'>%s</a>",
				Convert::raw2att($url->expanded_url),
				Convert::raw2att($url->display_url));
			$replacements[$placeholder] = $replacement;
		}
		// Inject hashtags
//		foreach ($tweet->entities->hashtags as $hashtag) {
//			$link = 'https://twitter.com/search?src=hash&q=' . Convert::raw2url('#' . $hashtag->text);
//			$text = "#" . $hashtag->text;
//			//$this->injectLink($rawText, $hashtag, $link, $text);
//		}
//		// Inject mentions
//		foreach ($tweet->entities->user_mentions as $mention) {
//			$link = 'https://twitter.com/' . Convert::raw2url($mention->screen_name);
//			$this->injectLink($rawText, $mention, $link, $mention->name);
//		}
		// Replace all entities (do this last or the indices will be off)
		foreach($replacements as $replace => $replacement){
			$rawText = str_replace($replace, $replacement, $rawText);
		}
		
		return $rawText;
	}
	
}
