<?php

class Ext_Thebing_Inquiry_Gui2_Group extends Ext_Thebing_Gui2_Data {

	use \Tc\Traits\Gui2\Import;

	public static $sDescription = 'Thebing » Invoice » Inbox';

	public static function getInquiryDataOptions() {
		$aInquiryData						= array();
		$aInquiryData['complete']			= L10N::t('alle Daten gleich', self::$sDescription);
		$aInquiryData['only_time']			= L10N::t('gleiche Zeiträume', self::$sDescription);
		$aInquiryData['no']					= L10N::t('individuell', self::$sDescription);

		return $aInquiryData;

	}

	public static function getGuideDataOptions() {

		$aInquiryGuideData					= array();
		$aInquiryGuideData['different']		= L10N::t('unterschiedlich zu Schülern', self::$sDescription);
		$aInquiryGuideData['equal']			= L10N::t('identisch mit Schülern', self::$sDescription);

		return $aInquiryGuideData;

	}

	protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false) {

		$interfaceLanguage = Ext_Thebing_School::fetchInterfaceLanguage();
		
		// Daten für die HTML Klassen setzten
		Ext_Thebing_Inquiry_Gui2_Html::$sL10NDescription = $this->_oGui->gui_description;
		Ext_Thebing_Inquiry_Gui2_Html::$oCalendarFormat = $this->_oGui->_oCalendarFormat;

		// ID (inquiry) definieren
		// es darf nur 1 ID makiert sein

		$iGroupId = 0;

		$aSelectedIds = (array)$aSelectedIds;
		if(count($aSelectedIds) > 1) {
			return array();
		} else {
			$iGroupId = (int) reset($aSelectedIds);
		}

		$aData = $oDialogData->generateAjaxData($aSelectedIds, $this->_oGui->hash);
		
		$oSchool = Ext_Thebing_School::getSchoolFromSession();

		if(
			!$oSchool instanceof Ext_Thebing_School ||
			!$oSchool->exist()
		) {
			$aData['all_school'] = 1;
		}

		$oGroup = Ext_Thebing_Inquiry_Group::getInstance($iGroupId);
		if($oGroup->id > 0) {
			$oSchool = Ext_Thebing_School::getInstance($oGroup->school_id);
		}

		$aData['group_id'] = $iGroupId;

		// Wird benötigt für Deaktivierung des Schul-Selects
		$aData['has_invoice'] = 0;
		$bHasProformaOrInvoice = false;
		if($oGroup->id > 0) {
			$aData['has_invoice'] = $oGroup->hasInvoice();
			$bHasProformaOrInvoice = $oGroup->hasInvoiceOrProforma();
		}
		
		foreach($aData['tabs'] as &$aTabData){
			switch($aTabData['options']['task']){
				case 'general_data':
					
					$aData['agency_payment_method'] = array();
					if(!is_array($aData['agency_currency_id'])) {
						$aData['agency_currency_id'] = array();
					}
					if(!is_array($aData['agency_contacts'])) {
						$aData['agency_contacts'] = array();
					}
					
					Ext_Thebing_Agency::getAgencyPaymentsCurrenciesContact($aData['agency_payment_method'], $aData['agency_currency_id'], $aData['agency_contacts']);
					
					break;
				case 'course_data':
					$aTabData['html']	.= Ext_Thebing_Inquiry_Gui2_Html::getCourseTabHTML($oDialogData, $aSelectedIds, $aData['tabs'][1]['readonly'], true);
					$aData['course_data'] = \Ext_TS_Inquiry_Index_Gui2_Data::getCourseDialogData($oSchool);
					$aData['course_languages'] = Ext_Thebing_Tuition_LevelGroup::getInstance()->getArrayList(true, 'name_'.$interfaceLanguage);
					$aData['additionalservices_course'] = Ext_Thebing_Client::getAdditionalServices('course', $oSchool,  true, false, null, false, false);
					break;
				case 'course_guide_data':
					$aTabData['html']	.= Ext_Thebing_Inquiry_Gui2_Html::getCourseTabHTML($oDialogData, $aSelectedIds, $aData['tabs'][5]['readonly'], true, 'course_guide');
					$aData['additionalservices_course'] = Ext_Thebing_Client::getAdditionalServices('course', $oSchool,  true, false, null, false, false);
					break;
				case 'accommodation_data':
					// Nachträglich hinzufügen, damit Kommentarfeld unten steht
					$aTabData['html'] = Ext_Thebing_Inquiry_Gui2_Html::getAccommodationTabHTML($oDialogData, $aSelectedIds, $aData['tabs'][2]['readonly'], true).$aTabData['html'];
					$aData['additionalservices_accommodation'] = Ext_Thebing_Client::getAdditionalServices('accommodation', $oSchool,  true, null, null, false, false);

					## START Raumarten passend zur Unterkunft
						// Alle Unterkünfte
						$aData['aAccRooms'] = $oSchool->getAccommodationRoomCombinations();
					## ENDE

					## START Verpflegung passend zur Raumart
						$aData['aRoomMeals'] = $oSchool->getAccommodationMealCombinations();
					## ENDE
					break;
				case 'accommodation_guide_data':
					$aTabData['html']	.= Ext_Thebing_Inquiry_Gui2_Html::getAccommodationTabHTML($oDialogData, $aSelectedIds, $aData['tabs'][6]['readonly'], true, 'accommodation_guide');
					$aData['additionalservices_accommodation'] = Ext_Thebing_Client::getAdditionalServices('accommodation', $oSchool,  true, null, null, false, false);
					break;
				case 'transfer_data':
					$aTabData['html']	.= Ext_Thebing_Inquiry_Gui2_Html::getIndividualTransferTabHtml($oDialogData, $aSelectedIds, true);
					## START Zusatzinfos der Reiseziele mitschicken
					$aData['transfer_location_terminals'] = Ext_TS_Transfer_Location_Terminal::getGroupedTerminals();

					## ENDE
					break;
				case 'customer_data':

					$sBundleDir = (new Core\Helper\Bundle())->getBundleDirectory('Ts');

					$templateEngine = new Core\Service\Templating();
					$templateEngine->assign('gui2', $this);

					$sHtml = $templateEngine->fetch($sBundleDir.'/Resources/views/groups/customer_upload.tpl');

					$sHtml .= '<table class="table group-table tblDocumentTable">';

					$sHtml .= '<colgroup>';
						$sHtml .= '<col style="width:20px;">';
						$sHtml .= '<col style="width:auto;">';
						$sHtml .= '<col style="width:80px;">';
						$sHtml .= '<col style="width:80px;">';
						$sHtml .= '<col style="width:90px;">';
						$sHtml .= '<col style="width:200px;">';
						$sHtml .= '<col style="width:100px;">';
						$sHtml .= '<col style="width:100px;">';
						$sHtml .= '<col style="width:80px;">';
						$sHtml .= '<col style="width:40px;">';
						$sHtml .= '<col style="width:40px;">';
						$sHtml .= '<col style="width:40px;">';
						$sHtml .= '<col style="width:40px;">';
						$sHtml .= '<col style="width:40px;">';
						$sHtml .= '<col style="width:40px;">';
						$sHtml .= '<col style="width:40px;">';
						$sHtml .= '<col style="width:30px;">';
					 $sHtml .= '</colgroup>';

						$sHtml .= '<tr>';
							$sHtml .= '<th>';
								$sHtml .= '&nbsp;';
							$sHtml .= '</th>';
							$sHtml .= '<th colspan="8">';
								$sHtml .= L10N::t('Kundendaten', $this->_oGui->gui_description);
							$sHtml .= '</th>';
							$sHtml .= '<th>';
								$sHtml .= '&nbsp;';
							$sHtml .= '</th>';
							$sHtml .= '<th colspan="6">';
								$sHtml .= L10N::t('Umsonst', $this->_oGui->gui_description);
							$sHtml .= '</th>';
							$sHtml .= '<th>';
								$sHtml .= '&nbsp;';
							$sHtml .= '</th>';
						$sHtml .= '</tr>';

						$sHtml .= '<tr>';
							$sHtml .= '<th>';
								$sHtml .= '&nbsp;';
							$sHtml .= '</th>';
							$sHtml .= '<th title="'.L10N::t('Nummer', $this->_oGui->gui_description).'">'.L10N::t('Nummer', $this->_oGui->gui_description).'</th>';
							$sHtml .= '<th title="'.L10N::t('Nachname', $this->_oGui->gui_description).'">'.L10N::t('Nachname', $this->_oGui->gui_description).'*</th>';
							$sHtml .= '<th title="'.L10N::t('Vorname', $this->_oGui->gui_description).'">'.L10N::t('Vorname', $this->_oGui->gui_description).'*</th>';
							$sHtml .= '<th title="'.L10N::t('Geschlecht', $this->_oGui->gui_description).'">'.L10N::t('Geschlecht', $this->_oGui->gui_description).'*</th>';
							$sHtml .= '<th title="'.L10N::t('Geburtsdatum', $this->_oGui->gui_description).'">'.L10N::t('Geburtsdatum', $this->_oGui->gui_description).'*</th>';
							$sHtml .= '<th title="'.L10N::t('Nationalität', $this->_oGui->gui_description).'">'.L10N::t('Nationalität', $this->_oGui->gui_description).'*</th>';
							$sHtml .= '<th title="'.L10N::t('Muttersprache', $this->_oGui->gui_description).'">'.L10N::t('Muttersprache', $this->_oGui->gui_description).'*</th>';
							$sHtml .= '<th title="'.L10N::t('Automatische E-Mails', $this->_oGui->gui_description).'">'.L10N::t('Automatische E-Mails', $this->_oGui->gui_description).'*</th>';
							$sHtml .= '<th title="'.L10N::t('Gruppenleiter', $this->_oGui->gui_description).'">'.L10N::t('Gruppenleiter', $this->_oGui->gui_description).'</th>';
							$sHtml .= '<th title="'.L10N::t('Kurs', $this->_oGui->gui_description).'">'.L10N::t('Kurs', $this->_oGui->gui_description).'</th>';
							$sHtml .= '<th title="'.L10N::t('Unter.', $this->_oGui->gui_description).'">'.L10N::t('Unter.', $this->_oGui->gui_description).'</th>';
							$sHtml .= '<th title="'.L10N::t('Zusatzkosten Kurse', $this->_oGui->gui_description).'">'.L10N::t('Z. K.', $this->_oGui->gui_description).'</th>';
							$sHtml .= '<th title="'.L10N::t('Zusatzkosten Unterkünfte', $this->_oGui->gui_description).'">'.L10N::t('Z. U.', $this->_oGui->gui_description).'</th>';
							$sHtml .= '<th title="'.L10N::t('Transfer', $this->_oGui->gui_description).'">'.L10N::t('Trans.', $this->_oGui->gui_description).'</th>';
							$sHtml .= '<th title="'.L10N::t('Alles', $this->_oGui->gui_description).'">'.L10N::t('Alles', $this->_oGui->gui_description).'</th>';
							$sHtml .= '<th>';
								//$sHtml .= L10N::t('Aktion', $this->_oGui->gui_description);
							$sHtml .= '</th>';
						$sHtml .= '</tr>';
						$iCounter = 1;
						$aCustomers = $oGroup->getInquiries(false,false);
						foreach((array)$aCustomers as $oInquiry){
							$sHtml .= $this->buildGroupCustomerTr($iCounter, $oInquiry, $oGroup);
							$iCounter ++;
						}
						$sHtml .= $this->buildGroupCustomerTr($iCounter, null, $oGroup);
					$sHtml .= '</table>';

					$aTabData['html']	.= $sHtml;
					break;
				case 'settings_data':
					$aTabData['html'] .= 'TEST';
					break;
			}
		}

//		$aData['title'] = $sTitle;
		$aData['bundled_course_levels'] = Ext_Thebing_Tuition_Course::getLevelGroupedCourses();

		$aData['date_format'] = strtoupper(Ext_Thebing_Format::getDateFormat(null, 'backend_datepicker_format'));
		$aData['date_format'] = preg_replace('/([^a-zA-Z]+)/', '"$1"', $aData['date_format']);
		
		$aData['translations'] = [
			'invalid_file' => $this->t('Ungültiges Dateiformat. Bitte wählen Sie eine XLSX-Datei!'),
			'no_items' => $this->t('Es konnten keine Kunden übernommen werden. Bitte prüfen Sie das Format der XLSX-Datei!')
		];

		return $aData;
	}

	public function buildGroupCustomerTr($iCounter = 1, $oInquiry = null, $group){

		$sName						= '';
		$sFirstname					= '';
		$sNumber					= '';
		$iGender					= 0;
		$sBirthday					= 0;
		$customerNationality		= 0;
		$customerLanguage			= 0;
		$customerNewsletter			= '';
		$iInquiryId					= 0;

		$iGuide						= 0;
		$iFreeCourse				= 0;
		$iFreeAccommodation			= 0;
		$iFreeCourseFee				= 0;
		$iFreeAccommodationFee		= 0;
		$iFreeTransfer				= 0;
		$iFreeAll					= 0;
		
		if($oInquiry != null)
		{
			$oSchool					= $oInquiry->getSchool();
			
			 /* @var $oCustomer Ext_TS_Inquiry_Contact_Traveller */
			 /* @var $oInquiry Ext_TS_Inquiry */
			$oCustomer				= $oInquiry->getCustomer();
			$sName					= $oCustomer->lastname;
			$sFirstname				= $oCustomer->firstname;
			$sNumber				= $oCustomer->getCustomerNumber();
			$iGender				= $oCustomer->gender;
			$iBirthday				= $oCustomer->getBirthday();
			$customerNationality	= $oCustomer->nationality;
			$customerLanguage		= $oCustomer->language;
			$customerNewsletter		= $oCustomer->detail_newsletter;
			$sBirthday				= Ext_Thebing_Format::LocalDate($iBirthday, $oSchool->id);
			$iInquiryId				= $oInquiry->id;
			$iGuide					= (int) $oInquiry->getJourneyTravellerOption('guide');
			$iFreeCourse			= (int) $oInquiry->getJourneyTravellerOption('free_course');
			$iFreeAccommodation		= (int) $oInquiry->getJourneyTravellerOption('free_accommodation');
			$iFreeCourseFee			= (int) $oInquiry->getJourneyTravellerOption('free_course_fee');
			$iFreeAccommodationFee	= (int) $oInquiry->getJourneyTravellerOption('free_accommodation_fee');
			$iFreeTransfer			= (int) $oInquiry->getJourneyTravellerOption('free_transfer');
			$iFreeAll				= (int) $oInquiry->getJourneyTravellerOption('free_all');
		}

		$oDialog = $this->_oGui->createDialog();

		$sNameId = 'customer['.$iInquiryId.'][data]';
		$sNameName = 'customer['.$iInquiryId.'][data]';
		$oData = $oDialog->createSaveField('hidden', array('name'=>$sNameName, 'id' => $sNameId, 'class'=>'txt group_data'));
		
		$sCalendarId = 'customer['.$iInquiryId.'][birthday]';
		$sCalendarid2 = 'calendarimg['.$iInquiryId.'][birthday]';
		$sCalendarName = 'customer['.$iInquiryId.'][birthday]';
		$oCalendar = $oDialog->createSaveField('calendar', array('value'=>$sBirthday, 'name'=>$sCalendarName, 'id' => $sCalendarId, 'calendar_id' => $sCalendarid2, 'display_age' => true));

		$sNameId = 'customer['.$iInquiryId.'][lastname]';
		$sNameName = 'customer['.$iInquiryId.'][lastname]';
		$oName = $oDialog->createSaveField('input', array('value'=>$sName, 'name'=>$sNameName, 'id' => $sNameId, 'class'=>'txt'));
		$oName->class = "txt name";

		$sFirstNameId = 'customer['.$iInquiryId.'][firstname]';
		$sFirstNameName = 'customer['.$iInquiryId.'][firstname]';
		$oFirstName = $oDialog->createSaveField('input', array('value'=>$sFirstname, 'name'=>$sFirstNameName, 'id' => $sFirstNameId, 'class'=>'txt'));
		$oFirstName->class = "txt firstname";

		// Geschlecht, ID wichtig für JS!
		$sGenderName = $sGenderId = 'customer['.$iInquiryId.'][gender]';

		$oGender = $oDialog->createSaveField('select', array('name'=>$sGenderName, 'id' => $sGenderId, 'class'=>'txt'));
		$aGenders = Ext_Thebing_Util::getGenders();
		foreach($aGenders as $iValue=>$sGender) {
			$oOption = $oDialog->create('option');
			$oOption->value = $iValue;
			if($iGender == $iValue) {
				$oOption->selected = "selected";
			}
			$oOption->setElement($sGender);
			$oGender->setElement($oOption);
		}

		$interfaceLanguage = \Ext_TC_System::getInterfaceLanguage();

		$nationalityName = 'customer['.$iInquiryId.'][nationality]';
		$nationalityField = $oDialog->createSaveField('select', ['name'=>$nationalityName, 'id' => $nationalityName, 'class'=>'txt', 'default_value' => 'AL']);
		$nationalities =  \Ext_Thebing_Nationality::getNationalities(true, $interfaceLanguage);

		// Default
		$oOption = $oDialog->create('option');
		$oOption->value = 'group_nationality';
		$defaultSelected = false;
		if($customerNationality == $group->nationality_id) {
			$oOption->selected = "selected";
			$defaultSelected = true;
		}
		$oOption->setElement(L10N::t('Gruppe - Nationalität'));
		$nationalityField->setElement($oOption);

		foreach($nationalities as $nationalityIso => $nationalityLabel) {
			$oOption = $oDialog->create('option');
			$oOption->value = $nationalityIso;
			if(
				$customerNationality == $nationalityIso &&
				!$defaultSelected
			) {
				$oOption->selected = "selected";
			}
			$oOption->setElement($nationalityLabel);
			$nationalityField->setElement($oOption);
		}

		$languageName = 'customer['.$iInquiryId.'][language]';
		$languageField = $oDialog->createSaveField('select', ['name'=>$languageName, 'id' => $languageName, 'class'=>'txt']);
		$allLanguages = \Ext_TC_Language::getSelectOptions($interfaceLanguage);

		// Default
		$oOption = $oDialog->create('option');
		$oOption->value = 'group_language';
		$defaultSelected = false;
		if($customerLanguage == $group->language_id) {
			$oOption->selected = "selected";
			$defaultSelected = true;
		}
		$oOption->setElement(L10N::t('Gruppe - Muttersprache'));
		$languageField->setElement($oOption);

		foreach($allLanguages as $languageIso => $languageLabel) {
			$oOption = $oDialog->create('option');
			$oOption->value = $languageIso;
			if(
				$customerLanguage == $languageIso &&
				!$defaultSelected
			) {
				$oOption->selected = "selected";
			}
			$oOption->setElement($languageLabel);
			$languageField->setElement($oOption);
		}

		$newsletterName = 'customer['.$iInquiryId.'][detail_newsletter]';
		$newsletterField = $oDialog->createSaveField('select', ['name'=>$newsletterName, 'id' => $newsletterName, 'class'=>'txt']);

		// Default
		$defaultOption = $oDialog->create('option');
		$defaultOption->value = 'group_newsletter';
		$defaultOption->selected = "selected";
		$defaultOption->setElement(L10N::t('Gruppe - Automatische E-Mails'));
		$newsletterField->setElement($defaultOption);

		$oOption = $oDialog->create('option');
		$oOption->value = 1;
		if(
			$customerNewsletter == 1 &&
			$customerNewsletter != $group->newsletter
		) {
			$oOption->selected = "selected";
			$defaultOption->removeAttribute('selected');
		}
		$oOption->setElement(L10N::t('Ja'));
		$newsletterField->setElement($oOption);

		$oOption = $oDialog->create('option');
		$oOption->value = 0;
		if(
			$customerNewsletter == 0 &&
			$customerNewsletter != $group->newsletter
		) {
			$oOption->selected = "selected";
			$defaultOption->removeAttribute('selected');
		}
		$oOption->setElement(L10N::t('Nein'));
		$newsletterField->setElement($oOption);

		$sFreeCheckboxClass = "customer_group_free_checkbox_" . $iInquiryId;

		$sHtml = '';
			$sHtml .= '<tr id="tr_group_inquiry_'.$iInquiryId.'" class="customer_tr tr_'.$iInquiryId.' tr['.$iInquiryId.'][tr]">';
				$sHtml .= '<td>';
					$sHtml .= $iCounter;
				$sHtml .= '</td>';
				$sHtml .= '<td class="customer_number_td">';
					$sHtml .= $sNumber;
				$sHtml .= '</td>';
				$sHtml .= '<td class="name_td">';
					$sHtml .= $oName->generateHtml();
					$sHtml .= $oData->generateHTML();
				$sHtml .= '</td>';
				$sHtml .= '<td class="firstname_td">';
					$sHtml .= $oFirstName->generateHtml();
				$sHtml .= '</td>';
				$sHtml .= '<td>';
					$sHtml .= $oGender->generateHtml();
				$sHtml .= '</td>';
				$sHtml .= '<td class="groupCustomerBirthday form-group input-group-sm">';
					$sHtml .= $oCalendar->generateHtml();
				$sHtml .= '</td>';
				$sHtml .= '<td>';
					$sHtml .= $nationalityField->generateHtml();
				$sHtml .= '</td>';
				$sHtml .= '<td>';
					$sHtml .= $languageField->generateHtml();
				$sHtml .= '</td>';
				$sHtml .= '<td>';
				$sHtml .= $newsletterField->generateHtml();
				$sHtml .= '</td>';
				$sHtml .= '<td class="guide_td">';
					$sName = 'inquiry_flag['.$iInquiryId.'][guide]';
					$sId = 'guide_checkbox_'.$iInquiryId;
					$sClass = 'guide_checkbox';
					$oHidden = $oDialog->create('input');
					$oHidden->type = "hidden";
					$oHidden->name = $sName;
					$oHidden->value = 0;

					$oCheckbox = $oDialog->createSaveField('checkbox', array( 'name'=>$sName, 'id'=>$sId, 'class'=>$sClass));
					$oCheckbox->title = L10N::t('Gruppenleiter', $this->_oGui->gui_description);
					$oCheckbox->value = 1;

					if($iGuide == 1){
						$oCheckbox->checked = "checked";
					}

					$sHtml .= $oHidden->generateHtml();
					$sHtml .= $oCheckbox->generateHtml();
				$sHtml .= '</td>';
				$sHtml .= '<td>';
					$sName = 'inquiry_flag['.$iInquiryId.'][free_course]';
					$oHidden = $oDialog->create('input');
					$oHidden->type = "hidden";
					$oHidden->name = $sName;
					$oHidden->value = 0;

					$oCheckbox = $oDialog->createSaveField('checkbox', array( 'name'=>$sName, 'class'=>$sFreeCheckboxClass));
					$oCheckbox->title = L10N::t('Kurs umsonst', $this->_oGui->gui_description);
					$oCheckbox->value = 1;
					
					if($iFreeCourse == 1){
						$oCheckbox->checked = "checked";
					}

					$sHtml .= $oHidden->generateHtml();
					$sHtml .= $oCheckbox->generateHtml();
				$sHtml .= '</td>';
				$sHtml .= '<td>';
					$sName = 'inquiry_flag['.$iInquiryId.'][free_accommodation]';
					$oHidden = $oDialog->create('input');
					$oHidden->type = "hidden";
					$oHidden->name = $sName;
					$oHidden->value = 0;

					$oCheckbox = $oDialog->createSaveField('checkbox', array( 'name'=>$sName, 'class'=>$sFreeCheckboxClass));
					$oCheckbox->title = L10N::t('Unterkunft umsonst', $this->_oGui->gui_description);
					$oCheckbox->value = 1;

					if($iFreeAccommodation == 1){
						$oCheckbox->checked = "checked";
					}

					$sHtml .= $oHidden->generateHtml();
					$sHtml .= $oCheckbox->generateHtml();
				$sHtml .= '</td>';
				$sHtml .= '<td>';
					$sName = 'inquiry_flag['.$iInquiryId.'][free_course_fee]';
					$oHidden = $oDialog->create('input');
					$oHidden->type = "hidden";
					$oHidden->name = $sName;
					$oHidden->value = 0;

					$oCheckbox = $oDialog->createSaveField('checkbox', array( 'name'=>$sName, 'class'=>$sFreeCheckboxClass));
					$oCheckbox->title = L10N::t('Kursgebüren umsonst', $this->_oGui->gui_description);
					$oCheckbox->value = 1;

					if($iFreeCourseFee == 1){
						$oCheckbox->checked = "checked";
					}

					$sHtml .= $oHidden->generateHtml();
					$sHtml .= $oCheckbox->generateHtml();
				$sHtml .= '</td>';
				$sHtml .= '<td>';
					$sName = 'inquiry_flag['.$iInquiryId.'][free_accommodation_fee]';
					$oHidden = $oDialog->create('input');
					$oHidden->type = "hidden";
					$oHidden->name = $sName;
					$oHidden->value = 0;

					$oCheckbox = $oDialog->createSaveField('checkbox', array( 'name'=>$sName, 'class'=>$sFreeCheckboxClass));
					$oCheckbox->title = L10N::t('Unterkunftsgebüren umsonst', $this->_oGui->gui_description);
					$oCheckbox->value = 1;

					if($iFreeAccommodationFee == 1){
						$oCheckbox->checked = "checked";
					}

					$sHtml .= $oHidden->generateHtml();
					$sHtml .= $oCheckbox->generateHtml();
				$sHtml .= '</td>';
				$sHtml .= '<td>';
					$sName = 'inquiry_flag['.$iInquiryId.'][free_transfer]';
					$oHidden = $oDialog->create('input');
					$oHidden->type = "hidden";
					$oHidden->name = $sName;
					$oHidden->value = 0;

					$oCheckbox = $oDialog->createSaveField('checkbox', array( 'name'=>$sName, 'class'=>$sFreeCheckboxClass));
					$oCheckbox->title = L10N::t('Transfer umsonst', $this->_oGui->gui_description);
					$oCheckbox->value = 1;

					if($iFreeTransfer == 1){
						$oCheckbox->checked = "checked";
					}

					$sHtml .= $oHidden->generateHtml();
					$sHtml .= $oCheckbox->generateHtml();
				$sHtml .= '</td>';
				$sHtml .= '<td>';
					$sName = 'inquiry_flag['.$iInquiryId.'][free_all]';
					$oHidden = $oDialog->create('input');
					$oHidden->type = "hidden";
					$oHidden->name = $sName;
					$oHidden->value = 0;

					$oCheckbox = $oDialog->createSaveField('checkbox', array( 'name'=>$sName, 'class'=>$sFreeCheckboxClass));
					$oCheckbox->title = L10N::t('Alles umsonst', $this->_oGui->gui_description);
					$oCheckbox->value = 1;

					if($iFreeAll == 1){
						$oCheckbox->checked = "checked";
					}

					$sHtml .= $oHidden->generateHtml();
					$sHtml .= $oCheckbox->generateHtml();
				$sHtml .= '</td>';
				$sHtml .= '<td>';
					$sHtml .= '<input id="delete_group_inquiry_'.$iInquiryId.'" id="" type="hidden" name="customer['.$iInquiryId.'][delete]" value="0" />';
					$sHtml .= '<input id="active_group_inquiry_'.$iInquiryId.'" id="" type="hidden" name="customer['.$iInquiryId.'][active]" value="1" />';
					// auser bei der leer zeile
					if($oInquiry == null) {
						$sHtml .= '<i style="cursor:pointer;" class="delete_group_customer_new_img fa '.Ext_Thebing_Util::getIcon('delete').'"  title="'.L10N::t('Löschen', $this->_oGui->gui_description).'"/>';
					} else {
						$sHtml .= '<i style="cursor:pointer;" class="delete_group_customer_img fa '.Ext_Thebing_Util::getIcon('delete').'"  title="'.L10N::t('Löschen', $this->_oGui->gui_description).'" />';
					}
				$sHtml .= '</td>';
			$sHtml .= '</tr>';
		return $sHtml;
	}

	/**
	 * @inheritdoc
	 */
	public function getTranslations($sL10NDescription) {

		$oInquiryGuiData = $this->createOtherGuiData(Ext_Thebing_Inquiry_Gui2::class);
		return $oInquiryGuiData->getTranslations($sL10NDescription);

	}

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveData
	 * @param bool $bSave
	 * @param string $aAction
	 * @param bool $bPrepareOpenDialog
	 * @return array
	 */
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave=true, $aAction='edit', $bPrepareOpenDialog = true) {
		global $user_data, $_VARS;

		DB::begin('save_inquiry_group');
		
		$aSelectedIds				= (array)$aSelectedIds;

		$aErrors					= array();
		$aData						= array();
		$bSuccess					= true;
		$iErrorCount				= 0;

		// Zahlungen von Unterkunftszuweisungen einzelner Gruppenmitglieder
		$aAccommodationPayments		= array();
		
		
		// Wenn keine ID oder genau EINE ID dann darf gespeichert werden
		if(count($aSelectedIds) <= 1) {

			$iGroupId = (int)reset($aSelectedIds);

			$oDateFormat = new Ext_Thebing_Gui2_Format_Date();
			
			$oGroup = new Ext_Thebing_Inquiry_Group($iGroupId);
			$oSchool = $oGroup->getSchool();
			if(!$oSchool){
				$oSchool = Ext_Thebing_School::getSchoolFromSession();
			}

			// Status VOR dem Speichern!
			$hasProformaOrInvoiceBeforeSaving = $oGroup->hasInvoiceOrProforma();
			
			$oCustomerContact = $oGroup->getContactPerson();
			// Geschlecht muss vorhanden sein
			$oCustomerContact->gender = 1;

			// Setzen der Daten in die Objekte
			foreach((array)$aSaveData as $sColumn => $aData) {
				foreach((array)$aData as $sAlias => $mValue) {

					if((string)$sAlias == 'kg') {
						$oGroup->$sColumn = $mValue;
					} else if((string)$sAlias == 'cdb1'){
						$oCustomerContact->$sColumn = $mValue;
					}
				}
			}
			
			/*
			 * Normale Kurse und Guide-kurse
			 * 
			 * Diese Tabs werden je nach Recht nicht da sein und die Werte eventuell nicht übermittelt
			 * Kurs und Kurs-Guide können nur zusammen da sein, genauso wie bei der Unterkunft
			 */
			$aAllCourses = array();
			if(
				!empty($_VARS['course']) &&
				!empty($_VARS['course_guide'])
			) {
				$aAllCourses['all'] = (array)$_VARS['course'];
				$aAllCourses['guide'] = (array)$_VARS['course_guide'];
			}

			// Normale Unterkünfte und Guide-Unterkünfte
			$aAllAccommodations = array();
			if(
				!empty($_VARS['accommodation']) &&
				!empty($_VARS['accommodation_guide'])
			) {
				$aAllAccommodations['all'] = (array)$_VARS['accommodation'];
				$aAllAccommodations['guide'] = (array)$_VARS['accommodation_guide'];
			}
			
			// ==========================================================================================
			// Unterkünfte müssen geprüft werden, ob Zahlungen existieren
			// ==========================================================================================
			if(!empty($aAllAccommodations)) {
				foreach($aAllAccommodations as $sType => $aAccommodations) {
					foreach($aAccommodations as $iAccommodationId => $aAccommodation) {

						// Bei individuellen Daten nicht prüfen, da die Daten verschieden sein dürfen
						// Wenn man im SR die Unterkunftsbuchung abändert, sind diese nämlich immer noch über groups_accommodation_id verknüpft
						if($oGroup->accommodation_data === 'no') {
							continue;
						}

						// Nur gespeicherte Unterkünfte prüfen
						if($iAccommodationId <= 0){
							continue;
						}

						// Unterkunftszeiten
						$sAccommodationFrom = $oDateFormat->convert($aAccommodation['from']);
						$sAccommodationUntil = $oDateFormat->convert($aAccommodation['until']);

						$oGroupAccommodation = new Ext_Thebing_Inquiry_Group_Accommodation($iAccommodationId);

						// prüfen ob sich die Daten verändert haben
						$aAccommodationPayments = $oGroupAccommodation->checkPaymentStatus($sAccommodationFrom, $sAccommodationUntil, $aAccommodation);

						// Unterkunftszahlungen die das verändern der Unterkunft verbieten
						if(!empty($aAccommodationPayments)){

							$sMessage = L10N::t('Es existieren Unterkunftszahlungen', $this->_oGui->gui_description);
							$sMessage .= '<br/><ul>';
							// Jede Bezahlung einzeln auflisten
							foreach($aAccommodationPayments as $oPayment){
								$sMessage .= '<li>' . $oPayment->comment . '</li>';
							}
							$sMessage .= '</ul>';

							$aErrors[$iErrorCount]['message']				= $sMessage;
							$aErrors[$iErrorCount]['input']					= array();
							$aErrors[$iErrorCount]['input']['dbcolumn']		= 'accommodation['.(int)$iGroupId.']['.(int)$iAccommodationId.'][weeks]';
							$aErrors[$iErrorCount]['input']['dbalias']		= 'accommodation';
							$iErrorCount++;
							$bSuccess = false;
						}

					}
				}		
			}

			// WDBasic Exeptions Abfangen!
			try {

				if($bSuccess == true) {

					// Prüfung, ob alle Informationen übermittelt wurden
					if(
						(
							!isset($_VARS['course']) &&
							!isset($_VARS['accommodation']) 
						) ||
						(						
							(
								$_VARS['save']['course_guide']['kg'] != 'equal' &&
								!isset($_VARS['course_guide'])
							) &&
							(
								$_VARS['save']['accommodation_guide']['kg'] != 'equal' &&
								!isset($_VARS['accommodation_guide'])
							)
						)
					) {
						throw new Exception('Missing information in request!');
					}

					$mErros = $oGroup->validate();
					$mErros2 = $oCustomerContact->validate(false, true);

					if(
						$mErros !== true || 
						$mErros2 !== true
					) {

						if(is_array($mErros)) {

							foreach((array)$mErros as $sColumn => $aWDBasicErrors) {
								foreach((array)$aWDBasicErrors as $sMessage){
									$aErrors[$iErrorCount]['message']				= $this->_getErrorMessage($sMessage, $sColumn);
									$aErrors[$iErrorCount]['input']					= array();
									$aErrors[$iErrorCount]['input']['dbcolumn']		= $sColumn;
									$aErrors[$iErrorCount]['input']['dbalias']		= 'kg';
									$iErrorCount++;
								}
							}
						}

						if(is_array($mErros2)){
							foreach((array)$mErros2 as $sColumn => $aWDBasicErrors){
								foreach((array)$aWDBasicErrors as $sMessage){
									$aErrors[$iErrorCount]['message']				= $this->_getErrorMessage($sMessage, $sColumn);
									$aErrors[$iErrorCount]['input']					= array();
									$aErrors[$iErrorCount]['input']['dbcolumn']		= $sColumn;
									$aErrors[$iErrorCount]['input']['dbalias']		= 'cdb1';
									$iErrorCount++;
								}
							}
						}

						$bSuccess = false;
					} else {

						$mErros2 = $oCustomerContact->save();

						if(is_array($mErros2)){

							foreach((array)$mErros2 as $sColumnInfo => $aWDBasicErrors){

								$aColumnInfo	= explode('.', $sColumnInfo);
								$sColumn		= $aColumnInfo[1];
								$sAlias			= $aColumnInfo[0];

								foreach((array)$aWDBasicErrors as $sMessage){
									$aErrors[$iErrorCount]['message']				= $this->_getErrorMessage($sMessage, $sColumn);
									$aErrors[$iErrorCount]['input']					= array();
									$aErrors[$iErrorCount]['input']['dbcolumn']		= $sColumn;
									$aErrors[$iErrorCount]['input']['dbalias']		= $sAlias;
									$iErrorCount++;
								}
							}


							$bSuccess = false;
						}
					}
				}

				// Rechnung auf "changed" setzen
				$bChangedInvoice = false;
				$aDeletedInvoices = [];

				// Kunden speichern
				if($bSuccess){

					$aGroupCustomers = array();
					// Kundenposition die gespeichert werden darf
					$aGroupCustomerSavePosition = array();

					// Zählt alle Kundenzeilen
					$iCustomerCounter = 0;

					foreach((array)$_VARS['customer'] as $iInquiryId => $aCustomer) {

						$iCustomerCounter++;

						if(
							$aCustomer['lastname'] == '' &&
							$aCustomer['firstname'] == '' &&
							empty($aCustomer['gender']) &&
							$aCustomer['birthday'] == ''
						) {
							$iCustomerCounter++;
							continue;
						}

						// wenn < 0 dann neuer Kunde
						$iTempCustomerId = (int)$iInquiryId;

						// Vorhandene Gruppe, neues Gruppenmitglied
						if(
							$iGroupId > 0 &&
							$iInquiryId <= 0
						) {
							// Neuer Kunde der Gruppe
							$iInquiryId = 0;
							// Wenn ein Kunde hinzukommt müssen die anderen als "changed" markiert werden, 
							// Da die Rechnung nicht mehr aktuell ist
							$bChangedInvoice = true;
						}

						$sDeleteCustomer = 'keep';
						if(!empty($aCustomer['delete'])) {
							if(in_array($aCustomer['delete'], ['delete_relation', 'delete_customer'])) {
								$sDeleteCustomer = $aCustomer['delete'];
							} else {
								throw new RuntimeException('$sDeleteCustomer has wrong value: '.$sDeleteCustomer);
							}
						}

						if($sDeleteCustomer !== 'keep') {

							if($iInquiryId == 0) {
								// Nichts speichern zu einer nicht existenten Buchung
								continue;
							}

							$bChangedInvoice = true;
						}
						
						unset($aCustomer['delete']);
					
						// Habe das auskommentiert, sonst wurden nicht die Daten richtig übernommen, z.B. tsp_transfer(#4031,Kommentar#6)
						// da mit 2 verschiedenen Schleifen gearbeitet wird und mit einer wird gespeichert, aber wenn beide nicht auf die 
						// gleiche Instanz zuweisen kommt alles durcheinander...
						#$oInquiry	= new Ext_TS_Inquiry($iInquiryId);
						
						$oInquiry	= Ext_TS_Inquiry::getInstance($iInquiryId);
						//Flag auf automatisches bestätigen überprüfen. ggf. confirmed in der Inquiry setzen
						if(System::d('booking_auto_confirm') > 0) {
							$oInquiry->confirm();
						}
						$oCustomer	= $oInquiry->getCustomer();

						$iCustomerId = (int)$oCustomer->id;

						if(
							$iCustomerId == 0 &&
							$aCustomer['active'] == 0
						) {
							continue;
						}

						// KEIN getInstance da mehrere mit ID = 0 möglich sind!!! >> unsere WDBasic kann nit mit ID=0 cachen,
						// darum no problem
						#$oCustomer = new Ext_TS_Inquiry_Contact_Traveller($iCustomerId);

						/**
						 * @TODO soweit ich das sehe, wird hier ganz viel mit $aCustomer gemacht, um damit dann $oCustomer
						 * Werte zu geben, um dann am Ende $oCustomer nicht zu speichern und nicht weiter zu geben und die Variable
						 * dann einfach zu überschreiben. Das ist natürlich nicht sehr intuitiv und wenn man an dieser Stelle
						 * etwas ändern möchte, schaut man sich ggf. hier erstmal um, obwohl das hier alles unnötig ist(?)
						 */
						## START Kundendaten erweiterun mit denen der Gruppe
						//Nationalität
						if ($aCustomer['nationality'] == 'group_nationality') {
							// Standardwert
							$aCustomer['nationality'] = $aSaveData['nationality_id']['kg'];
						}
						// Muttersprache
						if ($aCustomer['language'] == 'group_language') {
							// Standardwert
							$aCustomer['language'] = $aSaveData['language_id']['kg'];
						}

						// Automatische E-Mails
						if ($aCustomer['detail_newsletter'] == 'group_newsletter') {
							// Standardwert
							// "detail_" wegen dem set- und getter der Ext_TC_Contact
							$aCustomer['detail_newsletter'] = $aSaveData['newsletter']['kg'];
						}

						// Kor. sprache WICHTIG da sonst bei templates auswahl evt. etwas durcheinander kommt
						$aCustomer['corresponding_language'] = $aSaveData['correspondence_id']['kg'];

						// Wenn Schüler neu oder Land des Schülers entsprach vorherigem Land der Gruppe: Land übernehmen
						$oCustomerAddress = $oCustomer->getAddress('contact');
						if(
							$iInquiryId == 0 ||
							$oGroup->getOriginalData('country') == $oCustomerAddress->country_iso
						) {
							$oCustomerAddress->country_iso = $aSaveData['country']['kg'];
							$oCustomerAddress->save();
						}

						// Wurde der Kunde gelöscht sollte nichts mehr hier gespeichert werden
						if($sDeleteCustomer === 'keep') {
							
							foreach((array)$aCustomer as $sField => $mValue) {

								if($sField == 'data') {
									
									$this->setCustomerData($oInquiry, $oCustomer, $mValue);

								} elseif($sField == 'birthday') {

									$sBirthday = $oDateFormat->convert($mValue);
									
									if(
										empty($sBirthday)
									){
										$sBirthday = '-';
									}

									$oCustomer->$sField = $sBirthday;

								} else {
									$oCustomer->$sField = $mValue;
								}

							}
						}
				
						$mValidate = $oCustomer->validate();
				
						$aTemp = array();
						#$aTemp['customer'] = $oCustomer;
						$aTemp['inquiry'] = $oInquiry;
						$aTemp['deleted'] = $sDeleteCustomer;

						if($mValidate === true){
							$aGroupCustomers[] = $aTemp;

							$aGroupCustomerSavePosition[] = $iCustomerCounter;
						} elseif(is_array($mValidate)) {

							$aLabels = [
								'lastname' => $this->t('Nachname'),
								'firstname' => $this->t('Vorname'),
								'gender' => $this->t('Geschlecht'),
								'birthday' => $this->t('Geburtsdatum')
							];

							$aSkipCodes = [];
							foreach((array)$mValidate as $sColumn => $aColumnErrors) {
								$sColumn = $this->_getFieldIdentifier($sColumn)['column'];
								foreach($aColumnErrors as $sCode) {
									if(isset($aSkipCodes[$sCode])) {
										continue;
									}

									$aErrors[$iErrorCount++] = [
										'message' => $this->_getErrorMessage($sCode, $sColumn, $aLabels[$sColumn]),
										'input' => [
											'name' => 'customer['.(int)$iTempCustomerId.']['.$sColumn.']'
										]
									];
								}
							}

							// Fehler Kunden speichern
							$bSuccess = false;
							
						}

					}

				}

				// Letzter Schüler darf nicht aus Gruppe gelöscht werden wenn eine Rechnung existiert (sonst Rechnung weg)
				if($bSuccess) {
					$aInquiries = $oGroup->getInquiries();

					if(
						count($aInquiries) === 1 &&
						reset($aInquiries)->hasProformaOrInvoice() &&
						reset($aGroupCustomers)['deleted'] !== 'keep'
					) {
						$bSuccess = false;
						$aErrors[$iErrorCount]['message'] = $this->_getErrorMessage('LAST_STUDENT_DELETION', '');
						$aErrors[$iErrorCount]['input'] = [];
						$iErrorCount++;
					}
				}

				//Kurse speichern
				if(
					$bSuccess &&
					!empty($aAllCourses)
				) {

					$aUsedGroupCourseIds = array();
					$aGroupCourses = array();
					$groupCourseAdditionalServices = new SplObjectStorage();

					foreach((array)$aAllCourses as $sType => $aCourses) {

						// Kursarten speichern
						foreach((array)$aCourses as $iCourseId => $aCourse) {

							unset($aCourse['category_id']);
							unset($aCourse['units_dummy']);

							if($aCourse['course_id'] <= 0){
								continue;
							}

							// nicht Lektionskurse, dürfen auch keine Lektionen speichern
							$oTuitionCourse = Ext_Thebing_Tuition_Course::getInstance($aCourse['course_id']);
							if($oTuitionCourse->per_unit != 1){
								$aCourse['units'] = 0;
							}
					
							$iTempCourseId = $iCourseId;
							if($iCourseId <= 0){
								$iCourseId = 0;
							}
							$aUsedGroupCourseIds[] = $iCourseId;
							$oCourse = new Ext_Thebing_Inquiry_Group_Course($iCourseId);
							// Kurstype
							$oCourse->type = $sType;

							foreach((array)$aCourse as $sField => $mValue){

								if(
									$sField == 'from' || 
									$sField == 'until'
								){
									$mValue = $oDateFormat->convert($mValue);
									
									if($mValue == 0){
										$mValue = 'error'; // Fehler beim Validate verursachen
									}
								} elseif ($sField == 'additionalservices') {
									$groupCourseAdditionalServices[$oCourse] = $mValue;
									continue;
								}

								$oCourse->$sField = $mValue;
							}

							// Group ID faken damit validiert werden kann die korrekte ID kommt nach dem speichern hinzu
							if((int)$oCourse->group_id < 1){
								$oCourse->group_id = 1;
							}

							$oCourse->adjustData();
							$mValidate = $oCourse->validate();

							if($mValidate === true) {
								// Enddatum darf nicht vor Startdatum liegen
								$dFrom = new DateTime($oCourse->from);
								$dUntil = new DateTime($oCourse->until);
								if($dFrom > $dUntil) {
									$mValidate = array();
									$mValidate['until'] = array('UNTIL_BEFORE_FROM');
								}
							}

							if($mValidate === true){
								$aGroupCourses[] = $oCourse;
							} else {
								if(is_array($mValidate)){
									
									// Das korrekte Feld muss geheighlitet werden
									$sInputNamePrefix = 'course';
									if($sType == 'guide'){
										$sInputNamePrefix = 'course_guide';
									}
									
									foreach((array)$mValidate as $sColumn => $aWDBasicErrors){
										foreach((array)$aWDBasicErrors as $sMessage){
											$aErrors[$iErrorCount]['message']				= $this->_getErrorMessage($sMessage, $sColumn);
											$aErrors[$iErrorCount]['input']					= array();
											$aErrors[$iErrorCount]['input']['id']			= $sInputNamePrefix.'['.(int)$iGroupId.']['.(int)$iTempCourseId.']['.$sColumn.']';
											#$aErrors[$iErrorCount]['input']['dbalias']		= 'course';
											$iErrorCount++;
										}
									}
								}
								// Fehler Gruppen speichern
								$bSuccess = false;
							}
						}
					}

					$aTemp = $oGroup->getCourses();
					foreach((array)$aTemp as $aTempData){
						if(!in_array($aTempData['id'], $aUsedGroupCourseIds)){
							$oCourse = new Ext_Thebing_Inquiry_Group_Course($aTempData['id']);
							$oCourse->active = 0;
							$aGroupCourses[] = $oCourse;
						}

					}

				}

				// Transfer speichern
				if(
					$bSuccess &&
					!empty($_VARS['transfer'])
				) {
					$aUsedGroupTransferIds = array();
					$aGroupTransfers = array();

					foreach((array)$_VARS['transfer'] as $iTransferId => $aTransfer){

						// Transfer nicht speichern
						if(
							$aTransfer['transfer_type'] == '0' &&
							(
								$aTransfer['start'] == '0' ||
								$aTransfer['end'] == '0' ||
								$aTransfer['transfer_date'] == ''
							)
						){
							if($iTransferId > 0){

								$oTransfer = new Ext_Thebing_Inquiry_Group_Transfer($iTransferId);
								$oTransfer->active = 0;
								$aGroupTransfers[] = $oTransfer;

							} else {
								continue;
							}

						} else {
							$aStartTemp		= explode('_', $aTransfer['start']);
							$aEndTemp		= explode('_', $aTransfer['end']);


							$aTransfer['start_type']	= $aStartTemp[0];
							$aTransfer['start']			= $aStartTemp[1];

							$aTransfer['end_type']		= $aEndTemp[0];
							$aTransfer['end']			= $aEndTemp[1];

							if($aTransfer['start_type'] != 'location'){
								$aTransfer['start_additional'] = 0;
							}
							if($aTransfer['end_type'] != 'location'){
								$aTransfer['end_additional'] = 0;
							}

							$aTransfer['booked'] = 0;

							if (
								(
									$aTransfer['transfer_type'] == Ext_TS_Inquiry_Journey_Transfer::TYPE_ARRIVAL &&
									$oGroup->transfer_mode & Ext_TS_Inquiry_Journey::TRANSFER_MODE_ARRIVAL
								) || (
									$aTransfer['transfer_type'] == Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE &&
									$oGroup->transfer_mode & Ext_TS_Inquiry_Journey::TRANSFER_MODE_DEPARTURE
								)
							) {
								$aTransfer['booked'] = 1;
							}

							$iTempTransferId = (int)$iTransferId;

							$aUsedGroupTransferIds[] = $iTransferId;

							$oTransfer = new Ext_Thebing_Inquiry_Group_Transfer($iTransferId);

							foreach($aTransfer as $sField => $mValue){
								if($sField == 'transfer_date'){
									$mValue = $oDateFormat->convert($mValue);
								}
								$oTransfer->$sField = $mValue;
							}
						}

						$mValidate = $oTransfer->validate();

						if($mValidate === true){
							$aGroupTransfers[] = $oTransfer;
						} else {
							if(is_array($mValidate)){
								foreach((array)$mValidate as $sColumn => $aWDBasicErrors){
									foreach((array)$aWDBasicErrors as $sMessage){
										$aErrors[$iErrorCount]['message']				= $this->_getErrorMessage($sMessage, $sColumn);
										$aErrors[$iErrorCount]['input']					= array();
										$aErrors[$iErrorCount]['input']['id']			= 'transfer['.(int)$iGroupId.']['.(int)$iTempTransferId.']['.$sColumn.']';
										#$aErrors[$iErrorCount]['input']['dbalias']		= 'transfer';
										$iErrorCount++;
									}
								}
							}
							// Fehler Transfer speichern
							$bSuccess = false;
						}

					}
					
					$aTempTransfers = $oGroup->getTransfers();
					foreach((array)$aTempTransfers as $oTempTransfer){
						if(!in_array($oTempTransfer->id, $aUsedGroupTransferIds)){
							$oTempTransfer->active = 0;
							$aGroupTransfers[] = $oTempTransfer;
						}
					}
				}

				// Unterkünfte speichern
				if(
					$bSuccess &&
					!empty($aAllAccommodations)
				) {
					$aUsedGroupAccommodationIds = array();
					$aGroupAccommodations = array();
					$groupAccommodationAdditionalServices = new SplObjectStorage();

					foreach((array)$aAllAccommodations as $sType => $aAccommodations){
						// Unterkunftsarten speichern
						foreach((array)$aAccommodations as $iAccommodationId => $aAccommodation){

							if($aAccommodation['accommodation_id'] <= 0){
								continue;
							}

							$iTempAccommodationId = $iAccommodationId;
							if($iAccommodationId <= 0){
								$iAccommodationId = 0;
							}
							$aUsedGroupAccommodationIds[] = $iAccommodationId;
							$oAccommodation = new Ext_Thebing_Inquiry_Group_Accommodation($iAccommodationId);

							// Unterkunftstype
							$oAccommodation->type = $sType;

							foreach((array)$aAccommodation as $sField => $mValue){
								if(
									$sField == 'from' || 
									$sField == 'until'
								){
									$mValue = $oDateFormat->convert($mValue);
									
									if($mValue == 0){
										$mValue = 'error'; // Fehler beim Validate verursachen
									}
								} elseif ($sField == 'additionalservices') {
									$groupAccommodationAdditionalServices[$oAccommodation] = $mValue;
									continue;
								}
								$oAccommodation->$sField = $mValue;
							}

							// Group ID faken damit validiert werden kann die korrekte ID kommt nach dem speichern hinzu
							if((int)$oAccommodation->group_id < 1){
								$oAccommodation->group_id = 1;
							}
					
							$mValidate = $oAccommodation->validate();

							if($mValidate === true) {
								// Enddatum darf nicht vor Startdatum liegen
								$dFrom = new DateTime($oAccommodation->from);
								$dUntil = new DateTime($oAccommodation->until);
								if($dFrom > $dUntil) {
									$mValidate = array();
									$mValidate['until'] = array('UNTIL_BEFORE_FROM');
								}
							}

							if($mValidate === true){
								$aGroupAccommodations[] = $oAccommodation;
							} else {
								if(is_array($mValidate)){
									
									// Das korrekte Feld muss geheighlitet werden
									$sInputNamePrefix = 'accommodation';
									if($sType == 'guide'){
										$sInputNamePrefix = 'accommodation_guide';
									}
									
									foreach((array)$mValidate as $sColumn => $aWDBasicErrors){
										foreach((array)$aWDBasicErrors as $sMessage){
											$aErrors[$iErrorCount]['message']				= $this->_getErrorMessage($sMessage, $sColumn);
											$aErrors[$iErrorCount]['input']					= array();
											$aErrors[$iErrorCount]['input']['id']			= $sInputNamePrefix.'['.(int)$iGroupId.']['.(int)$iTempAccommodationId.']['.$sColumn.']';
											#$aErrors[$iErrorCount]['input']['dbalias']		= 'accommodation';
											$iErrorCount++;
										}
									}
								}
								// Fehler Unterkünfte speichern
								$bSuccess = false;
							}


						}
					}

					$aTemp = $oGroup->getAccommodations();

					foreach((array)$aTemp as $aTempData){
						if(!in_array($aTempData['id'], $aUsedGroupAccommodationIds)){
							$oAccommodation = new Ext_Thebing_Inquiry_Group_Accommodation($aTempData['id']);
							$oAccommodation->active = 0;

							$mValidate = $oAccommodation->validate();

							if($mValidate === true){
								$aGroupAccommodations[] = $oAccommodation;
							} else {
								if(is_array($mValidate)){
									foreach((array)$mValidate as $sColumn => $aWDBasicErrors){
										foreach((array)$aWDBasicErrors as $sMessage){
											$aErrors[$iErrorCount]['message']				= L10N::t($sMessage);
											$aErrors[$iErrorCount]['input']					= array();
											$aErrors[$iErrorCount]['input']['id']			= 'accommodation['.(int)$iGroupId.']['.(int)$iTempAccommodationId.']['.$sColumn.']';
											#$aErrors[$iErrorCount]['input']['dbalias']		= 'accommodation';
											$iErrorCount++;
										}
									}
								}

								// Fehler Unterkünfte speichern
								$bSuccess = false;
							}
						}
					}
				}

				if($bSuccess) {
					
					// Aktuelle Inbox Daten holen
					$aInboxData = Ext_Thebing_Util::getCurrentInboxData((int)$this->_oGui->getOption('inbox_id'));
					
					// Wenn school id gesetzt ( edit group oder all ansicht )
					$iSchoolId = $oGroup->school_id;
					if($oGroup->school_id <= 0){
						// Sonst aus seession
						$iSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');
					}

					$oGroup->contact_id = $oCustomerContact->id;
					$oGroup->client_id = $user_data['client'];
					$oGroup->school_id = $iSchoolId;
					$oGroup->inbox_id = (int)$aInboxData['id'];
					$oGroup->active = 1;
					$oGroup->save();
					
					$oSchool			= Ext_Thebing_School::getInstance($iSchoolId);
					$iProductLineId		= $oSchool->getProductLineId();

					// Kunden speichern
					$iSavedInquiries = 0;
					
					$oDataInquiry		= new Ext_Thebing_Inquiry_Gui2($this->_oGui);
                    
                    $aChangedGroupFlags = array();

					foreach((array)$aGroupCustomers as $aTemp) {

						#$oCustomer = $aTemp['customer'];
						#$oCustomer->save();

						/** @var $oInquiry Ext_TS_Inquiry */
						$oInquiry							= $aTemp['inquiry'];
						
						$oJourney							= $oInquiry->getJourney();
						$oJourney->school_id				= $iSchoolId;
						$oJourney->productline_id			= $iProductLineId;
						
						$oCustomer							= $oInquiry->getCustomer();
						
						$oInquiry->active					= $oCustomer->active;

						// Buchung löschen oder Buchung mit Informationen der Gruppe aktualisieren
						if($aTemp['deleted'] !== 'keep') {

							if ($aTemp['deleted'] !== 'delete_customer') {
								$oInquiry->group_id = 0;
							}

							// @TODO Persistent loggen, wenn Schüler gelöscht wurde, da das nachher überhaupt nicht mehr nachvollziehbar ist
							$oLog = Log::getLogger();
							$oLog->addInfo('Group inquiry deletion: Deleted inquiry from group', [
								'group_id' => $oGroup->id,
								'inquiry_id' => $oInquiry->id,
								'deletion_type' => $aTemp['deleted']
							]);

							// Alle Rechnungen dieser Buchung sammeln, damit die Items später neu zugewiesen werden können
							$aDeletedInvoices[$oInquiry->id] = $oInquiry->getDocuments('invoice_with_creditnote', true, true);

							if(empty($aDeletedInvoices[$oInquiry->id])) {
								// Key löschen, damit empty() auf $aDeletedInvoices funktioniert
								unset($aDeletedInvoices[$oInquiry->id]);
							}

							// Wenn Schüler ganz gelöscht werden soll: Hier löschen und weiteren Durchlauf überspringen
							if($aTemp['deleted'] === 'delete_customer') {
								$oInquiry->delete(true);
								$iSavedInquiries++; // Muss erhöht werden, sonst werden Flags von einem gelöschten Mitglied auf das nächste gesetzt (nummerischer Mist)
								continue;
							}

						} else {
							$oInquiry->setGroup($oGroup);
						}

						// Wenn eine Inbox angegeben wurde
						if (
							empty($oInquiry->inbox) &&
							$aInboxData['short'] != ''
						) {
							$oInquiry->inbox = $aInboxData['short'];
						}
						
						$mSuccessInquiry = $oInquiry->validate();

						if($mSuccessInquiry === true) {
							
							$oldId = $oInquiry->id;
							
							$mSuccessInquiry = $oInquiry->save();

							\System::wd()->executeHook('ts_group_inquiry_save', $oldId, $oInquiry);

							Ext_Gui2_Index_Stack::add('ts_inquiry', $oInquiry->id, 0);
							
						}
                        
						if(!is_array($mSuccessInquiry)) {
							
							$iSavedInquiries++;

							// Kunden nummer erzeugen falls noch nicht vorhanden
							$oCustomerNumber = new Ext_Thebing_Customer_CustomerNumber($oInquiry);
							$aNumberErrors = $oCustomerNumber->saveCustomerNumber();

                            if(empty($aNumberErrors)) {

                                // Gruppenflags speichern nur wenn Inquiry vorhanden und kein Fehler vorhanden
                                if(isset($aGroupCustomerSavePosition[$iSavedInquiries - 1])){
                                    $iSavedPosition = $aGroupCustomerSavePosition[$iSavedInquiries - 1];
                                    $iCount = 0;
                                    foreach((array)$_VARS['inquiry_flag'] as $iInquiryId => $aFlags){
                                        $iCount++;										
                                        if($iCount == $iSavedPosition){
                                            $aChanges = $oInquiry->saveGroupFlags($aFlags);
											if($iGroupId > 0) {
												$aChangedGroupFlags[$oInquiry->id] = $aChanges;
											}
                                        }

                                    }
                                }  

                            } else {
                               foreach($aNumberErrors as $sError){
                                   $aErrors[$iErrorCount]['message']				= $sError;
                                   $iErrorCount++;
                               }
                               $bSuccess = false; 
                            }
							
						} else {
							$bSuccess = false;
							
							// Dieser Part wurde eigentlich nur für den Fehler "INVALID_PAYMENT_METHOD" geschrieben,
							// die anderen Fehler kommen für gewöhnlich nie (validate habe ich jetzt ergänzt, früher stand da @todo :) )
							// falls aber doch was kommen sollte, sollte dieser Code irgendwas im Dialog anzeigen, kann zwar leider nicht
							// zu 100 Prozent funktionieen aber besser als gar nichts :)
							foreach($mSuccessInquiry as $sKeyValidate => $mErrorInquiry) {
								$aErrorInquiry = (array)$mErrorInquiry;
								
								foreach($aErrorInquiry as $sErrorKey) {
									$sErrorMessage = $oDataInquiry->getErrorMessage($sErrorKey, $sKeyValidate);
									
									$aErrors[$sKeyValidate] = $sErrorMessage;
								}
								
							}
							
							#Throw new Exception("Productline Error");
						}

					}

					
					if($bSuccess) {
						
						// Gelöscht Mitglieder werden jetzt nicht mehr gefunden :)
						$aInquirys					= $oGroup->getInquiries();
						$aInquiryCourses			= array();
						$aInquiryAccommodations		= array();

						foreach((array)$aInquirys as $oInquiry){	
							$aInquiryCourses[$oInquiry->id]			= $oInquiry->getCourses(true, true, true, false);
							$aInquiryAccommodations[$oInquiry->id]	= $oInquiry->getAccommodations(true, true);
							
						}

						$oPrice		= new Ext_Thebing_Price($oSchool, null, null, 'en');

						$sCheckModusCourse			= $oGroup->course_data;
						$sCheckModusAccommodation	= $oGroup->accommodation_data;
						$sCheckModusTransfer		= $oGroup->transfer_data;

						## START Kurs speichern
						// Alle buchungen durchegehen und alle Kurse löschen welche zuviel sind
						// wenn "alle Daten gleich"
						if($sCheckModusCourse == 'complete'){
							foreach((array)$aInquirys as $oInquiry){
								
								$aDeleteCourses = array();

								$aTempCourses = $aInquiryCourses[$oInquiry->id];
                                $aUsedGroupCourseIds = array();
								// Alle Inquiry Kurse durchlaufen
								foreach((array)$aTempCourses as $iKey => $oInquiryCourse){
									
									if(
										$oInquiryCourse->groups_course_id <= 0 ||
                                        // es kann pro Inquiry immer nur 1 Kurs die gleiche group course id haben
                                        // daher kurs löschen wenn bereits ein Kurs mit dieser ID berabeitet wurde
                                        in_array($oInquiryCourse->groups_course_id, $aUsedGroupCourseIds)
									){
										$aDeleteCourses[] = $oInquiryCourse;
									} else {
									
										// Alle Group Kurse durchlaufen
										foreach((array)$aGroupCourses as $iKey => $oCourse){

											// Alle wenn es keine passende gibt müssen sie gelöscht werden
											if(
												$oInquiryCourse->groups_course_id == $oCourse->id
											){
                                                // merken welche group course id schon benutzt wurde
                                                // es kann pro Inquiry immer nur 1 Kurs die gleiche group course id haben
                                                $aUsedGroupCourseIds[] = $oInquiryCourse->groups_course_id;
                                                
												// Prüfen ob Kunde den Kurs haben darf
												if(
													$oGroup->course_guide == 'different' &&
													$oInquiry->isGuide() && // guide
													$oCourse->type == 'all' // normaler Kurs
												){
													$aDeleteCourses[] = $oInquiryCourse; // Guides haben keine normalen Kurse
												}elseif(
													$oGroup->course_guide == 'different' &&
													$oInquiry->isGuide() != 1 &&
													$oCourse->type == 'guide' // guide Kurs
												){
													$aDeleteCourses[] = $oInquiryCourse; // Kunden haben keine Guide Kurse
												}elseif(
													$oGroup->course_guide == 'equal' &&
													$oCourse->type == 'guide'
												){
													$aDeleteCourses[] = $oInquiryCourse;// GuideKurse nicht speichern wenn Kurse identisch sein sollen
												}

											}
										}
									}
								}
 
								foreach($aDeleteCourses as $oCourse){
									$oCourse->active = 0;
									$oCourse->save();
								}

							}
						}

						// Kurse speichern
						// und einzelkunden kurse anpassen
						foreach((array)$aGroupCourses as $iKey => $oCourse){
							$oCourse->group_id = $oGroup->id;
							$oCourse->save();

							$courseAdditionalServiceData = [];
							// Additional Services erst nach dem save, weil die ID benötigt wird.
							if(
								$groupCourseAdditionalServices->count() !== 0 &&
								$groupCourseAdditionalServices->contains($oCourse)
							) {
								// Bei ausgewählten Einträgen im Select
								$additionalServiceIds = $groupCourseAdditionalServices->offsetGet($oCourse);
								foreach($additionalServiceIds as $additionalServiceId) {
									$courseAdditionalServiceData[] = [
										'group_id' => $oGroup->id,
										'additionalservice_id' => $additionalServiceId,
									];
								}
							}

							// Durch ->additionalservices wird das Array überschrieben und somit werden quasi
							// Additional Services die entfernt wurden aus dem Select auch aus der DB entfernt.
							$oCourse->additionalservices = $courseAdditionalServiceData;
							$oCourse->save();

							// Bei Individuellen-Kursen darf kein Jou
							if($sCheckModusCourse === 'no') {
								continue;
							}
							foreach((array)$aInquirys as $oInquiry){

								/** @var Ext_TS_Inquiry_Journey_Course $oInquiryCourse */
								$oInquiryCourse = null;
								
								// passenden Inquiry Course finden
								foreach((array)$aInquiryCourses[$oInquiry->id] as $oTempInquiryCourse){
									if($oTempInquiryCourse->groups_course_id == $oCourse->id){
										$oInquiryCourse = $oTempInquiryCourse;
										break;
									}
								}

								// Prüfen ob Kunde den Kurs haben darf
								if(
									$oGroup->course_guide == 'different' &&
									$oInquiry->isGuide() && // guide
									$oCourse->type == 'all' // normaler Kurs
								){
									continue; // Guides haben keine normalen Kurse
								}elseif(
									$oGroup->course_guide == 'different' &&
									!$oInquiry->isGuide() &&
									$oCourse->type == 'guide' // guide Kurs
								){
									continue; // Kunden haben keine Guide Kurse
								}elseif(
									$oGroup->course_guide == 'equal' &&
									$oCourse->type == 'guide'
								){
									continue; // GuideKurse nicht speichern wenn Kurse identisch sein sollen
								}

								// Wenn Kurs nicht vorhanden dann hinzubuchen zum Kunden
								if(!is_object($oInquiryCourse)){
									$oJourney		= $oInquiry->getJourney();
									$oInquiryCourse = $oJourney->getJoinedObjectChild('courses', 0);
									$oInquiryCourse->active = 1;
									$oInquiryCourse->for_tuition = 1;
								}

								// Wenn gelöscht
								if($oCourse->active == 0 && $oInquiryCourse->id > 0){
									$oInquiryCourse->active = 0;
									$oInquiryCourse->save();
									continue;
								}

								$bChanged = $oInquiryCourse->checkForChange($oCourse, $sCheckModusCourse);
								if(!$bChanged) {
									// Prüfung darf nicht in checkForChange() eingebaut werden, da diese nur für rechnungsrelevante Änderungen da ist
									// isChanged() funktioniert auch nicht, da zwei unterschiedliche Objekte verglichen werden
									if(
										$oInquiryCourse->flexible_allocation != $oCourse->getOriginalData('flexible_allocation') ||
										$oInquiryCourse->comment != $oCourse->getOriginalData('comment')
									) {
										$bChanged = 'flexible_allocation';
									}
								}

								if(
									$bChanged ||
									$oInquiryCourse->id == 0
								) {

									$bCourseChanged = false;

									if(
										$sCheckModusCourse == 'only_time' ||
										$sCheckModusCourse == 'complete'
									) {
										$oInquiryCourse->from				= $oCourse->from;
										$oInquiryCourse->until				= $oCourse->until;
										$oInquiryCourse->weeks				= $oCourse->weeks;
										$oInquiryCourse->units				= $oCourse->units;
										$oInquiryCourse->visible			= $oCourse->visible;
										$oInquiryCourse->groups_course_id	= (int)$oCourse->id;
										$oInquiryCourse->flexible_allocation = $oCourse->flexible_allocation;
										$bCourseChanged = true;
									}
									if($sCheckModusCourse == 'only_time') {
										
										// Kursbuchung darf nicht ohne Kurs gespeichert werden
										if(empty($oInquiryCourse->course_id)) {
											$oInquiryCourse->course_id = $oCourse->course_id;
											$oInquiryCourse->courselanguage_id = $oCourse->courselanguage_id;
											$oInquiryCourse->program_id = $oCourse->program_id;
											$oInquiryCourse->level_id = $oCourse->level_id;
										}
										
										$oInquiryCourse->save();
									}
									else if($sCheckModusCourse == 'complete') {
										$oInquiryCourse->course_id = $oCourse->course_id;
										$oInquiryCourse->courselanguage_id = $oCourse->courselanguage_id;
										$oInquiryCourse->program_id = $oCourse->program_id;
										$oInquiryCourse->level_id = $oCourse->level_id;
										$oInquiryCourse->comment = $oCourse->comment;

										$additionalServices = $oCourse->additionalservices;

										// Alle gespeicherten holen
										$aSavedAdditionalServices = $oInquiryCourse->getJoinedObjectChilds('additionalservices');

										foreach($additionalServices as $additionalServiceData) {

											$bFound = false;
											foreach($aSavedAdditionalServices as $iSavedAdditionalService=>$oSavedAdditionalService) {

												if($oSavedAdditionalService->additionalservice_id == $additionalServiceData['additionalservice_id']) {
													$bFound = true;
													break;
												}
											}

											if($bFound !== true) {
												$oJourneyAdditionalService = $oInquiryCourse->getJoinedObjectChild('additionalservices');
												$oJourneyAdditionalService->additionalservice_id = $additionalServiceData['additionalservice_id'];
												$oJourneyAdditionalService->journey_id = $oInquiryCourse->journey_id;
											} else {
												unset($aSavedAdditionalServices[$iSavedAdditionalService]);
											}

										}

										foreach($aSavedAdditionalServices as $oSavedAdditionalService) {
											$oSavedAdditionalService->delete();
										}


										$oInquiryCourse->save();
									}

									if(
										$oInquiry->confirmed > 0 &&
										$bCourseChanged &&
										$bChanged != 'new' &&
										$bChanged !== 'flexible_allocation'
									){
										Ext_Thebing_Inquiry_Document_Version::setChange($oInquiry->id, $oInquiryCourse->id, 'course');
										// und das die zusatzkosten verändert wurden ( da sie zum kurs gehören )
										$aAdditionalCourseCostList = $oPrice->getAdditionalCourseCostList($oInquiryCourse->course_id);
										foreach((array)$aAdditionalCourseCostList as $aCost){
											Ext_Thebing_Inquiry_Document_Version::setChange($oInquiry->id, $aCost['id'], 'additional_course', 'edit', $oInquiryCourse->id);
										}
										// Lösche Tuition Daten
										$oInquiryCourse->deleteTuition();
									}

								}
								
							}

						}
					## ENDE

					## START Transfer speichern
						// Transfer speichern
						// und einzelkunden kurse anpassen

						$aValidTransferIds = array();
						foreach((array)$aGroupTransfers as $iKey => $oTransfer) {

							$aOriginalData = $oTransfer->getOriginalData();
							
							$oTransfer->group_id = $oGroup->id;
							$oTransfer->save();

							foreach((array)$aInquirys as $oInquiry) {

								$bTransferChanged = false;

								if($sCheckModusTransfer != 'no') {
									if($sCheckModusTransfer == 'only_time') {
										$aFields = array(
											'transfer_type' => 1,
											'transfer_date' => 1, 
											'transfer_time' => 1
										);
									}
									else {
										$aFields = array(
											'transfer_type' => 1, 
											'start' => 1,
											'end' => 1,
											'start_type' => 1,
											'end_type' => 1,
											'transfer_date' => 1,
											'transfer_time' => 1,
											'comment' => 1,
											'start_additional' => 1,
											'end_additional' => 1,
											'airline' => 1,
											'flightnumber' => 1,
											'pickup' => 1,
										);
									}

									$aCompareData = array_intersect_key($aOriginalData, $aFields);

									$oJourney = $oInquiry->getJourney();
									$oInquiryTransfer = $oJourney->getSameService('transfers', $aCompareData);

									// Wenn gelöscht
									if($oTransfer->active == 0) {
										if(is_object($oInquiryTransfer) && $oInquiryTransfer->id > 0) {
											$oJourney->removeJoinedObjectChildByKey('transfers', $oInquiryTransfer->id);
										}
										continue;
									}

									if(!is_object($oInquiryTransfer)) {
										$oInquiryTransfer = new Ext_TS_Inquiry_Journey_Transfer(0);
										$oInquiryTransfer->active = 1;
										$oInquiryTransfer->journey_id = $oJourney->id;
									}

									$bChanged = $oInquiryTransfer->checkForChange($oTransfer, $sCheckModusTransfer);
									if($bChanged) {
										$bTransferChanged = true;
									}

									$aNewData = array_intersect_key($oTransfer->getArray(), $aFields);
									foreach($aNewData as $sField => $mValue) {
										$oInquiryTransfer->$sField = $mValue;
									}
									$oInquiryTransfer->save();

									$aValidTransferIds[] = $oInquiryTransfer->id;
								}

								if(
									$oInquiry->confirmed > 0 &&
									$bTransferChanged
								) {
									Ext_Thebing_Inquiry_Document_Version::setChange($oInquiry->id, $oInquiryTransfer->id, 'transfer');
								}

							}

						}
						// Löschen aller nicht mehr benötigten
						// Transferdatensätze Ticket #5843
						if(
							$sCheckModusTransfer != 'no' &&
							$oGroup->id > 0 &&
							!empty($aValidTransferIds)
						) {
							foreach($aInquirys as $oInquiry) {
								$oJourney = $oInquiry->getJourney();
								$aTransfers = $oJourney->getUsedTransfers();
								foreach($aTransfers as $oTransfer) {
									if(!in_array($oTransfer->id, $aValidTransferIds)) {
										$oTransfer->delete();
									}
								}
							}
						}
					## ENDE

					## START Unterkunft speichern
						// Alle buchungen durchegehen und alle Unterkünfte löschen welche zuviel sind
						// wenn "alle Daten gleich"
						if($sCheckModusAccommodation == 'complete') {

							foreach((array)$aInquirys as $oInquiry) {

								$aDeleteAccommodations = array();

								$aTempAccommodations = $aInquiryAccommodations[$oInquiry->id];
								// Alle Inquiry Unterkünfte durchlaufen
								foreach((array)$aTempAccommodations as $iKey => $oInquiryAccommodation){
									
									if(
										$oInquiryAccommodation->groups_accommodation_id <= 0
									){
										$aDeleteAccommodations[] = $oInquiryAccommodation;
									} else {
									
										// Alle Group Unterkünfte durchlaufen
										foreach((array)$aGroupAccommodations as $iKey => $oAccommodation){
											// Alle wenn es keine passende gibt müssen sie gelöscht werden
											if(
												$oInquiryAccommodation->groups_accommodation_id == $oAccommodation->id
											){

												// Prüfen ob Kunde die Unterkunft haben darf
												if(
													$oGroup->accommodation_guide == 'different' &&
													$oInquiry->isGuide() && // guide
													$oAccommodation->type == 'all' // normale Unterkunft
												){
													$aDeleteAccommodations[] = $oInquiryAccommodation; // Guides haben keine normalen Unterkünfte
												}elseif(
													$oGroup->accommodation_guide == 'different' &&
													!$oInquiry->isGuide() &&
													$oAccommodation->type == 'guide' // guide Kurs
												){
													$aDeleteAccommodations[] = $oInquiryAccommodation; // Kunden haben keine Guide-Unterkünfte
												}elseif(
													$oGroup->accommodation_guide == 'equal' &&
													$oAccommodation->type == 'guide'
												){
													$aDeleteAccommodations[] = $oInquiryAccommodation;// Guide-Unterkünfte nicht speichern wenn Unterkünfte identisch sein sollen
												}

											}
										}
									}
								}

								foreach($aDeleteAccommodations as $oAccommodation){
									$oAccommodation->active = 0;
									$oAccommodation->save();
								}

								if(empty($oInquiry->getMatchingData()->acc_comment)) {
									$oInquiry->getMatchingData()->acc_comment = $oGroup->accommodation_comment;
								} elseif(!empty($oGroup->accommodation_comment)) {
									$oInquiry->getMatchingData()->acc_comment .= "\n".$oGroup->accommodation_comment;
								}

							}
						}

						// Unterkünfte speicher
						// und einzelkunden Unterkünfte anpassen
						foreach((array)$aGroupAccommodations as $iKey => $oAccommodation){
							$oAccommodation->group_id = $oGroup->id;
							$oAccommodation->save();

							$accommodationAdditionalServiceData = [];
							// Additional Services erst nach dem save, weil die ID benötigt wird.
							if(
								$groupAccommodationAdditionalServices->count() !== 0 &&
								$groupAccommodationAdditionalServices->contains($oAccommodation)
							) {
								// Bei ausgewählten Einträgen im Select
								$additionalServiceIds = $groupAccommodationAdditionalServices->offsetGet($oAccommodation);
								foreach($additionalServiceIds as $additionalServiceId) {
									$accommodationAdditionalServiceData[] = [
										'group_id' => $oGroup->id,
										'additionalservice_id' => $additionalServiceId,
									];
								}
							}

							// Durch ->additionalservices wird das Array überschrieben und somit werden quasi
							// Additional Services die entfernt wurden aus dem Select auch aus der DB entfernt.
							$oAccommodation->additionalservices = $accommodationAdditionalServiceData;
							$oAccommodation->save();

							foreach((array)$aInquirys as $oInquiry){

								$oInquiryAccommodation = NULL;
								$aOriginalAcco = array();
								
								// passenden Inquiry Accommodation finden
								foreach((array)$aInquiryAccommodations[$oInquiry->id] as $oTempInquiryAccommodation){
									if($oTempInquiryAccommodation->groups_accommodation_id == $oAccommodation->id){
										$oInquiryAccommodation = $oTempInquiryAccommodation;
										// Alte daten nochmal holen ( bevor die neuen gespeichert werden )
										$aOriginalAcco = $oInquiryAccommodation->getOriginalData();
										break;
									}
								}

								if(!is_object($oInquiryAccommodation)){
									
									$oJourney		= $oInquiry->getJourney();
									
									$oInquiryAccommodation = new Ext_TS_Inquiry_Journey_Accommodation(0);
									$oInquiryAccommodation->active = 1;
									$oInquiryAccommodation->journey_id = $oJourney->id;
									$oInquiryAccommodation->for_matching = 1;
									$oInquiryAccommodation->accommodation_id = $oAccommodation->accommodation_id;
									$oInquiryAccommodation->roomtype_id = $oAccommodation->roomtype_id;
									$oInquiryAccommodation->meal_id = $oAccommodation->meal_id;
								}

								// Wenn gelöscht
								if($oAccommodation->active == 0 && $oInquiryAccommodation->id > 0){
									$oInquiryAccommodation->active = 0;
									$oInquiryAccommodation->save();
									continue;
								}

								// Prüfen ob Kunde die Unterkunft haben darf
								if(
									$oGroup->accommodation_guide == 'different' &&
									$oInquiry->isGuide() && // guide
									$oAccommodation->type == 'all' // normale Unterkunft
								){
									continue; // Guides haben keine normale Unterkunft
								}elseif(
									$oGroup->accommodation_guide == 'different' &&
									$oInquiry->isGuide() != 1 &&
									$oAccommodation->type == 'guide' // guide Unterkunft
								){
									continue; // Kunden haben keine Guide Unterkunft
								}elseif(
									$oGroup->accommodation_guide == 'equal' &&
									$oAccommodation->type == 'guide'
								){
									continue; // GuideUnterkunft nicht speichern wenn Kurse identisch sein sollen
								}


								$bChange = $oInquiryAccommodation->checkForChange($oAccommodation, $sCheckModusAccommodation);

								if(
									$bChange || 
									$oInquiryAccommodation->id == 0
								){

									$bAccoChanged = false;

									if($sCheckModusAccommodation == 'only_time'){
										$oInquiryAccommodation->from					= $oAccommodation->from;
										$oInquiryAccommodation->until					= $oAccommodation->until;
										$oInquiryAccommodation->weeks					= $oAccommodation->weeks;
										$oInquiryAccommodation->visible					= $oAccommodation->visible;
										$oInquiryAccommodation->groups_accommodation_id	= (int)$oAccommodation->id;
										$oInquiryAccommodation->save();
										$bAccoChanged = true;

									} else if($sCheckModusAccommodation == 'complete') {
										$oInquiryAccommodation->from					= $oAccommodation->from;
										$oInquiryAccommodation->until					= $oAccommodation->until;
										$oInquiryAccommodation->weeks					= $oAccommodation->weeks;
										$oInquiryAccommodation->accommodation_id		= $oAccommodation->accommodation_id;
										$oInquiryAccommodation->roomtype_id				= $oAccommodation->roomtype_id;
										$oInquiryAccommodation->meal_id					= $oAccommodation->meal_id;
										$oInquiryAccommodation->comment					= $oAccommodation->comment;
										$oInquiryAccommodation->visible					= $oAccommodation->visible;
										$oInquiryAccommodation->groups_accommodation_id	= (int)$oAccommodation->id;

										$additionalServices = $oAccommodation->additionalservices;

										// Alle gespeicherten holen
										$aSavedAdditionalServices = $oInquiryAccommodation->getJoinedObjectChilds('additionalservices');

										foreach($additionalServices as $additionalServiceData) {

											$bFound = false;
											foreach($aSavedAdditionalServices as $iSavedAdditionalService=>$oSavedAdditionalService) {

												if($oSavedAdditionalService->additionalservice_id == $additionalServiceData['additionalservice_id']) {
													$bFound = true;
													break;
												}
											}

											if($bFound !== true) {
												$oJourneyAdditionalService = $oInquiryAccommodation->getJoinedObjectChild('additionalservices');
												$oJourneyAdditionalService->additionalservice_id = $additionalServiceData['additionalservice_id'];
												$oJourneyAdditionalService->journey_id = $oInquiryAccommodation->journey_id;
											} else {
												unset($aSavedAdditionalServices[$iSavedAdditionalService]);
											}

										}

										foreach($aSavedAdditionalServices as $oSavedAdditionalService) {
											$oSavedAdditionalService->delete();
										}

										$oInquiryAccommodation->save();

										$bAccoChanged = true;
									}

									if(
										$oInquiry->confirmed > 0 &&
										$bAccoChanged &&
										$bChange != 'new'
									){
										Ext_Thebing_Inquiry_Document_Version::setChange($oInquiry->id, $oInquiryAccommodation->id, 'accommodation');
										// und das die zusatzkosten verändert wurden ( da sie zur unterkunft gehören )
										$aAdditionalAccommodationList	= (array)$oInquiryAccommodation->getAdditionalCosts();
										
										foreach($aAdditionalAccommodationList as $oCost){
											$iCostId = (int)$oCost->id;
											Ext_Thebing_Inquiry_Document_Version::setChange($oInquiry->id, $iCostId, 'additional_accommodation', 'edit', $oInquiryAccommodation->id);
										}

									}

								}
					
								// Wenn es eine ÄNDERUNG ist Matching anpassen
								if(
									$aOriginalAcco['id'] > 0 && 
									$bChange
								) {

									// Wenn die Acco nicht gelöscht wird die Zeiten anpassen 
									// NUR wenn sich die wesentlichen Punkte NICH verändert haben
									if(
										$oInquiryAccommodation->active == 1 &&
										$oInquiryAccommodation->accommodation_id == $aOriginalAcco['accommodation_id'] &&
										$oInquiryAccommodation->roomtype_id == $aOriginalAcco['roomtype_id'] &&
										$oInquiryAccommodation->meal_id == $aOriginalAcco['meal_id']
									){ 
										// Matching anpassen
										$aOldAcco = array('from'=>$aOriginalAcco['from'], 'until' => $aOriginalAcco['until']);
										$aNewAcco = array('from'=>$oInquiryAccommodation->from, 'until' => $oInquiryAccommodation->until);

										$oInquiryAccommodation->saveMatchingChange($aNewAcco, $aOldAcco);
									} else {
										// Hier werden die Matching Einträge gelöscht die NICHT mehr auf die
										// NEUE Kombination passt
										$oInquiryAccommodation->deleteUnfittingAllocations($aOriginalAcco);
									}
								}
								

							}
						}	
					}
				}

				// Alle Gruppenmitglieder als geändert markieren
				// TODO: Eigentlich müsste hier der Cache der Versions-History gelöscht werden, aber das geht wegen dem Key-Aufbau nicht
				if(
					$bSuccess &&
					$bChangedInvoice
				) {
					foreach($aInquirys as $oInquiry) {
						Ext_Thebing_Inquiry_Document_Version::setChange($oInquiry->id, 0, 'group', 'edit');
					}
				}

				/*
				 * Items der Rechnungen von gelöschten Kunden umschreiben #9443
				 *
				 * Da jeder Kunde der Gruppe eine eigene Rechnung hat, gehört die Rechnung nicht mehr zur Gruppe,
				 * wenn der Kunde rausfliegt. Logischerweise ändert sich dann einfach der Rechnungsbetrag und das ist ziemlicher Mist.
				 * Die Items der gelöschten Kunden werden hier auf eine andere Rechnung der Gruppe umgeschrieben,
				 * damit der Betrag derselbe bleibt (Anzahl auch). Lediglich der Einzelbetrag der entsprechenden Buchung,
				 * bei welcher der Betrag zugewiesen wurde, verändert sich, aber das ist besser als Anomalien.
				 */
				if(
					$bSuccess &&
					!empty($aDeletedInvoices)
				) {
					$oMainInquiry = $oGroup->getExistingMainDocumentInquiry();
					$mainInquiryInvoices = Ext_Thebing_Inquiry_Document_Search::search($oMainInquiry->id, 'invoice', true);
					
					// Wenn Gruppe eine Rechnung oder Proforma hatte, aber Hauptbuchung keine, Fehler anzeigen!
					if(
						$hasProformaOrInvoiceBeforeSaving === true &&
						empty($mainInquiryInvoices)
					) {
						throw new Ts\Exception\MessageException('Ein Schüler konnte nicht gelöscht werden, da es eine (Proforma-)Rechnung gibt, die nur diesem Schüler zugewiesen ist! Bitte bearbeiten Sie zuerst die Rechnung der Gruppe.');
					}
					
					if($oMainInquiry === null) {
						throw new RuntimeException('Couldn\'t find any main inquiry! ('.$oGroup->id.')');
					}

					foreach($aDeletedInvoices as $iInquiryId => $aInvoices) {
						foreach($aInvoices as $oDeletedDocument) {
							/** @var Ext_Thebing_Inquiry_Document $oDeletedDocument */
							$aOtherDocuments = $oDeletedDocument->getDocumentsOfSameNumber(false);

							if(!empty($aOtherDocuments)) {
								// Prüfen, ob die Main Inquiry dieselbe Rechnung hat, damit nicht vollkommenes Chaos entsteht
								$oAllocationDocument = null;
								foreach($aOtherDocuments as $oOtherDocument) {
									if($oOtherDocument->entity === Ext_TS_Inquiry::class && $oOtherDocument->entity_id == $oMainInquiry->id) {
										$oAllocationDocument = $oOtherDocument;
									}
								}

								// Wenn Main Inquiry die Rechnung nicht hat: Erstbeste Rechnung nehmen
								if($oAllocationDocument === null) {
									$oAllocationDocument = reset($aOtherDocuments);
								}

								// Items kopieren und zuweisen
								$oAllocationVersion = $oAllocationDocument->getLastVersion();
								$aItems = $oDeletedDocument->getLastVersion()->getItemObjects(true);
								foreach($aItems as $oOldItem) {
									$oNewItem = $oOldItem->createCopy(null, null, ['save' => false]);
									$oNewItem->version_id = $oAllocationVersion->id;;
									$oNewItem->save();

									// Zahlungen auf das neue Item (vom anderen Dokument) umschreiben
									$oOldItem->refreshPaymentData($oNewItem, true);
								}

								// Total wichtig für $oInquiry->getAmount(), wenn mehr als ein Schüler gelöscht wird!
								$oAllocationVersion->bUseAmountCache = false;

								// Price-Index neu aufbauen
								$oAllocationVersion->refreshPriceIndex();
								$oAllocationInquiry = $oAllocationDocument->getInquiry();

								$oLog = Log::getLogger();
								$oLog->addInfo('Group inquiry deletion: Merged document items', [
									'group_id' => $oGroup->id,
									'origin_docuemnt' => $oDeletedDocument->id,
									'target_document' => $oAllocationDocument->id,
									'allocation_inquiry' => $oAllocationInquiry->id
								]);

							} else {
								// Wenn es gar keine andere Rechnung gibt, muss die Rechnung einer anderen Buchung zugewiesen werden
								// Beispiel: Gruppe und Rechnung mit Schüler 1, Schüler 2 hinzufügen (hat keine Rechnung), Schüler 1 löschen
								// Wenn die Gruppe mit Proforma oder Rechnung nur einen Schüler hat, ist das oben abgefangen (LAST_STUDENT_DELETION)
								$oDeletedDocument->entity = Ext_TS_Inquiry::class;
								$oDeletedDocument->entity_id = $oMainInquiry->id;
								$oDeletedDocument->setAutoGenerateNumber(false); // Importierte Rechnungen und sonstiger Spaß
								$oDeletedDocument->save(true); // Sofort speichern, damit Rechnung nicht wieder in removeDocuments() gefunden wird
								$oAllocationInquiry = $oMainInquiry;

								// Wenn das Dokument einer nicht bestätigen Buchung zugewiesen wird, muss diese bestätigt werden
								if(!$oAllocationInquiry->isConfirmed()) {
									$oAllocationInquiry->setInquiryStatus($oDeletedDocument->type);
								}

								$oLog = Log::getLogger();
								$oLog->addInfo('Group inquiry deletion: Assigned document to inquiry (no other document available!)', [
									'group_id' => $oGroup->id,
									'origin_docuemnt' => $oDeletedDocument->id,
									'allocation_inquiry' => $oAllocationInquiry->id
								]);

							}

							// Betragsfelder in der Buchung aktualisieren (hier wird ganz oft save() aufgerufen)
							$oAllocationInquiry->getAmount(false, true);
							$oAllocationInquiry->getAmount(true, true);
							//$oAllocationInquiry->getCreditAmount(true);
							$oAllocationInquiry->calculatePayedAmount(); // Items wurden umgeschrieben, daher neu berechnen
							Ext_Gui2_Index_Stack::add('ts_inquiry', $oAllocationInquiry->id, 0);

						}

						// Alle Rechnungen der (aus der Gruppe) gelöschten Buchung löschen
						$oDeletedInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);
						$oDeletedInquiry->removeDocuments('invoice_with_creditnote');
						$oDeletedInquiry->calculatePayedAmount(); // Items wurden umgeschrieben, daher neu berechnen (ggf. Overpayment vorhanden?)
						$oDeletedInquiry->save();

					}

				}

				// Nochmal speichern, um service_from und _until zu speichern
				foreach((array)$aInquirys as $oInquiry) {

                    if(
                       isset($aChangedGroupFlags[$oInquiry->id]) && 
                       isset($aChangedGroupFlags[$oInquiry->id]['guide']) &&
                       $aChangedGroupFlags[$oInquiry->id]['guide'] == true
                    ) {

                        $oJourney = $oInquiry->getJourney();
                        $aAcc = $oJourney->getJoinedObjectChilds('accommodations');
						/** @var Ext_TS_Inquiry_Journey_Accommodation[] $aAcc */

                        foreach($aAcc as $oAcc) {
                            $aCost  = $oAcc->getAdditionalCosts();
                            foreach($aCost as $oCost) {
                                Ext_Thebing_Inquiry_Document_Version::setChange($oInquiry->id, $oCost->id, 'additional_accommodation', 'edit', $oAcc->id);
                            }
                        }
                    }

					$oInquiry->refreshServicePeriod();
					$oInquiry->save();

					// Index-Eintrag manuell ergänzen, da WDBasic in der Inbox Ext_TS_Inquiry_Index ist und hier Ext_TS_Inquiry,
					// wird sonst beim neu anlegen nicht hinzugefügt
					//Ext_Gui2_Index_Stack::add('ts_inquiry', $oInquiry->id, 0);

					// Tuition-Index aktualisieren
					$oCourseTuitionIndex = new Ext_TS_Inquiry_TuitionIndex($oInquiry);
					$oCourseTuitionIndex->update();

				}

			} catch (Exception $e) {

				if(System::d('debugmode') == 2) {
					throw $e;
				}

				$bSuccess = false;
				
				if($e instanceof \Ts\Exception\MessageException) {
					$aErrors[$iErrorCount++]['message'] = $e->getMessage();
				} else {
					$aErrors[$iErrorCount++]['message'] = L10N::t('Es ist ein Systemfehler aufgetreten, der Vorfall wurde gemeldet', $this->_oGui->gui_description);	
				}

				// error(); <= verursacht endlosschleifen ;)
				Ext_Thebing_Util::reportError('Gruppe speichern', $e);
			}

			if($iErrorCount > 0 || !empty($aErrors)){				
				array_unshift($aErrors, array('message' => $this->_getErrorMessage('SAVE_TITLE', '')));
			}
			
			$aErrors = array_values($aErrors);

			$aSelectedIds = array($oGroup->id);
			// Dialog Daten neu holden und übermitteln
			$aData = $this->prepareOpenDialog('edit', $aSelectedIds);

			$aData['id']			= 'GROUP_'.implode('_', (array)$aSelectedIds);
			$aData['save_id']		= $oGroup->id;

			$aTransfer				= array();
			$aTransfer['dialog_id_tag'] = 'GROUP_';
			$aTransfer['action'] 	= 'saveDialogCallback';
			$aTransfer['error'] 	= $aErrors;
			$aTransfer['data'] 		= $aData;

			if(
				!$bSuccess && 
				count($aTransfer['error']) <= 0
			) {
				$aTransfer['error'][] = array('message'=>L10N::t('Fehler beim Speichern', $this->_oGui->gui_description));
			}

		}

		if(
			$bSuccess &&
			empty($aTransfer['error'])
		) {
			DB::commit('save_inquiry_group');
		} else {
			DB::rollback('save_inquiry_group');
		}

		return $aTransfer;
	}

	/**
	 *
	 * Methode um eine einzelne Spalte einer Zeile zu updaten
	 */
	public function updateOne($aParams){

		$mValue		= $aParams['value'];
		$iRowId		= $aParams['row_id'];
		$sColumn	= $aParams['column'];
		$sAlias		= $aParams['alias'];
		$sType		= $aParams['type'];
		
		if($sColumn == 'active' && ($sAlias == 'kg' || $sAlias == '' ) && $mValue == 0){
			// Logschreiben und löschen
			$oGroup = new Ext_Thebing_Inquiry_Group($iRowId);
			$oGroup->delete();

			return true;
		} else {

			$bSuccess = parent::updateOne($aParams);
			return $bSuccess;

		}
	}

	/*
	 * Daten zusätzlich zur standart delete Row noch mit gelöscht werden müssen
	 */
	protected function deleteRowHook($iRowId){

		$oDeletedGroup = Ext_Thebing_Inquiry_Group::getInstance($iRowId);

		/*
		 * Alle Gruppenkunden müssen ebenfalls gelöscht werden
		 */
		$aInquiries = $oDeletedGroup->getInquiries();
		foreach((array)$aInquiries as $oInquiry){
			$oInquiry->delete();
		}
	}

	/**
	 * WRAPPER Ajax Request verarbeiten
	 * @param $_VARS
	 * @return unknown_type
	 */
	public function switchAjaxRequest($_VARS){

		$aTransfer = array();
		
		include_once Util::getDocumentRoot().'/system/extensions/thebing/inquiry/gui2/include.switchajaxrequestgroup.php';
		
		$aTemp = (array)$aTransfer;

		// wenn switch zugetroffen hat
		if(!empty($aTemp)){
			echo json_encode($aTransfer);
			return true;
		}

		// sonst parent ( hier wird ein echo gestartet )
		parent::switchAjaxRequest($_VARS);
		
	}
	
	protected function _getErrorMessage($sError, $sField, $sLabel='', $sAction=null, $sAdditional=null) {
		$sMessage = '';
		$bTranslate = true;
		
		if(
			$sField == 'email'	
		){
			$sLabel = $this->t('E-Mail');
		}

		switch($sError){
			case 'NO_GENDER':
				$sMessage = 'Bitte füllen Sie das Feld "%s" aus.';
				break;
			case 'SAVE_TITLE':
				$sMessage = 'Fehler beim Speichern';
				break;
			case 'UNTIL_BEFORE_FROM':
				// TODO INVALID_DATE_UNTIL_BEFORE_FROM
				$sMessage = 'Das Enddatum darf nicht vor dem Startdatum liegen.';
				break;
			case 'LAST_STUDENT_DELETION':
				$sMessage = 'Der letzte Schüler kann aufgrund von bestehenden Rechnungen nicht aus der Gruppe gelöscht werden.';
				break;
			default:
				$sMessage = parent::_getErrorMessage($sError, $sField, $sLabel);
				$bTranslate = false;
		}

		if($bTranslate){
			$sMessage = $this->t($sMessage);
			if(!empty($sLabel)){
				$sMessage = sprintf($sMessage, $sLabel);
			}
		}

		return $sMessage;
	}
	
	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab = false, $sAdditional = false, $bSaveSuccess = true)
	{
		$aData					= parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);
		
		if(!$this->oWDBasic)
		{
			$this->_getWDBasicObject($aSelectedIds);
		}
		
		$oWDBasic = $this->oWDBasic;

		$aMembers = $oWDBasic->getInquiries();
		
		$iDisableCurrencySelect = 0;
		
		foreach($aMembers as $oMember)
		{
			$iLastInquiryDoc = Ext_Thebing_Inquiry_Document_Search::search($oMember->id, 'invoice');
			
			if($iLastInquiryDoc > 0)
			{
				$iDisableCurrencySelect = 1;
				break;
			}
		}
		
		$aData['bDisableCurrencySelect']	= $iDisableCurrencySelect;

		return $aData;
	}

	/**
	 * Dialog: Gruppen bearbeiten
	 *
	 * @param Ext_Gui2 $oGui
	 * @return Ext_Gui2_Dialog
	 */
	public static function createDialog(Ext_Gui2 $oGui) {

		$sBackendLanguage = System::d('systemlanguage');

		$oClient = Ext_Thebing_Client::getFirstClient();
		$oSchool = Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
		$aSchools = $oClient->getSchools(true);
		$aCountries = (array)Ext_Thebing_Data::getCountryList(true, true);
		$aNationalities = (array)Ext_Thebing_Nationality::getNationalities(true, $sBackendLanguage, 0);
		$aNationalities	= Ext_Thebing_Util::addEmptyItem($aNationalities, Ext_Thebing_L10N::getEmptySelectLabel('nationalities'));
		$aLanguages = (array)Ext_Thebing_Data::getLanguageSkills(true);
		$aCorrespondenceLanguages = (array)Ext_Thebing_Data::getCorrespondenceLanguages();
		$aPaymentMethods = (array)Ext_Thebing_Inquiry_Amount::getPaymentMethods();
		$aCurrencies = (new Ext_Thebing_Currency_Util($oSchool->id))->getCurrencyList(2);
		$aServiceSettings = Ext_Thebing_Inquiry_Gui2_Group::getInquiryDataOptions();
		$aServiceSettingsGuide = Ext_Thebing_Inquiry_Gui2_Group::getGuideDataOptions();
		$aTransferOptions = (array)Ext_Thebing_Data::getTransferList();
		
		$oDialog = $oGui->createDialog($oGui->t('Gruppe "{name}" bearbeiten'), $oGui->t('Neue Gruppe erstellen'));
		$oDialog->sDialogIDTag = 'GROUP_';
		$oDialog->width = 1200;
		$oDialog->height = 1200;

		$oTab1 = $oDialog->createTab($oGui->t('Allgemein'));
		$oTab2 = $oDialog->createTab($oGui->t('Kurse'));
		$oTab3 = $oDialog->createTab($oGui->t('Unterkünfte'));
		$oTab4 = $oDialog->createTab($oGui->t('Transfer'));
		$oTab5 = $oDialog->createTab($oGui->t('Kunden'));
		$oTab6 = $oDialog->createTab($oGui->t('Kurse Gruppenleiter'));
		$oTab7 = $oDialog->createTab($oGui->t('Unterkünfte Gruppenleiter'));

		$oTab1->aOptions = ['access' => '', 'task' => 'general_data', 'section' => ['groups_enquiries_bookings']];
		$oTab2->aOptions = ['access' => 'thebing_tuition_icon', 'task' => 'course_data'];
		$oTab3->aOptions = ['access' => 'thebing_accommodation_icon', 'task' => 'accommodation_data'];
		$oTab4->aOptions = ['access' => 'thebing_pickup_icon', 'task' => 'transfer_data', 'section' => 'inquiries_groups_transfer'];
		$oTab5->aOptions = ['access' => '', 'task' => 'customer_data', 'class'=>'customer_data_tab'];
		$oTab6->aOptions = ['access' => 'thebing_tuition_icon', 'task' => 'course_guide_data'];
		$oTab7->aOptions = ['access' => 'thebing_accommodation_icon', 'task' => 'accommodation_guide_data'];

		if(Ext_Thebing_System::isAllSchools()) {
			$oTab1->setElement($oDialog->createRow($oGui->t('Schule'), 'select', [
				'db_column' => 'school_id',
				'db_alias' => 'kg',
				'select_options' => $aSchools,
				'required' => true,
				'class'=> 'school_select',
			]));
		} else {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$oTab1->setElement($oDialog->createSaveField('hidden', array('name' => 'school_id', 'value' => $oSchool->id, 'class'=> 'school_select')));
		}

		$oTab1->setElement($oDialog->createRow($oGui->t('Name'), 'input', [
			'db_column' => 'name',
			'db_alias' => 'kg',
			'required' => true
		]));

		$iMaxLength = 10;
		$oTab1->setElement($oDialog->createRow(
			sprintf($oGui->t('Kürzel (max. %1$d Zeichen)'), $iMaxLength),
			'input', [
				'db_column' => 'short',
				'db_alias' => 'kg',
				'required' => true,
				'class' => 'w100',
				'max_length' => $iMaxLength,
			]
		));

		$oTab1->setElement($oDialog->create('h4')->setElement($oGui->t('Sprachen')));

		$oTab1->setElement($oDialog->createRow($oGui->t('Nationalität'), 'select', [
			'db_column' => 'nationality_id',
			'db_alias' => 'kg',
			'select_options' => $aNationalities,
			'required' => true
		]));

		$oTab1->setElement($oDialog->createRow($oGui->t('Muttersprache'), 'select', [
			'db_column' => 'language_id',
			'db_alias' => 'kg',
			'select_options' => $aLanguages,
			'required' => true
		]));

		$oTab1->setElement($oDialog->createRow($oGui->t('Korrespondenzsprache'), 'select', [
			'db_column' => 'correspondence_id',
			'db_alias' => 'kg',
			'select_options' => $aCorrespondenceLanguages,
			'required' => true
		]));

		$oTab1->setElement($oDialog->create('h4')->setElement($oGui->t('Adresse')));

		$oTab1->setElement($oDialog->createRow($oGui->t('Adresse'), 'textarea', [
			'db_column' => 'address',
			'db_alias' => 'kg'
		]));

		$oTab1->setElement($oDialog->createRow($oGui->t('Adresszusatz'), 'textarea', [
			'db_column' => 'address_addon',
			'db_alias' => 'kg'
		]));

		$oTab1->setElement($oDialog->createRow($oGui->t('PLZ'), 'input', [
			'db_column' => 'plz',
			'db_alias' => 'kg'
		]));

		$oTab1->setElement($oDialog->createRow($oGui->t('Stadt'), 'input', [
			'db_column' => 'city',
			'db_alias' => 'kg'
		]));

		$oTab1->setElement($oDialog->createRow($oGui->t('Staat'), 'input', [
			'db_column' => 'state',
			'db_alias' => 'kg'
		]));

		$oTab1->setElement($oDialog->createRow($oGui->t('Land'), 'select', [
			'db_column' => 'country',
			'db_alias' => 'kg',
			'select_options' => $aCountries
		]));

		$oTab1->setElement($oDialog->create('h4')->setElement($oGui->t('Ansprechpartner')));

		$oTab1->setElement($oDialog->createRow($oGui->t('Vorname'), 'input', [
			'db_column' => 'firstname',
			'db_alias' => 'cdb1'
		]));

		$oTab1->setElement($oDialog->createRow($oGui->t('Nachname'), 'input', [
			'db_column' => 'lastname',
			'db_alias' => 'cdb1'
		]));

		$oTab1->setElement($oDialog->createRow($oGui->t('E-Mail'), 'input', [
			'db_column' => 'email',
			'db_alias' => 'cdb1'
		]));

		$oTab1->setElement($oDialog->createRow($oGui->t('Kunde/Teilnehmer'), 'checkbox', [
			'db_column' => 'contact_is_customer',
			'db_alias' => 'kg'
		]));

		$oTab1->setElement($oDialog->create('h4')->setElement($oGui->t('Agentur & Währung')));

		$oTab1->setElement($oDialog->createRow($oGui->t('Agentur'), 'select', [
			'db_column' => 'agency_id',
			'db_alias' => 'kg',
			'selection' => new Ext_TS_Inquiry_Gui2_Selection_Agency(),
			'events' => [
				[
					'event' => 'change',
					'function'  => 'reloadAgencyDependingFields',
					'parameter' => 'aDialogData, 1, "kg"'
				]
			]
		]));

		$oTab1->setElement($oDialog->createRow($oGui->t('Agenturmitarbeiter'), 'select', [
			'db_column' => 'agency_contact_id',
			'db_alias' => 'kg',
			'select_options' => [],
		]));

		$oTab1->setElement($oDialog->createRow($oGui->t('Zahlungsmethode'), 'select', [
			'db_column' => 'payment_methode_group',
			'db_alias' => 'kg',
			'select_options' => $aPaymentMethods,
		]));

		$oTab1->setElement($oDialog->createRow($oGui->t('Zahlungskommentar'), 'textarea', [
			'db_column' => 'payment_method_comment_group',
			'db_alias' => 'kg',
		]));

		$oTab1->setElement($oDialog->createRow($oGui->t('Währung'), 'select', [
			'db_column' => 'currency_id',
			'db_alias' => 'kg',
			'select_options' => $aCurrencies,
			'required' => true
		]));

		$oTab1->setElement($oDialog->createRow($oGui->t('Vertriebsmitarbeiter'), 'select', [
			'db_column' => 'sales_person_id',
			'db_alias' => 'kg',
			'selection' => new Ext_Thebing_Gui2_Selection_User_SalesPerson(),
		]));

		$oTab1->setElement($oDialog->create('h4')->setElement($oGui->t('Einstellungen - Gruppenmitglieder')));

		$oTab1->setElement($oDialog->createRow($oGui->t('Automatische E-Mails'), 'checkbox', [
			'db_column' => 'newsletter',
			'db_alias' => 'kg',
			'default_value' => '1'
		]));

		$oTab1->setElement($oDialog->createRow($oGui->t('geschlossener Unterricht'), 'select', [
			'db_column' => 'course_closed',
			'db_alias' => 'kg',
			'select_options' => Ext_Thebing_Util::getYesNoArray(),
		]));

		$oTab1->setElement($oDialog->createRow($oGui->t('Kurse'), 'select', [
			'db_column' => 'course_data',
			'db_alias' => 'kg',
			'select_options' => $aServiceSettings,
		]));

		$oTab1->setElement($oDialog->createRow($oGui->t('Unterkünfte'), 'select', [
			'db_column' => 'accommodation_data',
			'db_alias' => 'kg',
			'select_options' => $aServiceSettings,
		]));

		$oTab1->setElement($oDialog->createRow($oGui->t('Transfer'), 'select', [
			'db_column' => 'transfer_data',
			'db_alias' => 'kg',
			'select_options' => $aServiceSettings,
		]));

		$oTab1->setElement($oDialog->create('h4')->setElement($oGui->t('Einstellungen - Gruppenleiter')));

		$oTab1->setElement($oDialog->createRow($oGui->t('Kurse für Gruppenleiter'), 'select', [
			'db_column' => 'course_guide',
			'db_alias' => 'kg',
			'select_options' => $aServiceSettingsGuide,
		]));

		$oTab1->setElement($oDialog->createRow($oGui->t('Unterkunft für Gruppenleiter'), 'select', [
			'db_column' => 'accommodation_guide',
			'db_alias' => 'kg',
			'select_options' => $aServiceSettingsGuide,
		]));

		// Unterkunft
		$oTab3->setElement($oDialog->createRow($oGui->t('Kommentar'), 'textarea', [
			'db_column' => 'accommodation_comment',
			'db_alias' => 'kg'
		]));

		// Transfer
		$oTab4->setElement($oDialog->createRow($oGui->t('Transfer'), 'select', [
			'db_column' => 'transfer_mode',
			'db_alias' => 'kg',
			'select_options' => $aTransferOptions
		]));

		$oDialog->setElement($oTab1);
		$oDialog->setElement($oTab2);
		$oDialog->setElement($oTab6);
		$oDialog->setElement($oTab3);
		$oDialog->setElement($oTab7);
		$oDialog->setElement($oTab4);
		$oDialog->setElement($oTab5);

		System::wd()->executeHook('ts_inquiry_group_create_dialog', $oDialog);

		$aButton = array(
			'label'	=> $oGui->t('Kunden importieren'),
			'task'	=> 'openImportCustomerModal',
			'id'	=> 'import_customers',
			'default' => true
		);
		$oDialog->aButtons = array($aButton);

		return $oDialog;
	}

	/**
	 * @param Ext_Gui2|null $oGui
	 * @return \Elastica\Query\BoolQuery
	 */
	public static function getIndexWhere(Ext_Gui2 $oGui = null) {

		$aWhere = Ext_TS_Inquiry_Index_Gui2_Data::getListWhere($oGui);

		$oBool = new \Elastica\Query\BoolQuery();

		if(is_array($aWhere['school_id'])) {
			// All Schools
			$oQuery = new \Elastica\Query\Terms('school_id', $aWhere['school_id'][1]);
			$oBool->addMust($oQuery);
		} else {
			$oQuery = new \Elastica\Query\Term();
			$oQuery->setTerm('school_id', $aWhere['school_id']);
			$oBool->addMust($oQuery);
		}

		if(
			$oGui instanceof Ext_Gui2 &&
			!empty($oGui->getOption('inbox_id'))
		) {

			$oInboxBool = new \Elastica\Query\BoolQuery();
			$oInboxBool->setMinimumShouldMatch(1);

			// Gruppen ohne Schüler haben keine Inbox, daher überall anzeigen
			$oSubBool = new \Elastica\Query\BoolQuery();
			$oQuery = new \Elastica\Query\Exists('inbox_id');
			$oSubBool->addMustNot($oQuery);
			$oInboxBool->addShould($oSubBool);

			$oQuery = new \Elastica\Query\Term();
			$oQuery->setTerm('inbox_id', $oGui->getOption('inbox_id'));
			$oInboxBool->addShould($oQuery);

			$oBool->addMust($oInboxBool);

		}

		return $oBool;

	}

	/**
	 * @return array
	 */
	public static function getIndexOrderBy() {
		return ['created_original' => 'DESC'];
	}
	
	/**
	 * 
	 * @param Ext_TS_Inquiry $oInquiry
	 * @param Ext_TS_Inquiry_Contact_Traveller $oCustomer
	 * @param type $sData
	 * @return void
	 */
	public function setCustomerData(Ext_TS_Inquiry $oInquiry, Ext_TS_Inquiry_Contact_Traveller $oCustomer, $sData) {
		
		$aData = [];
		
		if(!empty($sData)) {
			$aData = json_decode($sData, true);
		}
		
		if(empty($aData)) {
			return;
		}

		$aStudentStatus = Ext_Thebing_Marketing_StudentStatus::getList(true, $oInquiry->getSchool()->id);

		$oEmergencyContact = $oInquiry->getEmergencyContact();
		$oMatchingData = $oInquiry->getMatchingData();
		$oVisaData = $oInquiry->getVisaData();
		
		$aValues = [
			'email' => [
				'object' => $oCustomer
			],
			'address' => [
				'object' => $oCustomer,
				'prefix' => 'address_'
			],
			'address_addon' => [
				'object' => $oCustomer,
				'prefix' => 'address_'
			],
			'zip' => [
				'object' => $oCustomer,
				'prefix' => 'address_'
			],
			'city' => [
				'object' => $oCustomer,
				'prefix' => 'address_'
			],
			'state' => [
				'object' => $oCustomer,
				'prefix' => 'address_'
			],
			'country' => [
				'object' => $oCustomer,
				'field' => 'address_country_iso',
				'function' => ['Ext_Thebing_Format', 'parseCountry']
			],
			'phone' => [
				'object' => $oCustomer,
				'field' => 'detail_phone_private'
			],
			'phone_mobile' => [
				'object' => $oCustomer,
				'prefix' => 'detail_'
			],
			'emergency_contact' => [
				'object' => $oEmergencyContact,
				'field' => 'lastname'
			],
			'emergency_phone' => [
				'object' => $oEmergencyContact,
				'field' => 'detail_phone_private'
			],
			'emergency_email' => [
				'object' => $oEmergencyContact,
				'field' => 'email'
			],
			'comment' => [
				'object' => $oCustomer,
				'prefix' => 'detail_'
			],
			'passport_number' => [
				'object' => $oVisaData
			],
			'passport_valid_until' => [
				'object' => $oVisaData,
				'field' => 'passport_due_date',
				'function' => ['Ext_Thebing_Format', 'parseExcelDate']
			],
			'passport_date_of_issue' => [
				'object' => $oVisaData,
				'function' => ['Ext_Thebing_Format', 'parseExcelDate']
			],
			'visa_required' => [
				'object' => $oVisaData,
				'field' => 'required',
				'function' => ['Ext_Thebing_Format', 'parseBooleanValue'],
				'function_additional' => [0=>0,1=>1]
			],
			'visa_valid_from' => [
				'object' => $oVisaData,
				'field' => 'date_from',
				'function' => ['Ext_Thebing_Format', 'parseExcelDate']
			],
			'visa_valid_until' => [
				'object' => $oVisaData,
				'field' => 'date_until',
				'function' => ['Ext_Thebing_Format', 'parseExcelDate']
			],
			'matching_allergies' => [
				'object' => $oMatchingData,
				'field' => 'acc_allergies'
			],
			'matching_smoker' => [
				'object' => $oMatchingData,
				'field' => 'acc_smoker',
				'function' => ['Ext_Thebing_Format', 'parseBooleanValue'],
				'function_additional' => [0=>1,1=>2]
			],
			'matching_vegetarian' => [
				'object' => $oMatchingData,
				'field' => 'acc_vegetarian',
				'function' => ['Ext_Thebing_Format', 'parseBooleanValue'],
				'function_additional' => [0=>1,1=>2]
			],
			'matching_muslim_diat' => [
				'object' => $oMatchingData,
				'field' => 'acc_muslim_diat',
				'function' => ['Ext_Thebing_Format', 'parseBooleanValue'],
				'function_additional' => [0=>1,1=>2]
			],
			'matching_comment' => [
				'object' => $oMatchingData,
				'field' => 'acc_comment'
			],
			'matching_comment2' => [
				'object' => $oMatchingData,
				'field' => 'acc_comment2'
			],
			'student_status' => [
				'object' => $oInquiry,
				'field' => 'status_id',
				'function' => ['Ext_Thebing_Format', 'getArrayValue'],
				'function_additional' => array_flip($aStudentStatus)
			],
			'social_security_number' => [
				'object' => $oInquiry,
				'field' => 'social_security_number'
			]
		];

		foreach($aValues as $sField=>$aField) {
			$this->setCustomerDataValue($sField, $aField, $aData);
		}

	}
	
	protected function setCustomerDataValue($sField, $aField, $aData) {
		
		// Werte werden nicht durch leere Werte überschrieben!
		if(empty($aData[$sField])) {
			return;
		}

		$oEntity = $aField['object'];
		
		if(isset($aField['prefix'])) {
			$sTargetField = $aField['prefix'].$sField;
		} elseif(isset($aField['field'])) {
			$sTargetField = $aField['field'];
		} else {
			$sTargetField = $sField;
		}

		$mValue = $aData[$sField];
		
		if(isset($aField['function'])) {
			if(isset($aField['function_additional'])) {
				$mValue = call_user_func($aField['function'], $mValue, $aField['function_additional']);
			} else {
				$mValue = call_user_func($aField['function'], $mValue);
			}
		}

		#__pout(get_class($oEntity).'-'.$sTargetField.'-'.$mValue);

		$oEntity->$sTargetField = $mValue;

	}

	/**
	 * @inheritdoc
	 */
	public function getFlexEditDataHTML(Ext_Gui2_Dialog $oDialog, $mSection, $iId, $iReadOnly = 0, $iDisabled = 1, $sFieldIdentifier = 'flex') {

		$sHtml = '';

		// Warnung anzeigen für unsere schlauen Kunden, sofern flexible Felder existieren
		foreach((array)$mSection as $sSection) {
			$aFields = Ext_TC_Flexibility::getFields($sSection);
			if(!empty($aFields)) {
				$oNotification = $oDialog->createNotification(
					$this->_oGui->t('Achtung'),
					$this->_oGui->t('Individuelle Felder werden nicht in die einzelnen Datensätze der Schüler übertragen.'),
					'info'
				);
				$sHtml .= $oNotification->generateHTML();
				break;
			}
		}

		$sHtml .= parent::getFlexEditDataHTML($oDialog, $mSection, $iId, $iReadOnly, $iDisabled, $sFieldIdentifier);

		return $sHtml;

	}
	
	public function requestCourseInfo($aData) {
		
		$oDataInquiry = new Ext_Thebing_Inquiry_Gui2($this->_oGui);
		$aTransfer = $oDataInquiry->requestCourseInfo($aData);
                
		return $aTransfer;
	}

	protected function getImportDialogId() {
		return 'GROUP_IMPORT_';
	}

	protected function getImportService(): \Tc\Service\Import\AbstractImport {
		return new \Ts\Service\Import\Group();
	}

}
