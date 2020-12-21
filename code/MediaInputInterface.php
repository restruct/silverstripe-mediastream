<?php

namespace Restruct\Silverstripe\MediaStream;




/**
 * Implementing classes 'MediaInput[Service]' take care of getting status updates
 * and saving them to the database as MediaUpdate's
 */

interface MediaInputInterface {
	
	/**
	 * Set credentials for the connection to service
	 * 
	 * @param type $consumerKey
	 * @param type $consumerSecret
	 * @param type $accessToken
	 * @param type $accessSecret
	 */
	function setCredentials($consumerKey,$consumerSecret,$accessToken,$accessSecret);
	
	/**
	 * Generate & return a new (OAuth) connection
	 * 
	 * @return ...OAuth
	 */
	function getConnection();
	
	/**
	 * Execute query to get items/updates from REST API & update/add to local database
	 * 
	 * @param type $query
	 * @param type $count
	 * @return array Array of updates 
	 */
	function fetchUpdates($query);
	
	/**
	 * Converts/updates an update into a MediaItem in the local database
	 * (optionally create an associative array to fill Content field via call to customise() & renderWith
	 * 
	 * @param stdObject $item
	 * 
	 * @return MediaItem
	 */
	public function getOrCreateMediaUpdate($item);
	
	/*
	 * Give instructions in HTML on how to get a oAuth token
	 */
	static function oauthInstructions();
	
}