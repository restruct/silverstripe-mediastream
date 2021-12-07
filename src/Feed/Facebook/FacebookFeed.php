<?php

namespace Restruct\Silverstripe\MediaStream\Facebook {

    use Facebook\Exception\ResponseException;
    use Facebook\Exception\SDKException;
    use Facebook\Facebook;
    use Restruct\Silverstripe\MediaStream\FacebookMedia;
    use Restruct\Silverstripe\MediaStream\Feed;
    use DateTime;
    use Exception;
    use Restruct\Silverstripe\MediaStream\MediaUpdate;
    use Restruct\Silverstripe\MediaStream\Providers\ProviderInterface;
    use SilverStripe\Control\Controller;
    use SilverStripe\Dev\Debug;
    use SilverStripe\ORM\ArrayList;

    class FacebookFeed extends Feed implements ProviderInterface
    {

        private $endPoint = 'https://graph.facebook.com';

        private $version = 'v12.0';

        private static $fetch_fields = [
            'from',
            'message',
            'message_tags',
            'story',
            'story_tags',
            'full_picture',
            'actions',
            'privacy',
            'status_type',
            'created_time',
            'updated_time',
            'shares',
            //'is_hidden',
            //'is_expired',
            'likes',
        ];

        /**
         * @var FacebookMedia
         */
        private $facebookMedia;

        /**
         * @param FacebookMedia $facebookMedia
         */
        public function __construct(FacebookMedia $facebookMedia)
        {
            $this->type = 'Facebook';
            $this->facebookMedia = $facebookMedia;
        }

        /**
         * @param int $limit
         *
         * @return bool|void
         * @throws SDKException
         */
        public function fetchUpdates($limit=80)
        {

            $facebookMedia = $this->getFacebookMedia();

            $fb = new Facebook([
                'app_id'                => $facebookMedia->AppID,
                'app_secret'            => $facebookMedia->AppSecret,
                'default_graph_version' => $this->getVersion(),
                'default_access_token'  => $this->getToken(),
            ]);

            try {

                $url = sprintf('/%s/%s?%s', $this->facebookMedia->PageID, $facebookMedia->getRequestType(), $this->getQueryParameters());

                $response = $fb->get($url, $this->facebookMedia->AccessToken);
                $aGraphEdge = $response->getGraphEdge()->asArray();

                foreach ( $aGraphEdge as $post ) {

                    $aData = $this->getPostData($post);
                    $aData[ 'MediaStreamID' ] = $this->facebookMedia->ID;
                    $aData[ 'ImageURL' ] = $this->getImage($post);

                    $this->getOrCreateMediaUpdate($this->facebookMedia, $aData);

                }
                if (! $facebookMedia->LiveStream ) {
                    $date = new DateTime();
                    $dateTime = $date->format('Y-m-d H:i:s');
                    $this->facebookMedia->LastSynced = strtotime($dateTime);
                    $this->facebookMedia->write();
                }

                return true;


            } catch ( ResponseException $e ) {
                echo 'Graph returned an error: ' . $e->getMessage();

            } catch ( SDKException $e ) {
                echo 'Facebook SDK returned an error: ' . $e->getMessage();

            } catch ( Exception $e ) {
                echo 'Error: ' . $e->getMessage();

            }
        }


        /**
         * @param $post
         *
         * @return null
         */
        public function getPostCreated($post)
        {

            return $post[ 'created_time' ] ?? null;
        }


        /**
         * Get the primary image for the post
         *
         * @param $post
         *
         * @return mixed
         */
        public function getImage($post)
        {

            return $post[ 'full_picture' ] ?? null;
        }


        /**
         * Get the user who made the post
         *
         * @param $post
         *
         * @return mixed
         */
        public function getUserName($post)
        {

            return $post[ 'from' ][ 'name' ];
        }


        /**
         * @param $post
         *
         * @return array|string|string[]|null
         */
        public function getPostContent($post)
        {

            $text = $post[ 'message' ] ?? '';

            return $this->parseText($text);
        }

        /**
         * @return FacebookMedia
         */
        public function getFacebookMedia(): FacebookMedia
        {
            return $this->facebookMedia;
        }

        /**
         * @return string
         */
        public function getVersion(): string
        {
            return $this->version;
        }

        /**
         * @return string
         */
        private function getQueryParameters()
        {

            $aQueryParameters = [
                'date_format'  => 'U',
                'fields'       => implode(',', static::getFetchFields()),
                'access_token' => $this->getToken(),
            ];
            $aQueryParameters[ 'limit' ] = 100;

            if ( $this->facebookMedia->LastSynced ) {
                $aQueryParameters[ 'since' ] = $this->facebookMedia->LastSynced;
            }

            return http_build_query($aQueryParameters);
        }

        /**
         * @return string
         */
        public function getEndPoint(): string
        {
            return $this->endPoint;
        }

        /**
         * @return string[]
         */
        public static function getFetchFields(): array
        {
            return self::$fetch_fields;
        }

        /**
         * @return string
         */
        public function getToken()
        {
            return $this->facebookMedia->getToken();
        }

        /**
         * Get the post URL from a post
         *
         * @param $post
         *
         * @return mixed
         */
        public function getPostUrl($post)
        {
            if ( !empty($post[ 'actions' ]) ) {
                $actions = $post[ 'actions' ];
                $action = reset($actions);

                return $action[ 'link' ];
            }

            return '#';


        }


    }
}
