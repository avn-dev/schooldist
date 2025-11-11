<?php
/**
 * erzeugt eine Excel-Datei mit Unterkunftsbezogenen Informationen
 *
 *
 * @created 08.02.11
 */
class Ext_Thebing_Accommodation_Export{

	protected $_oSchool = null;
	private $iCounter = 0;

	//Container für Unterkunfts-Informationen
	private $aStudentData;
	private $aLabels; //Spalten Überschriften

	private $oDate;
	private $oDateTime;

	private $sDescription;

	/**
	 *
	 */
	public function __construct($oSchool, $sDescription) {

		$this->sDescription = $sDescription;

		$this->_oSchool = $oSchool;
		$this->iCounter = 2;
		
		$this->_setStudentData();
		$this->_setLabels();
		$this->_createExcelSheet("Accommodationliste","Accommodationlist","Accommodationlist");

		$this->oDate	 = new Ext_Thebing_Gui2_Format_Date();
		$this->oDateTime = new Ext_Thebing_Gui2_Format_Date_Time();
		$this->oTime		= new Ext_Thebing_Gui2_Format_Time();

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
	 *
	 * @param <type> $aLabels
	 */
	private function _setLabels(){

		$this->aLabels = array(
				0 => array( L10N::t('K.Nr.', $this->sDescription),				10),
				1 => array(L10N::t('Name', $this->sDescription),					20),
				2 => array(L10N::t('Alter', $this->sDescription),					10),
				3 => array(L10N::t('Geschlecht', $this->sDescription),				10),
				4 => array(L10N::t('Gebuchte Unterkunft', $this->sDescription),		20),
				5 => array(L10N::t('Raum', $this->sDescription) ,					10),
				6 => array(L10N::t('Verpflegung', $this->sDescription),				10),
				7 => array(L10N::t('zug. Zimmer', $this->sDescription),				10),
				8 => array(L10N::t('Zimmer teilen mit', $this->sDescription),		10),
				9 => array(L10N::t('Unterkunft von', $this->sDescription),			20),
				10 => array(L10N::t('Unterkunft bis', $this->sDescription),			20),

				11 => array(L10N::t('Von Familie abgeholt', $this->sDescription),	20),
				12 => array(L10N::t('Anreisedatum', $this->sDescription),			20),
				13 => array(L10N::t('Ankunftszeit', $this->sDescription),			20),
				14 => array(L10N::t('Abholzeit Ankunft', $this->sDescription),		20),
				15 => array(L10N::t('Von Familie weggefahren', $this->sDescription),20),
				16 => array(L10N::t('Abreisedatum', $this->sDescription),			20),
				17 => array(L10N::t('Abreisezeit', $this->sDescription),			20),
				18 => array(L10N::t('Abholzeit Abreise', $this->sDescription),		20)

			);
	}

	/**
	 *
	 */
	private function _setStudentData(){

		$this->aStudentData = array(
				'customer_id'       => '',
				'name'						=> '',
				'age'               => '',
				'gender'            => '',
				'booked_accommodation' => '',
				'rooms'             => '',
				'catering'          => '',
				'assigned_rooms'	=> '',
				'share_with'        => '',
				'from'              => '',
				'until'				=> '',

				'arrival_pickup_info'		=> '',
				'arrival_date'      => '',
				'arrival_time'      => '',
				'arrival_pickup_time'		=> '',
				'departure_pickup_info'     => '',
				'departure_date'			=> '',
				'departure_time'			=> '',
				'departure_pickup_time'		=> ''
			);
	}

	/**
	 *
	 * @param <Ext_Thebing_Accommodation_Allocation> $oObject
	 */
	public function loadStudentData( $oAccommodationAllocation){

            if(!is_a($oAccommodationAllocation, 'Ext_Thebing_Accommodation_Allocation')){ //error handling
				throw new Exception("Ext_Thebing_Accommodation_Allocation object could not be loaded");//todo: log failures   
            }

            $oInquiryAccommodation = Ext_TS_Inquiry_Journey_Accommodation::getInstance($oAccommodationAllocation->inquiry_accommodation_id);
			$oInquiry = $oInquiryAccommodation->getInquiry();
			$sLang = $oInquiry->getSchool()->getLanguage();

			if(!is_a($oInquiryAccommodation,'Ext_Thebing_Inquiry_Accommodation')){
				throw new Exception(" Ext_Thebing_Inquiry_Accommodation object couldn't be loaded");
			}

            $iAccommodationId   = $oInquiryAccommodation->accommodation_id;
			$iMealId            = $oInquiryAccommodation->meal_id;
            $iRoomTypeId		= $oInquiryAccommodation->roomtype_id;

			try{

				$this->_setStudentPersonalData($oAccommodationAllocation);																	//Studenteninformationen
				$this->_setStudentCategoryData(Ext_Thebing_Accommodation_Category::getInstance($iAccommodationId),  $oInquiryAccommodation, $sLang); //Unterkunftskategorie
				$this->_setStudentRoomData(Ext_Thebing_Accommodation_Roomtype::getInstance($iRoomTypeId),  $oAccommodationAllocation, $sLang);		//Unterkunftseingenschaften
				$this->_setStudentMealData(Ext_Thebing_Accommodation_Meal::getInstance($iMealId), $oAccommodationAllocation, $sLang);				//Verpflegung

				$this->_setTransferData($oInquiry, $sLang);	//Transfer

				$this->_loadDataIntoSheet();

			}catch(Exception $e){
				throw new Exception("Error while loading Studentdata ".$e->getMessage());
			}
	}

	/*
	 * Setzen der Transferdaten
	 */
	public function _setTransferData(&$oInquiry, $sLang){
		$oTransferArrival = $oInquiry->getTransfers('arrival');
		$oTransferDeparture = $oInquiry->getTransfers('departure');


		$aData = array();
		
		// Ankunft
		if($oTransferArrival instanceof Ext_TS_Inquiry_Journey_Transfer){

			// Prüfen ob Provider fährt
			$sPickupInfo = L10N::t('nein');
			if(
				$oTransferArrival->provider_id > 0 &&
				$oTransferArrival->provider_type == 'accommodation'
			){
				$sPickupInfo = L10N::t('ja');
			}

			$aData['arrival_pickup_info']	= $sPickupInfo;
			$aData['arrival_date']			= $this->oDate->format($oTransferArrival->transfer_date);
			$aData['arrival_time']			= $this->oTime->format($oTransferArrival->transfer_time);
			$aData['arrival_pickup_time']	= $this->oTime->format($oTransferArrival->pickup);
		}

		// Abreise
		if($oTransferDeparture instanceof Ext_TS_Inquiry_Journey_Transfer){

			// Prüfen ob Provider fährt
			$sPickupInfo = L10N::t('nein');
			if(
				$oTransferDeparture->provider_id > 0 &&
				$oTransferDeparture->provider_type == 'accommodation'
			){
				$sPickupInfo = L10N::t('ja');
			}

			$aData['departure_pickup_info']		= $sPickupInfo;
			$aData['departure_date']			= $this->oDate->format($oTransferDeparture->transfer_date);
			$aData['departure_time']			= $this->oTime->format($oTransferDeparture->transfer_time);
			$aData['departure_pickup_time']		= $this->oTime->format($oTransferDeparture->pickup);
		}




		$this->_setDataForExcelSheets($aData);
	}


	/**
	 * 
	 * @param <Ext_Thebing_Accommodation_Roomtype> $oRoomTypeObject
	 * @param <Ext_Thebing_Accommodation_Allocation> $oObject
	 */
	private function _setStudentRoomData($oAccommodationRoomtype, $oAccommodationAllocation, $sLang){

		if($oAccommodationAllocation->share_with == 0){
			$sShareWith = L10N::t('---');
		}else{
			$sShareWith = $oAccommodationAllocation->share_with;
		}

		$oRoom = Ext_Thebing_Accommodation_Room::getInstance($oAccommodationAllocation->room_id);

		$aData['rooms']			 = $oAccommodationRoomtype->getName($sLang);
		$aData['assigned_rooms'] = $oRoom->name;
		$aData['share_with']	 = $sShareWith;

		$this->_setDataForExcelSheets($aData);
	}

	/**
	 *
	 * @param <Ext_Thebing_Accommodation_Meal> $oMealObject
	 * @param <Ext_Thebing_Accommodation_Allocation> $oObject
	 */
	private function _setStudentMealData($oAccommodationMeal, $oAccommodationAllocation, $sLang){

		$sFrom  = explode(" ",$oAccommodationAllocation->from);
		$sUntil = explode(" ",$oAccommodationAllocation->until);

		$aData['catering'] = $oAccommodationMeal->getName($sLang, false);
		$aData['from']	   = $this->oDate->format($sFrom[0]);
		$aData['until']	   = $this->oDate->format($sUntil[0]);

		$this->_setDataForExcelSheets($aData);
	}

	/**
	 * @param <Ext_Thebing_Accommodation_Category> $oCategoryObject
	 * @param <Ext_Thebing_Inquiry_Accommodation> $oObject
	 */
	private function _setStudentCategoryData($oCategoryAccommodation, $oInquiryAccommodation, $sLang){
		

		if($oCategoryAccommodation){
			$aData['booked_accommodation'] = $oCategoryAccommodation->getName($sLang);
		}

		

		$this->_setDataForExcelSheets($aData);
		
	}

	/**
	 *
	 * @param <Ext_Thebing_Accommodation_Allocation> $oObject
	 */
	private function _setStudentPersonalData($oObject){

		$aData['customer_id'] = $oObject->getInquiry()->getCustomer()->getCustomerNumber();
		$aData['name']			= $oObject->getInquiry()->getCustomer()->getName();
		$aData['gender']      = $oObject->getInquiry()->getCustomer()->getGender();
		$aData['age']	      = (int) $oObject->getInquiry()->getCustomer()->getAge();

		$this->_setDataForExcelSheets($aData);
	}

	/**
	 *
	 * @param array $aData
	 */
	private function _setDataForExcelSheets(array $aData){

		while(list($key, $value) = each($aData)){
			$this->aStudentData[$key] = $value;
		}
	}

	/**
     *  Datensätze aufnehmen
	 */
	private function _loadDataIntoSheet(){

		$i = 0;
		$this->iCounter++;

		foreach($this->aStudentData as $aCol){

			if(is_array($aCol)  ){
				$sTempData = '';
				foreach($aCol as $aC){
					if(isset($aC) && $aC != '') $sTempData .= $aC.', ';
				}
				$aCol = $sTempData;
			}

			$this->_oExcel->getActiveSheet()->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i).$this->iCounter, $aCol);
			$i++;
        }
		$this->_resetStudentData();
	}

	/**
	 *
	 */
	private function _resetStudentData(){

		reset($this->aStudentData);
		while(list($key, $value) = each($this->aStudentData)){
			if($key == 'accommodation'  || $key == 'accommodation2'){
				while(list($key2, $value2) = each($this->aStudentData[$key])){
					$this->aStudentData[$key][$key2] = '';
				}
			}else{
				$this->aStudentData[$key] = '';
			}
		}
	}



	/**
	 * Speichern des Dokuments (Dateiname mit fortlaufender ID generieren)
	 *
	 * @return string
	 */
	public function save() {

		// Letzte ID holen
		$sKey = 'accommodation_students_file_'.$this->_oSchool->id;
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

		$sName = 'accommodation_students_'.date('Ymd_His').'_'.$iCounter.'.xls';
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


}
