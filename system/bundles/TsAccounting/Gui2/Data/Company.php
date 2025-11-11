<?php

namespace TsAccounting\Gui2\Data;

class Company extends \Ext_Thebing_Gui2_Data
{

	use \Ts\Traits\ServiceSettings;

	/**
	 * Alle Zuweisungen
	 *
	 * @var array
	 */
	protected $_aAllocations = array();

	const L10N_PATH = 'Thebing » Admin » Companies';

	/**
	 * Dialog new/edit
	 *
	 * @param \Ext_Gui2 $oGui
	 * @return \Ext_Gui2_Dialog
	 */
	public static function getDialog(\Ext_Gui2 $oGui)
	{

		$aCharsets = \Ext_TC_Export::getCharsetOptions();

		// Dialog
		$oDialog = $oGui->createDialog($oGui->t('Firma "{name}" editieren'), $oGui->t('Neue Firma anlegen'));
		$oDialog->width = 1600;

		// Tab Einstellungen
		$oTab = $oDialog->createTab($oGui->t('Einstellungen'));

		$oTab->aOptions = array(
			'section' => 'accounting_companies_options'
		);

		$oTab->setElement($oDialog->createRow($oGui->t('Name'), 'input', array(
			'db_column' => 'name',
			'required' => true,
		)));

		// Wiederholbarer Bereich für Schul/Inboxselect
		$oJoinContainer = $oDialog->createJoinedObjectContainer('combinations', array(
			'min' => 1,
			'max' => 20,
		));

		$oJoinContainer->setElement($oJoinContainer->createRow($oGui->t('Schule'), 'select', array(
			'db_column' => 'schools',
			'db_alias' => 'ts_com_c',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'style' => 'height: 105px;', 'class' => 'txt school_multiselects',
			'required' => true,
			'select_options' => \Ext_Thebing_Client::getStaticSchoolListByAccess(true)
		)));

		$oJoinContainer->setElement($oJoinContainer->createRow($oGui->t('Leistungen'), 'select', array(
			'db_column' => 'services',
			'db_alias' => 'ts_com_c',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'selection' => new \TsAccounting\Gui2\Selection\Company\ServiceTypes()
		)));

		$oJoinContainer->setElement($oDialog->createRow($oGui->t('Aufteilung nach Kurskategorien'), 'checkbox', [
			'db_column' => 'courses_by_category',
			'db_alias' => 'ts_com',
			'dependency_visibility' => [
				'db_column' => 'services',
				'db_alias' => 'ts_com_c',
				'on_values' => ['course']
			],
			'child_visibility' => [
				array(
					'id' => 'dialog_input_course_categories',
					'on_values' => array(
						'1'
					)
				)
			]
		]));

		$oJoinContainer->setElement($oJoinContainer->createRow($oGui->t('Kurskategorien'), 'select', [
			'row_id' => 'dialog_input_course_categories',
			'db_column' => 'course_categories',
			'db_alias' => 'ts_com_c',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'style' => 'height: 105px;', 'class' => 'txt week_fields',
			'select_options' => \Ext_Thebing_Tuition_Course_Category::query()
				->get()
				->mapWithKeys(fn ($category) => [$category->id => $category->getName()]),
			'dependency_visibility' => [
				'db_column' => 'courses_by_category',
				'db_alias' => 'ts_com',
				'on_values' => ['1']
			],
		]));

		// Wenn keine Inbox verwendet wird, dann Multiselect nicht anzeigen
		if (\Ext_Thebing_System::hasInbox()) {
			$oJoinContainer->setElement($oJoinContainer->createRow($oGui->t('Inbox'), 'select', array(
				'db_column' => 'inboxes',
				'db_alias' => 'ts_com_c',
				'multiple' => 5,
				'jquery_multiple' => 1,
				'style' => 'height: 105px;',
				'class' => 'txt',
				'required' => true,
				'select_options' => \Ext_Thebing_System::getInboxList('use_id', true)
			)));
		}

		$oTab->setElement($oJoinContainer);

		$oTab->setElement($oDialog->createRow($oGui->t('Währung'), 'select', array(
			'db_column' => 'currency_iso',
			'required' => true,
			'selection' => new \TsAccounting\Gui2\Selection\Company\Currency(),
			'dependency' => [
				[
					'db_alias' => 'ts_com_c',
					'db_column' => 'schools',
				],
			]
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Buchführung'), 'select', array(
			'db_column' => 'accounting_type',
			'required' => true,
			'select_options' => self::getAccountingTypeOptions($oGui),
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Buchhaltungsschnittstelle'), 'select', array(
			'db_column' => 'interface',
			'required' => true,
			'child_visibility' => array(
				array(
					'id' => 'QB_Notification',
					'on_values' => array(
						'quickbooks'
					)
				),
				array(
					'id' => 'export_settings_container',
					'on_values' => array(
						'universal',
						'datev',
						'sage50',
						'quickbooks',
						'git'
					)
				)
			),
			'select_options' => self::getInterfaceOptions($oGui),
		)));

		$oH3 = new \Ext_Gui2_Html_H4();
		$oH3->setElement($oGui->t('Automatische Verarbeitung'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Automatische Freigabe'), 'checkbox', array(
			'db_column' => 'automatic_release',
		)));

		$hours = [];
		for ($i = 1; $i <= 24; $i++) {
			$hours[$i] = $i;
		}

		$oTab->setElement($oDialog->createMultiRow("", array(
			'items' => array(
				array(
					'db_column' => 'automatic_document_release_after',
					'input' => 'select',
					'select_options' => $hours,
					'text_before' => $oGui->t('Rechnungen nach'),
					'text_after' => '' . $oGui->t('Stunde(n)'),
					'dependency_visibility' => array(
						'db_column' => 'automatic_release',
						'on_values' => [1]
					)
				),
			),
		)));

		$oTab->setElement($oDialog->createMultiRow("", array(
			'items' => array(
				array(
					'db_column' => 'automatic_payment_release_after',
					'input' => 'select',
					'select_options' => $hours,
					'text_before' => $oGui->t('Zahlungen nach'),
					'text_after' => '' . $oGui->t('Stunde(n)'),
					'dependency_visibility' => array(
						'db_column' => 'automatic_release',
						'on_values' => [1]
					)
				),
			),
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Ausführung der automatischen Freigabe'), 'select', array(
			'db_column' => 'automatic_release_time',
			'select_options' => \Ext_TC_Util::getHours(),
			'dependency_visibility' => array(
				'db_column' => 'automatic_release',
				'on_values' => [1]
			)
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Weiterverarbeitung automatisch ausführen'), 'checkbox', array(
			'db_column' => 'automatic_stack_export',
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Ausführung der Weiterverarbeitung'), 'select', array(
			'db_column' => 'automatic_stack_export_time',
			'select_options' => \Ext_TC_Util::getHours(),
			'dependency_visibility' => array(
				'db_column' => 'automatic_stack_export',
				'on_values' => [1]
			)
		)));

		$oExportSettingsContainer = new \Ext_Gui2_Html_Div();
		$oExportSettingsContainer->id = 'export_settings_container';

		$oH3 = new \Ext_Gui2_Html_H4();
		$oH3->setElement($oGui->t('Buchungsstapel: Export'));

		$oExportSettingsContainer->setElement($oH3);

		$oExportSettingsContainer->setElement($oDialog->createRow($oGui->t('Dateiformat'), 'select', [
			'db_column' => 'export_file_extension',
			'required' => true,
			'select_options' => [
				'csv' => 'CSV',
				'txt' => 'TXT'
			],
		]));

		$oExportSettingsContainer->setElement($oDialog->createRow($oGui->t('Überschriften'), 'checkbox', [
			'db_column' => 'export_headlines',
			'value' => 1,
			'child_visibility' => [
				[
					'class' => 'column_headline',
					'on_values' => [
						1
					]
				]
			]
		]));

		$aDelimiterOptions = [
			'' => '',
			';' => $oGui->t('Semikolon'),
			',' => $oGui->t('Komma'),
			"tabulator" => $oGui->t('Tabulator'),
			\Gui2\Service\Export\Csv::SEPERATOR_FIX_WIDTH => $oGui->t('Feste Breite')
		];

		$oExportSettingsContainer->setElement($oDialog->createRow($oGui->t('Trennzeichen'), 'select', [
			'db_column' => 'export_delimiter',
			'required' => true,
			'select_options' => $aDelimiterOptions,
			'child_visibility' => [
				[
					'class' => 'column_width',
					'on_values' => [
						\Gui2\Service\Export\Csv::SEPERATOR_FIX_WIDTH
					]
				]
			]
		]));

		$oExportSettingsContainer->setElement($oDialog->createRow($oGui->t('Zeilenumbruch'), 'select', [
			'db_column' => 'export_linebreak',
			'required' => true,
			'select_options' => [
				'unix' => 'Unix (\n)',
				'windows' => 'Windows (\r\n)',
			],
		]));

		$aEnclosureDelimiterOptions = $aDelimiterOptions;
		unset($aEnclosureDelimiterOptions[\Gui2\Service\Export\Csv::SEPERATOR_FIX_WIDTH]);
		$aEnclosureDelimiterOptions = array_keys($aEnclosureDelimiterOptions);

		$oExportSettingsContainer->setElement($oDialog->createRow($oGui->t('Feld-Begrenzungszeichen'), 'select', [
			'db_column' => 'export_enclosure',
			'select_options' => [
				// TODO Welchen Sinn macht ''? Wenn ein Feld (z.B. Name) ein Komma enthält und der Delimeter auch Komma ist, muss das enclosed werden?
				'' => $oGui->t('Keines'),
				'double_quotes' => $oGui->t('Doppelte Anführungszeichen')
			],
			'dependency_visibility' => [
				'db_column' => 'export_delimiter',
				'on_values' => $aEnclosureDelimiterOptions
			],
		]));

		$oExportSettingsContainer->setElement($oDialog->createRow($oGui->t('Kodierung'), 'select', [
			'db_column' => 'export_charset',
			'select_options' => $aCharsets,
			'required' => true
		]));

		$oExportSettingsContainer->setElement($oDialog->createRow($oGui->t('Dateiname'), 'input', [
			'db_column' => 'export_filename'
		]));

		$oExportSettingsContainer->setElement($oDialog->createMultiRow($oGui->t('Spalten'), [
			'input_container' => true,
			'multi_rows' => true,
			'db_alias' => 'columns_export_full',
			'items' => [
				[
					'db_column' => 'column',
					'input' => 'select',
					'selection' => new \TsAccounting\Gui2\Selection\Company\Columns(),
					'jointable' => true,
					'text_after' => '&nbsp;'
				],
				[
					'placeholder' => $oGui->t('Breite'),
					'db_column' => 'width',
					'input' => 'input',
					'jointable' => true,
					'class' => 'column_width',
					'text_after' => '&nbsp;'
				],
				[
					'placeholder' => $oGui->t('Vorgabe oder Format'),
					'db_column' => 'content',
					'input' => 'input',
					'jointable' => true,
					'text_after' => '&nbsp;'
				],
				[
					'placeholder' => $oGui->t('Überschrift'),
					'db_column' => 'headline',
					'input' => 'input',
					'jointable' => true,
					'class' => 'column_headline',
					'text_after' => '&nbsp;'
				]
			]
		]));

		$oTab->setElement($oExportSettingsContainer);
		
		$oH3 = new \Ext_Gui2_Html_H4();
		$oH3->setElement($oGui->t('Sonstiges'));
		$oTab->setElement($oH3);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Kostenstelle'),
				'input',
				[
					'db_column' => 'cost_center'
				]
			)
		);

		$oTab->setElement($oDialog->createRow($oGui->t('Buchungsschlüssel (positiver Betrag)'), 'input', array(
			'db_column' => 'posting_key_positive',
			'dependency_visibility' => [
				'db_column' => 'interface',
				'on_values' => ['datev'],
			]
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Buchungsschlüssel (negativer Betrag)'), 'input', array(
			'db_column' => 'posting_key_negative',
			'dependency_visibility' => [
				'db_column' => 'interface',
				'on_values' => ['datev'],
			]
		)));

		$aAdditionalPlaceholders = array(
			'Dokumentennummer: %document_number',
			'Agenturname: %agency',
			'Agenturnummer: %agency_number',
			'Vorname des Kunden: %firstname',
			'Nachname des Kunden: %surname',
			'Kundennummer: %customernumber',
			'Name des Adressaten: %addresse',
			'Bsp.: %d/%m/%Y: {date}',
		);
		$oNotification = \Ext_TC_Util::getDateFormatDescription($oDialog, 'QB_Notification', $aAdditionalPlaceholders);
		$oTab->setElement($oNotification);

		$oTab->setElement($oDialog->createRow($oGui->t('QB Nummernformat'), 'input', array(
			'db_column' => 'qb_number_format',
			'dependency_visibility' => array(
				'db_column' => 'interface',
				'on_values' => array(
					'quickbooks'
				)
			)
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Rechnungstext veränderbar bei freigegebenen Rechnungen?'), 'checkbox', array(
			'db_column' => 'invoice_item_description_changeable',
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Forderungspositionen erzeugen'), 'select', [
			'db_column' => 'create_claim_debt',
			'select_options' => [
				\TsAccounting\Entity\Company::NO_CLAIM_DEBT_POSITIONS => $oGui->t('Keine Forderungspositionen'),
				\TsAccounting\Entity\Company::SEPARATE_CLAIM_DEBT_POSITIONS => $oGui->t('Eine Forderungsposition pro Fälligkeit'),
				\TsAccounting\Entity\Company::SINGLE_CLAIM_DEBT_POSITION => $oGui->t('Nur eine Forderungsposition'),
			]
//			'dependency_visibility' => [
//				'db_column' => 'interface',
//				'on_values' => array(
//					'universal',
//					'xero'
//				)
//			]
		]));

		$oTab->setElement($oDialog->createRow($oGui->t('Nettorechnungen mit Brutto- und Provisionsbetrag verbuchen'), 'checkbox', array(
			'db_column' => 'book_net_with_gross_and_commission',
			'child_visibility' => array(
				array(
					'class' => 'fieldset_expense_net',
					'on_values' => array('1')
				)
			)
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Rabatt als eigenen Buchungssatz exportieren'), 'select', array(
			'db_column' => 'additional_booking_record_for_discount',
			'select_options' => [
				'' => $oGui->t('Nein'),
				'all' => $oGui->t('Überall'),
				'not_commission' => $oGui->t('Alles außer Provision')
			],
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Buchungssätze'), 'select', [
			'db_column' => 'accounting_records',
			'select_options' => self::getAccountingRecordOptions($oGui),
			'dependency_visibility' => [
				'db_column' => 'interface',
				'on_values' => array(
					'universal',
					'sage50',
					'quickbooks'
				)
			]
		]));

		$oTab->setElement($oDialog->createRow($oGui->t('Automatikkonten'), 'select', [
			'db_column' => 'automatic_account_setting',
			'select_options' => [
				'none' => $oGui->t('Keine Automatikkonten (Aufteilung nach Steuer)'),
				'all' => $oGui->t('Alle Konten sind Automatikkonten (Keine Aufteilung nach Steuer)'),
				'per_account' => $oGui->t('Einstellung pro Konto')
			]
		]));

		$oTab->setElement($oDialog->createRow($oGui->t('Beginn Geschäftsjahr'), 'select', [
			'db_column' => 'financial_year_start',
			'select_options' => \Ext_TC_Util::addEmptyItem(\Ext_TC_Util::getMonths()),
			'required' => true
		]));

		$oTab->setElement($oDialog->createRow($oGui->t('Export auf Geschäftsjahre aufteilen'), 'checkbox', array(
			'db_column' => 'split_export_by_financial_year',
		)));

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Feste Habenkontonummer für Forderungspositionen'),
				'input',
				[
					'db_column' => 'fixed_expense_claim_debt_account_number'
				]
			)
		);

		$oTab->setElement($oDialog->createRow($oGui->t('Debitor anhand des Rechnungsadressaten ermitteln'), 'checkbox', [
			'db_column' => 'debitor_by_invoice'
		]));

		$oTab->setElement($oDialog->createRow($oGui->t('Zahlungseinträge aufteilen'), 'checkbox', [
			'db_column' => 'payment_entries_split'
		]));

		$oDialog->setElement($oTab);

		// Tab Verbuchung
		$oTabConfigAccounts = self::_getTabAccounts($oGui, $oDialog);
		$oDialog->setElement($oTabConfigAccounts);

		// Tab Zuweisung
		$oTabAllocation = $oDialog->createTab($oGui->t('Zuweisung'));
		$oTabAllocation->class = 'tab_allocation v-scrolling';
		$oDialog->setElement($oTabAllocation);

		return $oDialog;
	}

	static public function getAccountingRecordOptions(\Ext_Gui2 $oGui)
	{

		$aOptions = [
			'single' => $oGui->t('Pro Beleg ein Buchungssatz'),
			'line_item' => $oGui->t('Pro Rechnungsposition ein Buchungssatz'),
			'deferred_income' => $oGui->t('Passive Rechnungsabgrenzung (Umsätze auf Monate aufsplitten)')
		];

		$aOptions = \Ext_Thebing_Util::addEmptyItem($aOptions);

		return $aOptions;
	}

	/**
	 *
	 * @param array $_VARS
	 */
	public function switchAjaxRequest($_VARS)
	{

		if ($_VARS['task'] == 'update_inbox_options') {

			// Hier wird eine eigene dependency selection gebaut für Schulselect/Inboxselect,
			// da das noch nicht bei wiederholbaren Bereichen richtig funktioniert...

			$sDialogId = $_VARS['dialog_id'];
			$sDialogId = str_replace('ID_', '', $sDialogId);

			$aSelectedIds = explode('_', $sDialogId);

			$aSaveData = $_VARS['save'];

			$sAction = $_VARS['action'];

			// Daten in die WDBasic setzen
			$aTransfer = $this->saveDialogData($sAction, $aSelectedIds, $aSaveData, false, false);

			if (!$this->oWDBasic) {
				$this->_getWDBasicObject($aSelectedIds);
			}

			// Wiederholbare ContainerID
			$iContainerId = $_VARS['child_id'];

			$oCombination = $this->oWDBasic->getCombination($iContainerId);

			// Dependency Select Optionen in den aTransfer setzen
//			$this->_addDependencySelectionOptions($oCombination, $iContainerId, $aTransfer, 'school');
//
//			$this->_addDependencySelectionOptions($oCombination, $iContainerId, $aTransfer, 'inbox');


			$aTransfer['action'] = 'update_select_options';

		} elseif (
			$_VARS['task'] == 'openDialog' ||
			$_VARS['task'] == 'saveDialog'
		) {
			// Beim öffnen / nach Speichervorgang müssen die Dependencies erneut aufgebaut werden...

			$aTransfer = $this->_switchAjaxRequest($_VARS);

			if (!$this->oWDBasic) {
				$this->_getWDBasicObject($aSelectedIds);
			}

			$aCombinations = $this->oWDBasic->getCombinations();

			if (!empty($aCombinations)) {
				foreach ($aCombinations as $oCombination) {
//					$this->_addDependencySelectionOptions($oCombination, $oCombination->id, $aTransfer, 'school');
//					$this->_addDependencySelectionOptions($oCombination, $oCombination->id, $aTransfer, 'inbox');

				}
			} else {
				// Wenn keine Kombinationen vorhanden (Neu-öffnen eines Dialoges ohne ID), dann müssen trotzdem
				// die Dependency-Optionen der Schule geladen werden

				$oCombination = new \TsAccounting\Entity\Company\Combination();

//				$this->_addDependencySelectionOptions($oCombination, $oCombination->id, $aTransfer, 'school');
//				$this->_addDependencySelectionOptions($oCombination, $oCombination->id, $aTransfer, 'inbox');
			}

		} elseif ($_VARS['task'] == 'reload_allocations') {
			$aTransfer = $this->_getTransferAllocations($_VARS);
		} else {
			parent::switchAjaxRequest($_VARS);

			return; //return da im parent eh echo json_decode gemacht wird...
		}

		echo json_encode($aTransfer);

	}

	/**
	 * aTransfer manipulieren und die Dependency Select Options in die Container packen bei Inboxes
	 *
	 * @param \TsAccounting\Entity\Company\Combination $oCombination
	 * @param int $iContainerId
	 * @param array $aTransfer
	 * @param string $sType
	 */
//	protected function _addDependencySelectionOptions(\TsAccounting\Entity\Company\Combination $oCombination, $iContainerId, &$aTransfer, $sType)
//	{
//		return;
//		// Selection Klasse definieren
//
//		if($sType == 'inbox') {
//			$oSelection = new \TsAccounting\Gui2\Selection\Company\Combination\Inbox(true);
//		} else {
//			$oSelection = new \TsAccounting\Gui2\Selection\Company\Combination\School(true);
//		}
//
//		// in die Selection muss das joinedobjectchild gesetzt werden
//		$oSelection->oJoinedObject = $oCombination;
//
//		// Dropdown Optionen bilden
//		if($sType == 'inbox') {
//			$aSelectOptions	= Ext_Thebing_System::getInboxList('use_id', true);
//		} else {
//			$aSelectOptions	= Ext_Thebing_Client::getStaticSchoolListByAccess(true);
//		}
//
//		// Selection-Optionen holen
//		$aOptions	= $oSelection->getOptions($aSelectedIds, array(
//			'select_options' => $aSelectOptions,
//		), $this->oWDBasic);
//
//		// aTransfer manipulieren und select_options setzen
//
//		$aData = $aTransfer['data'];
//
//		$aValues = $aData['values'];
//
//		foreach($aValues as $iKey => $aSaveField) {
//			if(
//				(
//					$sType == 'inbox' &&
//					$aSaveField['db_column'] == 'inboxes'
//				) ||
//				(
//					$sType == 'school' &&
//					$aSaveField['db_column'] == 'schools'
//				)
//			) {
//
//				if(!empty($aOptions)) {
//					$aTransfer['data']['values'][$iKey]['select_options'][$iContainerId] = array();
//
//					// select_options in die Container-Optionen packen
//					foreach($aOptions as $iOptionKey => $mValue) {
//						$aTransfer['data']['values'][$iKey]['joined_object_options'] = 1;
//
//						$aTransfer['data']['values'][$iKey]['select_options'][$iContainerId][] = array(
//							'value'		=> $iOptionKey,
//							'text'		=> $mValue,
//							'selected'	=> '0',
//						);
//					}
//
//					// Beim erst-öffnen eines Dialoges ohne ID, werden JS-seitig mit Verbindung  value = select_options
//					// die Optionen angezeigt, deshalb muss man hier einen leer-Eintrag definieren
//					if(empty($aTransfer['data']['values'][$iKey]['value'])) {
//
////						$aTransfer['data']['values'][$iKey]['value'] = array(
////							array(
////								'id' => '0',
////								'value' => array(),
////								'key' => '0',
////								'index' => '0',
////							)
////						);
//
//					}
//				} else {
//
//					// Wenn Optionen leer sind, dann kann es keine Werte geben
//					// (leerer Eintrag mit ID=0,key=0 etc wird standardmäßig befüllt)
////					foreach($aSaveField['value'] as $iKeyValue => $aValueData) {
////						if($aValueData['key'] == $iContainerId) {
////							unset($aTransfer['data']['values'][$iKey]['value'][$iKeyValue]);
////						}
////					}
//
//				}
//
//			}
//		}
//
//	}

	/**
	 * Buchführungsoptionen (einfach/doppelt)
	 *
	 * @param \Ext_Gui2 $oGui
	 * @return array
	 */
	public static function getAccountingTypeOptions(\Ext_Gui2 $oGui)
	{
		$aOptions = array(
			'single' => $oGui->t('einfach'),
			'double' => $oGui->t('doppelt'),
		);

		$aOptions = \Ext_Thebing_Util::addEmptyItem($aOptions);

		return $aOptions;
	}

	/**
	 * Schnittstellenoptionen
	 *
	 * @param \Ext_Gui2 $oGui
	 * @return array
	 */
	public static function getInterfaceOptions(\Ext_Gui2 $oGui)
	{

		$aOptions = array(
			'datev' => 'DATEV',
			'universal' => $oGui->t('Allgemein'),
			'sage50' => 'Sage 50',
			'quickbooks' => 'Quickbooks',
			'xero' => 'Xero',
			'git' => 'GIT',
		);

		asort($aOptions);

		$aOptions = \Ext_Thebing_Util::addEmptyItem($aOptions);

		return $aOptions;
	}

	/**
	 * Tab Verbuchung
	 *
	 * @param \Ext_Gui2 $oGui
	 * @param \Ext_Gui2_Dialog $oDialog
	 * @return \Ext_Gui2_Dialog_Tab
	 */
	protected static function _getTabAccounts(\Ext_Gui2 $oGui, \Ext_Gui2_Dialog $oDialog)
	{
		// Daten
		$aAgencyBookingTypes = self::_getAgencyBookingTypes($oGui);

		$oTab = $oDialog->createTab($oGui->t('Verbuchung'));

		############## Kundeneinstellungen ##############

//		$oDivCustomer	= self::_createSettingsDiv('customer', $oGui, $oDialog);
//
//		$oH3			= $oDialog->create('h4');
//
//		$oH3->setElement($oGui->t('Kundeneinstellungen'));
//
//		$oDivCustomer->setElement($oH3);

//		$oRow			= self::_getAccountTypeRow('customer', $oGui, $oDialog);
//		$oDivCustomer->setElement($oRow);
//
//		$oRow			= self::_getUseNumberCheckbox('customer', 'customer', $oGui, $oDialog);
//		$oDivCustomer->setElement($oRow);
//
//		$oRow			= self::_getNumberrangeSelect('customer', 'customer', $oGui, $oDialog);
//		$oDivCustomer->setElement($oRow);
//
//		$oTab->setElement($oDivCustomer);

		############## Agentureinstellungen ##############

		$oDivAgency = self::_createSettingsDiv('agency', $oGui, $oDialog);
		
		$oH3			= $oDialog->create('h4');
		
		$oH3->setElement($oGui->t('Agentureinstellungen'));

		$oDivAgency->setElement($oH3);

//		$oRow			= self::_getAccountTypeRow('agency', $oGui, $oDialog);
//		$oDivAgency->setElement($oRow);

		$oRow = $oDialog->createRow($oGui->t('Verbuchungsart'), 'select', array(
			'db_column' => 'agency_account_booking_type',
			'required' => true,
			'select_options' => $aAgencyBookingTypes,
			'dependency_visibility' => array(
				'db_column' => 'accounting_type',
				'on_values' => array('double')
			)
		));

		$oDivAgency->setElement($oRow);

		######### Agentureinstellungen Aktiv #########

		// TODO Kann nicht auskommentiert werden, da im JS alles zusammenhängt
		$oDivAgencyActive = self::_createSettingsDiv('agency_active_settings', $oGui, $oDialog);
		$oDivAgencyActive->style = 'display: none';

		$oH3			= $oDialog->create('h4');

		$oH3->setElement($oGui->t('Verbuchungsart Aktiv'));

		$oDivAgencyActive->setElement($oH3);

		$oRow = self::_getUseNumberCheckbox('agency_active', 'agency', $oGui, $oDialog);
		$oDivAgencyActive->setElement($oRow);

		$oRow = self::_getNumberrangeSelect('agency_active', 'agency', $oGui, $oDialog);
		$oDivAgencyActive->setElement($oRow);

		$oDivAgency->setElement($oDivAgencyActive);

		######### Agentureinstellungen Aktiv & Passiv #########

		// TODO Kann nicht auskommentiert werden, da im JS alles zusammenhängt
		$oDivAgencyActivePassive = self::_createSettingsDiv('agency_activepassive_settings', $oGui, $oDialog);
		$oDivAgencyActivePassive->style = 'display: none';

		$oH3			= $oDialog->create('h4');

		$oH3->setElement($oGui->t('Verbuchungsart Aktiv und Passiv'));

		$oDivAgencyActivePassive->setElement($oH3);

		$oRow = self::_getUseNumberCheckbox('agency_activepassive', 'agency', $oGui, $oDialog);
		$oDivAgencyActivePassive->setElement($oRow);

		$oRow = self::_getNumberrangeSelect('agency_activepassive', 'agency', $oGui, $oDialog);
		$oDivAgencyActivePassive->setElement($oRow);

		$oDivAgency->setElement($oDivAgencyActivePassive);

		$oTab->setElement($oDivAgency);

		############## Leistungseinstellungen ##############

		$oDivService = self::_createSettingsDiv('service', $oGui, $oDialog);
		
		$oH3			= $oDialog->create('h4');
		
		$oH3->setElement($oGui->t('Einstellungen'));

		$oDivService->setElement($oH3);

		$oRow = $oDialog->createRow($oGui->t('Alle Vorzeichen umkehren'), 'select', array(
			'db_column' => 'service_account_book_reverse_sign',
			'select_options' => [
				'' => '',
				'1' => $oGui->t('Alle Positionen außer Agenturgutschriften'),
				'2' => $oGui->t('Alle Positionen außer Agenturgutschriften und Forderungspositionen'),
				'3' => $oGui->t('Alle Positionen')
			]
		));

		$oDivService->setElement($oRow);

		$oRow = $oDialog->createRow($oGui->t('Gutschrift als Reduktion bei der Verbuchung?'), 'checkbox', array(
			'db_column' => 'service_account_book_credit_as_reduction',
		));

		$oDivService->setElement($oRow);

		// Erträge
		$oDivIncome = self::_createSettingsDiv('service_income_settings', $oGui, $oDialog);
		$oDivIncome->class = 'fieldset_service_allocation';

		$oH3			= $oDialog->create('h4');

		$oH3->setElement($oGui->t('Erträge'));

		$oDivIncome->setElement($oH3);

		$aServiceTypes = array(
			'course' => 1,
			'additional_course' => 1,
			'accommodation' => 1,
			'additional_accommodation' => 1,
			'additional_general' => 1,
			'insurance' => 1,
			'cancellation' => 1,
			'currency' => 1,
			'activity' => 1
		);

		foreach ($aServiceTypes as $sServiceType => $iValue) {
			$oRow = self::_getAccountSettingsRow('income', $sServiceType, $oGui, $oDialog);

			$oDivIncome->setElement($oRow);
		}

		$oDivService->setElement($oDivIncome);

		// Aufwände CN

		// Diese Optionen werden wir manuell in nem anderen Fieldset darstellen
		unset($aServiceTypes['currency'], $aServiceTypes['cancellation']);

		$oFieldset = self::_getFieldset('service_expense_cn_settings', $oGui);
		$oFieldset->class = 'fieldset_service_allocation EXPENSE';

		$oLegend = self::_getLegend($oGui->t('Konten für Provisionen auf Agenturgutschriften'));

		$sMessage = $oGui->t('Diese Einstellungen gelten nur für Gutschriften basierend auf Brutto Agenturrechnungen (CN Agentur).');

		$oInfoDiv = $oDialog->createNotification($oGui->t('Info'), $sMessage, 'info', array(
			'row_style' => 'margin: 5px;'
		));

		$oFieldset->setElement($oInfoDiv);

		foreach ($aServiceTypes as $sServiceType => $iValue) {
			$oRow = self::_getAccountSettingsRow('expense_cn', $sServiceType, $oGui, $oDialog);

			$oFieldset->setElement($oRow);
		}

		$oFieldset->setElement($oLegend);

		$oDivService->setElement($oFieldset);

		// Provisionskonten, wenn Nettorechnungen mit Brutto- und Provisionsbetrag verbucht werden
		#book_net_with_gross_and_commission

		$oFieldset = self::_getFieldset('service_expense_net_settings', $oGui);
		$oFieldset->class = 'fieldset_expense_net EXPENSE';

		$oLegend = self::_getLegend($oGui->t('Konten für Provisionen auf Nettorechnungen'));

		$sMessage = $oGui->t('Diese Einstellungen gelten nur für Provisionen basierend auf Nettorechnungen an Agenturen.');

		$oInfoDiv = $oDialog->createNotification($oGui->t('Info'), $sMessage, 'info', array(
			'row_style' => 'margin: 5px;'
		));

		$oFieldset->setElement($oInfoDiv);

		foreach ($aServiceTypes as $sServiceType => $iValue) {
			$oRow = self::_getAccountSettingsRow('expense_net', $sServiceType, $oGui, $oDialog);

			$oFieldset->setElement($oRow);
		}

		$oFieldset->setElement($oLegend);

		$oDivService->setElement($oFieldset);


		// Aufwände Generell (Währung & Storno)
		$oDivExpenses = self::_createSettingsDiv('service_expense_settings', $oGui, $oDialog);
		$oDivExpenses->class = 'fieldset_service_allocation';

		$oH3			= $oDialog->create('h4');
		$oH3->setElement($oGui->t('Aufwände Generell'));

		$oDivExpenses->setElement($oH3);

		$oRow = self::_getAccountSettingsRow('expense', 'cancellation', $oGui, $oDialog);

		$oDivExpenses->setElement($oRow);

		$oRow = self::_getAccountSettingsRow('expense', 'currency', $oGui, $oDialog);

		$oDivExpenses->setElement($oRow);

		$oDivService->setElement($oDivExpenses);

		$oTab->setElement($oDivService);

		return $oTab;
	}

	/**
	 * Kontotyp Optionen (Individuell/Sammelkonto)
	 *
	 * @param \Ext_Gui2 $oGui
	 * @return array
	 */
	protected static function _getAccountTypeOptions(\Ext_Gui2 $oGui)
	{
		$aOptions = array(
			'1' => $oGui->t('Individuell'),
			//'2' => $oGui->t('Sammelkonto'),
		);

		$aOptions = \Ext_Thebing_Util::addEmptyItem($aOptions);

		return $aOptions;
	}

	/**
	 * Kontotyp-Select
	 *
	 * @param string $sType
	 * @param \Ext_Gui2 $oGui
	 * @param \Ext_Gui2_Dialog $oDialog
	 * @return \Ext_Gui2_Html_Div
	 */
	protected static function _getAccountTypeRow($sType, \Ext_Gui2 $oGui, \Ext_Gui2_Dialog $oDialog)
	{
		$aAccountTypes = self::_getAccountTypeOptions($oGui);

		$oRow = $oDialog->createRow($oGui->t('Kontotyp'), 'select', array(
			'select_options' => $aAccountTypes,
			'db_column' => $sType . '_account_type',
			'required' => true,
			'dependency_visibility' => array(
				'db_column' => 'accounting_type',
				'on_values' => array('double')
			)
		));

		return $oRow;
	}

	/**
	 * Divs für die verschiedenen Verbuchungseinstellungen (Kunde/Agentur/Leistung)
	 *
	 * @param string $sType
	 * @param \Ext_Gui2 $oGui
	 * @param \Ext_Gui2_Dialog $oDialog
	 * @return \Ext_Gui2_Html_Div
	 */
	protected static function _createSettingsDiv($sType, \Ext_Gui2 $oGui, \Ext_Gui2_Dialog $oDialog)
	{
		$oDiv = $oDialog->create('div');

		$oDiv->id = $sType . '_settings_' . $oGui->hash . '_' . $oGui->instance_hash;

		return $oDiv;
	}

	/**
	 * Optionen für die Verbuchungsart
	 *
	 * @param \Ext_Gui2 $oGui
	 * @return array
	 */
	protected static function _getAgencyBookingTypes(\Ext_Gui2 $oGui)
	{
		$aOptions = array(
			'1' => $oGui->t('Aktiv'),
			'2' => $oGui->t('Aktiv und Passiv'),
		);

		$aOptions = \Ext_Thebing_Util::addEmptyItem($aOptions);

		return $aOptions;
	}

	/**
	 * Agentur/Kundenummer als Kontonummer verwenden Option
	 *
	 * @param string $sType
	 * @param string $sTypeDependency
	 * @param \Ext_Gui2 $oGui
	 * @param \Ext_Gui2_Dialog $oDialog
	 * @return \Ext_Gui2_Html_Div
	 */
	protected static function _getUseNumberCheckbox($sType, $sTypeDependency, \Ext_Gui2 $oGui, \Ext_Gui2_Dialog $oDialog)
	{
		if ($sType == 'customer') {
			$sTitleNumber = 'Kundennummer';
		} else {
			$sTitleNumber = 'Agenturnummer';
		}

		$oRow = $oDialog->createRow($oGui->t($sTitleNumber . ' als Kontonummer verwenden?'), 'checkbox', array(
			'db_column' => $sType . '_account_use_number',
			'dependency_visibility' => array(
				'db_column' => $sTypeDependency . '_account_type',
				'on_values' => array('1'),
			),
		));

		return $oRow;
	}

	/**
	 * Legende einheitlich über diese Methode generieren
	 *
	 * @param string $sTitle
	 * @return \Ext_Gui2_Html_Fieldset_Legend
	 */
	protected static function _getLegend($sTitle)
	{
		$oLegend = new \Ext_Gui2_Html_Fieldset_Legend();

		$oLegend->setElement($sTitle);

		return $oLegend;
	}

	/**
	 * Fieldset einheitlich über diese Methode generieren
	 *
	 * @param string $sIdAddon
	 * @param \Ext_Gui2 $oGui
	 * @return \Ext_Gui2_Html_Fieldset
	 */
	protected static function _getFieldset($sIdAddon, \Ext_Gui2 $oGui)
	{

		$oFieldset = new \Ext_Gui2_Html_Fieldset();

		$oFieldset->id = $sIdAddon . '_' . $oGui->hash . '_' . $oGui->instance_hash;

		return $oFieldset;
	}

	/**
	 *
	 * @param type $sType
	 * @param type $sTypeDependency
	 * @param \Ext_Gui2 $oGui
	 * @param \Ext_Gui2_Dialog $oDialog
	 * @return \Ext_Gui2_Html_Div
	 */
	protected static function _getNumberrangeSelect($sType, $sTypeDependency, \Ext_Gui2 $oGui, \Ext_Gui2_Dialog $oDialog)
	{
		// Alle Nummernkreise mit der Kategorie "Konten" laden
		$aNumberranges = \Ext_TS_NumberRange::getNumberrangesByCategory('global');
		$aNumberranges = \Ext_Thebing_Util::addEmptyItem($aNumberranges);

		$oRow = $oDialog->createRow($oGui->t('Nummernkreis'), 'select', array(
			'db_column' => $sType . '_account_numberrange_id',
			//'required'					=> true,
			'select_options' => $aNumberranges,
			'dependency_visibility' => array(
				'db_column' => $sTypeDependency . '_account_type',
				'on_values' => array('1'),
			),
		));

		return $oRow;
	}

	/**
	 *
	 * @param string $sType
	 * @param string $sTypeService
	 * @param \Ext_Gui2 $oGui
	 * @param \Ext_Gui2_Dialog $oDialog
	 * @return \Ext_Gui2_Html_Div
	 * @see Ext_Thebing_Provision_Gui2
	 */
	public static function _getAccountSettingsRow($sType, $sTypeService, \Ext_Gui2 $oGui, \Ext_Gui2_Dialog $oDialog, array $inputOptions = [])
	{

		// Optionen für die verschiedenen Leistungen
		$aTypeOptions = self::getServiceTypeOptions($sTypeService, $sType);

		$inputTypeName = 'Konto';
		$inputTypeNamePlural = 'Konten';
		$inputTypeKeyPrefix = 'service_';
		$inputTypeKeySeparator = '_account_';
		if ($sType == 'commission') {
			$inputTypeName = 'Provisionssatz';
			$inputTypeNamePlural = 'Provisionssätze';
			$inputTypeKeyPrefix = '';
			$inputTypeKeySeparator = '_';
		}

		// Select Optionen
		if (isset($aTypeOptions['type_key'])) {
			$selectOptions['1'] = $oGui->t('Unterschiedliche ' . $inputTypeNamePlural . ' pro ' . $aTypeOptions['type_key']);
		}

		if (isset($aTypeOptions['parent_type_key'])) {
			$selectOptions['2'] = $oGui->t('Unterschiedliche ' . $inputTypeNamePlural . ' pro ' . $aTypeOptions['parent_type_key']);
		} elseif (isset($aTypeOptions['parent_type_string'])) {
			$selectOptions['2'] = $oGui->t($aTypeOptions['parent_type_string']);
		}

		$selectOptions['3'] = $oGui->t('Ein ' . $inputTypeName . ' für alle ' . $aTypeOptions['type_key_multiple']);

		$selectOptions = \Ext_Thebing_Util::addEmptyItem($selectOptions);

		// Bei Erträgen das Wort "Verbuchung" verwenden & bei Aufwänden "Provisionen", nur bei Währung
		// & Stornos gibt es in beiden Fällen nur Verbuchungen
		if ($sType == 'income' || $sTypeService == 'cancellation' || $sTypeService == 'currency') {
			$sTitleForType = 'Verbuchung';
		} else {
			$sTitleForType = 'Provisionen';
		}

		if ($sTypeService == 'currency') {
			$sTitleForType .= ' je ';
		} else {
			$sTitleForType .= ' von ';
		}

		$inputOptions['db_column'] = $inputTypeKeyPrefix . $sType . $inputTypeKeySeparator . $sTypeService;
		$inputOptions['required'] = true;
		$inputOptions['select_options'] = $selectOptions;
		$inputOptions['default_value'] = '3';

		$oRow = $oDialog->createRow($oGui->t($sTitleForType . $aTypeOptions['type_key_multiple_label']), 'select', $inputOptions);

		return $oRow;
	}

	/**
	 * Leistungseinstellungen
	 *
	 * @param string $sServiceType
	 * @return array
	 */
	public static function getServiceTypeOptions($sServiceType, $sTypeAccount = null)
	{

		switch ($sServiceType) {
			case 'course':
				$aOptions = array(
					'real_name' => 'Kurse',
					'type_key' => 'Kurs',
					'parent_type_key' => 'Kategorie',
					'type_key_multiple' => 'Kurse',
					'type_key_multiple_label' => 'Kursen',
					'error_name' => 'Der Kurs',
					'error_name_multiple' => 'Die Kurse',
				);
				break;
			case 'additional_course':
				$aOptions = array(
					'real_name' => 'Zusätzliche Kursgebühren',
					'type_key' => 'Gebühr',
					'parent_type_key' => 'Kurs',
					'type_key_multiple' => 'Gebühren',
					'type_key_multiple_label' => 'zusätzlichen Kursgebühren',
					'error_name' => 'Die zusätzliche Kursgebühr',
					'error_name_multiple' => 'Die zusätzlichen Kursgebühren',
				);
				break;
			case 'accommodation':
				$aOptions = array(
					'real_name' => 'Unterkünfte',
					'parent_type_key' => 'Kategorie',
					'type_key_multiple' => 'Unterkünfte',
					'type_key_multiple_label' => 'Unterkünften',
					'error_name' => 'Die Unterkunft',
					'error_name_multiple' => 'Die Unterkünfte',
				);
				break;
			case 'additional_accommodation':
				$aOptions = array(
					'real_name' => 'Zusätzliche Unterkunftsgebühren',
					'type_key' => 'Gebühr',
					'parent_type_key' => 'Unterkunftskategorie',
					'type_key_multiple' => 'Gebühren',
					'type_key_multiple_label' => 'zusätzlichen Unterkunftsgebühren',
					'error_name' => 'Die zusätzliche Unterkunftsgebühr',
					'error_name_multiple' => 'Die zusätzlichen Unterkunftsgebühren',
				);
				break;
			case 'additional_general':
				$aOptions = array(
					'real_name' => 'Generellen Gebühren',
					'type_key' => 'Gebühr',
					'type_key_multiple' => 'Gebühren',
					'type_key_multiple_label' => 'generellen Gebühren',
					'error_name' => 'Die generelle Gebühr',
					'error_name_multiple' => 'Die generellen Gebühren',
				);
				break;
			case 'insurance':
				$aOptions = array(
					'real_name' => 'Versicherungen',
					'type_key' => 'Versicherung',
					'type_key_multiple' => 'Versicherungen',
					'type_key_multiple_label' => 'Versicherungen',
					'error_name' => 'Die Versicherung',
					'error_name_multiple' => 'Die Versicherungen',
				);
				break;
			case 'activity':
				$aOptions = array(
					'real_name' => 'Aktivitäten',
					'type_key' => 'Aktivität',
					'type_key_multiple' => 'Aktivitäten',
					'type_key_multiple_label' => 'Aktivitäten',
					'error_name' => 'Die Aktivität',
					'error_name_multiple' => 'Die Aktivitäten',
				);
				break;
			case 'cancellation':
				$aOptions = array(
					'real_name' => 'Stornierungen',
					'type_key' => 'Gebühr',
					'type_key_multiple' => 'Gebühren',
					'type_key_multiple_label' => 'Stornierungen',
					'error_name' => 'Die Stornierungen',
					'error_name_multiple' => 'Die Stornierungen',
				);

				if ($sTypeAccount == 'expense') {
					unset($aOptions['type_key']);
				}

				break;
			case 'currency':
				$aOptions = array(
					'type_key' => 'Währung',
					'type_key_multiple' => 'Währungen',
					'type_key_multiple_label' => 'Währung',
				);
				break;
			case 'transfer':
				$aOptions = array(
					'real_name' => 'Transfer',
					'error_name' => 'Der Transfer',
					'error_name_multiple' => 'Die Transfers',
				);
				break;
			case 'extra_position':
				$aOptions = array(
					'real_name' => 'Nicht zugeordnete Extrapositionen',
					'error_name' => 'Die nicht zugeordnete Extraposition',
					'error_name_multiple' => 'Die nicht zugeordneten Extrapositionn',
				);
				break;
			case 'manual_creditnote':
				$aOptions = array(
					'real_name' => 'Manuelle Agenturgutschriften',
					'error_name' => 'Die manuelle Agenturgutschrift',
					'error_name_multiple' => 'Die manuellen Agenturgutschriften',
				);
				break;
			case 'commission':
				$aOptions = array(
					'real_name' => 'Provisionen',
					'error_name' => 'Die Provision',
					'error_name_multiple' => 'Die Provisionen',
				);
				break;
			default:
				$aOptions = array();
		}

		return $aOptions;
	}


	/**
	 * siehe parent
	 *
	 * @param string $sError
	 * @param string $sField
	 * @param string $sLabel
	 * @param string $sAction
	 * @param string $sAdditional
	 * @return string
	 */
	protected function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null)
	{
		switch ($sError) {
			case 'COMBINATION_NOT_FREE':

				$sErrorMessage = $this->t('Kombination wurde schon angelegt!');

				break;

			case 'ACTIVE_PASSIVE_NUMBERRANGE':

				$sErrorMessage = $this->t('Der Nummernkreis für die Agenturkonten kann entweder für die aktive Verbuchung oder für die passive Verbuchung verwendet werden. Eine Zuweisung des gleichen Nummernkreises ist nicht möglich. Bitte korrigieren Sie ihre Eingabe.');

				break;

			case 'SAME_ACCOUNT_FOR_DIFFERENT_CURRENCY':

				$sErrorMessage = '%service_group "%service_name" hat die gleiche Kontonummer für verschiedene Währungen! (%currency_list)';

				$sErrorMessage = $this->t($sErrorMessage);

				break;

			case 'SAME_ACCOUNT_FOR_AUTOMATIC_ACCOUNT':

				$sErrorMessage = '%service_group "%service_name" hat die gleiche Automatik-Kontonummer für verschiedene Steuersätze! (%currency_list)';

				$sErrorMessage = $this->t($sErrorMessage);

				break;

			case 'SAME_ACCOUNT_FOR_ALL_ACCOUNT':

				$sErrorMessage = '%service_group haben gleiche Automatik-Kontonummern für verschiedene Steuersätze! (%currency_list)';

				$sErrorMessage = $this->t($sErrorMessage);

				break;

			default:
				$sErrorMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
				break;
		}

		return $sErrorMessage;
	}

	/**
	 * aTransfer fürs HTML-Zeug im Zuweisungsdialog
	 *
	 * @param array $aVars
	 * @return array
	 */
	protected function _getTransferAllocations($aVars)
	{
		$aVars = (array)$aVars;

		$aSelectedIds = (array)$aVars['id'];

		if (!$this->oWDBasic) {
			$this->_getWDBasicObject($aSelectedIds);
		}

		/** @var \TsAccounting\Entity\Company $oCompany */
		$oCompany = $this->oWDBasic;

		// Das Zuweisungsobjekt ist in der Gui2_Dialog_Data gecached und in der Session der Gui enthalten, deshalb
		// entfernen wir das Zuweisungs-Objekt an dieser Stelle, damit bei einem neuen Request nicht das vorher erstellte
		// Zuweisungs-Objekt geladen wird...
		$oCompany->resetAllocationObject();

		// Daten in die WDBasic setzen
		$aTransfer = $this->saveDialogData($aVars['action'], $aSelectedIds, $aVars['save'], false, false);

		$oAllocation = $oCompany->getAllocationsObject();

		$oAllocation->setTranslationPath($this->_oGui->gui_description);

		$aTransfer['action'] = 'set_allocation_html';

		$oDivMain = new \Ext_Gui2_Html_Div();
		$oDivMain->class = 'GUIDialogContentPadding';

		############### Hinweis ###############

		$this->_addAllocationNotification($oDivMain);

		############### Erträge ###############

		$this->_addAccountTypeElements('income', $oAllocation, $oDivMain);

		############### Aufwände ###############

		$this->_addAccountTypeElements('expense', $oAllocation, $oDivMain);

		if ($this->oWDBasic->book_net_with_gross_and_commission) {
			$this->_addAccountTypeElements('expense_net', $oAllocation, $oDivMain);
		}

		############### Bestandskonten ###############

		// Bestandskonten-Einstellungen sollen ausgeblendet werden, wenn
		// die Buchführungs-Einstellung 'einfach' ist.
		// !Werden doch benötigt, wenn man die Buchungssätze bei 'Nicht-Automatikkonten' aufteilt!
		#if($this->oWDBasic->accounting_type !== 'single') {
		$this->_addAccountTypeElements('continuance', $oAllocation, $oDivMain);
		#}

		############### Bezahlmethoden ###############

		// Verrechnungskonten-Einstellungen sollen ausgeblendet werden, wenn
		// die Buchführungs-Einstellung 'einfach' ist.
		if ($this->oWDBasic->accounting_type !== 'single') {
			$this->_addAccountTypeElements('clearing', $oAllocation, $oDivMain);
		}

		// HTML generieren
		$aTransfer['allocation_html'] = $oDivMain->generateHTML();

		$aVatRates = $oAllocation->getVatRates();

		unset($aVatRates[0]);

		$aTransfer['vat_rates'] = array_keys($aVatRates);

		return $aTransfer;

	}

	/**
	 *
	 * @param string $sTitle
	 * @return \Ext_Gui2_Html_H4
	 */
	protected function _getH3($sTitle)
	{
		$oH3 = new \Ext_Gui2_Html_H4();
		$oH3->setElement($sTitle);

		return $oH3;
	}

	/**
	 *
	 * @return \Ext_Gui2_Html_Table
	 */
	protected function _getAllocationsTable()
	{

		$oTable = new \Ext_Gui2_Html_Table();
		$oTable->class = 'table table-condensed table-hover';

		return $oTable;
	}

	/**
	 * Zuweisungselemente in die Tabelle einfügen
	 *
	 * @param array $aElements
	 * @param array $aHeaderData
	 * @param \Ext_Gui2_Html_Table $oTable
	 * @param \TsAccounting\Entity\Company\AccountAllocation $oAllocation
	 */
	protected function _addElementsToTable(string $sAccountType, array $aElements, array $aHeaderData, \Ext_Gui2_Html_Table $oTable, \TsAccounting\Entity\Company\AccountAllocation $oAllocation)
	{

		foreach ($aElements as $sRowKey => $aKeys) {

			$oTr = new \Ext_Gui2_Html_Table_tr();

			$oTd = new \Ext_Gui2_Html_Table_Tr_Td();

			$sTypeName = (string)$oAllocation->getAccountName($sRowKey);

			if (empty($sTypeName)) {
				$sTypeName = $this->t('Alle');
			}

			$oTd->setElement($sTypeName);

			$oTr->setElement($oTd);

			#if($sTypeName === 'Wire transfer') dd($aHeaderData);

			foreach ($aHeaderData as $aHeader) {
				$oTd = new \Ext_Gui2_Html_Table_Tr_Td();

				$sKeyCheck = $sRowKey . $oAllocation->sKeyDelimiter . $aHeader['currency_iso'] . $oAllocation->sKeyDelimiter . $aHeader['vat_rate'];

				if ($oAllocation->hasKey($sKeyCheck)) {

					$aAllocation = $oAllocation->getAllocation($sKeyCheck);

					$oDiv = new \Ext_Gui2_Html_Div();
					$oDiv->class = 'input-group input-group-sm';
					$oDiv->title = $aHeader['title'];

					$oDivCheckbox = new \Ext_Gui2_Html_Div();
					$oDivCheckbox->class = 'input-group-addon';

					if ($this->oWDBasic->automatic_account_setting === 'per_account') {

						$oCheckbox = new \Ext_Gui2_Html_Input();

						$oCheckbox->type = 'checkbox';
						$oCheckbox->class = 'txt';
						$oCheckbox->title = $this->_oGui->t('Automatikkonto');
						$oCheckbox->name = 'save[allocations][' . $sKeyCheck . '][automatic_account]';
						$oCheckbox->id = 'allocations_' . $sKeyCheck . '_automatic_account';

						if (
							isset($aAllocation['automatic_account']) &&
							$aAllocation['automatic_account'] == 1
						) {
							$oCheckbox->checked = 'checked';
						}

						$oCheckbox->value = '1';

						$oDivCheckbox->setElement($oCheckbox);
						$oDiv->setElement($oDivCheckbox);

					}

					$oInput = new \Ext_Gui2_Html_Input();

					$oInput->type = 'text';

					$oInput->name = 'save[allocations][' . $sKeyCheck . '][account_number]';

					$oInput->id = 'allocations_' . $sKeyCheck . '_account_number';

					$oInput->maxlength = '12';
					$oInput->title = $this->_oGui->t('Ertragskonto');
					$oInput->placeholder = $this->_oGui->t('Ertragskonto');

					if (isset($aAllocation['account_number'])) {
						$oInput->value = $aAllocation['account_number'];
					}

					$sInputClass = 'txt w100 form-control input-sm';

					if ($aHeader['vat_rate'] == 0) {
						$sInputClass .= ' default_rate_input';
					}

					$oInput->class = $sInputClass;

					$oDiv->setElement($oInput);

					if (
						$this->oWDBasic->additional_booking_record_for_discount === 'all' ||
						(
							$sAccountType == 'income' &&
							$this->oWDBasic->additional_booking_record_for_discount === 'not_commission'
						)
					) {

						$oInput = new \Ext_Gui2_Html_Input();

						$oInput->type = 'text';

						$oInput->name = 'save[allocations][' . $sKeyCheck . '][account_number_discount]';

						$oInput->id = 'allocations_' . $sKeyCheck . '_account_number_discount';

						$oInput->maxlength = '12';
						$oInput->title = $this->_oGui->t('Rabattkonto');
						$oInput->placeholder = $this->_oGui->t('Rabattkonto');

						if (isset($aAllocation['account_number_discount'])) {
							$oInput->value = $aAllocation['account_number_discount'];
						}

						$sInputClass = 'txt w100 form-control input-sm';

						if ($aHeader['vat_rate'] == 0) {
							$sInputClass .= ' default_rate_input';
						}

						$oInput->class = $sInputClass;

						$oDiv->setElement($oInput);
					}

					$oTd->setElement($oDiv);

				}

				$oTr->setElement($oTd);
			}

			$oTable->setElement($oTr);
		}
	}

	/**
	 * Zwischenüberschrift erstellen
	 *
	 * @param mixed $mElement
	 * @param int $iColspan
	 * @param \Ext_Gui2_Html_Table $oTable
	 * @param bool $bTh
	 */
	protected function _addSubHeader($mElement, $iColspan, \Ext_Gui2_Html_Table $oTable, $bTh = false)
	{
		$oTr = new \Ext_Gui2_Html_Table_tr();

		if ($bTh) {
			$oTd = new \Ext_Gui2_Html_Table_Tr_Th();
		} else {
			$oTd = new \Ext_Gui2_Html_Table_Tr_Td();
		}

		$oTd->colspan = $iColspan;

		$oTd->setElement($mElement);

		$oTr->setElement($oTd);

		$oTable->setElement($oTr);
	}

	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true)
	{
		global $_VARS;

		if ($bSave) {
			\DB::begin('save_company');
		}

		$ignoreErrors = (isset($_VARS['ignore_errors']) && $_VARS['ignore_errors'] == 1) ? true : false;

		if (
			$bSave &&
			$ignoreErrors === false &&
			(
				$aData['automatic_release'] == 1 ||
				$aData['automatic_stack_export'] == 1
			)
		) {

			$aTransfer = [];
			$aTransfer['data']['id'] = $_VARS['dialog_id'];
			$aTransfer['data']['values'] = [];
			$aTransfer['action'] = 'saveDialogCallback';
			$aTransfer['data']['show_skip_errors_checkbox'] = 1;
			$aTransfer['error'][] = array(
				'message' => $this->_oGui->t('Jegliche Fehler werden bei dem automatischen Freigeben und Weiterverarbeiten ignoriert. Demnach kann es vorkommen, dass Belegtexte, Kontenzuweisungen oder Steuerhinweise fehlen. Kontrollieren Sie die Firmen- und Rechnungseinstellungen und daraus resultierende Exporte gründlich!'),
				'type' => 'hint',
			);

		} else {

			$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);

			if (!$this->oWDBasic) {
				$this->_getWDBasicObject($aSelectedIds);
			}

			$oCompany = $this->oWDBasic;
			/** @var $oCompany \TsAccounting\Entity\Company */

			if (isset($aData['allocations'])) {
				$oCompany->setAllocationData((array)$aData['allocations']);
			}

			$oAllocation = $oCompany->getAllocationsObject();

			if ($bSave) {
				$aErrorsAllocations = array();

				$mReturnAllocations = $oCompany->saveAllocations($ignoreErrors);

				$showHint = true;
				if (!empty($aTransfer['error'])) {
					$showHint = false;
				}

				if (is_array($mReturnAllocations)) {

					foreach ($mReturnAllocations as $sErrorType => $aErrorByType) {

						foreach ($aErrorByType as $sKeyAllocationGroup => $aCurrencyList) {

							// Alle leeren Array-Einträge löschen, damit der Fehlerstring
							// richtig dargestellt wird (ohne unnötige ',')
							foreach ($aCurrencyList as $sCurrencyEntryKey => $sCurrencyEntryValue) {
								if ($sCurrencyEntryValue === '') {
									unset($aCurrencyList[$sCurrencyEntryKey]);
								}
							}

							$aKeyData = explode($oAllocation->sKeyDelimiter, $sKeyAllocationGroup);

							$sTypeAccount = $aKeyData[0];

							$sServiceType = $aKeyData[1];

							$aErrorData = $this->getServiceTypeOptions($sServiceType, $sTypeAccount);

							$sTypeName = $oAllocation->getAccountName($sKeyAllocationGroup);

							$sServiceGroup = $aErrorData['error_name'];

							// Sollte die ID 0 sein gibt es keinen eindeutigen Datensatz.
							// Dies kann nur passieren wenn bspw. im Dialog "Ein Konto für alle Kurse" ausgewählt ist.
							// Dann wird ein Satz im Plural dargestellt.
							if (
								$sTypeName === '' &&
								(int)$aKeyData[2] === 0
							) {
								$sErrorType = 'SAME_ACCOUNT_FOR_ALL_ACCOUNT';
								$sServiceGroup = $aErrorData['error_name_multiple'];
							}

							if (
								$sErrorType != 'SAME_ACCOUNT_FOR_ALL_ACCOUNT' &&
								$sErrorType != 'SAME_ACCOUNT_FOR_AUTOMATIC_ACCOUNT'
							) {
								$showHint = false;
							}

							$sErrorMessage = $this->_getErrorMessage($sErrorType, '');

							$sErrorMessage = str_replace('%service_group', $sServiceGroup, $sErrorMessage);

							$sErrorMessage = str_replace('%service_name', $sTypeName, $sErrorMessage);

							$sCurrencyList = implode(', ', $aCurrencyList);

							$sErrorMessage = str_replace('%currency_list', $sCurrencyList, $sErrorMessage);

							foreach ($aCurrencyList as $sKeyAllocation => $sCurrency) {
								$aErrorsAllocations[] = array(
									'input' => array(
										'id' => 'allocations_' . $sKeyAllocation . '_account_number',
									),
									'message' => $sErrorMessage,
									'type' => $showHint ? 'hint' : 'error'
								);
							}
						}
					}
				}

				if (
					!$showHint &&
					empty($aTransfer['error']) &&
					!empty($aErrorsAllocations)
				) {
					$aTransfer['error'][] = [
						'message' => $this->getTranslation('error_dialog_title'),
						'type' => $showHint ? 'hint' : 'error'
					];
				}

				$aTransfer['data']['show_skip_errors_checkbox'] = $showHint ? 1 : 0;
				$aTransfer['error'] = array_merge($aTransfer['error'], $aErrorsAllocations);
			}
		}

		$aErrors = $aTransfer['error'];

		if ($bSave) {
			if (!empty($aErrors)) {
				\DB::rollback('save_company');
			} else {
				\DB::commit('save_company');
			}
		}

		return $aTransfer;
	}

	/**
	 *
	 * @param \Ext_Gui2_Html_Div $oDivMain
	 */
	protected function _addAllocationNotification(\Ext_Gui2_Html_Div &$oDivMain)
	{

		$oDialog = new \Ext_Gui2_Dialog;

		$sTitle = $this->_oGui->t('Information');
		$sMessage = $this->_oGui->t('Bitte markieren Sie die Checkbox vor dem entsprechenden Konto, falls es sich bei dem Konto um ein Automatikkonto handelt.');

		$oNotification = $oDialog->createNotification($sTitle, $sMessage, 'info');

		$oDivMain->setElement($oNotification);

	}

	protected function _addAccountTypeElements($sAccountType, \TsAccounting\Entity\Company\AccountAllocation $oAllocation, \Ext_Gui2_Html_Div $oDivMain)
	{
		$oH3 = new \Ext_Gui2_Html_H4();
	
		$sTitleAccountType = $this->_getAccountTypeTitle($sAccountType);

		$oH3->setElement($sTitleAccountType);

		$oDivMain->setElement($oH3);

		$oTable = $this->_getAllocationsTable();

		if ($sAccountType === 'clearing') {
			$aVatRates = array(0 => '');
		} else {
			$aVatRates = $oAllocation->getVatRates();
		}

		$aCurrencyList = $oAllocation->getCurrencyList($sAccountType);

		$oTr = new \Ext_Gui2_Html_Table_tr();

		$aHeaderData[0] = array(
			'title' => '',
		);

		foreach ($aVatRates as $iRate => $sRate) {

			if (
				$iRate == 0 &&
				empty($sRate)
			) {
				$sRate = $this->t('Kein Steuersatz');
			}

			foreach ($aCurrencyList as $iCurrencyId => $sCurrency) {

				$sTitleTh = trim($sCurrency . ' ' . $sRate);

				$aData = array(
					'vat_rate' => $iRate,
					'currency_iso' => $iCurrencyId,
					'title' => $sTitleTh,
				);

				$aHeaderData[] = $aData;
			}
		}

		foreach ($aHeaderData as $iHeader => $aHeader) {

			$oTh = new \Ext_Gui2_Html_Table_Tr_Th();

			$oTh->setElement($aHeader['title']);

			if ($iHeader > 0) {
				$oTh->style = 'width:120px;';
			} else {
				$oTh->style = 'width:auto;';
			}

			$oTr->setElement($oTh);
		}

		$oTable->setElement($oTr);

		$iColspan = count($aHeaderData);

		unset($aHeaderData[0]);

		$aGroupedData = $oAllocation->getGroupedAllocations($oAllocation->getAllocations());

		$aDataAccountType = (array)$aGroupedData[$sAccountType];

		foreach ($aDataAccountType as $sTypeAllocations => $aElementData) {
			$sServiceType = str_replace('_allocations', '', $sTypeAllocations);

			$aTypeData = $this->getServiceTypeOptions($sServiceType, $sAccountType);

			$sServiceTypeName = (string)$aTypeData['real_name'];

			if (!empty($sServiceTypeName)) {
				$sServiceTypeName = $this->t($sServiceTypeName);
			}

			if (!empty($sServiceTypeName)) {
				$this->_addSubHeader($sServiceTypeName, $iColspan, $oTable, true);
			}

			if (isset($aElementData['elements'])) {
				$aElements = $aElementData['elements'];

				$this->_addElementsToTable($sAccountType, $aElements, $aHeaderData, $oTable, $oAllocation);
			}

			if (isset($aElementData['school_data'])) {
				foreach ($aElementData['school_data'] as $iSchoolId => $aSchoolElements) {
					$oSchool = \Ext_Thebing_School::getInstance($iSchoolId);

					$oH3 = $this->_getH3((string)$oSchool->getName());

					$oH3->style = 'margin: 5px 0;';

					$this->_addSubHeader($oH3, $iColspan, $oTable);

					if (isset($aSchoolElements['parent_data'])) {

						foreach ($aSchoolElements['parent_data'] as $iParentTypeId => $aElements) {
							$aDataParent = array(
								1 => $sServiceType,
								3 => $iParentTypeId,
							);

							$sParentName = (string)$oAllocation->getAccountName($aDataParent);

							$oLabel = new \Ext_Gui2_Html_Label();

							$oLabel->style = 'font-weight:bold; margin-left:5px; line-height:22px;';

							$oLabel->setElement($sParentName);

							$this->_addSubHeader($oLabel, $iColspan, $oTable);

							$this->_addElementsToTable($sAccountType, $aElements, $aHeaderData, $oTable, $oAllocation);
						}
					} else {
						$this->_addElementsToTable($sAccountType, $aSchoolElements, $aHeaderData, $oTable, $oAllocation);
					}

				}
			}
		}


		$oDivMain->setElement($oTable);
	}

	/**
	 *
	 * @param string $sAccountType
	 * @return string
	 */
	protected function _getAccountTypeTitle($sAccountType)
	{
		$sTitle = '';

		switch ($sAccountType) {
			case 'income':
				$sTitle = $this->t('Ertragskonten');
				break;
			case 'expense':
				$sTitle = $this->t('Aufwandskonten (Provisionsgutschriften)');
				break;
			case 'expense_net':
				$sTitle = $this->t('Aufwandskonten (Provisionen von Nettorechnungen)');
				break;
			case 'continuance':
				$sTitle = $this->t('Bestandskonten');
				break;
			case 'clearing':
				$sTitle = $this->t('Verrechnungskonten');
				break;
			default:
				break;
		}

		return $sTitle;
	}

	public function resetAllocationData()
	{
		foreach ($this->_aAllocations as $sKey => $aAllocation) {
			$this->_aAllocations[$sKey]['account_number'] = '';
			$this->_aAllocations[$sKey]['account_number_discount'] = '';
			$this->_aAllocations[$sKey]['automatic_account'] = 0;
		}
	}

	protected function getSchools()
	{
		return $this->_oCompany->getCombinationObjectArray('getSchools');
	}

	/**
	 * Konten-Zuweisung generieren
	 *
	 * @param array $aAllocationData
	 */
	public function createAllocation($aAllocationData)
	{
		$aCurrencyIds = $this->getCurrencyList($aAllocationData['account_type']);

		foreach ($aCurrencyIds as $iCurrencyId => $sCurrency) {
			if (in_array($aAllocationData['type'], ['vat', 'payment_method'])) {
				$aVatRates = array(0 => '');
			} else {
				$aVatRates = $this->_aVatRates;
			}

			foreach ($aVatRates as $iRate => $sRate) {
				$aAllocationData['currency_iso'] = $iCurrencyId;

				$aAllocationData['vat_rate'] = $iRate;

				$sKey = $this->generateKey($aAllocationData);

				$this->_aAllocations[$sKey] = $aAllocationData;
			}
		}
	}

	/**
	 * Alle Konten-Zuweisungen
	 *
	 * @return array
	 */
	public function getAllocations()
	{
		return $this->_aAllocations;
	}

}
