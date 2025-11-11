<?php

namespace TsAccommodation\Gui2\Data;

class RequirementData extends \Ext_Thebing_Gui2_Data {

	/**
	 * Den Dialog aufbauen
	 * @param \Ext_Gui2 $oGui
	 * @return \Ext_Gui2_Dialog $oDialog
	 * @throws \Exception
	 */
	public static function getDialog(\Ext_Gui2 $oGui) {

		// Dialog
		$oDialog		 = $oGui->createDialog($oGui->t('Voraussetzung "{name}" editieren'), $oGui->t('Neue Voraussetzung anlegen'));
		$oDialog->width	 = 900;
		$oDialog->height = 650;
		
		// Tab Einstellungen
		$oTab = $oDialog->createTab($oGui->t('Einstellungen'));
		
		$oTab->aOptions	= array(
			'section' => 'requirements'
		);
		
		$oTab->setElement($oDialog->createRow($oGui->t('Name'), 'input', array(
			'db_alias' => 'ts_apr',
			'db_column' => 'name',
			'required'	=> true,
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Benötigt für'), 'select', array(
			'db_alias' => 'ts_apr',
			'db_column' => 'requirement',
			'required'	=> true,
			'select_options' => array(
				'accommodation_provider' => $oGui->t('Unterkunftsanbieter'),
				'member' => $oGui->t('Person')
			)
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Benötigt ab (Alter)'), 'input', array(
			'db_alias' => 'ts_apr',
			'db_column' => 'age',
			'required'	=> true,
			'dependency_visibility' => [
				'db_alias' => 'ts_apr',
				'db_column' => 'requirement',
				'on_values' => ['member']
			],
		)));

		$oDialog->setElement($oTab);

		
		return $oDialog;

	}

	/**
	 * Den Request zum Öffnen des Dialoges bearbeiten
	 * @param array $aVars
	 * @return array $aTransfer
	 */
	protected function requestRequirements($aVars) {

		$oEntity = $this->_getWDBasicObject($aVars['parent_gui_id'][0]);
		$iRequirementid = $aVars['id'][0];
		$oRequirement = \TsAccommodation\Entity\Requirement::getInstance($iRequirementid);
		$oDialog = new \Ext_Gui2_Dialog();
		$oDialog->save_button = false;
		$oDialog->sDialogIDTag = 'NOTICES_';
		
		$oIframe = new \Ext_Gui2_Html_Iframe();
		$oIframe->src = '/ts-accommodation/requirement/interface/view?id='.(int)$oRequirement->id.'&accommodation='.$oEntity->id;
		$oIframe->style = 'width: 100%; height: 100%; border: 0;';

		$oDialog->setElement($oIframe);
		
		$aTransfer = [];
		$aTransfer['data'] = $oDialog->getDataObject()->getHtml($aVars['action'], $aVars['id'], $aVars['additional']);

		if($oEntity->id > 0) {
			$aTransfer['data']['title'] = str_replace('{name}', $oRequirement->getName(), $this->t('Nachweise für "{name}"'));
		} else {
			$aTransfer['data']['title'] = $this->t('Nachweise');
		}

		$aTransfer['data']['no_scrolling'] = true;
		$aTransfer['data']['no_padding'] = true;
		$aTransfer['data']['full_height'] = true;
		
		$aTransfer['action'] = 'openDialog';
		
		return $aTransfer;
	}

	/**
	 * Holt die Gültigkeit-Status der Voraussetzung-Dokumente
	 *
	 * @param \Ext_Gui2 $oGui
	 * @return array
	 */
	public static function getDocumentStatuses(\Ext_Gui2 $oGui) {

		$aDocumentStatuses = [
			'document_missing'		=> $oGui->t('Fehlende Dokumente'),
			'document_expired'		=> $oGui->t('Abgelaufene Dokumente'),
		];

		return $aDocumentStatuses;
	}
	
}

