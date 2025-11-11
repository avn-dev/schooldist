<?php

namespace Office\Entity;

abstract class Basic extends \WDBasic {

	public function save() {
		
		$oUser = \System::getCurrentUser();
		
		if(
			$oUser instanceof \User &&
			$oUser->id > 0
		) {
			$this->_aData['editor_id'] = $oUser->id;

			if(
				$this->_aData['creator_id'] == 0 
			) {
				$this->_aData['creator_id'] = $oUser->id;
			}
		}
		
		parent::save();
	}
	
}