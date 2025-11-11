<?php

use Ts\Dto\CourseStartDate;

/**
 * @property \Ext_TS_Inquiry WDBasic
 */
class Ext_Thebing_Inquiry_Gui2 extends Ext_Thebing_Document_Gui2 {

	const TRANSLATION_PATH = 'Thebing » Invoice » Inbox';

	/**
	 * @var array
	 */
	protected $_aSaveErrors = array();

	/**
	 * Nötig da sonst bei Agenturen in der Kind-GUI nach dem
	 * Speichern der Edit-Button nichtmehr funktioniert
	 *
	 * @var bool
	 */
    protected $_bAddParentIdsForSaveCallback = false;

	/**
	 * Flag um include.gettablequerydata.php zu umgehen
	 *
	 * @var bool
	 */
	protected $_bIgnoreTableQueryDataInclude = false;

	/**
	 * @param string $sColumn
	 * @param string $sAlias
	 * @param int $iID
	 * @param string $sFileNameOld
	 * @param array $aOptionValues
	 * @return string
	 */
	public function buildFileName($sColumn, $sAlias, $iID, $sFileNameOld, $aOptionValues = array()) {

		$aTempType	= explode('.', $sFileNameOld);
		$oInquiry = Ext_TS_Inquiry::getInstance($iID);
		$oCustomer = $oInquiry->getCustomer();

		// Fotoupload
		if($sColumn == 'upload1' && $sAlias == 'upload') {

			$iUserId = $oCustomer->id;
			$sFileName = 'photo_'.$iUserId.'.'.$aTempType[1];

		// Passbild
		} else if($sColumn == 'upload2' && $sAlias == 'upload') {

			$iUserId = $oCustomer->id;
			$sFileName = 'passport_'.$iUserId.'.'.$aTempType[1];

		// Dynamische Uploads
		} else if(strpos($sColumn, 'studentupload_') !== FALSE  && $sAlias == 'upload') {

			$iUploadId = str_replace('studentupload_', '', $sColumn);
			$oSchool = $oInquiry->getSchool();
			$sPath = $oSchool->getSchoolFileDir(false,false);
			$sPath = $sPath.'/studentuploads/';
			$sFileName = $sPath.$iID.'/upload_'.$iUploadId.'.'.$aTempType[1];
			$sFileName = trim($sFileName, '/');

		} else {
			$sFileName = Ext_Thebing_Document_Gui2::buildFileName($sColumn, $sAlias, $iID, $sFileNameOld);
		}

		return $sFileName;
	}

	/**
	 * @todo Wird auch in der Ext_Thebing_Gui2_Data (parent) benötigt an einigen stellen müsste man mal dorthin verlagern
	 * @param int $iInquiryId
	 * @param array $aSelectedIds
	 */
	protected function checkInquiryId(&$iInquiryId, &$aSelectedIds) {

		if($this->_oGui->query_id_alias == 'kit') {
			$iInquiryId = $this->_oGui->decodeId((int)reset($aSelectedIds), 'transfer_inquiry_id');
			$aSelectedIds = array($iInquiryId);
		} else if($this->_oGui->query_id_alias == 'kia') {
			$iInquiryId = $this->_oGui->decodeId((int)reset($aSelectedIds), 'accommodation_inquiry_id');
			$aSelectedIds = array($iInquiryId);
		}

	}

	/**
	 * @see \Ext_TS_Inquiry_Index_Gui2_Data::getDialog()
	 * @see Ext_Thebing_Inquiry_Gui2_Html
	 *
	 * Erzeugt HTML und Tabs für den "edit" Dialog
	 * HTML bitte in die Ext_Thebing_Inquiry_Gui2_Html auslagern!
	 */
	protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false) {

		// Daten für die HTML Klassen setzten
		Ext_Thebing_Inquiry_Gui2_Html::$sL10NDescription = $this->_oGui->gui_description;
		Ext_Thebing_Inquiry_Gui2_Html::$oCalendarFormat = $this->_oGui->_oCalendarFormat;

		$interfaceLanguage = Ext_Thebing_School::fetchInterfaceLanguage();
		
		$aSelectedIds = (array)$aSelectedIds;
		if(count($aSelectedIds) > 1) {
			return array();
		} else {
			$iInquiryId = (int)reset($aSelectedIds);
		}

		$this->checkInquiryId($iInquiryId, $aSelectedIds);

		if(!$this->oWDBasic) {
			$this->oWDBasic = Ext_TS_Inquiry::getInstance($iInquiryId);
		}

		$sTitle = L10N::t('Buchung', $this->_oGui->gui_description);

		$oCustomer	= null;
		$oInquiry	= null;
		$oSchool	= null;
			
		if($iInquiryId > 0) {
			$oInquiry = $this->oWDBasic;
			$oSchool = $oInquiry->getSchool();
			$oCustomer = $oInquiry->getCustomer();
			$oFormat = new Ext_Thebing_Gui2_Format_CustomerName();
			$sTitle .= ' - "'.$oFormat->format('', $aDummy, $oCustomer->aData).'"';
		} else {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$sTitle = L10N::t('Neue Buchung', $this->_oGui->gui_description);
		}

		$aData = $oDialogData->generateAjaxData($aSelectedIds, $this->_oGui->hash);

		$aData['inquiry_id'] = $iInquiryId;
		$aData['contact_id'] = $oCustomer->id ?? 0; // FormData erzeugt aus null 'null'

		foreach($aData['tabs'] as &$aTabData) {
							
			switch($aTabData['options']['task']) {
				case 'personal_data':
					## Start Agency Paymentmethoden mitschicken
						$aData['agency_payment_method'] = array();
						$aData['agency_currency'] = array();
					$aAgencies = Ext_Thebing_Client::getFirstClient()->getAgencies();

					foreach((array)$aAgencies as $aAgency) {
						$aData['agency_payment_method'][$aAgency['id']] = array('id'=>$aAgency['ext_26'], 'comment' =>$aAgency['ext_38']);
						$aData['agency_currency'][$aAgency['id']] = $aAgency['ext_23'];
					}
					## ENDE

					## Schuldaten

					if(Ext_Thebing_System::isAllSchools()) {
						$aData['all_school'] = 1;
					}

					// Wird benötigt für Deaktivierung des Schul-Selects
					$aData['has_invoice'] = 0;
					if($oInquiry) {
						$aData['has_invoice'] = $oInquiry->has_invoice;
					}

					## GruppenListe mitschicken
						$aGroups = $oSchool->getAllGroups(true); 
						$aGroups[0] = '';
						ksort($aGroups);
						$aData['groups'] = $aGroups;
						$aData['customer_group'] = 0;
						if($oInquiry){
							$aData['inquiry_group'] = (int)$oInquiry->group_id;
						}
					## ENDE

					## START Mitschicken von Gruppeninformationen zum deaktivieren bestimmter Felder
						$sCourseInfo = $sAccommodationInfo = $sTransferInfo = '';
						if($oInquiry->group_id > 0){
							$oGroup						= Ext_Thebing_Inquiry_Group::getInstance($oInquiry->group_id);
							$sCourseInfo				= $oGroup->course_data;
							$sAccommodationInfo			= $oGroup->accommodation_data;
							$sTransferInfo				= $oGroup->transfer_data;
						}
						$aData['course_info']			= $sCourseInfo;
						$aData['accommodation_info']	= $sAccommodationInfo;
						$aData['transfer_info']			= $sTransferInfo;

					## ENDE
						
					## START Mitschicken von Informationen die das Währungsselect sperren
						
						$iDisableCurrencySelect = 0;
						
						if($oInquiry->id > 0)
						{
							$iLastInquiryDoc = Ext_Thebing_Inquiry_Document_Search::search($oInquiry->id, 'invoice');
							if($iLastInquiryDoc > 0){
								$iDisableCurrencySelect = 1;
							}	
						}
						
						$aData['bDisableCurrencySelect']	= $iDisableCurrencySelect;
					## ENDE

					## Gruppen ID mitschicken
						$aData['group_id'] = (int)$oInquiry->group_id;
						
					## Secial Informationen mitschicken
						$aSpecialInfo = array();
						$sSpecialInfo = '';

						if($oInquiry->id > 0){
							$aSpecialBlocks = $oInquiry->getSpecialData();
							if(!empty($aSpecialBlocks)){
								// Special Blocks gefunden
								foreach((array)$aSpecialBlocks as $oInquirySpecialPosition){
									$oSpecial	= $oInquirySpecialPosition->getSpecial();
									if(is_object($oSpecial)){
										$oObject = null;
										switch($oInquirySpecialPosition->type){
											case 'course':
												$oBookingObject = Ext_TS_Inquiry_Journey_Course::getInstance($oInquirySpecialPosition->type_id);
												$oObject = $oBookingObject->getCourse();
												$aSpecialInfo[] = $oSpecial->name . ': ' . $oObject->getName($oSchool->fetchInterfaceLanguage());
												break;
											case 'accommodation':
												$oBookingObject = Ext_TS_Inquiry_Journey_Accommodation::getInstance($oInquirySpecialPosition->type_id);
												$aSpecialInfo[] = $oSpecial->name . ': ' . $oBookingObject->getAccommodationCategoryWithRoomTypeAndMeal();
												break;
											case 'transfer':
												$oBookingObject = Ext_Thebing_Transfer_Package::getInstance($oInquirySpecialPosition->type_id);
												$aSpecialInfo[] = $oSpecial->name . ': ' . $oBookingObject->name;
												break;
											case 'additional_course':
											case 'additional_accommodation':
												$oBookingObject = Ext_Thebing_School_Additionalcost::getInstance($oInquirySpecialPosition->type_id);
												$aSpecialInfo[] = $oSpecial->name . ': ' . $oBookingObject->getName($oSchool->getLanguage());
												break;
											case '': // Fällt jeder Rein (Einmaliger Rabatt)
												$aSpecialInfo[] = $oSpecial->name;
												break;
										}
									}
								}


							}
						}
					
						// Formatieren
						if(count($aSpecialInfo) > 0){
							$oUl = new Ext_Gui2_Html_Ul();
							
							foreach((array)$aSpecialInfo as $sInfo){		
								$oLi = new Ext_Gui2_Html_Li();
								$oLi->setElement($sInfo);
								$oUl->setElement($oLi);
							}
							$sSpecialInfo = $oUl->generateHTML();
						}
						
						$aData['special_info'] = $sSpecialInfo;


					// E-Mails für (manuellen) wiederholbaren Bereich
					\Ext_Thebing_Inquiry_Gui2::setEditDialogDataContactEmails($oInquiry, $aData);

					if($oCustomer instanceof Ext_TS_Inquiry_Contact_Abstract) {
						$aData['student_photo'] = $oCustomer->getPhoto();
					}

					// Verknüpfung Nationalität / Muttersprache nur bei neuen Buchungen
					if($oInquiry->id == 0) {
						$aData['mothertongues_by_nationality'] = Ext_Thebing_Nationality::getMotherTonguesByNationality();
					}
					
					break;
				case 'transfer_data':

					// Prüfen ob Tab editierbar sein darf
					$bReadonly = false;
					if(
						is_object($oInquiry) &&
						!$oInquiry->checkIfCategoryIsEditable('transfer')
					){
						$bReadonly = true;
					}else{
						$bReadonly = $aData['tabs'][4]['readonly'];
					}

					$aTabData['html']	.= Ext_Thebing_Inquiry_Gui2_Html::getIndividualTransferTabHtml($oDialogData, $aSelectedIds, false, $bReadonly);

					## START Zusatzinfos der Reiseziele mitschicken
					$aData['transfer_location_terminals'] = Ext_TS_Transfer_Location_Terminal::getGroupedTerminals();
					## ENDE

					## START Transferselect sperren und nicht editierbar machen wenn readonly oder Schülerliste
						if($bReadonly){
							$bDisableTransferSelect = 1;
						}else{
							$bDisableTransferSelect = 0;
						}
						$aData['bDisableTransferSelect'] = $bDisableTransferSelect;
					## ENDE
					
					## START Transfer Bezahlungen
						$aPayments = array();
						if(is_object($oInquiry)){
							$aTransfers = $oInquiry->getTransfers('', true);
							
							foreach((array)$aTransfers as $oTransfer){
								$aPaymentsTemp = $oTransfer->getJoinedObjectChilds('accounting_payments_active');
								
								if(!empty($aPaymentsTemp)){
									$aPayments[$oTransfer->id] = 1;
								}
							}
						}

					$aData['aTransferPayments'] = (array)$aPayments;
					break;
				case 'course_data':
					
					// Prüfen ob Tab editierbar sein darf
					$bReadonly = false;
					if(
						is_object($oInquiry) &&
						!$oInquiry->checkIfCategoryIsEditable('course')
					) {
						$bReadonly = true;
					} else {
						$bReadonly = $aData['tabs'][1]['readonly'];
					}
	
					$aTabData['html'] .= Ext_Thebing_Inquiry_Gui2_Html::getCourseTabHTML($oDialogData, $aSelectedIds, $bReadonly);
					$aData['course_data'] = \Ext_TS_Inquiry_Index_Gui2_Data::getCourseDialogData($oSchool);
					$aData['course_lessons_units'] = collect(\TsTuition\Enums\LessonsUnit::cases())
						->mapWithKeys(fn ($case) => [$case->value => $case->getLabelText($this->_oGui->getLanguageObject())])
						->toArray();

					$aData['additionalservices_course'] = Ext_Thebing_Client::getAdditionalServices('course', $oSchool,  true, false, null, false, false);

					$aData['course_languages'] = Ext_Thebing_Tuition_LevelGroup::getInstance()->getArrayList(true, 'name_'.$interfaceLanguage);
					
					if(
						is_object($oInquiry) &&
						$oInquiry instanceof Ext_TS_Inquiry
					) {
						$aInquiryCourses		= $oInquiry->getCourses(true, true, true, false);
					} else {
						$aInquiryCourses		= array();
					}

					// Durch Schulferien gesplittete Kurse anzeigen
					$aHolidayData = array();
					// Durch Schülerferien gesplittete  Kurse anzeigen
					$aCustomerHolidayData = array();
					// Zahlungen für Kurs vorhanden
					$aPayments = array();
		
					foreach($aInquiryCourses as $oInquiryCourse){

						// Zahlungen Kurs
						$aPaymentsTemp = $oInquiryCourse->checkPaymentStatus();
					
						if(!empty($aPaymentsTemp)){
							$aPayments[$oInquiryCourse->id] = 1;
						}

					}
				
					// Zahlungen Kurse
					$aData['aCoursePayments'] = (array)$aPayments;
				
					break;
				case 'accommodation_data':

					// Prüfen ob Tab editierbar sein darf
					$bReadonly = false;
					if(
						is_object($oInquiry) &&
						!$oInquiry->checkIfCategoryIsEditable('accommodation')
					){
						$bReadonly = true;
					}else{
						$bReadonly = $aData['tabs'][2]['readonly'];
					}

					$aTabData['html']	.= Ext_Thebing_Inquiry_Gui2_Html::getAccommodationTabHTML($oDialogData, $aSelectedIds, $bReadonly);
					
					// Mitschicken von Hostfamilies
					$aData['aHostFamilies'] = $oSchool->getAccommodationList(true, 1);

					$aData['additionalservices_accommodation'] = Ext_Thebing_Client::getAdditionalServices('accommodation', $oSchool,  true, null, null, false, false);

					## START AnreiseZeit der Unterkünfte
						//$aData['aAccTimes'] = $oSchool->getAccommodationTime();
					## ENDE

					## START Raumarten passend zur Unterkunft
						// Alle Unterkünfte
						$aData['aAccRooms'] = $oSchool->getAccommodationRoomCombinations();
					## ENDE

					## START Verpflegung passend zur Raumart
						$aData['aRoomMeals'] = $oSchool->getAccommodationMealCombinations();
					## ENDE
						
//					// Schülerferien
//					$aCustomerHolidayData = array();
				
					if(is_object($oInquiry)){
						$aInquiryAccommodationObjects = $oInquiry->getAccommodations(true);
					}
					
					// Zahlungen für Unterkünfte vorhanden
					$aPayments = array();
					
					foreach((array)$aInquiryAccommodationObjects as $oInquiryAccommodation){
						
						$iCategoryId	= (int)$oInquiryAccommodation->accommodation_id;
						$iRoomTypeId	= (int)$oInquiryAccommodation->roomtype_id;
						$iMealId		= (int)$oInquiryAccommodation->meal_id;
						
						// Zahlungen Unterkunft
						$aPaymentsTemp = $oInquiryAccommodation->checkPaymentStatus();
						if(!empty($aPaymentsTemp)){
							$aPayments[$oInquiryAccommodation->id] = 1;
						}
						
//						// Schülerferien
//						$aStudentAccommodationHolidayInfos = Ext_Thebing_Inquiry_Holidays::_getHolidaySplittings($oInquiryAccommodation->id, 'left', 'accommodation');
//
//						if(count($aStudentAccommodationHolidayInfos) > 0){
//							$aCustomerHolidayData[] = $oInquiryAccommodation->getAccommodationCategoryWithRoomTypeAndMeal(false);
//						}
						
						if(
							!array_key_exists($iCategoryId, $aData['aAccRooms'])
						){
							$aData['aAccRooms'][$iCategoryId] = array();
						}

						if(
							!array_key_exists($iCategoryId, $aData['aRoomMeals'])
						){
							$aData['aRoomMeals'][$iCategoryId] = array();
						}
						
						if(
							!in_array($iRoomTypeId, $aData['aAccRooms'][$iCategoryId])
						){
							$aData['aAccRooms'][$iCategoryId][] = $iRoomTypeId;
						}
						
						if(
							!array_key_exists($iRoomTypeId, $aData['aRoomMeals'][$iCategoryId])
						){
							$aData['aRoomMeals'][$iCategoryId][$iRoomTypeId] = array();
						}
						
						if(
							!in_array($iMealId, $aData['aRoomMeals'][$iCategoryId][$iRoomTypeId])
						){
							$aData['aRoomMeals'][$iCategoryId][$iRoomTypeId][] = $iMealId;
						}
					}
		
//					$aData['aAccommodationCustomerHolidayInfo'] = (array)$aCustomerHolidayData;
					// Zahlungen Unterkünfte
					$aData['aAccommodationPayments'] = (array)$aPayments;
					break;
				case 'matching_data':

					$bReadonly = false;
					if(
						is_object($oInquiry) &&
						!$oInquiry->checkIfCategoryIsEditable('matching')
					){
						$bReadonly = true;
					}else{
						$bReadonly = $aData['tabs'][3]['readonly'];
					}

					$sHtml = Ext_Thebing_Inquiry_Gui2_Html::getIndividualMatchingTabHtml($oDialogData, $aSelectedIds);
					$aTabData['html'] = $sHtml . $aTabData['html'];

					// Matching Informationen
					if($oInquiry){
						$aResult = $oInquiry->getMatchingInformations();
						$aData['sMatchingInformation'] = implode("\n", $aResult);
					}

					// Zusammenreisende Schüler
					$aRoomSharingList	= array();
					$aSharedInquiries	= array();

					if(
						is_object($oInquiry) &&
						$oInquiry instanceof Ext_TS_Inquiry
					){ 
						$aSharedInquiries = (array)$oInquiry->getRoomSharingCustomers();
					}
					
					//
					foreach ( $aSharedInquiries as $iSharedInquiryId ) {
						$oSharedInquiry = new Ext_TS_Inquiry((int)$iSharedInquiryId);
						$oSharedCustomer = $oSharedInquiry->getCustomer();

						$aTemp = array(
								"customerNumber"  => $oSharedCustomer->getCustomerNumber(),
								"name"            => $oSharedCustomer->lastname.", ".$oSharedCustomer->firstname,
								"id"              => $oSharedInquiry->id
						);
						$aRoomSharingList[] = $aTemp;
					}

					$aData['aRoomSharingList'] = $aRoomSharingList;

					## START "Zusammenreisende Schüler"-suche sperren und nicht editierbar machen wenn readonly oder Schülerliste
						if($bReadonly){
							$bDisableMatchingSearch = 1;
						}else{
							$bDisableMatchingSearch = 0;
						}
						$aData['bDisableMatchingSelect'] = $bDisableMatchingSearch;  
					## ENDE
					break;
				case 'visum_data':
					// Abhängigkeiten zum Ausblenden der Felder
					$aData['visa_status_flex_fields'] = Ext_Thebing_Visum::getVisaStatusListWithFlexFieldIds();
					break;
				case 'upload_data':
					break;
				case 'tuition_data':
					$aTabData['html']	.= Ext_Thebing_Inquiry_Gui2_Html::getTuitionTabHtml($aSelectedIds);
					break;
				case 'holiday_data':
					$aHolidays = [];
					if($oInquiry instanceof Ext_TS_Inquiry) {
						$aHolidays = $oInquiry->getJoinedObjectChilds('holidays', true);
					}
					$aTabData['html'] .= Ext_Thebing_Inquiry_Gui2_Html::getHolidayTabHtml($oDialogData, $aSelectedIds, $aHolidays, $aTabData['readonly']);
					break;
				case 'insurance_data':
					$aInsurances	= $this->_getInsurancesList($iInquiryId);
					$aTabData['html']	.= Ext_Thebing_Inquiry_Gui2_Html::getInsuranceTabHtml($oDialogData, $aInsurances, $aSelectedIds, $aData['tabs'][1]['readonly']);
					$aInsurancesDD		= Ext_Thebing_Insurances_Gui2_Insurance::getInsurancesListForInbox(true);
					$aData['aInsurancesTypeLinks'] = $aInsurancesDD;
					break;
				case 'activity_data':

					$aTabData['html'] .= Ext_Thebing_Inquiry_Gui2_Html::getActivityTabHtml($oDialogData, $aSelectedIds, $aData['tabs'][1]['readonly']);

					$aData['activity_config'] = \TsActivities\Entity\Activity::getRepository()->getConfigMap($oSchool);

					break;
				case 'sponsoring_data':
					$aTabData['html'] .= Ext_Thebing_Inquiry_Gui2_Html::getSponsoringTabHtml($this->_oGui, $oDialogData, $aSelectedIds, $aData['tabs'][1]['readonly']);
					break;
			}
		}

		$aData['title'] = $sTitle;
		$aData['bundled_course_levels'] = Ext_Thebing_Tuition_Course::getLevelGroupedCourses();
		
		System::wd()->executeHook('ts_inquiry_dialog_data', $aData, $oDialogData, $this->oWDBasic);

		return $aData;
	}

	/**
	 * Funktion speichert den Studentrecordx
	 *
	 * Niemals parent aufrufen!
	 */
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave=true, $sAction='edit', $bPrepareOpenDialog = true) {
		global $_VARS;

		// SPeicherfehler leeren
		$this->_aSaveErrors = array();
        $aSelectedIds   = (array)$aSelectedIds;
		$iInquiryID     = reset($aSelectedIds);
		
		// Bei Transferliste IDs von inquiry_transfer Ids auf Inquiry Ids umschreiben
		$this->checkInquiryId($iInquiryID, $aSelectedIds);
        $_VARS['id'] = array($iInquiryID);

        DB::begin('Ext_TS_Inquiry_Saver');

		$oRequest = new MVC_Request();
        $oRequest->add($_VARS);
        $oInquirySaver = new Ext_TS_Inquiry_Saver($oRequest, $this->_oGui, $bSave);
        $oInquirySaver->save();
        $oObject = $oInquirySaver->getObject();
        $oInquiry = $oObject->getObject();
        $this->oWDBasic = $oInquiry;
        $aTransfer = $oInquirySaver->getRequestData();

		// Tuition-Index aktualisieren
		// Wenn Fehler aufgetreten sind, könnten neue Kurse im Cache existieren, aber für ID 0 gibt es keinen Tuition-Index
		if(!$oInquirySaver->hasErrors()) {
			DB::commit('Ext_TS_Inquiry_Saver');

			// Achtung: Redundant mit Ext_Thebing_Inquiry_Gui2_Group::saveEditDialogData()
			if($bSave) {
				$oTuitionIndex = new Ext_TS_Inquiry_TuitionIndex($oInquiry);
				$oTuitionIndex->update();
			}
		} else {
			DB::rollback('Ext_TS_Inquiry_Saver');
		}

		return $aTransfer;

	}

	public function getCameryDialogData(&$oDialog, $aSelectedIds) {

		/* @var $oInquiry Ext_TS_Inquiry */
		$oInquiry = $this->getWDBasicObject($aSelectedIds);
		
		$oCustomer = $oInquiry->getCustomer();
		
		$sPhoto = null;
		if($oCustomer instanceof Ext_TS_Inquiry_Contact_Traveller) {
			$sPhoto = $oCustomer->getPhoto();
		}

		$oDialog = new Ext_Gui2_Dialog($this->t('Foto für "{customer_name}" erstellen'));
		$oDialog->width = 1050;
		$oDialog->height = 620;
		$oDialog->no_scrolling = 1;
		$oDialog->sDialogIDTag = 'CAMERA_';

		$oDialog->aButtons = [
			[
				'label' => $this->t('Foto erstellen'), 
				'task' => 'snap',
				'default' => true
			]
		];
		
		$aData = $oDialog->generateAjaxData($aSelectedIds, $this->_oGui->hash);

		$aData['html'] = '<div id="camera_container" style="margin-left: 10px; margin-top: 15px; width: 640px; float: left; background-color: #f7f7f7; position: relative;">
	<video id="video" width="640" height="480" autoplay></video>
	<div id="camera_frame" style="border: 5px solid #fff; width: 280px; height: 360px; position: absolute; top: 50px; left: 180px; cursor: move;"></div>
</div>
<div style="margin-top: 15px; width: 350px; float: left; margin-left: 20px;">
	<input type="hidden" id="save_camera" name="save[camera]" value="">
	<canvas id="canvas_preview" width="350" height="450" style="background-color: #f7f7f7;"></canvas>
	<canvas id="canvas" width="700" height="900" style="display:none;"></canvas>
</div>
<div style="clear:both;"></div>';

		$aData['no_scrolling'] = 1;
		if($sPhoto !== null) {
			$aData['js'] = '
				var canvas_preview = document.getElementById("canvas_preview");
				var context_preview = canvas_preview.getContext("2d");
				var imageObj = new Image();

				imageObj.onload = function() {
					context_preview.drawImage(imageObj, 0, 0, 350, 450);
				};
				imageObj.src = "'.$sPhoto.'?r='.\Util::generateRandomString(8).'";';
		}
		
		return $aData;
	}
	
	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true) {
		global $_VARS;

		if($sAction == 'camera') {
			
			$oInquiry = $this->_getWDBasicObject($aSelectedIds);
			$oContact = $oInquiry->getTraveller();
			
			$sPhoto = str_replace('data:image/png;base64,', '', $aData['camera']);
			$sPhoto = str_replace(' ', '+', $sPhoto);
			$sPhoto = base64_decode($sPhoto);
			$sPhotoName = 'camera_photo.png';
			
			$sTmpFile = tempnam(sys_get_temp_dir(), 'camera_');

			$bSuccess = file_put_contents($sTmpFile, $sPhoto);
			
			$hTmpFile = fopen($sTmpFile, 'wb');
			fwrite($hTmpFile, $sPhoto);
			fclose($hTmpFile);

			$sError = $oContact->savePhoto($sPhotoName, $sTmpFile);

			if(empty($sError)) {

				unlink($sTmpFile);

				$aTransfer = array();
				$aTransfer['action'] = 'saveDialogCallback';
				$aTransfer['error'] = array();
				$aTransfer['success_message'] = $this->t('Das Foto wurde gespeichert.');

			}

		} elseif($sAction === 'copy') {

			$oSchool = Ext_Thebing_School::getInstance($aData['school_id']);

			$this->getWDBasicObject($aSelectedIds);

			$bError = false;

			foreach ($aSelectedIds as $iInquiryId) {
				$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);
				if ($oInquiry->hasGroup()) {
					$bError = true;
					break;
				}
			}

			if ($bError === true) {
				$aTransfer['action'] = 'showError';
				$aTransfer['error'] = array($this->t('Gruppenbuchungen können nicht kopiert werden!'));
			} else {

				foreach ($aSelectedIds as $iInquiryId) {
					$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);
					$oInquiry->copyToSchool($oSchool);
				}

				sort($aSelectedIds);

				$aTransfer = array();
				$aTransfer['action'] = 'closeDialogAndReloadTable';
				$aTransfer['data'] = [
					'id' => 'COPY_' . implode('_', $aSelectedIds)
				];
				if (count($aSelectedIds) > 1) {
					$aTransfer['success_title'] = $this->t('Buchungen kopieren');
					$aTransfer['success_message'] = $this->t('Die Buchungen wurden erfolgreich kopiert.');
				} else {
					$aTransfer['success_title'] = $this->t('Buchung kopieren');
					$aTransfer['success_message'] = $this->t('Die Buchung wurden erfolgreich kopiert.');
				}

			}

		} else if($sAction === 'change_inbox') {

			$bGroup = false;
			foreach ($aSelectedIds as $iInquiryId) {
				$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);
				if ($oInquiry->hasGroup()) {
					$bGroup = true;
					break;
				}
			}

			if ($bGroup) {
				$aTransfer['action'] = 'showError';
				$aTransfer['error'] = [$this->t('Gruppenbuchungen können nicht verschoben werden!')];
			} else {

				$oInbox = Ext_Thebing_Client_Inbox::getInstance($aData['inbox_id']);
				$aErrors = [];

				$bForce = (int)$_VARS['ignore_errors'] === 1;

				$oPersister = \WDBasic_Persister::getInstance();

				try {
					foreach ($aSelectedIds as $iInquiryId) {
						$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);
						$oInquiry->changeInbox($oInbox, $bForce);

						$oPersister->attach($oInquiry);
					}
				} catch (\RuntimeException) {
					$aErrors[] = [
						'message' => $this->t('Die aktuellen Nummernkreise stimmen nicht mehr mit den Einstellungen für die neue Inbox überein.'),
						'type' => 'hint'
					];
				}

				sort($aSelectedIds);

				$aTransfer = [];
				if (!empty($aErrors)) {
					$aTransfer['action'] = 'saveDialogCallback';
					$aTransfer['error'] = $aErrors;
					$aTransfer['data']['show_skip_errors_checkbox'] = 1;
				} else {
					$oPersister->save();

					$aTransfer['action'] = 'closeDialogAndReloadTable';
					$aTransfer['data'] = [
						'id' => 'CHANGE_INBOX_' . implode('_', $aSelectedIds)
					];
				}
			}

		} else {
			$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
		}
		
		return $aTransfer;
	}

	protected function getDialogHTML(&$sIconAction, &$oDialog, $aSelectedIds = array(), $sAdditional=false) {

		global $_VARS;
		$aData = array();
		switch($sIconAction) {
			case 'camera':
				$aData = $this->getCameryDialogData($oDialog, $aSelectedIds);
				break;
			case 'transfer_provider':
				
				$aProvider = array();
                $aPayments = array();
				foreach((array)$aSelectedIds as $iKey => $iInquiry_trasfer_id){
					$oTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($iInquiry_trasfer_id);
                    $aPayments += $oTransfer->accounting_payments;
					// Alle Schulprovider die den Transfer übernehmen könnten bzw. Unterkünfte
					$aProvider += $oTransfer->getTransferProvider();
				}
                
                if(!empty($aPayments)){
                    $oDialog = $this->_oGui->createDialog($this->t('Es ist ein Fehler aufgetreten!'), $this->t('Es ist ein Fehler aufgetreten!'));
                    $oDiv = $oDialog->createNotification($this->t('Es ist ein Fehler aufgetreten!'), $this->t('Der Transfer wurde bereits bezahlt! Der Anbieter kann nicht mehr verändert werden.'));
                    $oDialog->setElement($oDiv);
                    $oDialog->save_button = false;
                    $aData = $oDialog->generateAjaxData($aSelectedIds, $this->_oGui->hash);
                } else {						
                    $oDialog = Ext_TS_Pickup_Gui2_Data::getDialog($this->_oGui, $aSelectedIds);
                    $aData = $oDialog->generateAjaxData($aSelectedIds, $this->_oGui->hash);
                    $aData['data']['provider'] = $aProvider;
                    // Wenn es nur eine Transfer gewählt wurde können die gespeicherten genommen werden
                    $iProviderId = 0;
                    $iDriverId = 0;
                    $sProviderType = '';
                    if(count($aSelectedIds) == 1){
                        $oTransfer		= Ext_TS_Inquiry_Journey_Transfer::getInstance(reset($aSelectedIds));
                        $iProviderId	= $oTransfer->provider_id;
                        $iDriverId		= $oTransfer->driver_id;
                        $sProviderType	= $oTransfer->provider_type;
                    }

                    if($sProviderType == 'accommodation'){
                        $iProviderId = $iProviderId * -1;
                    }

                    $aData['data']['provider_id']	= $iProviderId;

                    $aData['data']['driver_id']		= $iDriverId;
                }
				
				break;
			case 'openProgressReport':
				$aSelectedIds	= (array)$aSelectedIds;
				$iSelectedId	= (int)reset($aSelectedIds);

				$dFrom			= Ext_Thebing_Format::ConvertDate($_VARS['filter']['search_time_from_1']);
				$dUntil			= Ext_Thebing_Format::ConvertDate($_VARS['filter']['search_time_until_1']);

				$oProgressReport	= new Ext_Thebing_Tuition_ProgressReport($iSelectedId, $dFrom, $dUntil);
				$oProgressReport->setTranslationPart($this->_oGui->gui_description);

				$aData['width']		= 960;
				$aData['height']	= 650;
				$aData['title']		= $this->t('Fortschrittsbericht');
				$aData['html']		= $oProgressReport->getDialogHtml();
				break;
			default:
				// Dialogdaten
				$aData = parent::getDialogHTML($sIconAction, $oDialog, $aSelectedIds, $sAdditional);
				if($sIconAction == 'transfer_provider_assign') {
					// Bisher angefragte Provider
					$aData['data']['provider_requested'] = json_encode(Ext_TS_Pickup_Gui2_Data::getProviderAssignFilter(false, false));
				}
				break;
		}

		return $aData;
	}

	/**
	 * @TODO Das ist doch total bescheuert, dass hier parent komplett umgangen wird
	 *
	 * Gibt die Daten für den Dialog zurück
	 *
	 * @param array $aSelectedIds
	 * @param array $aSaveData
	 * @return array
	 */
	protected function getEditDialogData($aSelectedIds, $aSaveData = array(), $sAdditional = false) {

		$sIconKey = self::getIconKey($sAdditional['action'], $sAdditional['additional']);

		$oDialog = $this->_getDialog($sIconKey);

		// Objekt kommt aus der gespeicherten GUI-Instanz, falls nicht klappt hier nix!

		/** @var Ext_TS_Inquiry $oInquiry */
		$oInquiry = $this->oWDBasic;

		$aData = $oInquiry->getEditDialogData($oDialog, $this->_oGui, $aSelectedIds, $aSaveData);

		return $aData;
	}

	/**
	 * Liefert ein Array mit dem Kürzel der aktuellen inbox
	 */
	public function getInboxData() {
		global $_VARS;

		$aInbox = array();
		$aInbox['short'] = '';

		$iInbox = (int)$this->_oGui->getOption('inbox_id');

		if($iInbox > 0) {
			$oClient	= Ext_Thebing_System::getClient();
			$aInboxList = $oClient->getInboxList();

			$aInbox = $aInboxList[$iInbox];
		}

		return $aInbox;
	}

	/*
	 *  Baut den Query zusammen und ruft die Daten aus der DB ab
	*/
	public function getTableQueryData($aFilter = array(), $aOrderBy = array(), $aSelectedIds = array(), $bSkipLimit=false) {

		// TODO schöner wäre es das irgendwie als Trait auszulagern
		if ($this->_bIgnoreTableQueryDataInclude === false) {
			global $_VARS, $user_data;
			// Ausgelagert um Die Klasse Übersichtlich zu halten
			include_once Util::getDocumentRoot().'system/extensions/thebing/inquiry/gui2/include.gettablequerydata.php';

			return $aResult;
		}

		return parent::getTableQueryData($aFilter, $aOrderBy, $aSelectedIds, $bSkipLimit);
	}

	/**
	 *
	 * Methode um eine einzelne Spalte einer Zeile zu updaten
	 */
	public function updateOne($aParams) {

		$mValue		= $aParams['value'];
		$iRowId		= $aParams['row_id'];
		$sColumn	= $aParams['column'];
		$sAlias		= $aParams['alias'];
		$sType		= $aParams['type'];
		
		if($sColumn == 'active' && $sAlias == 'ki' && $mValue == 0) {

			$oInquiry = new Ext_TS_Inquiry($iRowId);
			$oInquiry->delete();

			return true;
			
		} else {

			$bSuccess = parent::updateOne($aParams);
			return $bSuccess;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getTranslations($sL10NDescription) {

		$aData = parent::getTranslations($sL10NDescription);

		$aData['delete_question_inquiry'] = L10N::t('Buchungsposition löschen?', $sL10NDescription);
		$aData['new_course'] = L10N::t('Neuer Kurs', $sL10NDescription);
		$aData['new_accommodation'] = L10N::t('Neue Unterkunft', $sL10NDescription);
		$aData['new_activity'] = L10N::t('Neue Aktivität', $sL10NDescription);
		$aData['new_course_guide'] = L10N::t('Neuer Kurs', $sL10NDescription);
		$aData['new_accommodation_guide'] = L10N::t('Neue Unterkunft', $sL10NDescription);
		$aData['new_transfer'] = L10N::t('Neuer Transfer', $sL10NDescription);
		$aData['new_sponsoring_gurantee'] = L10N::t('Neue Finanzgarantie', $sL10NDescription);
		$aData['discount'] = L10N::t('Rabatt', $sL10NDescription);
		$aData['transferquestion'] = L10N::t('Neues Unterkunftsdatum für Transfer übernehm', $sL10NDescription);
		$aData['accommodationquestion'] = L10N::t('Neues Kursdatum für Unterkunft übernehm', $sL10NDescription);
		$aData['groupContact'] = L10N::t('Kunde ist bereits Gruppenmitglied', $sL10NDescription);
		$aData['group_customer_overwrite_denied'] = L10N::t('Das Überschrieben eines Gruppenmitgliedes ist leider nicht möglich. Bitte legen Sie einen entsprechenden Eintrag im Gruppendialog an. ', $sL10NDescription);
//		$aData['holidayCheck'] = L10N::t('Es sind Ferien mit diesem Eintrag verknüpft! Bitte entfernen Sie zuerst die Ferien bevor sie diesen Eintrag weiter bearbeiten!', $sL10NDescription);
		$aData['holiday_delete_question'] = L10N::t('Möchten Sie diese Ferien wirklich entfernen? Vorhandene Zuweisungen, die nicht den neuen Einstellungen entsprechen, werden gelöscht. Nach dem Löschen von Ferien findet bei geteilten Leistungen keine fortlaufende Preisberechnung mehr statt.', $sL10NDescription);
		$aData['holiday_delete_question_restore'] = L10N::t('Sollen die originalen Daten der Leistung wiederhergestellt werden? Nach dem Eintragen von Ferien können nach späterer, manueller Veränderung des Leistungszeitraums Fehler auftreten! ', $sL10NDescription);
		$aData['confirm_change_school'] = L10N::t('Durch das ändern der Schule gehen alle Kurs/Unterkunft/Transfer und Versicherungsdaten verlohren. Sind Sie sicher?', $sL10NDescription);
		$aData['course_info'] = L10N::t('Kurszeitraum', $sL10NDescription);
		$aData['confirm_course_period_change'] = L10N::t('Soll der aktuelle Zeitraum wirklich überschrieben werden?', $sL10NDescription);
		$aData['delete_position_payment'] = L10N::t('Buchungsposition kann nicht gelöscht werden. Es existieren Zahlungen.', $sL10NDescription);
//		$aData['holiday_school_split_info'] = L10N::t('Folgende Kurse wurden durch Schulferien geteilt', $sL10NDescription);
//		$aData['holiday_customer_course_split_info'] = L10N::t('Folgende Kurse wurden durch Schülerferien geteilt', $sL10NDescription);
//		$aData['holiday_customer_accommodation_split_info']	= L10N::t('Folgende Unterkünfte wurden durch Schülerferien geteilt', $sL10NDescription);
		$aData['delete_customer_question'] = L10N::t('Möchten Sie den Schüler der Gruppe komplett löschen? Ansonsten bleibt der Schüler als individuelle Buchung erhalten. Der Schüler wird keine Rechnung mehr besitzen.', $sL10NDescription);
		$aData['delete_last_customer_error'] = L10N::t('Der letzte, gespeicherte Schüler der Gruppe darf nicht gelöscht werden.', $sL10NDescription);
		$aData['delete_email_question'] = L10N::t('Möchten Sie diese E-Mail-Adresse wirklich löschen?', $sL10NDescription);
		$aData['camera_not_available'] = L10N::t('Ihre Webcam ist aktuell nicht verfügbar. Eventuell wird sie von einer anderen Anwendung verwendet.', $sL10NDescription);

		$aData = array_merge($aData, Ext_Thebing_Util::getPaymentTranslations());

		return $aData;

	}


	/**
	 * WRAPPER Ajax Request verarbeiten
	 *
	 * @todo das was hier aktuell passiert ist ganz übel!!! je nach dem werden
	 * manche sachen im include abgehandelt, und abhängig ob aTransfer leer ist
	 * oder nicht wird es ausgegeben, ob parent aufgerufen. Das ist scheiße!
	 *
	 * @param $_VARS
	 * @return unknown_type
	 */
	public function switchAjaxRequest($_VARS) {
		global $user_data;

		$aTransfer = array();

		if(!isset($_VARS['action'])) {
			$_VARS['action'] = null;
		}
		
		// Hier drin ist ein switch mit inquiry fällen
		if(
			(
				$_VARS['task'] != 'openDialog' &&
				$_VARS['action'] != 'additional_document' &&
				$_VARS['task'] != 'reloadTemplateLanguageSelect' &&
				$_VARS['task'] != 'reloadPositionsTable' &&
				$_VARS['task'] != 'savePositionDialog' &&
				$_VARS['task'] != 'openPositionDialog' &&
				$_VARS['task'] != 'deleteProformaDocument' &&
				$_VARS['task'] != 'markAsCanceled' &&
				$_VARS['task'] != 'getNewCommission' &&
				$_VARS['task'] != 'saveDialog' &&
				$_VARS['task'] != 'searchForHubspotContact' &&
				$_VARS['action'] != 'convertProformaDocument' &&
				$_VARS['task'] != 'updateIdentity' //Fall wird behandelt in Ext_Thebing_Gui2_Data, deshalb dürfen wir nicht in die include Datei rein, der Parent erledigt schon alles
			) || (
				$_VARS['task'] == 'saveDialog' &&
			(
					$_VARS['action'] == 'nettoAC' ||
					$_VARS['action'] == 'transfer_provider'	
				)
			)			
		) {
			include_once(Util::getDocumentRoot().'system/extensions/thebing/inquiry/gui2/include.switchajaxrequest.php');
		}

		// Data aus dem Array raushauen da dies immer default mässig gesetzt wird
		$aTemp = (array)$aTransfer;
		unset($aTemp['data']);
		unset($aTemp['number_format']);

		// wenn switch zugetroffen hat
		if(!empty($aTemp)){
			echo json_encode($aTransfer);
			return true;
		}

		// sonst parent ( hier wird ein echo gestartet ) 

		parent::switchAjaxRequest($_VARS);

	}

	// TODO Entfernen
	// TODO Muss das nicht in die Inquiry Class??
	// Habe ich ins Inquiry gepackt! Diese Fkt, also NICHT wieder verwenden MF
	protected function _getInsurancesList($iInquiryID) {
		$sSQL = "
			SELECT
				`ts_i_j_i`.*
			FROM
				`ts_inquiries_journeys_insurances` `ts_i_j_i` INNER JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`id` = `ts_i_j_i`.`journey_id` AND
					`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_i_j`.`active` = 1
			WHERE 
				`ts_i_j`.`inquiry_id` = :iInquiryID AND
				`ts_i_j_i`.`active` = 1
			ORDER BY
				`ts_i_j_i`.`id` DESC
		";
		$aSQL = array('iInquiryID' => (int)$iInquiryID);
		$aInsurances = DB::getPreparedQueryData($sSQL, $aSQL);

		return (array)$aInsurances;
	}

	protected function _getErrorMessage($sError, $sField, $sLabel='', $sAction=null, $sAdditional=null) { 

		$sMessage = '';
		$bTranslate = true;

		switch($sError){
			case 'ALLOCATED_HOLIDAY_CHECK':
				$sMessage = 'Für bereits zugewiesene Unterkünfte dürfen keine Ferien gebucht werden. Bitte löschen Sie zuerst die Zuweisung.';
				break;
			case 'HOLIDAY_CHECK':
				$sMessage = 'Schülerferien können nur für selektierte Positionen gebucht werden.';
				break;
			case 'HOLIDAY_CHECK_WEEKS':
				$sMessage = 'Schülerferien benötigen eine Wochenanzahl.';
				break;
			case 'HOLIDAY_CHECK_FROM':
				$sMessage = 'Schülerferien benötigen ein Startdatum.';
				break;
			case 'HOLIDAY_CHECK_UNTIL':
				$sMessage = 'Schülerferien benötigen ein Enddatum.';
				break;
			case 'HOLIDAY_ACC_ACTIVE':
				$sMessage = 'Für die Unterkunft wurden Ferien eingetragen, sie kann nicht deaktiviert werden.';
				break;
			case 'HOLIDAY_ACC_MEAL':
				$sMessage = 'Für die Unterkunft wurden Ferien eingetragen, die Mahlzeit kann nicht verändert werden.';
				break;
			case 'HOLIDAY_ACC_ROOM':
				$sMessage = 'Für die Unterkunft wurden Ferien eingetragen, der Raum kann nicht verändert werden.';
				break;
			case 'HOLIDAY_ACC_ID':
				$sMessage = 'Für die Unterkunft wurden Ferien eingetragen, sie kann nicht verändert werden.';
				break;
			case 'HOLIDAY_ACC_FROM':
				$sMessage = 'Für die Unterkunft wurden Ferien eingetragen, das Stardatum kann nicht verändert werden.';
				break;
			case 'HOLIDAY_ACC_UNTIL':
				$sMessage = 'Für die Unterkunft wurden Ferien eingetragen, das Enddatum kann nicht verändert werden.';
				break;
			case 'HOLIDAY_ACC_VISIBLE':
				$sMessage = 'Für die Unterkunft gibt es Zahlungen. Sie kann nicht deaktiviert werden.';
				break;
			case 'HOLIDAY_COURSE_ACTIVE':
				$sMessage = 'Für den Kurs wurden Ferien eingetragen, er kann nicht deaktiviert werden.';
				break;
			case 'HOLIDAY_COURSE_ID':
				$sMessage = 'Für den Kurs wurden Ferien eingetragen, er kann nicht verändert werden.';
				break;
			case 'HOLIDAY_COURSE_FROM':
				$sMessage = 'Für den Kurs wurden Ferien eingetragen, das Stardatum kann nicht verändert werden.';
				break;
			case 'HOLIDAY_COURSE_UNTIL':
				$sMessage = 'Für den Kurs wurden Ferien eingetragen, das Enddatum kann nicht verändert werden.';
				break;
			case 'HOLIDAY_COURSE_VISIBLE':
				$sMessage = 'Für den Kurs gibt es Zahlungen. Sie kann nicht deaktiviert werden.';
				break;
			case 'ACC_DATA_CHANGE':
				$sMessage = 'Die Unterkunftsdaten haben sich geändert. Vorhandene Zuweisungen, die nicht den neuen Einstellungen entsprechen werden gelöscht.';
				break;
			case 'COURSE_DATA_CHANGE':
				$sMessage = 'Die Kursdaten haben sich geändert. Vorhandene Zuweisungen, die nicht den neuen Einstellungen entsprechen werden gelöscht.';
				break;
			case 'VISUM_UNTIL':
				$sMessage = 'Visum gültig bis muss größer sein als Visum gültig von.';
				break;
			case 'PASS_UNTIL':
				$sMessage = 'Fälligkeitsdatum muss größer sein als Ausstellungsdatum.';
				break;
			case 'INVALID_PAYMENT_METHOD':
				$sMessage = 'Die Bezahlmethode stimmt nicht mit der letzten Rechnungsversion überein. Überprüfen Sie bitte Ihre Eingaben.';
				break;
			case 'TRANSFER_PROVIDER_PAYED':
				$sMessage = 'Der Provider kann erst geändert werden nachdem alle Transferzahlungen gelöscht wurden.';
				break;
			case 'TRANSFER_PAYED':
				$sMessage = 'Die Transferart kann erst geändert werden nachdem die Transferzahlungen gelöscht wurden.';
				break;
			case 'CONVERT_PROFORMA_ERROR':
				$sMessage = 'Proforma "%s" konnte nicht umgewandelt werden. Bitte überprüfen Sie das Dokument.';
				break;
			case 'SYSTEM':
				$sMessage = 'Es ist ein Systemfehler aufgetreten, der Vorfall wurde gemeldet.';
				break;
			case 'SAVE_TITLE':
				$sMessage = 'Fehler beim Speichern';
				break;
			case 'COURSE_NOT_VALID':
				$sMessage = 'Der Kurs "%s" ist für diesen Zeitraum nicht mehr gültig.';
				break;
			case 'ACCOMMODATION_CATEGORY_NOT_VALID':
				$sMessage = 'Die Unterkunftskategorie "%s" ist für diesen Zeitraum nicht mehr gültig.';
				break;
			case 'ROOMTYPE_NOT_VALID':
				$sMessage = 'Die Raumart "%s" ist für diesen Zeitraum nicht mehr gültig.';
				break;
			case 'MEAL_NOT_VALID':
				$sMessage = 'Die Verpflegung "%s" ist für diesen Zeitraum nicht mehr gültig.';
				break;
			case 'PAYMENT_EXISTS_COURSE':
				$sMessage = 'Für den Kurs %s wurden bereits Lehrer bezahlt.';
				break;
			case 'INVALID_AIRPORT':
				$sMessage = 'Der gewünschte "%s" steht an dem angegebenen Tag nicht zur Verfügung.';
				break;
			case 'ACCOMMODATION_ALLOCATIONS_EXISTS':
				$sMessage = 'Für die Unterkunft existieren noch Zuweisungen.';
				break;
			case 'PAYMENTS_EXIST':
				$sMessage = 'Es existieren noch Zahlungen.';
				break;
			case 'ATTENDANCES_EXIST':
				$sMessage = 'Es existieren noch Anwesenheiten für den Kurs "%s".';
				break;
			default:				
				$sMessage = parent::_getErrorMessage($sError, $sField, $sLabel);
				$bTranslate = false;
		}

		if($bTranslate === true){
			$sMessage = $this->t($sMessage);
			if(!empty($sLabel)){
				$sMessage = sprintf($sMessage, $sLabel);
			}
		}

		return $sMessage;

	}
	/**
	 * @param Ext_Gui2 $oGui
	 * @return Ext_Gui2_Dialog
	 */
	public static function createGroupDialog(Ext_Gui2 $oGui) {

		$oFactory = new Ext_Gui2_Factory('ts_inquiry_group');
		$oChildGui = $oFactory->createGui('inquiry', $oGui, [
			'inbox_id' => $oGui->getOption('inbox_id')
		]);

		$oDialog = $oGui->createDialog($oGui->t('Gruppen'), $oGui->t('Gruppen'), $oGui->t('Gruppen'));
		$oDialog->width	= 1100;
		$oDialog->height = 1200;
		$oDialog->sDialogIDTag = 'GROUP_';

		$oDialog->setElement($oChildGui);

		return $oDialog;

	}

	public function _validateFlexField($aFieldData, $mValue) {
		global $_VARS;

		// Flex-Felder gehören ggf. zu Visum-Status und dürfen nur bei Auswahl vom Status validiert werden
		$aVisaStatusFlexFields = \Ext_Thebing_Visum::getVisaStatusListWithFlexFieldIds();
		foreach($aVisaStatusFlexFields as $iStatusId => $aFlexFieldIds) {
			if(in_array($aFieldData['id'], $aFlexFieldIds)) {
				if($iStatusId === (int)$_VARS['save']['status']['ts_ijv']) {
					return parent::_validateFlexField($aFieldData, $mValue);
				}

				// Flex-Feld gehört zu diesem Status, aber Status nicht ausgewählt: Nicht validieren
				return [];
			}
		}

		return parent::_validateFlexField($aFieldData, $mValue);

	}

	/**
	 * Daten setzen für (manuellen) wiederholbaren E-Mail-Bereich, da das nicht anders geht (erster Tab ist statisch)
	 *
	 * @see \Ext_Thebing_Inquiry_Gui2_Html::setContactEmailContainer()
	 * @param Ext_TS_Inquiry_Abstract|null $oInquiry
	 * @param array $aData
	 */
	public static function setEditDialogDataContactEmails($oInquiry, &$aData) {

		$aData['contact_emails'] = [];
		if($oInquiry instanceof Ext_TS_Inquiry_Abstract) {
			$aEmailAddresses = $oInquiry->getFirstTraveller()->getEmailAddresses(true);
			foreach($aEmailAddresses as $oEmail) {
				// Hier muss auf active geprüft werden, da gelöschte E-Mails noch im Objekt sind (kein delete()-Aufruf im Saver)
				if($oEmail->active) {
					$aData['contact_emails'][] = ['id' => $oEmail->id, 'email' => $oEmail->email];
				}
			}
		}

	}

	public function requestCourseInfo($aData) {

		$label = $this->t('Bitte wähle zuerst einen Kurs');
		$dates = $disabledDates = $holidays = $publicHolidays = [];
		$oDateRange = null;

		if(!empty($aData['course_id'])) {

			$oCourse = \Ext_Thebing_Tuition_Course::getInstance($aData['course_id']);

			if($oCourse->isProgram()) {

				/*$oPrograms = $oCourse->getPrograms();

				if($oPrograms->isEmpty()) {
					$sInfo = $this->t('Keine Leistungen zu diesem Programm gefunden');
				} else {

					$sInfo = $oPrograms->map(function (\TsTuition\Entity\Course\Program $oProgram) {
							return '<a href="javascript:void(0);"
								data-from="'.Ext_Thebing_Format::LocalDate($oProgram->getFrom()).'"
								data-weeks="1"
								data-until="'.Ext_Thebing_Format::LocalDate($oProgram->getUntil()).'"
								data-program="'.$oProgram->getId().'">'.Ext_Thebing_Format::LocalDate($oProgram->getFrom(), null, true);
						})
						->implode(', ');
				}*/

			} else {

				$oDateRange = Ext_TS_Frontend_Combination_Inquiry_Helper_Services::getCourseDurationFromAndUntil($oCourse);

				if(
					$oCourse->avaibility == \Ext_Thebing_Tuition_Course::AVAILABILITY_ALWAYS ||
					$oCourse->avaibility == \Ext_Thebing_Tuition_Course::AVAILABILITY_UNDEFINED ||
					$oCourse->avaibility == \Ext_Thebing_Tuition_Course::AVAILABILITY_ALWAYS_EACH_DAY ||
					$oCourse->avaibility == \Ext_Thebing_Tuition_Course::AVAILABILITY_STARTDATES
				) {
					$oSchool = $oCourse->getSchool();

					//if($oCourse->avaibility == \Ext_Thebing_Tuition_Course::AVAILABILITY_ALWAYS_EACH_DAY) {
					//	$label = $this->t('Immer verfügbar (jeden Tag)');
					//} elseif($oCourse->avaibility != \Ext_Thebing_Tuition_Course::AVAILABILITY_STARTDATES) {
					//	$label = $this->t('Immer verfügbar (am Kursstarttag)');
					//} else {

						$aStartDates = $oCourse->getStartDatesWithDurations($oDateRange->from, $oDateRange->until);

						if(!empty($aData['level_id'])) {
							$iLevel = (int)$aData['level_id'];
							$aStartDates = array_filter($aStartDates, function (CourseStartDate $oStartDate) use ($iLevel) {
								if(empty($oStartDate->levels)) {
									return true;
								}
								return in_array($iLevel, $oStartDate->levels);
							});
						}

						if(!empty($aData['courselanguage_id'])) {
							$iCourselanguage = (int)$aData['courselanguage_id'];
							$aStartDates = array_filter($aStartDates, function (CourseStartDate $oStartDate) use ($iCourselanguage) {
								if(empty($oStartDate->courselanguages)) {
									return true;
								}
								return in_array($iCourselanguage, $oStartDate->courselanguages);
							});
						}

						if(empty($aStartDates)) {
							$label = $this->t('Nicht verfügbar');
						} else {
							if($oCourse->avaibility == \Ext_Thebing_Tuition_Course::AVAILABILITY_ALWAYS_EACH_DAY) {
								$label = $this->t('Immer verfügbar (jeden Tag)');
							} elseif($oCourse->avaibility != \Ext_Thebing_Tuition_Course::AVAILABILITY_STARTDATES) {
								$label = $this->t('Immer verfügbar (am Kursstarttag)');
							} else {
								$label = $this->t('Startdaten');
							}

							foreach($aStartDates as $oStartDate) {
								$dates[] = [
									'date' => $oStartDate->start->toDateString(),
									'label' => Ext_Thebing_Format::LocalDate($oStartDate->start),
									'weeks' => $oStartDate->minDuration,
									'popover' => ($oStartDate->minDuration != $oStartDate->maxDuration)
										? sprintf('%d - %d%s', $oStartDate->minDuration, $oStartDate->maxDuration, $this->t('W'))
										: null
								];
							}
						}
					//}

					$oGenerator = new \TsTuition\Generator\StartDatesGenerator($oCourse, \Carbon\Carbon::instance($oDateRange->from), \Carbon\Carbon::instance($oDateRange->until));

					$notAvailableDates = $oGenerator->generateNotAvailableDates();

					if($notAvailableDates->isNotEmpty()) {
						foreach($notAvailableDates as $notAvailableDate) {
							$disabledDates[] = [
								'from' => $notAvailableDate->start_date,
								'until' => $notAvailableDate->end_date,
								'disabled' => true
							];
						}
					}

					$schoolHolidays = $oSchool->getSchoolHolidays($oDateRange->from, $oDateRange->until);
					$schoolPublicHolidays = $oSchool->getHolidays($oDateRange->from->getTimestamp(), $oDateRange->until->getTimestamp(), false);

					foreach ($schoolHolidays as $holiday) {
						$holidays[] = ['from' => $holiday->from, 'until' => $holiday->until];
					}

					foreach ($schoolPublicHolidays as $publicHoliday) {
						if ($publicHoliday['annual']) {
							for($i = $oDateRange->from->format('Y'); $i <= $oDateRange->until->format('Y'); $i++) {
								$publicHolidays[] = ['date' => $i.substr($publicHoliday['date'], 4), 'popover' => $publicHoliday['name']];
							}
						} else {
							$publicHolidays[] = ['date' => $publicHoliday['date'], 'popover' => $publicHoliday['name']];
						}
					}

				} elseif($oCourse->avaibility == \Ext_Thebing_Tuition_Course::AVAILABILITY_NEVER) {
					$label = $this->t('Nicht verfügbar');
				}

			}

		}

		$aTransfer = [
			'action' => 'writeCourseInfo',
			'container' => $aData['container'],
			'courseId' => (int)$aData['course_id'],
			'label' => $label,
			'minDate' => $oDateRange?->from->format('Y-m-d'),
			'maxDate' => $oDateRange?->until->format('Y-m-d'),
			'dates' => $dates,
			'disabledDates' => $disabledDates,
			'holidays' => $holidays,
			'publicHolidays' => $publicHolidays,
			'locale' => \System::getInterfaceLanguage()
		];

		return $aTransfer;
	}

	/**
	 * @see \Ext_Thebing_Inquiry_Gui2::saveDialogData
	 */
	static public function getCopyDialog(Ext_Gui2 $oGui) {
		
		$oDialog = $oGui->createDialog($oGui->t('Buchung "{number}" kopieren'), 'n/a', $oGui->t('Buchungen kopieren'));
		$oDialog->width	= 600;
		$oDialog->height = 300;
		$oDialog->sDialogIDTag = 'COPY_';

		$oClient = Ext_Thebing_Client::getFirstClient();
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$aSchools = $oClient->getSchools(true);
		unset($aSchools[$oSchool->id]);
		$aSchools = Ext_Thebing_Util::addEmptyItem($aSchools);
		
		$oElement = $oDialog->createRow($oGui->t('Schule'), 'select', [
				'db_column' => 'school_id',
				'select_options' => $aSchools,
				'required' => true,
				'class'=> 'school_select',
		]);
		
		$oDialog->setElement($oElement);

		return $oDialog;
	}

	static public function getChangeInboxDialog(Ext_Gui2 $oGui) {

		$oDialog = $oGui->createDialog($oGui->t('Buchung "{number}" verschieben'), 'n/a', $oGui->t('Buchungen verschieben'));
		$oDialog->width	= 600;
		$oDialog->height = 300;
		$oDialog->sDialogIDTag = 'CHANGE_INBOX_';

		$aInboxlist = Ext_Thebing_Client::getInstance()->getInboxList('use_id', true, true);

		$oElement = $oDialog->createRow($oGui->t('Inbox'), 'select', [
			'db_column' => 'inbox_id',
			'select_options' => $aInboxlist,
			'required' => true,
			'class'=> 'inbox_select',
		]);

		$oDialog->setElement($oElement);

		return $oDialog;
	}

	/**
	 * Button: Zahlungsbeträge neu zuordnen
	 */
	public function confirmReallocateAmounts() {

		DB::begin(__METHOD__);

		$inquiryId = \Illuminate\Support\Arr::first($this->request->input('id'));
		$ids = [$inquiryId];

		// Bei einer Gruppe ist das ein Array aus allen Mitgliedern, daher darf nur eine Buchung ausgewählt sein
		$paymentData = \Ext_Thebing_Inquiry_Payment::buildPaymentDataArray($ids, 'inquiry');

		/** @var \Illuminate\Support\Collection<Ext_TS_Inquiry> $inquiries */
		$inquiries = array_column($paymentData, 'inquiry'); /** @var Ext_TS_Inquiry[] $inquiries */
		$inquiry = \Illuminate\Support\Arr::first($inquiries); /** @var Ext_TS_Inquiry $inquiry */

		if (empty($inquiry)) {
			throw new RuntimeException('No inquiry found');
		}

		$items = array_reduce($paymentData, function (array $carry, array $data) {
			$carry += array_reduce($data['documents'], function (array $carry, array $document) {
				$carry += $document['items'];
				return $carry;
			}, []);
			return $carry;
		}, []);

		$payments = $inquiry->getPayments(true);

		foreach ($payments as $payment) {
			$payment->reallocateAmounts($items);
		}

		foreach ($inquiries as $inquiry) {
			$inquiry->calculatePayedAmount();
		}

		DB::commit(__METHOD__);

		return [
			'action' => 'showSuccessAndReloadTable',
			'success_title' => $this->t('Zahlungsbeträge neu zuordnen'),
			'message' => [$this->t('Die Zahlungsbeträge wurden neu zugeordnet.')]
		];

	}

	// TODO @Marlon Was soll das hier?
	public function searchForHubspotContact()
	{
		$api = new \TsHubspot\Service\Api();

		$helper = new \TsHubspot\Service\Helper\General();

		$searchCriteria = new \HubSpot\Client\Crm\Contacts\Model\PublicObjectSearchRequest();

		$config = new Ext_TS_Config();
		$hubspotInquiryFieldRows = $config->hubspot_inquiry_fields;

		// fideloFieldKey => potentialHubspotFieldKey
		$searchableFields = [
			'firstname' => 'firstname',
			'lastname' => 'lastname',
			'birthday' => 'date_of_birth',
			'email' => 'email',
			'agency_id' => 'company',
			'gender' => 'gender',
		];

		// Alle Reihen der externen App Einstellungen zur Buchung durchgehen und Hubspot-Property-Name zu dem jeweiligen
		// Fidelo-Feld-Key finden:
		$hubspotPropertyNames = [];
		foreach ($hubspotInquiryFieldRows as $hubspotInquiryFieldRow) {
			foreach ($searchableFields as $fideloFieldKey => $potentialHubspotFieldKey) {
				if ($fideloFieldKey === $hubspotInquiryFieldRow['fidelo_field']) {
					$hubspotPropertyNames[$fideloFieldKey] = $hubspotInquiryFieldRow['hubspot_property_name_1'];
					continue 2;
				}
			}
		}

		// Wenn das Feld nicht gesetzt wurde in den externen-App-Einstellungen, dann oben definierten default nehmen.
		// (-> man kann auch hier dann die Property ignorieren, muss man sehen was die Kunden eher wollen..)
		foreach ($searchableFields as $fideloFieldKey => $potentialHubspotFieldKey) {
			if (empty($hubspotPropertyNames[$fideloFieldKey])) {
				$hubspotPropertyNames[$fideloFieldKey] = $potentialHubspotFieldKey;
			}
		}

		$hubspotFirstnamePropertyName = $hubspotPropertyNames['firstname'];
		$hubspotLastnamePropertyName = $hubspotPropertyNames['lastname'];
		$hubspotBirthdayPropertyName = $hubspotPropertyNames['birthday'];
		$hubspotEmailPropertyName = $hubspotPropertyNames['email'];
		$hubspotAgencyPropertyName = $hubspotPropertyNames['agency_id'];
		$hubspotGenderPropertyName = $hubspotPropertyNames['gender'];

		$searchString = $this->request->get('search');
		if (!empty($searchString)) {
			// Hubspots Default Suche ohne spezifische Filter sucht nach allem relevantem und der Operator ist "CONTAINS"
			// Es wird nirgend wo Dokumentiert wie man Querys zu schreiben hat und es gibt eine Maximalanzahl an Filtern
			// die man benutzen kann, also muss man hier eigentlich die default Properties und default Suche lassen.
			$searchCriteria->setQuery($searchString);
		} else {
			// Suche von Fidelo NACH Hubspot -> hier ist der searchString dementsprechend leer.
			$firstname = $this->request->get('firstname');
			$lastname = $this->request->get('lastname');
			$birthday = $this->request->get('bday');
			$companyId = $this->request->get('companyId');
			$emails = $this->request->get('emails');

			$filterArray = [];

			// Nur hinzufügen, wenn es ein Wert gibt zu Suchen
			$helper->addFilter($filterArray, $firstname, $hubspotFirstnamePropertyName);
			$helper->addFilter($filterArray, $lastname, $hubspotLastnamePropertyName);
			$helper->addFilter($filterArray, $birthday, $hubspotBirthdayPropertyName);

			if (
				// Es können maximal 3 Filter benutzt werden
				count($filterArray) < 3 &&
				!empty($emails)
			) {
				$emails = explode(',', $emails);
				// Eine E-Mail muss nur zutreffen bei der Suche
				$helper->addFilter($filterArray, $emails, $hubspotEmailPropertyName, 'IN');
			}

			if (
				// Es können maximal 3 Filter benutzt werden
				count($filterArray) < 3 &&
				!empty($companyId)
			) {
				// Es wird nur nach der "Company"-Property vom Kontakt gesucht und nicht die Assoziation zu der Company von dem Kontakt
				$companyName = \Ext_Thebing_Agency::getInstance($companyId)->getName(true);
				$helper->addFilter($filterArray, $companyName, $hubspotAgencyPropertyName);
			}

			$group = new \HubSpot\Client\Crm\Contacts\Model\FilterGroup();
			$group->setFilters($filterArray);

			$searchCriteria->setFilterGroups([$group]);
		}

		// Rückgabewerte
		$searchCriteria->setProperties(
			[
				$hubspotAgencyPropertyName,
				$hubspotLastnamePropertyName,
				$hubspotFirstnamePropertyName,
				$hubspotGenderPropertyName,
				$hubspotBirthdayPropertyName,
				$hubspotEmailPropertyName
			]
		);

		try {
			$searchRequest = $api->oHubspot->crm()->contacts()->searchApi()->doSearch($searchCriteria);
			$hubspotContacts = $searchRequest->getResults();
		} catch (\Exception $e) {
			// Hier kommt immer nur "There was a problem with the request.". Mögliche Fehlerquellen können sein:
			// 1. Property gibt es nicht 2. Nach der Property kann nicht gesucht werden so 3. Format passt nicht vom
			// gesuchten und von der Property..
			\Log::getLogger('api', 'hubspot')->error('searching for hubspot contact failed..');
		}

		$inquiryIdArray = $this->request->input('id');

		// Bei Neuerstellung gibt es noch keine InquiryId und dementsprechend auch keinen Traveller
		if (!empty($inquiryIdArray)) {
			$inquiryId = reset($inquiryIdArray);
			$traveller = Ext_Ts_Inquiry::getInstance($inquiryId)->getTraveller();
			$travellerHubspotId = $helper->findHubspotIdByEntity($traveller);
		} else {
			$inquiryId = '0';
		}

		$searchResultCount = 0;
		foreach ($hubspotContacts as $hubspotContact) {
			$contactHubspotId = $hubspotContact->getId();
			// Damit man sich nicht selber findet
			if ($contactHubspotId != $travellerHubspotId) {

				// Erstmal nicht
//					$agencyHubspotId = reset(json_decode($api->oHubspot->apiRequest([
//						'method' => 'get',
//						'path' => '/crm-associations/v1/associations/' . $contactHubspotId . '/HUBSPOT_DEFINED/279',
//					])->getBody()->getContents())->results);

				$contactProperties = $hubspotContact->getProperties();

				// Hubsport-Property-Namen für dieses Array in Defaults ändern, damit man im JS damit leichter arbeiten kann
				// und nicht nochmal neu die Namen ermitteln muss
				$changedContactProperties = [
					'firstname' => $contactProperties[$hubspotFirstnamePropertyName],
					'lastname' => $contactProperties[$hubspotLastnamePropertyName],
					'date_of_birth' => $contactProperties[$hubspotBirthdayPropertyName],
					'email' => $contactProperties[$hubspotEmailPropertyName],
					'company' => $contactProperties[$hubspotAgencyPropertyName],
					'gender' => $contactProperties[$hubspotGenderPropertyName],
				];

				$properties[$searchResultCount] = $changedContactProperties;
				$properties[$searchResultCount]['id'] = $contactHubspotId;
				$properties[$searchResultCount]['traveller_id'] = \TsHubspot\Service\Helper\General::findEntityIdByHubspotIdAndEntity($contactHubspotId, 'Ext_TS_Inquiry_Contact_Traveller');

				// Wenn das Geschlechtsfeld bei Hubspot ein Select ist, dann nicht anzeigen, weil das die ID ist und man das
				// nicht zu 100% konvertieren kann mit einer Formatklasse, weil vielleicht in Hubspot die ID für "Männlich"
				// eine andere ist als bei uns.
				if (is_numeric($properties[$searchResultCount][$hubspotGenderPropertyName])) {
					$properties[$searchResultCount][$hubspotGenderPropertyName] = null;
				}
				// Erstmal nicht
//				$properties[$searchResultCount]['agency_id'] = \TsHubspot\Service\Helper\General::findEntityIdByHubspotIdAndEntity($agencyHubspotId, 'Ext_Thebing_Agency');
				$searchResultCount++;
			}
		}

		return [
			'action' => 'showHubspotContacts',
			'data' => [
				'searchResultCount' => $searchResultCount,
				'container_id' => $this->request->get('container_id'),
				'id' => 'ID_' . $inquiryId,
				'searchResult' => $properties,
			]
		];

	}
}
