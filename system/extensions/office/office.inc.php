<?php

global $strPdfPath, $aSectionNames, $oOffice, $scale, $aConfigData, $aActivities, $aTypeNames, $aDocumentStates, $objOfficeDao, $aTableKeys;

include_once(\Util::getDocumentRoot()."system/extensions/office/office.dao.inc.php");

bcscale(5);

if(!is_dir(\Util::getDocumentRoot()."storage/office/")) {
	@mkdir(\Util::getDocumentRoot()."storage/office/", $system_data['chmod_mode_dir']);
	@chmod(\Util::getDocumentRoot()."storage/office/", $system_data['chmod_mode_dir']);
}

if(!is_dir(\Util::getDocumentRoot()."storage/office/pdf/")) {
	@mkdir(\Util::getDocumentRoot()."storage/office/pdf/", $system_data['chmod_mode_dir']);
	@chmod(\Util::getDocumentRoot()."storage/office/pdf/", $system_data['chmod_mode_dir']);
}

$strPdfPath = \Util::getDocumentRoot()."storage/office/pdf/";

$officeClientId = \Core\Handler\SessionHandler::getInstance()->get('office_client_id');

// get selected client_id
if(isset($_VARS['client_id'])) {
	$officeClientId = (int)$_VARS['client_id'];
}

if(empty($officeClientId)) {
	$officeClientId = 1;
}

\Core\Handler\SessionHandler::getInstance()->set('office_client_id', $officeClientId);

// get all clients
$aClients = classExtensionDao_Office::getAllClients(true);

// Typen
$aSectionNames = array(
	""				=> "Alle Dokumente",
	"letter"		=> "Briefe",
	"fax"			=> "Faxe",
	"offer"			=> "Angebote",
	"confirmation"	=> "Auftragsbestätigungen",
	"account"		=> "Rechnungen",
	"credit"		=> "Gutschriften",
	"cancellation_invoice" => "Stornorechnungen",
	"reminder"		=> "Mahnungen",
	"contract"		=> "Daueraufträge"
);
$aTypeNames = array(
	"letter"		=> "Brief",
	"fax"			=> "Fax",
	"offer"			=> "Angebot",
	"confirmation"	=> "Auftragsbestätigung",
	"account"		=> "Rechnung",
	"credit"		=> "Gutschrift",
	"cancellation_invoice" => "Stornorechnung",
	"reminder"		=> "Mahnung",
	"contract"		=> "Dauerauftrag"
);

// Protocol activities
$oOffice = new classExtension_Office;
$aConfigData = $oOffice->getConfigData();

$aTmp = array();
foreach((array)$aConfigData['activities'] as $sKey => $sValue)
{
	if(substr($sKey, 0, 4) == 'add_')
	{
		$aTmp[$sKey] = $sValue;
	}
}
$aActivities = array_merge(
	array(
		'customer_data'		=> 'Kunde',
		'customer_contact'	=> 'Ansprechspartner',
		'-'					=> '----------',
	),
	$aSectionNames,
	array('--'				=> '----------',),
	$aTmp
);

$scale = array("Wochen" => "Wochen", "Monate" => "Monate");

function clean_text($sString) {

	// convert the passed arguments
	$sString = (string)$sString;
	
	if(
		ord($sString[0]) == 239 &&
		ord($sString[1]) == 187 &&
		ord($sString[2]) == 191
	) {
		$sString = substr($sString, 6);
	}

	//$sString = html_entity_decode($sString, ENT_QUOTES, 'UTF-8');

	// return the string
	return $sString;
}

function get_config_data() {
	$objOffice = new classExtension_Office;
	return $objOffice->getConfigData();
}

class classExtension_Office {

	public $aConfigData;

	public function __construct() {
		$this->_getConfigData();
	}

	/**
	 * @todo Entfernen und durch Aufrufe von Ext_Office_Config austauschen
	 * @deprecated Bitte Ext_Office_Config verwenden
	 */
	public function getConfigData($bReload=false) {
		
		// Konfiguration neu einlesen
		if($bReload === true) {
			$this->_getConfigData();
		}

		return $this->aConfigData;
	}

	/**
	 * @todo Entfernen und durch Aufrufe von Ext_Office_Config austauschen
	 * @deprecated Bitte Ext_Office_Config verwenden
	 */
	protected function _getConfigData() {
		global $_VARS, $bFirstStart;

		$officeClientId = \Core\Handler\SessionHandler::getInstance()->get('office_client_id');
		
		// get may set client id
		if(isset($_VARS['client_id']) && !empty($_VARS['client_id'])) {
			$officeClientId = (int)$_VARS['client_id'];
		}
		
		if(empty($officeClientId)) {
			$officeClientId = 1;
		}

		$aSQL = array(
			'client_id'	=> (int)$officeClientId
		);
		$sSQL = "
			SELECT
				*
			FROM
				`office_config`
			WHERE
				`client_id` = :client_id
		";

		$aConfigData = DB::getPreparedQueryData($sSQL, $aSQL);

		$aData = array();
		foreach((array)$aConfigData as $iKey => $aConfig)
		{
			if(
				$aConfig['key'] == 'vat' ||
				$aConfig['key'] == 'payment' ||
				$aConfig['key'] == 'activities' ||
				$aConfig['key'] == 'units'
			) {
				$aConfig['value'] = Util::decodeSerializeOrJson($aConfig['value']);
			} elseif(
				$aConfig['key'] == 'document_columns'
			) {
				$aConfig['value'] = json_decode($aConfig['value'], true);
			}
			$aData[$aConfig['key']] = $aConfig['value'];
		}

		$this->aConfigData = $aData;

		return $aData;
	}

	static function addFont($sName, $sStyle, $sPath)
	{
				
		// Switch the font style (normal, bold or italic)
		switch($sStyle)
		{
			case 'b': case 'i': case 'bi':
			{
				$sStyle = $sStyle;
				break;
			}
			case 'n': default:
			{
				$sStyle = '';
				break;
			}
		}
		// Name of Font (not the name of *.ttf file)
		$sName = $sName;
		$sFile = \Util::getCleanFileName($sName).".ttf";

		// Connect to Plan-I-Server
		$ch = curl_init();

		// Config array
		$aData = array(
			'session_id'	=> session_id(),	// or random hex chars
			'font'			=> '@'.$sPath,		// temporary name of $_FILES['your_file']
			'submit'		=> 1,				// flag to execute the font creation
			'name'			=> $sFile.$sStyle	// name of *.ttf file
		);
		$sFile = str_replace('.ttf', '', $sFile);

		$sSQL = "
			SELECT
				`file`, `style`
			FROM
				`office_fonts`
			WHERE
				`file` = :sFile
				AND
				`style` = :sStyle
			LIMIT
				1
		";
		$aSQL = array('sFile' => $sFile, 'sStyle' => $sStyle);
		$aResult = (array)DB::getPreparedQueryData($sSQL, $aSQL);

		$bExists = false;
		$sFontError = '';
		if(count($aResult) != 0)
		{
			$bExists = true;
		}

		if(!$bExists) {

			curl_setopt($ch, CURLOPT_URL, 'http://update.webdynamics.de/fonts.php');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $aData);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			// Return from update.webdynamics.de server
			$strReturn = curl_exec($ch);

			// Creation successfull?
			if(
				strpos($strReturn, "Font file compressed") !== false && 
				strpos($strReturn, "Font definition file generated") !== false
			) {
				// Your folder with font files
				$sDir = \Util::getDocumentRoot().'system/includes/fpdi/font/'.$sFile.$sStyle;

				// Creation of new files on your server
				file_put_contents($sDir.'.afm', str_replace(session_id().'_', '', file_get_contents('http://update.webdynamics.de/fonts.php?filename='.session_id().'_'.$sFile.$sStyle.'.afm', FILE_BINARY)));
				file_put_contents($sDir.'.z', str_replace(session_id().'_', '', file_get_contents('http://update.webdynamics.de/fonts.php?filename='.session_id().'_'.$sFile.$sStyle.'.z', FILE_BINARY)));
				file_put_contents($sDir.'.php', str_replace(session_id().'_', '', file_get_contents('http://update.webdynamics.de/fonts.php?filename='.session_id().'_'.$sFile.$sStyle.'.php', FILE_BINARY)));

				$sSQL = "
					INSERT INTO
						`office_fonts`
					SET
						`created` = NOW(),
						`file`	= :sFile,
						`style`	= :sStyle,
						`name`	= :sName
				";

				$aSQL = array(
						'sFile' 	=> $sFile,
						'sStyle'	=> $sStyle,
						'sName'		=> $sName
					);
				DB::executePreparedQuery($sSQL, $aSQL);
				return 1;
			} else {
				return $strReturn;
			}
			
		} else {
			return -1;
		}
	}

	public function cleanPhoneNumber($strPhone) {
		
		$strPhone = preg_replace("/^\+/", "00", $strPhone);
		$strPhone = preg_replace("/[^0-9]/", '', $strPhone);
		return $strPhone;

	}

	public function getPhoneLink($strPhone) {

		$strLink = false;

		if($this->aConfigData['cti_command']) {
			$strPhone = $this->cleanPhoneNumber($strPhone);
			$strLink = $this->aConfigData['cti_command']."://".$this->aConfigData['cti_prefix'].$strPhone;
		}

		return $strLink;

	}

	public function getEmailTemplate($sType, $sLanguage) {

		// Wenn keine Sprache übergeben wurde, dann "de"
		if(empty($sLanguage)) {
			$sLanguage = 'de';
		}

		$aReturn = array();

		if(isset($this->aConfigData[$sType.'_subject_'.$sLanguage])) {
			$aReturn['subject'] = $this->aConfigData[$sType.'_subject_'.$sLanguage];
			$aReturn['body'] = $this->aConfigData[$sType.'_body_'.$sLanguage];
		} else {
			$aReturn['subject'] = $this->aConfigData[$sType.'_subject'];
			$aReturn['body'] = $this->aConfigData[$sType.'_body'];
		}

		return $aReturn;
	}

	static public function getEmailPlaceholders(){
		$aEmailPlaceholders = array(
			'{DocumentNumber}',
			'{DocumentPrice}',
			'{DocumentDate}',
			'{DocumentOutstanding}',
			'{DocumentPurchaseOrderNumber}',

			'{ContactName}',
			'{ContactEmail}',
			'{ContactPhone}',

			'{CustomerContactSalutation}',
			'{CustomerContactName}',
			'{CustomerContactEmail}',
			'{CustomerContactPhone}',
			'{CustomerContactFirstname}',
			'{CustomerContactLastname}',

			'{CustomerName}',
			'{CustomerNumber}'
		);

		return $aEmailPlaceholders;
	}

}

$objOffice = new classExtension_Office;
$arrConfigData = $objOffice->getConfigData();
$objOfficeDao = new classExtensionDao_Office($arrConfigData);

// Form-Variablen
$aConfigData = get_config_data();;
$aVat = $aConfigData['vat'];
$aUnits = $aConfigData['units'];
$sPaymentAdvice = $aConfigData['payment_advice'] ?? '';

// Zahlungsbedingungen
$arrTerms = $objOfficeDao->getPaymentTerms();
$aPayment = array();
foreach((array)$arrTerms as $iTermId=>$sTerm) {
	$aPayment[$iTermId] = $sTerm;
}

// PDF Variablen
$aConfigData['contact_small'] = str_replace("\r", "", $aConfigData['contact_small'] ?? '');
$sAddressHeader = str_replace("\n", " � ", $aConfigData['contact_small'] ?? '');
$sAddressFooter = $aConfigData['contact_large'] ?? '';
$sAdditionalFooter = $aConfigData['contact_footer'] ?? '';

if(isset($_VARS['type']) && ($_VARS['type'] == "account" or $_VARS['type'] == "offer" or $_VARS['type'] == "reminder")) {
	$sHTMLareaHeight = 200;
} else {
	$sHTMLareaHeight = 400;
}

$aChangeType = array("letter"		=> array("letter" => "neuer Brief", "fax" => "neues Fax"),
					 "fax"			=> array("fax" => "neues Fax", "letter" => "neuer Brief"),
					 "offer"		=> array("offer" => "neues Angebot", "confirmation" => "neue Auftragsbestätigung", "account" => "neue Rechnung"),
					 "confirmation"	=> array("account" => "neue Rechnung"),
					 "account"		=> array("account" => "neue Rechnung", "offer" => "neues Angebot", "cancellation_invoice" => "neue Stornorechnung", "credit" => "neue Gutschrift", "contract" => "neuer Dauerauftrag"),
					 "reminder"		=> array("reminder" => "2. Mahnung"),
					 "contract" 	=> array("account" => "neue Rechnung"));

$intDatabaseId = $aConfigData['database'];

$aTableKeys = array(
		'position'		=> array('title' => 'Position',		'align' => 'R'),
		'quantity'		=> array('title' => 'Menge',		'align' => 'R'),
		'unit'			=> array('title' => 'Einheit',		'align' => 'L'),
		'number'		=> array('title' => 'Artikel-Nr.',	'align' => 'L'),
		'text'			=> array('title' => 'Beschreibung',	'align' => 'L'),
		'amount'		=> array('title' => 'Einzelpreis',	'align' => 'R'),
		'discount'		=> array('title' => 'Rabatt',		'align' => 'R'),
		'vat'			=> array('title' => 'Steuer',		'align' => 'R'),
		'totalamount'	=> array('title' => 'Gesamtpreis',	'align' => 'R')
	);

$aReminderTableKeys = array(
		'inv_date'		=> array('title' => 'Datum'),
		'inv_number'	=> array('title' => 'Belegnr.'),
		'inv_pay_date'	=> array('title' => 'Fälligkeit'),
		'inv_amount'	=> array('title' => 'Betrag'),
		'inv_fee'		=> array('title' => 'Gebühr'),
		'inv_zins'		=> array('title' => 'Zinsen'),
		'inv_total'		=> array('title' => 'Gesamt'),
	);

$aBlockPlaceholders = array(
		'{DocumentYear}'					=> ' - aktuelles Jahr<br/>',

		'{DocumentPagesCurrent}'			=> ' - aktuelle Seite<br />',
		'{DocumentPagesTotal}'				=> ' - Seitenanzahl<br /><br />',

		'{DocumentNumber}'					=> ' - Nummer des Dokuments<br />',
		'{DocumentDate|%x}'					=> ' - Datum (formatierbar, <a href="http://php.net/strftime">strftime()</a>)<br />',
		'{DocumentType}'					=> ' - Typ des Dokuments<br />',
		'{DocumentCurrency}'				=> ' - Währung des Dokumentes<br /><br />',
		'{DocumentSubject}'					=> ' - Betreff<br /><br />',
		'{DocumentClient}'					=> ' - Voller Mandantenname<br /><br />',
		'{DocumentClientShort}'				=> ' - Abgekürzter Mandantenname<br /><br />',

		'{DocumentOriginalSubject}'			=> ' - Original Betreff<br /><br />',
		'{DocumentPurchaseOrderNumber}' => ' - Bestellnummer<br /><br />',

		'{ContactName}'						=> ' - Ansprechpartner, Name<br />',
		'{ContactEmail}'					=> ' - Ansprechpartner, E-Mail-Adresse<br />',
		'{ContactPhone}'					=> ' - Ansprechpartner, Telefon Nr.<br />',
		'{ContactSignatureImage}'			=> ' - Signatur (nur im Schlusstext)<br /><br />',

		'{CustomerName}'					=> ' - Kunde, Name<br />',
		'{CustomerNumber}'					=> ' - Kunde, Kunden Nr.<br />',
		'{CustomerAddress}'					=> ' - Kunde, Adresse<br /><br />',
		'{CustomerVatID}'					=> ' - Kunde, USt. ID<br /><br />',
		'{CustomerEU}'						=> ' - Kunde, aus der EU<br />',
		'{CustomerNotEU}'						=> ' - Kunde, nicht aus der EU<br /><br />',
	
		'{CreditCardPaymentLink}'			=> ' - Link zur Kreditkartenzahlung<br /><br />',

		'{CustomerContact}'					=> ' - Kunde, Ansprechpartner<br />',
		'{CustomerContactFirstname}'		=> ' - Kunde, Ansprechpartner, Vorname<br />',
		'{CustomerContactLastname}'			=> ' - Kunde, Ansprechpartner, Nachname<br />',
		'{CustomerContactEmail}'			=> ' - Kunde, Ansprechpartner, E-Mail<br />',
		'{CustomerContactPhone}'			=> ' - Kunde, Ansprechpartner, Telefon<br />',
		'{CustomerContactSalutation}'		=> ' - Kunde, Ansprechpartner, Anrede<br />'
	);

$aDocumentStates = array(
		'draft'		=> 'angelegt',
		'released'	=> 'versendet',
		'accepted'	=> 'angenommen',
		'finished'	=> 'erledigt',
		'declined'	=> 'abgelehnt',
		'reminded'	=> 'erinnert',
		'paid'		=> 'bezahlt'
	);
