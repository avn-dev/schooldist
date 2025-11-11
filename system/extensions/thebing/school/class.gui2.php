<?php

/**
 * @property Ext_Thebing_School $oWDBasic
 */
class Ext_Thebing_School_Gui2 extends Ext_Thebing_Gui2_Data
{
	
	// The GUI description
	protected static $_sDescription	= 'Thebing » Admin » Schools';

	/* ==================================================================================================== */

	/**
	 * Get the list of start days of accommodation
	 * 
	 * @return array
	 */
	public static function getAccommodationStartDays()
	{
		$aDays = Ext_TC_Util::getDays();

		$aData = array(
			'sa' => $aDays[6],
			'so' => $aDays[7],
			'mo' => $aDays[1]
		);

		return $aData;
	}

	public static function getAccommodationInclusiveNightOptions()
	{
		$range = range(2, 7);
		$options = array_combine($range, $range);
		return $options;
	}


	/**
	 * Get the list of accounting types
	 * 
	 * @return array
	 */
	public static function getAccountingTypes()
	{
		$aData = array(
			L10N::t('Brutto + Provision', self::$_sDescription),
			L10N::t('Netto', self::$_sDescription)
		);

		return $aData;
	}


	/**
	 * Get the list of exclusive PDF settings
	 * 
	 * @return array
	 */
	public static function getExclusivePdfSettings()
	{
		$aData = array(
			0 => L10N::t('Je Leistung/Paket Anzeige in %', self::$_sDescription),
			1 => L10N::t('Je Leistung/Paket Anzeige in Währung', self::$_sDescription),
			2 => L10N::t('je % Satz - eine Extrazeile unten drunter', self::$_sDescription),
			Ext_Thebing_Inquiry_Document::PDF_LINEITEM_NUMBERING => L10N::t('Positionsnummern', self::$_sDescription),
			Ext_Thebing_Inquiry_Document::PDF_VAT_LINES_EXTENDED => L10N::t('Zusammenfassende Zeile pro Steuersatz', self::$_sDescription)
		);

		return $aData;
	}


	/**
	 * Get the list of methods (days included)
	 * 
	 * @return array
	 */
	public static function getMethodsDaysIncluded()
	{
		$aData = array(
			L10N::t('Methode A', self::$_sDescription),
			L10N::t('Methode B', self::$_sDescription)
		);

		return $aData;
	}


	/**
	 * Get the list of number ranges
	 * 
	 * @return array
	 */
	public static function getNumberRanges()
	{
		$aNumberRanges = array(
			'customer'			=> L10N::t('Kunde', self::$_sDescription),
//			'invoice'			=> L10N::t('Rechnung', self::$_sDescription),
//			'proforma'			=> L10N::t('Proforma', self::$_sDescription),
//			'receipt_payment'	=> L10N::t('Beleg pro Zahlung', self::$_sDescription),
//			'receipt_invoice'	=> L10N::t('Beleg für Zahlungen je Rechnung', self::$_sDescription),
//			'receipt_total'		=> L10N::t('Beleg für Zahlungen aller Rechnungen', self::$_sDescription),
			'cheque'			=> L10N::t('Schecks', self::$_sDescription)
		);

		return $aNumberRanges;
	}


	/**
	 * Get the list of price calculation options
	 * 
	 * @return array
	 */
	public static function getPriceCalculations()
	{
		$aData = array(
			0=>L10N::t('normal', self::$_sDescription),
			1=>L10N::t('fortlaufend', self::$_sDescription)
		);

		return $aData;
	}


	/**
	 * Get the list of price presentations
	 * 
	 * @return array
	 */
	public static function getPricePresentations()
	{
		$aData = array(
			L10N::t('Paketpreise', self::$_sDescription),
			L10N::t('Preise je Leistung', self::$_sDescription)
		);

		return $aData;
	}


	/**
	 * Get the list of single price structures
	 * 
	 * @return array
	 */
	public static function getPriceStructureSingle()
	{
		$aData = array(
			L10N::t('Normale Preisstruktur', self::$_sDescription),
			L10N::t('Preis pro Lektion', self::$_sDescription)
		);

		return $aData;
	}


	/**
	 * Get the list of weeks price structures
	 * 
	 * @return array
	 */
	public static function getPriceStructureWeeks()
	{
		$aData = array(
			L10N::t('Normale Preisstruktur', self::$_sDescription),
			L10N::t('Preis pro Woche', self::$_sDescription)
		);

		return $aData;
	}


	/**
	 * Get the list of tax calculation options
	 * 
	 * @return array
	 */
	public static function getTaxCalculations()
	{
		$aData = array(
			0 => L10N::t('Nein', self::$_sDescription),
			1 => L10N::t('Inklusiv', self::$_sDescription),
			2 => L10N::t('Exklusiv', self::$_sDescription)
		);

		return $aData;
	}


	/**
	 * Get the list of teacher payment options
	 * 
	 * @return array
	 */
	public static function getTeacherPayments()
	{
		$aData = array(
			L10N::t('Lektion', self::$_sDescription),
			L10N::t('Stunde', self::$_sDescription)
		);

		return $aData;
	}


	/**
	 * Get the list of types of deposit
	 * 
	 * @return array
	 */
	public static function getTypesOfDeposit()
	{
		$aData = array(
			L10N::t('Geldbetrag', self::$_sDescription),
			L10N::t('Prozentsatz', self::$_sDescription)
		);

		return $aData;
	}

	public static function getIntervals() {
		
		$sMinutes = L10N::t('Minuten', self::$_sDescription);

		$aIntervals = array(
			15 => '15 '.$sMinutes,
			30 => '30 '.$sMinutes,
			//45 => '45 '.$sMinutes,
			//60 => '60 '.$sMinutes,
			//90 => '90 '.$sMinutes
		);

		return $aIntervals;

	}

	/**
	 * @TODO Das kann man schon seit Jahren nicht mehr einstellen und das hier wird auch nicht benutzt
	 *
	 * Liefert den Dialog für Unterrichtszeiten
	 */
	public function getClassTimesDialog() {

		$aTimes	= Ext_Thebing_Util::getTimeRows('format', 5, 0, 86400);

		$aIntervals = self::getIntervals();

		$oInnerGui = $this->_oGui->createChildGui(md5('thebing_admin_schools_classtimes'), 'Ext_Thebing_Gui2_Data');
		$oInnerGui->gui_title 			= $oInnerGui->t('Unterrichtszeiten');
		$oInnerGui->query_id_column		= 'id';
		$oInnerGui->query_id_alias		= '';
		$oInnerGui->foreign_key			= 'school_id';
		$oInnerGui->parent_primary_key	= 'id';
		$oInnerGui->load_admin_header	= false;
		$oInnerGui->multiple_selection  = false;
		$oInnerGui->calendar_format		= new Ext_Thebing_Gui2_Format_Date();

		$oInnerGui->setWDBasic('Ext_Thebing_School_ClassTimes');
		$oInnerGui->setTableData('limit', 30);
		$oInnerGui->setTableData('orderby', array('from'=>'ASC'));
		$oInnerGui->setTableData('where', array('active'=>1));

		/**
		 * Dialog
		 */
		$oInnerDialog = $oInnerGui->createDialog($oGui->t('Unterrichtszeit bearbeiten'), $oGui->t('Unterrichtszeit anlegen'));
		$oInnerDialog->sDialogIDTag = 'CLASS_TIMES_EDIT_';

		$oInnerDialog->setElement($oInnerDialog->createRow(L10N::t('Von', $this->_oGui->gui_description), 'select', array('db_alias'=>'', 'db_column'=>'from', 'select_options'=>$aTimes, 'required'=>true, 'format'=>new Ext_Thebing_Gui2_Format_Time())));
		$oInnerDialog->setElement($oInnerDialog->createRow(L10N::t('Bis', $this->_oGui->gui_description), 'select', array('db_alias'=>'', 'db_column'=>'until', 'select_options'=>$aTimes, 'required'=>true, 'format'=>new Ext_Thebing_Gui2_Format_Time())));
		$oInnerDialog->setElement($oInnerDialog->createRow(L10N::t('Interval', $this->_oGui->gui_description), 'select', array('db_alias'=>'', 'db_column'=>'interval', 'select_options'=>$aIntervals, 'required'=>true)));

		$oInnerDialog->width = 650;
		$oInnerDialog->height = 300;

		# START - Leiste 2 #
		$oBar = $oInnerGui->createBar();
		$oBar->width = '100%';

		/*$oLabelGroup = $oBar->createLabelGroup(L10N::t('Aktionen', $this->_oGui->gui_description));
		$oBar ->setElement($oLabelGroup);*/

		$oIcon = $oBar->createNewIcon(
								L10N::t('Neuer Eintrag', $this->_oGui->gui_description),
								$oInnerDialog,
								L10N::t('Neuer Eintrag', $this->_oGui->gui_description)
							);
		$oBar ->setElement($oIcon);

		$oIcon = $oBar->createEditIcon(
								L10N::t('Editieren', $this->_oGui->gui_description),
								$oInnerDialog,
								L10N::t('Editieren', $this->_oGui->gui_description)
							);
		$oBar ->setElement($oIcon);

		$oIcon = $oBar->createDeleteIcon(
								L10N::t('Löschen', $this->_oGui->gui_description),
								L10N::t('Löschen', $this->_oGui->gui_description)
							);
		$oBar ->setElement($oIcon);

		$oInnerGui->setBar($oBar);
		# ENDE - Leiste 2 #

		# START - Leiste 3 #
		$oBar = $oInnerGui->createBar();
		$oBar->width = '100%';
		$oBar->position = 'top';

		$oPagination = $oBar->createPagination();
		$oBar ->setElement($oPagination);

		$oLoading = $oBar->createLoadingIndicator();
		$oBar->setElement($oLoading);

		$oInnerGui->setBar($oBar);
		# ENDE - Leiste 3 #

		/**
		 * Spalten
		 */
		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column		= 'from';
		$oColumn->title			= L10N::t('Von', $oInnerGui->gui_description);
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('time');
		$oColumn->width_resize	= false;
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Time();
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column		= 'until';
		$oColumn->title			= L10N::t('Bis', $oInnerGui->gui_description);
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('time');
		$oColumn->width_resize	= false;
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Time();
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column		= 'interval';
		$oColumn->title			= L10N::t('Interval', $oInnerGui->gui_description);
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('short_name');
		$oColumn->width_resize	= true;
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Select($aIntervals);
		$oInnerGui->setColumn($oColumn);

		$oInnerGui->addDefaultColumns();

		$oDialog = $oInnerGui->createDialog($oInnerGui->t('Unterrichtszeiten'));
		$oDialog->sDialogIDTag = 'CLASS_TIMES_';

		$oDialog->setElement($oInnerGui);

		$oDialog->width = 950;
		$oDialog->height = 650;
		$oDialog->save_button = false;

		return $oDialog;

	}

	 /**
	  * See parent
	  */
	 protected function _getErrorMessage($sError, $sField, $sLabel='', $sAction=null, $sAdditional=null)
     {

		switch($sError) {
			case 'TOO_MANY_SCHOOLS':
				$sMessage = 'Ihre Lizenz lässt das Anlegen weiterer Schulen nicht zu.';
				$sMessage = $this->t($sMessage);
				break;
			case 'NOT_SUPPORTED_TIMEZONE':
				$sMessage = 'Die gewählte Zeitzone wird von dem Server leider nicht unterstützt.';
				$sMessage = $this->t($sMessage);
				break;
			case 'NOT_SUPPORTED_DATE_FORMAT':
				$sMessage = 'Bitte überprüfen Sie das Datumsformat.';
				$sMessage = $this->t($sMessage);
				break;
			case 'CURRENCY_NOT_CHANGABLE':
				$sMessage = 'Die Standardbuchhaltungswährung darf nicht mehr geändert werden, sobald eine Transaktion auf den Konten stattgefunden hat.';
				$sMessage = $this->t($sMessage);
				break;
			case 'PRODUCTLINE_FAILED':
				$sMessage = 'Produktlinie konnte nicht gespeichert werden.';
				$sMessage = $this->t($sMessage);
				break;
			default:
				$sMessage = parent::_getErrorMessage($sError, $sField, $sLabel);
				break;
		}

		return $sMessage;
	}

	public function addValiditySql(&$aSqlParts, &$aSql)
	{
		$aSqlParts['select'] .= " ,`kcg`.`name` AS `item_title`";

		$aSqlParts['from'] .= " INNER JOIN
			`tc_cancellation_conditions_groups` `kcg` ON
				`kv`.`item_id` = `kcg`.`id`
		";
	}

	public function getValidityOptions($aSelectedIds)
	{
		$oCancellationGroup = new Ext_Thebing_Cancellation_Group();
		return $oCancellationGroup->getList('dialog');
	}

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveData
	 * @param bool $sAdditional
	 * @return array
	 */
	protected function getEditDialogData($aSelectedIds, $aSaveData = array(), $sAdditional = false)
    {

		// Auf empty() prüfen, da ansonsten der Schul-Konstruktor zuschlägt
		if(!empty($aSelectedIds)) {

			$this->_getWDBasicObject($aSelectedIds);

			foreach($aSaveData as &$aSaveField) {
				if(
					$aSaveField['db_column'] === 'logo' /*||
					$aSaveField['db_column'] === 'app_image'*/
				) {
					// Upload-Pfad überschreiben, damit richtiger Link im Uploader angezeigt wird
					$aSaveField['upload_path'] = $this->oWDBasic->getSchoolFileDir(false).'/';
//					if($aSaveField['db_column'] === 'app_image') {
//						$aSaveField['upload_path'] .= 'app/';
//					}
				}
			}
		}

		$data = parent::getEditDialogData($aSelectedIds, $aSaveData, $sAdditional);

		// Wert für Schule setzen
		foreach ($data as &$column) {
			if ($column['db_column'] === 'draft_invoices') {
				if (\Ext_Thebing_School::draftInvoicesActive(\Ext_Thebing_School::getInstance(reset($aSelectedIds)))) {
					$column['value'] = 1;
				}
			}
		}
		return $data;
	}

    /**
     * @param array $_VARS
     * @return void
     */
	public function switchAjaxRequest($_VARS)
    {

	    // Die letzte Schule darf nie gelöscht werden, da sonst die Installation nicht mehr arbeiten kann.
		// TODO Mal wieder kein Review geschehen, denn dafür gibt es bereits Methoden!
	    if($_VARS['task'] === 'deleteRow') {
            if($this->checkSchoolCanDelete()) {
                parent::switchAjaxRequest($_VARS);
            }
            $aTransfer['action'] = 'showError';
            $aTransfer['error'] = [
                L10N::t('Fehler beim Löschen', $this->_oGui->gui_description),
                L10N::t('Die letzte Schule kann nicht gelöscht werden.', $this->_oGui->gui_description)
            ];
            echo json_encode($aTransfer);
            die();
        } else {
            parent::switchAjaxRequest($_VARS);
        }

    }

    /**
     * Es müssen die ausgewählten Schulids nicht berücksichtigt werden,
     * da man nur einen Eintrag jeweils löschen kann.
     *
     * @return bool
     */
    private function checkSchoolCanDelete() {

        /** @var Ext_Thebing_SchoolRepository $oSchoolRepository */
        $oSchoolRepository = Ext_Thebing_School::getRepository();
        return $oSchoolRepository->hasMoreThanOne();

    }

	static public function getOrderby()
    {
		
		return ['cdb2.ext_1' => 'ASC'];
	}

	static public function getWhere()
    {
		
		$oClient = Ext_Thebing_Client::getInstance();
		return ['cdb2.active' => 1, 'cdb2.idClient' => (int)$oClient->id];
	}

	static public function getDialog(Ext_Thebing_Gui2 $oGui)
    {
		
		$oData = $oGui->getDataObject();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Dialog

		$oDialog = $oGui->createDialog(
			$oGui->t('Schule "{ext_1}" bearbeiten'),
			$oGui->t('Neue Schule')
		);
		$oDialog->width	= 960;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Tab "School"

		$aDays = Ext_TC_Util::getDays();
		$aCountries	= Ext_Thebing_Data::getCountryList(true, true);
		$aTimeZones	= Ext_TC_Util::getTimeZones();
		$aTimeZones = Ext_Thebing_Util::addEmptyItem($aTimeZones);
		$aSMTPs	= Ext_TC_Communication_EmailAccount::getSelection();

		$aCharsets = Ext_TC_Export::getCharsetOptions();
		$aCharsets = Ext_Thebing_Util::addEmptyItem($aCharsets);
		$aAccommodationDays = Ext_Thebing_Util::addEmptyItem(self::getAccommodationInclusiveNightOptions());
		$aPaymentConditions = Ext_Thebing_Util::addEmptyItem(Ext_TS_Payment_Condition::getSelectOptions());

		$aExecutionTimeOptions = Ext_TC_Util::getHours();
		$aExecutionTimeOptions[-1] = L10N::t('Einstellung der Installation verwenden');

		ksort($aExecutionTimeOptions);

		$oTab = $oDialog->createTab($oGui->t('Schule'));
		$oTab->class = 'school_settings';
		$oTab->setElement($oDialog->createRow($oGui->t('Name'), 'input', array('db_column' => 'ext_1', 'db_alias' => 'cdb2', 'required' => true)));
		$oTab->setElement($oDialog->createRow($oGui->t('Abkürzung'), 'input', array('db_column' => 'short', 'db_alias' => 'cdb2', 'required' => true)));

		$oUpload = new Ext_Gui2_Dialog_Upload($oGui, $oGui->t('Logo'), $oDialog, 'logo', '');
		$oUpload->bAddColumnData2Filename = 0;
		$oUpload->oPostProcess = new \Ts\Gui2\School\LogoUpload();
		$oTab->setElement($oUpload);

		$oTab->setElement($oDialog->createRow($oGui->t('Systemfarbe'), 'color', array('db_column' => 'system_color', 'db_alias' => 'cdb2')));

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Adresse'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Adresse'), 'input', array('db_column' => 'address', 'db_alias' => 'cdb2')));
		$oTab->setElement($oDialog->createRow($oGui->t('Adresszusatz'), 'input', array('db_column' => 'address_addon', 'db_alias' => 'cdb2')));
		$oTab->setElement($oDialog->createRow($oGui->t('PLZ'), 'input', array('db_column' => 'zip', 'db_alias' => 'cdb2')));
		$oTab->setElement($oDialog->createRow($oGui->t('Stadt'), 'input', array('db_column' => 'city', 'db_alias' => 'cdb2')));
		$oTab->setElement($oDialog->createRow($oGui->t('Bundesland'), 'input', array('db_column' => 'state', 'db_alias' => 'cdb2')));
		$oTab->setElement($oDialog->createRow($oGui->t('Land'), 'select', array('db_column' => 'country_id', 'db_alias' => 'cdb2', 'select_options' => $aCountries, 'required' => true)));

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Allgemeine Einstellungen'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Zeitzone'), 'select', array('db_column' => 'timezone', 'db_alias' => 'cdb2', 'select_options' => $aTimeZones)));
		//$oTab->setElement($oDialog->createRow($oGui->t('Ausführungszeit Index-Aktualisierung'), 'select', array('db_column' => 'execution_time_index_refreshing', 'db_alias' => 'cdb2', 'select_options' => $aExecutionTimeOptions)));

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Kontaktdaten'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('URL'), 'input', array('db_column' => 'url', 'db_alias' => 'cdb2')));
		$oTab->setElement($oDialog->createRow($oGui->t('Telefon'), 'input', array('db_column' => 'phone_1', 'db_alias' => 'cdb2')));
		$oTab->setElement($oDialog->createRow($oGui->t('Telefon 2'), 'input', array('db_column' => 'phone_2', 'db_alias' => 'cdb2')));
		$oTab->setElement($oDialog->createRow($oGui->t('Fax'), 'input', array('db_column' => 'fax', 'db_alias' => 'cdb2')));
		$oTab->setElement($oDialog->createRow($oGui->t('E-Mail-Adresse'), 'input', array('db_column' => 'email', 'db_alias' => 'cdb2', 'required' => 1)));
		$oTab->setElement($oDialog->createRow($oGui->t('E-Mail-Konto (SMTP)'), 'select', array('db_column' => 'email_account_id', 'db_alias' => 'cdb2',  'select_options' => $aSMTPs, 'required' => 1)));
		$oTab->setElement($oDialog->createRow($oGui->t('SMS Absender'), 'input', array(
			'db_alias' => 'attribute', 
			'db_column' => 'sms_sender'
		)));

		$oDialog->setElement($oTab);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Tab "Settings"

		$aLanguages	= Ext_Thebing_Data::getSystemLanguages();
		$aNumberFormats	= Ext_Thebing_Util::getNumberFormats();
		$oDefaultLanguage = new Ext_Thebing_Gui2_Selection_School_DefaultLanguage();

		$oTab = $oDialog->createTab($oGui->t('Einstellungen'));

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Sprachen'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Sprachen'), 'select', array('db_column' => 'languages', 'multiple' => 5, 'select_options' => $aLanguages, 'jquery_multiple' => 1, 'searchable' => 1, 'required' => 1)));
		$oTab->setElement($oDialog->createRow($oGui->t('Standardsprache'), 'select', array('db_column' => 'language', 'db_alias' => 'cdb2', 'selection' => $oDefaultLanguage, 'required' => 1, 'dependency' => array(array('db_column' => 'languages')))));

		if(Ext_Thebing_Access::hasLicenceRight('thebing_students_visa_list')) {
			$oH3 = $oDialog->create('h4');
			$oH3->setElement($oGui->t('Visa / Pass Ablaufwarnung'));
			$oTab->setElement($oH3);

			$oTab->setElement($oDialog->createRow($oGui->t('Pass-Ablaufwarnung (Tage vor Ablaufdatum)'), 'input', array('db_column' => 'passport_due', 'db_alias' => 'cdb2')));
			$oTab->setElement($oDialog->createRow($oGui->t('Visa-Ablaufwarnung (Tage vor Ablaufdatum)'), 'input', array('db_column' => 'visum_due', 'db_alias' => 'cdb2')));
		}

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Formatierung'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Zahlenformatierung'), 'select', array('db_column' => 'number_format', 'db_alias' => 'cdb2',  'select_options' => $aNumberFormats)));


		$oNotification = Ext_TC_Util::getDateFormatDescription($oDialog, null, null, true);
		$oTab->setElement($oNotification);

		$oTab->setElement($oDialog->createRow($oGui->t('Datumsformat lang'), 'input', array('db_column' => 'date_format_long', 'db_alias' => 'cdb2', 'required' => true)));
		$oTab->setElement($oDialog->createRow($oGui->t('Datumsformat kurz'), 'input', array('db_column' => 'date_format_short', 'db_alias' => 'cdb2', 'required' => true)));
		$oTab->setElement($oDialog->createRow($oGui->t('Export Trennzeichen'), 'input', array('db_column' => 'export_delimiter', 'db_alias' => 'cdb2')));
		$oTab->setElement($oDialog->createRow($oGui->t('Export Kodierung'), 'select', array('db_column' => 'csv_charset', 'db_alias' => 'cdb2', 'select_options' => $aCharsets)));

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('E-Mail Standards'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Nettodokumente an Nicht-Agentur Adresse'), 'select', array(
			'db_column' => 'net_email_warning',
			'db_alias' => 'cdb2',
			'select_options' => array(
				'always' => $oGui->t('Warnung immer anzeigen'),
				'never' => $oGui->t('Warnung nie anzeigen'),
				'main_reception_field' => $oGui->t('Warnung nur auf Hauptempfängerfeld beziehen'),
				'recipient_is_sender' => $oGui->t('Warnung nicht anzeigen, wenn Empfänger der Versender ist')
			)
		)));

		$layouts = \Ext_TC_Communication_Template_Email_Layout::query()->pluck('name', 'id')
			->prepend('', 0);

		$oTab->setElement($oDialog->createRow($oGui->t('Standardlayout'), 'select', array(
			'db_column' => 'default_communication_layout_id',
			'db_alias' => 'cdb2',
			'select_options' => $layouts
		)));

		$oDialog->setElement($oTab);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Tab "Prices/Invoices"

		$aPriceStructureSingle	= Ext_Thebing_School_Gui2::getPriceStructureSingle();
		$aPriceStructureWeeks	= Ext_Thebing_School_Gui2::getPriceStructureWeeks();
		$aPriceCalculations		= Ext_Thebing_School_Gui2::getPriceCalculations();
		$aTaxCalculations		= Ext_Thebing_School_Gui2::getTaxCalculations();
		$aExclusivePdfSettings	= Ext_Thebing_School_Gui2::getExclusivePdfSettings();

		$oTab = $oDialog->createTab($oGui->t('Preise / Rechnungen'));
		$oTab->class = 'price_invoice_settings';

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Preise'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Preisstruktur - Wochenunterricht'), 'select', array('db_column' => 'price_structure_week', 'db_alias' => 'cdb2',  'select_options' => $aPriceStructureWeeks)));
		$oTab->setElement($oDialog->createRow($oGui->t('Preisstruktur - Einzelunterricht'), 'select', array('db_column' => 'price_structure_unit', 'db_alias' => 'cdb2',  'select_options' => $aPriceStructureSingle)));
		$oTab->setElement($oDialog->createRow($oGui->t('Preisberechnung'), 'select', array('db_column' => 'price_calculation', 'db_alias' => 'cdb2',  'select_options' => $aPriceCalculations)));
		$oTab->setElement($oDialog->createRow($oGui->t('Steuern'), 'select', array('db_column' => 'tax', 'db_alias' => 'cdb2',  'select_options' => $aTaxCalculations)));
		$oTab->setElement($oDialog->createRow($oGui->t('PDF - Exklusiv Einstellungen'), 'select', array('db_column' => 'aExclusivePDFs', 'multiple' => 3, 'select_options' => $aExclusivePdfSettings, 'jquery_multiple' => 1, 'searchable' => 1)));

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Rechnungen'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Standardzahlungsbedingung'), 'select', ['db_column' => 'payment_condition_id', 'select_options' => $aPaymentConditions, 'required' => true]));
		if(Ext_Thebing_Access::hasLicenceRight('thebing_marketing_agencies')) {
			$oTab->setElement($oDialog->createRow($oGui->t('Netto Rechnung - nur Netto Spalte anzeigen'), 'checkbox', array('db_column' => 'netto_column', 'db_alias' => 'cdb2')));
			$oTab->setElement($oDialog->createRow($oGui->t('Agenturgutschrift nur mit Kommissionsspalte'), 'checkbox', array('db_column' => 'commission_column', 'db_alias' => 'cdb2')));
		}
		$oTab->setElement($oDialog->createRow($oGui->t('Zusatzkosten sind Vorortkosten'), 'checkbox', array('db_column' => 'additional_costs_are_initial', 'db_alias' => 'cdb2')));
		$oTab->setElement($oDialog->createRow($oGui->t('Keine 0er Rechnungen erlauben'), 'checkbox', array('db_column' => 'invoice_amount_null_forbidden', 'db_alias' => 'cdb2')));
		if (!Ext_Thebing_Client::draftInvoicesForced()) {
			$oTab->setElement($oDialog->createRow($oGui->t('Rechnungsentwürfe aktivieren'), 'checkbox', array('db_column' => 'draft_invoices', 'db_alias' => 'cdb2')));
		}
		$oDialog->setElement($oTab);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Tab "School / Bankdetails"

		$oTab = $oDialog->createTab($oGui->t('Bankinformationen'));

		$oTab->setElement($oDialog->createRow($oGui->t('Kontoinhaber'), 'input', ['db_column' => 'account_holder', 'db_alias'=>'cdb2']));
		$oTab->setElement($oDialog->createRow($oGui->t('Kontonummer'), 'input', ['db_column' => 'account_number', 'db_alias'=>'cdb2']));
		$oTab->setElement($oDialog->createRow($oGui->t('Bankleitzahl'), 'input', ['db_column' => 'bank_code', 'db_alias' => 'cdb2']));
		$oTab->setElement($oDialog->createRow($oGui->t('Name der Bank'), 'input', ['db_column' => 'bank', 'db_alias' => 'cdb2']));
		$oTab->setElement($oDialog->createRow($oGui->t('Bankadresse'), 'input', ['db_column' => 'bank_address', 'db_alias' => 'cdb2']));
		$oTab->setElement($oDialog->createRow($oGui->t('IBAN'), 'input', ['db_column' => 'iban', 'db_alias' => 'cdb2']));
		$oTab->setElement($oDialog->createRow($oGui->t('BIC'), 'input', ['db_column' => 'bic', 'db_alias' => 'cdb2']));

		$oDialog->setElement($oTab);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Tab "Cources/Accommodations"

		$aAccommodationStartDays = Ext_Thebing_School_Gui2::getAccommodationStartDays();
		$aTimes = Ext_Thebing_Util::getTimeRows('format', 5, 0, 86400);
		$aIntervals = Ext_Thebing_School_Gui2::getIntervals();

		$oTab = $oDialog->createTab($oGui->t('Kurse / Unterkünfte'));

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Kurse'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Starttag der Kurswoche'), 'select', array(
			'db_column' => 'course_startday',
			'db_alias' => 'cdb2',
			'select_options' => $aDays
		)));

		if(Ext_Thebing_Access::hasLicenceRight('thebing_accommodation_icon')) {

			$oH3 = $oDialog->create('h4');
			$oH3->setElement($oGui->t('Unterkunft'));
			$oTab->setElement($oH3);

			$oTab->setElement($oDialog->createRow($oGui->t('Starttag der Unterkunftswoche'), 'select', array('db_column' => 'accommodation_start', 'db_alias' => 'cdb2',  'select_options' => $aAccommodationStartDays)));
			$oTab->setElement($oDialog->createRow($oGui->t('Anzahl Nächte der letzten Unterkunftswoche'), 'select', array('db_column' => 'inclusive_nights', 'db_alias' => 'cdb2', 'required'=>true, 'select_options' => $aAccommodationDays)));
			$oTab->setElement($oDialog->createRow($oGui->t('Preise - Anzahl der Extranächte, die einer Woche entsprechen'), 'select', array('db_column' => 'extra_nights_price', 'db_alias' => 'cdb2', 'required'=>true, 'select_options' => $aAccommodationDays)));
			//$oTab->setElement($oDialog->createRow($oGui->t('Kosten: Anzahl der Nächte pro Unterkunftswoche'), 'select', array('db_column' => 'inclusive_nights_cost', 'db_alias' => 'cdb2', 'select_options' => $aAccommodationDays)));
			$oTab->setElement($oDialog->createRow($oGui->t('Kosten - Anzahl der Extranächte, die einer Woche entsprechen'), 'select', array('db_column' => 'extra_nights_cost', 'db_alias' => 'cdb2', 'required'=>true, 'select_options' => $aAccommodationDays)));

			$oTab->setElement(
				$oDialog->createNotification(
					'<strong>'.$oGui->t('Verfügbare Platzhalter').':</strong>',
					'<ul>'
					.'<li>'.$oGui->t('Alter').': {age}</li>'
					.'<li>'.$oGui->t('Vorname').': {firstname}</li>'
					.'<li>'.$oGui->t('abgekürzter Vorname').': {firstname_capital}</li>'
					.'<li>'.$oGui->t('Nachname').': {surname}</li>'
					.'<li>'.$oGui->t('Sprache').': {language}</li>'
					.'<li>'.$oGui->t('Nationalität').': {nationality}</li>'
					.'<li>'.$oGui->t('Geschlecht').': {gender}</li>'
					.'<li>'.$oGui->t('Gruppe').': {group}</li>'
					.'<li>'.$oGui->t('Unterkunft').': {accommodation}</li>'
					.'<li>'.$oGui->t('Verpflegung').': {meal}</li>'
					.'<li>'.$oGui->t('Zimmer').': {room}</li>'
					.'<li>'.$oGui->t('Schülernummer').': {student_number}</li>'
					.'<li>'.$oGui->t('Kommentar').': {comment}</li>'
					.'<li>'.$oGui->t('Matching Kommentar').': {matching_note}</li>'
					.'<li>'.$oGui->t('Matching Kommentar 2').': {matching_additional_note}</li></ul>',
					'info'
				)
			);
			$oTab->setElement($oDialog->createRow($oGui->t('Vorlage für Zuweisungslabel'), 'input', array('db_column' => 'accommodation_allocation_label', 'db_alias' => 'cdb2')));
			$oTab->setElement($oDialog->createRow($oGui->t('Paralleles Zuweisen erlauben'), 'checkbox', array('db_column' => 'accommodation_parallel_assignment', 'db_alias' => 'cdb2')));
		}

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Schule'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Kritische Anwesenheit (%)'), 'input', array('db_column' => 'critical_attendance', 'db_alias' => 'cdb2', 'required' => true)));
		$oTab->setElement($oDialog->createRow($oGui->t('Erwachsenenalter'), 'input', array('db_column' => 'adult_age', 'db_alias' => 'cdb2', 'required' => true)));

		$oTab->setElement($oDialog->create('h4')->setElement($oGui->t('Unterrichtszeiten')));
		$oTab->setElement($oDialog->createRow($oGui->t('Startzeit'), 'select', ['db_column' => 'class_time_from', 'db_alias' => 'cdb2', 'required' => true, 'select_options' => $aTimes]));
		$oTab->setElement($oDialog->createRow($oGui->t('Endzeit'), 'select', ['db_column' => 'class_time_until', 'db_alias' => 'cdb2', 'required' => true, 'select_options' => $aTimes]));
		$oTab->setElement($oDialog->createRow($oGui->t('Intervall'), 'select', ['db_column' => 'class_time_interval', 'db_alias' => 'cdb2', 'required' => true, 'select_options' => $aIntervals]));

		$oTab->setElement($oDialog->create('h4')->setElement($oGui->t('Aktivitätszeiten')));
		$oTab->setElement($oDialog->createRow($oGui->t('Startzeit'), 'select', ['db_column' => 'activity_starttime', 'db_alias' => 'cdb2', 'required' => true, 'select_options' => $aTimes]));
		$oTab->setElement($oDialog->createRow($oGui->t('Endzeit'), 'select', ['db_column' => 'activity_endtime', 'db_alias' => 'cdb2', 'required' => true, 'select_options' => $aTimes]));
		$oTab->setElement($oDialog->createRow($oGui->t('Schüler können im Frontend Aktivitäten buchen die parallel zu ihren Kursen stattfinden'), 'checkbox', ['db_column' => 'activity_parallel_frontend', 'db_alias' => 'cdb2']));

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Lehrerlogin'));
		$oTab->setElement($oH3);

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$iSchoolId = $oSchool->id;

		$aTemplates = \Ext_TC_Communication_Template::getSelectOptions('mail', [
			'application' => 'tuition_teacher',
			'sub_objects' => $iSchoolId
		])->toArray();

		$aTemplates = \Ext_TC_Util::addEmptyItem($aTemplates,'--'.$oGui->t('Vorlage').'--');

		$oTab->setElement($oDialog->createRow($oGui->t('Willkommensnachricht'), 'html', array('db_column'=>'teacherlogin_welcome_text', 'db_alias' => 'cdb2', 'default_value'=>'')));
		$oTab->setElement($oDialog->createRow($oGui->t('Vorlage für "Passwort vergessen"-E-Mail'), 'select', array('db_column' => 'teacherlogin_template', 'db_alias' => 'cdb2', 'select_options' => $aTemplates)));
		$oTab->setElement($oDialog->createRow($oGui->t('Vorlage für den Versand von Berichten'), 'select', array('db_column' => 'teacherlogin_reportcard_template', 'db_alias' => 'cdb2', 'select_options' => $aTemplates)));

		$aFlexFields = $oSchool->getAttendanceFlexFields();
		$aFlexFieldsForSelect = [];
		foreach($aFlexFields as $oFlexField) {
			$aFlexFieldsForSelect[$oFlexField->aData['id']] = $oGui->t('Anwesenheit').' - '.$oFlexField->aData['title'];
		}
		$oTab->setElement($oDialog->createRow($oGui->t('Verfügbare Felder'), 'select', array(
			'db_alias' => 'cdb2',
			'db_column' => 'teacherlogin_flexfields',
			'multiple' => 5,
			'select_options' => $aFlexFieldsForSelect,
			'jquery_multiple' => 1,
			'searchable' => 1,
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Schülerinformationen'), 'select', array(
			'db_alias' => 'cdb2',
			'db_column' => 'teacherlogin_student_informations',
			'multiple' => 5,
			'select_options' => [
				'age' => $oGui->t('Alter'),
				'gender' => $oGui->t('Geschlecht'),
				'nationality' => $oGui->t('Nationalität'),
				'email' => $oGui->t('E-Mail'),
				'phone' => $oGui->t('Telefonnummer'),
				'emergency_email' => $oGui->t('E-Mail (Notfallkontakt)'),
				'emergency_phone' => $oGui->t('Telefonnummer (Notfallkontakt)'),
				'booker_email' => $oGui->t('E-Mail (Buchungskontakt)'),
				'booker_phone' => $oGui->t('Telefonnummer (Buchungskontakt)'),
			],
			'jquery_multiple' => 1,
			'searchable' => 1,
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Alle Flexfelder standardmäßig ausklappen'), 'checkbox', array('db_column' => 'teacherlogin_flex_expand', 'db_alias' => 'cdb2', 'default_value' => 0)));

		$oTab->setElement($oDialog->createRow($oGui->t('Kommunikation: E-Mail-Adresse des Lehrer als Antwortadresse verwenden'), 'checkbox', array('db_column' => 'teacherlogin_teacher_email_replyto', 'db_alias' => 'cdb2', 'default_value' => 0)));

		$oTab->setElement($oDialog->createRow($oGui->t('E-Mails an Rechnungskontakte erlauben'),
			'checkbox',
			[
				'db_column' => 'teacherlogin_communication_enable_booking_contact_email',
				'db_alias' => 'cdb2',
				'default_value' => 0
			]
		));

		$oTab->setElement($oDialog->createRow($oGui->t('Internen Klassenkommentar anzeigen'), 'checkbox', array('db_column' => 'teacherlogin_show_internal_class_comment', 'db_alias' => 'cdb2', 'default_value' => 0)));

		$oTab->setElement($oDialog->createRow($oGui->t('Standardansicht Anwesenheitserfassung'), 'select',
			[
				'db_column' => 'teacherlogin_attendance_view',
				'db_alias' => 'cdb2',
				'select_options' => ['simple' => $oGui->t('Einfach'), 'extended' => $oGui->t('Erweitert')],
			]
		));

		$oTab->setElement($oDialog->createRow($oGui->t('Kursbuchungskommentar in Anwesenheitslisten anzeigen'),
			'checkbox',
			[
				'db_column' => 'teacherlogin_show_course_comment_in_attendance',
				'db_alias' => 'cdb2',
				'default_value' => 0
			]
		));

		$periodModeOptions = [
			'W' => $oGui->t('Woche(n)'),
			'D' => $oGui->t('Tag(e)'),
		];

		$oTab->setElement($oDialog->createMultiRow($oGui->t('Ansicht: Report Cards'), [
			'db_alias' => 'cdb2',
			'items' => [
				[
					'input' => 'input',
					'db_column' => 'teacherlogin_reportcards_period_before_value',
					'format' => new \Ext_Gui2_View_Format_Null(),
					'style' => 'width: 50px',
					'no_savedata' => true
				],
				[
					'input' => 'select',
					'db_column' => 'teacherlogin_reportcards_period_before_mode',
					'select_options' => $periodModeOptions,
					'no_savedata' => true,
					'text_after' => $oGui->t('vor und')
				],
				[
					'input' => 'input',
					'db_column' => 'teacherlogin_reportcards_period_after_value',
					'format' => new \Ext_Gui2_View_Format_Null(),
					'style' => 'width: 50px',
					'no_savedata' => true
				],
				[
					'input' => 'select',
					'db_column' => 'teacherlogin_reportcards_period_after_mode',
					'select_options' => $periodModeOptions,
					'text_after' => $oGui->t('nach heute'),
					'no_savedata' => true
				],
				[
					'input' => 'hidden',
					'db_column' => 'teacherlogin_reportcards_period',
					'format' => new \Ext_Gui2_View_Format_Null(),
					'class' => 'teacherlogin_view_period'
				]
			]
		]));

		$oTab->setElement($oDialog->createMultiRow($oGui->t('Ansicht: Stundenplan'), [
			'db_alias' => 'cdb2',
			'items' => [
				[
					'input' => 'input',
					'db_column' => 'teacherlogin_timetable_period_before_value',
					'format' => new \Ext_Gui2_View_Format_Null(),
					'style' => 'width: 50px',
					'no_savedata' => true
				],
				[
					'input' => 'select',
					'db_column' => 'teacherlogin_timetable_period_before_mode',
					'select_options' => $periodModeOptions,
					'text_after' => $oGui->t('vor und'),
					'no_savedata' => true
				],
				[
					'input' => 'input',
					'db_column' => 'teacherlogin_timetable_period_after_value',
					'format' => new \Ext_Gui2_View_Format_Null(),
					'style' => 'width: 50px',
					'no_savedata' => true
				],
				[
					'input' => 'select',
					'db_column' => 'teacherlogin_timetable_period_after_mode',
					'select_options' => $periodModeOptions,
					'text_after' => $oGui->t('nach heute'),
					'no_savedata' => true
				],
				[
					'input' => 'hidden',
					'db_column' => 'teacherlogin_timetable_period',
					'format' => new \Ext_Gui2_View_Format_Null(),
					'class' => 'teacherlogin_view_period'
				]
			]
		]));

		$oTab->setElement($oDialog->createMultiRow($oGui->t('Ansicht: Anwesenheit'), [
			'db_alias' => 'cdb2',
			'items' => [
				[
					'input' => 'input',
					'db_column' => 'teacherlogin_attendance_period_before_value',
					'format' => new \Ext_Gui2_View_Format_Null(),
					'style' => 'width: 50px',
					'no_savedata' => true
				],
				[
					'input' => 'select',
					'db_column' => 'teacherlogin_attendance_period_before_mode',
					'select_options' => $periodModeOptions,
					'text_after' => $oGui->t('vor heute'),
					'no_savedata' => true
				],
				[
					'input' => 'hidden',
					'db_column' => 'teacherlogin_attendance_period',
					'format' => new \Ext_Gui2_View_Format_Null(),
					'class' => 'teacherlogin_view_period'
				]
			]
		]));

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Klassenplanung'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Automatische Zuweisung von Schülern nach Ferien'), 'checkbox', array('db_column' => 'tuition_automatic_holiday_allocation', 'db_alias' => 'cdb2')));
		$oTab->setElement($oDialog->createRow($oGui->t('Automatische Zuweisung von Schülern nach Kursverlängerungen'), 'checkbox', array('db_column' => 'tuition_automatic_course_extension_allocation', 'db_alias' => 'cdb2')));
		$oTab->setElement($oDialog->createRow($oGui->t('Leere Klassen anzeigen (Lehrerübersicht und eigene Übersichten)'), 'checkbox', array('db_column' => 'tuition_show_empty_classes', 'db_alias' => 'cdb2')));
		$oTab->setElement($oDialog->createRow($oGui->t(' Entschuldigte Abwesenheiten'), 'select', array('db_column' => 'tuition_excused_absence_calculation', 'db_alias' => 'cdb2', 'select_options' => [
			'include' => $oGui->t('Als Anwesend werten'),
			'exclude' => $oGui->t('Aus Anwesenheitskalkulation ausschließen')
		])));
		$oTab->setElement($oDialog->createRow($oGui->t('Prüfungen: Automatisch bestanden (Punkte)'), 'input', array('db_column' => 'examination_score_passed', 'db_alias' => 'cdb2')));

		$oTab->setElement($oDialog->createRow($oGui->t('Standard-Einstufungstest'), 'select', [
			'db_column' => 'default_placementtest_id',
			'select_options' => \Ext_TC_Util::addEmptyItem(\TsTuition\Entity\Placementtest::getSelectOptions())
			]));

		$oTab->setElement($oDialog->createRow($oGui->t('Klassenräume anderer Schulen verwenden'), 'select', [
			'db_column' => 'classroom_usage',
			'selection' => new Ext_Thebing_Gui2_Selection_School_Classrooms(),
			'multiple' => 5,
			'jquery_multiple' => true,
			'sortable' => true,
			'searchable' => true
		]));
		$oTab->setElement($oDialog->createRow($oGui->t('Bei Änderung der Zuweisung nicht auf bestehende Anwesenheitdaten prüfen'), 'checkbox', array('db_column' => 'tuition_allow_allocation_with_attendances_modification', 'db_alias' => 'cdb2')));

		$oDialog->setElement($oTab);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Tab "Snippets"

		$oTab = $oDialog->createTab($oGui->t('Frontend'));
		$oTab->aOptions = array(
		  'access' => '',
		  'section' => 'schools_frontend'
		);

		$oTab->setElement($oDialog->createRow($oGui->t('Datumsformat'), 'input', array(
			'db_alias' => 'cdb2',
			'db_column' => 'frontend_date_format'
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Im Voraus buchbare Leistungen'), 'select', [
			'db_alias' => 'cdb2',
			'db_column' => 'frontend_years_of_bookable_services',
			'select_options' => [
				2 => $oGui->t('2 Jahre'),
				3 => $oGui->t('3 Jahre'),
				4 => $oGui->t('4 Jahre'),
			]
		]));

		$oTab->setElement($oDialog->createRow($oGui->t('Mindestanzahl von Tagen vor Leistungsbeginn'), 'input', ['db_alias' => 'cdb2', 'db_column' => 'frontend_min_bookable_days_ahead']));
		$oTab->setElement($oDialog->createRow($oGui->t('Anzahl der Nachkommastellen'), 'input', array('db_column' => 'decimal_place', 'db_alias' => 'cdb2')));
		$oTab->setElement($oDialog->createRow($oGui->t('Link zum Einstufungstest'), 'input', array('db_column' => 'url_placementtest', 'db_alias' => 'cdb2')));
		$oTab->setElement($oDialog->createRow($oGui->t('Link zum Feedbackformular'), 'input', array('db_column' => 'url_feedback', 'db_alias' => 'cdb2')));
		$oTab->setElement($oDialog->createRow($oGui->t('E-Mail-Adresse für Zahlungsbestätigung'), 'input', array('db_column' => 'email_receipts', 'db_alias' => 'cdb2')));

		// Settings für Zahlungsanbieter
		// TODO Vieleicht so umstellen, dass die Felder nur angezeigt werden, wenn der Anbieter auch benutzt wird
		//foreach(TsFrontend\Handler\Payment\AbstractPayment::CLASSES as $sClass) {
		//	/** @var TsFrontend\Handler\Payment\AbstractPayment $sClass */
		//	$oTab->setElement($oDialog->create('h4')->setElement($sClass::getLabel()));
		//	foreach($sClass::getSettings() as $sSetting => $aSetting) {
		//		$oTab->setElement($oDialog->createRow($oGui->t($aSetting['label']), $aSetting['type'], ['db_column' => $sSetting]));
		//	}
		//}

		$oDialog->setElement($oTab);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Tab "Accounting"

		$aCurrencies = Ext_Thebing_Data_Currency::getCurrencyList();
		$aTeacherPayments = Ext_Thebing_School_Gui2::getTeacherPayments();

		$oTab = $oDialog->createTab($oGui->t('Buchhaltung'));
		$oTab->aOptions = array(
		  'access' => '',
		  'section' => 'schools_accounting'
		);

		// TODO Sollten sich nicht alle verfügbaren Währungen der Selects nach dem Multiselect richten?
		$oTab->setElement($oDialog->createRow($oGui->t('Währungen'), 'select', array('db_column' => 'aCurrencies', 'multiple' => 5, 'select_options' => $aCurrencies, 'jquery_multiple' => 1, 'searchable' => 1, 'required' => true)));
		$oTab->setElement($oDialog->createRow($oGui->t('Standardwährung'), 'select', array('db_column' => 'currency', 'db_alias' => 'cdb2',  'select_options' => $aCurrencies)));
		$oTab->setElement($oDialog->createRow($oGui->t('Lehrerwährung'), 'select', array('db_column' => 'currency_teacher', 'db_alias' => 'cdb2',  'select_options' => $aCurrencies)));
		$oTab->setElement($oDialog->createRow($oGui->t('Unterkunftswährung'), 'select', array('db_column' => 'currency_accommodation', 'db_alias' => 'cdb2',  'select_options' => $aCurrencies)));
		$oTab->setElement($oDialog->createRow($oGui->t('Transferwährung'), 'select', array('db_column' => 'currency_transfer', 'db_alias' => 'cdb2',  'select_options' => $aCurrencies)));
		$oTab->setElement($oDialog->createRow($oGui->t('Lehrerbezahlung pro'), 'select', array('db_column' => 'teacher_payment_type', 'db_alias' => 'cdb2',  'select_options' => $aTeacherPayments)));
		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Kostenstelle'),
				'input',
				[
					'db_column' => 'cost_center'
				]
			)
		);

		if(Ext_Thebing_Access::hasLicenceRight('thebing_accommodation_icon')) {
			$oH3 = $oDialog->create('h4');
			$oH3->setElement($oGui->t('Unterkunftsbezahlungen: Export'));
			$oTab->setElement($oH3);

			$aFileOptions = [
				'sepa' => $oGui->t('SEPA'),
				'csv' => $oGui->t('CSV'),
				'txt' => $oGui->t('TXT'),
			];

			$aFileOptions = Util::addEmptyItem($aFileOptions);

			$aExport = [
				'family' => $oGui->t('Familie'),
				'payment_entry' => $oGui->t('Bezahleintrag'),
			];

			$aExport = Util::addEmptyItem($aExport);

			$oTab->setElement(
				$oDialog->createRow(
					$oGui->t('Dateiformat'),
					'select',
					[
						'db_column' => 'sepa_file_format',
						'db_alias' => '',
						'select_options' => $aFileOptions,
					]
				)
			);
			$oTab->setElement(
				$oDialog->createRow(
					$oGui->t('Export Trennzeichen'),
					'input',
					[
						'db_column' => 'sepa_export_separator',
						'dependency_visibility' => [
							'db_column' => 'sepa_file_format',
							'on_values' => ['csv', 'txt'],
						],
					]
				)
			);
			$oTab->setElement(
				$oDialog->createRow(
					$oGui->t('Kodierung'),
					'select',
					[
						'db_column' => 'sepa_file_coding',
						'select_options' => $aCharsets,
						'dependency_visibility' => [
							'db_column' => 'sepa_file_format',
							'on_values' => ['csv', 'txt'],
						]
					]
				)
			);
			$oTab->setElement(
				$oDialog->createRow(
					$oGui->t('Export pro'),
					'select',
					[
						'db_column' => 'sepa_export_per',
						'select_options' => $aExport,
					]
				)
			);
			$oTab->setElement(
				$oDialog->createRow(
					$oGui->t('Spalten'),
					'select',
					[
						'db_column' => 'sepa_columns',
						'selection' => new Ext_Thebing_School_Gui2_Selection_SepaColumns(),
						'multiple' => 5,
						'jquery_multiple' => true,
						'style' => 'height: 105px;',
						'sortable' => true,
						'searchable' => true,
						'dependency_visibility' => [
							'db_column'	=> 'sepa_file_format',
							'on_values' => ['csv', 'txt'],
						],
						'dependency' => [
							[
								'db_column' => 'sepa_export_per',
							],
						],
					]
				)
			);
			$oTab->setElement(
				$oDialog->createRow(
					$oGui->t('Sepa Pain-Format'),
					'select',
					[
						'db_column' => 'sepa_pain_format',
						'select_options' => [
							'' => '',
							'pain.001.001.09' => 'pain.001.001.09',
							'pain.001.002.03' => 'pain.001.002.03',
							'pain.001.001.03' => 'pain.001.001.03'
						],
						'dependency_visibility' => [
							'db_column' => 'sepa_file_format',
							'on_values' => ['sepa'],
						],
					]
				)
			);
			$oTab->setElement(
				$oDialog->createRow(
					$oGui->t('Sepa Organisation Identification'),
					'input',
					[
						'db_column' => 'sepa_org_id',
						'dependency_visibility' => [
							'db_column' => 'sepa_file_format',
							'on_values' => ['sepa'],
						],
					]
				)
			);




			$oTab->setElement(
				$oDialog->createNotification(
					'<strong>'.$oGui->t('Verfügbare Platzhalter').':</strong>',
					'<ul>'
					.'<li>'.$oGui->t('Vorname').': {firstname}</li>'
					.'<li>'.$oGui->t('Nachname').': {lastname}</li>'
					.'<li>'.$oGui->t('Startdatum').': {start}</li>'
					.'<li>'.$oGui->t('Enddatum').': {end}</li>'
					.'<li>'.$oGui->t('Startwoche').': {start_week}</li>'
					.'<li>'.$oGui->t('Endwoche').': {end_week}</li>'
					.'<li>'.$oGui->t('Name des Anbieters').': {provider_name}</li>',
					'info'
				)
			);
			$oTab->setElement(
				$oDialog->createRow(
					$oGui->t('Verwendungszweck'),
					'textarea',
					[
						'db_column' => 'sepa_comment'
					]
				)
			);

		}

		$oDialog->setElement($oTab);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Tab "Storno"
		$oTab = $oDialog->createTab($oGui->t('Storno'));
		$oValidityGui = $oGui->getValidityGui('school', 'cancellation_group', 'Stornogruppe');
		$oTab->setElement($oValidityGui);
		$oDialog->setElement($oTab);

		return $oDialog;
	}

	protected function saveEditDialogData(array $selectedIds, $saveData, $save = true, $action = 'edit', $prepareOpenDialog = true)
	{
		global $_VARS;

		if (!$this->oWDBasic) {
			$this->_getWDBasicObject($selectedIds);
		}

		$ignoreError = (bool)$_VARS['ignore_errors'] ?? false;
		$ignoreErrorCodes = $_VARS['ignore_errors_codes'] ?? [];

		if (
			!$ignoreError &&
			!in_array('excused_absence_calculation', $ignoreErrorCodes) &&
			$this->oWDBasic->tuition_excused_absence_calculation !== null &&
			$saveData['tuition_excused_absence_calculation']['cdb2'] !== $this->oWDBasic->tuition_excused_absence_calculation
		) {
			$transfer = [];
			$transfer['action'] = 'saveDialogCallback';
			$transfer['data']['show_skip_errors_checkbox'] = 1;
			$transfer['data']['id'] = 'ID_'.implode('_', $selectedIds);
			$transfer['error'] = [[
				'message' => $this->t('Beim Ändern der Abwesenheitskalkulation werden alle bisherigen Abwesenheiten auf die neue Berechnung angepasst.'),
				'type' => 'hint',
				'code' => 'excused_absence_calculation'
			]];

			return $transfer;
		}

		return parent::saveEditDialogData($selectedIds, $saveData, $save, $action, $prepareOpenDialog);
	}

}
