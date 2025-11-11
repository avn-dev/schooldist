<?php

class Ext_Thebing_Placementtests_Question_Gui2 extends Ext_Thebing_Gui2_Data {

	static protected $categorySelectionGui;

	public static function getOrderby()
	{
		return ['position' => 'ASC'];
	}

	static public function getCategorySelectionGui($oGui) {

		// Durch getDialog() beim New- und Edit-Button wird immer 2x die Gui erstellt, dadurch (keine Ahnung warum genau)
		// Hat das "where" bei der Categorie-Gui nicht funktioniert, wenn man eine neue Frage anlegen wollte und dann
		// in dem Dialog die Kategorie-Gui aufgerufen hat.  Wenn man einmal den Editdialog der Frage geöffnet hat, hat
		// es funktioniert. Diese Methode stellt sicher, dass es jetzt die gleiche Gui ist.
		if(!isset(self::$categorySelectionGui)) {
			$categoryFactory = new \Ext_Gui2_Factory('TsTuition_placementtest_categories');
			self::$categorySelectionGui = $categoryFactory->createGui('', $oGui);
		}

		return self::$categorySelectionGui;
	}

	public static function getDialog(\Ext_Gui2 $oGui)
	{
		$sDescriptionPart		= 'Thebing » Tuition » Placementtests';

		$aTypeOptions			= Ext_Thebing_Placementtests_Question::getTypesOptions($sDescriptionPart, true);

		$oDialog = $oGui->createDialog($oGui->t('Frage editieren'),$oGui->t('Neue Frage anlegen'));
		$oDialog->width = 1100;
		$oDialog->height = 900;

		$oDialog->save_as_new_button		= true;
		$oDialog->save_bar_options			= true;
		$oDialog->save_bar_default_option	= 'new';

		$questionTab = $oDialog->createTab($oGui->t('Frage'));

		$questionTab->setElement($oDialog->createRow($oGui->t('Frage'), 'html', array(
			'db_column' => 'text',
			'required' => 1,
			'advanced' => true,
			'style' => 'height:250px;'
		)));

		$questionTab->setElement($oDialog->createRow($oGui->t('Typ'), 'select', array(
			'db_column' => 'type',
			'required' => 1,
			'select_options' => $aTypeOptions,
		)));

		$sChoose = Ext_Thebing_L10N::getEmptySelectLabel('please_choose');
		$aPleaseChoose = array(
			0 => array(
				'text'	=> $sChoose,
				'value' => 0
			)
		);

		$categoryGui = self::getCategorySelectionGui($oGui);

		$questionTab->setElement(
			$oDialog->createRow(
				$oGui->t('Kategorie'),
				'select',
				[
					'db_column'				=> 'idCategory',
					'required'				=> 1,
					'selection_gui'			=> $categoryGui,
					'selection_settings'	=> [
						'value_column'		=> 'id', // Die Spalte im GUI Ergebnis für den Wert der Option
						'text_column'		=> 'category', // Die Spalte im GUI Ergebnis für das Label der Option
						'dialog_width'		=> 700, // Dialog Breite
						'dialog_height'		=> 500, // Dialog Höhe
						'dialog_title'		=> $categoryGui->t('Kategorien bearbeiten'),
						'button_label'		=> $categoryGui->t('Kategorie hinzufügen'), // Label des Buttons
						'static_elements'	=> $aPleaseChoose, //statische Elemente die mitgeladen werden müssen
					]
				]
			)
		);

		$questionTab->setElement($oDialog->createRow($oGui->t('Optional'), 'checkbox', array(
			'db_column' => 'optional',
			'required' => 0,
		)));

		// Wenn die Checkbox checked ist, "Optional" aber unchecked wurde, bleibt der Wert auf 1, ist dann aber egal, weil die
		// Frage dann sowieso immer bewertet wird (sie ist ja nicht mehr Optional dann)
		$questionTab->setElement($oDialog->createRow($oGui->t('Immer bewerten'), 'checkbox', array(
			'db_column' => 'always_evaluate',
			'required' => 0,
			'dependency_visibility' => [
				'db_column' => 'optional',
				'on_values' => ['1']
			],
		)));

		$oDialog->setElement($questionTab);

		$answerTab = $oDialog->createTab($oGui->t('Antworten'));

		$answersFactory = new \Ext_Gui2_Factory('TsTuition_placementtest_answers');
		$answersGui = $answersFactory->createGui('', $oGui);

		$answerTab->setElement($answersGui);

		$oDialog->setElement($answerTab);

		return $oDialog;
	}

	protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false)
	{
		// Weil wir hier noch die placementtest-Id haben, später beim aufrufen der YML nicht mehr
		// (-> getWhere kann man dann nicht mehr setzen)
		foreach ($oDialogData->aSaveData as $row) {
			if (isset($row['selection_gui'])) {
				$placementtestIds = $this->getParentGuiIds();
				$row['selection_gui']->setTableData('where', ['placementtest_id'=>(int)reset($placementtestIds)]);
				break;
			}
		}

		return parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);
	}

}