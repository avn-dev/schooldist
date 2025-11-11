<?php

class Ext_TC_Positiongroup_Section_Gui2_Data extends Ext_TC_Gui2_Data {
	
	public function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false) {
		
		$oDialogData->aElements = array();	

		if(!$this->oWDBasic) {
			$this->_getWDBasicObject($aSelectedIds);
		}

		$aLanguages = Ext_TC_Factory::executeStatic('Ext_TC_Util', 'getTranslationLanguages');

		$oDialogData->setElement($oDialogData->createI18NRow(
			$this->_oGui->t('Name'),
			array(
				'db_alias' => 'i18n',
				'db_column'=> 'name',
				'i18n_parent_column' => 'section_id',
				'required' => true
				),
			$aLanguages
		));

		$oDialogData->setElement($oDialogData->createRow(
			$this->_oGui->t('Rechnungspositionen'),
			'select',
			array(
				'db_column'			=> 'positions',
				'db_alias'			=> 'tc_p',
				'multiple'			=> 5,
				'sortable'			=> 1,
				'jquery_multiple'	=> 1,
				'selection'			=> new Ext_TC_Positiongroup_Selection_Types(),
				'searchable'		=> true,
				'events'			=> array(
					array(
						'event' 		=> 'change',
						'function' 		=> 'reloadDialogTab',
						'parameter'		=> 'aDialogData.id, 0'
					)
				)
			)
		));
		
		if($this->oWDBasic->checkPositions(array('course', 'additionalservice_course'))) {
			$oDialogData->setElement($oDialogData->createRow(
				$this->_oGui->t('Kursleistungen zusammenfügen'),
				'checkbox',
				array(
					'db_column'	=> 'merge_course_services',
					'db_alias'	=> 'tc_p',
				)
			));
		}
		
		if($this->oWDBasic->checkPositions(array('accommodation', 'additionalservice_accommodation'))) {
			$oDialogData->setElement($oDialogData->createRow(
				$this->_oGui->t('Unterkunftsleistungen zusammenfügen'),
				'checkbox',
				array(
					'db_column'	=> 'merge_accommodation_services',
					'db_alias'	=> 'tc_p',
				)
			));
		}
		
		return parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);
	}

}
