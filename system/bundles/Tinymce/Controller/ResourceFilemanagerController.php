<?php

namespace Tinymce\Controller;

class ResourceFilemanagerController extends \Core\Controller\Vendor\ResourceAbstractController {

	protected $sPath = "vendor/trippo/responsivefilemanager/responsive_filemanager/filemanager/";

	protected $bExecutePHP = true;
	
	protected $aGlobalVars = [
		'lang_vars',
		'mime_types',
		'hidden_folders',
		'hidden_files',
		'MaxSizeTotal',
		'current_path'
	];

	public function __construct($sExtension, $sController, $sAction, $oAccess = null) {
		
		// Fallback auf alte Dateistruktur
		if(!is_dir(\Util::getDocumentRoot().$this->sPath)) {
			$this->sPath = "vendor/trippo/responsivefilemanager/filemanager/";
		}
		
		parent::__construct($sExtension, $sController, $sAction, $oAccess);
	}
	
}