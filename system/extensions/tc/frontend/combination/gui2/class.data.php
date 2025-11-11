<?php

/**
 * @property Ext_TC_Frontend_Combination $oWDBasic
 */
class Ext_TC_Frontend_Combination_Gui2_Data extends Ext_TC_Gui2_Data
{

	static public function getDialog(Ext_Gui2 $gui, ?Ext_TC_Frontend_Combination $combination=null) 
    {
		
		$aUsageOptions = Ext_TC_Factory::executeStatic('Ext_TC_Frontend_Template_Gui2_Data', 'getUsageOptions', array(true));
		
		$oDialog = $gui->createDialog($gui->t('Kombination "{name}" editieren'), $gui->t('Neue Kombination anlegen'));		
		$oDialog->save_as_new_button = true;
		$oDialog->height = 700;

		$oDialog->setElement(
			$oDialog->createRow(
				$gui->t('Name'), 
				'input', 
				array(
					'db_column' => 'name',
					'db_alias'  => 'tc_fc',
					'required'  => true
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$gui->t('Verwendung'), 
				'select', 
				array(
					'db_column'=>'usage',
					'db_alias'=>'tc_fc',
					'select_options'=>$aUsageOptions,
					'required'=>true,
					'events'=>array(
						array(
							'event' 	=> 'change',
							'function' 	=> 'reloadDialogTab',
							'parameter'	=> 'aDialogData.id, 0'
						)
					)
				)
			)
		);

		return $oDialog;
	}

	/**
	 * @return Ext_Gui2_Dialog
	 */
	public static function getUsagesDialog(\Ext_Gui2 $oGui)
    {

		$oFactory = new Ext_Gui2_Factory('TcFrontend_combination_usages');

		$oGui = $oFactory->createGui('', $oGui);
		$oGui->setTableData('orderby', ['created' => 'DESC']);
		$oGui->foreign_key = 'combination_id';
		$oGui->parent_primary_key = 'id';

		$oDialog = $oGui->createDialog($oGui->t('Verwendungen von "{name}"'));
		$oDialog->setElement($oGui);

		return $oDialog;

	}

	static protected function _addPasswordSecuritySelect($oDialog)
    {
		
		$aPasswordSecurityStatus = array();
		$aPasswordSecurityStatus['low'] = $oDialog->oGui->t('niedrig');
		$aPasswordSecurityStatus['medium'] = $oDialog->oGui->t('normal');
		$aPasswordSecurityStatus['high'] = $oDialog->oGui->t('hoch');
		$aPasswordSecurityStatus = Ext_TC_Util::addEmptyItem(
			$aPasswordSecurityStatus,
			Ext_TC_L10N::getEmptySelectLabel('please_choose')
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oDialog->oGui->t('Passwort-Sicherheit'),
				'select',
				array(
					'db_column' => 'items_password_security',
					'db_alias' => 'tc_fc',
					'select_options' => $aPasswordSecurityStatus,
					'required' => true
				)
			)
		);
	}
	
	public function switchAjaxRequest($_VARS)
    {

		if(
			$_VARS['task'] == 'confirm' &&
			$_VARS['action'] == 'refreshCombinationData'
		) {

			$aMessages = array();
			$aTransfer = array();
			
			$oCombinationProcessing = new Ext_TC_Frontend_Combination_Helper_ParallelProcessing();
			
			foreach($_VARS['id'] as $iCombinationId) {

				/** @var Ext_TC_Frontend_Combination $oCombination */
				$oCombination = Ext_TC_Factory::getInstance('Ext_TC_Frontend_Combination', $iCombinationId);

				try {					
					$oCombinationProcessing->addToStack($oCombination);
				} catch (Exception $e) {
					Ext_TC_Util::reportError('TC - Fehler - Update Frontend', $e);
				}

				$aMessages[] = sprintf($this->t('Die Kombination "%s" wird aktualisiert'), $oCombination->getName());

			}

            $aTransfer['action'] = 'showSuccessAndReloadTable';
            $aTransfer['success'] = 1;
            $aTransfer['message'] = implode('<br>', $aMessages);
            $aTransfer['success_title']	= $this->t('Kombination aktualisieren');

			echo json_encode($aTransfer);
			
		} elseif($_VARS['action'] === 'refreshCombinationDataRightNow') {

			foreach($_VARS['id'] as $iCombinationId) {
				/** @var \Ext_TC_Frontend_Combination $oCombination */
				$oCombination = Ext_TC_Factory::getInstance('Ext_TC_Frontend_Combination', $iCombinationId);

				$oFrontendCombination = $oCombination->getObjectForUsage(new \SmartyWrapper());
				$oFrontendCombination->initializeData();
			}

			$aTransfer['action'] = 'showSuccessAndReloadTable';
			$aTransfer['success'] = 1;
			$aTransfer['message'] = $this->t('Die Kombinationen wurden aktualisiert.');
			$aTransfer['success_title']	= $this->t('Kombination aktualisieren');

			echo json_encode($aTransfer);

		}
	
		else {
			parent::switchAjaxRequest($_VARS);
		}
		
	}
	
	public function confirmDeleteCacheforCombination($_VARS)
    {

		foreach($_VARS['id'] as $iCombinationId) {

			$oCombination = \Ext_TC_Frontend_Combination::getInstance($iCombinationId);

			$oCombinationCacheHandler = new \TcCache\Handler\CombinationCache();
			$oCombinationCacheHandler->setCombination($oCombination);
			$oCombinationCacheHandler->clearCache();

			$oCombinationHelperCaching = new Ext_TC_Frontend_Combination_Helper_Caching($oCombination);
			$oCombinationHelperCaching->clearCache();
			
			$oCache = new \TcCache\Handler\Frontend\CombinationWDCache($oCombination);
			$oCache->deleteGroup();
			
		}

		$aTransfer['action'] = 'showSuccessAndReloadTable';
		$aTransfer['success'] = 1;
		$aTransfer['message'] = $this->t('Die Cache Dateien für die Kombinationen wurden gelöscht.');
		$aTransfer['success_title']	= $this->t('Kombination\'s Cache löschen');

		return $aTransfer;
	}
    /**
     * @param string $sAction
     * @param array $aSelectedIds
     * @param array $aSaveData
     * @param bool $sAdditional
     * @param bool $bSave
     * @return array
     */
    protected function saveDialogData($sAction, $aSelectedIds, $aSaveData, $sAdditional=false, $bSave=true)
    {

		if(!$this->oWDBasic) {
			$this->_getWDBasicObject($aSelectedIds);
		}
		
		if($bSave === true) {
			
			if(!$this->oWDBasic->isPending()) {
			
				$aData = parent::saveDialogData($sAction, $aSelectedIds, $aSaveData, $sAdditional, $bSave);

				if($bSave === true) {
					/* @var Ext_TC_Frontend_Combination $oCombination */
					$oCombination = $this->oWDBasic;
					$oCombinationProcessing = new Ext_TC_Frontend_Combination_Helper_ParallelProcessing();
					$oCombinationProcessing->addToStack($oCombination);
				}

			} else {
				$aData = [];
				$aData['success']	= 0;
				$aData['error']		= [
					$this->t('Fehler beim Speichern'), [
						'message' => $this->t('Die Kombination wird zurzeit aktualisiert! Bitte warten Sie bis dieser Vorgang abgeschlossen ist und versuchen Sie es später erneut.'),
					]
				];
				$aData['action'] = 'showError';
				$aData['data'] = [];
			}
			
		} else {
			$aData = parent::saveDialogData($sAction, $aSelectedIds, $aSaveData, $sAdditional, $bSave);
		}
		
        return $aData;
    }

    static protected function addWidgetCombinationSettings(Ext_Gui2_Dialog $oDialog, Ext_TC_Frontend_Combination $combination)
    {

		$oDialog->setElement($oDialog->createRow($oDialog->oGui->t('Domains'), 'textarea', [
			'db_column' => 'items_domains',
			'db_alias' => 'ts_fc',
			'required' => true,
			'maxlength' => 767 // tc_frontend_combinations_items.item_value ascii_general_ci
		]));

		$oDialog->setElement($oDialog->createRow($oDialog->oGui->t('URL'), 'input', [
			'db_column' => 'items_url',
			'db_alias' => 'ts_fc'
		]));

		$oDialog->setElement($oDialog->createRow($oDialog->oGui->t('Widget als Inlineframe einbinden'), 'checkbox', [
			'db_column' => 'items_use_iframe',
			'db_alias' => 'ts_fc',
			'input_div_elements' => ['<span class="help-block">'.$oDialog->oGui->t('Bei Nichtverwendung des Inlineframes sind eventuelle Anzeigefehler kein Fehler von Fidelo; das Element fügt sich allerdings nahtloser und performanter in die Website ein.').'</span>'],
		]));

		$oDialog->setElement($oDialog->createRow($oDialog->oGui->t('CSS-Bundle verwenden'), 'checkbox', [
			'db_column' => 'items_use_css_bundle',
			'db_alias' => 'ts_fc',
			'input_div_elements' => ['<span class="help-block">'.$oDialog->oGui->t('Hiermit werden Bootstrap 4 (Subset) und Font Awesome 5 automatisch eingebunden.').'</span>'],
			'dependency_visibility' => [
				'db_alias' => 'ts_fc',
				'db_column' => 'items_use_iframe',
				'on_values' => [0],
			]
		]));

		$aTemplates = Ext_TC_Frontend_Template::getRepository()->findBy(['usage' => $combination->usage]);
		$aTemplates = collect($aTemplates)->mapWithKeys(function (Ext_TC_Frontend_Template $oTemplate) {
			return [$oTemplate->id => $oTemplate->name];
		})->prepend('', '');

		$oDialog->setElement($oDialog->createRow($oDialog->oGui->t('Template einbinden'), 'select', [
			'db_column' => 'items_template',
			'db_alias' => 'ts_fc',
			'select_options' => $aTemplates,
			'dependency_visibility' => [
				'db_alias' => 'ts_fc',
				'db_column' => 'items_use_iframe',
				'on_values' => [1],
			]
		]));

	}

	public static function getOrderby()
    {

		return ['tc_fc.name'=>'ASC'];
	}
	
}
