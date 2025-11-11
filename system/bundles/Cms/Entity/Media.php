<?php

namespace Cms\Entity;

class Media extends \WDBasic {
	
	protected $_sTable = 'cms_media';
	protected $_sTableAlias = 'sm';

	public function getPath() {
		
		$sPath = \Util::getDocumentRoot().'storage/public/'.$this->folder.$this->file;
		
		return $sPath;
	}
	
}