<?php

class Ext_Thebing_System_Checks_Pdfimport extends GlobalChecks {
	
	public function isNeeded(){
		global $user_data;
		
		// wenn bereits ausgeführt
		if($this->checkClient()){
			return false;
		}
		$oClient = new Ext_Thebing_Client($user_data['client']);
		$aSchools = $oClient->getSchools(true);
		
		// wenn keine schulen
		if(count($aSchools) <= 0){
			return false;
		}

		return true;
	}

	/**
	 * Dont Set the Check to active = 0
	 */
	public function updateCheck(){

	}
	
	public function modifyCheckData(){
		global $user_data;
		
		$oClient = new Ext_Thebing_Client($user_data['client']);
		$aMaster = $oClient->getMasterUser();

		if($aMaster['id'] != $user_data['id'] && empty($this->_aFormErrors)){
			$this->_aFormErrors[] = 'Only your master user has access!';
			$this->bError = true;
			return false; 
		}
		
		return true;
		
	}
	
	public function checkClient(){
		global $user_data;
		$sSql = " SELECT 
						* 
					FROM 
						`kolumbus_system_checks` 
					WHERE 
						`check_id` = :check_id AND
						`client_id` = :client_id AND 
						`status` = 1";
		$aSql = array(
						'check_id' => (int)$this->_aCheck['id'],
						'client_id' =>(int)$user_data['client']);		
		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		if(empty($aResult)){
			return false;
		}
		return true;
	}
	
	public function saveClient(){
		global $user_data;
		$sSql = " INSERT INTO
						`kolumbus_system_checks` 
					SET 
						`check_id` = :check_id,
						`client_id` = :client_id,
						`status` = 1";
		$aSql = array(
						'check_id' => (int)$this->_aCheck['id'],
						'client_id' =>(int)$user_data['client']);		
		DB::executepreparedQuery($sSql, $aSql);
	}
	
	public function executeCheck(){
		global $user_data, $_VARS;

		if($user_data['client'] <= 0){
			return false;
		}

		require_once \Util::getDocumentRoot().'system/includes/kolumbus.inc.php';
		
		$oClient = new Ext_Thebing_Client($user_data['client']);
		
		$aSchools = $oClient->getSchools(true);
		reset($aSchools);

        $sPdfDin = 'A4';
		
		$aBorder = array();
		$aBorder['right'] 	= 20;
		
		if($sPdfDin == 'Letter'){
			$aBorder['left'] 	= 17;
			$aBorder['top'] 	= 40;
			$aBorder['bottom'] 	= 30;
			
			$iWidth 			= 216;
			$iHeight 			= 279;
			$sFormat 			= 'a4';
		} else {
			$aBorder['left'] 	= 20;
			$aBorder['top'] 	= 50;
			$aBorder['bottom'] 	= 30;
			
			$iWidth 			= 210;
			$iHeight 			= 297;
			$sFormat 			= 'letter';
		}
		
		$aTypeIds = array();
		
		// Template Typen Speichern
		$oType = new Ext_Thebing_Pdf_Template_Type(0);
		$oType->name 							= 'Invoice';
		$oType->active 							= 1;
		$oType->client_id 						= $user_data['client'];
		$oType->user_id 						= $user_data['id'];
		$oType->font_size 						= 10;
		$oType->page_format 					= $sFormat;
		$oType->page_format_width 				= $iWidth;
		$oType->page_format_height 				= $iHeight;
		$oType->first_page_border_top 			= $aBorder['top'];
		$oType->first_page_border_right 		= $aBorder['right'];
		$oType->first_page_border_bottom 		= $aBorder['bottom'];
		$oType->first_page_border_left 			= $aBorder['left'];
		$oType->additional_page_border_top 		= $aBorder['top'];
		$oType->additional_page_border_right 	= $aBorder['right'];
		$oType->additional_page_border_bottom 	= $aBorder['bottom'];
		$oType->additional_page_border_left 	= $aBorder['left'];
		$oType->element_date 					= 1;
		$oType->element_date_x 					= 150;
		$oType->element_date_y 					= $aBorder['top']+30;
		$oType->element_address 				= 1;
		$oType->element_address_x 				= $aBorder['left'];
		$oType->element_address_y 				= $aBorder['top'];
		$oType->element_subject 				= 1;
		$oType->element_subject_x 				= $aBorder['left'];
		$oType->element_subject_y 				= $aBorder['top']+30;
		$oType->element_text1 					= 1;
		$oType->element_text1_x 				= $aBorder['left'];
		$oType->element_text1_y 				= $aBorder['top']+50;
		$oType->element_inquirypositions 		= 1;
		$oType->element_text2 					= 1;
		$oType->save();
		
		$aTypeIds['invoice'] = $oType->id;
		
		// Template Typen Speichern
		$oType = new Ext_Thebing_Pdf_Template_Type(0);
		$oType->name 							= 'LOA';
		$oType->active 							= 1;
		$oType->client_id 						= $user_data['client'];
		$oType->user_id 						= $user_data['id'];
		$oType->font_size 						= 10;
		$oType->page_format 					= $sFormat;
		$oType->page_format_width 				= $iWidth;
		$oType->page_format_height 				= $iHeight;
		$oType->first_page_border_top 			= $aBorder['top'];
		$oType->first_page_border_right 		= $aBorder['right'];
		$oType->first_page_border_bottom 		= $aBorder['bottom'];
		$oType->first_page_border_left 			= $aBorder['left'];
		$oType->additional_page_border_top 		= $aBorder['top'];
		$oType->additional_page_border_right 	= $aBorder['right'];
		$oType->additional_page_border_bottom 	= $aBorder['bottom'];
		$oType->additional_page_border_left 	= $aBorder['left'];
		$oType->element_date 					= 1;
		$oType->element_date_x 					= 150;
		$oType->element_date_y 					= $aBorder['top']+30;
		$oType->element_address 				= 1;
		$oType->element_address_x 				= $aBorder['left'];
		$oType->element_address_y 				= $aBorder['top'];
		$oType->element_subject 				= 1;
		$oType->element_subject_x 				= $aBorder['left'];
		$oType->element_subject_y 				= $aBorder['top']+30;
		$oType->element_text1 					= 1;
		$oType->element_text1_x 				= $aBorder['left'];
		$oType->element_text1_y 				= $aBorder['top']+50;
		$oType->save();
		
		$aTypeIds['loa'] = $oType->id;
		
		// Template Typen Speichern
		$oType = new Ext_Thebing_Pdf_Template_Type(0);
		$oType->name 							= 'Student PDFs';
		$oType->active 							= 1;
		$oType->client_id 						= $user_data['client'];
		$oType->user_id 						= $user_data['id'];
		$oType->font_size 						= 10;
		$oType->page_format 					= $sFormat;
		$oType->page_format_width 				= $iWidth;
		$oType->page_format_height 				= $iHeight;
		$oType->first_page_border_top 			= $aBorder['top'];
		$oType->first_page_border_right 		= $aBorder['right'];
		$oType->first_page_border_bottom 		= $aBorder['bottom'];
		$oType->first_page_border_left 			= $aBorder['left'];
		$oType->additional_page_border_top 		= $aBorder['top'];
		$oType->additional_page_border_right 	= $aBorder['right'];
		$oType->additional_page_border_bottom 	= $aBorder['bottom'];
		$oType->additional_page_border_left 	= $aBorder['left'];
		$oType->element_date 					= 1;
		$oType->element_date_x 					= 150;
		$oType->element_date_y 					= $aBorder['top']+30;
		$oType->element_address 				= 1;
		$oType->element_address_x 				= $aBorder['left'];
		$oType->element_address_y 				= $aBorder['top'];
		$oType->element_subject 				= 1;
		$oType->element_subject_x 				= $aBorder['left'];
		$oType->element_subject_y 				= $aBorder['top']+30;
		$oType->element_text1 					= 1;
		$oType->element_text1_x 				= $aBorder['left'];
		$oType->element_text1_y 				= $aBorder['top']+50;
		$oType->save();
		
		$aTypeIds['student'] = $oType->id;

		// Template Typen Speichern
		$oType = new Ext_Thebing_Pdf_Template_Type(0);
		$oType->name 							= 'Accommodation Communication';
		$oType->active 							= 1;
		$oType->client_id 						= $user_data['client'];
		$oType->user_id 						= $user_data['id'];
		$oType->font_size 						= 10;
		$oType->page_format 					= $sFormat;
		$oType->page_format_width 				= $iWidth;
		$oType->page_format_height 				= $iHeight;
		$oType->first_page_border_top 			= $aBorder['top'];
		$oType->first_page_border_right 		= $aBorder['right'];
		$oType->first_page_border_bottom 		= $aBorder['bottom'];
		$oType->first_page_border_left 			= $aBorder['left'];
		$oType->additional_page_border_top 		= $aBorder['top'];
		$oType->additional_page_border_right 	= $aBorder['right'];
		$oType->additional_page_border_bottom 	= $aBorder['bottom'];
		$oType->additional_page_border_left 	= $aBorder['left'];
		$oType->element_date 					= 1;
		$oType->element_date_x 					= 150;
		$oType->element_date_y 					= $aBorder['top']+30;
		$oType->element_address 				= 1;
		$oType->element_address_x 				= $aBorder['left'];
		$oType->element_address_y 				= $aBorder['top'];
		$oType->element_subject 				= 1;
		$oType->element_subject_x 				= $aBorder['left'];
		$oType->element_subject_y 				= $aBorder['top']+30;
		$oType->element_text1 					= 1;
		$oType->element_text1_x 				= $aBorder['left'];
		$oType->element_text1_y 				= $aBorder['top']+50;
		$oType->save();
		
		$aTypeIds['accommodation'] = $oType->id;
		
		// Template Typen Speichern
		$oType = new Ext_Thebing_Pdf_Template_Type(0);
		$oType->name 							= 'Receipt';
		$oType->active 							= 1;
		$oType->client_id 						= $user_data['client'];
		$oType->user_id 						= $user_data['id'];
		$oType->font_size 						= 10;
		$oType->page_format 					= $sFormat;
		$oType->page_format_width 				= $iWidth;
		$oType->page_format_height 				= $iHeight;
		$oType->first_page_border_top 			= $aBorder['top'];
		$oType->first_page_border_right 		= $aBorder['right'];
		$oType->first_page_border_bottom 		= $aBorder['bottom'];
		$oType->first_page_border_left 			= $aBorder['left'];
		$oType->additional_page_border_top 		= $aBorder['top'];
		$oType->additional_page_border_right 	= $aBorder['right'];
		$oType->additional_page_border_bottom 	= $aBorder['bottom'];
		$oType->additional_page_border_left 	= $aBorder['left'];
		$oType->element_date 					= 1;
		$oType->element_date_x 					= 150;
		$oType->element_date_y 					= $aBorder['top']+30;
		$oType->element_address 				= 1;
		$oType->element_address_x 				= $aBorder['left'];
		$oType->element_address_y 				= $aBorder['top'];
		$oType->element_subject 				= 1;
		$oType->element_subject_x 				= $aBorder['left'];
		$oType->element_subject_y 				= $aBorder['top']+30;
		$oType->element_text1 					= 1;
		$oType->element_text1_x 				= $aBorder['left'];
		$oType->element_text1_y 				= $aBorder['top']+50;
		$oType->element_inquirypositions 		= 1;
		$oType->save();
		
		$aTypeIds['receipt'] = $oType->id;

		$iOldSchoolSession = \Core\Handler\SessionHandler::getInstance()->get('sid');
		
		foreach($aSchools as $iSchoolId => $sName){
			
			// SESSION umschreiben damit alles korrekt erkannt wird!
            \Core\Handler\SessionHandler::getInstance()->set('sid', $iSchoolId);
				
			$oCertTPL = new Ext_Thebing_Certificate();
			
			// Template Typen Speichern
			$oType = new Ext_Thebing_Pdf_Template_Type(0);
			$oType->name 							= 'Certificate - '.$sName;
			$oType->active 							= 1;
			$oType->client_id 						= $user_data['client'];
			$oType->user_id 						= $user_data['id'];
			$oType->font_size 						= $oCertTPL->font_size;
			$oType->page_format 					= 1;
			$oType->page_format_width 				= $oCertTPL->din_X;
			$oType->page_format_height 				= $oCertTPL->din_Y;
			$oType->first_page_border_top 			= 0;
			$oType->first_page_border_right 		= 0;
			$oType->first_page_border_bottom 		= 0;
			$oType->first_page_border_left 			= 0;
			$oType->additional_page_border_top 		= 0;
			$oType->additional_page_border_right 	= 0;
			$oType->additional_page_border_bottom 	= 0;
			$oType->additional_page_border_left 	= 0;
			$oType->element_text1 					= 1;
			$oType->element_text1_x 				= $oCertTPL->text_pos_X;
			$oType->element_text1_y 				= $oCertTPL->text_pos_Y;
			$oType->save();
			
			$aTypeIds['certificate'] = $oType->id;
			
			$oCardTPL = new Ext_ac_studentcard();
			
			// Template Typen Speichern
			$oType = new Ext_Thebing_Pdf_Template_Type(0);
			$oType->name 							= 'Studentcards - '.$sName;
			$oType->active 							= 1;
			$oType->client_id 						= $user_data['client'];
			$oType->user_id 						= $user_data['id'];
			$oType->font_size 						= $oCardTPL->font_size;
			$oType->page_format 					= 0;
			$oType->page_format_width 				= $oCardTPL->din_X;
			$oType->page_format_height 				= $oCardTPL->din_Y;
			$oType->first_page_border_top 			= 0;
			$oType->first_page_border_right 		= 0;
			$oType->first_page_border_bottom 		= 0;
			$oType->first_page_border_left 			= 0;
			$oType->additional_page_border_top 		= 0;
			$oType->additional_page_border_right 	= 0;
			$oType->additional_page_border_bottom 	= 0;
			$oType->additional_page_border_left 	= 0;
			$oType->save();
			
			$oElement = new Ext_Thebing_Pdf_Template_Type_Element(0);
			$oElement->active 		= 1;
			$oElement->type_id 		= $oType->id;
			$oElement->client_id 	= 1;
			$oElement->user_id 		= 1;
			$oElement->name 		= 'document_number';
			$oElement->x 			= $oCardTPL->text_1_X;
			$oElement->y 			= $oCardTPL->text_1_Y;
			$oElement->font_size 	= $oCardTPL->font_size;
			$oElement->element_type = 'text';
			$oElement->page 		= 'all';
			$oElement->save();
			$aTypeIds['cards_1'] = $oElement->id;
			
			$oElement = new Ext_Thebing_Pdf_Template_Type_Element(0);
			$oElement->active 		= 1;
			$oElement->type_id 		= $oType->id;
			$oElement->client_id 	= 1;
			$oElement->user_id 		= 1;
			$oElement->name 		= 'Name';
			$oElement->x 			= $oCardTPL->text_2_X;
			$oElement->y 			= $oCardTPL->text_2_Y;
			$oElement->font_size 	= $oCardTPL->font_size;
			$oElement->element_type = 'text';
			$oElement->page 		= 'all';
			$oElement->save();
			$aTypeIds['cards_2'] = $oElement->id;
			
			$oElement = new Ext_Thebing_Pdf_Template_Type_Element(0);
			$oElement->active 		= 1;
			$oElement->type_id 		= $oType->id;
			$oElement->client_id 	= 1;
			$oElement->user_id 		= 1;
			$oElement->name 		= 'Nationality';
			$oElement->x 			= $oCardTPL->text_4_X;
			$oElement->y 			= $oCardTPL->text_4_Y;
			$oElement->font_size 	= $oCardTPL->font_size;
			$oElement->element_type = 'text';
			$oElement->page 		= 'all';
			$oElement->save();
			$aTypeIds['cards_3'] = $oElement->id;
			
			$oElement = new Ext_Thebing_Pdf_Template_Type_Element(0);
			$oElement->active 		= 1;
			$oElement->type_id 		= $oType->id;
			$oElement->client_id 	= 1;
			$oElement->user_id 		= 1;
			$oElement->name 		= 'Course from - until';
			$oElement->x 			= $oCardTPL->text_5_X;
			$oElement->y 			= $oCardTPL->text_5_Y;
			$oElement->font_size 	= $oCardTPL->font_size;
			$oElement->element_type = 'text';
			$oElement->page 		= 'all';
			$oElement->save();
			$aTypeIds['cards_4'] = $oElement->id;
			
			$oElement = new Ext_Thebing_Pdf_Template_Type_Element(0);
			$oElement->active 		= 1;
			$oElement->type_id 		= $oType->id;
			$oElement->client_id 	= 1;
			$oElement->user_id 		= 1;
			$oElement->name 		= 'Age';
			$oElement->x 			= $oCardTPL->text_6_X;
			$oElement->y 			= $oCardTPL->text_6_Y;
			$oElement->font_size 	= $oCardTPL->font_size;
			$oElement->element_type = 'text';
			$oElement->page 		= 'all';
			$oElement->save();
			$aTypeIds['cards_5'] = $oElement->id;
			
	
			$oElement = new Ext_Thebing_Pdf_Template_Type_Element(0);
			$oElement->active 		= 1;
			$oElement->type_id 		= $oType->id;
			$oElement->client_id 	= 1;
			$oElement->user_id 		= 1;
			$oElement->name 		= 'User Image';
			$oElement->x 			= $oCardTPL->pic_pos_X;
			$oElement->y 			= $oCardTPL->pic_pos_Y;
			$oElement->font_size 	= $oCardTPL->font_size;
			$oElement->element_type = 'img';
			$oElement->page 		= 'all';
			$oElement->img_width 	= $oCardTPL->pic_size_X;
			$oElement->img_height 	= $oCardTPL->pic_size_Y;
			$oElement->save();
			$aTypeIds['cards_6'] = $oElement->id;
			
			$aTypeIds['cards'] = $oType->id;

			$this->importPDFTemplates($iSchoolId, $aTypeIds);
		}

        \Core\Handler\SessionHandler::getInstance()->set('sid', $iOldSchoolSession);
		
		$this->saveClient();
		
		return false;
	}

	public function importPDFTemplates($iSchool, $aTypeIds){
		global $user_data;

		$aTemplatesInvoice 			= Ext_Thebing_Document_invoice_search::search();
		$aTemplatesInvoiceNet		= Ext_Thebing_Document_invoicenet_search::search();
		$aTemplatesLoa 				= Ext_Thebing_Document_loa_search::search();
		$aTemplatesStorno 			= Ext_Thebing_Document_cancellation_search::search();
		$aTemplatesCredit 			= Ext_Thebing_Document_credit_search::search();
		$aTemplatesStudentRecord 	= Ext_Thebing_Document_studentrecord_search::search();
		$aTemplatesAccommodation 	= Ext_Thebing_Document_accommodation_search::search();
		
		
		$aTemplatesReceipt = Ext_Thebing_Template_Payment_Search::getTemplate(0, 0, false);
		$aTemplatesReceiptNet = Ext_Thebing_Template_Payment_Search::getTemplate(0, 1, false);
		
		$oSchool = Ext_Thebing_School::getInstance($iSchool);
		
		$aLangs = $oSchool->getLanguageList();
		
		foreach($aTemplatesInvoice as $oTemplate){
			$oNewTemplate 					= new Ext_Thebing_Pdf_Template(0);	
			$oNewTemplate->name 			= $oSchool->ext_1.' - '.$oTemplate->title;
			$oNewTemplate->user_id 			= $user_data['id'];
			$oNewTemplate->client_id 		= $user_data['client'];
			$oNewTemplate->active 			= 1;
			$oNewTemplate->template_type_id = $aTypeIds['invoice'];
			$oNewTemplate->type				= 'document_invoice_customer';
			$oNewTemplate->save();
			$oNewTemplate->saveSchools(array($iSchool));
			$sLang = $oTemplate->lang;
			$oNewTemplate->saveLanguages(array($sLang));
			//foreach($aLangs as $sLang){
				$oNewTemplate->saveStaticElementValue($sLang, 'address', 	$oTemplate->txt_enclosures);
				$oNewTemplate->saveStaticElementValue($sLang, 'date', 		'');
				$oNewTemplate->saveStaticElementValue($sLang, 'subject', 	$oTemplate->txt_subject);
				$oNewTemplate->saveStaticElementValue($sLang, 'text1', 		$oTemplate->txt_intro);
				$oNewTemplate->saveStaticElementValue($sLang, 'text2', 		$oTemplate->txt_outro);
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'signatur_text', $oTemplate->txt_signature );
				
				$sPath = '';
				$sSigTemp = $oTemplate->signature;
				if(!empty($sSigTemp)){
					$sPath = '/signatur/'.$oTemplate->signature;
				}
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'signatur_img', $sPath);
				
				$sPath = '';
				$sPdfTemp = $oTemplate->txt_pdf;
				if(!empty($sPdfTemp)){
					$sPath = '/pdf/'.$oTemplate->txt_pdf;
				} else {
					$aFiles = $oSchool->getSchoolFiles(0, $sLang, true);
					$aFile = reset($aFiles);
					$sPath = $aFile['path'];
				}
				
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'first_page_pdf_template',  $sPath);
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'additional_page_pdf_template', $sPath);
			//}
		}
		
		foreach($aTemplatesInvoiceNet as $oTemplate){
			$oNewTemplate 					= new Ext_Thebing_Pdf_Template(0);	
			$oNewTemplate->name 			= $oSchool->ext_1.' - '.$oTemplate->title;
			$oNewTemplate->user_id 			= $user_data['id'];
			$oNewTemplate->client_id 		= $user_data['client'];
			$oNewTemplate->active 			= 1;
			$oNewTemplate->template_type_id = $aTypeIds['invoice'];
			$oNewTemplate->type				= 'document_invoice_agency';
			$oNewTemplate->save();
			$oNewTemplate->saveSchools(array($iSchool));
			$sLang = $oTemplate->lang;
			$oNewTemplate->saveLanguages(array($sLang));
			//foreach($aLangs as $sLang){
				$oNewTemplate->saveStaticElementValue($sLang, 'address', 	$oTemplate->txt_enclosures);
				$oNewTemplate->saveStaticElementValue($sLang, 'date', 		'');
				$oNewTemplate->saveStaticElementValue($sLang, 'subject', 	$oTemplate->txt_subject);
				$oNewTemplate->saveStaticElementValue($sLang, 'text1', 		$oTemplate->txt_intro);
				$oNewTemplate->saveStaticElementValue($sLang, 'text2', 		$oTemplate->txt_outro);
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'signatur_text', $oTemplate->txt_signature );
				$sPath = '';
				$sSigTemp = $oTemplate->signature;
				if(!empty($sSigTemp)){
					$sPath = '/signatur/'.$oTemplate->signature;
				}
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'signatur_img', $sPath);
				$sPath = '';
				$sPdfTemp = $oTemplate->txt_pdf;
				if(!empty($sPdfTemp)){
					$sPath = '/pdf/'.$oTemplate->txt_pdf;
				} else {
					$aFiles = $oSchool->getSchoolFiles(0, $sLang, true);
					$aFile = reset($aFiles);
					$sPath = $aFile['path'];
				}
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'first_page_pdf_template',  $sPath);
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'additional_page_pdf_template', $sPath);
			//}
		}
		
		foreach($aTemplatesStorno as $oTemplate){
			$oNewTemplate 					= new Ext_Thebing_Pdf_Template(0);	
			$oNewTemplate->name 			= $oSchool->ext_1.' - '.$oTemplate->title;
			$oNewTemplate->user_id 			= $user_data['id'];
			$oNewTemplate->client_id 		= $user_data['client'];
			$oNewTemplate->active 			= 1;
			$oNewTemplate->template_type_id = $aTypeIds['invoice'];
			$oNewTemplate->type				= 'document_invoice_storno';
			$oNewTemplate->save();
			$oNewTemplate->saveSchools(array($iSchool));
			$sLang = $oTemplate->lang;
			$oNewTemplate->saveLanguages(array($sLang));
			//foreach($aLangs as $sLang){
				$oNewTemplate->saveStaticElementValue($sLang, 'address', 	$oTemplate->txt_address);
				$oNewTemplate->saveStaticElementValue($sLang, 'date', 		'');
				$oNewTemplate->saveStaticElementValue($sLang, 'subject', 	$oTemplate->txt_subject);
				$oNewTemplate->saveStaticElementValue($sLang, 'text1', 		$oTemplate->txt_intro);
				$oNewTemplate->saveStaticElementValue($sLang, 'text2', 		$oTemplate->txt_outro);
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'signatur_text', $oTemplate->txt_signature );
				$sSigTemp = $oTemplate->signature;
				if(!empty($sSigTemp)){
					$sPath = '/signatur/'.$oTemplate->signature;
				}
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'signatur_img', $sPath);
				$sPath = '';
				$sPdfTemp = $oTemplate->txt_pdf;
				if(!empty($sPdfTemp)){
					$sPath = '/pdf/'.$oTemplate->txt_pdf;
				} else {
					$aFiles = $oSchool->getSchoolFiles(0, $sLang, true);
					$aFile = reset($aFiles);
					$sPath = $aFile['path'];
				}
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'first_page_pdf_template',  $sPath);
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'additional_page_pdf_template', $sPath);
			//}
		}
		
		foreach($aTemplatesCredit as $oTemplate){
			$oNewTemplate 					= new Ext_Thebing_Pdf_Template(0);	
			$oNewTemplate->name 			= $oSchool->ext_1.' - '.$oTemplate->title;
			$oNewTemplate->user_id 			= $user_data['id'];
			$oNewTemplate->client_id 		= $user_data['client'];
			$oNewTemplate->active 			= 1;
			$oNewTemplate->template_type_id = $aTypeIds['invoice'];
			$oNewTemplate->type				= 'document_invoice_credit';
			$oNewTemplate->save();
			$oNewTemplate->saveSchools(array($iSchool));
			$sLang = $oTemplate->lang;
			$oNewTemplate->saveLanguages(array($sLang));
			//foreach($aLangs as $sLang){
				$oNewTemplate->saveStaticElementValue($sLang, 'address', 	$oTemplate->txt_address);
				$oNewTemplate->saveStaticElementValue($sLang, 'date', 		'');
				$oNewTemplate->saveStaticElementValue($sLang, 'subject', 	$oTemplate->txt_subject);
				$oNewTemplate->saveStaticElementValue($sLang, 'text1', 		$oTemplate->txt_intro);
				$oNewTemplate->saveStaticElementValue($sLang, 'text2', 		$oTemplate->txt_outro);
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'signatur_text', $oTemplate->txt_signature );
				$sPath = '';
				$sSigTemp = $oTemplate->signature;
				if(!empty($sSigTemp)){
					$sPath = '/signatur/'.$oTemplate->signature;
				}
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'signatur_img', $sPath);
				$sPath = '';
				$sPdfTemp = $oTemplate->txt_pdf;
				if(!empty($sPdfTemp)){
					$sPath = '/pdf/'.$oTemplate->txt_pdf;
				} else {
					$aFiles = $oSchool->getSchoolFiles(0, $sLang, true);
					$aFile = reset($aFiles);
					$sPath = $aFile['path'];
				}
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'first_page_pdf_template',  $sPath);
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'additional_page_pdf_template', $sPath);
			//}
		}
		
		foreach($aTemplatesLoa as $oTemplate){
			$oNewTemplate 					= new Ext_Thebing_Pdf_Template(0);	
			$oNewTemplate->name 			= $oSchool->ext_1.' - '.$oTemplate->title;
			$oNewTemplate->user_id 			= $user_data['id'];
			$oNewTemplate->client_id 		= $user_data['client'];
			$oNewTemplate->active 			= 1;
			$oNewTemplate->template_type_id = $aTypeIds['loa'];
			$oNewTemplate->type				= 'document_loa';
			$oNewTemplate->save();
			$oNewTemplate->saveSchools(array($iSchool));
			$sLang = $oTemplate->lang;
			$oNewTemplate->saveLanguages(array($sLang));
			//foreach($aLangs as $sLang){
				$oNewTemplate->saveStaticElementValue($sLang, 'address', 	$oTemplate->txt_enclosures);
				$oNewTemplate->saveStaticElementValue($sLang, 'date', 		'');
				$oNewTemplate->saveStaticElementValue($sLang, 'subject', 	$oTemplate->txt_subject);
				$oNewTemplate->saveStaticElementValue($sLang, 'text1', 		$oTemplate->txt_intro);
				$oNewTemplate->saveStaticElementValue($sLang, 'text2', 		$oTemplate->txt_outro);
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'signatur_text', $oTemplate->txt_signature );
				$sPath = '';
				$sSigTemp = $oTemplate->signature;
				if(!empty($sSigTemp)){
					$sPath = '/signatur/'.$oTemplate->signature;
				}
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'signatur_img', $sPath);
				$sPath = '';
				$sPdfTemp = $oTemplate->txt_pdf;
				if(!empty($sPdfTemp)){
					$sPath = '/pdf/'.$oTemplate->txt_pdf;
				} else {
					$aFiles = $oSchool->getSchoolFiles(0, $sLang, true);
					$aFile = reset($aFiles);
					$sPath = $aFile['path'];
				}
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'first_page_pdf_template',  $sPath);
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'additional_page_pdf_template', $sPath);
			//}
		}
		
		foreach($aTemplatesStudentRecord as $oTemplate){
			$oNewTemplate 					= new Ext_Thebing_Pdf_Template(0);	
			$oNewTemplate->name 			= $oSchool->ext_1.' - '.$oTemplate->title;
			$oNewTemplate->user_id 			= $user_data['id'];
			$oNewTemplate->client_id 		= $user_data['client'];
			$oNewTemplate->active 			= 1;
			$oNewTemplate->template_type_id = $aTypeIds['student'];
			$oNewTemplate->type				= 'document_studentrecord_additional_pdf';
			$oNewTemplate->save();
			$oNewTemplate->saveSchools(array($iSchool));
			$sLang = $oTemplate->lang;
			$oNewTemplate->saveLanguages(array($sLang));
			//foreach($aLangs as $sLang){
				$oNewTemplate->saveStaticElementValue($sLang, 'address', 	$oTemplate->txt_enclosures);
				$oNewTemplate->saveStaticElementValue($sLang, 'date', 		'');
				$oNewTemplate->saveStaticElementValue($sLang, 'subject', 	$oTemplate->txt_subject);
				$oNewTemplate->saveStaticElementValue($sLang, 'text1', 		$oTemplate->txt_intro);
				$oNewTemplate->saveStaticElementValue($sLang, 'text2', 		$oTemplate->txt_outro);
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'signatur_text', $oTemplate->txt_signature );
				$sPath = '';
				$sSigTemp = $oTemplate->signature;
				if(!empty($sSigTemp)){
					$sPath = '/signatur/'.$oTemplate->signature;
				}
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'signatur_img', $sPath);
				$sPath = '';
				$sPdfTemp = $oTemplate->txt_pdf;
				if(!empty($sPdfTemp)){
					$sPath = '/pdf/'.$oTemplate->txt_pdf;
				} else {
					$aFiles = $oSchool->getSchoolFiles(0, $sLang, true);
					$aFile = reset($aFiles);
					$sPath = $aFile['path'];
				}
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'first_page_pdf_template',  $sPath);
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'additional_page_pdf_template', $sPath);
			//}
		}

		foreach($aTemplatesAccommodation as $oTemplate){
			$oNewTemplate 					= new Ext_Thebing_Pdf_Template(0);	
			$oNewTemplate->name 			= $oSchool->ext_1.' - '.$oTemplate->title;
			$oNewTemplate->user_id 			= $user_data['id'];
			$oNewTemplate->client_id 		= $user_data['client'];
			$oNewTemplate->active 			= 1;
			$oNewTemplate->template_type_id = $aTypeIds['accommodation'];
			$oNewTemplate->type				= 'document_accommodation_communication';
			$oNewTemplate->save();
			$oNewTemplate->saveSchools(array($iSchool));
			$sLang = $oTemplate->lang;
			$oNewTemplate->saveLanguages(array($sLang));
			//foreach($aLangs as $sLang){
				$oNewTemplate->saveStaticElementValue($sLang, 'address', 	$oTemplate->txt_address);
				$oNewTemplate->saveStaticElementValue($sLang, 'date', 		'');
				$oNewTemplate->saveStaticElementValue($sLang, 'subject', 	$oTemplate->txt_subject);
				$oNewTemplate->saveStaticElementValue($sLang, 'text1', 		$oTemplate->txt_intro);
				$oNewTemplate->saveStaticElementValue($sLang, 'text2', 		$oTemplate->txt_outro);
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'signatur_text', $oTemplate->txt_signature );
				$sPath = '';
				$sSigTemp = $oTemplate->signature;
				if(!empty($sSigTemp)){
					$sPath = '/signatur/'.$oTemplate->signature;
				}
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'signatur_img', $sPath);
				$sPath = '';
				$sPdfTemp = $oTemplate->txt_pdf;
				if(!empty($sPdfTemp)){
					$sPath = '/pdf/'.$oTemplate->txt_pdf;
				} else {
					$aFiles = $oSchool->getSchoolFiles(0, $sLang, true);
					$aFile = reset($aFiles);
					$sPath = $aFile['path'];
				}
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'first_page_pdf_template',  $sPath);
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'additional_page_pdf_template', $sPath);
			//}
		}
		
		//foreach($aTemplatesReceipt as $aTemplate){
			$aTemplate = $aTemplatesReceipt;
			$oNewTemplate 					= new Ext_Thebing_Pdf_Template(0);	
			$oNewTemplate->name 			= $oSchool->ext_1.' - Receipt';
			$oNewTemplate->user_id 			= $user_data['id'];
			$oNewTemplate->client_id 		= $user_data['client'];
			$oNewTemplate->active 			= 1;
			$oNewTemplate->template_type_id = $aTypeIds['receipt'];
			$oNewTemplate->type				= 'document_invoice_customer_receipt';
			$oNewTemplate->save();
			$oNewTemplate->saveSchools(array($iSchool));
			$oNewTemplate->saveLanguages($aLangs);
			foreach($aLangs as $sLang){
				$oNewTemplate->saveStaticElementValue($sLang, 'address', 	$aTemplate->address);
				$oNewTemplate->saveStaticElementValue($sLang, 'date', 		'');
				$oNewTemplate->saveStaticElementValue($sLang, 'subject', 	$aTemplate->subject);
				$oNewTemplate->saveStaticElementValue($sLang, 'text1', 		$aTemplate->intro);
				$oNewTemplate->saveStaticElementValue($sLang, 'text2', 		$aTemplate->outro);
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'signatur_text', $aTemplate->signature);
				$sPath = '';
				$sSigTemp = $aTemplate->signature;
				if(!empty($sSigTemp)){
					$sPath = '/signatur/'.$aTemplate->signature_img;
				}
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'signatur_img', $sPath);
				$sPath = '';
				$sPdfTemp = $aTemplate->pdf;
				if(!empty($sPdfTemp)){
					$sPath = '/pdf/'.$aTemplate->pdf;
				} else {
					$aFiles = $oSchool->getSchoolFiles(0, $sLang, true);
					$aFile = reset($aFiles);
					$sPath = $aFile['path'];
				}
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'first_page_pdf_template',  $sPath);
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'additional_page_pdf_template', $sPath);
			}
		//}
		
		//foreach($aTemplatesReceiptNet as $aTemplate){
			$aTemplate = $aTemplatesReceiptNet;
			$oNewTemplate 					= new Ext_Thebing_Pdf_Template(0);	
			$oNewTemplate->name 			= $oSchool->ext_1.' - Receipt';
			$oNewTemplate->user_id 			= $user_data['id'];
			$oNewTemplate->client_id 		= $user_data['client'];
			$oNewTemplate->active 			= 1;
			$oNewTemplate->template_type_id = $aTypeIds['receipt'];
			$oNewTemplate->type				= 'document_invoice_agency_receipt';
			$oNewTemplate->save();
			$oNewTemplate->saveSchools(array($iSchool));
			$oNewTemplate->saveLanguages($aLangs);
			foreach($aLangs as $sLang){
				$oNewTemplate->saveStaticElementValue($sLang, 'address', 	$aTemplate->address);
				$oNewTemplate->saveStaticElementValue($sLang, 'date', 		'');
				$oNewTemplate->saveStaticElementValue($sLang, 'subject', 	$aTemplate->subject);
				$oNewTemplate->saveStaticElementValue($sLang, 'text1', 		$aTemplate->intro);
				$oNewTemplate->saveStaticElementValue($sLang, 'text2', 		$aTemplate->outro);
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'signatur_text', $aTemplate->signature);
				$sPath = '';
				$sSigTemp = $aTemplate->signature;
				if(!empty($sSigTemp)){
					$sPath = '/signatur/'.$aTemplate->signature_img;
				}
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'signatur_img', $sPath);
				$sPath = '';
				$sPdfTemp = $aTemplate->pdf;
				if(!empty($sPdfTemp)){
					$sPath = '/pdf/'.$aTemplate->pdf;
				} else {
					$aFiles = $oSchool->getSchoolFiles(0, $sLang, true);
					$aFile = reset($aFiles);
					$sPath = $aFile['path'];
				}
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'first_page_pdf_template',  $sPath);
				$oNewTemplate->saveOptionValue($sLang, $iSchool, 'additional_page_pdf_template', $sPath);
			}
		//}
		
		//Zertifikate
		$oCertTPL = new Ext_Thebing_Certificate();
		
		$oNewTemplate 					= new Ext_Thebing_Pdf_Template(0);	
		$oNewTemplate->name 			= $oSchool->ext_1.' - Certificates';
		$oNewTemplate->user_id 			= $user_data['id'];
		$oNewTemplate->client_id 		= $user_data['client'];
		$oNewTemplate->active 			= 1;
		$oNewTemplate->template_type_id = $aTypeIds['certificate'];
		$oNewTemplate->type				= 'document_certificates';
		$oNewTemplate->save();
		$oNewTemplate->saveSchools(array($iSchool));
		$oNewTemplate->saveLanguages($aLangs);
		foreach($aLangs as $sLang){
			$oNewTemplate->saveStaticElementValue($sLang, 'text1', 		$oCertTPL->text);
			$oNewTemplate->saveOptionValue($sLang, $iSchool, 'signatur_text', '');
			$oNewTemplate->saveOptionValue($sLang, $iSchool, 'signatur_img', '');
			$sPdf = '/certificates/template_'.$iSchool.'.pdf';
			$sPdf2 = '/pdf/certificates_template_'.$iSchool.'.pdf';
			if(!is_file(\Util::getDocumentRoot().'media/secure'.$sPdf)){
				$sPdf = '';
			} else {
				$sSchoolPath = $oSchool->getSchoolFileDir(true, true);
				@chmod(\Util::getDocumentRoot().'media/secure'.$sPdf, 0777);
				$aTemp = explode('/', $sSchoolPath.$sPdf2);
				array_pop($aTemp);
				Ext_Thebing_Util::checkDir(implode('/', $aTemp));
				@copy(\Util::getDocumentRoot().'media/secure'.$sPdf, $sSchoolPath.$sPdf2);
				@chmod($sSchoolPath.$sPdf2, 0777);
			}
			
			$oNewTemplate->saveOptionValue($sLang, $iSchool, 'first_page_pdf_template', $sPdf2);
			$oNewTemplate->saveOptionValue($sLang, $iSchool, 'additional_page_pdf_template', $sPdf2);
		}		
		
		//Studentcards
		$oNewTemplate 					= new Ext_Thebing_Pdf_Template(0);	
		$oNewTemplate->name 			= $oSchool->ext_1.' - Studentcard';
		$oNewTemplate->user_id 			= $user_data['id'];
		$oNewTemplate->client_id 		= $user_data['client'];
		$oNewTemplate->active 			= 1;
		$oNewTemplate->template_type_id = $aTypeIds['cards'];
		$oNewTemplate->type				= 'document_student_cards';
		$oNewTemplate->save();
		$oNewTemplate->saveSchools(array($iSchool));
		$oNewTemplate->saveLanguages($aLangs);
		foreach($aLangs as $sLang){
			$oNewTemplate->saveStaticElementValue($sLang, 'address', 	'');
			$oNewTemplate->saveStaticElementValue($sLang, 'date', 		'');
			$oNewTemplate->saveStaticElementValue($sLang, 'subject', 	'');
			$oNewTemplate->saveStaticElementValue($sLang, 'text1', 		'');
			$oNewTemplate->saveStaticElementValue($sLang, 'text2', 		'');
			$oNewTemplate->saveOptionValue($sLang, $iSchool, 'signatur_text', '');
			$oNewTemplate->saveOptionValue($sLang, $iSchool, 'signatur_img', '');
			$sPdf = '/studentcards/template_'.$iSchool.'.pdf';
			$sPdf2 = '/pdf/studentcards_template_'.$iSchool.'.pdf';
			if(!is_file(\Util::getDocumentRoot().'media/secure'.$sPdf)){
				$sPdf = '';
			} else {
				$sSchoolPath = $oSchool->getSchoolFileDir(true, true);
				@chmod(\Util::getDocumentRoot().'media/secure'.$sPdf, 0777);
				$aTemp = explode('/', $sSchoolPath.$sPdf2);
				array_pop($aTemp);
				Ext_Thebing_Util::checkDir(implode('/', $aTemp));
				@copy(\Util::getDocumentRoot().'media/secure'.$sPdf, $sSchoolPath.$sPdf2);
				@chmod($sSchoolPath.$sPdf2, 0777);
			}
			
			$oNewTemplate->saveOptionValue($sLang, $iSchool, 'first_page_pdf_template', $sPdf2);
			$oNewTemplate->saveOptionValue($sLang, $iSchool, 'additional_page_pdf_template', $sPdf2);
			
			$oElement = new Ext_Thebing_Pdf_Template_Type_Element($aTypeIds['cards_1']);
			$oElement->saveValue($sLang, $oNewTemplate->id, '{document_number}');
			
			$oElement = new Ext_Thebing_Pdf_Template_Type_Element($aTypeIds['cards_2']);
			$oElement->saveValue($sLang, $oNewTemplate->id, '{surname} , {firstname}');
			
			$oElement = new Ext_Thebing_Pdf_Template_Type_Element($aTypeIds['cards_3']);
			$oElement->saveValue($sLang, $oNewTemplate->id, '{date_course_start} - {date_course_end}');
			
			$oElement = new Ext_Thebing_Pdf_Template_Type_Element($aTypeIds['cards_4']);
			$oElement->saveValue($sLang, $oNewTemplate->id, '{nationality}');
			
			$oElement = new Ext_Thebing_Pdf_Template_Type_Element($aTypeIds['cards_5']);
			$oElement->saveValue($sLang, $oNewTemplate->id, '{age}');
			
			$oElement = new Ext_Thebing_Pdf_Template_Type_Element($aTypeIds['cards_6']);
			$oElement->saveValue($sLang, $oNewTemplate->id, 'default_customer_picture');
		}		
		
	}
	
	protected function prepareExecuteCheck(){
		global $_VARS;
		// If no Check found, dont try to execute
		// If you land on this Method and it dosn´t exist an Check ist must give an Error in your Script!
		if(empty($this->_oUsedClass->_aCheck)){
			$this->_oUsedClass->_aFormErrors[] = L10N::t('No Check Found!');
			return false;
		}

		// if after sending the Form the Check is has Changed trow an Error
		// ( e.g 2 Users make the check to the same time an it give an second Check )
		if($_VARS['check_id'] != $this->_oUsedClass->_aCheck['id']){
			$this->_oUsedClass->_aFormErrors[] = L10N::t('You send Data for an other Check, please go back');
			return false;
		}
		
		$this->_oUsedClass->executeCheck();
		
		return true;
	}
}
