<?php

namespace Restruct\Silverstripe\MediaStream;



use GridFieldCopyButton;
use Restruct\Silverstripe\MediaStream\MediaInput;
use Restruct\Silverstripe\MediaStream\MediaUpdate;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Admin\ModelAdmin;



class MediaStreamAdmin extends ModelAdmin {

    private static $managed_models = array(
        MediaInput::class,
        MediaUpdate::class,
//		'Location'
    );

    private static $url_segment = 'mediastream';
    private static $menu_title = 'Media stream';
	private static $menu_priority = 5;
	private static $page_length = 100;
	private static $default_model = MediaInput::class;
	private static $model_importers = array();
	
	function getEditForm($id = null, $fields = null) {
		$form = parent::getEditForm();
		
		// add duplicate option to MediaInput
		if($this->modelClass == MediaInput::class &&
			$gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass)) ) {
			if ($gridField instanceof GridField) {
				$gridField->getConfig()
					->addComponent(new GridFieldCopyButton());
			}
		}

		return $form;
	}
	
}
