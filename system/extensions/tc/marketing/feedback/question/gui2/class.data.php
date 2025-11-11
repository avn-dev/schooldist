<?php

class Ext_TC_Marketing_Feedback_Question_Gui2_Data extends Ext_TC_Gui2_Data {

    /**
     * @return array
     */
    public function getTopics() {
		$aTopics = Ext_TC_Marketing_Feedback_Topic::getSelectOptions();
		return $aTopics;
	}
	
	/**
	 * Dialog um Themen anzulegen
     *
	 * @param Ext_Gui2 $oGui
	 * @return Ext_Gui2_Dialog 
	 */
	public static function getDialog(Ext_Gui2 $oGui) {

		$oDialog = $oGui->createDialog($oGui->t('Frage editieren'), $oGui->t('Frage anlegen'));	
		$oDialog->width = 1100;
		
		$oDialog->save_as_new_button = true;
		$oDialog->save_bar_options   = true;
		$oDialog->save_bar_default_option = 'open';
		
		return $oDialog;
	}

	/**
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param array $aSelectedIds
	 * @param bool $sAdditional
	 * @return array
     */
    public function getEditDialogHTML(&$oDialog, $aSelectedIds, $sAdditional = false) {
		
		$oDialog->aElements = array();
		
		if(!$this->oWDBasic) {
			$this->_getWDBasicObject($aSelectedIds);
		}
		
		$aTopics = $this->getTopics();
		$aLanguages = Ext_TC_Factory::executeStatic('Ext_TC_Util', 'getTranslationLanguages');
		$aQuestionTypes = Ext_TC_Factory::executeStatic('Ext_TC_Marketing_Feedback_Question', 'getQuestionTypes');
		$aDependencies = Ext_TC_Factory::executeStatic('Ext_TC_Marketing_Feedback_Question', 'getDependencies');
		
		$oTab = $oDialog->createTab($this->t('Einstellungen'));
		
		$oTab->setElement($oDialog->createRow($this->t('Thema'), 'select', array(
			'db_column' => 'topic_id',
			'select_options' => Ext_TC_Util::addEmptyItem($aTopics),
			'required' => 1
		)));
		
		$oTab->setElement($oDialog->createRow($this->t('Antworttyp'), 'select', array(
			'db_column' => 'question_type',
			'select_options' => Ext_TC_Util::addEmptyItem($aQuestionTypes),
			'required' => 1
		)));

        $aQuantityStars = array( 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7 );
        $oTab->setElement($oDialog->createRow( $this->t('Anzahl der Sterne'), 'select', array(
            'db_column' => 'quantity_stars',
            'select_options' => Ext_TC_Util::addEmptyItem($aQuantityStars),
            'dependency_visibility' => array(
                'db_column' => 'question_type',
                'on_values' => array('stars')
            ),
            'required' => 1
        )));

		$oTab->setElement($oDialog->createRow($this->t('Skala'), 'select', array(
			'db_column' => 'rating_id',
			'select_options' => Ext_TC_Marketing_Feedback_Rating::getSelectOptions(true),
			'required' => true,
			'dependency_visibility' => [
				'db_column' => 'question_type',
				'on_values' => array('rating')
			]
		)));

		$oTab->setElement($oDialog->createRow($this->t('Abhängigkeit von'), 'select', array(
			'db_column' => 'dependency_on',
			'select_options' => Ext_TC_Util::addEmptyItem($aDependencies),
			'events' => array(
				array(
					'event' => 'change',
					'function' => 'reloadDependencyFields',
					'parameter' => 'aDialogData.id, 0'
				)
			)
		)));
		
		$oTab->setElement($oDialog->createRow( $this->t('Frage nach Gesamtzufriedenheit'), 'checkbox', array(
			'db_column' => 'overall_satisfaction',
			'dependency_visibility' => array(
				'db_column' => 'question_type',
				'on_values' => array('stars', 'rating')
			)
		)));
		
		$sType = $this->oWDBasic->dependency_on;
		$aDependencyData = $this->_getDependencySelectOptions($sType);

		if(
			!empty($aDependencyData) &&
			!empty($aDependencyData['objects'])
		) {

			$oTab->setElement($oDialog->createRow($this->t('Auswahl'), 'select', array(
				'db_column' => 'dependency_objects',
				'multiple' => 5, 
				'jquery_multiple' => 1,
				'select_options' => $aDependencyData['objects'],
				'searchable' => 1,
				'style' => 'height: 105px;',
				'required' => 1
			)));

			$this->createElement($oDialog, $oTab, 'post_dependency_objects');
			
			if(!empty($aDependencyData['selection'])) {

				$oSelection = null;
				if(is_string($aDependencyData['selection'])) {
					$oSelection = new $oSelection();
				} else if($aDependencyData['selection'] instanceof Ext_Gui2_View_Selection_Abstract) {
					$oSelection = $aDependencyData['selection'];
				}

				$this->createElement($oDialog, $oTab, 'subdependency_objects', $oSelection);

			}
			
		}
		
		$oDialog->setElement($oTab);
		
		$oTab = $oDialog->createTab($this->t('Frage'));
		
		$oTab->setElement($oDialog->createI18NRow($this->t('Frage'), array(
			'type' => 'html',
			'db_alias'=> 'questions_tc_i18n', 
			'db_column' => 'question',
			'i18n_parent_column' => 'question_id',
			'required' => 1
		), $aLanguages));
		
		$oDialog->setElement($oTab);
		
		$aData = parent::getEditDialogHTML($oDialog, $aSelectedIds, $sAdditional);
		
		return $aData;
	}

	/**
	 * @inheritdoc
	 */
	protected function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null) {

		if($sError == 'QUESTION_DEPENDENCY_ALREADY_USED') {
			$sMessage = $this->t('Die Abhängigkeit kann nicht mehr verändert werden, nachdem eine Frage verwendet wurde.');
		} else {
			$sMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}

		return $sMessage;
	}

	/**
	 * TODO Achtung, Methode wird komplett überschrieben!
	 *
	 * lädt die Select-Options für das Feld "Auswahl"
	 * 
	 * array(
	 *		'object' => array(1 => '1', 2 => '2')
	 *		'selection => new SelectionClass()  // null => unteres MS wird ausgeblendet
	 * )
	 * 
	 * @param string $sType
	 * @return array
	 */
	protected function _getDependencySelectOptions($sType) {

		$aReturn = array();
		
		switch($sType) {
			case 'course_category':
				$aReturn['objects'] = $this->_getCourseCategories();
				break;
			case 'accommodation_category':
				$aReturn['objects']	= $this->_getAccommodationCategories();
				break;
			case 'meal':
				$aReturn['objects']	= $this->_getMealTypes();
				break;
			case 'transfer':
				$aReturn['objects']	= $this->_getTransfers();
				break;
			default:
				$aReturn = [];
		}
		
		if(!empty($aReturn)) {
			$aReturn['selection'] = null;
		}

		return $aReturn;
	}

    /**
     * @param string $sLanguage
     * @return array
     */
    protected function _getCourseCategories($sLanguage = '') {
		return array();
	}

    /**
     * @param string $sLanguage
     * @return array
     */
    protected function _getAccommodationCategories($sLanguage = '') {
		return array();
	}

    /**
     * @param string $sLanguage
     * @return array
     */
    protected function _getMealTypes($sLanguage = '') {
		return array();
	}
	
	/**
	 * Transfertypen
	 * 
	 * 1 => Anreise
	 * 2 => Abreise
	 * 3 => An- und Abreise
	 * 4 => Nicht gewünscht
	 * 
	 * @param string $sLanguage
	 * @return array
	 */
	protected function _getTransfers($sLanguage = '') {

		$aReturn = array(
			'1'	=> Ext_TC_L10N::t('Anreise', $sLanguage),
			'2'	=> Ext_TC_L10N::t('Abreise', $sLanguage),
			'3'	=> Ext_TC_L10N::t('An- und Abreise', $sLanguage),
			'4'	=> Ext_TC_L10N::t('Nicht gewünscht', $sLanguage),
		);
		
		return $aReturn;
	}

    /**
     * @param bool $bEmptyItem
     * @return array|object
     */
    public static function getDependencyFilterOptions($bEmptyItem = false) {

		$aOptions = Ext_TC_Factory::executeStatic('Ext_TC_Marketing_Feedback_Question', 'getDependencies');

	    if($bEmptyItem) {
			$aOptions = Ext_TC_Util::addEmptyItem($aOptions);
	    }
		
		return $aOptions;
	}

	/**
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param Ext_Gui2_Dialog_Tab $oTab
	 * @param string $sType
	 * @param Ext_Gui2_View_Selection_Abstract $oSelection
	 */
	protected function createElement(Ext_Gui2_Dialog &$oDialog, Ext_Gui2_Dialog_Tab $oTab, $sType, $oSelection = null) {

		switch($sType) {
			case 'subdependency_objects':
				$oTab->setElement($oDialog->createRow($this->t('Unterauswahl'), 'select', array(
					'db_column' => 'dependency_subobjects',
					'multiple' => 5,
					'jquery_multiple' => 1,
					'selection' => $oSelection,
					'searchable' => 1,
					'style' => 'height: 105px;',
					'required' => 1,
					'dependency' => array(array(
						'db_column' => 'dependency_objects'
					))
				)));
			break;
		}

	}
	
}