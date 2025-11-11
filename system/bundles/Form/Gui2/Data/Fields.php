<?php

namespace Form\Gui2\Data;

class Fields extends \Ext_Gui2_Data {

	static public $iFormId;
	
	public static function getDialog(\Ext_Gui2 $oGui) {

		$oDialog = $oGui->createDialog('Feld "{name}" bearbeiten', 'Neues Feld anlegen');
		$oDialog->save_as_new_button = true;
		$oDialog->width = 1100;

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Name'), 
				'input', 
				array(
					'db_column' => 'name',
					'db_alias' => 'f_o',
					'required' => true
				)
			)
		);
		
		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Seite'), 
				'select', 
				array(
					'db_column' => 'page_id',
					'db_alias' => 'f_o',
					'required' => true,
					'selection' => new \Form\Gui2\Selection\Pages(),
				)
			)
		);
		
		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Typ'), 
				'select', 
				array(
					'db_column' => 'type',
					'db_alias' => 'f_o',
					'select_options' => \Util::addEmptyItem(self::getTypes()),
					'required' => true,
					'child_visibility' => [
						[
							'class' => 'reference-container',
							'on_values' => array('reference')
						],
						[
							'db_column' => 'file',
							'db_alias' => 'f_o',
							'on_values' => array('file_reference')
						],
						[
							'class' => 'methodcall-container',
							'on_values' => array('method_call')
						]
					]
				)
			)
		);
		
		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Vorbelegung (Auswahlliste mit Komma trennen)'), 
				'textarea', 
				array(
					'db_column' => 'value',
					'db_alias' => 'f_o',
					'required' => false
				)
			)
		);
		
		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Optionen'), 
				'textarea', 
				array(
					'db_column' => 'options',
					'db_alias' => 'f_o',
					'required' => false
				)
			)
		);
		
		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Class-Attribut'), 
				'textarea', 
				array(
					'db_column' => 'class',
					'db_alias' => 'f_o',
					'required' => false
				)
			)
		);
		
		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Info-Text'), 
				'html', 
				array(
					'db_column' => 'infotext',
					'db_alias' => 'f_o',
					'required' => false
				)
			)
		);
		
		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Pflichtfeld'), 
				'checkbox', 
				array(
					'db_column' => 'check',
					'db_alias' => 'f_o',
					'required' => false
				)
			)
		);
		
		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Prüfung'), 
				'select', 
				array(
					'db_column' => 'validation',
					'db_alias' => 'f_o',
					'select_options' => \Util::addEmptyItem(self::getValidations(), '', ''),
					'required' => false
				)
			)
		);
		
		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Zuordnung'), 
				'select', 
				array(
					'db_column' => 'allocation',
					'db_alias' => 'f_o',
					'select_options' => \Util::addEmptyItem(self::getAllocations(), '', ''),
					'required' => false
				)
			)
		);
		
		$sPath = '/storage/public/form/';
		$oUpload = $oGui->createDialogUpload($oGui->t('Datei'), $oDialog, 'file', 'f_o', $sPath);
		$oUpload->bAddColumnData2Filename = false;
		$oDialog->setElement($oUpload);

		// Reference
		$oReferenceContainer = $oDialog->create('div');
		$oReferenceContainer->class = 'reference-container';
		
		$oSubheading = $oDialog->createSubheading($oGui->t('Datenbankverknüpfung'));
		$oReferenceContainer->setElement($oSubheading);
	
		$oReferenceContainer->setElement(
			$oDialog->createRow(
				$oGui->t('Tabelle'), 
				'select', 
				array(
					'db_column' => 'additional_db_table',
					'db_alias' => 'f_o',
					'select_options' => \Util::addEmptyItem(self::getTables(), '', ''),
					'required' => false
				)
			)
		);
		
		$oReferenceContainer->setElement(
			$oDialog->createRow(
				$oGui->t('Feld'), 
				'select', 
				array(
					'db_column' => 'additional_db_field',
					'db_alias' => 'f_o',
					'selection' => new \Form\Gui2\Selection\TableFields,
					'required' => false,
					'dependency' => [
						[
							'db_column' => 'additional_db_table',
							'db_alias' => 'f_o',
						],
					],
				)
			)
		);
		
		$oReferenceContainer->setElement(
			$oDialog->createRow(
				$oGui->t('Abfrage (z.B. "WHERE id > 2 ORDER BY id")'), 
				'textarea', 
				array(
					'db_column' => 'additional_db_query',
					'db_alias' => 'f_o',
					'required' => false
				)
			)
		);
		
		
		$oDialog->setElement($oReferenceContainer);

		// Methodenaufruf
		$oMethodCallContainer = $oDialog->create('div');
		$oMethodCallContainer->class = 'methodcall-container';
		
		$oSubheading = $oDialog->createSubheading($oGui->t('Methodenaufruf'));
		$oMethodCallContainer->setElement($oSubheading);
			
		$oMethodCallContainer->setElement(
			$oDialog->createRow(
				$oGui->t('Aufruf (als JSON-String)'), 
				'input', 
				array(
					'db_column' => 'additional_method_call',
					'db_alias' => 'f_o',
					'required' => false
				)
			)
		);		
		
		$oDialog->setElement($oMethodCallContainer);
		
		// Abhängigkeiten
		$oSubheading = $oDialog->createSubheading($oGui->t('Abhängigkeiten'));
		$oDialog->setElement($oSubheading);
		
		$oContainer = $oDialog->createJoinedObjectContainer('conditions');
		$oContainer->min = 0;
		$oContainer->max = 99;
		
		$oContainer->setElement(
			$oContainer->createRow(
				$oGui->t('Verknüpfung'), 
				'select', 
				array(
					'db_column' => 'operator',
					'db_alias' => 'f_oc',
					'select_options' => \Util::addEmptyItem(self::getOperators(), '', ''),
				)
			)
		);

		$oContainer->setElement(
			$oContainer->createRow(
				$oGui->t('('), 
				'checkbox', 
				array(
					'db_column' => 'open',
					'db_alias' => 'f_oc'
				)
			)
		);

		$oContainer->setElement(
			$oContainer->createRow(
				$oGui->t('Feld'), 
				'select', 
				array(
					'db_column' => 'field',
					'db_alias' => 'f_oc',
					'selection' => new \Form\Gui2\Selection\FormFields()
				)
			)
		);

		$oContainer->setElement(
			$oContainer->createRow(
				$oGui->t('Vergleichsoperator'), 
				'select', 
				array(
					'db_column' => 'mode',
					'db_alias' => 'f_oc',
					'select_options' => \Util::addEmptyItem(self::getModes(), '', ''),
				)
			)
		);

		$oContainer->setElement(
			$oContainer->createRow(
				$oGui->t('Vergleichswert'), 
				'select', 
				array(
					'db_column' => 'value',
					'db_alias' => 'f_oc',
					'selection' => new \Form\Gui2\Selection\Options(),
					'dependency' => [
						[
							'db_column' => 'field',
							'db_alias' => 'f_oc'
						]
					]
				)
			)
		);

		$oContainer->setElement(
			$oContainer->createRow(
				$oGui->t(')'), 
				'checkbox', 
				array(
					'db_column' => 'close',
					'db_alias' => 'f_oc'
				)
			)
		);

		$oDialog->setElement($oContainer);

		return $oDialog;
	}

	static public function getTables() {
		
		$aItems = \DB::listTables();
		foreach ((array)$aItems as $sTable) {
			$aTables[$sTable] = $sTable;
		}
		
		return $aTables;
	}
	
	static public function getModes() {

		$aModes = array(
			1	=> \L10N::t("Beinhaltet", 'Formulare'),
			2	=> \L10N::t("Gleich", 'Formulare'),
			3	=> \L10N::t("Nicht gleich", 'Formulare')
		);
		
		return $aModes;
	}
	
	static public function getOperators() {

		$aOperators = array(
			'AND' => \L10N::t("Und", 'Formulare'),
			'OR' => \L10N::t("Oder", 'Formulare')
		);
		
		return $aOperators;
	}
	
	static public function getValidations() {
	
		$aValidations = [
			'numbers' => \L10N::t('Nur Zahlen', 'Formulare'),
			'email' => \L10N::t('E-Mail-Adresse', 'Formulare'),
			'plz' => \L10N::t('PLZ', 'Formulare'),
			'date' => \L10N::t('Datum', 'Formulare'),
			'currency' => \L10N::t('Währung', 'Formulare')
		];

		return $aValidations;
	}
	
	static public function getAllocations() {
	
		$aAllocations = [
			'email' => \L10N::t('E-Mail'),
			'sex' => \L10N::t('Geschlecht'),
			'name' => \L10N::t('Name'),
			'firstname' => \L10N::t('Vorname'),
			'newsletter' => \L10N::t('Newsletter'),
		];
		
		return $aAllocations;
	}
	
	static public function getTypes() {

		$aFormTypes = array();
		$aFormTypes['text'] 		= \L10N::t("Einzeiliges Eingabefeld", 'Formulare');
		$aFormTypes['textarea'] 	= \L10N::t("Mehrzeiliger Eingabebereich", 'Formulare');
		$aFormTypes['select'] 		= \L10N::t("Auswahlliste", 'Formulare');
		$aFormTypes['checkbox'] 	= \L10N::t("Checkbox", 'Formulare');
		$aFormTypes['radio'] 		= \L10N::t("Radio-Button", 'Formulare');
		$aFormTypes['hidden'] 		= \L10N::t("Versteckt", 'Formulare');
		$aFormTypes['onlytext'] 	= \L10N::t("Nur Text (kein Eingabefeld)", 'Formulare');
		$aFormTypes['onlytitle'] 	= \L10N::t("Nur Titel (kein Eingabefeld)", 'Formulare');
		$aFormTypes['file'] 		= \L10N::t("Dateiupload", 'Formulare');
		$aFormTypes['reference'] 	= \L10N::t("Datenbankverknüpfung", 'Formulare');
		$aFormTypes['file_reference'] = \L10N::t("Dateiverlinkung", 'Formulare');
		$aFormTypes['method_call'] = \L10N::t("Methodenaufruf", 'Formulare');

		return $aFormTypes;
	}
	
    public static function getOrderBy(){
        return array('name' => 'ASC');
    }	

	static public function getWhere(\Ext_Gui2 $oGui) {

		$aWhere = [
			'form_id' => self::$iFormId
		];
		
		return $aWhere;
	}
	
	public function setForeignKey(&$oWDBasic) {
		
		/*
		 * Die Seiten-ID muss per Dialog-Feld überschreibbar sein. Wenn Sie also nicht leer ist, darf sie nicht 
		 * überschrieben werden
		 */
		$iPageId = $oWDBasic->page_id;
		
		parent::setForeignKey($oWDBasic);
		
		if(!empty($iPageId)) {
			$oWDBasic->page_id = $iPageId;
		}
		
	}
	
}
