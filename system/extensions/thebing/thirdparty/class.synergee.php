<?php

class Ext_Thebing_ThirdParty_Synergee {

	protected $_oClient;
	protected $_sError;
	protected $_aReport = array();

	public function __construct($oClient) {

		$this->_oClient = $oClient;

	}

	public static function processClients() {

		$iCount = 0;
		
		// Can take a while, increase max memory and max runtime
		ini_set("memory_limit", "1024M");
		set_time_limit(1800);

		// Get all clients with synergee data
		$sSql = "
				SELECT
					*
				FROM 
					kolumbus_clients
				WHERE
					`synergee_email` != '' AND
					`synergee_pop3_server` != '' AND	
					`synergee_username` != '' AND
					`synergee_password` != '' AND
					`active` = 1
				";
		$aClients = DB::getQueryData($sSql);
		
		foreach((array)$aClients as $aClient) {
			try {
				$oClient = new Ext_Thebing_Client($aClient['id']);
				$oSynergee = new Ext_Thebing_ThirdParty_Synergee($oClient);
				$oSynergee->processImport();
				$iCount++;
			} catch(Exception $e) {
				Ext_Thebing_Util::reportError('Ext_Thebing_ThirdParty_Synergee::processClients() - Exception', $e);
			}
		}

		return $iCount;

	}
	
	public function processImport() {

		$this->_aReport = array();
		
		$oConnection = new Ext_TC_Communication_Pop3($this->_oClient->synergee_pop3_server, $this->_oClient->synergee_username, $this->_oClient->synergee_password);
		$aMails = $oConnection->getMails();

		$this->_aReport['mails'] = $aMails;
		
		if($aMails === false) {
			return 'no_connection';
		}

		foreach((array)$aMails as $iMail=>$aMail) {

			$sError = false;
			$iSuccess = 0;

			if(empty($aMail['attachments'])) {

				$sError = 'no_attachments';

			} else {

			    // check attachments
			    foreach((array)$aMail['attachments'] as $iAttachment=>$sFile) {
				    $aInfo = pathinfo($sFile);
				    $bSuccess = false;

					$this->_sError = false;

				    if(is_file($sFile)) {

					    if($aInfo['extension'] == 'xml') {

						    $sContent = file_get_contents($sFile);

						    try {

							    $oXML = new SimpleXMLElement($sContent);

							    $bSuccess = $this->_insertInquiry($oXML);

							    $this->_aReport[$sFile] = $bSuccess;

							    if($bSuccess === true) {
								    $iSuccess++;
									$this->_aReport['success']++;
							    }

						    } catch(Exception $e) {

							    __pout($e);

							    $this->_sError = 'no_valid_xml';

						    }

					    } else {

						    $this->_sError = 'no_xml_attachment';

					    }

				    } else {

					    $this->_sError = 'file_not_exists';

				    }

					$aMail['attachments'][$iAttachment] = array();
					$aMail['attachments'][$iAttachment]['file'] = $sFile;
					$aMail['attachments'][$iAttachment]['error'] = $this->_sError;
					$aMail['attachments'][$iAttachment]['success'] = $bSuccess;

			    }

			}

			if($iSuccess == 0) {
				$this->_aReport[$iMail] = $sError;
				$this->_aReport['errors']++;
				$this->_sendResponse(false, $sError, $aMail);
			} else {
				$this->_sendResponse(true, 'success', $aMail);
			}

			foreach((array)$aMail['attachments'] as $aFile) {
				unlink($aFile['file']);
			}
			
		}
		
		return 'success';
		
	}

	public function getReport() {
		return $this->_aReport;
	}

	protected function _getMessage($sMessageCode) {

		switch($sMessageCode) {
			case 'no_valid_xml':
				$sBody = 'No valid XML';
				break;
			case 'no_xml_attachment':
				$sBody = 'The attachment is no XML file.';
				break;
			case 'file_not_exists':
				$sBody = 'The attachment cannot be opened.';
				break;
			case 'no_school_found':
				$sBody = 'No school was found.';
				break;
			case 'no_course_found':
				$sBody = 'No course was found.';
				break;
			case 'no_attachments':
				$sBody = 'No attachments found.';
				break;
			case 'success':
				$sBody = 'One or more inquiries have been successfully imported.';
				break;
			case 'attachment_success':
				$sBody = 'The XML has been successfully imported.';
				break;
			default:
				$sBody = 'Error: message code missing!';
				break;
		}
		
		return $sBody;

	}

	protected function _sendResponse($bSuccess, $sMessageCode, $aMail, $aAdditional=array()) {
		
		$oMail = new WDMail();
		
		$sSubject = 'Thebing.com API - Synergee XML Import - ';
		
		if($bSuccess) {
			$sSubject .= 'Success';
		} else {
			$sSubject .= 'Error';
		}

		$this->_getMessage($sMessageCode);

		$aAttachments = array();
		
		$sBody .= "\n\nOriginal e-mail:\n\n";
		$sBody .= "From e-mail:   ".$aMail['from_email']."\n";
		$sBody .= "From name:     ".$aMail['from_name']."\n";
		$sBody .= "Subject:       ".$aMail['subject']."\n";
		foreach((array)$aMail['plain'] as $sValue) {
			$sBody .= "Text:          ".$sValue."\n";
		}
		foreach((array)$aMail['attachments'] as $iAttachment=>$aValue) {
			$aFile = pathinfo($aValue['file']);
			$aAttachments[$aValue['file']] = $aFile['basename'];
			$sBody .= "\nAttachment ".($iAttachment+1)."\n";
			$sBody .= "Name:   ".$aFile['basename']."\n";
			if($aValue['success']) {
				$aValue['error'] = 'attachment_success';
			}
			$sBody .= "Status: ".$this->_getMessage($aValue['error'])."\n";
		}

		if(!empty($aAttachments)) {
			$oMail->attachments = $aAttachments;
		}

		$oMail->subject = $sSubject;
		$oMail->text = $sBody;

		$bSuccess = $oMail->send($this->_oClient->synergee_email);

	}
	
	protected function _insertInquiry($oXML) {
		
		$oDB = DB::getDefaultConnection();
		
		// schoolName die Schul ID gesucht,
		$sSchool = (string)$oXML->requestsList->schoolOptions->schoolName;
		
		$oSchool = $this->_oClient->searchSchoolByName($sSchool);
		
		if(!$oSchool) {
			$this->_sError = 'no_school_found';
			return false;
		}

		$sLanguage = $oSchool->getLanguage();
		$oL10N = Ext_Thebing_L10N::getInstance($sLanguage);
		
		$iFlexFieldCourse = (int)$this->_oClient->synergee_flex_course;
		$iFlexFieldAccommodation = (int)$this->_oClient->synergee_flex_accommodation;
		
		// dann Kurs ID und
		$sCourse = (string)$oXML->requestsList->schoolOptions->variableDatesCourses->name;

		if($iFlexFieldCourse == 0) {
			$oCourse = $oSchool->getCourseByName($sCourse);
		
			if(!$oCourse) {
				$this->_sError = 'no_course_found';
				return false;
			}
		}

		// Unterkunft ID
		$sAccommodationCategory = (string)$oXML->requestsList->schoolOptions->accommodations->name;
		
		if($iFlexFieldAccommodation == 0) {
			$iAccommodationCategoryId = $oSchool->getAccommodationCategoryByName($sAccommodationCategory);
		
			if(!$iAccommodationCategoryId) {
				$this->_sError = 'no_accommodation_found';
				return false;
			}
		}

		// Ebenfalls wird geprüft anhand von E-Mail-Adresse, Vorname und Nachname ob der Kunde schon eingetragen ist.
		$sEmail = (string)$oXML->requestsList->contact->email;
		$sFirstname = (string)$oXML->requestsList->contact->firstName;
		if((string)$oXML->requestsList->contact->middleName != '') {
			$sFirstname .= " ".(string)$oXML->requestsList->contact->middleName;
		}
		$sLastname = (string)$oXML->requestsList->contact->lastName;
		$sBirthday = (string)$oXML->requestsList->contact->birthDate;
		$aBirthday = explode("-", $sBirthday);

		$sSql = "
				SELECT
					*
				FROM
					`customer_db_1`
				WHERE
					`active` = 1 AND
					`office` = :client_id AND
					`ext_31` = :school_id AND
					(
						`ext_1` LIKE :lastname AND
						`ext_2` LIKE :firstname AND
						`ext_3` = :birthday_day AND
						`ext_4` = :birthday_month AND
						`ext_5` = :birthday_year
					)
				";
		$aSql = array(
					'client_id'=>(int)$this->_oClient->id,
					'school_id'=>(int)$oSchool->id,
					'birthday_day'=>$aBirthday[2],
					'birthday_month'=>$aBirthday[1],
					'birthday_year'=>$aBirthday[0],
					'firstname'=>$sFirstname,
					'lastname'=>$sLastname
					);
		$aCustomer = DB::getQueryRow($sSql, $aSql);

		// Falls nicht wird der Kunde angelegt. 
		$aDataCustomer = array();
		$aDataInquiry = array();
		$aDataCourse = array();
		$aDataAccommodation = array();
		
		$aDataInquiry['office'] = $this->_oClient->id;
		$aDataInquiry['crs_partnerschool'] = $oSchool->id;

		// Generate number
		// $aDataCustomer['customerNumber'] = (string)$oXML->requestsList->contact->id;
		$aDataCustomer['office'] = $this->_oClient->id;
		$aDataCustomer['ext_31'] = $oSchool->id;
		$aDataCustomer['email'] = $sEmail;
		$aDataCustomer['nickname'] = \Util::generateRandomString(16);
		$aDataCustomer['ext_1'] = $sLastname;
		$aDataCustomer['ext_2'] = $sFirstname;
		$aDataCustomer['ext_3'] = $aBirthday[2];
		$aDataCustomer['ext_4'] = $aBirthday[1];
		$aDataCustomer['ext_5'] = $aBirthday[0];
		
		$iTitle = (int)$oXML->requestsList->contact->title;
		if($iTitle == 1) {
			$aDataCustomer['ext_6'] = 1;
		} elseif($iTitle > 1) {
			$aDataCustomer['ext_6'] = 2;
		}
		
		$aDataCustomer['ext_7'] = (string)$oXML->requestsList->contact->nationality;
		
		$aDataCustomer['ext_8'] = (string)$oXML->requestsList->contact->addressHome->address1;
		if((string)$oXML->requestsList->contact->addressHome->address2 != '') {
			$aDataCustomer['ext_8'] .= " ".(string)$oXML->requestsList->contact->addressHome->address2;
		}
		$aDataCustomer['ext_9'] = (string)$oXML->requestsList->contact->addressHome->zipCode;
		$aDataCustomer['ext_10'] = (string)$oXML->requestsList->contact->addressHome->city;
		$aDataCustomer['ext_11'] = (string)$oXML->requestsList->contact->addressHome->country;
		
		$aDataCustomer['ext_12'] = (string)$oXML->requestsList->contact->addressBilling->address1;
		if((string)$oXML->requestsList->contact->addressBilling->address2 != '') {
			$aDataCustomer['ext_12'] .= " ".(string)$oXML->requestsList->contact->addressBilling->address2;
		}
		$aDataCustomer['ext_13'] = (string)$oXML->requestsList->contact->addressBilling->zipCode;
		$aDataCustomer['ext_14'] = (string)$oXML->requestsList->contact->addressBilling->city;
		$aDataCustomer['ext_15'] = (string)$oXML->requestsList->contact->addressBilling->country;
		
		$aDataCustomer['ext_16'] = (string)$oXML->requestsList->contact->phoneHome;
		$aDataCustomer['ext_17'] = (string)$oXML->requestsList->contact->phoneProfessional;
		$aDataCustomer['ext_18'] = (string)$oXML->requestsList->contact->phoneMobile;
		$aDataCustomer['ext_19'] = (string)$oXML->requestsList->contact->faxHome;
		
		// Das XML speichere ich komplett im Kommentarfeld der Buchung.
		$aDataCustomer['ext_23'] = $oXML->asXML();
		$aDataCustomer['ext_24'] = (string)$oXML->requestsList->contact->addressBilling->name;
		
		$aDataCustomer['ext_31'] = $oSchool->id;
		
		$aDataCustomer['ext_32'] = $oSchool->getLanguageByName((string)$oXML->requestsList->contact->motherTongue1);
		
		$aDataInquiry['profession'] = (string)$oXML->requestsList->contact->profession;
		
		$aLevels = array();
		$aLevels[1] = "Beginner";
		$aLevels[2] = "Elementary";
		$aLevels[3] = "Pre-intermediate";
		$aLevels[4] = "Intermediate";
		$aLevels[5] = "Upper intermediate";
		$aLevels[6] = "Advanced";
		$aLevels[7] = "High Advanced";
		$aLevels[8] = "Proficiency";
		
		$aRoomType = array();
		$aRoomType[1] = "Single";
		$aRoomType[2] = "Double";
		$aRoomType[3] = "Triple";
		$aRoomType[4] = "Quadruple";
		$aRoomType[5] = "Dorm";
		
		$aMeal = array();
		$aMeal[0] = "Without meal";
		$aMeal[1] = "Breakfast";
		$aMeal[2] = "Half board";
		$aMeal[3] = "Full board";

		$sLevel = $aLevels[(string)$oXML->requestsList->contact->level]; // customer_db_24
		$sRoomType = $aRoomType[(string)$oXML->requestsList->schoolOptions->accommodations->roomType]; // customer_db_10
		$sMeal = $aMeal[(string)$oXML->requestsList->schoolOptions->accommodations->meal]; // customer_db_11

		$aDataInquiry['matching_bath'] = (string)$oXML->requestsList->schoolOptions->accommodations->bathroom;
		
		// Internet
		$iInternet = (string)$oXML->requestsList->schoolOptions->accommodations->internet;
		if($iInternet == 1) { 
			$aDataInquiry['matching_internet'] = 1;
		} elseif($iInternet > 1) {
			$aDataInquiry['matching_internet'] = 2;
		}

		// Kursdaten
		if($iFlexFieldCourse == 0) {

			$aDataCourse['course_id'] = $oCourse->id;
	        $aDataCourse['level_id'] = $oSchool->getLevelByName($sLevel);
	        $aDataCourse['from'] = Ext_Thebing_Import::makeTimestamp('mysql', (string)$oXML->requestsList->schoolOptions->variableDatesCourses->startDate, 'Y-m-d');
			$aDataCourse['until'] = Ext_Thebing_Import::makeTimestamp('mysql', (string)$oXML->requestsList->schoolOptions->variableDatesCourses->endDate, 'Y-m-d'); 
			$aDataCourse['weeks'] = (string)$oXML->requestsList->schoolOptions->variableDatesCourses->weeks; 

		} else {
			
			$iFrom = Ext_Thebing_Import::makeTimestamp('unix', (string)$oXML->requestsList->schoolOptions->variableDatesCourses->startDate, 'Y-m-d');
			$iUntil = Ext_Thebing_Import::makeTimestamp('unix', (string)$oXML->requestsList->schoolOptions->variableDatesCourses->endDate, 'Y-m-d');
			$iWeeks = (string)$oXML->requestsList->schoolOptions->variableDatesCourses->weeks; 
			
			$sTemplate = $oL10N->translate('{course_name}, {course_level}, {course_from} - {course_until}, {weeks} Woche(n)', 'Thebing » Synergee XML Import');
			
			$sTemplate = str_replace('{course_name}', $sCourse, $sTemplate);
			$sTemplate = str_replace('{course_level}', $sLevel, $sTemplate);
			$sTemplate = str_replace('{course_from}', Ext_Thebing_Format::LocalDate($iFrom), $sTemplate);
			$sTemplate = str_replace('{course_until}', Ext_Thebing_Format::LocalDate($iUntil), $sTemplate);
			$sTemplate = str_replace('{weeks}', $iWeeks, $sTemplate);
			
			$aDataCourse = array();
			$aDataCourse[$iFlexFieldCourse] = $sTemplate;
			
		}
			
		// Unterkunftsdaten
		if($iFlexFieldAccommodation == 0) {

			$aDataAccommodation['accommodation_id'] = $iAccommodationCategoryId;
			$aDataAccommodation['from'] = Ext_Thebing_Import::makeTimestamp('mysql', (string)$oXML->requestsList->schoolOptions->accommodations->startDate, 'Y-m-d');
			$aDataAccommodation['until'] = Ext_Thebing_Import::makeTimestamp('mysql', (string)$oXML->requestsList->schoolOptions->accommodations->endDate, 'Y-m-d');
			$aDataAccommodation['weeks'] = (string)$oXML->requestsList->schoolOptions->accommodations->weeks;
			$aDataAccommodation['roomtype_id'] = $oSchool->getRoomTypeByName($sRoomType);
			$aDataAccommodation['meal_id'] = $oSchool->getMealByName($sMeal);
			$aDataAccommodation['visible'] = 1;
			$aDataAccommodation['active'] = 1;

		} else {
						
			$iFrom = Ext_Thebing_Import::makeTimestamp('unix', (string)$oXML->requestsList->schoolOptions->accommodations->startDate, 'Y-m-d');
			$iUntil = Ext_Thebing_Import::makeTimestamp('unix', (string)$oXML->requestsList->schoolOptions->accommodations->endDate, 'Y-m-d');
			$iWeeks = (string)$oXML->requestsList->schoolOptions->accommodations->weeks; 
			
			$sTemplate = $oL10N->translate('{accommodation_category}, {room_type}, {meal}, {accommodation_from} - {accommodation_until}, {weeks} Woche(n)', 'Thebing » Synergee XML Import');
			
			$sTemplate = str_replace('{accommodation_category}', $sAccommodationCategory, $sTemplate);
			$sTemplate = str_replace('{room_type}', $sRoomType, $sTemplate);
			$sTemplate = str_replace('{meal}', $sMeal, $sTemplate);
			$sTemplate = str_replace('{accommodation_from}', Ext_Thebing_Format::LocalDate($iFrom), $sTemplate);
			$sTemplate = str_replace('{accommodation_until}', Ext_Thebing_Format::LocalDate($iUntil), $sTemplate);
			$sTemplate = str_replace('{weeks}', $iWeeks, $sTemplate);
			
			$aDataAccommodation = array();
			$aDataAccommodation[$iFlexFieldAccommodation] = $sTemplate;
						
		}
		
		// Hin- und Rückfahrt 
		$iRoundTrip = (string)$oXML->requestsList->schoolOptions->transfers->roundTrip;
		if($iRoundTrip) {
			$aDataInquiry['tsp_transfer'] = 'arr_dep';
		}

		// Airport
		$aDataInquiry['comment_transfer_arr'] = (string)$oXML->requestsList->schoolOptions->transfers->name;

		// Dann wird die Buchung angelegt mit Kurs, Unterkunft und Transfer.
		if(empty($aCustomer)) {
			$oDB->insert('customer_db_1', $aDataCustomer);
			$aCustomer['id'] = $oDB->getInsertID();
		} else {
			$oDB->update('customer_db_1', $aDataCustomer, "`id` = ".(int)$aCustomer['id']."");
		}
		
		// Inbox
		$aInbox = $this->_oClient->getInboxList();
		$aInbox = reset($aInbox);
		
		$aDataInquiry['ac_office'] = $aInbox['short'];
		$aDataInquiry['idUser'] = (int)$aCustomer['id'];
		$aDataInquiry['active'] = 1;
		$aDataInquiry['changed'] = date("YmdHis");
		$aDataInquiry['created'] = date("YmdHis");
		$aDataInquiry['idCurrency'] = 2;
		
		$oDB->insert('kolumbus_inquiries', $aDataInquiry);
		$iInquiryId = $oDB->getInsertID();
		
		if($iInquiryId > 0) {

			$oInquiry = new Ext_TS_Inquiry($iInquiryId);

			// Generate new customer number
			$oCustomerNumber = new Ext_Thebing_Customer_CustomerNumber($oInquiry);
			$oCustomerNumber->saveCustomerNumber();

			if($iFlexFieldCourse == 0) {
				$aDataCourse['inquiry_id'] = $iInquiryId;
				$oDB->insert('kolumbus_inquiries_courses', $aDataCourse);	
			} else {
				Ext_TC_Flexibility::saveData($aDataCourse, $iInquiryId);
			}
			
			if($iFlexFieldAccommodation == 0) {
				$aDataAccommodation['inquiry_id'] = $iInquiryId;
				$oDB->insert('kolumbus_inquiries_accommodations', $aDataAccommodation);
			} else {
				Ext_TC_Flexibility::saveData($aDataAccommodation, $iInquiryId);
			}		
			
			return true;

		}

		return false;

	}
	
}