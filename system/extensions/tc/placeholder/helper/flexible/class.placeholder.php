<?php


class Ext_TC_Placeholder_Helper_Flexible_Placeholder extends Ext_TC_Placeholder_Abstract {

	protected $_aSettings = array(
		'variable_name' => 'oDataModel'
	);

	public function setFlexiblePlaceholder($bAssign = false) {

		$aChildFields = \Ext_TC_Flexibility::getRepository()->findBy(['parent_id' => $this->_oWDBasic->parent_field]);

		$aPlaceholders = [];
		foreach($aChildFields as $oChildField) {
			if(!empty($oChildField->placeholder)) {
				$aPlaceholders[$oChildField->placeholder] = [
					'label' => $oChildField->description,
					'type' => 'field',
					'source' => 'field_'.$oChildField->getId(),
					'translate_label' => false
				];
			}
		}

		if(!empty($aPlaceholders)) {
			$this->_aPlaceholders = $aPlaceholders + $this->_aPlaceholders;
		}

	}

}
