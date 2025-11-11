<?php
/**
 *
 * generiert eine Excel Datei mit Transferbezogenen Informationen
 *
 *
 * @created 07.02.11
 *
 */
class Ext_Thebing_Pickup_Export  {

	/**
	 * @var Ext_Thebing_School
	 */
	protected $_oSchool = null;
	private $iCounter = 0;
	//Container für Transfer-Informationen
	private $aStudentData;
	private $aLabels; //Spalten Überschriften
	private $currentTransferPosition = ''; //indiziert start bzw. end Positionen

	private $oDate;
	private $oDateTime;

	private $sDescription;

    /**
    *
    * @param <type> $oSchool
    */
	public function __construct($oSchool, $sDescription) {

		$this->sDescription = $sDescription;
		$this->_oSchool = $oSchool;
        $this->iCounter = 2;

		$this->_setStudentData();
		$this->_setLabels();
		$this->_createExcelSheet("Transferliste","Transferlist","Transferliste");

		$this->oDate	 = new Ext_Thebing_Gui2_Format_Date();
		$this->oDateTime = new Ext_Thebing_Gui2_Format_Time();
	}

	/**
	 *   Excel-Datei anlegen ( Title, Beschreibung, etc)
	 */
	private function _createExcelSheet($sTitle='', $sSubject='', $sDescription = ''){

		$this->_oExcel = new PhpOffice\PhpSpreadsheet\Spreadsheet();
		$this->_oExcel->getProperties()->setTitle($sTitle)->setSubject($sSubject)->setDescription($sDescription);
		$this->_oExcel->setActiveSheetIndex(0);
		$this->_oExcel->getDefaultStyle()->getFont()->setName('Calibri');
		$this->_oExcel->getDefaultStyle()->getFont()->setSize(8);

		$i = 0;

		foreach($this->aLabels as $sLab){
			$this->_oExcel->getActiveSheet()->getColumnDimension(chr( ord("A")+$i ))->setWidth($sLab[1]);
			$this->_oExcel->getActiveSheet()->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i)."1", $sLab[0]);
			$i++;
		}
	}

	/**
	 *set lables and column width
	 * @param <type> $aLabels
	 */
	private function _setLabels(){

		$this->aLabels = array(
			0  => array(L10N::t('K.Nr.', $this->sDescription),			10),
			1  => array(L10N::t('Name', $this->sDescription),				20),
			2  => array(L10N::t('Vorname', $this->sDescription),			20),
			3  => array(L10N::t('Alter', $this->sDescription),				10),
			4  => array(L10N::t('Geschlecht', $this->sDescription),			10),
			5  => array(L10N::t('Anreisedatum', $this->sDescription),		20),
			6  => array(L10N::t('Ankunftszeit', $this->sDescription),		20),
			7  => array(L10N::t('Abholzeit', $this->sDescription),			20),
			8  => array(L10N::t('Fluggeselschaft', $this->sDescription),	20),
			9  => array(L10N::t('Transfer von', $this->sDescription),		30),
			10 => array(L10N::t('Transfer zur', $this->sDescription),		30),
			11 => array(L10N::t('Unterkunft-Start', $this->sDescription),	40),
			12 => array(L10N::t('Unterkunft-Ziel', $this->sDescription),	40),
			13 => array(L10N::t('Transfer Kommentar', $this->sDescription),	40)
		  );
	}

	/**
	 * Attribute die in der Excel-Datei gespeichert werden
	 */
	private function _setStudentData(){

		$this->aStudentData = array(
				'customerId'    => '',
				'lastname'	=> '',
				'firstname'	=> '',
				'age'		=> '',
				'gender'	=> '',
				'date'		=> '',
				'time'		=> '',
				'pickup'	=> '',
				'airline'	=> '',
				'from'		=> '',
				'to'		=> '',
				'accommodation'  => array('accommodation' => '' , 'address' => '','plz'=>'' ,'city'=> '','phoneNo' => ''),
				'accommodation2' => array('accommodation' => '',  'address' => '','plz'=>'','city' => '','phoneNo' => '')
			);
	}

	/**
     *  Datensätze in der Excel-Tabelle aufnehmen
	*/
	private function _loadDataIntoSheet(){

		$i = 0;
		$this->iCounter++;

		foreach($this->aStudentData as $mCol){

			$sCol = '';
			if(is_array($mCol)) {

				// Leere Einträge überspringen
				$aNewCol = array();
				$bIsEmpty = true;
				foreach($mCol as $sKey => $mData) {
					if(!empty($mData)) {
						$aNewCol[$sKey] = $mData;
					}
				}

				if(!$bIsEmpty) {
					$sCol = implode(', ', $aNewCol);
				}

			} else {
				$sCol = $mCol;
			}

			$this->_oExcel->getActiveSheet()->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i).$this->iCounter, $sCol);
			$i++;
        }

		$this->_resetStudentData();
	}

	/**
	 * Speichern des Dokuments (Dateiname mit fortlaufender ID generieren)
	 *
	 * @return string
	 */
	public function save() {

		// Letzte ID holen
		$sKey = 'transfer_students_file_'.$this->_oSchool->id;
		$sSql = "
			SELECT
				`value`
			FROM
				`kolumbus_config`
			WHERE
				`key` = :key AND
				`active` = 1
			LIMIT 1
				";
		$aSql = array('key'=>$sKey);
		$iCounter = DB::getQueryOne($sSql, $aSql);

		if(!empty($iCounter)) {
			$iCounter = (int)$iCounter + 1;
		} else {
			$iCounter = 1;
		}

		$sName = 'transfer_students_'.date('Ymd_His').'_'.$iCounter.'.xls';
		$sPath = $this->_oSchool->getSchoolFileDir().'/exports/'.date('Y').'/'.date('m').'/';

		$bCheck = Util::checkDir($sPath);

		if(!$bCheck) {
			return false;
		}

		$sFile = $sPath.$sName;

		$oWriter = new PhpOffice\PhpSpreadsheet\Writer\Xls($this->_oExcel);
		$oWriter->save($sFile);

		if(is_file($sFile)) {
			return $sFile;
		} else {
			return false;
		}
	}

	/**
	 * new approach
	 *
	 * @param <Ext_Thebing_Inquiry_Transfer> $oObject
	 */
	public function loadStudentData($oObject){

		if(!($oObject instanceof Ext_TS_Inquiry_Journey_Transfer)) {
			throw new RuntimeException('Wrong object of type "'.get_class($oObject).'"!');
		}

		$aAccommodation = $oObject->getInquiry()->getFirstLastMatchedAccommodation();

		$sStartType   = $oObject->start_type;  //start transfer  (accommodation, location, school)
		$iStartDataId = $oObject->start;	   // id des Objekts

		$sEndType	  = $oObject->end_type;    //end transfer  (accommodation, location, school)  (start_additional & end_additional ?)
		$iEndDataId	  = $oObject->end;

		$sTransferType = $oObject->transfer_type; // 1=> arrival, 2=> departure

		$this->_resetStudentData();
		$this->_loadPersonalUserData($oObject);


		//*****************************************************************//
		//**************	START TRANSFER		***************************//
		//*****************************************************************//
		if(!empty($sStartType)){

			$this->currentTransferPosition = 'start';

			if($sStartType == 'accommodation'){ //falls es sich um eine Unterkunft handelt die letzte holen, egal wieviel dazwischen liegen!

				if($iStartDataId == 0){
					if($sTransferType == 1){ //arrival
						$this->_setAccommodationData($oObject, $aAccommodation['first']);
					}else if($sTransferType == 2){ //departure
						$this->_setAccommodationData($oObject, $aAccommodation['last']);
					}else{ //individual
						$this->_setAccommodationData($oObject, $aAccommodation['first']);
					}
				}else{
					$this->_setAccommodationData($oObject, Ext_Thebing_Accommodation::getInstance($iStartDataId));
				}
			}else if($sStartType == 'location'){
				$this->_setLocationData($oObject);
			}else if($sStartType == 'school'){
				$this->_setSchoolData($oObject);
			}else{
				//not defined yet!
			}
		}

		//*****************************************************************//
		//**************	END TRANSFER		***************************//
		//*****************************************************************//
		if(!empty($sEndType)){

			$this->currentTransferPosition = 'end';

			if($sEndType == 'accommodation'){

				if($iEndDataId == 0){
					if($sTransferType == 1){ //arrival
						$this->_setAccommodationData($oObject, $aAccommodation['last']);
					}else if($sTransferType == 2){ //departure
						$this->_setAccommodationData($oObject, $aAccommodation['first']);
					}else{ //individual
						$this->_setAccommodationData($oObject, $aAccommodation['last']);
					}
				}else{
					$this->_setAccommodationData($oObject, Ext_Thebing_Accommodation::getInstance($iEndDataId));
				}
			}else if($sEndType == 'location'){
				$this->_setLocationData($oObject);
			}else if($sEndType == 'school'){
				$this->_setSchoolData($oObject);
			}else{
				//not defined yet!
			}
		}

		$this->_loadDataIntoSheet();  //der Inhalt des Arrays wird in der Excel Datei gespeichert

		return 0;

	}

	/**
	 * Kunden/Studenteninformationen aufnehmen
	 *
	 * @param <Ext_Thebing_Inquiry_Transfer> $oObject
	 */
	private function _loadPersonalUserData($oObject){

		$oCustomer				= false;
		$oInquiry				= $oObject->getInquiry();
		if(
			is_object($oInquiry) &&
			$oInquiry instanceof Ext_TS_Inquiry
		){
			$oCustomer			= $oInquiry->getCustomer();
		}

		if(
			is_object($oCustomer) &&
			$oCustomer instanceof Ext_TS_Inquiry_Contact_Abstract
		){
			$aData['firstname']		= $oCustomer->firstname;
			$aData['lastname']		= $oCustomer->lastname;
			$aData['gender']		= $oCustomer->getGender();
			$aData['age']			= $oCustomer->getAge();
			$aData['customerId']	= $oCustomer->getCustomerNumber();
		}

		$aData['airline']		= $oObject->airline;
		$aData['comment']		= $oObject->comment;
		$aData['date']			= $this->oDate->format($oObject->transfer_date);
		$aData['time']			= $this->oDateTime->format($oObject->transfer_time);
		$aData['pickup']		= $this->oDateTime->format($oObject->pickup);


		$this->_setDataForExcelSheets($aData);
	}

	/**
	 * Anreise/Abreise Informationen aufnehmen
	 *
	 * @param <Ext_Thebing_Inquiry_Transfer> $oObject
	 */
	private function _setLocationData($oObject){ //

		if($this->currentTransferPosition == 'start'){

			if($oObject->start > 0){
				$oLocation =  Ext_TS_Transfer_Location::getInstance($oObject->start);
				$aData['from']		= $oLocation->getName();
				$aData['from']		.= ' ' . $oObject->getTerminalName('start');
				$aData['from']		.= ' ' . $oObject->flightnumber;
			}

		}else{

			if($oObject->end > 0){
				$oLocation = Ext_TS_Transfer_Location::getInstance($oObject->end);
				$aData['to']		= $oLocation->getName();
				$aData['to']		.= ' ' . $oObject->getTerminalName('end');
				$aData['to']		.= ' ' . $oObject->flightnumber;
			}

		}

		$this->_setDataForExcelSheets($aData);
	}

	/**
	 *  Unterkunftsrelevanten Informationen aufnehmen
	 *
	 * @param <Ext_Thebing_Inquiry_Accommodation> $oObject
	 * @param <Ext_Thebing_Accommodation> $oTransferData
	 */
	private function _setAccommodationData($oObject, $oAccommodation){

		if(!$oAccommodation){ //!?
			//throw new Exception("Accommodation object not available!"); 
			if($this->currentTransferPosition == 'start'){
				$aData['from'] = L10N::t('Unterkunft');
				$aData['from']		.= ' ' . $oObject->flightnumber;
			}else{
				$aData['to'] = L10N::t('Unterkunft');
				$aData['to']		.= ' ' . $oObject->flightnumber;
			}
			$this->_setDataForExcelSheets($aData);
			return;
		}

		$accommodation = 'accommodation';

		if($this->currentTransferPosition == 'start'){
			$aData['from'] = L10N::t('Unterkunft');
			$aData['from']		.= ' ' . $oObject->flightnumber;
		}else{
			$aData['to'] = L10N::t('Unterkunft');
			$aData['to']		.= ' ' . $oObject->flightnumber;
			if($oObject->start_type == 'accommodation' || $oObject->start_type == 'school' ||$oObject->start_type == 'location' ){
				$accommodation = 'accommodation2';
			}
		}

		$aData[$accommodation]['accommodation']  = $oAccommodation->ext_33; //name der Unterkunftsanbieter
		$aData[$accommodation]['address']		 = $oAccommodation->ext_63;
		$aData[$accommodation]['plz']			 = $oAccommodation->ext_64;
		$aData[$accommodation]['city']			 = $oAccommodation->ext_65;
		$aData[$accommodation]['phoneNo']		 = $oAccommodation->ext_67;

		if($this->aStudentData['date'] == ''){
			$aData['date']  = $this->oDate->format($oObject->transfer_date);
		}

		$this->_setDataForExcelSheets($aData);
	}


	/**
	 * Schulinformationen aufnehmen
	 *
	 * @param <Ext_Thebing_Inquiry_Transfer> $oObject
	 * @param <string> $sType
	 */
	private function _setSchoolData($oObject){

		if($this->currentTransferPosition == 'start'){
			$aData['from'] = L10N::t('Schule');
		}else{
			$aData['to'] = L10N::t('Schule');
		}

		if($this->aStudentData['date'] == ''){
			$aData['date'] = $this->oDate->format($oObject->transfer_date);
		}

		$this->_setDataForExcelSheets($aData);
	}


	/**
	 *
	 * insert data into Array
	 * @param <array> $aData
	 */
	private function _setDataForExcelSheets(array $aData){

		foreach($aData as $key=>$value) {
			$this->aStudentData[$key] = $value;
		}

	}

	/**
	 *  reset Array
	 */
	private function _resetStudentData(){

		reset($this->aStudentData);
		foreach($this->aStudentData as $key=>$value) {
			if($key == 'accommodation'  || $key == 'accommodation2') {
				foreach($this->aStudentData[$key] as $key2=>$value2) {
					$this->aStudentData[$key][$key2] = '';
				}
			} else {
				$this->aStudentData[$key] = '';
			}
		}
	}

}