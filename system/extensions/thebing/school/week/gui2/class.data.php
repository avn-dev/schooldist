<?php

class Ext_Thebing_School_Week_Gui2_Data extends Ext_Thebing_Gui2_Data
{

	/**
	 * {@inheritdoc}
	 */
	static public function getOrderby()
    {
		
		return ['kw.start_week' => 'ASC'];
	}

	static public function getDialog(Ext_Thebing_Gui2 $oGui)
    {

		$oDialog = $oGui->createDialog(
			L10N::t('Woche "{title}" editieren', $oGui->gui_description),
			L10N::t('Neue Woche anlegen', $oGui->gui_description)
		);

		$oDialog->width = 900;
		$oDialog->height = 650;
		$oDialog->save_as_new_button = true;
		$oDialog->save_bar_options = true;
		$oDialog->save_bar_default_option = 'new';
		
		return $oDialog;
	}

	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab = false, $sAdditional = false, $bSaveSuccess = true)
    {

		if (in_array($sIconAction, ['new', 'edit'])) {
			if (!$this->oWDBasic) {
				$this->_getWDBasicObject($aSelectedIds);
			}

			if (
				!empty($aSelectedIds) &&
				$sIconAction == 'new'
			) {
				$sIconKey = self::getIconKey('edit', $sAdditional);
			} else {
				$sIconKey = self::getIconKey($sIconAction, $sAdditional);
			}
			$oDialog = $this->aIconData[$sIconKey]['dialog_data'];

			$this->setDialogContent($oDialog, $aSelectedIds);
		}

		return parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);

	}

	/**
	 * Erstellt den Inhalt des Dialoges
	 *
	 * @param \Ext_Gui2_Dialog $oDialog
	 * @param $aSelectedIds
	 */
	private function setDialogContent(Ext_Gui2_Dialog $oDialog, $aSelectedIds)
    {

		$oDialog->aElements = [];
		$oDialog->aSaveData = [];
		$oDialog->aUniqueFields = [];

		$mSelectedPriceStructure = $this->getSelectedPriceStructure($this->oWDBasic);

		$oDialog->setElement(
			$oDialog->createRow(
				$oDialog->oGui->t('Bezeichnung'),
				'input',
				[
					'db_alias' => 'kw',
					'db_column' => 'title',
					'required' => 1,
				]
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oDialog->oGui->t('Schulen'),
				'select',
				[
					'db_alias' => '',
					'db_column' => 'schools',
					'multiple' => 5,
					'select_options' => [],
					'selection' => new Ext_Thebing_Gui2_Selection_School_SchoolsWithSameValue('price_structure_week'),
					'jquery_multiple' => 1,
					'searchable' => 1,
					'required' => 1,
					'events' => [
						[
							'event' => 'change',
							'function' => 'reloadDialogTab',
							'parameter' => 'aDialogData.id, 1'
						]
					],
					'always_add_unknown_entries' => true,
				]
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oDialog->oGui->t('Startwoche'),
				'input',
				[
					'db_alias' => 'kw',
					'db_column' => 'start_week',
					'required' => 1,
				]
			)
		);

		if($mSelectedPriceStructure == 1) {

			$oDialog->setElement(
				$oDialog->createRow(
					$oDialog->oGui->t('Wochenanzahl'),
					'input',
					[
						'db_alias' => 'kw',
						'db_column' => 'week_count',
					]
				)
			);

		}

		$oDialog->setElement(
			$oDialog->createRow(
				$oDialog->oGui->t('Extrawoche'),
				'checkbox',
				[
					'db_alias' => 'kw',
					'db_column'=>'extra',
				]
			)
		);

	}

	/**
	 * @param Ext_Thebing_School_Week $oWDBasic
	 * @return null|int
	 */
	private function getSelectedPriceStructure(Ext_Thebing_School_Week $oWDBasic)
    {

		$aSelectedSchoolIds = $oWDBasic->schools;
		$aSchools = Ext_Thebing_Client::getSchoolList(false, 0, true);

		/*
		 * als Wert den Wert der ersten selektierten Schule nehmen, welche auch als gültige
		 * Auswahl in $aSchools auftaucht
		 */
		foreach($aSelectedSchoolIds as $iSelectedSchoolId) {
			$oSelectedSchool = Ext_Thebing_School::getInstance($iSelectedSchoolId);
			foreach($aSchools as $oSchool) {
				if($oSchool->getId() === $oSelectedSchool->getId()) {
					return $oSelectedSchool->price_structure_week;
				}
			}
		}

	}

}
