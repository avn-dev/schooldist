<?php

namespace Form\Entity;

class Mail extends \WDBasic {
	
	protected $_sTable = 'form_mailing';
	
	protected $_sTableAlias = 'f_m';
	
	public function isHtml() {
		return ($this->html == 1);
	}
	
	public function getAttachments() {
		
		$aAttachments = json_decode($this->attachments, true);
		
		$sTargetDir = \Util::getDocumentRoot().'storage/form/';
		
		$aReturn = [];
		foreach($aAttachments as $aFiles) {
			foreach($aFiles as $sFile) {
				$aReturn[$sTargetDir.$sFile] = $sFile;
			}
		}		

		return $aReturn;
	}
	
	public function setAttachments(array $aAttachments) {
		return json_encode($aAttachments);
	}
}
