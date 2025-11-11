<?php

/**
 * @TODO Diese Klasse ist GUI-Data, Format-Klasse und Preisberechnung in einem!
 */
class Ext_Thebing_Insurances_Gui2_Customer extends Ext_Thebing_Document_Gui2 {

    public function executeGuiCreatedHook()
    {
        $this->_oGui->name = 'ts_insurances_customer';
        $this->_oGui->set = '';

        $oInquiryAdditionalDocuments = new Ext_Thebing_Inquiry_Document_Additional();
        $oInquiryAdditionalDocuments->use_template_type	= 'document_insurances';
        $oRowIconActive = new Ext_Thebing_Gui2_Icon_Inbox('inquiry_id');
        $oInquiryAdditionalDocuments->icon_status_active = $oRowIconActive;

        $this->_oGui->addAdditionalDocumentsOptions($oInquiryAdditionalDocuments);

        $this->_oGui->addJs('insurances/js/customers.js');

    }

	/**
	 * @TODO Die Variable geht inkl. Preisberechnung in die GUI2-Session (Festplatte)
	 *
	 * @var array
	 */
	static public $aCache = array();

	public $aErrors = array();

	protected $aCalculationDescription = [];


	/**
	 * @param string $sL10NDescription
	 * @return array
	 */
	public function getTranslations($sL10NDescription) { 

		$aData = parent::getTranslations($sL10NDescription);
		
		$aData['provider_confirm'] = $this->t('Vom Anbieter bestätigt');
		$aData['provider_both_confirm'] = $this->t('Vom Anbieter bestätigt').'/'.$this->t('Bestätigung entfernen');
		$aData['provider_de_confirm'] = $this->t('Bestätigung entfernen');

		return $aData;
	}

	/**
	 * See parent
	 */
	protected function getDialogHTML(&$sIconAction, &$oDialogData, $aSelectedIds = array(), $sAdditional=false) {

		// get dialog object
		if($sIconAction == 'editPDF') {

			$aSelectedIds	= (array)$aSelectedIds;
			$iSelectedId	= (int)reset($aSelectedIds);
			#$iLinkID	= reset($aSelectedIds);
			$iLinkID = $this->_oGui->decodeId($iSelectedId,"id");

			$oLink		= new Ext_TS_Inquiry_Journey_Insurance($iLinkID);
			$oInquiry	= Ext_TS_Inquiry::getInstance($oLink->inquiry_id);
			$oCustomer	= $oInquiry->getCustomer();
			$aTemplates	= $this->getTemplates($oLink->inquiry_id, $iLinkID, $oCustomer->getLanguage());

			$iCurrency	= $oInquiry->getCurrency();
			$oSchool	= $oInquiry->getSchool();
			$iSchool	= $oSchool->id;

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

			if($oLink->document_id > 0) {
				$oDocument = new Ext_Thebing_Inquiry_Document($oLink->document_id);

				$oVersion = $oDocument->getLastVersion();
				if($oVersion) {
					// Versions daten holen
					$iTemplateId	= $oVersion->template_id;
					$sDate			= Ext_Thebing_Format::LocalDate($oVersion->date, $oSchool->id);
					$sAddress		= $oVersion->txt_address;
					$sSubject		= $oVersion->txt_subject;
					$sIntro			= $oVersion->txt_intro;
					$sOutro			= $oVersion->txt_outro;
					$sSignaturText	= $oVersion->txt_signature;
					$sSignaturTmg	= $oVersion->signature;
					$sComment		= $oVersion->comment;
				}

			} else {

				$iTemplateId	= '';
				$sDate			= '';
				$sSubject		= '';
				$sAddress		= '';
				$sIntro			= '';
				$sOutro			= '';
				$sSignaturText	= '';
				$sSignaturTmg	= '';
				$sComment		= '';

			}

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

			$oDialogPDF = $this->_oGui->createDialog();

			$oDocumentTab	= $oDialogPDF->createTab(L10N::t('Dokumente', $this->_oGui->gui_description));
			$oHistoryTab	= $oDialogPDF->createTab(L10N::t('Historie', $this->_oGui->gui_description));

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

			$aDocumentsArray = Ext_Thebing_Inquiry_Document_Search::search($oLink->inquiry_id, 'insurance', true);
			$aDocuments = array();

			foreach($aDocumentsArray as $aData)
			{
				$aDocuments[] = new Ext_Thebing_Inquiry_Document($aData['id']);
			}

			$sHistoryHTML = Ext_Thebing_Inquiry_Gui2_Html::getHistoryHTML($aDocuments, $this->_oGui->gui_description, $iSchool, $iCurrency, 'insurance');

			$oHistoryTab->setElement($sHistoryHTML);

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

			$sId = 'saveid[template_id]';
			$sName = 'save[template_id]';
			$oTemplateRow = $oDialogPDF->createRow(L10N::t('Vorlage', $this->_oGui->gui_description), 'select', array('id' => $sId, 'name' => $sName, 'style' => 'width:500px;', 'required' => 1, 'select_options' => $aTemplates['tpls'], 'default_value' => $iTemplateId));

			$oDocumentTab->setElement($oTemplateRow);

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

			$oDiv = new Ext_Gui2_Html_Div();

			$sId			= 'saveid[date]';
			$sName			= 'save[date]';
			$oDateRow		= $oDialogPDF->createRow(L10N::t('Datum', $this->_oGui->gui_description), 'calendar', array('value' => $sDate, 'id' => $sId, 'name' => $sName, 'row_style' => 'display:none;', 'style' => 'width:500px;'));
			$oDiv->setElement($oDateRow);

			$sId			= 'saveid[address]';
			$sName			= 'save[address]';
			$oAddressRow	= $oDialogPDF->createRow(L10N::t('Adresse', $this->_oGui->gui_description), 'textarea', array('default_value' => $sAddress,'id' => $sId, 'name' => $sName, 'row_style' => 'display:none;', 'style' => 'width:500px;height:100px;'));
			$oDiv->setElement($oAddressRow);

			$sId			= 'saveid[subject]';
			$sName			= 'save[subject]';
			$oSubjectRow	= $oDialogPDF->createRow(L10N::t('Betreff', $this->_oGui->gui_description), 'input', array('default_value' => $sSubject,'id' => $sId, 'name' => $sName, 'row_style' => 'display:none;', 'style' => 'width:500px;'));
			$oDiv->setElement($oSubjectRow);

			$sId			= 'saveid[intro]';
			$sName			= 'save[intro]';
			$oIntroRow		= $oDialogPDF->createRow(L10N::t('Text oben', $this->_oGui->gui_description), 'html', array('default_value' => $sIntro,'id' => $sId, 'name' => $sName, 'row_style' => 'display:none;', 'style' => 'width:500px;height:150px;'));
			$oDiv->setElement($oIntroRow);

			$sId			= 'saveid[outro]';
			$sName			= 'save[outro]';
			$oOutroRow		= $oDialogPDF->createRow(L10N::t('Text unten', $this->_oGui->gui_description), 'html', array('default_value' => $sOutro,'id' => $sId, 'name' => $sName, 'row_style' => 'display:none;', 'style' => 'width:500px;height:150px;'));
			$oDiv->setElement($oOutroRow);

			$sId			= 'saveid[signature_img]';
			$sName			= 'save[signature_img]';
			$oSignaturImg	= $oDialogPDF->createRow(L10N::t('Signature Bild', $this->_oGui->gui_description), 'select', array('default_value' => $sSignaturTmg,'id' => $sId, 'name' => $sName, 'style' => 'width:500px;'));
			$oDiv->setElement($oSignaturImg);

			$sId			= 'saveid[signature_txt]';
			$sName			= 'save[signature_txt]';
			$oSignaturText	= $oDialogPDF->createRow(L10N::t('Signature', $this->_oGui->gui_description), 'textarea', array('default_value' => $sSignaturText,'id' => $sId, 'name' => $sName, 'style' => 'width:500px;'));
			$oDiv->setElement($oSignaturText);

			$sId			= 'saveid[comment]';
			$sName			= 'save[comment]';
			$oAddressRow	= $oDialogPDF->createRow(L10N::t('Kommentar', $this->_oGui->gui_description), 'textarea', array('default_value' => $sComment,'id' => $sId, 'name' => $sName, 'style' => 'width:500px;height:100px;'));
			$oDiv->setElement($oAddressRow);

			$oDocumentTab->setElement($oDiv);

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

			$oDialogPDF->setElement($oDocumentTab);
			$oDialogPDF->setElement($oHistoryTab);

			$oDialogPDF->width = 950;

			$aData = $oDialogPDF->generateAjaxData($aSelectedIds, $this->_oGui->hash);
			$aData['bSaveButton'] = 1;
			$aData['template_field_data'] = $aTemplates['data'];

		} else {

			$aData = parent::getDialogHTML($sIconAction, $oDialogData, $aSelectedIds, $sAdditional);

		}

		return $aData;

	}

	public function getTemplates($iInquiryID, $iLinkID, $sLang = 'en') {
		$oInquiry = new Ext_TS_Inquiry($iInquiryID);
		
		$aTemplates_ = Ext_Thebing_Pdf_Template_Search::s('document_insurances', $sLang);

		$aTemplates = $aTemplatesFieldData = array();

		$aTemplates[0] = '&nbsp;';
		
		$oContact = $oInquiry->getCustomer();
		
		$oReplace = new Ext_Thebing_Inquiry_Placeholder($iInquiryID, $oContact->id);

        $iSessionSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');

		foreach($aTemplates_ as $oTemplate)
		{
			$aTemplates[$oTemplate->id] =  $oTemplate->name;
			$oTemplateType = new Ext_Thebing_Pdf_Template_Type($oTemplate->template_type_id);

			$aTemp = array();
			$aTemp['element_address']				= $oTemplateType->element_address;
			$aTemp['element_date']					= $oTemplateType->element_date;
			$aTemp['element_inquirypositions']		= $oTemplateType->element_inquirypositions;
			$aTemp['element_subject']				= $oTemplateType->element_subject;
			$aTemp['element_text1']					= $oTemplateType->element_text1;
			$aTemp['element_text2']					= $oTemplateType->element_text2;

			$aTemp['element_address_html']			= $oReplace->replace($oTemplate->getStaticElementValue($sLang, 'address'), 0, $iLinkID);
			$aTemp['element_date_html']				= $oReplace->replace($oTemplate->getStaticElementValue($sLang, 'date'), 0, $iLinkID);
			$aTemp['element_subject_html']			= $oReplace->replace($oTemplate->getStaticElementValue($sLang, 'subject'), 0, $iLinkID);
			$aTemp['element_text1_html']			= $oReplace->replace($oTemplate->getStaticElementValue($sLang, 'text1'), 0, $iLinkID);
			$aTemp['element_text2_html']			= $oReplace->replace($oTemplate->getStaticElementValue($sLang, 'text2'), 0, $iLinkID);

			$aTemp['signatur_img_html']				= $oTemplate->getOptionValue($sLang, $iSessionSchoolId, 'signatur_img');
			$aTemp['signatur_text_html']			= $oTemplate->getOptionValue($sLang, $iSessionSchoolId, 'signatur_text');

			$aTemplatesFieldData[$oTemplate->id]	= $aTemp;
		}

		$aReturn = array(
			'data'	=> $aTemplatesFieldData,
			'tpls'	=> $aTemplates
		);

		return $aReturn;
	}

	public function format($aResult, $sLang = '') {

		if(
			count($aResult) < 1 ||
			!isset($aResult[0])
		) {
			return $aResult;
		}

        if(isset($aResult[0]['school_id'])) {
            $oSchool = Ext_Thebing_School::getInstance($aResult[0]['school_id']);
        } else {
            $oSchool = Ext_Thebing_School::getSchoolFromSession();
        }

		if(empty($sLang)) {
			$sLang = $oSchool->fetchInterfaceLanguage();
		}

		foreach((array)$aResult as $iKey => $aValue) {

			// Prüfüen ob die Versicherung schon auf einer Rechnung drauf ist und die 
			switch($aValue['payment']) {

				case 1: // Einmalig
					$oDateFrom = Ext_TC_Util::getDateTimeObject($aValue['from']);
					$oDateTill = Ext_TC_Util::getDateTimeObject($aValue['until']);

					$oDiff = $oDateFrom->diff($oDateTill, true);
					$iDays = $oDiff->days + 1;

					$aValue['temp_count'] = $iDays;

					$aResult[$iKey]['price'] = $this->_calculatePrice($aValue, $oSchool);
					$aResult[$iKey]['payment'] = \Ext_TC_Placeholder_Abstract::translateFrontend('Aufenthalt', $sLang);

					break;

				case 2: // Pro Tag
					$oDateFrom = Ext_TC_Util::getDateTimeObject($aValue['from']);
					$oDateTill = Ext_TC_Util::getDateTimeObject($aValue['until']);

					$oDiff = $oDateFrom->diff($oDateTill, true);
					$iDays = $oDiff->days + 1;

					$aValue['temp_count'] = $iDays;

					$sDays = \Ext_TC_Placeholder_Abstract::translateFrontend('Tag', $sLang);
					if($iDays > 1){
						$sDays = \Ext_TC_Placeholder_Abstract::translateFrontend('Tage', $sLang);
					}

					$aResult[$iKey]['price'] = $this->_calculatePrice($aValue, $oSchool);
					$aResult[$iKey]['payment'] = $iDays.' '.$sDays;

					break;

				case 3: // Pro Woche

					$iWeeks = (int)$aResult[$iKey]['weeks'];

//					$oDateFrom = Ext_TC_Util::getDateTimeObject($aValue['from']);
//					$oDateTill = Ext_TC_Util::getDateTimeObject($aValue['until']);
//
//					$oDiff  = $oDateFrom->diff($oDateTill, true);
//					$iDays  = $oDiff->days + 1;
//					$iWeeks = ceil($iDays / 7);
//
					$aValue['temp_count'] = $iWeeks;

					$sWeeks = \Ext_TC_Placeholder_Abstract::translateFrontend('Woche', $sLang);
					if($iWeeks > 1){
						$sWeeks = \Ext_TC_Placeholder_Abstract::translateFrontend('Wochen', $sLang);
					}

					$aResult[$iKey]['price'] = $this->_calculatePrice($aValue, $oSchool);
					$aResult[$iKey]['payment'] = $iWeeks.' '.$sWeeks;

					break;

			}

		}

		return $aResult;
	}

	protected function _calculatePrice($aValue, Ext_Thebing_School $oSchool)
	{
		global $user_data;

		$iCount = $aValue['temp_count'];
		$iPrice = 0;

		// In Timestamp konvertieren für alten Code
		$aValue['inquiry_created'] = new DateTime(strstr($aValue['inquiry_created'], ' ', true));
		$aValue['inquiry_created'] = $aValue['inquiry_created']->getTimestamp();

		switch($aValue['payment'])
		{
			case 1: // Einmalig
			case 2: // Pro Tag
			{
				$oDate = new WDDate($aValue['from']);

				while($iCount--)
				{
					$oSaisonSearch	= new Ext_Thebing_Saison_Search();
					$aSaison		= $oSaisonSearch->bySchoolAndTimestamp($oSchool->getId(), $oDate->get(WDDate::TIMESTAMP), $aValue['inquiry_created'], 'insurance', true, 0, 0, 0, 0, 0, 1);
					$iSaisonID		= $aSaison[0]['id'];

					if($aValue['payment'] == 1)
					{
						return $this->_getDayPrice($iSaisonID, $aValue, $oSchool);
					}

					$iPrice += $this->_getDayPrice($iSaisonID, $aValue, $oSchool);

					$oDate->add(1, WDDate::DAY);
				}

				break;
			}
			case 3: // Pro Woche
			{
				$oClient = Ext_Thebing_Client::getFirstClient();

				if($oClient->insurance_price_method == 1)
				{
					$iPrice = $this->_getWeekPricePerUnit($aValue, $oSchool);
				}
				else
				{
					$iPrice = $this->_getWeekPriceNormal($aValue, $oSchool);
				}

				break;
			}
		}

		return $iPrice;
	}

	protected function _getDayPrice($iSaisonID, $aValue, Ext_Thebing_School $oSchool) {

		if (empty($aValue['insurance_id'])) {
			throw new \LogicException('No insurance_id in data construct');
		}

		$aSql = array(
			'iID'			=> (int)$aValue['id'],
			'iSchoolID'		=> (int)$oSchool->getId(),
			'iPeriodID'		=> (int)$iSaisonID
		);
		
		$sCacheKey = '_getDayPrice_'.implode('_', $aSql);
		
		if(
			isset($aValue['insurance_id']) && 
			$aValue['insurance_id'] > 0
		) {
			$sCacheKey .= '_'.$aValue['insurance_id'];
			$sCacheKey .= '_'.$aValue['currency_id'];
		}

		if(!array_key_exists($sCacheKey, self::$aCache)) {

			// For frontend (registration forms)
			if(
				isset($aValue['insurance_id']) && 
				$aValue['insurance_id'] > 0
			) {

				$sSql = "
					SELECT
						`price`
					FROM
						`kolumbus_insurance_prices`
					WHERE
						`insurance_id`	= " . (int)$aValue['insurance_id'] . " AND
						`currency_id`	= " . (int)$aValue['currency_id'] . " AND
						`active`		= 1				AND
						`school_id`		= :iSchoolID	AND
						`period_id`		= :iPeriodID AND
						`week_id` = 0
					";
			} else {
				/*$sSql = "
					SELECT
					`kip`.`price`
				FROM
					`kolumbus_insurance_prices` AS `kip`		INNER JOIN
					`ts_inquiries_journeys_insurances` AS `kii`		ON
						`kip`.`insurance_id` = `kii`.`insurance_id`		AND
						`kii`.`id` = :iID						INNER JOIN
					`ts_inquiries_journeys` `ts_i_j` ON
						`ts_i_j`.`id` = `kii`.`journey_id` AND
						`ts_i_j`.`active` = 1 INNER JOIN
					`ts_inquiries` AS `ki`					ON
						`ts_i_j`.`inquiry_id` = `ki`.`id`
				WHERE
					`kip`.`active`		= 1				AND
					`kip`.`school_id`	= :iSchoolID	AND
					`kip`.`period_id`	= :iPeriodID	AND
					`kip`.`currency_id`	= `ki`.`currency_id` AND
					`kip`.`week_id` = 0
				";*/
			}

			self::$aCache[$sCacheKey] = (float)DB::getQueryOne($sSql, $aSql);

		}

		return self::$aCache[$sCacheKey];
	}

	/**
	 * @TODO Hier wird weeks nicht benutzt, sondern mal wieder errechnet
	 *
	 * @param $aValue
	 * @param Ext_Thebing_School $oSchool
	 * @return float|int
	 */
	protected function _getWeekPricePerUnit($aValue, Ext_Thebing_School $oSchool) {

		if (empty($aValue['insurance_id'])) {
			throw new \LogicException('No insurance_id in data construct');
		}

		$iPrice = 0;

		$iCount = $aValue['temp_count'];

		$oDate = new WDDate($aValue['from']);

		$aCache = $aLast = array();

		$iWeek = 0;

		while($iCount--)
		{
			$oSaisonSearch	= new Ext_Thebing_Saison_Search();
			$aSaison		= $oSaisonSearch->bySchoolAndTimestamp($oSchool->getId(), $oDate->get(WDDate::TIMESTAMP), $aValue['inquiry_created'], 'insurance', true, 0, 0, 0, 0, 0, 1);
			$iSaisonID		= (int)$aSaison[0]['id'];

			if(
				$iSaisonID <= 0
			)
			{
				$this->aErrors['insurance_season_not_found'][$aValue['id']] = $aValue['insurance'];
			}

			$oDate->add(1, WDDate::WEEK);

			if(isset($aCache[$iSaisonID]))
			{
				$aCache[$iSaisonID][++$iWeek] = $aLast;

				continue;
			}

			// For frontend (registration forms)
			if(isset($aValue['insurance_id']) && $aValue['insurance_id'] > 0)
			{
			$sSQL = "
				SELECT
					`kinsw`.*,
					`kip`.`price`
				FROM
					`kolumbus_insurance_prices` AS `kip`		INNER JOIN
						`kolumbus_insurance_weeks` AS `kinsw`		ON
							`kip`.`week_id` = `kinsw`.`id`
					WHERE
						`kip`.`insurance_id`	= " . (int)$aValue['insurance_id'] . " AND
						`kip`.`currency_id`		= " . (int)$aValue['currency_id'] . " AND
						`kip`.`active`			= 1				AND
						`kip`.`school_id`		= :iSchoolID	AND
						`kip`.`period_id`		= :iPeriodID	AND
						/*`kinsw`.`startweek` != 0 AND*/
						(
							(
								`kinsw`.`startweek`	<= :iWeeks AND `kinsw`.`weeks` <= :iWeeks
							) OR
							`kinsw`.`extra` = 1
						)
					ORDER BY
						`kinsw`.`extra`, /* Extrawoche immer zuletzt */
						`kinsw`.`startweek` DESC
					LIMIT
						1
				";
			}
			else // For backend
			{
				/*$sSQL = "
					SELECT
						`kinsw`.*,
						`kip`.`price`
					FROM
						`kolumbus_insurance_prices` AS `kip`		INNER JOIN
					`ts_inquiries_journeys_insurances` AS `kii`		ON
						`kip`.`insurance_id` = `kii`.`insurance_id`		AND
						`kii`.`id` = :iID						INNER JOIN
					`ts_inquiries_journeys` `ts_i_j` ON
						`ts_i_j`.`id` = `kii`.`journey_id` AND
						`ts_i_j`.`active` = 1 INNER JOIN
					`ts_inquiries` AS `ki`					ON
						`ts_i_j`.`inquiry_id` = `ki`.`id`					AND
						`kip`.`currency_id`	= `ki`.`currency_id`	INNER JOIN
					`kolumbus_insurance_weeks` AS `kinsw`			ON
						`kip`.`week_id` = `kinsw`.`id`
				WHERE
					`kip`.`active`		= 1				AND
					`kip`.`school_id`	= :iSchoolID	AND
					`kip`.`period_id`	= :iPeriodID	AND
					/*`kinsw`.`startweek` != 0 AND/
					(
						(
							`kinsw`.`startweek`	<= :iWeeks AND `kinsw`.`weeks` >= :iWeeks
						) OR
						`kinsw`.`extra` = 1
					)
					ORDER BY
						`kinsw`.`extra`
					LIMIT
						1
			";*/
			}

			$aSQL = array(
				'iID'		=> (int)$aValue['id'],
				'iSchoolID'	=> (int)$oSchool->getId(),
				'iPeriodID'	=> (int)$iSaisonID,
				'iWeeks'	=> (int)$aValue['temp_count']
			);
			$aLast = $aCache[$iSaisonID][++$iWeek] = DB::getQueryRow($sSQL, $aSQL);
		}

		foreach((array)$aCache as $iSaisonID => $aWeeks)
		{
			foreach((array)$aWeeks as $iWeek => $aWeek)
			{
				$iPrice += (float)$aWeek['price'];
			}
		}

		return $iPrice;
	}

	/**
	 * @TODO Hier wird weeks nicht benutzt, sondern mal wieder errechnet
	 *
	 * @param $aValue
	 * @param Ext_Thebing_School $oSchool
	 * @return float|int
	 */
	protected function _getWeekPriceNormal($aValue, Ext_Thebing_School $oSchool) {

		if (empty($aValue['insurance_id'])) {
			throw new \LogicException('No insurance_id in data construct');
		}

		$iCount = $aValue['temp_count'];

		$oDate = new WDDate($aValue['from']);

		$aCache = $aExtras = array();

		$iWeek = $iLastSaison = 0;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Prepare cache array

//		$sSql = "
//			SELECT
//				`kiw`.`weeks`
//			FROM
//				`ts_inquiries_journeys_insurances` `kii` INNER JOIN
//				`kolumbus_insurances` `kins` ON
//					`kii`.`insurance_id` = `kins`.`id` AND
//					`kii`.`active` = 1 INNER JOIN
//				`kolumbus_insurances2weeks` `ki2w` ON
//					`ki2w`.`insurance_id` = `kins`.`id` INNER JOIN
//				`kolumbus_insurance_weeks` `kiw` ON
//					`kiw`.`id` = `ki2w`.`week_id` AND
//					`kiw`.`active` = 1
//			WHERE
//				`kii`.`active` = 1 AND
//				`kii`.`id` = :inquiry_insurance_id
//		";
//
//		$aSql = array(
//			'inquiry_insurance_id' => (int)$aValue['id']
//		);

		// Keine Abhängigkeit auf gespeicherte Objekte mehr
		$sSql = "
			SELECT
				`kiw`.`weeks`
			FROM
			    `kolumbus_insurances` `kins` INNER JOIN
				`kolumbus_insurances2weeks` `ki2w` ON
					`ki2w`.`insurance_id` = `kins`.`id` INNER JOIN
				`kolumbus_insurance_weeks` `kiw` ON
					`kiw`.`id` = `ki2w`.`week_id` AND
					`kiw`.`active` = 1
			WHERE
				`kins`.`id` = :insurance_id
		";

		$aSql = ['insurance_id' => $aValue['insurance_id']];

		$aWeekNums = (array)DB::getQueryCol($sSql, $aSql);

		while($iCount--) {
		
			$oSaisonSearch	= new Ext_Thebing_Saison_Search();
			$aSaison		= $oSaisonSearch->bySchoolAndTimestamp($oSchool->getId(), $oDate->get(WDDate::TIMESTAMP), $aValue['inquiry_created'], 'insurance', true, 0, 0, 0, 0, 0, 1);
			$iSaisonID		= (int)$aSaison[0]['id'];

			if($iSaisonID <= 0) {
				$this->aErrors['insurance_season_not_found'][$aValue['id']] = $aValue['insurance'];
			}

			$oDate->add(1, WDDate::WEEK);

			$sWhere = "";

			// Ignore the extra price entry
			if($iLastSaison == $iSaisonID) {
				$sWhere .= " AND `kinsw`.`extra` = 0 ";
			}

			$sSQL = "
				SELECT
					`kinsw`.`weeks`,
					`kinsw`.`extra`,
					`kip`.`price`
				FROM
					`kolumbus_insurance_prices` AS `kip` JOIN
					`kolumbus_insurances2weeks` `ki2w` ON
						`kip`.`insurance_id` = `ki2w`.`insurance_id` AND
						`kip`.`week_id` = `ki2w`.`week_id` JOIN
					`kolumbus_insurance_weeks` AS `kinsw` ON
						`kip`.`week_id` = `kinsw`.`id`
				WHERE
					`kip`.`insurance_id`	= " . (int)$aValue['insurance_id'] . " AND
					`kip`.`currency_id`		= " . (int)$aValue['currency_id'] . " AND
					`kip`.`active`			= 1				AND
					`kinsw`.`active`		= 1				AND
					`kip`.`school_id`		= :iSchoolID	AND
					`kip`.`period_id`		= :iPeriodID	AND
					`kinsw`.`startweek`		= 0
					" . $sWhere . "
				ORDER BY
					`kinsw`.`extra`,
					`kinsw`.`weeks`
			";

			$aSQL = array(
				'iID'		=> (int)$aValue['id'],
				'iSchoolID'	=> (int)$oSchool->getId(),
				'iPeriodID'	=> (int)$iSaisonID
			);

			$aLast = DB::getPreparedQueryData($sSQL, $aSQL);

			// Prepare extra prices of new saison only on first call
			if($iLastSaison != $iSaisonID && !isset($aExtras[$iSaisonID]))
			{
				foreach((array)$aLast as $iKey => $aWeek)
				{
					if($aWeek['extra'] == 1)
					{
						$aExtras[$iSaisonID][$aWeek['weeks']] = $aWeek['price'];

						// Prepare extra prices for the length of calculation period
						while($aWeek['weeks']++ < $aValue['temp_count'])
						{
							$aExtras[$iSaisonID][$aWeek['weeks']] = $aWeek['price'];
						}

						// Unset extra price value
						unset($aLast[$iKey]);
					}
				}

				$iLastSaison = $iSaisonID;
			}

			// Redesign the cache array
			$aTemp = array();
			foreach((array)$aLast as $iKey => $aTempWeek)
			{
				if($aTempWeek['extra'])
				{
					continue;
				}
				$aTemp[$aTempWeek['weeks']] = $aTempWeek['price'];
			}

			$iWeekNum = $iWeek + 1;

			if(!in_array($iWeekNum, $aWeekNums) && !isset($aExtras[$iSaisonID][$iWeekNum]))
			{
				$this->aErrors['insurance_wrong_week_number'][$aValue['id']] = $aValue['insurance'];
			}

			$aCache[++$iWeek][$iSaisonID] = $aTemp;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Calculate price

		$iPrice = $amountOfThisSeasonOfWeekBeforeSaisonSwitch = 0;
		$iLastSaison = null;

		$aTemp = array();

		foreach((array)$aCache as $iWeek => $aOneSaison) {
			
			// $aCache[$iWeek] includes only one saison
			foreach((array)$aOneSaison as $iSaisonID => $aWeeks) {

				// Extra weeks
				if(!isset($aWeeks[$iWeek])) {
					
					$aKeys = array_keys($aWeeks);
					$iMaxWeek = 0;
					
					if(!empty($aKeys))
					{
						$iMaxWeek = max($aKeys);
					}
					
					if($iWeek > $iMaxWeek)
					{
						$iStart = $iMaxWeek + 1;

						for($i=$iStart; $i<=$iWeek; $i++)
						{
							$iCurrentPrice = (float)end($aWeeks);

							$aWeeks[$i] = $iCurrentPrice + (float)$aExtras[$iSaisonID][$i];
						}

						$aCache[$iWeek][$iSaisonID] = $aWeeks;
					}
				}

				// No saison break on first week
				if($iLastSaison === null)
				{
					$iLastSaison = $iSaisonID;
				} 

				// If the next week is in other saison and price ist empty
				if(
					!isset($aCache[$iWeek + 1][$iSaisonID]) && 
					$iPrice == 0
				) {
					
					// $aWeeks not includes the price for the last calculation week bacause the next break follow
					// Wenn die nächste Woche noch unter der "Maximalanzahl" ist
					if($iWeek + 1 < $aValue['temp_count']) {
						// Wenn es ein Preis für diese Wochenanzahl gibt
						if(isset($aWeeks[$iWeek])) {
							$iPrice = (float)$aWeeks[$iWeek];
							$this->aCalculationDescription[] = '+'.$aWeeks[$iWeek].' ('.$iWeek.')';
						} else {
							$iPrice = (float)end($aWeeks);
							$this->aCalculationDescription[] = '+'.end($aWeeks);
						}
					} else {
						$iPrice = (float)$aWeeks[$aValue['temp_count']];
						$this->aCalculationDescription[] = '+'.$aWeeks[$aValue['temp_count']].' ('.$aValue['temp_count'].')';
					}
				}

				// Wenn die Saison sich einmal gewechselt hat (nicht nur in der einen Woche, wo der wechsel war sondern
				// dann immer wieder, weil $iLastSaison sich nicht wechselt, bis zur letzten Woche (s.u.)
				if($iLastSaison != $iSaisonID)
				{

					if($amountOfThisSeasonOfWeekBeforeSaisonSwitch == 0)
					{
						// Notice the tamporary payed weeks price
						$amountOfThisSeasonOfWeekBeforeSaisonSwitch = (float)$aWeeks[$iWeek - 1];
					}

					// Letzte Woche (der Saison)
					if(!isset($aCache[$iWeek + 1][$iSaisonID]))
					{
						$iLastSaison = $iSaisonID;

						// z.B. 6 Wochen Hochsaison - 3 Wochen Hochsaison, für den Preis von 3 Wochen hochsaison oder
						// 4 Wochen Hochsaison - 2 Wochen Hochsaison, für den Preis von 2 Wochen hochsaison
						// -> Der Preis für einfach nur 2 Wochen kann ein anderer sein, deswegen muss man das so berechnen
						// (siehe Nextcloud Thebing_SMS_Kompendium)
						$iTemp = (float)$aWeeks[$iWeek] - $amountOfThisSeasonOfWeekBeforeSaisonSwitch;

						$iPrice += $iTemp;
						
						$this->aCalculationDescription[] = '+'.$iTemp;

							$amountOfThisSeasonOfWeekBeforeSaisonSwitch = 0;
					}
				}
			}
		}

		return $iPrice;
	}

	public function switchAjaxRequest($_VARS) {

		if($_VARS['action'] == 'provider_accepted') {

			$_VARS['id']	= (array)$this->_oGui->decodeId((array)$_VARS['id'], 'inquiry_insurance_id');
						
			$bUnset = false;			
			foreach((array)$_VARS['id'] as $iInquiryInsuranceId){
				$oInquiryInsurance  = Ext_TS_Inquiry_Journey_Insurance::getInstance($iInquiryInsuranceId);
				if($oInquiryInsurance->isConfirmed()) {
					$oInquiryInsurance->confirm = 0;
					$bUnset = true;
				} else {
					$oInquiryInsurance->confirm = time();
				}
				$oInquiryInsurance->changes_confirm = 0;
				$oInquiryInsurance->save();
			}

			$aTransfer['action'] = 'showSuccess';
			$aTransfer['load_table'] = true;
			
			if($bUnset) {
				$aTransfer['message'] = $this->t('Bestätigungsinformationen wurden verändert');
			} else {
				$aTransfer['message'] = $this->t('Versicherungsanbieter hat bestätigt.');
			}
			
			echo json_encode($aTransfer);

		} else {
			/*
			 * da pro Buchungen mehrere Versicherungen hinzugefügt werden können muss auch für jede Versicherung
			 * eine Dokument erzeugt werden. Bei "true" wird nur für die Buchung ein Dokument erzeugt 
			 */
			$this->_bUniqueInquiriesDocuments = false;
			
			parent::switchAjaxRequest($_VARS);
		}

	}

	public function prepareColumnListByRef(&$aColumnList) {

		parent::prepareColumnListByRef($aColumnList);

		if(System::d('debugmode') == 2) {
			$oColumn = new Ext_Gui2_Head();
			$oColumn->db_column = 'inquiry_insurance_id';
			$oColumn->title = 'IJI-ID';
			$oColumn->width = 50;
			$oColumn->sortable = false;
			array_unshift($aColumnList, $oColumn);
		}

	}

	public function getCalculationDescription() {
		return $this->aCalculationDescription;
	}

    public static function getDefaultFilterFrom() {
		return Ext_Thebing_Format::LocalDate(\Carbon\Carbon::today());
    }

    public static function getDefaultFilterUntil() {
		return Ext_Thebing_Format::LocalDate(
			\Carbon\Carbon::today()->addYear()->endOfYear()
		);
    }

    public static function getInsurancesOptions()
    {
        $oSchool = Ext_Thebing_School::getSchoolFromSession();

        $aSchoolLanguages = $oSchool->getLanguageList();
        $sInterfaceLanguage = \System::getInterfaceLanguage();

        if(in_array($sInterfaceLanguage, $aSchoolLanguages)) {
            $sLanguage = $sInterfaceLanguage;
        } else {
            $sLanguage = $oSchool->getLanguage();
        }
        $oInsurance = Ext_Thebing_Insurance::getInstance();
        $aInsurances = $oInsurance->getArrayList(true, 'name_'.$sLanguage);

		return $aInsurances;
    }

    public static function getSearchCustomerStatusOptions (\Ext_Thebing_Gui2 $oGui)
    {
        $aSearchCustomerStatus = array(
            'not_receive' => $oGui->t('Kunde Dokument nicht erhalten'),
            'receive' => $oGui->t('Kunde Dokument erhalten')
        );

		return $aSearchCustomerStatus;
    }

    public static function getSearchProdiverStatusOptions (Ext_Thebing_Gui2 $oGui)
    {
        $aSearchProdiverStatus = array(
            'not_informed' => $oGui->t('Anbieter nicht informiert'),
            'informed' => $oGui->t('Anbieter informiert')
        );

		return $aSearchProdiverStatus;
    }

    public static function getCanceledOptions (\Ext_Thebing_Gui2 $oGui)
    {
        $aCanceledOptions = array(
            'canceled' => $oGui->t('Storniert'),
            'not_canceled' => $oGui->t('Nicht storniert')
        );

		return $aCanceledOptions;
    }

    public static function getConfirmedProviderOptions(\Ext_Thebing_Gui2 $oGui)
    {
        $aConfirmedProvider = array(
            'confirmed' => $oGui->t('Bestätigt'),
            'not_confirmed' => $oGui->t('Nicht bestätigt')
        );

        return $aConfirmedProvider;
    }

    public static function getInboxesOptions()
    {
        $oClient = Ext_Thebing_System::getClient();
        $aInboxes = $oClient->getInboxList(true, true);

		return $aInboxes;
    }

    public static function getYesNoOptions()
    {
        $aYesNo = Ext_Thebing_Util::getYesNoArray(false);

		return $aYesNo;
    }

    public static function getFilterOptions(\Ext_Thebing_Gui2 $oGui)
    {
        $oFilter = [
            ''			=>	$oGui->t('Alle Dokumente'),
            'invoice'	=>	$oGui->t('Nur Rechnungen'),
            'proforma'	=>	$oGui->t('Nur Proforma')
        ];

		return $oFilter;
    }

	static public function manipulateSearchFilter(\Ext_Gui2 $oGui) {

		$defaultLang = \Ext_Thebing_Util::getInterfaceLanguage();

		return [
			'column' => [
				'lastname',
				'firstname',
				'name_'.$defaultLang,
				'email',
				'number',
			],
			'alias' => [
			   'tc_c',
			   'tc_c',
			   'kins',
			   'tc_ea',
			   'tc_cn',
			]
		];

	}

}
