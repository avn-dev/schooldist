<?php

class Ext_Thebing_Accommodation_Gui2_Costcategory extends Ext_Thebing_Gui2_Basic_School {

	/**
	 * @var string
	 */
	protected $sSchoolField = '';

	/**
	 * @var string
	 */
	protected $sClientField = '';
	
	protected $_sEditorIdColumn = 'editor_id';

	/**
	 * @var string
	 */
	protected static $_sDescription = 'Thebing » Marketing » Accommodation Categories';

	/**
	 * @return mixed[]
	 */
	public static function getCostTypes() {

		$aCostTypes = [
			'night' => L10N::t('Pro Nacht', self::$_sDescription),
			'week' => L10N::t('Pro Woche', self::$_sDescription),
			'periods' => L10N::t('Pro Zeitraum', self::$_sDescription),
			'non_calculate' => L10N::t('Nicht berechnen (wird nicht in Bezahlung aufgeführt)', self::$_sDescription),
		];

		return $aCostTypes;

	}

	/**
	 * {@inheritdoc}
	 */
	protected function _getErrorMessage($sError, $sField = '', $sLabel = '', $sAction = null, $sAdditional = null) {

		switch($sError) {
			case 'PAYMENTS_EXIST':
				return $this->t('Es existieren noch Zahlungen zu dieser Kostenkategorie. Die Kostenart darf nicht verändert werden.');
			case 'ALLOCATIONS_EXIST':
				return $this->t('Es existieren noch gültige Zuweisungen zu Unterkunftsanbietern.');
		}

		return parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);

	}

	/**
	 * {@inheritdoc}
	 */
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave = true, $sAction = 'edit', $bPrepareOpenDialog = true) {

		$aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);

		foreach((array)$aTransfer['error'] as $iKey => $mError) {
			if(!is_array($mError)) {
				continue;
			}
			if(
				$mError['input']['dbalias'] == 'kacc' && (
					$mError['input']['dbcolumn'] == 'rounding_precision' ||
					$mError['input']['dbcolumn'] == 'rounding_increment'
				)
			) {
				$aTransfer['error'][$iKey]['input']['dbalias'] = '';
			}
		}

		return $aTransfer;

	}
	
	static public function getDialog(\Ext_Gui2 $oGui) {

		$sDefaultLang = \Ext_Thebing_Util::getInterfaceLanguage();
		$aCostTypes = Ext_Thebing_Accommodation_Gui2_Costcategory::getCostTypes();
	
		$aSchools = Ext_Thebing_Client::getSchoolList(true);

		$oDialog = $oGui->createDialog(
		$oGui->t('Eintrag "{name}" bearbeiten'),
		$oGui->t('Neuer Eintrag')
		);

		$oDialog->width = 950;
		$oDialog->height = 500;

		$oDialog->setElement(
			$oDialog->createRow(
				L10N::t('Name', $oGui->gui_description),
				'input',
				[
					'db_alias' => 'kacc',
					'db_column' => 'name',
					'required' => 1,
				]
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Schulen'),
				'select',
				[
					'db_alias' => '',
					'db_column' => 'schools',
					'multiple' => 5,
					'select_options' => $aSchools,
					'jquery_multiple' => 1,
					'searchable' => 1,
					'required' => 1,
				]
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				L10N::t('Kostenart', $oGui->gui_description),
				'select',
				[
					'db_alias' => 'kacc',
					'db_column'=> 'cost_type',
					'required' => 1,
					'select_options' => $aCostTypes,
				]
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				L10N::t('Kostenwochen', $oGui->gui_description),
				'select',
				[
					'db_alias' => '',
					'db_column' => 'cost_weeks',
					'select_options' => [],
					'selection' => new Ext_Thebing_Gui2_Selection_School_CostWeek(),
					'row_style' => 'display: none;',
					'multiple' => 5,
					'jquery_multiple' => 1,
					'dependency' => [
						[
							'db_alias' => '', 
							'db_column' => 'schools',
						],
					],
				]
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				L10N::t('Unterkunftskategorien', $oGui->gui_description),
				'select',
				[
					'db_alias' => '',
					'db_column' => 'accommodation_categories',
					'select_options' => [],
					'selection' => new Ext_Thebing_Gui2_Selection_School_AccommodationCategory($sDefaultLang, false),
					'multiple' => 5,
					'jquery_multiple' => 1,
					'dependency' => [
						[
							'db_alias' => '', 
							'db_column' => 'schools',
						],
					],
				]
			)
		);

		$oDialog->setElement($oDialog->create('h4')->setElement($oGui->t('Rundungseinstellungen')));

		$oDialog->setElement(
			$oDialog->createRow(
				L10N::t('Nachkommastellen', $oGui->gui_description),
				'input',
				[
					'db_alias' => 'kacc',
					'db_column' => 'rounding_precision',
					'required' => 1,
				]
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				L10N::t('Inkrement', $oGui->gui_description),
				'input',
				[
					'db_alias' => 'kacc',
					'db_column' => 'rounding_increment',
					'required' => 1,
				]
			)
		);
			return $oDialog;


			}
	
	static public function getOrderby() {


		return ['kacc.name' => 'ASC'];
	}
	
}
