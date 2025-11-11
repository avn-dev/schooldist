<?php

class Ext_Gui2_Data {

	use \Gui2\Traits\GuiFilterDataTrait;

	/**
	 * @var Ext_Gui2
	 */
	protected $_oGui;

	/**
	 * @var MVC_Request
	 */
	protected $request;

	/**
	 * @var bool
	 */
	protected $_mSaveAsNew = false;

	/**
	 * @var bool
	 */
	protected $_bOpenNewDialog = false;

	/**
	 * @var bool
	 */
	public $bDebugmode = false;

	/**
	 * @var int
	 */
    public $iPaginationOffset = 0;

	/**
	 * @var int
	 */
	public $iPaginationEnd = 0;

	/**
	 * @var int
	 */
	public $iPaginationTotal = 0;

	/**
	 * @var int
	 */
	public $iPaginationShow = 0;

	/**
	 * @var array
	 */
	public $aIconData = array();

	/**
	 * @var array
	 */
	public $aDialogToken = array();

	/**
	 * Merkt sich, ob die getDialog für einen Key innerhalb eines Request schon ausgeführt wurde
	 *
	 * @var array
	 */
	protected $_aGetDialogResetWDBasicCache = array();
	
	/**
	 * @var WDBasic
	 */
	public $oWDBasic;
	
	/**
	 * IDs der Parent GUI, falls vorhanden
	 *
	 * @var array
	 */
	protected $_aParentGuiIds;

	/**
	 * @var array
	 */
	public $aAlertMessages = array();

	/**
	 * @var array
	 */
	protected $_aFilter = array();

	/**
	 * @var array
	 */
	protected $_aQueryParts	= array();

	/**
	 * @var array
	 */
	protected $_aSql = array();

    /**
     * @var \ElasticaAdapter\Facade\Elastica
     */
	protected $_oWDSearch = null;

	/**
	 * @var array
	 */
	protected $_aWDSearchDataColumns = array();

	/**
	 * @var array
	 */
	protected $_aWDSearchSearchColumns = array();

	/**
	 * @var array
	 */
    protected $_aVarsOriginal = array();

	/**
	 * @var bool
	 */
    protected $_bAddParentIdsForSaveCallback = true;

	/**
	 * Array mit Übersetzungen, soll nur einmal geholt werden
	 *
	 * @var array
	 */
	protected $_aTranslations = null;

	/**
	 * @var bool
	 */
	protected $_bCallPersister = true;

    /**
	 * @var DB
	 */
	protected $_oDb = null;

	protected $initialReadonly;

	public function __sleep(): array
	{
		$this->_oWDSearch = null;
		$this->_oDb = null;
		$reflection = new \ReflectionClass($this);
		$properties = $reflection->getProperties();
		$propertyNames = [];
		foreach ($properties as $property) {
			$propertyNames[] = $property->getName();
		}

		return $propertyNames;
	}

	/**
	 * @param MVC_Request $oRequest
	 */
	public function setRequest(MVC_Request $oRequest) {
		$this->request = $oRequest;
	}

	/**
	 * @param string $sField
	 * @param string $sValue
	 */
	public function __set($sField, $sValue) {

		if($sField == 'aIconData') {
			__pout($sValue);
		} else {
			$this->$sField = $sValue;
		}

	}
		
	/**
	 * gibt ein Array mit den IDs der Eltern-GUI, falls vorhanden
	 * @return array|null
	 */
	public function getParentGuiIds(): ?array {
		return $this->_aParentGuiIds;
	}

	/**
	 * @TODO Entfernen
	 *
	 * @param WDBasic $oWDBasic
	 * @param array $arTransfer
	 */
//	protected function deleteRowTaskHook(WDBasic $oWDBasic, &$arTransfer) {
//
//	}

	// @TODO Entfernen (man kann auch einfach deleteRow() ableiten)
	// Falls noch anhängige Sachen gelöscht werden müssen kann man dies hier machen (ableiten)
	protected function deleteRowHook($iRowId) {

	}

	/**
	 * @param string $sIso
	 * @return bool
	 */
	public function checkI18NAccess($sIso) {
		return true;
	}

	/**
	 * Check Access and set if needed readonly
	 *
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param array $aSelectedIds
	 * @return boolean
	 */
	public function checkDialogAccess(&$oDialog, $aSelectedIds){

		$oAccess = Access::getInstance();
		
		// Wenn recht angegeben ist und das recht nicht vorhanden ist
		// nur READONLY
		// Es wird hier nicht der dialog gesperrt da dies über das ICON geschehen soll!
		if(
			$oDialog->access != "" &&
			!$oAccess->hasRight($oDialog->access)
		) {
			$oDialog->bReadOnly = true;
			return false;
		}

		return true;
	}

	/**
	 * @param string $sColumn
	 * @param string $sAlias
	 * @param int $iID
	 * @param string $sFileNameOld
	 * @param array $aOptionValues
	 * @return string
	 */
	public function buildFileName($sColumn, $sAlias, $iID, $sFileNameOld, $aOptionValues = array()) {

		$sFileName = '';

		if($aOptionValues['add_column_data_filename'] == 1) {
			$sFileName .= $sColumn;
		}

		if(
			!empty($sAlias) &&
			$aOptionValues['add_column_data_filename'] == 1
		) {
			$sFileName .= '_'.$sAlias;
		}

		if(!empty($sFileName)) {
			$sFileName .= '_';
		}

		if($aOptionValues['add_id_filename'] == 1) {
			$sFileName .= $iID.'_';
		}

		$sFileName .= Util::getCleanFilename($sFileNameOld);

		return $sFileName;
	}

	/**
	 * Wrapper um die Column List zu ändern/erweitern
	 *
	 * Nur in Ausnahmefällen benutzen! Es gibt noch getVisibleColumnList()!
	 *
	 * @param array $aColumnList
	 */
	public function prepareColumnListByRef(&$aColumnList) {

		// Im Debug-Modus eine ID-Spalte hinzufügen
		if(
			System::d('debugmode') == 2 &&
			!$this->_oGui->checkEncode() &&
			!empty($aColumnList) &&
			reset($aColumnList)->db_column !== 'id'
		) {
			$oColumn = new Ext_Gui2_Head();
			$oColumn->db_column = 'id';
			$oColumn->title = 'ID';
			$oColumn->width = 70;
			$oColumn->sortable = false;
			//$oColumn->format = Factory::getObject('Ext_Gui2_View_Format_Int');
			array_unshift($aColumnList, $oColumn);
		}

	}

	/**
	 * Wrapper um Daten ab zu ändern/erweitern
	 */
	public function manipulateTableDataResultsByRef(&$aResult) {

	}

	/**
	 * @param string $sTitle
	 * @param array $aExport
	 * @param string $sType
	 */
	protected function export($sTitle, $aExport, $sType = 'csv') {

		if($sType == 'csv') {
			$this->exportCSV($sTitle, $aExport);
		} else if($sType == 'xls') {
			//TODO
		} else if($sType == 'pdf') {
			//TODO
		}

	}

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveData
	 * @param bool $sAdditional
	 * @return array
	 * @throws Exception
	 */
	protected function getEditDialogData($aSelectedIds, $aSaveData = array(), $sAdditional = false) {

		if(is_array($sAdditional)) {
			$aIconAction = $sAdditional['action'];
			$sAdditional = $sAdditional['additional'];
		} else {
			$aIconAction = 'edit';
			/** @todo Prüfen, ob dies auch überall funktionieren kann! */
		}
		
		$sIconKey = self::getIconKey($aIconAction, $sAdditional);

		$oDialog = $this->_getDialog($sIconKey);

		if($oDialog instanceof \Gui2\Dialog\DialogInterface) {
			$aData = $oDialog->getDataObject()->getEdit($aSelectedIds, $aSaveData, $sAdditional);
			return $aData;
		} else {
			__pout($oDialog);
			throw new Exception('No dialog object!');
		}

	}

	/**
	 * Neuer Ansatz, um EIN Feld zu bearbeiten, ohne das komplette Array von getEditDialogData(9 durchlaufen zu müssen
	 *
	 * @param $aRow
	 */
	public function modifiyEditDialogDataRow(&$aRow) {

	}

	/**
	 * @param mixed $mFormat
	 * @param $sValue
	 * @param string $sMode
	 * @param null $oColumn
	 * @param null|array $aResultData
	 * @return mixed
	 */
	public static function executeFormat($mFormat, $sValue, $sMode='format', &$oColumn = null, &$aResultData = null) {

		if(isset($mFormat)) {

			if($mFormat instanceof Ext_Gui2_View_Format_Interface) {
				$oObject = $mFormat;
			} else if(
				is_string($mFormat)
			) {
				$sTempView = 'Ext_Gui2_View_Format_'.$mFormat;
				$oObject = new $sTempView();
			}

			if($sMode == 'convert') {
				$sValue = $oObject->convert($sValue, $oColumn, $aResultData);
			} else {
				$sValue = $oObject->format($sValue, $oColumn, $aResultData);
			}

		}

		return $sValue;
	}

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveData
	 * @param bool $bSave
	 * @param string $sAction
	 * @param bool $bPrepareOpenDialog
	 * @return array
	 * @throws Exception
	 */
	public function saveEditDialogDataPublic(array $aSelectedIds, $aSaveData, $bSave=true, $sAction='edit', $bPrepareOpenDialog = true) {

		$aTransfer = $this->saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);

		return $aTransfer;
	}

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveData
	 * @param boolean $bSave
	 * @param array|string $mAction Now with additional: array('action' => action, 'additional' => null)
	 * @param bool $bPrepareOpenDialog
	 * @return array
	 * @throws Exception
	 */
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave=true, $mAction='edit', $bPrepareOpenDialog = true) {

		// Key konvertieren, da $mAdditonal kein eigener Paramter ist
		$aActionAndAdditional = self::convertActionStringToAdditionalArray($mAction);

		$oDialog = $this->_getDialog(
			self::getIconKey($aActionAndAdditional['action'], $aActionAndAdditional['additional'])
		);

		if($oDialog instanceof \Gui2\Dialog\DialogInterface) {
			$aData = $oDialog->getDataObject()->saveEdit($aSelectedIds, $aSaveData, $bSave, $mAction, $bPrepareOpenDialog);
			return $aData;
		} else {
			__pout($oDialog);
			throw new Exception('No dialog object!');
		}
		
	}

	/**
	 * Alten $sAction-Paramter von saveEditDialogData() konvertieren in neues Array-Format für Icon-Key:
	 * Bei String ist Additional dementsprechend leer, bei Array einfach zurückgeben
	 *
	 * @param $mAction
	 * @return array
	 */
	public static function convertActionStringToAdditionalArray($mAction) {

		if(is_array($mAction)) {
			return $mAction;
		}

		return array(
			'action' => $mAction,
			'additional' => ''
		);

	}

	/**
	 * Wrapper für _getErrorData
	 *
	 * @param array $aErrorData
	 * @param array $aAction
	 * @param string $sType
	 * @param bool $bShowTitle
	 * @return array
	 * @throws Exception
	 */
	public function getErrorData($aErrorData, $aAction, $sType, $bShowTitle = true) {

		$aData = $this->_getErrorData($aErrorData, $aAction, $sType, $bShowTitle);

		return $aData;
	}

	/**
	 * @param array $aErrorData
	 * @param mixed $mAction
	 * @param string $sType
	 * @param bool $bShowTitle
	 * @return array
	 * @throws Exception
	 */
	protected function _getErrorData($aErrorData, $mAction, $sType, $bShowTitle = true) {

		// Key konvertieren, da $mAdditonal kein eigener Paramter ist
		$aActionAndAdditional = self::convertActionStringToAdditionalArray($mAction);

		$sIconKey = self::getIconKey($aActionAndAdditional['action'], $aActionAndAdditional['additional']);

		$oDialog = $this->_getDialog($sIconKey);

		if($oDialog instanceof Ext_Gui2_Dialog) {
			$aData = $oDialog->getDataObject()->getErrorData($aErrorData, $sType, $bShowTitle);
			return $aData;
		} else {
			throw new Exception('No dialog object for key '.$sIconKey.'!');
		}
		
	}

	/**
	 * @todo : Default im Switch-Case muss $this->t() benutzen. Überprüfen welche Klassen Custom Fehlermeldungen haben und umstellen...
	 * @param $sError
	 * @param $sField
	 * @param string $sLabel
	 * @param null $sAction
	 * @param null $sAdditional
	 * @return string
	 * @throws Exception
	 */
	protected function _getErrorMessage($sError, $sField, $sLabel='', $sAction=null, $sAdditional=null) {

		if($sAction !== null) {

			$sIconKey = self::getIconKey($sAction, $sAdditional);

			$oDialog = $this->_getDialog($sIconKey);

			if($oDialog instanceof Ext_Gui2_Dialog) {
				$sMessage = $oDialog->getDataObject()->getErrorMessage($sError, $sField, $sLabel);
			} else {
				throw new Exception('No dialog object for key '.$sIconKey.'!');
			}

		} else {

			switch($sError) { 
				case 'EXISTING_JOINED_ITEMS':
					$sLabel = $this->_getJoinedItemsErrorLabel($sField); 
					$sMessage = 'Dieser Eintrag ist noch mit "%s" verknüpft.';
					break;
				default:
					$sMessage = static::convertErrorKeyToMessage($sError);
					break;
			}

			$sMessage = L10N::t($sMessage, Ext_Gui2::$sAllGuiListL10N);

			if(!empty($sLabel)){
				$sMessage = sprintf($sMessage, $sLabel);
			}

		}

		return $sMessage;
	}

	/**
	 * @param $sKey
	 * @return string
	 */
	public static function convertErrorKeyToMessage($sKey) {
		
		switch($sKey) { 
			case 'EMPTY':
				$sMessage = 'Feld "%s" darf nicht leer sein.';
				break;
			case 'NOT_UNIQUE':
				$sMessage = 'Der Wert in Feld "%s" ist bereits vorhanden.';
				break;
			case 'INVALID_DATE':
				$sMessage = 'Das Format in Feld "%s" ist nicht korrekt.';
				break;
			case 'NOT_NUMERIC':
				$sMessage = 'Der Wert in Feld "%s" muss numerisch sein.';
				break;
			case 'TO_LONG':
				$sMessage = 'Der Wert in Feld "%s" ist zu lang.';
				break;
			case 'TO_SMALL':
				$sMessage = 'Der Wert in Feld "%s" ist zu niedrig.';
				break;
			case 'TO_HIGH':
				$sMessage = 'Der Wert in Feld "%s" ist zu hoch.';
				break;
			case 'TOO_MANY':
				$sMessage = 'Im Feld "%s" wurden zu viele Einträge ausgewählt.';
				break;
			case 'INVALID_MAIL':
				$sMessage = 'Das Feld "%s" muss eine gültige E-Mail-Adresse enthalten.';
				break;
			case 'INVALID_REGEX':
				$sMessage = 'Das Format in Feld "%s" ist nicht korrekt.';
				break;
			case 'INVALID_ALNUM':
				$sMessage = 'Das Feld "%s" darf nur alphanumerische Zeichen enthalten.';
				break;
			case 'INVALID__ALNUM':
				$sMessage = 'Das Feld "%s" darf nur alphanumerische Zeichen und Leerzeichen enthalten.';
				break;
			case 'INVALID_TEXT':
				$sMessage = 'Das Format in Feld "%s" ist nicht korrekt.';
				break;
			case 'INVALID_NUMERIC':
				$sMessage = 'Der Wert in Feld "%s" muss numerisch sein.';
				break;
			case 'INVALID_INT':
				$sMessage = 'Der Wert in Feld "%s" muss eine ganze Zahl sein.';
				break;
			case 'INVALID_INT_POSITIVE':
				$sMessage = 'Der Wert in Feld "%s" muss eine positive, ganze Zahl sein.';
				break;
			case 'INVALID_INT_NOTNEGATIVE':
				$sMessage = 'Der Wert in Feld "%s" muss eine nicht negative, ganze Zahl sein.';
				break;
			case 'INVALID_FLOAT':
				$sMessage = 'Der Wert in Feld "%s" muss eine Zahl sein.';
				break;
			case 'INVALID_FLOAT_POSITIVE':
				$sMessage = 'Der Wert in Feld "%s" muss eine positive Zahl sein.';
				break;
			case 'INVALID_FLOAT_NOTNEGATIVE':
				$sMessage = 'Der Wert in Feld "%s" muss eine nicht negative Zahl sein.';
				break;
			case 'INVALID_DATE_TIME':
				$sMessage = 'Das Feld "%s" muss ein Datum mit Uhrzeit enthalten.';
				break;
			case 'INVALID_TIME':
				$sMessage = 'Das Feld "%s" muss eine Zeitangabe enthalten.';
				break;
			case 'INVALID_PHONE':
				$sMessage = 'Das Feld "%s" muss eine Telefonnummer enthalten.';
				break;
			case 'INVALID_PHONE_ITU':
				$sMessage = 'Das Feld "%s" muss eine Telefonnummer im Format "+49 221 123456789" enthalten.';
				break;
			case 'INVALID_UNIQUE':
				$sMessage = 'Der Wert in Feld "%s" muss eindeutig sein.';
				break;
			case 'INVALID':
				$sMessage = 'Das Format in Feld "%s" ist nicht korrekt.';
				break;
			case 'INVALID_CTYPT_DIGIT':
				$sMessage = 'Das Format in Feld "%s" ist nicht korrekt.';
				break;
			case 'INVALID_DATE_FUTURE':
				$sMessage = 'Das Datum in Feld "%s" muss in der Zukunft liegen.';
				break;
			case 'INVALID_DATE_PAST':
				$sMessage = 'Das Datum in Feld "%s" muss in der Vergangenheit liegen.';
				break;
			case 'INVALID_DATE_UNTIL_BEFORE_FROM':
				$sMessage = 'Das Enddatum darf nicht vor dem Startdatum liegen.';
				break;
			case 'INVALID_HEX_COLOR':
				$sMessage = 'Der Wert in Feld "%s" muss einen hexadezimalen Wert im Format "#123ABC" enthalten.';
				break;
			case 'INVALID_ZIP':
				$sMessage = 'Das Format in Feld "%s" entspricht nicht dem Format des gewählten Landes';
				break;
			case 'INVALID_IBAN':
				$sMessage = 'Das Format in Feld "%s" entspricht nicht dem Format einer IBAN.';
				break;
			case 'ENTITY_LOCKED':
				$sMessage = 'Die Entität wird gerade bereits von einer anderen Stelle aus bearbeitet. Bitte versuchen Sie es zu einem späteren Zeitpunkt erneut.';
				break;
			default:
				$sMessage = $sKey;
				break;
		}
		
		return $sMessage;
	}

	/**
	 * Liefert das Label wenn existierende joined Items noch mit der ID verknüpft sind im Fehlerdialog
	 *
	 * @param string $sLabel
	 * @return mixed
	 */
	protected function _getJoinedItemsErrorLabel($sLabel) {
		return $sLabel;
	}

	/**
	 * Wrapper für _getErrorMessage
	 *
	 * @param string $sError
	 * @param string $sField
	 * @param string $sLabel
	 * @param null $sIconAction
	 * @param null $sAdditional
	 * @return string
	 * @throws Exception
	 */
	public function getErrorMessage($sError, $sField, $sLabel='', $sIconAction=null, $sAdditional=null) {
		return $this->_getErrorMessage($sError, $sField, $sLabel, $sIconAction, $sAdditional);
	}

	/**
	 * Holt das Dialog-Object und setzt ggf. die WDBasic und das DataObject
	 *
	 * @param string $sIconKey
	 * @return Ext_Gui2_Dialog 
	 */
	public function _getDialog($sIconKey) {

		if($this->aIconData[$sIconKey]) {

			/** @var Ext_Gui2_Dialog $oDialog */
			$oDialog = $this->aIconData[$sIconKey]['dialog_data'];

			// Altes Verhalten von prepareOpenDialog: null aus aIconData.dialog_data, z.B. bei Dialogen von getDialogHTML()
			if (!$oDialog) {
				return null;
			}

			if ($oDialog instanceof \Gui2\Dialog\LazyDialogProxy) {
				$oDialog->create($this->_oGui);
			}

			$bIssetWDBasic = $oDialog->getDataObject()->issetWDBasic();

			// Wenn das Dataobjekt keine eigene WDBasic hat
			if(!$bIssetWDBasic) {
				$oDialog->getDataObject()->setWDBasic($this->_oGui->class_wdbasic, $this->_oGui->_aTableData['where']);
			}

			// Wenn die WDBasic aus der Gui2_Data genommen wird, dann auch prüfen, ob Instanz übernommen werden kann
			if(
				!is_null($this->oWDBasic) &&
				is_a($this->oWDBasic, $oDialog->getDataObject()->getWDBasic())
			) {
				$oDialog->getDataObject()->setWDBasicObject($this->oWDBasic);
			} else {
				// Beim ersten Aufruf WDBasic zurücksetzen
				if(!isset($this->_aGetDialogResetWDBasicCache[$sIconKey])) {
					$oDialog->getDataObject()->resetWDBasicObject();
				}
			}

			if ($oDialog instanceof \Gui2\Dialog\LazyDialogProxy) {
				$oDialog->prepare($this->_oGui);
			}

			// Speichern, dass während dieses Request diese Methode für diesen Key schon mal ausgeführt wurde
			$this->_aGetDialogResetWDBasicCache[$sIconKey] = 1;
			
			return $oDialog;

		}

		return null;
	}

	/**
	 * Erzeugt HTML und Tabs für den "edit" Dialog
	 *
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param $aSelectedIds
	 * @param bool $sAdditional
	 * @return array
	 * @throws Exception
	 */
	protected function getEditDialogHTML(&$oDialog, $aSelectedIds, $sAdditional = false){

		if($oDialog instanceof \Gui2\Dialog\DialogInterface) {
			$aData = $oDialog->getDataObject()->getHtml('edit', $aSelectedIds, $sAdditional);
			return $aData;
		} else {
			__pout($oDialog);
			throw new Exception('No dialog object!');
		}

	}

	/**
	 * Speichert einen Dialog
	 *
	 * @param string $sAction
	 * @param array $aSelectedIds
	 * @param array $aData
	 * @param bool $sAdditional
	 * @param bool $bSave
	 * @return array
	 * @throws Exception
	 */
	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional=false, $bSave=true){

		$aData = (array)$aData;
		$sIconKey = self::getIconKey($sAction, $sAdditional);
		
		$oDialog = $this->_getDialog($sIconKey);
		
		if($oDialog instanceof \Gui2\Dialog\DialogInterface) {
			$aTransfer = $oDialog->getDataObject()->save($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);		
			return $aTransfer;
		} else {
			throw new Exception('No dialog object for key '.$sIconKey.'!');
		}

	}

	/**
	 * Erzeugt ein Array mit den HTML und Tab Daten
	 *
	 * @param string $sIconAction
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param array $aSelectedIds
	 * @param bool $sAdditional
	 * @return array
	 */
	protected function getDialogHTML(&$sIconAction, &$oDialog, $aSelectedIds = array(), $sAdditional=false) {

		if(!($oDialog instanceof Ext_Gui2_Dialog)) {
			$sIconKey = self::getIconKey($sIconAction, $sAdditional);
			$oDialog = $this->_getDialog($sIconKey);
		}

		$aData = $oDialog->getDataObject()->getHtml($sIconAction, $aSelectedIds, $sAdditional);

		return $aData;
	}

	/**
	 * Wrapper um zu definieren welcher Info text aufklappbar sein soll
	 *
	 * @param array $aIds
	 * @param array $aRowData
	 * @param $oIcon
	 * @return string
	 */
	public function getRowIconInfoText(&$aIds, &$aRowData, &$oIcon){
		return '';
	}

	/**
	 * Methode um eine einzelne Spalte einer Zeile zu updaten
	 *
	 * @param array $aParameter
	 * @return bool|mixed
	 * @throws Exception
	 */
	public function updateOne($aParameter){

		// Wenn einfache Tabellen informationen da sind nutzte WDBasic !
		if(!empty($this->_oGui->_aTableData['table'])) {

			if(is_string($this->_oGui->_aTableData['table'])){

				$this->_getWDBasicObject($aParameter['row_id']);

				if($aParameter['type'] == 'timestamp'){
					$aParameter['value'] = date('Y-m-d h:m:s', $aParameter['value']);
				}
				$this->oWDBasic->getJoinedObject($aParameter['alias'])->{$aParameter['column']} = $aParameter['value'];

				// Changed und Editor sollen bei Sortierung nicht verändert werden
				$this->oWDBasic->disableUpdateOfCurrentTimestamp();

				$mValidate = $this->oWDBasic->validate();

				if($mValidate === true) {
					$this->oWDBasic->save();
					return true;
				} else {
					return $mValidate;
				}

			}

		} else {
			throw new Exception("Sorry, please overwrite the updateOne Method for complex updates");
		}

		return false;
	}

	/**
	 * WRAPPER Ajax Request verarbeiten
	 * @param $_VARS
	 */
	public function switchAjaxRequest($_VARS) {

        $this->_aVarsOriginal = $_VARS;

		$aTransfer = $this->_switchAjaxRequest($_VARS);

		echo Util::encodeJson($aTransfer);

	}

	public function resetRequest() {

		$this->_aVarsOriginal = [];
		$this->oWDBasic = null;
		$this->request = null;

	}
	
    /**
     * schauen ob die VARS manipuliert wurden
     * @param array $_VARS
     * @return boolean
     */
    protected function _isLoadTableManipulated($_VARS) {
        if(
           ($this->_aVarsOriginal['task'] ?? null) != ($_VARS['task'] ?? null) ||
           ($this->_aVarsOriginal['action'] ?? null) != ($_VARS['action'] ?? null)
        ){
            return true;
        }
        return false;
    }

    protected function _buildWherePart($aWhere) {
        return self::buildWherePart($aWhere);
	}

    public static function buildWherePart($aWhere, \ElasticaAdapter\Facade\Elastica $oWDSearch = null) {

        $aWhereQueryData = array();
		$aWhereQueryData['sql'] = '';
		$aWhereQueryData['data'] = array();

		if(is_string($aWhere)){
			$aWhere = array('SQL' => $aWhere);
		}

		// Wenn Elastica-Query, dann direkt setzen und den ganzen anderen Kram nicht ausführen
		if($aWhere instanceof \Elastica\Query\AbstractQuery) {
			if($oWDSearch === null) {
				throw new InvalidArgumentException('Elastica query given but no Elastica facade!');
			}
			$oWDSearch->addMustQuery($aWhere);
			return $aWhereQueryData;
		}

		$i = 0;
		foreach((array)$aWhere as $sColumn => $mValue){

			if($sColumn === 'SQL'){
				// Frei definierbarer Wherepart
				$aWhereQueryData['sql'] .= $mValue;
                if($oWDSearch){
                    throw new Exception('SQL Parts not allowed with WDSearch');
                }
			} else if($oWDSearch == null){
				$aFieldIdentifier = self::getFieldIdentifier($sColumn);

				if(!is_array($mValue)) {
					$sOperator = ' = ';
				} else {
					$sOperator	= ' ' . $mValue[0] . ' ';
					$mValue		= $mValue[1];
				}

				if(!empty($aFieldIdentifier['alias'])) {
					$aWhereQueryData['data']['where_alias_'.$i] = $aFieldIdentifier['alias'];
					$aWhereQueryData['sql'] .= ' AND #where_alias_'.$i.'.#where_'.$i.' ';
				} else {
					$aWhereQueryData['sql'] .= ' AND #where_'.$i.' ';
				}

				if(is_array($mValue)) {
					if(
						$sOperator == 'IN' &&
						count($mValue) > 0
					){

						$aWhereQueryData['sql'] .= $sOperator." ('".implode("', '", $mValue)."') ";
					}else{
						$aWhereQueryData['sql'] .= $sOperator.' (:where_value_'.$i.') ';
					}
				} else {
					$aWhereQueryData['sql'] .= $sOperator.' :where_value_'.$i.' ';
				}

				$aWhereQueryData['data']['where_'.$i] = $aFieldIdentifier['column'];
				$aWhereQueryData['data']['where_value_'.$i] = $mValue;

				$i++;
			} else if($oWDSearch) {

                $sOperator = '';
                //operatoren werden aktuell noch nicht unterstützt
                // nur "=" und "IN"
                if(is_array($mValue)){
                    $sOperator = reset($mValue);
                    $mValue = end($mValue);
                }

                if(is_array($mValue)){
                    $oQuery = new \Elastica\Query\BoolQuery();
                    foreach($mValue as $sValue){
                        $oShould = $oWDSearch->getFieldQuery($sColumn, (string)$sValue);
                        $oQuery->addShould($oShould);
                    }
				} elseif(
					$mValue instanceof \Elastica\Query\AbstractQuery ||
					$mValue instanceof \Elastica\Filter\AbstractFilter
				) {
					// Direkte Querys und Filter akzeptieren
					$oQuery = $mValue;
                } else {
                    $oQuery = $oWDSearch->getFieldQuery($sColumn, $mValue);
                }
				$oWDSearch->addMustQuery($oQuery, array($sColumn));
            }

		}

		return $aWhereQueryData;

    }

	public static function getFieldIdentifier($sIdentifier) {

		$aIdentifier = array();

		$aIdentifier['alias'] = '';
		$aIdentifier['column'] = '';
		
		$aColumn    = explode(".", $sIdentifier);

        $aIds       = array();
        foreach($aColumn as $iKey => $sColumn){
            if(strpos($sColumn, '[') !== false){
                $sNewColumn     = substr($sColumn, 0, strpos($sColumn, '['));
				if(!empty($sNewColumn)) {
					$iId            = (int)substr($sColumn, (strpos($sColumn, '[') + 1), (strpos($sColumn, ']') - 1));
					$aColumn[$iKey] = $sNewColumn;
					$aIds[$iKey]    = $iId;	
				}
            }
        }

		$sLastElement = end($aColumn);
		$iLastElement = key($aColumn);

		if(strpos($sLastElement, '-') !== false) {
			$aLastElement = explode('-', $sLastElement, 2);
			$aColumn[$iLastElement] = $aLastElement[0];
			$aIdentifier['multiple_index'] = $aLastElement[1];
		}

		if(count($aColumn) == 1) {
			$aIdentifier['column'] = $aColumn[0];
		}if(count($aColumn) == 2){
			// Table & Alias vorhanden
			$aIdentifier['alias'] = $aColumn[0];
			$aIdentifier['column'] = $aColumn[1];
		}elseif(count($aColumn) == 3){
			// Die Fehlermeldung kommt über ein joined Object dessen 'key' als spaltennamen angegeben
			// wurde! So ist es möglich innerhalb eines Dialoges Daten in ein anderes Obj. zu speichern
			$aIdentifier['alias']   = $aColumn[0];
			$aIdentifier['column']  = $aColumn[2];
            $aIdentifier['id']      = $aIds[1]; // id des kindes
		}elseif(count($aColumn) == 4) {
			// child -> child
			// hier nehmen wir den child key(0) und das Feld (3)
			// hier wurde das speichern wohl manuell gemacht (z.b SR schule )
			$aIdentifier['alias']   = $aColumn[2];
			$aIdentifier['column']  = $aColumn[3];
			$aIdentifier['id']      = $aIds[1]; // id des kindes
		}

        $aIdentifier['identifier']      = $sIdentifier; // id des kindes

		$sErrorId = '';

		if(
			!empty($aIds) ||
			isset($aIdentifier['multiple_index'])
		) {			
			$iColumnCount	= count($aColumn) - 1;
			
			for($i = $iColumnCount; $i >= 0; $i--) {
				if(isset($aIds[$i])) {
					$sErrorId .= '[' . $aIds[$i] . ']';
				}
				
				$sErrorId .= '[' . $aColumn[$i] . ']';
			}
			
			if(isset($aIdentifier['multiple_index'])) {
				$sErrorId .= '['.$aIdentifier['multiple_index'].']';
			}
		}
		
		$aIdentifier['error_id'] = $sErrorId;

		return $aIdentifier;

	}

	protected function _getFieldIdentifier($sIdentifier) {

		$aIdentifier = self::getFieldIdentifier($sIdentifier);

		return $aIdentifier;

	}

	protected function _setFieldIdentifier($sColumn, $sAlias) {

		$sKey = self::setFieldIdentifier($sColumn, $sAlias);

		return $sKey;

	}

	public static function setFieldIdentifier($sColumn, $sAlias) {
		$sKey = "";
		if(!empty($sAlias)) {
			$sKey .= $sAlias.".";
		}
		$sKey .= $sColumn;
		return $sKey;
	}

	protected function _generateJoinData(){

		$sAlias = 'auto_gui_jointable';

		if (
			is_array($this->_oGui->foreign_key) ||
			is_array($this->_oGui->parent_primary_key)
		) {
			throw new \RuntimeException('Please use scalar values for Foreign- and Parent-Primary-Key when using join data');
		}

		$aJoinData = array(
					$sAlias => array(
						'table' => $this->_oGui->foreign_jointable,
						'foreign_key_field' => $this->_oGui->foreign_key,
						'primary_key_field' => $this->_oGui->parent_primary_key,
						'autoload' => true,
						'join_operator' => 'INNER JOIN'
					)
				);

		return $aJoinData;

	}

	/**
	 * bereitet den Query für die Methode getTableQueryData vor
	 */
	protected function _buildQueryParts(&$sSql, &$aSql, &$aSqlParts, &$iLimit) {

		// Wenn WDBasic Ableitung gesetzt wurde
		if($this->_oGui->class_wdbasic != 'WDBasic') {

			if(
				is_null($this->oWDBasic) ||
				!($this->oWDBasic instanceof WDBasic)
			) {
				$this->_getWDBasicObject(array(0));
			}

			$oClass = &$this->oWDBasic;

			try {
				$this->_oDb = $oClass->getDbConnection();
			} catch(Exception $e) {
				$this->_oDb = DB::getDefaultConnection();
			}

			if($this->_oGui->foreign_jointable != ""){

				$mJoinTable = $oClass->getJoinTable($this->_oGui->foreign_jointable);

				// Wenn es noch keine JoinTable gibt, dann eine dynamisch anlegen
				if($mJoinTable === false) {
					$aJoinData = $this->_generateJoinData();
					$oClass->addJoinTable($aJoinData);
				}

			}

			$aQueryData = $oClass->getListQueryData($this->_oGui);

			$aSql = (array)$aQueryData['data'];

			$aSqlParts = $this->splitSqlString($aQueryData['sql']);

			$oClass->manipulateSqlParts($aSqlParts, $this->_oGui->sView);
			$this->manipulateSqlParts($aSqlParts, $this->_oGui->sView);

			if(!empty($aSqlParts['where'])) {
				$aSqlParts['where'] = " WHERE ".$aSqlParts['where'];
			}

			// Individuelle WHERE Parts ergänzen
			if(
				!empty($this->_oGui->_aTableData['where']) &&
				empty($aSqlParts['where'])
			){
				$aSqlParts['where'] = ' WHERE 1 ';
			}

			$aWhereQueryData = $this->_buildWherePart($this->_oGui->_aTableData['where'] ?? []);

			$aSqlParts['where'] .= $aWhereQueryData['sql'];
			$aSql += (array)$aWhereQueryData['data'];

			// Wenn Order By per setTableData gesetzt wurde, diese Einstellung bevorzugt nutzen
			if(!empty($this->_oGui->_aTableData['orderby'])){

				$aSqlParts['orderby'] = ' ORDER BY ';

				$aSqlParts['orderby'] .= $this->_buildOrderByPart($this->_oGui->_aTableData['orderby'], $aSql);

			// Sonst Order by aus dem Query nehmen
			} elseif(!empty($aSqlParts['orderby'])) {

				if(substr_count($aSqlParts['orderby'], ' DESC') >= 1){
					$this->_oGui->query_order = 'DESC';
				} else {
					$this->_oGui->query_order = 'ASC';
				}

				$sOrderBy = $aSqlParts['orderby'];
				$sOrderBy = str_replace(array('DESC','ASC','`'),'',$sOrderBy);
				$sOrderBy = trim($sOrderBy);

				$aTemp = explode(',' , $sOrderBy);
				$sOrderBy = $aTemp[0];

				if(strpos($sOrderBy,'.')!== false){
					$aTemp = explode('.',$sOrderBy);
					$sOrderBy = $aTemp[1];
				}

				$this->_oGui->query_orderField = $sOrderBy;

				$aSqlParts['orderby'] = " ORDER BY ".$aSqlParts['orderby'];

			}

			// Wenn Order By per setTableData gesetzt wurde, diese Einstellung bevorzugt nutzen
			if(!empty($this->_oGui->_aTableData['groupby'])){

				$aSqlParts['groupby'] = $this->_oGui->_aTableData['groupby'];

			}

		} else {

			$this->_oDb = DB::getDefaultConnection();
			
			// Selectionen schreiben
			foreach((array)$this->_oGui->_aTableData['select'] as $iKey => $sSelect){
				if(strpos($sSelect, '*') === FALSE){
					$aSqlParts['select'] .= '#select_'.$iKey;
					$aSql['select_'.$iKey] = $sSelect;
				} else {
					$aSqlParts['select'] .= '*,';
				}
			}
			$aSqlParts['select'] = rtrim($aSqlParts['select'], ',');

			// Tabelle schreiben
			$aSqlParts['from'] = $this->_oGui->_aTableData['table'];

			// Where
			if(!empty($this->_oGui->_aTableData['where'])){
				$aSqlParts['where'] = ' WHERE 1 ';
			}

			$aWhereQueryData = $this->_buildWherePart($this->_oGui->_aTableData['where']);

			$aSqlParts['where'] .= $aWhereQueryData['sql'];
			$aSql += (array)$aWhereQueryData['data'];

			// Group By
			if(!empty($this->_oGui->_aTableData['groupby'])){
				$aSqlParts['orderby'] = ' GROUP BY ';
			}
			$i = 0;

			foreach((array)$this->_oGui->_aTableData['groupby'] as $sColumn){
				$aSqlParts['groupby'] .= ' #group_'.$i.' ,';
				$aSql['group_'.$i] = $sColumn;
				$i++;
			}
			$aSqlParts['groupby'] = rtrim($aSqlParts['groupby'], ',');

			// Order By
			if(!empty($this->_oGui->_aTableData['orderby'])){
				$aSqlParts['orderby'] = ' ORDER BY ';

				$aSqlParts['orderby'] .= $this->_buildOrderByPart($this->_oGui->_aTableData['orderby'], $aSql);

			}

		}

		if(!empty($this->_oGui->_aTableData['limit'])) {
			$iLimit = $this->_oGui->_aTableData['limit'];
		}

		$this->setParentGuiWherePartByRef($aSqlParts, $aSql);

		// SQL String ohne WHERE Teil
		$sSql = "
					SELECT AUTO_SQL_CALC_FOUND_ROWS
						".$aSqlParts['select']."
					FROM
						".$aSqlParts['from']."
					";

	}

	/**
	 * Neuer Ansatz, diese Methode aus der »Entität« draußen zu lassen
	 *
	 * @param array $aSqlParts
	 * @param string $sView
	 */
	protected function manipulateSqlParts(array &$aSqlParts, string $sView) {

	}

	protected function _buildOrderByPart($aOrderBy, &$aSql) {

		$i = 0;
		$sOrderBy = '';
		foreach((array)$aOrderBy as $sColumn => $sSort){

			$aFieldIdentifier = $this->_getFieldIdentifier($sColumn);

			if($i==0) {
				$this->_oGui->query_orderField = $aFieldIdentifier['column'];
				$this->_oGui->query_orderAlias = $aFieldIdentifier['alias'];
				$this->_oGui->query_order = $sSort;
			}

			if(!empty($aFieldIdentifier['alias'])) {
				$aSql['order_alias_'.$i] = $aFieldIdentifier['alias'];
				$sOrderBy .= ' #order_alias_'.$i.'.#order_'.$i.' '.$sSort.' ,';
			} else {
				$sOrderBy .= ' #order_'.$i.' '.$sSort.' ,';
			}

			$aSql['order_'.$i] = $aFieldIdentifier['column'];
			$i++;
		}
		$sOrderBy = rtrim($sOrderBy, ',');

		return $sOrderBy;

	}

	public function setParentGuiWherePartByRef(&$aSqlParts, &$aSql) {
		global $_VARS;

		$sSelfAlias = '';

		if($this->_oGui->query_id_alias != ""){
			$sSelfAlias = $this->_oGui->query_id_alias;
		}

		// GUI Verknüpfung herstellen, falls angegeben
		if($this->_oGui->foreign_key != "") {

			// Definition kann ein String oder ein Array sein, immer in ein Array umwandeln
			$aParentPrimaryKeys = (array)$this->_oGui->parent_primary_key;
			$aForeignKeys = (array)$this->_oGui->foreign_key;

			// Die Anzahl der Primary-/Foreign-Keys muss immer gleich sein
			if (count($aParentPrimaryKeys) !== count($aForeignKeys)) {
				throw new \RuntimeException('Mismatch between number of Primary-Keys and Foreign-Keys.');
			}

			foreach ($aForeignKeys as $iIndex => $sForeignKey) {
				// Abwärtskompatibilität: Vorher war es immer $aSql['foreign_key']
				if ($iIndex === 0) {
					$aSql['foreign_key'] = (string)$sForeignKey;
				} else {
					$aSql['foreign_key_'.$iIndex] = (string)$sForeignKey;
				}
			}

			$oParentGui = $this->_getParentGui();

			// Wenn nicht genau eine Parent ID da, aber es eine Kind-GUI ist, dann keine Ergebnisse anzeigen!
			if(empty($_VARS['parent_gui_id'])) {

				$sParentWhere = ' 1 = 0 ';

			} else {

				$aParentWhere = array();

				$iPkIsEncoded = $this->_oGui->decode_parent_primary_key;

				$sForeignKeyAlias = $this->_oGui->foreign_key_alias;
				if(empty($sForeignKeyAlias)) {
					$sForeignKeyAlias = $sSelfAlias;
				}

				$aSql['foreign_key_alias'] = $sForeignKeyAlias;

				$aForeignKeysWhere = [];

				foreach((array)$_VARS['parent_gui_id'] as $iKey => $iParentId) {

					foreach ($aParentPrimaryKeys as $iIndex => $sPk) {

						if (!$iPkIsEncoded) {

							$sParentWDBasic = $oParentGui->class_wdbasic;

							if($sParentWDBasic == '') {
								break;
							}

							if($oParentGui->encode_data_id_field != false) {
								$sIdField = $oParentGui->encode_data_id_field;
							} else {
								$sIdField = $oParentGui->query_id_column;
							}

							$iSubParentId = $oParentGui->decodeId($iParentId, $sIdField);

							// Wenn es nicht das ID Feld ist UND nicht per JOIN Table gearbeitet wird
							// muss der WERT über das Object geholt werden
							if(
								$sPk != 'id' &&
								$sPk != $oParentGui->query_id_column &&
								$this->_oGui->foreign_jointable == ""
							) {
								$oParentWDBasic = call_user_func(array($sParentWDBasic, 'getInstance'), $iSubParentId);
								$mPK = (int)$oParentWDBasic->$sPk;
							} else {
								$mPK = $iSubParentId;
							}

						} else {
							$mPK = $oParentGui->decodeId($iParentId, $sPk);
						}

						// Falls es eine Kommaseperate Liste ist => mehrere IDs -> mehrere Eltern
						if(strpos($mPK, ',') !== false){
							$mPK = explode(',', $mPK);
						}

						$sForeignKeyPlaceholder = ($iIndex === 0) ? '#foreign_key' : '#foreign_key_'.$iIndex;

						if(!empty($sForeignKeyAlias)) {
							$sTemp = '#foreign_key_alias.'.$sForeignKeyPlaceholder;
						} else {
							$sTemp = $sForeignKeyPlaceholder;
						}

						// Wenn mehrere Parent-Ids übergeben wurden mit "IN (...)" einbauen
						if(is_array($mPK)){
							$sOperator = ' IN ( :parent_id_'.(int)$iKey.'_'.$iIndex.' ) ';
							$aSql['parent_id_'.(int)$iKey.'_'.$iIndex] = $mPK;
						} else {
							$sOperator = ' = :parent_id_'.(int)$iKey.'_'.$iIndex.' ';
							$aSql['parent_id_'.(int)$iKey.'_'.$iIndex] = (int)$mPK;
						}

						$aForeignKeysWhere[$iKey][$sPk] = $sTemp.' '.$sOperator;
					}

				}

				if(count($aForeignKeys) === 1) {

					$aSqlParts['select'] .= ', ';

					if(!empty($sForeignKeyAlias)) {
						$aSqlParts['select'] .= '#foreign_key_alias.';
					}

					$aSqlParts['select'] .= '#foreign_key `parent_gui_id`';
				}

				foreach ($aForeignKeysWhere as $aForeignSelects) {
					if (count($aForeignSelects) > 1) {
						// Mehrere Primary-/Foreign-Keys mit AND in einer Klammer verknüpfen
						$aParentWhere[] = '('.implode(' AND ', $aForeignSelects).')';
					} else {
						$aParentWhere[] = reset($aForeignSelects);
					}
				}

				// Alle WHERE Bedingungen der Eltern-Einträge mit OR verknüpfen
				$sParentWhere = implode(' OR ', $aParentWhere);
				$sParentWhere = '('.$sParentWhere.')';

			}

			if(!empty($aSqlParts['where'])){
				$aSqlParts['where'] .= ' AND '.$sParentWhere;
			} else {
				$aSqlParts['where'] .= ' WHERE '.$sParentWhere;
			}

		}

	}

	/*
	 *  Baut den Query zusammen und ruft die Daten aus der DB ab
	 */
	public function getTableQueryData($aFilter = array(), $aOrderBy = array(), $aSelectedIds = array(), $bSkipLimit=false) {

		if(
			!empty($aSelectedIds) &&
			$this->_oGui->checkEncode() &&
			$this->_oGui->encode_data_reload_for_icons == false
		){
			$aResult = array();
			$aResult['data'] = $this->_oGui->decodeId((array)$aSelectedIds);
			return $aResult;
		}

		$sSql = '';
		$aSql = array();
		$iLimit = 0;
		$aSqlParts = array();

		$this->setFilterValues($aFilter);
		
		$this->_buildQueryParts($sSql, $aSql, $aSqlParts, $iLimit);

		// wenn WDSearch an wird hier der Query gebaut um ALLES zu bekommen
		// daher darf das nicht passieren
		if(!$this->_oGui->checkWDSearch()){

			// Filter in den Where Part einbauen
			$this->setQueryFilterDataByRef($aFilter, $aSqlParts, $aSql);

		}

		// MUSS bei WDSearch ebenfalls passieren ( für das aktuallisieren von einzelnen Einträgen )
		// IDs mit filtern falls übergeben
		$this->setQueryIdDataByRef($aSelectedIds, $aSqlParts, $aSql);

		// Filter: Da der FROM-Teil schon weiter oben generiert wird, muss das hier nachträglich reingesetzt werden
		if(!empty($aSqlParts['from_additional'])) {
			$sSql .= $aSqlParts['from_additional'];
		}

		// WHERE an den SELECT anhängen
		$sSql .= $aSqlParts['where'];

		// Query um den GROUP BY Teil erweitern
		$this->setQueryGroupByDataByRef($sSql, $aSqlParts['groupby']);

		// wenn WDSearch an wird hier der Query gebaut um ALLES zu bekommen
		// daher darf das nicht passieren
		if(!$this->_oGui->checkWDSearch()){

			if(
				!empty($aSqlParts['having']) &&
				strpos($aSqlParts['having'], 'HAVING') === false
			) {
				$aSqlParts['having'] = " HAVING \n".$aSqlParts['having'];
			}

			// HAVING an den SELECT anhängen
			$sSql .= $aSqlParts['having'];
		}

		$aColumnList = $this->_oGui->getColumnList();

		// Query um den ORDER BY Teil erweitern und den Spalten die sortierung zuweisen
		$this->setQueryOrderByDataByRef($sSql, $aOrderBy, $aColumnList, $aSqlParts['orderby']);

		$iEnd = 0;

		if(!$bSkipLimit) {
			// LIMIT anhängen!
			$this->setQueryLimitDataByRef($iLimit, $iEnd, $sSql);
		}

		$this->_prepareTableQueryData($aSql, $sSql);
		
		$aResult = $this->_getTableQueryData($sSql, $aSql, $iEnd, $iLimit);		

		return $aResult;

	}

	/**
	 * Es gibt noch den Parameter $sSql, aber dieser wird nicht übergeben, da dies sonst Warnungen erzeugt
	 *
	 * @param $aSql
	 */
	protected function _prepareTableQueryData(&$aSql, &$sSql) {
		return;
	}

	/**
	 * Wrapper für getTranslations
	 * Sorgt dafür, dass die Translations in der Instanz gespeichert werden
	 * und nicht bei jeder Verwendung neu aufgerufen werden müssen
	 * 
	 * @param string $sL10NDescription
	 * @return array
	 */
	public function getTranslationsCache($sL10NDescription) {
		
		if($this->_aTranslations === null) {
			
			$this->_aTranslations = $this->getTranslations($sL10NDescription);
			
		}
		
		return $this->_aTranslations;
		
	}
	
	public function getTranslations($sL10NDescription) {
		global $session_data;

		// Übersetzungspfad der für die generellen ATG Übersetzungen
		$sL10NAllGUIs = Ext_Gui2::$sAllGuiListL10N;

		$aData = array();
		$aData["save_dialog_message"] 	= $this->t('Der Dialog muss noch gespeichert werden..', $sL10NAllGUIs);
		$aData["delete_question"] 		= $this->t('Wollen Sie den Eintrag wirklich löschen?', $sL10NAllGUIs);
		$aData["delete_all_question"] 	= $this->t('Wollen Sie alle markierte Einträge wirklich löschen?', $sL10NAllGUIs);
		$aData["translation_not_found"] = $this->t('Übersetzung nicht gefunden!', $sL10NAllGUIs);
		$aData["move"] 					= $this->t('verschieben', $sL10NAllGUIs);
		$aData["multiple selection"] 	= $this->t('zur Mehrfachauswahl wechseln', $sL10NAllGUIs);
		$aData["on selection"] 			= $this->t('zur Einfachauswahl wechseln', $sL10NAllGUIs);
		$aData["select_all"] 			= $this->t('alles auswählen', $sL10NAllGUIs);
		$aData["deselect_all"] 			= $this->t('alles abwählen', $sL10NAllGUIs);
		$aData["flexibility"] 			= $this->t('Flexibilität', $sL10NAllGUIs);
		$aData["close"]					= $this->t('Schließen', $sL10NAllGUIs);
		$aData["minimize"]				= $this->t('Minimieren',	$sL10NAllGUIs);
		$aData["maximize"]				= $this->t('Maximieren',	$sL10NAllGUIs);
		$aData["save"]					= $this->t('Speichern', $sL10NAllGUIs);
		$aData["save_as_new"]			= $this->t('Als neuen Eintrag speichern', $sL10NAllGUIs);
		$aData["show_more_options"]		= $this->t('weitere Optionen anzeigen', $sL10NAllGUIs);
		$aData["hide_more_options"]		= $this->t('weitere Optionen ausblenden', $sL10NAllGUIs);
		$aData["pagination_items"]		= $this->t('Einträge', $sL10NAllGUIs);
		$aData["pagination_to"]			= $this->t('bis', $sL10NAllGUIs);
		$aData["pagination_total"]		= $this->t('von', $sL10NAllGUIs);
		$aData["save_success"]			= $this->t('Erfolgreich gespeichert', $sL10NAllGUIs);
		$aData["unknown"]				= $this->t('Unbekannt', $sL10NAllGUIs);
		$aData["field_required"]		= $this->t('Bitte füllen Sie das Feld "%s" aus.', $sL10NAllGUIs);
		$aData["multiselect_required"]	= $this->t('Bitte wählen Sie mindestens einen Eintrag vom Feld "%s" aus.', $sL10NAllGUIs);
		$aData["select_required"]		= $this->t('Bitte wählen Sie einen Eintrag vom Feld "%s" aus.', $sL10NAllGUIs);
		$aData["field_format"]			= $this->t('Falsches Format', $sL10NAllGUIs);
		$aData["required_fields"]		= $this->t('* Pflichtfelder', $sL10NAllGUIs);
		$aData["success"]				= $this->t('Erfolgreich gespeichert', $sL10NAllGUIs);
		$aData["save_item"]				= $this->t('Datensatz speichern', $sL10NAllGUIs);
		$aData["duplicate_item"]		= $this->t('Datensatz duplizieren', $sL10NAllGUIs);
		$aData["success_duplicated"]	= $this->t('Erfolgreich dupliziert.', $sL10NAllGUIs);
		$aData["delete_item"]			= $this->t('Datensatz löschen', $sL10NAllGUIs);
		$aData["success_deleted"]		= $this->t('Erfolgreich gelöscht.', $sL10NAllGUIs);
		$aData["error_dialog_title"]	= $this->t('Fehler beim Speichern', $sL10NAllGUIs);
		$aData["hint_dialog_title"]		= $this->t('Achtung', $sL10NAllGUIs);
		$aData["info_dialog_title"]		= $this->t('Info', $sL10NAllGUIs);
		$aData["really"]				= $this->t('Sind sie sicher?', $sL10NAllGUIs);
		$aData["json_error_occured"]	= $this->t('Bei der Bearbeitung der Anfrage ist ein Fehler aufgetreten! Bitte versuchen Sie es erneut.', $sL10NAllGUIs);
		$aData["ignore_errors"]			= $this->t('Fehler ignorieren und speichern', $sL10NAllGUIs);
		$aData["general_error"]			= $this->t('Es ist ein Fehler aufgetreten', $sL10NAllGUIs);

		$aData["joined_object_delete_confirm"] 	= $this->t('Soll das Element wirklich gelöscht werden? Bitte beachten Sie, dass der Eintrag für das endgültige Löschen noch einmal gespeichert werden muss.', $sL10NAllGUIs);
		$aData["joined_object_delete_failed"] 	= $this->t('Dieses Element darf nicht gelöscht werden!',	$sL10NAllGUIs);
		$aData["joined_object_add_failed"] 		= $this->t('Sie haben die maximale Anzahl an Elementen erreicht.', $sL10NAllGUIs);
        $aData["joined_object_add_max_allowed"] = $this->t('Es sind maximal %i Element(e) erlaubt!', $sL10NAllGUIs);
		
		// Save bar dropdown options
		$aData["save_bar_options_title"]= $this->t('Nach dem Speichern',	$sL10NAllGUIs);
		$aData["save_bar_option_close"]	= $this->t('Fenster schließen', $sL10NAllGUIs);
		$aData["save_bar_option_open"]	= $this->t('Eintrag wieder öffnen', $sL10NAllGUIs);
		$aData["save_bar_option_new"]	= $this->t('Neuen Eintrag anlegen', $sL10NAllGUIs);

		// Kalender übersetzungen ( GLOBAL VERFÜGBAR )
//		$oLocale = new WDLocale(\System::getInterfaceLanguage(), 'date');
//		$aData["calendar_sonntag"]		= $oLocale->getValue('A', '7');
//		$aData["calendar_montag"]		= $oLocale->getValue('A', '1');
//		$aData["calendar_dienstag"]		= $oLocale->getValue('A', '2');
//		$aData["calendar_mittwoch"]		= $oLocale->getValue('A', '3');
//		$aData["calendar_donnerstag"]	= $oLocale->getValue('A', '4');
//		$aData["calendar_freitag"]		= $oLocale->getValue('A', '5');
//		$aData["calendar_samstag"]		= $oLocale->getValue('A', '6');
//		$aData["calendar_so"]			= $oLocale->getValue('a', '7');
//		$aData["calendar_mo"]			= $oLocale->getValue('a', '1');
//		$aData["calendar_di"]			= $oLocale->getValue('a', '2');
//		$aData["calendar_mi"]			= $oLocale->getValue('a', '3');
//		$aData["calendar_do"]			= $oLocale->getValue('a', '4');
//		$aData["calendar_fr"]			= $oLocale->getValue('a', '5');
//		$aData["calendar_sa"]			= $oLocale->getValue('a', '6');
//		$aData["calendar_januar"]		= $oLocale->getValue('B', '1');
//		$aData["calendar_februar"]		= $oLocale->getValue('B', '2');
//		$aData["calendar_maerz"]		= $oLocale->getValue('B', '3');
//		$aData["calendar_april"]		= $oLocale->getValue('B', '4');
//		$aData["calendar_mai"]			= $oLocale->getValue('B', '5');
//		$aData["calendar_juni"]			= $oLocale->getValue('B', '6');
//		$aData["calendar_juli"]			= $oLocale->getValue('B', '7');
//		$aData["calendar_august"]		= $oLocale->getValue('B', '8');
//		$aData["calendar_september"]	= $oLocale->getValue('B', '9');
//		$aData["calendar_oktober"]		= $oLocale->getValue('B', '10');
//		$aData["calendar_november"]		= $oLocale->getValue('B', '11');
//		$aData["calendar_dezember"]		= $oLocale->getValue('B', '12');
//		$aData["calendar_jan"]			= $oLocale->getValue('b', '1');
//		$aData["calendar_feb"]			= $oLocale->getValue('b', '2');
//		$aData["calendar_maerz"]		= $oLocale->getValue('b', '3');
//		$aData["calendar_apr"]			= $oLocale->getValue('b', '4');
//		$aData["calendar_mai"]			= $oLocale->getValue('b', '5');
//		$aData["calendar_jun"]			= $oLocale->getValue('b', '6');
//		$aData["calendar_jul"]			= $oLocale->getValue('b', '7');
//		$aData["calendar_aug"]			= $oLocale->getValue('b', '8');
//		$aData["calendar_sep"]			= $oLocale->getValue('b', '9');
//		$aData["calendar_okt"]			= $oLocale->getValue('b', '10');
//		$aData["calendar_nov"]			= $oLocale->getValue('b', '11');
//		$aData["calendar_dez"]			= $oLocale->getValue('b', '12');
//
//		$aData["calendar_heute"]		= $this->t('Heute',			$sL10NAllGUIs);
		$aData["age"] = $this->t('Alter', $sL10NAllGUIs);

		// jQuery multiselect translations
		$aData["jquery_addAll"]			= $this->t('Alle +',			$sL10NAllGUIs);
		$aData["jquery_removeAll"]		= $this->t('Alle -',			$sL10NAllGUIs);
		$aData["jquery_itemsCount"]		= $this->t('ausgewählt',		$sL10NAllGUIs);
		
		// Fastselect
		$aData["fastselect_placeholder"] = $this->t('Bitte wählen', $sL10NAllGUIs);
		$aData["fastselect_searchPlaceholder"] = $this->t('Suchen', $sL10NAllGUIs);
		$aData["fastselect_noResultsText"] = $this->t('Keine Ergebnisse', $sL10NAllGUIs);
		$aData["fastselect_userOptionPrefix"] = $this->t('Ergänze ', $sL10NAllGUIs);

		$aData["ok"]					= $this->t('Ok',				$sL10NAllGUIs);
		$aData["cancel"]				= $this->t('Abbruch',		$sL10NAllGUIs);

		$aData["inplaceeditor_savingtext"]	= $this->t('Wird gespeichert...',						$sL10NAllGUIs);
		$aData["inplaceeditor_clicktoedit"]	= $this->t('Klicken Sie hier, um den Wert zu ändern.',	$sL10NAllGUIs);

		// Select-Navigation
		$aData["select_navigation_back"]	= $this->t('Einen Eintrag zurück',	$sL10NAllGUIs);
		$aData["select_navigation_default"]	= $this->t('Standardwert',	$sL10NAllGUIs);
		$aData["select_navigation_next"]	= $this->t('Einen Eintrag vor',	$sL10NAllGUIs);
        
        // TC Uploader
		$aData["upload_error"]              = $this->t('Fehler beim Upload',	$sL10NAllGUIs);
		$aData["wrong_file_type"]           = $this->t('Dateiformat wird nicht unterstützt',	$sL10NAllGUIs);
		$aData["wrong_file_size"]           = $this->t('Dateigröße überschreitet das Maximum',	$sL10NAllGUIs);
		
		$aData["per_page"] = $this->t('Pro Seite', $sL10NAllGUIs);
        
		$aData["empty_select_error"] = $this->t('Der Dialog kann nicht gespeichert werden. Bitte legen Sie Einträge für das Feld %s an.', $sL10NAllGUIs);

		$aData['add_entry'] = $this->t('Eintrag hinzufügen', $sL10NAllGUIs);
		$aData['remove_entry'] = $this->t('Eintrag entfernen', $sL10NAllGUIs);

		$aData += $this->getFilterTranslations();
		
		return $aData;

	}

	###############################

	public function __construct(&$oGui){
		$this->_oGui = $oGui;
	}

	public static function getIconKey($aIconAction, $sAdditional) {
		$sKey = $aIconAction;
		if(!empty($sAdditional)) {
			$sKey .= '_'.$sAdditional;
		}
		return $sKey;
	}

	/**
	 *
	 * Methode für Dialoge
	 */
	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab=false, $sAdditional=false, $bSaveSuccess = true) {

		global $user_data, $_VARS;

		$aData = array();
		/**
		 * $aData['tabs'][0]['sTitle']		= 'Tab 1';
		 * $aData['tabs'][0]['sHtml']		= 'test'
		 * $aData['values']					= array()
		 * $aData['bSaveButton']			= true;
		 * $aData['title']					= ''; // wenn leer , dann wird die vorgabe genutzt welche beim icon object hinterlegt ist
		 */

		/** @var Ext_Gui2_Dialog $oDialog */
		$oDialog = null;
		$sIconKey = self::getIconKey($sIconAction, $sAdditional);

		if($sIconAction === 'edit_dialog_info_icon') {
			// Info Icon Dialog
			// @todo anders lösen (per eigenem Request - siehe requestNotices())
			$this->aIconData[$sIconKey]['dialog_data'] = $this->generateInfoIconDialog($_VARS, $sAdditional);
		} else {
			$oDialog = $this->_getDialog($sIconKey);
		}

//		if($this->aIconData[$sIconKey]){
//			$oDialog = $this->aIconData[$sIconKey]['dialog_data'];
//		}

		// Prüfen, ob der Dialog gesperrt ist
		$sDialogId = $this->_getDialogId($oDialog, $aSelectedIds);
		$mCheck = true;
		if(
			!$oDialog || // War vorher schon außerhalb von $oDialog, daher mit eingebaut
			$oDialog->bCheckLock
		) {
			$mCheck = $this->checkDialogLock($sDialogId, $aSelectedIds);
		}

		if($oDialog) {
			$oDialog->bDefaultReadOnly = $oDialog->bReadOnly;
		}

		// Wenn der Dialog gelocked ist
		if($mCheck !== true) {

			if(is_object($oDialog)) {
				$oDialog->bReadOnly = true;
			}

			$aDialogElements = (array)$oDialog->aElements;
			foreach($aDialogElements as &$mElement){
				if(is_object($mElement) && $mElement instanceof Ext_Gui2_Dialog_Tab){
					$aInnerElements = (array)$mElement->aElements;
					foreach($aInnerElements as &$mInnerElement){
						if(is_object($mInnerElement) && $mInnerElement instanceof Ext_Gui2){
							$mInnerElement->bReadOnly = 1;
						}
					}
				}elseif(is_object($mElement) && $mElement instanceof Ext_Gui2){
					$mElement->bReadOnly = 1;
				}
			}

			if($user_data['id'] == $mCheck['user_id']) {
				$sLockError = L10N::t('Der Dialog ist bereits bei Ihnen in einem anderen Fenster geöffnet!', Ext_Gui2::$sAllGuiListL10N);
			} else {
				$sLockError = L10N::t('Der Dialog ist bereits bei Benutzer "%s" geöffnet!', Ext_Gui2::$sAllGuiListL10N);
				$sLockingUserName = $mCheck['firstname'].' '.$mCheck['lastname'];
				$sLockError = sprintf($sLockError, $sLockingUserName);
			}
			
			$this->aAlertMessages[] = $sLockError; 

		} else {
			if($oDialog) {
				$oDialog->bReadOnly = $oDialog->bDefaultReadOnly;
			}
			self::lockDialogs($this->_oGui->hash, $this->_oGui->instance_hash, $user_data['id'], [$sDialogId]);
		}

		switch($sIconAction) {
			case 'edit':
			case 'new':

				// Wenn nach dem speichern Edit nicht gefunden wurde => new aufrufen und sagen Schliessen
				if(
					$sIconAction == 'new' &&
					!empty($aSelectedIds) &&
					is_object($oDialog)
				) {

					$sIconKey = self::getIconKey('edit', $sAdditional);

					if($this->aIconData[$sIconKey]) {
						// Was sollte das? Das wird oben schon gemacht
						$oDialog = $this->aIconData[$sIconKey]['dialog_data'];

						$sIconAction = 'edit';
					} else {
						$oDialog->aOptions['close_after_save'] = 1;
					}

				}

				if($oDialog instanceof \Gui2\Dialog\DialogInterface) {

					// HTML und Tabs holen
					$aData = $this->getEditDialogHTML($oDialog, $aSelectedIds, $sAdditional);
					// Alle Speicher Feld Daten holen
					$aSaveData = $oDialog->aSaveData;
					// Values der inputs holen
					$aEditDialogData = $this->getEditDialogData($aSelectedIds, $aSaveData, array('action' => $sIconAction, 'additional' => $sAdditional));
					
					$aData['values'] = $aEditDialogData; 

					// Array mit Regex für die Validation der einzelnen Felder
					$aData['validate'] = $oDialog->getValidationInfo();

					// Array mit Events für die Eingabefelder
					$aData['events'] = array_merge((array)$oDialog->getEvents(), (array)($aData['events'] ?? []));

				} else {

					dd($oDialog);

					__pout(array_keys($this->aIconData));
					__pout(Util::getBacktrace());
					__pout($sIconKey);
					throw new Exception("Sorry, i need a Dialog Object for this Action");
				}
				break;
			default:
				$aData = $this->getDialogHTML($sIconAction, $oDialog, $aSelectedIds, $sAdditional);

		}

		$sKey = self::getIconKey($sIconAction, $sAdditional);

		$this->aIconData[$sKey]['dialog_data'] = $oDialog;

		$oWDBasic = $this->oWDBasic;
		// Schauen, ob der Dialog eine eigene WDBasic hat und wenn ja diese benutzen
		if ($oDialog && $oDialog->getDataObject()->issetWDBasic()) {
			$oWDBasic = $oDialog->getDataObject()->getWDBasicObject($aSelectedIds);
		}

		if(
			is_null($oWDBasic) &&
			!empty($this->_oGui->_aTableData['table'])
		) {
			$iSelectedID = 0;
			if (is_array($aSelectedIds)) {
				$iSelectedID = reset($aSelectedIds);
			}

			$oWDBasic = $this->_getWDBasicObject(array($iSelectedID));
		}

		if(!isset($aData['title'])){
			$aData['title'] = '';
		}

		if(!empty($aData['title'])) {
			$aData['title'] = $this->replacePlaceholders($aData['title'], $oWDBasic);
		}

		$iId = $aSelectedIds;
		if(is_array($aSelectedIds)){
			$iId = implode('_', $aSelectedIds);
		}

		$aData['task']			= 'saveDialog';
		$aData['action']		= $sIconAction;
		$aData['read_only']		= (bool)$oDialog->bReadOnly;
		$aData['additional']	= $sAdditional;
		$aData['save_id']		= $iId;
		$aData['selectedRows']	= $aSelectedIds;
		if(
			(
				$oDialog &&
				$oDialog->save_button
			) ||
			$aData['bSaveButton'] == 1
		) {
			$aData['bSaveButton']	= 1;
		} else {
			$aData['bSaveButton']	= 0;
		}

		if(
			$oDialog &&
			!empty($oDialog->aButtons)
		) {
			$aData['buttons'] = $oDialog->aButtons;
		} else {
			$aData['buttons'] = false;
		}

		if($oDialog) {
			$oDialog->bReadOnly = $oDialog->bDefaultReadOnly;
		}

		return $aData;

	}

	/**
	 * Generiert den Dialog um Texte eines Info-Icons zu bearbeiten 
	 * 
	 * @param array $aVars
	 * @param string $sRowKey
	 * @return \Ext_Gui2_Dialog
	 */
	protected function generateInfoIconDialog($aVars, $sRowKey) {

		$oDialog = $this->_oGui->createDialog(\L10N::t('Infotext bearbeiten'));
		$oDialog->setDataObject(\Gui2\Service\InfoIcon\DialogData::class);
		$oDialog->sDialogIDTag = 'INFOICON_';
		
		$aRowKeyData = \Gui2\Service\InfoIcon\Hashing::decode($sRowKey);
		
		$oDialog->setOption('row_key', $sRowKey);
		$oDialog->setOption('row_key_data', $aRowKeyData);

		if (!empty($aVars['field_type'])) {
			$oDialog->setOption('field_type', $aVars['field_type']);
		}

		if (!empty($aVars['languages'])) {
			$oDialog->setOption('languages', (array)$aVars['languages']);
		}

		$oDialog->disableInfoIcons();
				
		return $oDialog;
	}
	
	/**
	 * Methode ist redundant in der Dialog_Data wg. Abwärtskompatibilität
	 * @param type $sTemplate
	 * @param type $oWDBasic
	 * @return type 
	 */
	public function replacePlaceholders($sTemplate, &$oWDBasic) {

		preg_match_all("/\{(.*?)\}/", $sTemplate, $aMatch);

		foreach((array)$aMatch[1] as $sMatch) {

			$aMatch = explode(".", $sMatch, 2);

			// Wenn Alias angegeben wurde
			if(count($aMatch) > 1) {
				$sAlias = $aMatch[0];
				$sColumn = $aMatch[1];
			} else {
				$sAlias = '';
				$sColumn = $sMatch;
			}

			$sTemplate = str_replace('{'.$sMatch.'}', $oWDBasic->getJoinedObject($sAlias)->$sColumn, $sTemplate);
		}

		return $sTemplate;

	}

	protected function setQueryLimitDataByRef(&$sLimit, &$iEnd, &$sSql){
		global $_VARS;

		// Offset setzten
		$iOffset = $_VARS['offset'] ?? 0;
		if($iOffset <= 0){
			$iOffset = 0;
		}

		// Wenn es ein Limit gibt
		if(!empty($sLimit)){
			$iEnd = $iOffset + (int)$sLimit;

			$sSql .= " LIMIT ".$iOffset.",".$sLimit;
		}

	}

	protected function fillDialogInfoIconMessageBag(Gui2\DTO\InfoIconMessageBag $oMessageBag) {
		// do nothing
	}
	
	protected function setQueryGroupByDataByRef(&$sSql, &$sGroupPart)
	{
		if(!empty($sGroupPart))
		{
			$sSql .= ' GROUP BY ' . $sGroupPart;
		}
	}

	protected function setQueryOrderByDataByRef(&$sSql, &$aOrderBy, &$aColumnList, &$sOrderPart){

		// Wenn die Tabelle sortierbar sein soll muss es daher nach dieser Sortierspalte sortiert werden
		if($this->_oGui->row_sortable == 1) {

			$sSql .= ' ORDER BY ';
			if($this->_oGui->row_sortable_alias != ''){
				$sSql .= '`'.$this->_oGui->row_sortable_alias.'`.';
			}
			$sSql .= '`'.$this->_oGui->row_sortable_column.'`';

			$sSql .= str_replace('ORDER BY', ',', $sOrderPart);

		// Wenn manuel sortiert wurde...
		} else if(!empty($aOrderBy)){

			$sSql .= ' ORDER BY ';

			foreach($aColumnList as &$oColumn){
				if(
					$oColumn->db_alias == $aOrderBy['db_alias'] &&
					(
						$oColumn->db_column == $aOrderBy['db_column'] ||
						(
							$oColumn->sortable_column !== null &&
							$oColumn->sortable_column == $aOrderBy['db_column']
						)
					)
				) {
					if($aOrderBy['order'] == 'DESC') {
						$oColumn->order = 'ASC';
						$oColumn->removeCssClass('sortasc');
						$oColumn->addCssClass('sortdesc');
					} else {
						$aOrderBy['order'] = 'ASC';
						$oColumn->order = 'DESC';
						$oColumn->removeCssClass('sortdesc');
						$oColumn->addCssClass('sortasc');
					}

					// Wenn normale Sortierung
					if(is_null($oColumn->order_settings)) {
						$sKey = $this->_setFieldIdentifier($aOrderBy['db_column'], $aOrderBy['db_alias']);
						$oColumn->order_settings = array($sKey=>'ASC');
					}

					$this->setQueryOrderByDataManualSort($sSql, $aOrderBy, $oColumn);

				} else {
					$oColumn->removeCssClass('sortasc');
					$oColumn->removeCssClass('sortdesc');
				}
			}

			$sSql = rtrim($sSql, ', ');

		// Normalfall
		} else {

			$sSql .= $sOrderPart;
			foreach($aColumnList as &$oColumn) {
				if(
					$this->_oGui->query_orderAlias == $oColumn->db_alias &&
					(
						$oColumn->db_column == $this->_oGui->query_orderField ||
						(
							$oColumn->sortable_column !== null &&
							$oColumn->sortable_column == $this->_oGui->query_orderField
						)
					)
				) {

					if($this->_oGui->query_order == 'DESC') {
						$oColumn->order = 'ASC';
						$oColumn->removeCssClass('sortasc');
						$oColumn->addCssClass('sortdesc');
					} else {
						$oColumn->order = 'DESC';
						$oColumn->removeCssClass('sortdesc');
						$oColumn->addCssClass('sortasc');
					}

				} else {
					$oColumn->removeCssClass('sortasc');
					$oColumn->removeCssClass('sortdesc');
				}
			}

		}

	}

	/**
	 * @param string $sSql
	 * @param array $aOrderBy
	 * @param Ext_Gui2_Head $oColumn
	 */
	protected function setQueryOrderByDataManualSort(&$sSql, &$aOrderBy, $oColumn) {

		foreach((array)$oColumn->order_settings as $sKey=>$sDirection) {
			$aKey = $this->_getFieldIdentifier($sKey);
			if(!empty($aKey['alias'])){
				$sSql .= '`'.$aKey['alias'].'`.';
			}
			$sSql .= '`'.$aKey['column'].'`';

			if(
				$aOrderBy['order'] == 'ASC' &&
				$sDirection == 'DESC'
			) {
				$sDirection = 'DESC';
			} elseif(
				$aOrderBy['order'] == 'DESC' &&
				$sDirection == 'DESC'
			) {
				$sDirection = 'ASC';
			} else {
				$sDirection = $aOrderBy['order'];
			}
			$sSql .= ' '.$sDirection.' ';
			$sSql .= ', ';
		}

	}

	/**
	 * @todo Erweitern für die Behandlung von encoded IDs
	 * @param <type> $aSelectedIds
	 * @param <type> $sWherePart
	 * @param <type> $aSql
	 */
	protected function setQueryIdDataByRef(&$aSelectedIds, &$aSqlParts, &$aSql) {

		$sWhereIdPart = '';

		// IDs Filtern die angeben wuden ( loadBars beim anklicken einer Zeile optimieren )
		if(
			!empty($aSelectedIds) &&
			$this->request->get('task') != 'loadTable' // bei laden der tabelle darf natürlich nicht gefiltert werden
		) {

			$bCheckEncode = $this->_oGui->checkEncode();

			if($bCheckEncode) {

				if(!isset($aSqlParts['having'])) {
					$aSqlParts['having'] = '';
				}

				$sHavingPart =& $aSqlParts['having'];

				// Wenn selectierte IDs angegeben sind, dann selectiere nur diese
				if(empty($aSqlParts['having'])) {
					$sWhereIdPart = ' HAVING ( ';
				} else {
					$sWhereIdPart = ' AND ( ';
				}

				$aEncodeData = $this->_oGui->encode_data;

				$sIdColumnIdentifier = $this->_setFieldIdentifier($this->_oGui->query_id_column, $this->_oGui->query_id_alias);

				$aEncodeData = array_merge(array($sIdColumnIdentifier), (array)$aEncodeData);

				$aDecodedData = $this->_oGui->decodeId($aSelectedIds);

				$bEndBracket = false;
				
				// Jede Zeile durchlaufen
				foreach((array)$aDecodedData as $iRow=>$aData) {

					$sHavingPart .= $sWhereIdPart;
					$sHavingPart .= ' ( ';

					$bEndBracket = true;
					
					$sInnerPart = ' ';

					// Jedes Feld durchlaufen
					foreach((array)$aEncodeData as $iKey=>$sEncodeData) {

						$sHavingPart .= $sInnerPart;

						$aField = $this->_getFieldIdentifier($sEncodeData);
						$sEncodeData = $aField['column'];

						if($aField['alias']) {
							$sHavingPart .= '#having_encoded_id_alias_'.(int)$iKey.'.';
							$aSql['having_encoded_id_alias_'.(int)$iKey] = $aField['alias'];
						}

						if(is_null($aData[$sEncodeData])) {
							$sHavingPart .= '#having_encoded_id_field_'.(int)$iKey.' IS NULL ';
						} else {
							$sHavingPart .= '#having_encoded_id_field_'.(int)$iKey.' = :having_encoded_id_value_'.(int)$iRow.'_'.(int)$iKey.' ';
							$aSql['having_encoded_id_value_'.(int)$iRow.'_'.(int)$iKey] = $aData[$sEncodeData];
						}

						$aSql['having_encoded_id_field_'.(int)$iKey] = $sEncodeData;

						$sWhereIdPart = ' OR ';
						$sInnerPart = ' AND ';

					}

					$sHavingPart .= ' ) ';

				}

				if($bEndBracket) {
					$sHavingPart .= ' ) ';
				}

			} else {

				if(!isset($aSqlParts['where'])) {
					$aSqlParts['where'] = '';
				}

				$sWherePart =& $aSqlParts['where'];

				// Wenn selectierte IDs angegeben sind, dann selectiere nur diese
				if($sWherePart == ''){
					$sWhereIdPart = ' WHERE ( ';
				} else {
					$sWhereIdPart = ' AND ( ';
				}

				$aSelectedIds = $this->_oGui->decodeId($aSelectedIds, $this->_oGui->query_id_column);

				foreach((array)$aSelectedIds as $iKey => $iId){
					$sWherePart .= $sWhereIdPart;
					if($this->_oGui->query_id_alias != ''){
						$sWherePart .= '#id_alias.';
					}
					$sWherePart .= '#id_field = :selected_id_'.$iKey.' ';
					$sWhereIdPart = ' OR ';
					$aSql['id_alias'] = $this->_oGui->query_id_alias;
					$aSql['id_field'] = $this->_oGui->query_id_column;
					$aSql['selected_id_'.$iKey] = (int)$iId;
				}

				$sWherePart .= ' ) ';

			}			

		}

	}

	protected function _setFilterDataByRef($bWDSearch = false){

		// Filter Elemente mit einbauen
		$aFilterElements = $this->_oGui->getAllFilterElements();
        
        $aLastFilterData = (array)$this->_oGui->getOption('last_filter_data');

		// Alle Filter durchgehen, prüfen ob Filterwert übergeben wurde und Filter SQL generieren
		foreach($aFilterElements as $iKey => &$oElement) {
			$this->_setFilterElementDataByRef($oElement, $iKey, $aLastFilterData, $bWDSearch);
		}
        
        $this->_oGui->setOption('last_filter_data', $aLastFilterData);
    
	}

	/**
	 * @TODO Dieser redundante Part mit $bVarsFound ist absolut unnötig
	 *
	 * @param Ext_Gui2_Bar_Filter_Abstract $oElement
	 * @param int $iElementKey
	 * @param array $aLastFilterData
	 * @param bool $bWDSearch
	 */
	protected function _setFilterElementDataByRef(Ext_Gui2_Bar_Filter_Abstract $oElement, $iElementKey, &$aLastFilterData, $bWDSearch = false) {

		$aFilter = &$this->_aFilter;
		$aQueryParts = &$this->_aQueryParts;
		$aSql = &$this->_aSql;
		$oWDSearch = &$this->_oWDSearch;
		$aSearchColumns = $this->_aWDSearchSearchColumns;

		$bVarsFound = false;
		$iFilterPartLength = strlen($aQueryParts[$oElement->filter_part] ?? '');

		if(
			!empty($aFilter) &&
			is_array($aFilter)
		) {

			if ($oElement instanceof Ext_Gui2_Bar_Filter) {

				if(array_key_exists($oElement->id, $aFilter)) {

					$mValue = $aFilter[$oElement->id];

					if($oElement->hasValue($mValue)) {

						$aLastFilterData[$oElement->id] = array('value' => $mValue);
						$bNegate = !empty($aFilter[$oElement->buildKeyForNegate()]);

						if(!$bWDSearch) {
							$oElement->setSqlDataByRef($mValue, $bNegate, $aQueryParts, $aSql, $iElementKey, $this->_oGui);
						} else {
							$oElement->setWDSearchQuery($mValue, $bNegate, $oWDSearch, $aSearchColumns, $this->_oGui);
						}
					}

					// Keine Ahnung, wofür das gut ist, da es gleichzeitig $aLastFilterData gibt und bei der Sidebar sonst der Default-Query dann nicht funktioniert
					if (!$oElement->sidebar) {
						$oElement->value = $mValue;
					}
					$bVarsFound = true;

				}

			// zeitraum Filter
			} elseif ($oElement instanceof Ext_Gui2_Bar_Timefilter) {

				$mFrom = '';
				$mTo = '';
				$bNewFilters = $oElement->sidebar;

				if ($bNewFilters) {
					if (is_string($aFilter[$oElement->id])) {
						// JavaScript encodeURIComponent(['a', 'b']) = "a,b"
						[$mFrom, $mTo] = explode(',', $aFilter[$oElement->id], 2);
					} else {
						$mFrom = $aFilter[$oElement->id][0] ?? '';
						$mTo = $aFilter[$oElement->id][1] ?? '';
					}
				}

				if(array_key_exists($oElement->from_id, $aFilter) && !$bNewFilters) {
					$mFrom = $aFilter[$oElement->from_id];
//					$bVarsFound = true;
				}

				if(array_key_exists($oElement->until_id, $aFilter) && !$bNewFilters) {
					$mTo = $aFilter[$oElement->until_id];
//					$bVarsFound = true;
				}

				if(array_key_exists($oElement->id.'_basedon', $aFilter) && !$bNewFilters) {
					
					
					$sBasedOn = $aFilter[$oElement->id.'_basedon'];
					
					$aBasedOnField = self::getFieldIdentifier($sBasedOn);

					$sColumn					= (string)$aBasedOnField['column'];

					$aFromUntilData				= $oElement->getFromUntilData($sColumn);
					$sColumnFrom				= $aFromUntilData['from_column'];
					$sColumnUntil				= $aFromUntilData['until_column'];

					$oElement->db_from_alias	= (string)$aBasedOnField['alias'];
					$oElement->db_until_alias	= (string)$aBasedOnField['alias'];

					$oElement->db_from_column	= $sColumnFrom;
					$oElement->db_until_column	= $sColumnUntil;

					$sSearchType				= $oElement->getSearchType($sColumn);

					$oElement->search_type		= $sSearchType;
				}

				$bVarsFound = $oElement->hasValue([$mFrom, $mTo]);

				// Wenn beide Daten gesammelt wurde setzte sie in den Query
				if(
//					!empty($mFrom) ||
//					!empty($mTo)
					$bVarsFound
				) {

                       $aLastFilterData[$oElement->id] = array('from' => $mFrom, 'until' => $mTo);

					if(!$bWDSearch){
						$oElement->setSqlDataByRef([$mFrom, $mTo], false, $aQueryParts, $aSql, $iElementKey, $this->_oGui);
					} else {
						$oElement->setWDSearchQuery([$mFrom, $mTo], false, $oWDSearch, [], $this->_oGui);
					}

					$oElement->default_from = $mFrom;
					$oElement->default_until = $mTo;

				}

			}
		}

		// Wenn keine Sucheingaben übergeben wurden, Defaultwerte nehmen
		if($bVarsFound == false) {

			$aElementLastFilterData = $aLastFilterData[$oElement->id] ?? [];

			if(
				$oElement instanceof Ext_Gui2_Bar_Filter &&
				$oElement->value != '' &&
				$oElement->value != 'xNullx' &&
				(
					$oElement->filter_type != 'checkbox' ||
					(
						$oElement->filter_type == 'checkbox' &&
						$oElement->checked == 1
					)
				)
			) {

				$mValue = $oElement->value;
				
				if(!empty($aElementLastFilterData)) {
					$mValue = $aElementLastFilterData['value'];
				}

				if($oElement->hasValue($mValue)) {

					if(!$bWDSearch) {
						$oElement->setSqlDataByRef($mValue, false, $aQueryParts, $aSql, $iElementKey, $this->_oGui);
					} else {
						$oElement->setWDSearchQuery($mValue, false, $oWDSearch, $aSearchColumns, $this->_oGui);
					}
					
				}

			} else if(
				// Bei der Sidebar wird das vom FilterQuery und $oElement->value übernommen
				!$oElement->sidebar &&
				$oElement instanceof Ext_Gui2_Bar_Timefilter &&
				(
					$oElement->default_from != '' ||
					$oElement->default_until != ''
				)
			) {

				// Achtung: Hiermit wird an der GUI-Session rumgepfuscht
				if(!empty($aElementLastFilterData)) {
					$oElement->default_from = $aElementLastFilterData['from'];
					$oElement->default_until = $aElementLastFilterData['until'];
				}

				if(!$bWDSearch) {
					$oElement->setSqlDataByRef([$oElement->default_from, $oElement->default_until], false, $aQueryParts, $aSql, $iElementKey, $this->_oGui);
				} else {
					$oElement->setWDSearchQuery([$oElement->default_from, $oElement->default_until], false, $oWDSearch, $aSearchColumns, $this->_oGui);
				}
			}
		}

		$iFilterPartLengthAfter	= strlen($aQueryParts[$oElement->filter_part] ?? '');

		if(
			$iFilterPartLengthAfter <= $iFilterPartLength &&
			$oElement->required == true
		){
			$sFilterLabel = $oElement->label;
			if(!empty($sFilterLabel)) {
				$sErrorMessage = 'Bitte überprüfen Sie Ihre Filtereingabe "%s".';
			} else {
				$sErrorMessage = 'Bitte überprüfen Sie Ihre Filtereingaben.';
			}
			$sError = L10N::t($sErrorMessage, $this->_oGui->gui_description);
			$sError = str_replace('%s',$sFilterLabel,$sError);

			$this->aAlertMessages[] = $sError;
		}

	}

	/**
	 * @return array
	 */
	public function getFilter() {
		return $this->_aFilter;
	}
	
	public function setFilterValues(&$aFilter) {
		
		// Leere Filterwerte entfernen
		if(is_array($aFilter)) {
			foreach($aFilter as $sKey=>&$mItems) {
				if(is_array($mItems)) {
					$mItems = array_filter($mItems, function($mValue) {
						return !empty($mValue);
					});
					$mItems = array_values($mItems);
				}
			}
		}

		$this->_aFilter = $aFilter;
		
	}
	
	protected function _setWDSearchFilterQueries(&$aFilter, &$oWDSearch, $aDataColumns, $aSearchColumns){
		$this->setFilterValues($aFilter);
		$mapping = $oWDSearch->getIndex()->getMapping();
		foreach ($this->_aFilter as $column => &$values) {
			foreach ($values as &$value) {
				if (
					$value !== "" &&
					!is_array($value) &&
					(
						isset($mapping['properties'][$column]['type']) &&
						$mapping['properties'][$column]['type'] == 'boolean'
					)
				) {
					if (
						$value == 'no' ||
						$value == 'yes'
					) {
						$value = $value == 'yes' ? true : false;
					} else {
						$value = (bool)$value;
					}
				}
			}
		}
		$this->_oWDSearch = $oWDSearch;
		$this->_aWDSearchDataColumns = $aDataColumns;
		$this->_aWDSearchSearchColumns = $aSearchColumns;
		$this->_setFilterDataByRef(true);
	}

	protected function setQueryFilterDataByRef(&$aFilter, &$aQueryParts, &$aSql) {
		$this->_aQueryParts =& $aQueryParts;
		$this->_aSql =& $aSql;
		$this->_setFilterDataByRef();
	}

	final protected function _getTableQueryData($sSql, $aSql = array(), $iEnd = 0, $iLimit = 0) {
		global $_VARS;

		$aAlertMessages		= $this->aAlertMessages;
		$iCount				= 0;
		$aTemp				= array();

		if(empty($aAlertMessages)){

			if($this->_oGui->checkWDSearch()){
				$aTemp = $this->_oDb->getCollection($sSql, $aSql);
			} else {
				$aTemp = $this->_oDb->preparedQueryData($sSql, $aSql);
			}

			$iCount = $this->_oDb->getFoundRows();
		}

		if($this->bDebugmode){
			#__pout($sSql);
			#__pout($aSql);
			__pout($this->_oDb->getLastQuery());
			__pout($iCount);
		}

		if($iEnd > $iCount){
			$iEnd = $iCount;
		}

		$iOffset = 0;
		if(!empty($_VARS['offset'])) {
			$iOffset = $_VARS['offset'];
		}
		if($iCount <= 0){
			$iOffset = 0;
		}

		$aResult = array();
		$aResult['data'] = $aTemp;
		$aResult['count'] = $iCount;
		$aResult['offset'] = $iOffset;
		$aResult['end'] = $iEnd;
		$aResult['show'] = $iLimit;

		// Wenn kein Limit dann ist das Ende die Gesammtanzahl
		if(empty($iLimit)){
			$aResult['end'] = $iCount;
		}

		return $aResult;

	}

	/**
	 * Wrapper um die ID für eine Zeile zu bekommen
	 * @param <array> $aRowData
	 * @param <bol> $bGuiId
	 * @return <int>
	 */
	final public function getIdOfRow($aRowData, $bGuiId = 0){

		if(!$this->_oGui->checkEncode()){
			return $aRowData[$this->_oGui->query_id_column];
		}

		$iGuiId = (int)$aRowData['encoded_gui_id'];

		if($bGuiId){
			return $iGuiId;
		} else {
			$iID = $this->_oGui->decodeId($iGuiId, $this->_oGui->query_id_column);
			return $iID;
		}

	}

	/**
	 * Public-Wrapper für Dialog-Data…
	 *
	 * @param int[] $aIds
	 */
	public function saveNewSort($aIds) {
		$this->_saveNewSort($aIds);
	}

	protected function _saveNewSort($aIds){

		$iPos = 1;

		$aParams = array(
			'column'	=> $this->_oGui->row_sortable_column,
			'alias'		=> $this->_oGui->row_sortable_alias
		);

		foreach((array)$aIds as $iId){

			$aParams['row_id']	= $iId;
			$aParams['value']	= $iPos;

			$this->updateOne($aParams);
			$iPos++;
		}

	}

	/**
	 * Prüfen, ob die Row gelöscht werden darf
	 *
	 * @param int $iRowId
	 * @return array|bool
	 */
	protected function checkDeleteRow($iRowId) {
		global $_VARS;

		// Prüfen, ob der Eintrag wirklich gelöscht werden darf #3049
		$oBar = $this->_oGui->getBar(0);
		$oDeleteIcon = $oBar->createDeleteIcon('');
		$aRowData[0] = $this->_oGui->getOneColumnData();
		$aSelectedIds = (array)$_VARS['id'];
		$iActive = $this->_oGui->_oIconStatusActive->getStatus($aSelectedIds, $aRowData, $oDeleteIcon);

		return !!$iActive;

	}

	/**
	 * Row löschen (Icon)
	 *
	 * @param int $iRowId
	 * @return array|bool
	 */
	protected function deleteRow($iRowId) {

		if(
			!empty($this->_oGui->_aTableData['table']) &&
			is_string($this->_oGui->_aTableData['table'])
		) {

			$this->_getWDBasicObject(array($iRowId));

			$mCheck = $this->checkDeleteRow($iRowId);

			if($mCheck === true) {

				$mDelete = $this->oWDBasic->delete();
				$this->deleteRowHook($iRowId);

				if($mDelete === true) {

					if(
						$this->_oGui->checkWDSearch() &&
						!$this->_oGui->wdsearch_use_stack
					) {
						$this->_oGui->getDataObject()->writeWDSearchIndexChange('_uid', $iRowId, 'deleted');
					}

					return true;

				} else {
					// Fehler von validate() direkt zurückliefern
					return $mDelete;
				}

			} else {
				return $mCheck;
			}

		} else {

			$aParams = array(
				'row_id'	=> $iRowId,
				'value'		=> 0,
				'column'	=> 'active',
				'alias'		=> $this->_oGui->query_id_alias
			);

			$bSuccess = $this->updateOne($aParams);
		}

		return $bSuccess;

	}

	protected function saveInplaceEditor($aParameter){

		$aParams = array(
			'row_id'	=> $aParameter['row_id'],
			'value'		=> $aParameter['value'],
			'column'	=> $aParameter['column'],
			'alias'		=> $aParameter['alias'],
			'type'		=> $aParameter['save_type'],
			'parent_id'	=> $aParameter['parent_id']
		);

		$mSuccess = $this->updateOne($aParams);

		return $mSuccess;

	}

	/**
	 * Exportiert eine GUI ins CSV Format
	 * 
	 * @param string $sTitle
	 * @param array $aExport
	 */
	final protected function exportCSV($sTitle = 'export', $aExport = array())
	{
		if(empty($sTitle))
		{
			$sTitle = 'Export';
		}

		$sCleanFilename = \Util::getCleanFileName($sTitle);

		//OUPUT HEADERS
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private", false);
		header("Content-Type: text/csv");
		header("Content-Disposition: attachment; filename=\"".$sCleanFilename.".csv\";" );
		header("Content-Transfer-Encoding: binary"); 

		$sCharset	= $this->_getCharsetForExport();
		$sSeparator	= $this->_getSeparatorForExport();

		foreach((array)$aExport as $i=> $aLine)
		{
			$sLine = '';

			foreach((array)$aLine as $n=> $mValue)
			{
				if(is_numeric($mValue))
				{
					$sLine .= $mValue;
				}
				else
				{
					$mValue = html_entity_decode((string)$mValue, ENT_QUOTES, 'UTF-8');

					// Wenn bereits das Charset schon utf8 ist, dann muss nicht erneut konvertiert werden
					if($sCharset !== 'UTF-8')
					{
						$mValue = iconv('UTF-8', $sCharset, $mValue);
					}

					// Vereinheitliche alle Breaks zu <br />
					$mValue = str_replace('<br/>', '<br />', $mValue);

					// Entferne alle <br /> am Ende des Strings
					while(strpos($mValue, '<br />') === strlen($mValue) - 6)
					{
						$mValue = substr($mValue, 0, strlen($mValue) - 6);
					}

					// Ersetze alle <br /> mitten im String mit dem Separator und einem Leerzeichen
					$mValue = str_replace('<br />', $sSeparator . ' ', $mValue);

					$mValue = str_replace('"', '""', $mValue);
					
					$sLine .= '"' . strip_tags($mValue) . '"';
				}

				$sLine .= $sSeparator;
			}

			// Letzten Separator entfernen und einen Umbruch für neue Zeile einfügen
			$sLine = substr($sLine, 0, -1) . "\n";

			echo $sLine;
		}

		die();
	}
	
	/**
	 * Zeichensatz für CSV-Export
	 * für ableitungen die protected ableiten!
	 * @return string 
	 */
	final public function getCharsetForExport(){
		return $this->_getCharsetForExport();
	}
	
	/**
	 * Trennzeichen für CSV-Export
	 * für ableitungen die protected ableiten!
	 * @return string 
	 */
	final public function getSeparatorForExport(){
		return $this->_getSeparatorForExport();
	}

	/**
	 * Zeichensatz für CSV-Export
	 * Kann abgeleitet werden
	 * @return string 
	 */
	protected function _getCharsetForExport(){
		$sCharset = 'CP1252';
		return $sCharset;
	}
	
	/**
	 * Trennzeichen für CSV-Export
	 * Kann abgeleitet werden
	 * @return string 
	 */
	protected function _getSeparatorForExport(){
		$sSeparator = ';';
		return $sSeparator;
	}

	protected function _exportMultiplePdf($aSelectedIds, $oMultiplePdfClass){
		
		$aSelectedIds	= (array)$aSelectedIds;
		$oPdfMerge		= $this->_getPdfMergeObject();

		if($this->_oGui->encode_data_id_field != false) {
			$sIdField = $this->_oGui->encode_data_id_field;
		} else {
			$sIdField = $this->_oGui->query_id_column;
		}

		$aSelectedIds		= (array)$this->_oGui->decodeId($aSelectedIds, $sIdField);

		$iDocumentsAdded	= 0;
		foreach($aSelectedIds as $iSelectedId)
		{
			$sPath = $oMultiplePdfClass->getPdfPath($iSelectedId);
			if(is_file($sPath))
			{
				$oPdfMerge->addPdf($sPath);
				$iDocumentsAdded++;
			}
		}

		$oPdfMerge->display();

		die();
	}

	protected function _getPdfMergeObject(){
		$oMergeClass = new Ext_Gui2_Pdf_Merge();

		return $oMergeClass;
	}

	/**
	 * Speichert die Flexibilität
	 */
	final protected function saveFlexMenue($aFlexData){

		$oFlex = Ext_Gui2_Flex::getInstance($this->_oGui->hash);

		$aColumnList = (array)$this->_oGui->getColumnList(); 

		foreach($aFlexData as $sType => $aData){
			$iPosition = 0;
			foreach($aData as $iOldPosition => $aTemp){
				foreach($aTemp as $sColumnAlias => $iVisible){
					$aDBTemp	= explode('||', $sColumnAlias);
					$sDbColumn	= $aDBTemp[0];
					$sDbAlias	= $aDBTemp[1];
					$iVisible	= (int)$iVisible;
					//wenn Flexibilität ausgeschaltet ist, dann visible immer auf 1 setzen
					foreach($aColumnList as $oColumn){
						if(
							$oColumn->db_column == $sDbColumn &&
							$oColumn->db_alias == $sDbAlias &&
							$oColumn->flexibility === false &&
                            $sType == 'list'
						){
							$iVisible = 1;
						}
					}
					$oFlex->saveFlexData($sDbColumn, $sDbAlias, $sType, $iPosition, $iVisible);
				}
				$iPosition++;
			}

		}

	}

	public function t($sTranslate, $sDescription = null) {
		return $this->_oGui->t($sTranslate, $sDescription);
	}
	
	/**
	 * Baut den Inhalt für das Flexibilitätsmenü
	 */
	final protected function prepareFlexMenue(){

		$oAccess = Access::getInstance();

		if(!$oAccess->hasRight('gui2_flexibility')) {

            $oDialog = new Ext_Gui2_Dialog($this->t('Es ist ein Fehler aufgetreten!'),$this->t('Es ist ein Fehler aufgetreten!'));
            $oDiv = $oDialog->createNotification($this->t('Es ist ein Fehler aufgetreten!'), $this->t('Sie haben keinen Zugriff auf diese Funktion!'));
            $oDialog->setElement($oDiv);
            $oDialog->save_button = false;
            $aData	= $oDialog->generateAjaxData(array(), $this->_oGui->hash);

            return $aData;
        }
        
		$oFlex = Ext_Gui2_Flex::getInstance($this->_oGui->hash);

		$aFlexColumns			= array();
		$aFlexColumns['list']	= array();
		$aFlexColumns['pdf']	= array();
		$aFlexColumns['csv']	= array();
		$aFlexColumns['excel']	= array();

		$bGroups = false;

		$aColumns = $this->_oGui->getColumnList();
		$this->prepareColumnListByRef($aColumns);
		$iDefaultPosition = 0;

		foreach($aColumns as $oColumn) {

			## TYPE list

			$sLabel = $oColumn->title;
			$sGroup = '';

			if(
				$oColumn->group &&
				$oColumn->group instanceof Ext_Gui2_HeadGroup
			) {
				$sGroup = $oColumn->group->title;
				$bGroups = true;
			}

			$aTemp = array();
			$aTemp = $oFlex->checkForFlexData('list', $oColumn->db_column, $oColumn->db_alias);

			if($aTemp === false){
				$aTemp = array();
				$aTemp['db_column'] = $oColumn->db_column;
				$aTemp['db_alias']	= $oColumn->db_alias;
				$aTemp['position']	= $iDefaultPosition;
				$aTemp['type']		= 'list';
				if($oColumn->default === true) {
					$aTemp['visible'] = 1;
				} else {
					$aTemp['visible'] = 0;
				}
			}
			$aTemp['label'] = $sLabel;
			$aTemp['mouseover_title'] = $oColumn->mouseover_title;
			$aTemp['group'] = $sGroup;
			//Flexibilität ausgeschaltet
			$aTemp['flexibility'] = $oColumn->flexibility;
			$aFlexColumns['list'][] = $aTemp;

			#########

			## TYPE pdf

			$aTemp = array();
			$aTemp = $oFlex->checkForFlexData('pdf', $oColumn->db_column, $oColumn->db_alias);
			if($aTemp === false){
				$aTemp = array();
				$aTemp['db_column'] = $oColumn->db_column;
				$aTemp['db_alias']	= $oColumn->db_alias;
				$aTemp['position']	= $iDefaultPosition;
				$aTemp['type']		= 'pdf';
				if($oColumn->default === true) {
					$aTemp['visible'] = 1;
				} else {
					$aTemp['visible'] = 0;
				}
			}
			$aTemp['label'] = $sLabel;
			$aTemp['mouseover_title'] = $oColumn->mouseover_title;
			$aTemp['group'] = $sGroup;
			$aTemp['flexibility'] = 1;
			$aFlexColumns['pdf'][] = $aTemp;

			#########

			## TYPE csv

			$aTemp = array();
			$aTemp = $oFlex->checkForFlexData('csv', $oColumn->db_column, $oColumn->db_alias);
			if($aTemp === false){
				$aTemp = array();
				$aTemp['db_column'] = $oColumn->db_column;
				$aTemp['db_alias']	= $oColumn->db_alias;
				$aTemp['position']	= $iDefaultPosition;
				$aTemp['type']		= 'csv';
				if($oColumn->default === true) {
					$aTemp['visible'] = 1;
				} else {
					$aTemp['visible'] = 0;
				}
			}
			$aTemp['label'] = $sLabel;
			$aTemp['mouseover_title'] = $oColumn->mouseover_title;
			$aTemp['group'] = $sGroup;
			$aTemp['flexibility'] = 1;
			$aFlexColumns['csv'][] = $aTemp;

			#########

			## TYPE list

			$aTemp = array();
			$aTemp = $oFlex->checkForFlexData('excel', $oColumn->db_column, $oColumn->db_alias);
			if($aTemp === false){
				$aTemp = array();
				$aTemp['db_column'] = $oColumn->db_column;
				$aTemp['db_alias']	= $oColumn->db_alias;
				$aTemp['position']	= $iDefaultPosition;
				$aTemp['type']		= 'excel';
				if($oColumn->default === true) {
					$aTemp['visible'] = 1;
				} else {
					$aTemp['visible'] = 0;
				}
			}
			$aTemp['label'] = $sLabel;
			$aTemp['mouseover_title'] = $oColumn->mouseover_title;
			$aTemp['group'] = $sGroup;
			$aTemp['flexibility'] = 1;
			$aFlexColumns['excel'][] = $aTemp;

			#########

			$iDefaultPosition++;

		}


		// Sortieren

		usort($aFlexColumns['list'], array('Ext_Gui2_Flex', 'sortFlexData'));
		usort($aFlexColumns['pdf'], array('Ext_Gui2_Flex', 'sortFlexData'));
		usort($aFlexColumns['csv'], array('Ext_Gui2_Flex', 'sortFlexData'));
		usort($aFlexColumns['excel'], array('Ext_Gui2_Flex', 'sortFlexData'));

		$oDialog = new Ext_Gui2_Dialog();
		$oTab1 = $oDialog->createTab(L10N::t('Liste',			Ext_Gui2::$sAllGuiListL10N));
		$oTab2 = $oDialog->createTab(L10N::t('PDF Export',		Ext_Gui2::$sAllGuiListL10N));
		$oTab3 = $oDialog->createTab(L10N::t('CSV Export',		Ext_Gui2::$sAllGuiListL10N));
		$oTab4 = $oDialog->createTab(L10N::t('Excel Export',	Ext_Gui2::$sAllGuiListL10N));

		$i = 1;
	
		foreach($aFlexColumns as $sType => $aData){

			$oTempTab = 'oTab'.$i;
			$oDiv = $oDialog->create('div');
			$oDiv->class = 'GUIDialogFlexRowList';
			$r = 0;

			$oDialog->aFlexColumnGroups = array();

			foreach($aData as $aTemp){
				$oRow = $oDialog->createFlexRow($aTemp, $sType, $bGroups, $i);
				$oRow->id = 'flex_row_'.$i.'_'.$r;
				$oRow->class = 'GUIDialogRow flex_row';
				$oDiv->setElement($oRow);
				$r++;
			}

			$$oTempTab->setElement($oDiv);
			$i++;

		}

		$oDialog->setElement($oTab1);
		//$oDialog->setElement($oTab2); // Wird wieder aktiviert wenn GUI2 das kann
		$oDialog->setElement($oTab3);
		$oDialog->setElement($oTab4);

		$oDialog->width = 600;

		$aData				= $oDialog->generateAjaxData(array(), $this->_oGui->hash);
		$aData['title']		= L10N::t('Flexibilität', Ext_Gui2::$sAllGuiListL10N);
		$aData['action']	= 'saveFlex';
		$aData['task']		= 'saveFlex';
		$aData['bSaveButton']= 1;
		$aData['buttons'] = [
			[
				'label' => $this->t('Zurücksetzen'),
				'task' => 'request',
				'action' => 'reset-flex',
				'default' => true
			]
		];
		$aData['id'] = 'FLEX';

		return $aData;
	}

	public function requestResetFlex($aVars) {
		
		$oFlex = Ext_Gui2_Flex::getInstance($this->_oGui->hash);
		$oFlex->deleteFlexData('all');

		$aData = $this->prepareFlexMenue();
		
		$aTransfer = [];
		$aTransfer['action'] 	= 'openFlexDialog';
		$aTransfer['data']		= $aData;
		$aTransfer['error'] 	= array();		
		
		return $aTransfer;
	}

	/**
	 * Ajax Request verarbeiten
	 * @param $_VARS
	 * @return array
	 */
	final protected function _switchAjaxRequest($_VARS) {
		global $session_data;

		$aTransfer = array();

		// Fehlerspeicher zurücksetzen
		$this->aAlertMessages = array();

		// WDBasic zurücksetzten damit beim öffnen eines anderen Dialoges nichts durcheinanderkommt
		$this->oWDBasic = null;
		
		$this->_aGetDialogResetWDBasicCache = array();

		$iDebugStartTotal = Util::getMicrotime();

		$bValidTransaction = true;

		/*
		 * Wenn Dialog ID und TAN übergeben werden, wird TAN überprüft
		 * @todo Das ist so nicht sicher, wenn diese Angaben optional sind 
		 */
		if(
			!empty($_VARS['dialog_id']) &&
			!empty($_VARS['token']) &&
			(
				// Nur beim speichern und öffnen validieren
				// da wir sonst bei reloadtab etc evt die fehlermeldung bekommen wenn der debugmode aus ist!
				$_VARS['task'] == 'saveDialog' 
			)
		) {
			// Token überprüfen
			$bValidTransaction = $this->_checkDialogToken($_VARS['dialog_id'], $_VARS['token']);
		}

		// IDs der Eltern-GUI immer ins Objekt schreiben, falls vorhanden
		if(isset($_VARS['parent_gui_id'])) {
			$this->_aParentGuiIds = $_VARS['parent_gui_id'];
		} else {
			$this->_aParentGuiIds = null;
		}

		if($bValidTransaction === true) {

			$this->_oGui->bReadOnly = $this->isLockedGui();
			
			# START : Tabellen Daten Laden #
			if($_VARS['task'] == 'loadTable') {

				if(!empty($_VARS['limit'])) {
					$this->_oGui->setTableData('limit', (int)$_VARS['limit']);
				}

				// TODO Warum wird das bei JEDEM loadTable gemacht?
				if(isset($_VARS['loadBars']) && $_VARS['loadBars'] == 0){
					$this->_oGui->load_table_bar_data = 0;
				} else {
					$this->_oGui->load_table_bar_data = 1;
				}
				$this->_oGui->load_table_pagination_data = 1;

				if($this->bDebugmode){
					__pout('START ### Tabellen Daten laden');
					$iDebugStart = Util::getMicrotime();
				}

				$aTable = $this->_oGui->getTableData($_VARS['filter'] ?? null, $_VARS['orderby'] ?? null, array(), 'list', false);

				if($this->bDebugmode){
					$iDebugEnde = Util::getMicrotime();
					$iDebugNow = $iDebugEnde - $iDebugStart;
					__pout('ENDE ### Tabellen Daten laden # '.$iDebugNow);
				}

				if($this->bDebugmode){
					__pout('START # Rückgabe Array füllen');
					$iDebugStart = Util::getMicrotime();
				}

				$aTransfer['action'] = 'createTable';

				// Aufgelaufene Fehler ergänzen
				Error_Handler::mergeErrors($this->aAlertMessages, $this->_oGui->gui_description);
				
				if(!empty($this->aAlertMessages)) {
					
					// Überschrift
					if(!empty($this->aAlertMessages)){
						array_unshift($this->aAlertMessages, array('message' => $this->_oGui->t('Fehler')));
					}
					
					$aTransfer['error'] 	= $this->aAlertMessages;
					$this->aAlertMessages = array();
				} else {
					$aTransfer['error'] 	= array();
				}

				$aTransfer['data'] = $aTable;
				$aTransfer['data']['limit'] = $this->_oGui->_aTableData['limit'] ?? null;

				$aTransfer['data']['column_flexibility'] = (bool)$this->_oGui->column_flexibility;

				$aTransfer['data']['row_sortable'] = $this->_oGui->row_sortable;
				if($this->_oGui->bReadOnly) {
					$aTransfer['data']['row_sortable'] = false;
				}
				
				if($this->bDebugmode){
					$iDebugEnde = Util::getMicrotime();
					$iDebugNow = $iDebugEnde - $iDebugStart;
					__pout('ENDE # Rückgabe Array füllen # '.$iDebugNow);
				}

				if(!empty($aTable['forced_transferdata'])){
					$aTransfer	= $aTable['forced_transferdata'];
				}

			}
		# ENDE #
		# START : Icon Daten laden #
			else if($_VARS['task'] == 'updateIcons'){
				$this->_oGui->load_table_bar_data = 1;
				// Blätterfunktion nicht neu laden da nur Leistendaten geholt werden
				$this->_oGui->load_table_pagination_data = 0;
				$aTable = $this->_oGui->getTableData($_VARS['filter'] ?? null, $_VARS['orderby'] ?? null, $_VARS['id'] ?? null);
				$aTransfer['action'] 	= 'updateIcons';
				$aTransfer['error'] 	= array();
				$aTransfer['data'] 		= $aTable;

			}
		# ENDE #
		# START : Leisten Daten laden #
			else if($_VARS['task'] == 'loadBars'){
				$this->_oGui->load_table_bar_data = 1;
				// Blätterfunktion nicht neu laden da nur Leistendaten geholt werden
				$this->_oGui->load_table_pagination_data = 0;
				$aTable = $this->_oGui->getTableData($_VARS['filter'] ?? null, $_VARS['orderby'] ?? null, $_VARS['id'] ?? null);
				$aTransfer['action'] 	= 'loadBars';
				$aTransfer['error'] 	= array();
				$aTransfer['data'] 		= $aTable;

			}
		# ENDE #
		# START : Inplace Editor #
			else if($_VARS['task'] == 'edit_on') {
				$mValue = $_VARS['save'];

				$aParams = array(
					'value'		=> $_VARS['save'],
					'row_id'	=> $_VARS['id'],
					'column'	=> $_VARS['db_column'],
					'alias'		=> $_VARS['db_alias'],
					'old_value'	=> $_VARS['old_value']
				);

				$mSuccess = $this->saveInplaceEditor($aParams);
				if($mSuccess === true) {
					echo $mValue;
				} else {
					echo $this->_oGui->t('Speichern nicht erfolgreich!');
				}
				die();
			}
		# ENDE #
		# START : Save Edit Dialog #
			else if($_VARS['task'] == 'reloadDialogTab'){

				$aTransfer = $this->saveDialogData($_VARS['action'], $_VARS['id'], $_VARS['save'], $_VARS['additional'], false);

				if(!empty($_VARS['id']) && empty($aTransfer['data']['id'])){
					$aTransfer['data']['id'] = 'ID_'.implode('_', (array)$_VARS['id']);
				} else if(empty($aTransfer['data']['id'])) {
					$aTransfer['data']['id'] = 'ID_0';
				}

				foreach((array)$_VARS['reload_tab'] as $iKey => $iTab){

					if($iTab < 0) {
						$_VARS['reload_tab'][$iKey] = count($aTransfer['data']['tabs']) + (int)$iTab;
					}
					
				}

				// Nicht casten! da auch arrays möglich sein sollen
				$aTransfer['reload_tab'] = $_VARS['reload_tab'];
				$aTransfer['action'] 	= 'reloadDialogTab';
				//$aTransfer['data']		= $aData;
				//$aTransfer['error'] 	= array();

			}
		# ENDE #
		# START : Save Edit Dialog #
			else if($_VARS['task'] == 'update_select_options'){

				$aTransfer = $this->saveDialogData($_VARS['action'], $_VARS['id'], $_VARS['save'], $_VARS['additional'], false);

				$aTransfer['action'] = 'update_select_options';

			}
		# ENDE #
		# START : Save Edit Dialog #
			else if($_VARS['task'] == 'saveDialog'){

				$sAction	= $_VARS['action'];
				$aIds		= $_VARS['id'];
				$aOldIds	= $aIds;

				if(isset($_VARS['save_as_new_from'])){
					// Cache current ID(s)
					$this->_mSaveAsNew = $_VARS['save_as_new_from'];
				}
				if(isset($_VARS['open_new_dialog']))
				{
					$this->_bOpenNewDialog = true;
				}

				$aSaveData = $this->_oGui->getRequest()->input('save');

				// Fehlercodes die bisher durch die Checkbox ignoriert werden sollen
				$aIgnoreErrorCodes = $_VARS['ignore_errors_codes'] ?? [];

				$aTransfer = $this->saveDialogData($sAction, $aIds, $aSaveData, $_VARS['additional'] ?? null);

				if(isset($aTransfer['tab'])) {
					
					// Class ist angegegeben
					if(!is_numeric($aTransfer['tab'])) {
						
						foreach($aTransfer['data']['tabs'] as $iTab=>$aTab) {
							if(
								isset($aTab['options']['class']) && 
								strpos($aTab['options']['class'], $aTransfer['tab']) !== false
							) {
								$aTransfer['tab'] = $iTab;
								break;
							}
						}
						
					} elseif($aTransfer['tab'] < 0) {
						$aTransfer['tab'] = count($aTransfer['data']['tabs']) + (int)$aTransfer['tab'];
					}
					
				}

				if(empty($aTransfer['error'])){
					// Token used setzen
					//$this->_setTokenUsed($sAction, $aOldIds, $sToken);
					//$this->_deleteDialogToken();
				} else if (!empty($aIgnoreErrorCodes)) {
					// Bereits akzeptierte Warnung bei weiteren Warnungen mitsenden da diese nicht nochmal akzeptiert werden müssen
					// TODO schönere Lösung finden
					$aFirstHint = \Illuminate\Support\Arr::first($aTransfer['error'], fn ($error) => is_array($error) && $error['type'] === 'hint');
					if ($aFirstHint !== null) {
						$aTransfer['error'][] = ['type' => 'hint_codes', 'codes' => array_unique($aIgnoreErrorCodes)];
					}
				}

				if($this->_bAddParentIdsForSaveCallback){
                    // ggf. Hashes der Parent Tables anhängen da diese auch neu geladen werden sollen
                    $aTransfer['parent_gui'] = $this->_oGui->getParentGuiData();
                }

                // Werte zurücksetzen, da das sonst komische Effekte haben kann
				$this->_mSaveAsNew = false;
				$this->_bOpenNewDialog = false;

			}
		# ENDE #
		# START : Individueller Inplace Editor #
			else if($_VARS['task'] == 'saveCustomInplaceEditor'){

				$mValue = $_VARS['value'];
				$mValueOld = $_VARS['old_value'];
				$sSaveType = '';

				if($_VARS['type'] == 'calendar'){
					$mValue = $this->_oGui->_oCalendarFormat->convertDate($mValue);
					$mValueOld = $this->_oGui->_oCalendarFormat->convertDate($mValueOld);
					$sSaveType = 'timestamp';
				}

				$aParams = array(
					'value'		=> $mValue,
					'row_id'	=> $_VARS['id'],
					'column'	=> $_VARS['db_column'],
					'alias'		=> $_VARS['db_alias'],
					'old_value'	=> $mValueOld,
					'save_type'	=> $sSaveType,
					'parent_id'	=> $_VARS['parent_gui_id']
				);

				$mSuccess = $this->saveInplaceEditor($aParams);

				if($_VARS['type'] == 'calendar'){
					$mValue = $this->formatDate($mValue);
				}

				$aTransfer['action'] 	= 'customInplaceEditorCallback';
				$aTransfer['error'] 	= array();
				$aTransfer['data'] 		= array('value' => $mValue);
			}
		# ENDE #
		# START : Sortierung Speichern #
			else if($_VARS['task'] == 'saveSort'){
								
				if(!$this->_oGui->bReadOnly) {
					$mValue = $this->_saveNewSort($_VARS['sortablebody_' . $this->_oGui->hash]);
				}				
				
				$aTransfer['error'] 	= array();
			}
		# ENDE #
		# START : Löschen #
			else if($_VARS['task'] == 'deleteRow') {

				$bSuccess = true;
				foreach((array)$_VARS['id'] as $iId){
					try {
						$mSuccess_ = $this->deleteRow($iId);
					} catch (\Core\Exception\Entity\ValidationException $e) {
						$mSuccess_ = [[$e]];
					}

					if($mSuccess_ !== true) {
						$bSuccess = false;
					} else {
						// Das macht irgendwie keinen Sinn, da ein gelöschtes Objekt auch ganz gelöscht sein kann
//						$this->_getWDBasicObject(array($iId));
//						$this->deleteRowTaskHook($this->oWDBasic, $aTransfer);
					}
				}				

				// Validierungsfehler
				if(
					$bSuccess === false ||
					!empty($aTransfer['error'])
				){

					$aErrors = array();

					$aErrors[] = L10N::t('Fehler beim Löschen', $this->_oGui->gui_description);

					if(is_array($mSuccess_)) {
						foreach((array)$mSuccess_ as $sField=>$aError) {
							foreach((array)$aError as $sError) {
								$sMessage = $this->_getErrorMessage($sError, $sField);
								$aErrors[] = $sMessage;
							}
						}
					}

					// Wenn nur eine Fehlermeldung (Titel), dann Fehlermeldung erzeugen
					if(
						count($aErrors) === 1 &&
						empty($aTransfer['error'])
					) {
						$aErrors[] = $aErrors[0];
					}

					$aTransfer['action'] 	= 'showError';
					
					if(!empty($aTransfer['error'])) {
						foreach($aTransfer['error'] as $sMessage) {
							$aErrors[] = $sMessage;
						}
					}
					
					$aTransfer['error'] 	= $aErrors;

				} else {
					$aTransfer['action'] 	= 'deleteCallback';
					$aTransfer['error'] 	= array();
					// ggf. Hashes der Parent Tables anhängen da diese auch neu geladen werden sollen
					$aTransfer['parent_gui'] = $this->_oGui->getParentGuiData();
				}

			}
		# ENDE #
		# START : Dialog öffnen #
			else if($_VARS['task'] == 'openDialog'){

				$aSelectedIds = (array)$_VARS['id'];

				// Prüfen ob man das Icon überhaupt benutzen darf!
				// Wichtig um den Doppelklick beim Editieren abzufangen falls nicht erlaubt
		
				foreach((array)$this->aIconData as $sAction => $aIcon){

					if(
						!empty($_VARS['action']) &&
						$sAction == $_VARS['action']
					){
						// TODO Was ist das?
						$oElement = (object)$aIcon;
						$aRowData[0] = $this->_oGui->getOneColumnData();
						$iActive = $this->_oGui->_oIconStatusActive->getStatus($aSelectedIds, $aRowData, $oElement);
						if($iActive == 0){
							$aTransfer['action'] 	= 'xXx';
						}
					}

				}

				if($this->_oGui->bReadOnly) {
					$aTransfer['action'] = 'xXx';
				}
				
				if(!isset($aTransfer['action']) || $aTransfer['action'] != 'xXx'){

					$aData = $this->prepareOpenDialog($_VARS['action'], $aSelectedIds, false, $_VARS['additional'] ?? null);
					
					if(!empty($aSelectedIds) && empty($aData['id'])){
						// ID sortieren damit einheitlich
						sort($aSelectedIds);
						$aData['id'] = 'ID_'.implode('_', $aSelectedIds);
					} else if(empty($aData['id'])) {
						$aData['id'] = 'ID_0';
					}

					if(!empty($aData)){
						$aTransfer['action'] 	= 'openDialog';
						$aTransfer['data']		= $aData;
						$aTransfer['error'] 	= array();

						if (isset($_VARS['load_translations'])) {
							$aTransfer['data']['translations'] = $this->getTranslationsCache($this->_oGui->gui_description);
						}
					} else {
						$aTransfer['action'] 	= 'showError';
						$aTransfer['error'] 	= array(L10N::t('Fehler beim Laden der Daten', Ext_Gui2::$sAllGuiListL10N));
					}

					// Aufgelaufene Fehler ergänzen
					Error_Handler::mergeErrors($aTransfer['error'], $this->_oGui->gui_description);

				}

			}
		# ENDE #
		# START : Eintrag duplizieren # (dg)
			else if($_VARS['task'] == 'createCopy') {

				$aExecuteCopyReturn = $this->_executeCreateCopy($_VARS);

				if(!empty($aExecuteCopyReturn['options']['copy_openDialog'])) {
					// Dialog öffnen für das kopierte Objekt
					$aTransfer['action'] = 'openDialogForCopy';
					$oFirstCopy = $aExecuteCopyReturn['copies'][0]->id;
					$aData = $this->prepareOpenDialog('edit', (array)$oFirstCopy, false, $_VARS['additional']);
					$aTransfer['data'] = $aData;
				} else {
					// Einfach eine Success-Meldung ausgeben für das kopierte Objekt
					$aTransfer['action'] = 'createCopySuccess';
				}
				
			}
			
		# ENDE #
		# START : Flex Dialog öffnen #
			else if($_VARS['task'] == 'loadFlexmenu'){

				$aData = $this->prepareFlexMenue();
				
				if(!empty($aData)){
					$aTransfer['action'] 	= 'openFlexDialog';
					$aTransfer['data']		= $aData;
					$aTransfer['error'] 	= array();
				} else {
					$aTransfer['action'] 	= 'showError';
					$aTransfer['error'] 	= array(L10N::t('Fehler beim Laden der Flexibilität', Ext_Gui2::$sAllGuiListL10N));
				}

			}
		# ENDE #
		# START : Fehler melden #
			else if($_VARS['task'] == 'reportError') {

				// Fehler nur absenden, wenn auch eine Fehlermeldung vorhanden ist
				if(
					!Util::isDebugIP() &&
					$_VARS['error'] != "Badly formed JSON string: ''"
				) {

					$sMessage = print_r($_VARS['error'], 1)."\n\n";

					if(isset($_VARS['request_status'])) {
						$sMessage .= 'Request status: '.$_VARS['request_status']."\n\n";
					}

					if(isset($_VARS['parameters'])) {
						$sMessage .= 'Parameter: '.print_r(json_decode($_VARS['parameters']), 1)."\n\n";
					}

					if(isset($_VARS['query_string'])) {
						$sMessage .= 'Query string: '.print_r($_VARS['query_string'], 1)."\n\n";
					}

					// Ext_TC_Util::reportError gibt es an dieser Stelle nicht
					Util::reportError('GUI2 Error Reporting (Badly formed JSON string)', $sMessage);

				}

			}
		# ENDE #
		# START : Flex Dialog speichern #
			else if($_VARS['task'] == 'saveFlex'){

				$aErrors = $this->saveFlexMenue($_VARS['flex']);
				if(empty($aErrors)){
					$aTransfer['action'] 	= 'saveFlexCallback';
					$aTransfer['data']		= array();
					$aTransfer['error'] 	= array();
				} else {
					$aTransfer['action'] 	= 'saveFlex';
					$aTransfer['error'] 	= $aErrors;
				}

			}
		# ENDE #
		# START : CSV Export #
			else if($_VARS['task'] == 'export_csv'){

				// Das leeren der IDs bewirkt das ALLE einträge exportiert werden nicht nur
				// die markierten. soll das geändert werden, Zeile entfernen
				$_VARS['id'] = array();

				$this->_oGui->enableCSVExport();
				$aTable = $this->_oGui->getTableData($_VARS['filter'], $_VARS['orderby'], $_VARS['id'], 'csv', true);
				$this->_oGui->disableExport();
				
			}
			else if(
				$_VARS['task'] == 'export_excel' &&
				empty($_VARS['action'])
			){

				// Das leeren der IDs bewirkt das ALLE einträge exportiert werden nicht nur
				// die markierten. soll das geändert werden, Zeile entfernen
				$_VARS['id'] = array();

				$this->_oGui->enableExcelExport();
				$aTable = $this->_oGui->getTableData($_VARS['filter'], $_VARS['orderby'], $_VARS['id'], 'excel', true);
				$this->_oGui->disableExport();
				
			}
		# ENDE #
		# START : Toggle Left menu #
			else if($_VARS['task'] == 'toggleMenu'){
				$bToggleType = 1;
				if($_SESSION['gui2_leftFrame'][$this->_oGui->hash]['toggle'] == 1){
					$bToggleType = 0;
				}elseif($_SESSION['gui2_leftFrame'][$this->_oGui->hash]['toggle'] == 0){
					$bToggleType = 1;
				}
				// Session schreiben
				$_SESSION['gui2_leftFrame'][$this->_oGui->hash]['toggle'] = $bToggleType;

				//$aTransfer['data']['url']	= $this->_oGui->sThisPath;
				$aTransfer['data']['type']	= $bToggleType;
				$aTransfer['action'] 		= 'toggleMenu';
			}
		# ENDE #
		# START : Übersetzungen holen #
			else if($_VARS['task'] == 'translations') {
				// Übersetzungen für JS
				$aTransfer['data']['translations']		= $this->getTranslationsCache($this->_oGui->gui_description);
				$aTransfer['data']['calendar_format']	= $this->_oGui->_oCalendarFormat->format_js;
				$aTransfer['action']	= 'translations';
			}
		# ENDE #
		# START : Ping verarbeiten #
            else if($_VARS['task'] == 'pingStack') {

                foreach($_VARS['stack'] as $sHash => $aParams){
                    $oGui = Ext_Gui2::getClass($sHash, $aParams['instance_hash']);
                    $oData = $oGui->getDataObject();
                    $oData->_executePing((array)($aParams['dialog'] ?? []), $aParams['unload'] ?? null);
                }
                
                $aTransfer['data']['value'] = 1;
				$aTransfer['action']		= 'pingCallback';

			}
			else if($_VARS['task'] == 'ping') {

				$this->_executePing((array)$_VARS['dialog'], $_VARS['unload']);

				$aTransfer['data']['value'] = 1;
				$aTransfer['action']		= 'pingCallback';

			}
		# ENDE #
		# START : Ping verarbeiten #
			else if($_VARS['task'] == 'autocomplete') {

				$sIconKey = self::getIconKey($_VARS['action'], $_VARS['additional']);

				if($this->aIconData[$sIconKey]){
					$oDialog = $this->aIconData[$sIconKey]['dialog_data'];
				}

				if($oDialog) {

					foreach((array)$oDialog->aSaveData as $aOption) {

						if(
							$aOption['db_column'] == $_VARS['db_column'] &&
							$aOption['db_alias'] == $_VARS['db_alias'] &&
							$aOption['element'] == 'autocomplete'
						) {

							$oOptions = $aOption['autocomplete'];

							if($oOptions instanceof Ext_Gui2_View_Autocomplete_Abstract) {
								$oOptions->printOptions($_VARS['search'], $_VARS['id'], $aOption);
							}

							break;
						}
					}

				}

				die();

			} elseif($_VARS['task']=='openMultiplePdf') {
				$mMultiplePdfClass = $this->_oGui->multiple_pdf_class;
				if (is_string($mMultiplePdfClass) && !empty($mMultiplePdfClass)) {
					$mMultiplePdfClass = new $mMultiplePdfClass($this);
				}
				if(is_object($mMultiplePdfClass) && $mMultiplePdfClass instanceof Ext_Gui2_Pdf_Abstract){
					$this->_exportMultiplePdf($_VARS['id'], $mMultiplePdfClass);
				}else{
					$aTransfer['action'] = 'showError';
					$aTransfer['error'] = array(L10N::t('Keine PDF Klasse gefunden',Ext_Gui2::$sAllGuiListL10N));
				}
			} elseif($_VARS['task'] === 'contextMenu') {
				$aTransfer['action'] = 'contextMenuCallback';
			} else if($_VARS['task'] == 'startSimilarityWDSearch'){

				$aErrors		= array();
				$aSearchResult	= array();
				$sSearchString	= $_VARS['search'];

				try {
					$aSearchResult = $this->getSimilarityWDSearch($sSearchString);
				} catch (Exception $exc) {
					$aErrors[] = L10N::t('Die Suche konnte nicht erfolgreich durchgeführt werden!',Ext_Gui2::$sAllGuiListL10N);
				}

				$aTransfer['action']				= 'similarityWDSearchCallback';
				$aTransfer['data']['similarity']	= $aSearchResult;
				$aTransfer['data']['id']			= $_VARS['element'];
				$aTransfer['error']					= $aErrors;
			}
			# START : Accordion Ajax #
			// Wurde nur für einen einzigen Fall in TA verwendet
			/*else if($_VARS['task'] == 'loadAccordionData') {
				
				$aErrors = array();

				try {
					$oRequest = new \MVC_Request();
					$oRequest->add($_VARS);

					$aTransfer['data'] = $this->loadAccordionElementData($oRequest->get('type'), $oRequest);
					
				} catch (Exception $e) {
					$aErrors[] = L10N::t('Es ist ein Fehler aufgetreten');
					$oDialog = new Ext_Gui2_Dialog(); 
					$oNotification = $oDialog->createNotification(L10N::t('Fehler'), L10N::t('Beim Laden der Informationen ist ein Fehler aufgetreten'));
					$aTransfer['data']['html'] = $oNotification->generateHTML();
					__pout($e);
				}

				$aTransfer['action'] = 'loadAccordionDataCallback';
				$aTransfer['data']['type'] = $_VARS['type'];
				$aTransfer['data']['element'] = $_VARS['container'];
				$aTransfer['data']['error']	= $aErrors;
				
			}*/
			# ENDE #
			else {
				
				$sMethodName = $_VARS['task'].\Util::convertHyphenLowerCaseToPascalCase(ucfirst($_VARS['action']));
				
				// TODO Requests hierüber sind doch total intransparent
				if(method_exists($this, $sMethodName)) {
					
					$aTransfer = $this->$sMethodName($_VARS);
					
				}

			}
			# ENDE #

		} else {

			$aTransfer = array();

			#if($_VARS['dialog_id']) {
				#$aTransfer['data']['id']	= $_VARS['dialog_id'];
			#}

			$aTransfer['action']		= 'showError';
			$aTransfer['error']			= array(L10N::t('Der Dialog wurde bereits gespeichert.', $this->_oGui->gui_description));

		}

		if(
			!empty($_VARS['id']) &&
			empty($aTransfer['data']['selectedRows'])
		) {
			$aTransfer['data']['selectedRows'] = $_VARS['id'];
		}

		if($this->bDebugmode){
			$iDebugEndeTotal = Util::getMicrotime();
			$iDebugNowTotal = $iDebugEndeTotal - $iDebugStartTotal;
			__pout('DEBUG ENDE # Dauer gesamt switchAjaxRequest # '.$iDebugNowTotal);
		}
	
		$sDialogId = null;
		if(
			isset($aTransfer['data']) &&
			isset($aTransfer['data']['id'])
		) {
			$sDialogId = $aTransfer['data']['id'];
		} elseif(
			isset($_VARS['dialog_id'])
		) {
			$sDialogId = $_VARS['dialog_id'];
		}

		if(
			$sDialogId !== null
		) {
			// Nur beim speichern validieren
			// da wir sonst bei reloadtab etc evt die fehlermeldung bekommen wenn der debugmode aus ist!
			if(
				$_VARS['task'] == 'saveDialog'||
				$_VARS['task'] == 'openDialog'
			){
				$sTransactionToken = $this->_generateToken($sDialogId);
				$aTransfer['token'] = $sTransactionToken;
			}
			$aTransfer['dialog_id'] = $sDialogId;
		}

		if(!empty($this->aAlertMessages)) {
			$aTransfer['alert_messages'] = $this->aAlertMessages;
		}

		return $aTransfer;

	}

	protected function loadAccordionElementData($sType, \MVC_Request $oRequest) {
        return array();
    }
	
	protected function requestFilemanager($aVars) {
		
		$oEntity = $this->_getWDBasicObject($aVars['id']);
		
		$oDialog = new Ext_Gui2_Dialog();
		$oDialog->save_button = false;
		$oDialog->sDialogIDTag = 'FILEMANAGER_';
		$oDialog->disableInfoIcons();
		
		$oIframe = new Ext_Gui2_Html_Iframe();
		$oIframe->src = '/wdmvc/file-manager/interface/view?entity='.get_class($oEntity).'&id='.(int)$oEntity->id;
		$oIframe->style = 'width: 100%; height: 100%; border: 0;';

		$oDialog->setElement($oIframe);
		
		$aTransfer = [];
		$aTransfer['data'] = $oDialog->getDataObject()->getHtml($aVars['action'], $aVars['id'], $aVars['additional']);
		
		$aTransfer['data']['title'] = str_replace('{name}', $oEntity->getName(), $this->t('Dateiverwaltung für "{name}"'));
		
		$aTransfer['data']['no_scrolling'] = true;
		$aTransfer['data']['no_padding'] = true;
		$aTransfer['data']['full_height'] = true;
		
		$aTransfer['action'] = 'openDialog';
		
		return $aTransfer;
	}

	protected function requestNotices($aVars) {

		$oEntity = $this->_getWDBasicObject($aVars['id']);
		
		$oDialog = new Ext_Gui2_Dialog();
		$oDialog->save_button = false;
		$oDialog->sDialogIDTag = 'NOTICES_';
		$oDialog->disableInfoIcons();
		
		$oIframe = new Ext_Gui2_Html_Iframe();
		$oIframe->src = '/notices/interface/view?entity='.get_class($oEntity).'&id='.(int)$oEntity->id;
		$oIframe->style = 'width: 100%; height: 100%; border: 0;';
		$oIframe->class = 'rounded-md';

		$oDialog->setElement($oIframe);
		
		$aTransfer = [];
		$aTransfer['data'] = $oDialog->getDataObject()->getHtml($aVars['action'], $aVars['id'], $aVars['additional'] ?? null);

		if($oEntity->id > 0) {
			$aTransfer['data']['title'] = str_replace('{name}', $oEntity->getName(), $this->t('Notizen für "{name}"'));
		} else {
			$aTransfer['data']['title'] = $this->t('Notizen');
		}

		$aTransfer['data']['no_scrolling'] = true;
		$aTransfer['data']['no_padding'] = true;
		$aTransfer['data']['full_height'] = true;
		
		$aTransfer['action'] = 'openDialog';
		
		return $aTransfer;
	}

	protected function requestDialogInfoIconValues($aVars) {
		
		$bWithPrivate = \Core\Handler\SessionHandler::getInstance()->get('system_infotexts_mode') === true;
		$sLanguage = \System::getInterfaceLanguage();

		$sCacheKey = 'gui2_info_text_'.implode('_', [
			'gui_hash' => $this->_oGui->hash,
			'dialog_id' => (string) $aVars['dialog_suffix'],
			'language' => $sLanguage,
			'private' => (int) $bWithPrivate
		]);

		$aResponseData = Core\Facade\Cache::get($sCacheKey);

		if(is_null($aResponseData)) {	

			$aInfoTexts = \Gui2\Entity\InfoText::getRepository()
					->findLanguageValuesForGuiDialog($this->_oGui, $aVars['dialog_suffix'], $sLanguage, $bWithPrivate);

			$oMessageBag = new Gui2\DTO\InfoIconMessageBag($this->_oGui, $aVars['dialog_suffix'], $sLanguage);

			foreach($aInfoTexts as $aText) {
				$oMessageBag->addText($aText['field'], $aText['value'], $aText['private']);
			}

			$this->fillDialogInfoIconMessageBag($oMessageBag);

			Core\Facade\Cache::put($sCacheKey, (60*60*24*7), $aResponseData = $oMessageBag->toArray(), 'gui2_info_texts');
		}

		$aTransfer = [];
		$aTransfer['action'] = 'requestDialogInfoIconValuesCallback';
		$aTransfer['dialog_suffix'] = $aVars['dialog_suffix']; 
		$aTransfer['dialog_id'] = $aVars['dialog_id'];
		$aTransfer['data'] = $aResponseData;
		
		return $aTransfer;
	}
	
	/**
	 * Funktion, die von _switchAjaxRequest createCopy ausgeführt wird
	 * @param array $aVars
	 * @return array
	 */
	protected function _executeCreateCopy(array $aVars) {
		$aReturn = array(
			'copies' => array(),
			'options' => array(),
			'mapping' => array()
		);

		// Optionen für den Button unserialisieren
		$aOptions = unserialize($aVars['options_serialized']);
		if(is_array($aOptions)) {
			$aReturn['options'] = $aOptions;
		}

		$oOldWDBasic = $this->oWDBasic;

		// Alle IDs durchlaufen und Kopien erzeugen
		foreach((array)$aVars['id'] as $iId) {
			$this->_getWDBasicObject((int)$iId);
			$aReturn['copies'][] = $this->oWDBasic->createCopy(null, null, $aOptions);
			$aReturn['mapping'][$this->oWDBasic->id] = $this->oWDBasic->getJoinedObjectCopyIds();
		}

		$this->oWDBasic = $oOldWDBasic;

		return $aReturn;
	}

	/**
	 * $aTable Baut aus dem Tabellen Array ein Array für den Export
	 */
	public function buildExportArray($aTable){
		$aExport = array();

		//HEADER
		$iRow = 0;
		foreach((array)$aTable['head'] as $aHeadColumn){
			$aExport[$iRow][] = $aHeadColumn['title'];
		}

		//BODY
		$iRow++;
		foreach((array)$aTable['body'] as $aBodyColumnData){
			foreach((array)$aBodyColumnData['items'] as $aBodyColumn){
				$aExport[$iRow][] = $aBodyColumn['text'];
			}
			$iRow++;
		}

		// SUM Row
		$aExport = array_merge($aExport, (array)$aTable['sum']);
		
		return $aExport;
	}

	/**
	 * Split a SQL String into all Parts
	 *
	 * @param string $sSql
	 * @return array
	 */
	public function splitSqlString($sSql) {

		$aSqlString = DB::splitQuery($sSql);

		return $aSqlString;

	}

	/**
	 * @param $oWDBasic
	 */
	public function setForeignKey(&$oWDBasic) {
		global $_VARS;

		// Wenn Objekt vorhanden und Parent ID gesetzt
		if(
			$oWDBasic instanceof WDBasic &&
			!empty($_VARS['parent_gui_id'])
		) {

			$_VARS['parent_gui_id'] = (array)$_VARS['parent_gui_id'];
			$iParentId = (int)reset($_VARS['parent_gui_id']);
			$aJoinTableData = array();
	
			// Wenn eine Jointable angegeben wurde als verbindung
			if($this->_oGui->foreign_jointable != "") {

				// Prüfen, ob es schon eine JoinTable gibt
				$mJoinTable = $oWDBasic->getJoinTable($this->_oGui->foreign_jointable);
		
				// Wenn es noch keine JoinTable gibt, dann eine dynamisch anlegen
				if($mJoinTable === false) {

					// JoinTable für die verknüpfung generieren
					$aJoinData = $this->_generateJoinData();
					$mJoinTable = $aJoinData;
					// jointabel ergänzen
					$oWDBasic->addJoinTable($aJoinData);
					$sJoin = 'auto_gui_jointable';
					// Leere Join Data setzten
					$aJoinTableData = array();

				} else {

					// evt wird das Kind für mehrere Eltern benutzt daher die bestehenden daten erweitern!
					$sJoin = $this->_oGui->foreign_jointable;
					$aJoinTableData = $oWDBasic->$sJoin;

				}

				// ID direkt in Array schreiben
				if(
					isset($mJoinTable['foreign_key_field']) &&
					!is_array($mJoinTable['foreign_key_field'])
				) {

					// Join Table mit der ID füllen
					$aJoinTableData[] = $iParentId;
					// unique damit aufjedenfall jedes Elternteil nur einmal vorhanden ist
					$aJoinTableData = array_unique($aJoinTableData);
				
				// ID in mehrdimensionales Array schreiben
				} else {
					
					$bParentIdExists = false;
					
					// Prüfen, ob Wert schon drin steht
					if(!empty($aJoinTableData)) {
						foreach($aJoinTableData as $aJoinTableItem) {
							
							if($aJoinTableItem[$this->_oGui->foreign_key] == $iParentId) {
								$bParentIdExists = true;
								break;
							}
							
						}							
					} 

					// Wenn die ID noch nicht existiert, dann reinschreiben
					if($bParentIdExists === false) {
						$aJoinTableData[] = array(
							$this->_oGui->foreign_key => $iParentId
						);
					}

				}
				
				// Definieren
				$oWDBasic->$sJoin = $aJoinTableData;

			// Fremdschlüssel setzen falls vorhanden, Wert übergeben und Schlüssel kein ID Feld ist
			} elseif(
				$this->_oGui->foreign_key &&
				$this->_oGui->foreign_key != 'id'
			) {

				if ($iParentId > 0) {

					// Definition kann ein String oder ein Array sein, immer in ein Array umwandeln
					$aParentPrimaryKeys = (array)$this->_oGui->parent_primary_key;
					$aForeignKeys = (array)$this->_oGui->foreign_key;

					// Die Anzahl der Primary-/Foreign-Keys muss immer gleich sein
					if (count($aParentPrimaryKeys) !== count($aForeignKeys)) {
						throw new \RuntimeException('Mismatch between Primary-Keys and Foreign-Keys.');
					}

					// Array aus Parent-Primary-Keys und Foreign-Keys generieren
					$aForeignKeyMapping = array_combine($aParentPrimaryKeys, $aForeignKeys);

					$oParentGui = $this->_oGui->getParentClass();

					// Alle Foreign-Keys im Joined-Object setzen
					foreach ($aForeignKeyMapping as $sParentPrimaryKey => $sSubForeignKey) {

						$iSubParentId = $iParentId;

						/**
						 * Vom Elternobjekt soll nicht das Feld "id" gesetzt werden, sondern ein kodierter Wert oder
						 * ein Fremdschlüssel
						 */
						if ($this->_oGui->decode_parent_primary_key) {

							$iSubParentId	= $oParentGui->decodeId($iParentId, $sParentPrimaryKey);

						} else if (
							$sParentPrimaryKey != "" &&
							$sParentPrimaryKey != "id" &&
							$sParentPrimaryKey != $oParentGui->query_id_column
						) {
							// Es wird nur die ID vom Elternelement übergeben. Um den Fremdschlüssel zu holen muss das Objekt instanziert werden
							$oParentWDBasic = call_user_func(array($oParentGui->class_wdbasic, 'getInstance'), $iParentId);
							$iSubParentId = (int)$oParentWDBasic->$sParentPrimaryKey;
						}

						$oWDBasic->getJoinedObject($this->_oGui->foreign_key_alias)->$sSubForeignKey = $iSubParentId;
						
					}

				}

			}

		}

	}

	/**
	 * @param string $sDialog
	 * @param array $aSelectedIds
	 * @return array|bool
	 */
	public function checkDialogLock($sDialog, $aSelectedIds) {
		global $user_data;

		if(empty($aSelectedIds)) {
			return true;
		}

		if(isset($_SESSION['gui2_dialog_lock_break']) && $_SESSION['gui2_dialog_lock_break'] === true) {
			return true;
		}

		if(System::d('debugmode') == 2) {
			return true;
		}
		
		$sSql = "
			SELECT
				gdl.*,
				su.firstname,
				su.lastname
			FROM
				`gui2_dialog_lock` gdl LEFT OUTER JOIN
				`system_user` su ON
					gdl.user_id = su.id
			WHERE
				`gdl`.`hash` = :hash AND
				`gdl`.`dialog_id` = :dialog_id AND
				(
					(
						`gdl`.`user_id` = :user_id AND
						`gdl`.`instance_hash` != :instance_hash
					) OR
					(
						`gdl`.`user_id` != :user_id
					)
				) AND
				`gdl`.`valid_until` > :now
		";

		$aLock = DB::getQueryRow($sSql, array(
			'dialog_id' => $sDialog,
			'user_id' => (int)$user_data['id'],
			'hash' => $this->_oGui->hash,
			'instance_hash' => $this->_oGui->instance_hash,
			'now' => date('Y-m-d H:i:s') // Für den MySQL Cacher
		));

		if($this->bDebugmode) {
			__pout($aLock);
		}

		if(empty($aLock)) {
			return true;
		} else {
			return $aLock;
		}
	}

	protected function _getDialogId($oDialog, $aSelectedIds) {

		$sDialogIDTag = 'ID_';

		if(isset($oDialog->sDialogIDTag)) {
			$sDialogIDTag = $oDialog->sDialogIDTag;
		}

		if(!empty($aSelectedIds)){
			$sDialogId = $sDialogIDTag.implode('_', (array)$aSelectedIds);
		} else {
			$sDialogId = $sDialogIDTag.'0';
		}

		return $sDialogId;

	}

	/**
	 * Redundanz mit \Ext_Gui2_Dialog_Data::_getWDBasicObject?
	 *
	 * @see \Ext_Gui2_Dialog_Data::_getWDBasicObject()
	 */
	protected function _getWDBasicObject($aSelectedIds) {

		if($this->_oGui->encode_data_id_field != false) {
			$sIdField = $this->_oGui->encode_data_id_field;
		} else {
			$sIdField = $this->_oGui->query_id_column;
		}

		$aSelectedIds	= (array)$this->_oGui->decodeId($aSelectedIds, $sIdField);
		$iSelectedId	= reset($aSelectedIds);

		if(
			is_numeric($iSelectedId) ||
			empty($iSelectedId)
		) {
			$iSelectedId = (int)$iSelectedId;
		}

		// Es soll immer eine neue Instanz erzeugt werden AUßER, wenn es sich um die ID == 0 handelt,
		// es bereits eine Klasse gibt und diese gleich der existierenden WDBasic ist
		// Das ist wichtig, da sonst alle bereits bereits existierenden Werte gelöscht werden würden z.B.
		// gespeicherte Joined Tables,..
		if(
			!(
				!is_null($this->oWDBasic) &&
				$iSelectedId == 0 &&
				get_class($this->oWDBasic) == $this->_oGui->class_wdbasic
			)
		) {
			$this->oWDBasic = call_user_func(array($this->_oGui->class_wdbasic, 'getInstance'), $iSelectedId);

			if(
				$iSelectedId != 0 &&
				!$this->oWDBasic->exist()
			) {
				// Nicht aktuelle Elasticsearch-Listen könnten hier reinlaufen
				throw new InvalidArgumentException('WDBasic '.$this->_oGui->class_wdbasic.'::'.$iSelectedId.' does not exist!');
			}
		}

		$this->setForeignKey($this->oWDBasic);

		return $this->oWDBasic;

	}

	/**
	 *
	 * sparen wir uns das ständige reset/typecasting
	 * @return <int>
	 */
	protected function _getFirstSelectedId(){
		global $_VARS;

		$aSelectedIds	= (array)$_VARS['id'];
		$iSelectedId	= (int)reset($aSelectedIds);

		return $iSelectedId;
	}

	/**
	 *
	 * @return Ext_Gui2
	 */
	protected function _getParentGui(){
		return $this->_oGui->getParentClass();
	}

	/**
	 *
	 * @param <int> $iDataId
	 * @return WDBasic
	 */
	protected function _getParentWDBasic($iDataId = null){
		if($iDataId == null){
			global $_VARS;
			$_VARS['parent_gui_id'] = (array)$_VARS['parent_gui_id'];
			$iDataId = reset($_VARS['parent_gui_id']);
		}
		$oParentGuiObject	= $this->_getParentGui();
		if(is_object($oParentGuiObject) && $oParentGuiObject instanceof Ext_Gui2){
			return $oParentGuiObject->getWDBasic($iDataId);
		}

		return false;
	}

	protected function _getParentDataObject(){
		$oParentGuiObject	= $this->_getParentGui();
		if(is_object($oParentGuiObject) && $oParentGuiObject instanceof Ext_Gui2){
			$oData = $oParentGuiObject->getDataObject();
			return $oData;
		}

		return false;
	}
	
	protected function _generateToken($sDialogId) {
		
		$sToken = md5(uniqid());
		$this->aDialogToken[$sDialogId] = array(
			'token' => $sToken,
			'used'	=> false
		);

		return $sToken;

	}
	
	protected function _checkDialogToken($sDialogId, $sToken) {

		$bCheck = false;

		$iDebugMode = System::d('debugmode');

		if($iDebugMode == 2) {
			return true;
		}

		if(
			array_key_exists($sDialogId, $this->aDialogToken) &&
			$this->aDialogToken[$sDialogId]['token'] == $sToken &&
			$this->aDialogToken[$sDialogId]['used'] === false
		){
			$bCheck = true;
			$this->aDialogToken[$sDialogId]['token'] = 'CHECKED';
			$this->aDialogToken[$sDialogId]['used'] = true;
		}
		
		return $bCheck;
	}
	
	/**
	 * Wrapper für Dialog Data, da protected ganz toll ist.
	 * 
	 * @see Ext_Gui2_Dialog_Data
	 */
	public function getSaveAsNewOption() 
	{
		return $this->_mSaveAsNew;
	}
	
	/**
	 * Wrapper für Dialog Data, da protected ganz toll ist.
	 * 
	 * @see Ext_Gui2_Dialog_Data
	 */
	/*public function setSaveAsNewOption($mOption)
	{
		$this->_mSaveAsNew = $mOption;
	}*/
	
	/**
	 * Wrapper für Dialog Data, da protected ganz toll ist.
	 * 
	 * @see Ext_Gui2_Dialog_Data
	 */
	public function getOpenNewDialogOption()
	{
		return $this->_bOpenNewDialog;
	}
	
	/**
	 * Wrapper für Dialog Data, da protected ganz toll ist.
	 * 
	 * @see Ext_Gui2_Dialog_Data
	 */
	public function setOpenNewDialogOption($mOption)
	{
		$this->_bOpenNewDialog = $mOption;
	}

	public function writeWDSearchIndexChange($sField, $sValue, $sStatus = 'changed'){
		self::writeGUIWDSearchIndexChange($this->_oGui->hash, $sField, $sValue, $sStatus);
	}

	public static function writeGUIWDSearchIndexChange($sHash, $mField, $sValue, $sStatus = 'changed') {

		if(Util::checkPHP53()){
			$aLanguages = call_user_func(array('static', 'getInterfaceLanguages'));
		} else {
			$aLanguages = self::getInterfaceLanguages();
		}

		foreach((array)$mField as $sField) {

			foreach($aLanguages as $sIso => $sLanguage){

				$aKeys = array(
					'hash'			=> (string)$sHash,
					'field'			=> (string)$sField,
					'value'			=> (string)$sValue,
					'language_iso'	=> (string)$sIso
				);

				$aData = array(
					array(
						'status' => (string)$sStatus
					)
				);

				DB::updateJoinData(
					'gui2_wdsearch_index_changes',
					$aKeys,
					$aData
				);

			}
		}
		
	}
	
	public function deletedWDSearchIndexChanges($sStatus = ''){
		$sLang = (string)$this->getInterfaceLanguage();
		$sSql = " DELETE FROM `gui2_wdsearch_index_changes` WHERE `hash` = :hash AND `language_iso` = :language_iso";
		$aSql = array('hash' => $this->_oGui->hash, 'language_iso' => $sLang);
		if(!empty($sStatus)){
			$sSql .= ' AND `status` = :status';
			$aSql['status'] = $sStatus;
		}

		DB::executePreparedQuery($sSql, $aSql);
	}
    
    public function getWDSearchChangedDocumentIds($sStatus = ''){

		$aResults = $this->getWDSearchChangedDocuments($sStatus);

		$aBack = array();

		foreach($aResults as $aHit){
			$aBack[] = $aHit['_id'];
		}

		return $aBack;
	}
    
    public function getWDSearchIndexChanges($sStatus = ''){

		$sLang = (string)$this->getInterfaceLanguage();

		$aKeys = array(
			'hash' => (string)$this->_oGui->hash,
			'language_iso' => $sLang
		);
		
		if(!empty($sStatus)){
			$aKeys['status'] = $sStatus;
		}

		$aChanges = (array)DB::getJoinData('gui2_wdsearch_index_changes', $aKeys);
	
		return $aChanges;

	}

	public function getWDSearchChangedDocuments($sStatus = ''){
		// ALLE Löschen
		$aStatus	= $this->getWDSearchIndexChanges($sStatus);
	
		$aBack		= array();
		$aIdCheck	= array();
		if(!empty($aStatus)){

			$sIndexName = $this->getWDSearchIndexName();
			$sTypeName	= $this->getWDSearchIndexTypeName();

			foreach($aStatus as $aData){
				$oSearch	= new \ElasticaAdapter\Facade\Elastica($sIndexName, $sTypeName);
				if($aData['field'] == '_uid'){
					$oSearch->addUIDQuery((string)$aData['value']);
				} else {
					$oSearch->addFieldQuery((string)$aData['field'], (string)$aData['value']);
				}
				$oSearch->setLimit(500000);
				$aResult = $oSearch->search();
	
				foreach($aResult['hits'] as $aHit){
					if(in_array($aHit['_id'], $aIdCheck)){
						continue;
					}
					$aBack[] = $aHit;
					$aIdCheck[] = $aHit['_id'];
				}

				if(
					$aData['field'] == '_uid' &&
					empty($aResult['hits'])
				){
					$sIdField = $this->_oGui->query_id_column;
					$aBack[] = array('_id' => $aData['value'], 'fields' => array($sIdField => $aData['value']));
				}
			}
		}

		return $aBack;
	}

	public function getWDSearchResult(array $aFilter, $oSearch, $aDataColumns, $aSearchColumns){

		if(empty($aDataColumns)){
			$aDataColumns = array('*');
		}
		
		if(empty($aSearchColumns)){
			$aSearchColumns = array('*');
		}

		$oSearch->setFields($aDataColumns, $aSearchColumns);

		$this->_setWDSearchFilterQueries($aFilter, $oSearch, $aDataColumns, $aSearchColumns);

		$aResult = $oSearch->search();

		$this->_oGui->setDebugPart('WDSearch ausgeführt', $oSearch);
		
		return $aResult;
	}
    
    public function addWDSearchIDFilter(\ElasticaAdapter\Facade\Elastica $oSearch, array $aSelectedIds, $sIdField)
	{
		$iSelectedIdCount	= count($aSelectedIds);
		$iSelectedId		= reset($aSelectedIds);

		// Wenn IDs angeklickt sind müssen diese als Filter eingebaut werden
		// und zwar als "Should" da pro eintrag nur 1 ID zutreffen kann
		if($iSelectedIdCount > 1){
			$oBool = new \Elastica\Query\BoolQuery();
			foreach((array)$aSelectedIds as $iSelectedId){
				$oQuery = $oSearch->getFieldQuery($sIdField, (string)$iSelectedId);
				$oBool->addShould($oQuery);
			}
			$oSearch->addMustQuery($oBool, array($sIdField));
			//$oSearch->setMinimunNumberShouldMatch($iSelectedIdCount);
		} else if($iSelectedIdCount == 1){
			$oQuery = $oSearch->getFieldQuery($sIdField, (string)$iSelectedId);
			$oSearch->addMustQuery($oQuery, array($sIdField));
		}
	}

	/**
	 * Gibt den Titel der Spalte zurück
	 * Dieser wird in der Autoverfolständigung grau hinterlegt angezeigt um zu wissen wo der Begriff gefunden wurde
	 */
	public function getWDSearchFieldTitle($sField){
		$aColumns = $this->_oGui->getColumnList();

		foreach($aColumns as $oColumn){
			$sSelectColumn = $oColumn->select_column;
			if(empty($sSelectColumn)){
				$sSelectColumn = $oColumn->db_column;
			}

			if($sSelectColumn == $sField){
				return $oColumn->title;
			}
		}

		return '';
	}

	/**
	 * Alle Spalten die für die Suche benutzt werden
	 * @return array
	 */
	public function getWDSearchSearchColumns(){

		$aColumns = $this->_oGui->getColumnList();

		$aSearchColumns = array();

		foreach($aColumns as $oColumn){

			if(
				$oColumn->sortable == 1 &&
				$oColumn->searchable == 1
			){

				$sSelectColumn = $oColumn->select_column;
				if(empty($sSelectColumn)){
					$sSelectColumn = $oColumn->db_column;
				}
				$aSearchColumns[] = $sSelectColumn.'_original';

			}

		}

		$aSearchColumns = array_unique($aSearchColumns);

		return $aSearchColumns;
	}

	/**
	 * array( FIELD => MAPPING ARRAY )
	 * @return type
	 */
	public function getWDSearchAdditionalDataColumns($bForMapping = false){
		return array();
	}

	// Nötig da wir auf V5 keine entsprechende Methode haben die wir ableiten können
	public static function getInterfaceLanguages(){
		$aLanguages = (array)Util::getLanguages('backend');
		return $aLanguages;
	}

	public function getInterfaceLanguage(){

		$sLanguage = System::getInterfaceLanguage();

		return $sLanguage;

	}

	public function getWDSearchIndexName($sLanguage = ''){
		if(empty($sLanguage)){
			$sLanguage	= $this->getInterfaceLanguage();
		}
		$sHash		= $this->_oGui->hash;
		$sLicense	= System::d('license');
		$sIndex		= md5($sLicense.$sLanguage.$sHash);

		return $sIndex;
	}

	public function getWDSearchIndexTypeName($sLanguage = ''){
		return $this->getWDSearchIndexName($sLanguage);
	}

	/**
	 * start the search for similitary Entries for the given String
	 * but only for the last part of the string ( Spacer => , )
	 * @param string $sSearchString
	 * @return type
	 */
	public function getSimilarityWDSearch($sSearchString){
		global $_VARS;
		
		$aBack = array();
		$sElementField = '';
		$sElementID = $_VARS['element'];

		if(!empty($sElementID)){
			$sElementField = str_replace('wdsearch_'.$this->_oGui->hash.'_', '', $sElementID);
			// oberer Syntax wurde nicht gefunden = kein eindeutiges feld
			if(strpos($sElementField, 'wdsearch_') !== false){
				$sElementField = '';
			}
		}

		if(!empty($sSearchString)){
			
			if(substr($sSearchString, -1) != '*'){
				$sSearchString = $sSearchString.'*';
			}
			// Felder suchen wo etwas gefunden wurde, hierfür muss das aktuelle "Wort" betrachtet werden
			// und die einzelnen Felder der funde durchgegangen werden
			$aCurrentSearchWord = (array)preg_split('/,|OR/', $sSearchString);
			$sCurrentSearchWord = end($aCurrentSearchWord);
			$sCurrentSearchWord = \ElasticaAdapter\Facade\Elastica::escapeTerm($sCurrentSearchWord);

			// Passende Eintr�e suchen
			if(empty($sElementField)){
				$aSearchColumns = $this->getWDSearchSearchColumns();
			} else {
				$aSearchColumns = array($sElementField);
			}

			$sIndexName = $this->getWDSearchIndexName();
			$sIndexTypeName = $this->getWDSearchIndexTypeName();

			$oSearch = new \ElasticaAdapter\Facade\Elastica($sIndexName, $sIndexTypeName);
			$oSearch->setLimit(10);
			$oSearch->setHighlight();
			$oSearch->setFields(array('*'), $aSearchColumns);
			$oSearch->addMustQuery($sCurrentSearchWord, $aSearchColumns);
			$this->_getSimilarityWDSearchHook($oSearch, $sCurrentSearchWord, $aSearchColumns);
			$aResult = $oSearch->search();
			$iCount = 1;

			$aFieldValueCheck = array();

			foreach((array)$aResult['hits'] as $aHit){

				// Wenn makierungen gesetzt sind wissen wir genau wo was gefunden wurde
				if(!empty($aHit['highlight'])){

					foreach((array)$aHit['highlight'] as $sField => $sHighlightValue){

						$aTemp	= explode('{#}', $sHighlightValue[0]);

						// Seit Elasticsearch 1.0 sind alle Felder im Index Arrays, daher reset()
						$mValue = reset($aHit['fields'][$sField]);

						if(!empty($aTemp)){
							$mValue = explode('{#}', $mValue);
							$iKey	= (int)array_search('<em>', $aTemp);
							$sValue = (string)$mValue[$iKey];
						} else {
							$sValue = $mValue;
						}

						if(!in_array($sValue, $aFieldValueCheck)){
							$aFieldValueCheck[] = $sValue;
							$aBack[] = array(
									'field' => $sField,
									'field_name' => $this->getWDSearchFieldTitle($sField),
									'value' => $sValue,
									'score' => $aHit['_score']
								);
						}

					}

				//  Ansonsten müssen wir leider nochmal alle ergebnisse jedes Feld durchsuchen um zu wissen wo es steht
				} else {

					foreach($aHit['fields'] as $sField => $sFieldValue){
						if(strpos($sFieldValue, $sCurrentSearchWord) !== false){
							if(!in_array($sFieldValue, $aFieldValueCheck)){
								$aFieldValueCheck[] = $sFieldValue;
								$aBack[] = array(
									'field' => $sField,
									'field_name' => $this->getWDSearchFieldTitle($sField),
									'value' => $sFieldValue,
									'score' => $aHit['_score']
								);
							}
						}

					}
				}

			}

		}

		return $aBack;
	}

	protected function _getSimilarityWDSearchHook($oSearch, $sCurrentSearchWord, $aSearchColumns){

	}

	protected function _executePing($aDialogs, $bUnload) {
		global $user_data;

		self::lockDialogs($this->_oGui->hash, $this->_oGui->instance_hash, $user_data['id'], $aDialogs, true);

		Ext_Gui2_GarbageCollector::clean();

		// Bei Unload, GUI Instanz aus Session löschen und Dialoge entsperren
		if($bUnload) {

			self::lockDialogs($this->_oGui->hash, $this->_oGui->instance_hash, $user_data['id'], array(), true);

			Ext_Gui2_GarbageCollector::unsetInstance($this->_oGui->hash, $this->_oGui->instance_hash);

		}
		
	}

	/**
	 * Sperrt die übergebenen Dialoge
	 * 
	 * @param string $sHash
	 * @param string $sInstanceHash
	 * @param int $iUserId
	 * @param array $aDialogs
	 * @param boolean $bDelete 
	 */
	static public function lockDialogs($sHash, $sInstanceHash, $iUserId, array $aDialogs, $bDelete=false) {

		if($bDelete) {

			$sSql = "
					DELETE FROM
						`gui2_dialog_lock`
					WHERE
						`hash` = :hash AND
						(
							(
								`user_id` = :user_id AND
								`instance_hash` = :instance_hash
							) OR
							`valid_until` < NOW()
						)
					";
			$aSql = array();
			$aSql['hash'] = $sHash;
			$aSql['instance_hash'] = $sInstanceHash;
			$aSql['user_id'] = (int)$iUserId;
			DB::executePreparedQuery($sSql, $aSql);

		}

		$sValidUntil = date('Y-m-d H:i:s', time() + (2*60));

		foreach($aDialogs as $sDialog) {

			if(!preg_match("/_[1-9][0-9]?/", $sDialog)) {
				continue;
			}

			$aSql = array();
			$aSql['hash'] = $sHash;
			$aSql['instance_hash'] = $sInstanceHash;
			$aSql['dialog_id'] = $sDialog;
			$aSql['user_id'] = (int)$iUserId;
			$aSql['valid_until'] = $sValidUntil;

			$sSql = "
				REPLACE
					`gui2_dialog_lock`
				SET
					`hash` = :hash,
					`instance_hash` = :instance_hash,
					`dialog_id` = :dialog_id,
					`user_id` = :user_id,
					`valid_until` = :valid_until
					";
			DB::executePreparedQuery($sSql, $aSql);

		}
		
	}

	/**
	 * Alle Dialoge des Users entsperren
	 *
	 * @param int $iUserId
	 */
	public static function unlockUserDialogs($iUserId) {

		$sSql = "
			DELETE FROM
				`gui2_dialog_lock`
			WHERE
				`user_id` = :user_id
		";

		\DB::executePreparedQuery($sSql, ['user_id' => $iUserId]);

	}
	
	public function getTranslation($sKey)
	{
		$aTranslations = $this->getTranslationsCache($this->_oGui->gui_description);
		
		if(isset($aTranslations[$sKey]))
		{
			return $aTranslations[$sKey];
		}
		else
		{
			return false;
		}
	}
	
	protected function callPersister() {
		
		// Nur ausführen, wenn gespeichert werden soll
		if($this->_bCallPersister !== true) {
			return;
		}
		
		$mReturn = null;

		DB::begin('gui2_save_dialog');

		try {

			$oPersister = WDBasic_Persister::getInstance();
			$oPersister->save();

			DB::commit('gui2_save_dialog');

			$mReturn = true;

		} catch (Exception $oException) {

			if($this->bDebugmode) {
				__out($oException);
			}

			// Fehlermeldung anzeigen im Dialog
			$aTransfer = array();
			$aTransfer['action'] 	= 'showError';
			$aTransfer['error']	= array();
			$aTransfer['error'][] = $this->t('Es ist ein Fehler aufgetreten');
			$aTransfer['error'][] = $oException->getMessage();

			DB::rollback('gui2_save_dialog');

			$mReturn = $aTransfer;

		}

		return $mReturn;
	}

	/**
	 * Andere Data-Klasse erzeugen mit Daten dieser Klasse
	 *
	 * @param $sClass
	 * @return Ext_Gui2_Data
	 */
	public function createOtherGuiData($sClass) {

		$oData = new $sClass($this->_oGui); /** @var Ext_Gui2_Data $oData */
		$oData->aIconData = $this->aIconData;

		return $oData;

	}

	/**
	 * @return Ext_Gui2
	 */
	public function getGui() {
		return $this->_oGui;
	}
	
	/**
	 * @param string $dialogKey
	 * @return \Ext_Gui2_Dialog
	 */
	public function getDialogById($dialogKey) {
		
		foreach($this->aIconData as $dialog) {
			if($dialog['dialog_data'] instanceof Ext_Gui2_Dialog) {
				
				if($dialog['dialog_data']->sDialogIDTag === $dialogKey.'_') {
					return $dialog['dialog_data'];
				}
			}
		}
		
	}
	
	public function requestDialogSettings(array $vars) {
		
		$transfer = [];
		
		$dialog = $this->getDialogById($vars['dialog_key']);
		
		if(!$dialog instanceof \Ext_Gui2_Dialog) {
			$transfer['action'] = 'showError';
			$transfer['error'] = array($this->t('Es ist ein interner Fehler aufgetreten. Das Dialog-Objekt konnte nicht gefunden werden!'));
			return $transfer;
		}
		
		$isCustomisable = true;
		
		// Erstmal nur Dialoge mit Tab
		foreach($dialog->aElements as $element) {
			if(!$element instanceof \Ext_Gui2_Dialog_Tab) {
				$isCustomisable = false;
			}
		}
		
		if($isCustomisable === false) {
			$transfer['action'] = 'showError';
			$transfer['error'] = array($this->t('Dieser Dialog ist leider nicht anpassbar!'));
			return $transfer;
		}
		
		$customiser = new Gui2\Service\CustomiseDialog($dialog, $this->getGui());
		$customiseDialog = $customiser->getCustomiseDialog();
		
		$transfer['data'] = $customiseDialog->generateAjaxData([], $this->getGui()->hash);
		$transfer['action'] = 'openDialog';
		$transfer['data']['id'] = 'CUSTOMISE_DIALOG';
		
		return $transfer;
	}

	protected function isLockedGui() {

		if ($this->initialReadonly === null) {
			$this->initialReadonly = $this->_oGui->bReadOnly;
		}

		if ($this->initialReadonly === false) {
			if (
				$this->_oGui->multiple_selection_lock &&
				count($this->request->input('parent_gui_id', [])) > 1
			) {
				return true;
			} else {
				return false;
			}
		}

		return $this->initialReadonly;

	}

	public function executeGuiCreatedHook() {

	}
	
}
