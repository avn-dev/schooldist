<?php

class Ext_TS_Marketing_Feedback_Question_Gui2_Data extends Ext_TC_Marketing_Feedback_Question_Gui2_Data {
	
	/**
	 * Lädt die Select-Options für das Feld "Auswahl"
	 * 
	 * array(
	 *      'object' => array(1 => '1', 2 => '2')
	 *		'selection => new SelectionClass()  // null => unteres MS wird ausgeblendet
	 * )
	 * 
	 * @param string $sType
	 * @return array
	 */
	protected function _getDependencySelectOptions($sType) {
		switch($sType) {
			case 'teacher':
				$aReturn['objects'] = [];
				$aReturn['selection'] = null;
				break;
			case 'booking_type':
				$aReturn['objects'] = Ext_TS_Document_Release_Gui2_Data::getBookingTypes($this->_oGui, true);
				$aReturn['selection'] = null;
				break;
			case 'booking_type':
				$aReturn['objects'] = Ext_TS_Document_Release_Gui2_Data::getBookingTypes($this->_oGui, true);
				$aReturn['selection'] = null;
				break;
			case 'rooms':
			case 'meal':
			case 'accommodation_provider':
			case 'accommodation_category':
			case 'course':
			case 'course_category':
			case 'teacher_course':
				$aReturn['selection']= new Ext_TS_Marketing_Feedback_Question_Gui2_Selection_SubObjects();
				$aReturn['objects'] = Ext_Thebing_Client::getSchoolList(true);
				break;
			default:
				$aReturn = parent::_getDependencySelectOptions($sType);
		}
				
		return $aReturn;
	}

	/**
	 * Transfertypen
	 *
	 * 1 => Anreise
	 * 2 => Abreise
	 * 3 => An- und Abreise
	 * 4 => Nicht gewünscht
	 * 5 => Individuell
	 *
	 * @param string $sLanguage
	 * @return array
	 */
	protected function _getTransfers($sLanguage = '') {

		$aReturn = parent::_getTransfers($sLanguage);
		$aReturn['5'] = Ext_TC_L10N::t('Individuell', $sLanguage);

		return $aReturn;
	}

	/**
	 * Erstellt Elemente anhand eines Typen
	 *
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param Ext_Gui2_Dialog_Tab $oTab
	 * @param string $sType
	 * @param Ext_Gui2_View_Selection_Abstract $oSelection
	 */
	protected function createElement(Ext_Gui2_Dialog &$oDialog, Ext_Gui2_Dialog_Tab $oTab, $sType, $oSelection = null) {

		switch($sType) {
			case 'post_dependency_objects':
				$oTab->setElement($oDialog->createRow( $this->t('Alle Anbieter?'), 'select', array(
					'db_column' => 'accommodation_provider',
					'select_options' => array(
						'all_provider' => $this->t('Ja'),
						'not_all_provider' => $this->t('Nein'),
						'just_host_family' => $this->t('Nur Gastfamilien'),
						'just_other_accommodations' => $this->t('Nur andere Unterkünfte')
					),
					'dependency_visibility' => array(
						'db_column' => 'dependency_on',
						'on_values' => array('accommodation_provider')
					),
					'events' => array(array(
						'event' => 'change',
						'function' => 'reloadDialogTab',
						'parameter' => 'aDialogData.id, 0'
					))
				)));
				break;
			case 'subdependency_objects';
				if(
					/*
					 * Bei Fragen die von einem Unterkunftsanbieter abhängig sind und die
		             * Einstellung "Alle Anbieter?" nicht auf "Nein" steht, darf die
					 * Unterauswahl nicht angezeigt werden.
					 */
					$this->oWDBasic->dependency_on == 'accommodation_provider' && (
						$this->oWDBasic->accommodation_provider == 'all_provider' ||
						$this->oWDBasic->accommodation_provider == 'just_host_family' ||
						$this->oWDBasic->accommodation_provider == 'just_other_accommodations'
					)
				) {
					break;
				}
				parent::createElement($oDialog, $oTab, $sType, $oSelection);
				break;
			default:
				parent::createElement($oDialog, $oTab, $sType, $oSelection);
		}

	}
	
}
