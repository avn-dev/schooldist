<?php

/**
 * @property Ext_Thebing_Marketing_Saison $oWDBasic
 */
class Ext_Thebing_Marketing_Saison_Gui2 extends Ext_Thebing_Gui2_Data {

	public static function getTranslationPart(){
		return 'Thebing » Marketing » Saison';
	}

	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave=true, $sAction='edit', $bPrepareOpenDialog = true) {

		$aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);

		// Wenn die Saison erfolgreich gespeichert wurde
		if(empty($aTransfer['error']) && $bSave){
			$sSuccessMessage = $this->t('Erfolgreich gespeichert');

			$iFromSaison = $aSaveData['copy_from'];
			if(0 < $iFromSaison){

				if($aSaveData['copy_price']){
					//wenn keine Datensätze zum kopieren da sind, ist bSaved trotzdem 1 (success)
					$bSaved = $this->oWDBasic->copyPrices($iFromSaison);

					if(1 > $bSaved){
						$sSuccessMessage .= '<br />';
						$sSuccessMessage .= $this->t('Das Kopieren der Preise ist fehlgeschlagen!');
					}
				}

				if($aSaveData['copy_commission']) {
					//wenn keine Datensätze zum kopieren da sind, ist bSaved trotzdem 1 (success)
					try {
						$this->oWDBasic->copyCommission($iFromSaison);
					} catch(Exception $e) {
						if(System::d('debugmode') == 2) {
							__out($e);
						}

						$sSuccessMessage .= '<br />';
						$sSuccessMessage .= $this->t('Das Kopieren der Provisionen ist fehlgeschlagen!');
					}
				}

				if($aSaveData['copy_cost']){
					//wenn keine Datensätze zum kopieren da sind, ist bSaved trotzdem 1 (success)
					$bSaved = $this->oWDBasic->copyCosts($iFromSaison);

					if(1 > $bSaved){
						$sSuccessMessage .= '<br />';
						$sSuccessMessage .= $this->t('Das Kopieren der Kosten ist fehlgeschlagen!');
					}
				}
			}
		}

		$aTransfer['success_message'] = $sSuccessMessage;

		return $aTransfer;
	}
	
	/**
	 * siehe parent
	 * @param string $sError
	 * @param string $sField
	 * @param string $sLabel
	 * @param string $sAction
	 * @param string $sAdditional
	 * @return string 
	 */
	protected function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null) {
			
		switch($sError){
			case'INVALID_PERIOD_END':
				$sErrorMessage = $this->t('Das Enddatum ist ungültig.');
				break;
			default:
				$sErrorMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
				break;
		}	

		return $sErrorMessage;
	}

	static public function getDialog(\Ext_Gui2 $oGui) {
		
		$oSchool				= $oSchool = Ext_Thebing_School::getSchoolFromSession();
		$sInterfaceLanguage		= $oSchool->getInterfaceLanguage();
		$sDbColumnTitle			= 'title_'.$sInterfaceLanguage;
		$aSchoolLanguages		= $oSchool->getLanguageList(true);
		
		$sEditTitle = $oGui->t('Saison "{title}" editieren');
		$sEditTitle = str_replace('{title}', '{'.$sDbColumnTitle.'}', $sEditTitle);

		$oDialog					= $oGui->createDialog($sEditTitle, $oGui->t('Neue Saison anlegen'));
		$oDialog->width				= 900;
		$oDialog->height			= 650;

		//$oDialog->save_as_new_button		= true;
		//$oDialog->save_bar_options			= true;
		//$oDialog->save_bar_default_option	= 'new';

		/******************************************* Tab Informationen *****************************************************/
		$oTab = $oDialog->createTab($oGui->t('Informationen'));

		if(!empty($aSchoolLanguages)){
			foreach((array)$aSchoolLanguages as $sCode=>$sLanguage){

				$oTab->setElement($oDialog->createRow($oGui->t('Titel'). ' ('.$sLanguage.')', 'input', array(
					'db_alias' => '',
					'db_column'=>'title_'.$sCode,
					'required' => 1
				)));

				$aSearch[] = 'title_'.$sCode;
			}
		}

		$oTab->setElement($oDialog->createRow($oGui->t('Von'), 'calendar', array(
				'db_alias'			=> '',
				'db_column'			=> 'valid_from',
				'required'			=> 1,
				'format'			=> new Ext_Thebing_Gui2_Format_Date(),
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Bis'), 'calendar', array(
				'db_alias'			=> '',
				'db_column'			=> 'valid_until',
				'required'			=> 1,
				'format'			=> new Ext_Thebing_Gui2_Format_Date(),
		)));


		/****************************** Frühbucherrabatt *********************************/
		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Frühbucherrabatt'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Frühbucherrabatt Kurs'), 'calendar', array(
				'db_alias'			=> '',
				'db_column'			=> 'discount_course',
				'required'			=> 0,
				'format'			=> new Ext_Thebing_Gui2_Format_Date(),
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Frühbucherrabatt Unterkunft'), 'calendar', array(
				'db_alias'			=> '',
				'db_column'			=> 'discount_accommodation',
				'required'			=> 0,
				'format'			=> new Ext_Thebing_Gui2_Format_Date(),
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Frühbucherrabatt Transfer'), 'calendar', array(
			'db_alias' => '',
			'db_column' => 'discount_transfer',
			'required' => 0,
			'format' => new Ext_Thebing_Gui2_Format_Date(),
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Frühbucherrabatt Versicherung'), 'calendar', array(
			'db_alias' => '',
			'db_column' => 'discount_insurance',
			'required' => 0,
			'format' => new Ext_Thebing_Gui2_Format_Date(),
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Alternativ Preise'), 'select', array(
				'db_alias'			=> '',
				'db_column'			=> 'discount_assignment',
				'required'			=> 0,
				'selection'			=> new Ext_Thebing_Gui2_Selection_Saisons(),
		)));


		/****************************** Preise *********************************/
		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Preise'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Kurs, Unterkunft, Transfer'), 'checkbox', array(
				'db_alias'			=> '',
				'db_column'			=> 'saison_for_price',
				'required'			=> 0,
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Versicherungen'), 'checkbox', array(
				'db_alias'			=> '',
				'db_column'			=> 'saison_for_insurance',
				'required'			=> 0,
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Aktivitäten'), 'checkbox', [
			'db_column' => 'season_for_activity'
		]));

		/****************************** Kosten *********************************/
		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Kosten'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Lehrersaison(Kosten)'), 'checkbox', array(
				'db_alias'			=> '',
				'db_column'			=> 'saison_for_teachercost',
				'required'			=> 0,
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Transfersaison(Kosten)'), 'checkbox', array(
				'db_alias'			=> '',
				'db_column'			=> 'saison_for_transfercost',
				'required'			=> 0,
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Unterkunftssaison(Kosten)'), 'checkbox', array(
				'db_alias'			=> '',
				'db_column'			=> 'saison_for_accommodationcost',
				'required'			=> 0,
		)));

		//$oTab->setElement($oDialog->createRow($oGui->t('Fixkostensaison(Kosten)'), 'checkbox', array(
		//		'db_alias'			=> '',
		//		'db_column'			=> 'saison_for_fixcost',
		//		'required'			=> 0,
		//)));

		/****************************** sonstiges *********************************/
		/*$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Sonstiges'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Sichtbar'), 'checkbox', array(
				'db_alias'			=> '',
				'db_column'			=> 'visible',
				'required'			=> 0,
		)));*/

		$oDialog->setElement($oTab);

		/******************************************* Kopieren *****************************************************/

		$oTab = $oDialog->createTab($oGui->t('Kopieren'));

		$oTab->setElement($oDialog->createRow($oGui->t('Kopieren von'), 'select', array(
				'db_alias'			=> '',
				'db_column'			=> 'copy_from',
				'required'			=> 0,
				'selection'			=> new Ext_Thebing_Gui2_Selection_Saisons(),
				'skip_value_handling' => true
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Preise'), 'checkbox', array(
				'db_alias'			=> '',
				'db_column'			=> 'copy_price',
				'required'			=> 0,
				'skip_value_handling' => true
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Kosten'), 'checkbox', array(
				'db_alias'			=> '',
				'db_column'			=> 'copy_cost',
				'required'			=> 0,
				'skip_value_handling' => true
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Provisionen'), 'checkbox', array(
			'db_column' => 'copy_commission',
				'skip_value_handling' => true
		)));


		$oDialog->setElement($oTab);
		
		return $oDialog;
	}
	
	static public function getOrderBy(){

		return [
			'valid_from' => 'DESC'
		];
	}
	
	static public function getWhere() {
		
		$iClientId			= Ext_Thebing_Client::getClientId();
		$iSchoolId			= (int)\Core\Handler\SessionHandler::getInstance()->get('sid');

		return ['idClient' => $iClientId, 'idPartnerschool' => $iSchoolId];
	}

	static public function getParams() {
		
		$oSchool				= $oSchool = Ext_Thebing_School::getSchoolFromSession();
		$aSchoolLanguages		= $oSchool->getLanguageList(true);

		return $aSchoolLanguages;
	}
	
	static public function manipulateSearchFilter(\Ext_Gui2 $oGui) {
		
		$defaultLang = \Ext_Thebing_Util::getInterfaceLanguage();
		
		return [
			'column' => [
				'id',
				'title_'.$defaultLang
			]
		];
		
	}
	
}
