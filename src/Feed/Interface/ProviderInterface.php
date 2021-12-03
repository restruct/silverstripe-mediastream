<?php

namespace Restruct\Silverstripe\MediaStream\Providers {

    interface ProviderInterface
    {

        public function fetchUpdates();

        public function getPostContent($post);

        public function getPostCreated($post);

        public function getPostUrl($post);

        public function getUserName($post);

        public function getImage($post);
    }
}
