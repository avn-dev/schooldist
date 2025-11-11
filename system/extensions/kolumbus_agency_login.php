<?PHP

/**
 * image request
 * 8
 */
if(
	$_REQUEST['task'] == 'get_image' &&
	!empty($_REQUEST['image'])
) {

	$_REQUEST['image'] = str_replace('/media/', '', $_REQUEST['image']);
	
	$sImage = \Util::getDocumentRoot()."media/".$_REQUEST['image'];
	
	$mSize = getimagesize($sImage); 
	
	if(
		$mSize &&
		$mSize[0] > 0
	) {

		$strExt = strtolower(substr($sImage, strrpos($sImage,".")+1));
		switch($strExt) {
			case("jpg"):
				header("Content-type: image/jpeg");
				break;
			case("gif"):
				header("Content-type: image/gif");
				break;
			case("png"):
				header("Content-type: image/png");
				break;
			default:
				break;
		}
		
		$fp = @fopen($sImage, 'rb');
		@fpassthru($fp);
		@fclose($fp);
		die();

	}
	
}

/**
 * image request
 */
if(
	$_REQUEST['task'] == 'get_js' &&
	!empty($_REQUEST['file'])
) {

	$_REQUEST['file'] = str_replace('/', '', $_REQUEST['file']);
	$_REQUEST['file'] = str_replace('..', '', $_REQUEST['file']);
	$sFile = \Util::getDocumentRoot()."media/js/".$_REQUEST['file'];

	if(is_file($sFile)) {
		
		header("Content-type: text/javascript");
		
		$fp = @fopen($sFile, 'r');
		@fpassthru($fp);
		@fclose($fp);
		die();

	}
	
}

// set language
if(isset($_REQUEST['page_language'])) {
	$_SESSION['page_language'] = $_REQUEST['page_language'];
}

include(\Util::getDocumentRoot()."system/includes/frontend.inc.php");

$system_data['debugmode'] = 0;

DB::setResultType(MYSQL_ASSOC);

$oSmarty = new SmartyWrapper();
$oSmarty->setTemplateDir(\Util::getDocumentRoot().'storage/templates');

if(isset($_VARS['view'])) {
	$sView = $_VARS['view'];
}

$bLoggedIn = false;

if(
	$user_data['idTable'] == 13 &&
	$user_data['login'] == 1
) {

	$bLoggedIn = true;

	$oAgency = new Ext_Thebing_Agency($user_data['id']);
	// Client id ist nötig da bei den Familien Bilder diese Client id so abgefragt wird
	$user_data['client'] = $oAgency->idClient;
	$oClient = new Ext_Thebing_Client((int)$user_data['client']);

	if($_VARS['task'] == 'get_document') {

		$oInquiry = $oAgency->getInquiry($_VARS['inquiry_id']);

		$oSchool = $oInquiry->getSchool();
		$sDocumentPath = $oSchool->getSchoolFileDir(false,false);	
		$sTemp = $oSchool->getSchoolFileDir();

		switch($_VARS['type']) {
			case 'get_inquiry_document':
				if($_VARS['document_id'] > 0 ){
					$oDocument = new Ext_Thebing_Inquiry_Document((int)$_VARS['document_id']);
					$oVersion = $oDocument->getLastVersion();
					$sDocumentPath = $oVersion->path;
				}
			case 'get_family_document':
				$oInquiry = new Ext_TS_Inquiry($_VARS['inquiry_id']);
				$aDocuments = $oInquiry->getAvailableFamilieDocuments($aInquiry['ext_32']);
				foreach((array)$aDocuments as $sPath=>$sDocument) {
					if(strpos($sPath, $_VARS['path']) !== false) {
						$sDocumentPath = str_replace(\Util::getDocumentRoot()."storage", '', $sPath);
						$sDocumentName = $sDocument;
						break;
					}
				}
				break;
			case 'get_family_image':
				$oInquiry = new Ext_TS_Inquiry($_VARS['inquiry_id']);
				$aDocument = $oInquiry->getFamiliePicturePdf();
				if(!empty($aDocument)) {
					$sPath = str_replace("/storage", '', key($aDocument));
					$sDocumentPath = $sPath;
					$sDocumentName= current($aDocument);
				}
				break;
			default:
				break;
		}

		$sDocumentPath = \Util::getDocumentRoot()."storage".$sDocumentPath;

		if(is_file($sDocumentPath)){
			$aTemp = explode('.',$sDocumentPath);
			$strExt = end($aTemp);
			//$strExt = strtolower(substr($sDocumentName, strrpos($sDocumentName,".")+1));
			switch($strExt) {
				case("pdf"):
					header("Content-Type: application/pdf");
					header("Content-Disposition: inline; filename=".$sDocumentName."");
					break;
				default:
					break;
			}

			$fp = fopen($sDocumentPath, 'rb');
			fpassthru($fp);
			fclose($fp);
			die();

		}
	
	}
	
	if(empty($sView)) {
		$sView = 'inbox';
	}

	if(empty($_VARS['school_id'])){
		$aSchools = $oAgency->getSchools(1);
		$_VARS['school_id'] = key($aSchools);
	}

	if($sView == 'inbox') {

		$aInboxList = $oClient->getInboxList(true);
		array_shift($aInboxList);
		if(empty($_VARS['ac_office'])){
			$_VARS['ac_office'] = key($aInboxList);
		}

		//__pout($_VARS);
		$iShowRows = 20;

		if(isset($_VARS['search'])) {
			$_SESSION['thebing']['agency']['search'] = $_VARS['search'];
		}
		if(isset($_VARS['filter'])) {
			$_SESSION['thebing']['agency']['filter'] = $_VARS['filter'];
		}
		if(isset($_VARS['filter_from'])) {
			$_SESSION['thebing']['agency']['from'] = $_VARS['filter_from'];
		}
		if(isset($_VARS['filter_until'])) {
			$_SESSION['thebing']['agency']['until'] = $_VARS['filter_until'];
		}
		
		if(
			isset($_SESSION['thebing']['agency']['from']) &&
			isset($_SESSION['thebing']['agency']['until'])
		) {
			$iFrom = strtotimestamp($_SESSION['thebing']['agency']['from']);
			$iUntil = strtotimestamp($_SESSION['thebing']['agency']['until']);
			$iFrom = mktime(0, 0, 0, date('m', $iFrom), date('d', $iFrom), date('Y', $iFrom));
			$iUntil = mktime(23, 59, 59, date('m', $iUntil), date('d', $iUntil), date('Y', $iUntil));
		} else {
			$iFrom = mktime(0, 0, 0, date('m'), 1, date('Y'));
			$iUntil = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
		}

		if(
			$_VARS['task'] == 'detail' ||
			$_VARS['task'] == 'add'
		) {

			$aGenders = Ext_Thebing_Util::getGenders();
			$aLanguages = Ext_Thebing_Data::getLanguageSkills(true);
			$aSchools = $oAgency->getSchools(1);

			$aCountries = Ext_Thebing_Data::getCountryList();
			$aBillingCountries = Ext_Thebing_Util::addEmptyItem($aCountries);

			$aYesNo = array('',L10N::t('No'),L10N::t('Yes'));
			$aFamilyAge = Ext_Thebing_Data::getFamilyAge();
			$aTransfer = Ext_Thebing_Data::getTransferList();
			$aNationality = Ext_Thebing_Nationality::getNationalities(true);

			if($_VARS['inquiry_id'] > 0) {

				$oInquiry = $oAgency->getInquiry($_VARS['inquiry_id']);

			} else {

				$oInquiry = new Ext_TS_Inquiry();
				$oInquiry->agency_id = $oAgency->id;
				$oInquiry->confirmed = 0;
				$oInquiry->active = 0;
				$oInquiry->is_agency_inquiry = 1;
				$oInquiry->office = $oAgency->idClient;

				if(
					empty($_VARS['crs_partnerschool']) &&
					empty($oInquiry->crs_partnerschool)
				) {
					$oInquiry->crs_partnerschool = key($aSchools);
				}

			}

			if(
				$_VARS['task'] == 'add' &&
				$_VARS['inquiry_id'] > 0 &&
				$oInquiry->active == 1
			) {
				wdmail("thebing@p32.de", "Thebing Debug - Agency login", "Access to active inquiry\n\n".print_r($_VARS, 1));
				die(L10N::t("Access not allowed!"));
			}

			$oCustomer = $oInquiry->getCustomer();

			if(
				$_VARS['act'] == 'save' ||
				$_VARS['act'] == 'add_course' ||
				$_VARS['act'] == 'add_accommodation' ||
				$_VARS['act'] == 'delete_course' ||
				$_VARS['act'] == 'delete_accommodation' ||
				$_VARS['act'] == 'reload'
			) {

				$oInquiry->crs_partnerschool = (int)$_VARS['crs_partnerschool'];
				$oInquiry->ac_office = $_VARS['ac_office'];

				// Format Klasse
				$aFormatData = array('school_id' => $oInquiry->crs_partnerschool);
				$oDummy;
				$oFormat = new Ext_Thebing_Gui2_Format_Date(false, $oInquiry->crs_partnerschool);

				$oCustomer->ext_1 = $_VARS['ext_1'];
				$oCustomer->ext_2 = $_VARS['ext_2'];
				$oCustomer->ext_6 = $_VARS['ext_6'];

				$sBirthday = $oFormat->convert($_VARS['birthday'], $oDummy, $aFormatData);
				$oCustomer->birthday = $sBirthday;

				$oCustomer->ext_7 = $_VARS['ext_7'];
				$oCustomer->ext_33 = $_VARS['ext_33'];
				$oCustomer->ext_11 = $_VARS['ext_11'];
				$oCustomer->ext_27 = $_VARS['ext_27'];
				$oCustomer->ext_32 = $_VARS['ext_32'];
				$oCustomer->ext_46 = $_VARS['ext_46'];
				$oCustomer->ext_8 = $_VARS['ext_8'];
				$oCustomer->ext_9 = $_VARS['ext_9'];
				$oCustomer->ext_10 = $_VARS['ext_10'];
				$oCustomer->ext_16 = $_VARS['ext_16'];
				$oCustomer->ext_17 = $_VARS['ext_17'];
				$oCustomer->ext_18 = $_VARS['ext_18'];
				$oCustomer->ext_19 = $_VARS['ext_19'];
				$oCustomer->email = $_VARS['email'];
				$oCustomer->ext_12 = $_VARS['ext_12'];
				$oCustomer->ext_13 = $_VARS['ext_13'];
				$oCustomer->ext_14 = $_VARS['ext_14'];
				$oCustomer->ext_15 = $_VARS['ext_15'];
//				$oCustomer->ext_37 = $_VARS['ext_37'];
				$oInquiry->currency_id = $_VARS['currency_id'];
				$oInquiry->promotion = $_VARS['promotion'];
				$oCustomer->ext_23 = $_VARS['ext_23'];
				$oCustomer->ext_25 = $_VARS['ext_25'];
				$oInquiry->referer = $_VARS['referer'];
				$oInquiry->referer_text = $_VARS['referer_text'];

				$oInquiry->acc_address = $_VARS['acc_address'];
				$oInquiry->acc_vegetarian = $_VARS['acc_vegetarian'];
				$oInquiry->acc_muslim_diat = $_VARS['acc_muslim_diat'];
				$oInquiry->acc_allergies = $_VARS['acc_allergies'];
				$oInquiry->acc_smoker = $_VARS['acc_smoker'];
				$oInquiry->acc_share_with_id = $_VARS['acc_share_with_id'];
				
				$oInquiry->visum_servis_id = $_VARS['visum_servis_id'];
				$oInquiry->visum_tracking_number = $_VARS['visum_tracking_number'];
				$oInquiry->visum_note = $_VARS['visum_note'];
				$oInquiry->visum_status = $_VARS['visum_status'];

				$oInquiry->tsp_transfer = $_VARS['tsp_transfer'];

				if(is_file($_FILES['upload_photo']['tmp_name'])) {
					$oCustomer->savePhoto($_FILES['upload_photo']['name'], $_FILES['upload_photo']['tmp_name']);
				}

				if(is_file($_FILES['upload_passport']['tmp_name'])) {
					$oCustomer->savePassport($_FILES['upload_passport']['name'], $_FILES['upload_passport']['tmp_name']);
				}
				
				$aCourses = array();
				foreach((array)$_VARS['courses']['inquiry_course_id'] as $iKey=> $iCourseId) {
					$aCourse = array();
					$aCourse['id'] = $iCourseId;
					$aCourse['course_id'] = $_VARS['courses']['course_id'][$iKey];
					$aCourse['level_id'] = $_VARS['courses']['level_id'][$iKey];
					$aCourse['weeks'] = $_VARS['courses']['weeks'][$iKey];

					$sFrom = $oFormat->convert($_VARS['courses']['from'][$iKey], $oDummy, $aFormatData);
					$sUntil = $oFormat->convert($_VARS['courses']['until'][$iKey], $oDummy, $aFormatData);

					$aCourse['from'] = $sFrom;
					$aCourse['until'] = $sUntil;
					$aCourses[] = $aCourse;
				}
		
				$aAccommodations = array();
				foreach((array)$_VARS['accommodations']['inquiry_accommodation_id'] as $iKey=> $iAccommodationId) {
					$aAccommodation = array();
					$aAccommodation['id'] = $iAccommodationId;
					$aAccommodation['accommodation_id'] = $_VARS['accommodations']['accommodation_id'][$iKey];
					$aAccommodation['roomtype_id'] = $_VARS['accommodations']['roomtype_id'][$iKey];
					$aAccommodation['meal_id'] = $_VARS['accommodations']['meal_id'][$iKey];
					$aAccommodation['weeks'] = $_VARS['accommodations']['weeks'][$iKey];

					$sFrom = $oFormat->convert($_VARS['accommodations']['from'][$iKey], $oDummy, $aFormatData);
					$sUntil = $oFormat->convert($_VARS['accommodations']['until'][$iKey], $oDummy, $aFormatData);

					$aAccommodation['from'] = $sFrom;
					$aAccommodation['until'] = $sUntil;
					$aAccommodations[] = $aAccommodation;
				}

			} else {
				
				$aCourses = $oInquiry->getCourses();
				$aAccommodations = $oInquiry->getAccommodations();
				
			}

			if($_VARS['act'] == 'save') {

				$oCustomer->save();

				$oInquiry->idUser = $oCustomer->id;
				$oInquiry->save();

				// Transferspeichern

				$oTransferArrival = $oInquiry->getTransfers('arrival');
				if(!$oTransferArrival || empty($oTransferArrival)){
					$oTransferArrival = new Ext_TS_Inquiry_Journey_Transfer();
				}

				$oTransferDeparture = $oInquiry->getTransfers('departure');
				if(!$oTransferDeparture || empty($oTransferDeparture)){
					$oTransferDeparture = new Ext_TS_Inquiry_Journey_Transfer();
				}

				$sArrival = $oFormat->convert($_VARS['tsp_arrival'], $oDummy, $aFormatData);
				$sDeparture = $oFormat->convert($_VARS['tsp_departure'], $oDummy, $aFormatData);

				$sArrivalTime = NULL;
				$sDepartureTime = NULL;

				if(!empty($_VARS['tsp_arrival_time'])){
					$sArrivalTime = $_VARS['tsp_arrival_time'].':00';
				}

				if(!empty($_VARS['tsp_departure_time'])){
					$sDepartureTime = $_VARS['tsp_departure_time'].':00';
				}

				$oTransferArrival->inquiry_id = $oInquiry->id;
				$oTransferArrival->transfer_date = $sArrival;
				$oTransferArrival->transfer_time = $sArrivalTime;
				$oTransferArrival->transfer_type = 1;
				$oTransferArrival->start = $_VARS['tsp_airport'];
				$oTransferArrival->end = 0;
				$oTransferArrival->start_type = 'location';
				$oTransferArrival->end_type = 'accommodation';
				$oTransferArrival->airline = $_VARS['tsp_airline'];
				$oTransferArrival->flightnumber = $_VARS['tsp_flightnumber'];
				$oTransferArrival->save();

				$oTransferDeparture->inquiry_id = $oInquiry->id;
				$oTransferDeparture->transfer_date = $sDeparture;
				$oTransferDeparture->transfer_time = $sDepartureTime;
				$oTransferDeparture->transfer_type = 2;
				$oTransferDeparture->start = 0;
				$oTransferDeparture->end = $_VARS['tsp_airport2'];
				$oTransferDeparture->start_type = 'accommodation';
				$oTransferDeparture->end_type = 'location';
				$oTransferDeparture->airline = $_VARS['tsp_airline2'];
				$oTransferDeparture->flightnumber = $_VARS['tsp_flightnumber2'];
				$oTransferDeparture->save();

				// delete old
				$aOldCourses = $oInquiry->getCourses();
				foreach((array)$aOldCourses as $aOldCourse) {
					$oInquiryCourse = new Ext_TS_Inquiry_Journey_Course($aOldCourse['id']);
					$oInquiryCourse->delete();
				}
				
				// save new
				foreach((array)$aCourses as $aCourse) {
					if($aCourse['course_id'] <= 0){
						continue;
					}
					$oInquiryCourse = new Ext_TS_Inquiry_Journey_Course($aCourse['id']);
					$oInquiryCourse->inquiry_id = $oInquiry->id;
					$oInquiryCourse->course_id = $aCourse['course_id'];
					$oInquiryCourse->level_id = $aCourse['level_id'];
					$oInquiryCourse->weeks = $aCourse['weeks'];
					$oInquiryCourse->from = $aCourse['from'];
					$oInquiryCourse->until = $aCourse['until'];
					$oInquiryCourse->visible = 1;
					$oInquiryCourse->active = 1;
					$oInquiryCourse->save();
				}

				// delete old
				$aOldAccommodations = $oInquiry->getAccommodations();
				foreach((array)$aOldAccommodations as $aOldAccommodation) {
					$oInquiryAccommodation = new Ext_TS_Inquiry_Journey_Accommodation($aOldAccommodation['id']);
					$oInquiryAccommodation->delete();
				}
				
				// save new
				foreach((array)$aAccommodations as $aAccommodation) {
					if(
						$aAccommodation['accommodation_id'] <= 0 ||
						$aAccommodation['roomtype_id'] <= 0 ||
						$aAccommodation['meal_id'] <= 0 
					){
						continue;
					}
					$oInquiryAccommodation = new Ext_TS_Inquiry_Journey_Accommodation($aAccommodation['id']);
					$oInquiryAccommodation->inquiry_id = $oInquiry->id;
					$oInquiryAccommodation->accommodation_id = $aAccommodation['accommodation_id'];
					$oInquiryAccommodation->roomtype_id = $aAccommodation['roomtype_id'];
					$oInquiryAccommodation->meal_id = $aAccommodation['meal_id'];
					$oInquiryAccommodation->weeks = $aAccommodation['weeks'];
					$oInquiryAccommodation->from = $aAccommodation['from'];
					$oInquiryAccommodation->until = $aAccommodation['until'];
					$oInquiryAccommodation->visible = 1;
					$oInquiryAccommodation->active = 1;
					$oInquiryAccommodation->save();
				}
				
			}

			$oSchool = $oInquiry->getSchool();

			// Bei neuanlegen
			if(!$oSchool || $oSchool->id == 0){
				$iSchool = reset($aSchools);
				$oSchool = Ext_Thebing_School::getInstance($iSchool);
			}

			$aCourseSelect 			= $oSchool->getCourseList();

			$aLevelSelect 			= $oSchool->getCourseLevelList();

			$aAccommodationSelect	= $oSchool->getAccommodationList();

			$aMealSelect			= $oSchool->getMealList();

			$aRoomtypeSelect		= $oSchool->getRoomtypeList();

			$sDateFormat = Ext_Thebing_Format::getDateFormat($oSchool->id);

			if(
				empty($aCourses) || 
				$_VARS['act'] == 'add_course'
			) {
				$aCourses[] = array();
			}
	
			if(
				empty($aAccommodations) || 
				$_VARS['act'] == 'add_accommodation'
			) {
				reset($aAccommodationSelect);
				$aAccommodations[] = array('accommodation_id'=>key($aAccommodationSelect));
			}
			
			if($_VARS['act'] == 'delete_course') {
				unset($aCourses[$_VARS['delete_id']]);
			}
	
			if($_VARS['act'] == 'delete_accommodation') {
				unset($aAccommodations[$_VARS['delete_id']]);
			}

			$oCurrency = new Ext_Thebing_Currency_Util($oSchool->id);
			$aCurrency = $oCurrency->getCurrencyList(2);
			$aAirports = Ext_Thebing_Data_Airport::getAirports($oInquiry->crs_partnerschool);
			$aVisumState = Ext_Thebing_Visum::getVisumStatusList($oInquiry->crs_partnerschool);

			$i = 0;
			$aFields[$i]['label'] 		= L10N::t("Persönliche Daten");
			$aFields[$i]['type'] 		= "tab";
			$i++;
			
			$aFields[$i]['value'] 		= $oInquiry->crs_partnerschool;
			$aFields[$i]['field'] 		= 'crs_partnerschool';
			$aFields[$i]['label'] 		= L10N::t("School");
			$aFields[$i]['type'] 		= 'select';
			$aFields[$i]['data_array'] 	= $aSchools;
			$aFields[$i]['onchange'] 	= 'reload();';			
			$i++;

			if(!empty($aInboxList)){
				$aFields[$i]['value'] 		= $oInquiry->ac_office;
				$aFields[$i]['field'] 		= 'ac_office';
				$aFields[$i]['label'] 		= L10N::t("Inbox");
				$aFields[$i]['type'] 		= 'select';
				$aFields[$i]['data_array'] 	= $aInboxList;
				$i++;
			}
			
			$aFields[$i]['value'] 		= $oCustomer->ext_1;
			$aFields[$i]['field'] 		= 'ext_1';
			$aFields[$i]['alias'] 		= 'c';
			$aFields[$i]['label'] 		= L10N::t("Last name");
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oCustomer->ext_2;
			$aFields[$i]['field'] 		= 'ext_2';
			$aFields[$i]['alias'] 		= 'c';
			$aFields[$i]['label'] 		= L10N::t("Firstname");
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oCustomer->ext_6;
			$aFields[$i]['field'] 		= 'ext_6';
			$aFields[$i]['alias'] 		= 'c';
			$aFields[$i]['label'] 		= L10N::t("Gender");
			$aFields[$i]['type'] 		= 'select';
			$aFields[$i]['data_array'] 	= $aGenders;
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;

			$aFields[$i]['value'] 		= $oCustomer->getBirthday();
			$aFields[$i]['field'] 		= 'birthday';
			$aFields[$i]['alias'] 		= 'c';
			$aFields[$i]['label'] 		= L10N::t("Birthday")." ( ".Ext_Thebing_Format::LocalDate(time())." )";;
			$aFields[$i]['type'] 		= 'date';
			$aFields[$i]['date_format'] = $sDateFormat;
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
		
			$aFields[$i]['value'] 		= $oCustomer->ext_46;
			$aFields[$i]['field'] 		= 'ext_46';
			$aFields[$i]['alias'] 		= 'c';
			$aFields[$i]['label'] 		= L10N::t("Nationality");
			$aFields[$i]['style']		= "width:300px;";
			$aFields[$i]['type'] 		= 'select';
			$aFields[$i]['data_array'] 	= $aNationality;
			$i++;
			$aFields[$i]['value'] 		= $oCustomer->ext_33;
			$aFields[$i]['field'] 		= 'ext_33';
			$aFields[$i]['alias'] 		= 'c';
			$aFields[$i]['label'] 		= L10N::t("State");
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oCustomer->ext_11;
			$aFields[$i]['field'] 		= 'ext_11';
			$aFields[$i]['alias'] 		= 'c';
			$aFields[$i]['label'] 		= L10N::t("Country");
			$aFields[$i]['type'] 		= 'select';
			$aFields[$i]['data_array'] 	= $aCountries;
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oCustomer->ext_27;
			$aFields[$i]['field'] 		= 'ext_27';
			$aFields[$i]['alias'] 		= 'c';
			$aFields[$i]['label'] 		= L10N::t("Language");
			$aFields[$i]['type'] 		= 'select';
			$aFields[$i]['data_array'] = $aLanguages;
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oCustomer->ext_32;
			$aFields[$i]['field'] 		= 'ext_32';
			$aFields[$i]['alias'] 		= 'c';
			$aFields[$i]['label'] 		= L10N::t("Mother tongue");
			$aFields[$i]['type'] 		= 'select';
			$aFields[$i]['data_array'] = $aLanguages;
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oCustomer->ext_8;
			$aFields[$i]['field'] 		= 'ext_8';
			$aFields[$i]['alias'] 		= 'c';
			$aFields[$i]['label'] 		= L10N::t("Address");
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oCustomer->ext_9;
			$aFields[$i]['field'] 		= 'ext_9';
			$aFields[$i]['alias'] 		= 'c';
			$aFields[$i]['label'] 		= L10N::t("Zip-Code");
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oCustomer->ext_10;
			$aFields[$i]['field'] 		= 'ext_10';
			$aFields[$i]['alias'] 		= 'c';
			$aFields[$i]['label'] 		= L10N::t("City");
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oCustomer->ext_16;
			$aFields[$i]['field'] 		= 'ext_16';
			$aFields[$i]['alias'] 		= 'c';
			$aFields[$i]['label'] 		= L10N::t("Phone home");
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oCustomer->ext_17;
			$aFields[$i]['field'] 		= 'ext_17';
			$aFields[$i]['alias'] 		= 'c';
			$aFields[$i]['label'] 		= L10N::t("Phone office");
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oCustomer->ext_18;
			$aFields[$i]['field'] 		= 'ext_18';
			$aFields[$i]['alias'] 		= 'c';
			$aFields[$i]['label'] 		= L10N::t("Cell phone");
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oCustomer->ext_19;
			$aFields[$i]['field'] 		= 'ext_19';
			$aFields[$i]['alias'] 		= 'c';
			$aFields[$i]['label'] 		= L10N::t("Fax");
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oCustomer->email;
			$aFields[$i]['field'] 		= 'email';
			$aFields[$i]['alias'] 		= 'c';
			$aFields[$i]['label'] 		= L10N::t("E-mail");
			$aFields[$i]['style']		= "width:300px;";
			$i++;
			$aFields[$i]['type']		= "h2"; 
			$aFields[$i]['label']		= L10N::t('Billing Details'); 
			$i++;
			$aFields[$i]['value'] 		= $oCustomer->ext_12;
			$aFields[$i]['field'] 		= 'ext_12';
			$aFields[$i]['alias'] 		= 'c';
			$aFields[$i]['label'] 		= L10N::t("Billing address");
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oCustomer->ext_13;
			$aFields[$i]['field'] 		= 'ext_13';
			$aFields[$i]['alias'] 		= 'c';
			$aFields[$i]['label'] 		= L10N::t("Billing Zip-Code");
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oCustomer->ext_14;
			$aFields[$i]['field'] 		= 'ext_14';
			$aFields[$i]['alias'] 		= 'c';
			$aFields[$i]['label'] 		= L10N::t("Billing City");
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oCustomer->ext_15;
			$aFields[$i]['field'] 		= 'ext_15';
			$aFields[$i]['alias'] 		= 'c';
			$aFields[$i]['label'] 		= L10N::t("Billing Country");
			$aFields[$i]['type'] 		= 'select';
			$aFields[$i]['data_array']	= $aBillingCountries;
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
//			$aFields[$i]['value'] 		= $oCustomer->ext_37;
//			$aFields[$i]['field'] 		= 'ext_37';
//			$aFields[$i]['alias'] 		= 'c';
//			$aFields[$i]['label'] 		= L10N::t("Überweisungskosten");
//			$aFields[$i]['type'] 		= 'select';
//			$aFields[$i]['data_array'] 	= array(0=>L10N::t('Schule'),1=>L10N::t('Schüler'));
//			$aFields[$i]['style']		= "width:300px;";
//			$i++;
			$aFields[$i]['type']		= "h2"; 
			$aFields[$i]['label']		= L10N::t('Currency'); 
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->getCurrency();
			$aFields[$i]['field'] 		= 'currency_id';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("Currency");
			$aFields[$i]['type'] 		= 'select';
			$aFields[$i]['data_array'] 	= $aCurrency;
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
						
			$aFields[$i]['type']		= "h2"; 
			$aFields[$i]['label']		= L10N::t('Other'); 
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->promotion;
			$aFields[$i]['field'] 		= 'promotion';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("Promotion-Code");
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oCustomer->ext_23;
			$aFields[$i]['field'] 		= 'ext_23';
			$aFields[$i]['alias'] 		= 'c';
			$aFields[$i]['label'] 		= L10N::t("Annotations");
			$aFields[$i]['style']		= "width:300px;"; 
			$aFields[$i]['type'] 		= 'textarea';
			$i++;
			$aFields[$i]['value'] 		= $oCustomer->ext_25;
			$aFields[$i]['field'] 		= 'ext_25';
			$aFields[$i]['alias'] 		= 'c';
			$aFields[$i]['label'] 		= L10N::t("Other");
			$aFields[$i]['style']		= "width:300px;"; 
			$aFields[$i]['type'] 		= 'textarea';
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->referer;
			$aFields[$i]['field'] 		= 'referer';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("How did you find us?");
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->referer_text;
			$aFields[$i]['field'] 		= 'referer_text';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("on the recommendation of... / Other");
			$aFields[$i]['style']		= "width:300px;"; 
			$aFields[$i]['type'] 		= 'textarea';
		
			$i++;
			$aFields[$i]['label'] 		= L10N::t("Courses");
			$aFields[$i]['type'] 		= "tab";
			
			$iCourse = 1;
			foreach((array)$aCourses as $iKey=>$aCourse) {
				
				$i++;
				$aFields[$i]['label'] 		= L10N::t("Course")." ".$iCourse;
				$aFields[$i]['type'] 		= "h3";

				$i++;
				$aFields[$i]['value'] 		= $aCourse['id'];
				$aFields[$i]['field'] 		= 'courses[inquiry_course_id][]';
				$aFields[$i]['type'] 		= 'hidden';
				$i++;
				$aFields[$i]['value'] 		= $aCourse['course_id'];
				$aFields[$i]['field'] 		= 'courses[course_id][]';
				$aFields[$i]['label'] 		= L10N::t("Course");
				$aFields[$i]['type'] 		= 'select';
				$aFields[$i]['data_array'] 	= $aCourseSelect;
				$i++;
				$aFields[$i]['value'] 		= $aCourse['level_id'];
				$aFields[$i]['field'] 		= 'courses[level_id][]';
				$aFields[$i]['label'] 		= L10N::t("Level");
				$aFields[$i]['type'] 		= 'select';
				$aFields[$i]['data_array'] 	= $aLevelSelect; 
				$i++;
				$aFields[$i]['value'] 		= $aCourse['weeks'];
				$aFields[$i]['field'] 		= 'courses[weeks][]';
				$aFields[$i]['label'] 		= L10N::t("Number of Weeks");
				$i++;
				$aFields[$i]['value'] 		= $aCourse['from'];
				$aFields[$i]['field'] 		= 'courses[from][]';
				$aFields[$i]['type'] 		= 'date';
				$aFields[$i]['date_format'] = $sDateFormat;
				$aFields[$i]['label'] 		= L10N::t("From");
				$i++;
				$aFields[$i]['value'] 		= $aCourse['until'];
				$aFields[$i]['field'] 		= 'courses[until][]';
				$aFields[$i]['type'] 		= 'date';
				$aFields[$i]['date_format'] = $sDateFormat;
				$aFields[$i]['label'] 		= L10N::t("Until");
				$i++;
				$aFields[$i]['onclick'] 	= "deleteEntry('course', ".(int)$iKey.");";
				$aFields[$i]['type'] 		= 'btn';
				$aFields[$i]['label'] 		= L10N::t("Delete this course");
				
				$iCourse++;
				
			}
		
			$i++;
			$aFields[$i]['onclick'] 	= "addEntry('course', ".(int)$iKey.");";
			$aFields[$i]['type'] 		= 'btn';
			$aFields[$i]['label'] 		= L10N::t("Add course");
			
			$i++;
			$aFields[$i]['label'] 		= L10N::t("Accommodations");
			$aFields[$i]['type'] 		= "tab";

			$iAccommodation = 1;
			foreach((array)$aAccommodations as $iKey=>$aAccommodation) {

				$oAccommodation = new Ext_Thebing_Accommodation_Util($oSchool);
				$oAccommodation->setAccommodationCategorie($aAccommodation['accommodation_id']);
				$aRoomtypeSelect = $oAccommodation->getRoomtypeList(1);

				$aMealSelect = array(''=>'');
				$aRooms = $oAccommodation->getRoomtypeList();
				
				foreach ((array)$aRooms as $aRoom) {
					if($aRoom['id'] != $aAccommodation['roomtype_id']){
						continue;
					}

					$aMeals = json_decode($aRoom['meal']);
					
					$oAccommodation->setRoomtype($aRoom);

					foreach ((array)$aMeals as $iMeal) {
						$oAccommodation->setMealById($iMeal);
						$aMealSelect[$iMeal] = $oAccommodation->getMealName(true);
					}

					break;

				}

				$i++;
				$aFields[$i]['label'] 		= L10N::t("Accommodation")." ".$iAccommodation;
				$aFields[$i]['type'] 		= "h3";

				$i++;
				$aFields[$i]['value'] 		= $aAccommodation['id'];
				$aFields[$i]['field'] 		= 'accommodations[inquiry_accommodation_id][]';
				$aFields[$i]['type'] 		= 'hidden';
				$i++;
				$aFields[$i]['value'] 		= $aAccommodation['accommodation_id'];
				$aFields[$i]['field'] 		= 'accommodations[accommodation_id][]';
				$aFields[$i]['label'] 		= L10N::t("Accommodation");
				$aFields[$i]['type'] 		= 'select';
				$aFields[$i]['data_array'] 	= $aAccommodationSelect;
				$aFields[$i]['onchange'] 	= 'reload();';
				$i++;
				$aFields[$i]['value'] 		= $aAccommodation['roomtype_id'];
				$aFields[$i]['field'] 		= 'accommodations[roomtype_id][]';
				$aFields[$i]['label'] 		= L10N::t("Roomtype");
				$aFields[$i]['type'] 		= 'select';
				$aFields[$i]['data_array'] 	= $aRoomtypeSelect; 
				$aFields[$i]['onchange'] 	= 'reload();';
				$i++;
				$aFields[$i]['value'] 		= $aAccommodation['meal_id'];
				$aFields[$i]['field'] 		= 'accommodations[meal_id][]';
				$aFields[$i]['label'] 		= L10N::t("Meal");
				$aFields[$i]['type'] 		= 'select';
				$aFields[$i]['data_array'] 	= $aMealSelect; 
				$i++;
				$aFields[$i]['value'] 		= $aAccommodation['weeks'];
				$aFields[$i]['field'] 		= 'accommodations[weeks][]';
				$aFields[$i]['label'] 		= L10N::t("Number of Weeks");
				$i++;
				$aFields[$i]['value'] 		= $aAccommodation['from'];
				$aFields[$i]['field'] 		= 'accommodations[from][]';
				$aFields[$i]['type'] 		= 'date';
				$aFields[$i]['date_format'] = $sDateFormat;
				$aFields[$i]['label'] 		= L10N::t("From");
				$i++;
				$aFields[$i]['value'] 		= $aAccommodation['until'];
				$aFields[$i]['field'] 		= 'accommodations[until][]';
				$aFields[$i]['type'] 		= 'date';
				$aFields[$i]['date_format'] = $sDateFormat;
				$aFields[$i]['label'] 		= L10N::t("Until");		
				$i++;
				$aFields[$i]['onclick'] 	= "deleteEntry('accommodation', ".(int)$iKey.");";
				$aFields[$i]['type'] 		= 'btn';
				$aFields[$i]['label'] 		= L10N::t("Delete this accommodation");		
				
				$iAccommodation++;
				
			}
	
			$i++;
			$aFields[$i]['onclick'] 	= "addEntry('accommodation', ".(int)$iKey.");";
			$aFields[$i]['type'] 		= 'btn';
			$aFields[$i]['label'] 		= L10N::t("Add accommodation");
			
			$i++;
			$aFields[$i]['label'] 		= L10N::t("Matching Details");
			$aFields[$i]['type'] 		= "tab";
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->acc_address;
			$aFields[$i]['field'] 		= 'acc_address';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("Matching Info");
			$aFields[$i]['type'] 		= 'textarea';
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->acc_vegetarian;
			$aFields[$i]['field'] 		= 'acc_vegetarian';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("I am a vegetarian");
			$aFields[$i]['type'] 		= 'select';
			$aFields[$i]['data_array'] 	= $aYesNo;
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->acc_muslim_diat;
			$aFields[$i]['field'] 		= 'acc_muslim_diat';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("Muslim Diat");
			$aFields[$i]['type'] 		= 'select';
			$aFields[$i]['data_array'] 	= $aYesNo;
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->acc_allergies;
			$aFields[$i]['field'] 		= 'acc_allergies';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("Allergies");
			$aFields[$i]['type'] 		= 'textarea';
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->acc_smoker;
			$aFields[$i]['field'] 		= 'acc_smoker';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("Smoker");
			$aFields[$i]['type'] 		= 'select';
			$aFields[$i]['data_array'] 	= $aYesNo;
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->acc_share_with_id;
			$aFields[$i]['field'] 		= 'acc_share_with_id';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("I would like to share double/twin room with (document_number)");
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;		
	
			$aFields[$i]['label'] 		= L10N::t("Pickup");
			$aFields[$i]['type'] 		= "tab";
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->tsp_airline;
			$aFields[$i]['field'] 		= 'tsp_airline';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("Arrival: Airline");
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->tsp_airport;
			$aFields[$i]['field'] 		= 'tsp_airport';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("Arrival: Airport");
			$aFields[$i]['style']		= "width:300px;"; 
			$aFields[$i]['type']		= "select"; 
			$aFields[$i]['data_array']	= $aAirports; 
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->tsp_flightnumber;
			$aFields[$i]['field'] 		= 'tsp_flightnumber';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("Arrival: Flight number");
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->tsp_arrival;
			$aFields[$i]['field'] 		= 'tsp_arrival';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("Arrival: Date")." ( ".Ext_Thebing_Format::LocalDate(time())." )";
			$aFields[$i]['style']		= "width:300px;"; 
			$aFields[$i]['type'] 		= 'date';
			$aFields[$i]['date_format'] = $sDateFormat;
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->tsp_arrival;
			$aFields[$i]['field'] 		= 'tsp_arrival_time';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("Arrival: Time")." ( ".strftime('%H:%M',time())." )";
			$aFields[$i]['style']		= "width:300px;"; 
			$aFields[$i]['type'] 		= 'time';
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->tsp_airline2;
			$aFields[$i]['field'] 		= 'tsp_airline2';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("Departure: Airline");
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->tsp_airport2;
			$aFields[$i]['field'] 		= 'tsp_airport2';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("Departure: Airport");
			$aFields[$i]['style']		= "width:300px;"; 
			$aFields[$i]['type']		= "select"; 
			$aFields[$i]['data_array']	= $aAirports; 
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->tsp_flightnumber2;
			$aFields[$i]['field'] 		= 'tsp_flightnumber2';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("Departure: Flight number");
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->tsp_departure;
			$aFields[$i]['field'] 		= 'tsp_departure';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("Departure: Date")." ( ".Ext_Thebing_Format::LocalDate(time())." )";
			$aFields[$i]['style']		= "width:300px;"; 
			$aFields[$i]['type'] 		= 'date';
			$aFields[$i]['date_format'] = $sDateFormat;
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->tsp_departure;
			$aFields[$i]['field'] 		= 'tsp_departure_time';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("Departure: Time")." ( ".strftime('%H:%M',time())." )";
			$aFields[$i]['style']		= "width:300px;"; 
			$aFields[$i]['type'] 		= 'time';
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->tsp_transfer;
			$aFields[$i]['field'] 		= 'tsp_transfer';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("Transfer");
			$aFields[$i]['style']		= "width:300px;"; 
			$aFields[$i]['type']		= "select"; 
			$aFields[$i]['data_array']	= $aTransfer; 
			$i++;
		
			
			$aFields[$i]['label'] 		= L10N::t("Visum");
			$aFields[$i]['type'] 		= "tab";
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->visum_servis_id;
			$aFields[$i]['field'] 		= 'visum_servis_id';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("Sevis ID");
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->visum_tracking_number;
			$aFields[$i]['field'] 		= 'visum_tracking_number';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("Mail tracking number");
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->visum_note;
			$aFields[$i]['field'] 		= 'visum_note';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("Note");
			$aFields[$i]['style']		= "width:300px;"; 
			$i++;
			$aFields[$i]['value'] 		= $oInquiry->visum_status;
			$aFields[$i]['field'] 		= 'visum_status';
			$aFields[$i]['alias'] 		= 'i';
			$aFields[$i]['label'] 		= L10N::t("Status");
			$aFields[$i]['style']		= "width:300px;"; 
			$aFields[$i]['type']		= "select"; 
			$aFields[$i]['data_array']	= $aVisumState; 
			$i++;
			
			
			$aFields[$i]['label'] 		= L10N::t("Upload");
			$aFields[$i]['type'] 		= "tab";
			$i++;
			$aFields[$i]['value'] 		= $oCustomer->getPhoto();
			$aFields[$i]['field'] 		= 'upload_photo';
			$aFields[$i]['type']		= "image";
			$aFields[$i]['label']		= L10N::t('Photo');
			$i++;
			$aFields[$i]['value'] 		= $oCustomer->getPassport();
			$aFields[$i]['field'] 		= 'upload_passport';
			$aFields[$i]['type']		= "file";
			$aFields[$i]['label']		= L10N::t('Passport');
			$i++;

			$oSmarty->assign('oInquiry', $oInquiry);
			$oSmarty->assign('oCustomer', $oCustomer);

			$oSmarty->assign('aFields', $aFields);

			$sLocaleDate = $page_data['language'];
			
			$intTest = mktime(0, 0, 0, 12, 31, 1990);
			$sDateFormat = Ext_Thebing_Format::LocalDate($intTest, $oSchool->id);
			$sDateFormat = str_replace('12', 'mm', $sDateFormat);
			$sDateFormat = str_replace('31', 'dd', $sDateFormat);
			$sDateFormat = str_replace('1990', 'yyyy', $sDateFormat);
			$sDateFormat = str_replace('90', 'yy', $sDateFormat);
			$iStartDate = mktime(0, 0, 0, 01, 01, 1910);

			$oSmarty->assign('sLocaleDate', $sLocaleDate);
			$oSmarty->assign('sDateFormat', $sDateFormat);
			$oSmarty->assign('iStartDate', $iStartDate);

		} elseif($_VARS['task'] == 'release') {
			
			if(!empty($_VARS['inquiries'])){
				$aMarkedInquiries = $_VARS['inquiries'];
			} else if($oInquiry && $oInquiry->id > 0){
				$aMarkedInquiries = array($oInquiry->id);
			}

			foreach((array)$aMarkedInquiries as $iInquiryId) {
				$oInquiry = $oAgency->getInquiry($iInquiryId);
				$oInquiry->active = 1;
				$oInquiry->is_agency_inquiry = 0;
				$oInquiry->save();
			}
			
			unset($_VARS['task']);

		} elseif($_VARS['task'] == 'confirm') {

			foreach((array)$_VARS['inquiries'] as $iInquiryId) {

				$oInquiry = $oAgency->getInquiry($iInquiryId);
				
				if($oInquiry->id > 0) {
	
					$oInquiry->confirm();
	
				}
			}
			
			unset($_VARS['task']);
		} 

		if(empty($_VARS['task'])) {
		
			$aFilterOptions = array();
			$aFilterOptions[''] = L10N::t('All');
			$aFilterOptions['not_paid'] = L10N::t('Not yet paid');
			$aFilterOptions['not_yet_travelled'] = L10N::t('Not yet travelled');
	
			$aReturn = $oAgency->getInquiries($iFrom, $iUntil, $_SESSION['thebing']['agency']['search'], $_SESSION['thebing']['agency']['filter'], (int)$_VARS['offset'], (int)$iShowRows);
	
			foreach((array)$aReturn['inquiries'] as $iKey=>$aInquiry) {
				$oInquiry = new Ext_TS_Inquiry($aInquiry['id']);
				$aDocuments = $oInquiry->getAvailableFamilieDocuments($aInquiry['ext_32']);
				foreach((array)$aDocuments as $sPath=>$sDocument) {
					//$sPath = str_replace(\Util::getDocumentRoot(), '', $sPath);
					$aReturn['inquiries'][$iKey]['family_documents'][$sPath] = $sDocument;
				}
				$aDocument = $oInquiry->getFamiliePicturePdf();
				if(!empty($aDocument)) {
					$sPath = str_replace(\Util::getDocumentRoot(), '', key($aDocument));
					$aReturn['inquiries'][$iKey]['family_image']['path'] = $sPath;
					$aReturn['inquiries'][$iKey]['family_image']['title'] = current($aDocument);
				}

				$iDocument = 0;
				$oDocument = $oInquiry->getDocuments('brutto', false, true);
				if($oDocument && $oDocument->id > 0){
					$iDocument = $oDocument->id;
				}
				$aReturn['inquiries'][$iKey]['idInvoice'] = (int)$iDocument;
				
				$iDocument = 0;
				$oDocument = $oInquiry->getDocuments('netto', false, true);
				if($oDocument && $oDocument->id > 0){
					$iDocument = $oDocument->id;
				}
				$aReturn['inquiries'][$iKey]['idInvoiceNet'] = (int)$iDocument;
				
				$iDocument = 0;
				$oDocument = $oInquiry->getDocuments('loa', false, true);
				if($oDocument && $oDocument->id > 0){
					$iDocument = $oDocument->id;
				}
				$aReturn['inquiries'][$iKey]['idLoa'] = (int)$iDocument;
				
				$iDocument = 0;
				$oDocument = $oInquiry->getDocuments('storno', false, true);
				if($oDocument && $oDocument->id > 0){
					$iDocument = $oDocument->id;
				}
				$aReturn['inquiries'][$iKey]['idCancelation'] = (int)$iDocument;
			}
			
			$aPagination = array();
			$aPagination['from'] = (int)$_VARS['offset'];
			$aPagination['to'] = ($_VARS['offset'] + count($aReturn['inquiries']));
			$aPagination['total'] = $aReturn['total'];
			$aPagination['offset_back'] = ($_VARS['offset'] - $iShowRows);
			if($aPagination['offset_back'] < 0) {
				$aPagination['offset_back'] = 0;
			}
			$aPagination['offset_forward'] = ($_VARS['offset'] + $iShowRows);
			if($aPagination['offset_forward'] > $aReturn['total']) {
				$iPages = floor($aReturn['total'] / $iShowRows);
				$aPagination['offset_forward'] = $iPages * $iShowRows;
			}
	
			$oSmarty->assign('sSearch', $_SESSION['thebing']['agency']['search']); 
			$oSmarty->assign('sFilter', $_SESSION['thebing']['agency']['filter']); 
			$oSmarty->assign('iFrom', $iFrom); 
			$oSmarty->assign('iUntil', $iUntil); 
			$oSmarty->assign('aFilterOptions', $aFilterOptions);
			$oSmarty->assign('aPagination', $aPagination);
			$oSmarty->assign('aInquiries', $aReturn['inquiries']);
		}
	
	} elseif($sView == 'material_orders') {

		$iShowRows = 20;

		if(isset($_VARS['filter'])) {
			$_SESSION['thebing']['agency_orders']['filter'] = $_VARS['filter'];
		}
		if(isset($_VARS['filter_from'])) {
			$_SESSION['thebing']['agency_orders']['from'] = $_VARS['filter_from'];
		}
		if(isset($_VARS['filter_until'])) {
			$_SESSION['thebing']['agency_orders']['until'] = $_VARS['filter_until'];
		}
		
		if(
			isset($_SESSION['thebing']['agency_orders']['from']) &&
			isset($_SESSION['thebing']['agency_orders']['until'])
		) {
			$iFrom = strtotimestamp($_SESSION['thebing']['agency_orders']['from']);
			$iUntil = strtotimestamp($_SESSION['thebing']['agency_orders']['until']);
			$iFrom = mktime(0, 0, 0, date('m', $iFrom), date('d', $iFrom), date('Y', $iFrom));
			$iUntil = mktime(23, 59, 59, date('m', $iUntil), date('d', $iUntil), date('Y', $iUntil));
		} else {
			$iFrom = mktime(0, 0, 0, date('m'), 1, date('Y'));
			$iUntil = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
		}
		
	
		if(
			$_VARS['task'] == 'add' ||
			$_VARS['task'] == 'detail'
		) {

			$aSchools = $oAgency->getSchools(1);
			$aAddresses = $oAgency->getDeliveryAddresses(1);

			if($_VARS['order_id'] > 0) {

				$oOrder = $oAgency->getMaterialOrder($_VARS['order_id']);

			} else {

				$oOrder = new Ext_Thebing_Agency_Materialorder();
				$oOrder->agency_id = $oAgency->id;

				if(
					empty($_VARS['school_id']) &&
					empty($oOrder->school_id)
				) {
					$oOrder->school_id = key($aSchools);
				} else if(empty($oOrder->school_id)) {
					$oOrder->school_id = $_VARS['school_id'];
				}

			}

			if(
				$_VARS['act'] == 'save' ||
				$_VARS['act'] == 'add_course' ||
				$_VARS['act'] == 'add_accommodation' ||
				$_VARS['act'] == 'delete_course' ||
				$_VARS['act'] == 'delete_accommodation' ||
				$_VARS['act'] == 'reload'
			) {

				$oOrder->school_id = (int)$_VARS['school_id'];

				$oOrder->address_id = (int)$_VARS['address_id'];
				$oOrder->message = (string)$_VARS['message'];				

				foreach((array)$_VARS['amount'] as $iMaterialId=>$iAmount) {
					$oOrder->setMaterialAmount($iMaterialId, $iAmount);
				}
						
			}

			$oSchool = $oAgency->getSchool($oOrder->school_id);

			$aMaterials = $oSchool->getMaterialOrderItems(1);
			
			if($_VARS['act'] == 'save') {

				$iCheckId = $oOrder->id;
				
				$oOrder->save();
				
				if($iCheckId == 0) {
					$oAgency->sendMaterialOrder($oOrder);
				}
				
				$_VARS['task'] = 'detail';
				
			}

			$i = 0;
			$aFields[$i]['label'] 		= L10N::t("Order data");
			$aFields[$i]['type'] 		= "h2";
			$i++;
			
			$aFields[$i]['value'] 		= $oOrder->school_id;
			$aFields[$i]['field'] 		= 'school_id';
			$aFields[$i]['label'] 		= L10N::t("School");
			$aFields[$i]['type'] 		= 'select';
			$aFields[$i]['data_array'] 	= $aSchools;
			$aFields[$i]['onchange'] 	= "reload('order_form');";			
			$i++;
			
			$aFields[$i]['value'] 		= $oOrder->address_id;
			$aFields[$i]['field'] 		= 'address_id';
			$aFields[$i]['label'] 		= L10N::t("Delivery address");
			$aFields[$i]['type'] 		= 'select';
			$aFields[$i]['data_array'] 	= $aAddresses;
			$i++;
			$aFields[$i]['value'] 		= $oOrder->message;
			$aFields[$i]['field'] 		= 'message';
			$aFields[$i]['label'] 		= L10N::t("Message");
			$aFields[$i]['style']		= "width:300px; height: 100px;";
			$aFields[$i]['type'] 		= 'textarea';

			$i++;
			$aFields[$i]['label'] 		= L10N::t("Materials");
			$aFields[$i]['type'] 		= "h2";
			
			$i++;
			$aFields[$i]['label'] 		= L10N::t("Please enter the amount you wish to order:");
			$aFields[$i]['type'] 		= "p";
			
			foreach((array)$aMaterials as $iMaterialId=>$sMaterial) {

				$i++;
				$aFields[$i]['value'] 		= $oOrder->getMaterialAmount($iMaterialId);
				$aFields[$i]['field'] 		= 'amount['.$iMaterialId.']';
				$aFields[$i]['type'] 		= 'input';
				$aFields[$i]['label'] 		= $sMaterial;
				$i++;

			}

			$oSmarty->assign('oOrder', $oOrder);

			$oSmarty->assign('aFields', $aFields);

		}

		if(empty($_VARS['task'])) {
		
			$aFilterOptions = array();
			$aFilterOptions[''] = L10N::t('All');
			$aFilterOptions['not_sent'] = L10N::t('Not yet sent');
			$aFilterOptions['sent'] = L10N::t('Sent');

			$aReturn = $oAgency->getMaterialOrders($iFrom, $iUntil, $_SESSION['thebing']['agency_orders']['filter'], (int)$_VARS['offset'], (int)$iShowRows);

			foreach((array)$aReturn['orders'] as $iKey=>$aOrder) {
				$oOrder = new Ext_Thebing_Agency_Materialorder($aOrder['id']);
				$aReturn['orders'][$iKey]['items'] = $oOrder->getMaterialString();
			}

			$aPagination = array();
			$aPagination['from'] = (int)$_VARS['offset'];
			$aPagination['to'] = ($_VARS['offset'] + count($aReturn['orders']));
			$aPagination['total'] = $aReturn['total'];
			$aPagination['offset_back'] = ($_VARS['offset'] - $iShowRows);
			if($aPagination['offset_back'] < 0) {
				$aPagination['offset_back'] = 0;
			}
			$aPagination['offset_forward'] = ($_VARS['offset'] + $iShowRows);
			if($aPagination['offset_forward'] > $aReturn['total']) {
				$iPages = floor($aReturn['total'] / $iShowRows);
				$aPagination['offset_forward'] = $iPages * $iShowRows;
			}
	
			$oSmarty->assign('sFilter', $_SESSION['thebing']['agency_orders']['filter']); 
			$oSmarty->assign('iFrom', $iFrom); 
			$oSmarty->assign('iUntil', $iUntil); 
			$oSmarty->assign('aFilterOptions', $aFilterOptions);
			$oSmarty->assign('aPagination', $aPagination);
			$oSmarty->assign('aOrders', $aReturn['orders']);
			
		}
	
	} elseif($sView == 'delivery_addresses') {
		
		$aCountries = Ext_Thebing_Data::getCountryList();
		
		if($_VARS['task'] == 'save') {
			
			$oAddress = new Ext_Thebing_Agency_Address((int)$_VARS['address_id']);
			
			$oAddress->company_id = $oAgency->id;
			$oAddress->shortcut = $_VARS['company'];
			$oAddress->company = $_VARS['company'];
			$oAddress->contact = $_VARS['contact'];
			$oAddress->street = $_VARS['street'];
			$oAddress->zip = $_VARS['zip'];
			$oAddress->city = $_VARS['city'];
			$oAddress->country = $_VARS['country'];
			$oAddress->phone = $_VARS['phone'];
			
			$mValidate = $oAddress->validate();

			if($mValidate === true) {

				$oAddress->save();
				$_VARS['address_id'] = $oAddress->id;
				unset($_VARS['task']);

			} else {

				$oSmarty->assign('aErrors', $mValidate);
				$_VARS['task'] = 'add';

			}			

		} elseif($_VARS['task'] == 'delete') {
			
			$oAddress = new Ext_Thebing_Agency_Address((int)$_VARS['address_id']);
			
			$oAddress->active = 0;
			$oAddress->save();

			unset($_VARS['task']);
			
		}
		
		if($_VARS['task'] == 'add') {

			$oAddress = new Ext_Thebing_Agency_Address((int)$_VARS['address_id']);

			$oSmarty->assign('aCountries', $aCountries);
			$oSmarty->assign('oAddress', $oAddress);
			
		} else {
			
			$aAddresses = $oAgency->getDeliveryAddresses();
			
			foreach((array)$aAddresses as $iKey=>$aAddress) {
				$aAddresses[$iKey]['country'] = $aCountries[$aAddress['country']];
			}
			
			$oSmarty->assign('aAddresses', $aAddresses);
			
		}
		
	} elseif($sView == 'statistics') {

		$aReports = $oAgency->getPlaceholderOverview(false); 

		$oSmarty->assign('aReports', $aReports);
		
	} elseif($sView == 'administration') {
		
		if(
			isset($_VARS['password']) &&
			isset($_VARS['password_repeat'])
		) {
			
			if(
				!empty($_VARS['password']) &&
				!empty($_VARS['password_repeat']) &&
				$_VARS['password'] == $_VARS['password_repeat']
			) {

				$oCustomerDb = new Ext_CustomerDB_DB(13);
	
				$oCustomerDb->updateCustomerField($user_data['id'], 'password', $_VARS['password']);
	
				$bSuccess = 1;
				
			} else {
				
				$bError = 1;
				
			}
			
		}
		
	}

	$oSmarty->assign('bSuccess', $bSuccess); 
	$oSmarty->assign('bError', $bError);

} else {
	
	if($sView == 'send_password') {

		if(!empty($_VARS['customer_login_1'])) {
			$oCustomerDb = new Ext_CustomerDB_DB(13);
			$aCustomer = $oCustomerDb->getCustomerByUniqueField('user', $_VARS['customer_login_1']);
			
			if(!empty($aCustomer)) {

				$oAgency = new Ext_Thebing_Agency($aCustomer['id']);
				$oAgency->sendPassword();

				$oSmarty->assign('bSuccess', 1); 
			} else {
				$oSmarty->assign('bError', 1);
			}
		}
		
	} else {
			
		if(!empty($_VARS['loginfailed'])) {
			$oSmarty->assign('sLoginfailed', $_VARS['loginfailed']);
		}

	}

}

$oSmarty->assign('bLoggedIn', $bLoggedIn);

$oSmarty->assign('sView', $sView);
$oSmarty->assign('sTask', $_VARS['task']);

$sCode = $oSmarty->fetch('script.agencylogin.tpl');

$sCode = replacevars($sCode);

echo $sCode;

?>
