<?php


class Ext_Thebing_Inquiry_Document_Numberrange extends Ext_TS_NumberRange {

	protected $_sNumberTable = 'kolumbus_inquiries_documents';
	protected $_sNumberField = 'document_number';

	/**
	 * @param string $sDocumentType
	 * @param bool $bIsCredit Schien mal für Gutschriften (nicht CNs) eingebaut worden zu sein, wird aber nicht verwendet
	 * @return string|null
	 */
	public static function getApplicationByType($sDocumentType, $bIsCredit=false) {

		$sApplication = null;
		
		if($sDocumentType === 'creditnote_subagency') {
			$sApplication = 'creditnote'; // creditnote, proforma_creditnote
		} elseif(strpos($sDocumentType, 'creditnote') !== false) {
			$sApplication = $sDocumentType; // creditnote, proforma_creditnote
		} elseif(strpos($sDocumentType, 'proforma') !== false) {
			$sApplication = 'proforma';
		} elseif(strpos($sDocumentType, 'storno') !== false) {
			$sApplication = 'cancellation';
		} else if(
			$sDocumentType == 'receipt_customer' || 
			$sDocumentType == 'receipt_agency'
		) {
			$sApplication = 'payment_receipt';
		} else if(
			$sDocumentType == 'document_payment_customer' || 
			$sDocumentType == 'document_payment_agency'
		) {
			$sApplication = 'invoice_payments';
		} else if(
			$sDocumentType == 'document_payment_overview_customer' || 
			$sDocumentType == 'document_payment_overview_agency'
		) {
			$sApplication = 'inquiry_payments';
		} else if(
			$sDocumentType == 'enquiry' ||
			strpos($sDocumentType, 'offer') !== false
		){
			$sApplication = 'enquiry';
		} else if($sDocumentType == 'certificate') {
			// Spezielles Zusatzdokument mit Template-Type "document_certificates"
			$sApplication = 'certificate';
		} else if($sDocumentType == 'manual_creditnote') {
			$sApplication = 'manual_creditnote';
		} else if($sDocumentType == 'additional_document') {
			// Normale Zusatzdokumente haben keine Nummern
		} else {
			$sApplication = 'invoice';
		}

		return $sApplication;
	}

	/**
	 * @param $sDocumentType
	 * @param bool $bIsCredit
	 * @param null $iObjectId
	 * @param null $iInvoiceNumberrangeId
	 * @return Ext_Thebing_Inquiry_Document_Numberrange
	 */
	public static function getObject($sDocumentType, $bIsCredit=false, $iObjectId=null, $iInvoiceNumberrangeId=null) {

		if($iObjectId === null) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$iObjectId = $oSchool->id;
		}

		// $sDocumentType ist eigentlich wirklich immer der Dokument-Typ, daher mappt diese Methode die Numberrange-Application
		$sApplication = self::getApplicationByType($sDocumentType, (bool)$bIsCredit);

		$oNumberrange = self::getByApplicationAndObject($sApplication, $iObjectId, $iInvoiceNumberrangeId);

		return $oNumberrange;
	}
	
	public static function getNumberrangesByType($sDocumentType, $bIsCredit=false, $bCheckAccess=true, $iObjectId=null, $bAsObjects=false) {

		$sApplication = self::getApplicationByType($sDocumentType, (bool)$bIsCredit);

		$aSql = array(
			'application' => $sApplication
		);

		$sWhere = '';
		if(!is_null($iObjectId)) {
			$aSql['object_id'] = $iObjectId;
			$sWhere = " AND  `tc_nrao`.`object_id` = :object_id ";
		}

		$sSql = "
			SELECT
				`tc_nr`.`id`,
				`tc_nr`.`name`
			FROM
				`tc_number_ranges_allocations_objects` `tc_nrao` JOIN
				`tc_number_ranges_allocations` `tc_nra` ON
					`tc_nra`.`id` = `tc_nrao`.`allocation_id` AND
					`tc_nra`.`active` = 1 JOIN
				`tc_number_ranges_allocations_sets` `tc_nras` ON
					`tc_nrao`.`allocation_id` = `tc_nras`.`allocation_id` AND
					`tc_nras`.`active` = 1 JOIN
				`tc_number_ranges_allocations_sets_applications` `tc_nrasa` ON
					`tc_nras`.`id` = `tc_nrasa`.`set_id` JOIN
				`tc_number_ranges` `tc_nr` ON
					`tc_nras`.`numberrange_id` = `tc_nr`.`id`
			WHERE
				`tc_nrasa`.`application` = :application
				".$sWhere."
			ORDER BY
				`tc_nra`.`position`
			";
		
		static::manipulateSqlNumberRangeQuery($sSql, $aSql);
		
		$aNumberranges = (array)DB::getQueryPairs($sSql, $aSql);

		// Zugriff prüfen
		if($bCheckAccess === true) {
			$oAccessMatrix = new Ext_TC_Numberrange_AccessMatrix;
			$aAccessItems = (array)$oAccessMatrix->getListByUserRight();
			$aNumberranges = array_intersect_assoc($aNumberranges, $aAccessItems);
		}

		if($bAsObjects) {

			$aReturn = array();
			foreach($aNumberranges as $iNumberrangeId => $sName) {
				$aReturn[] = static::getInstance($iNumberrangeId);
			}

			return $aReturn;
		}

		return $aNumberranges;

	}

	/**
	 * Nummernkreis Select Row generieren
	 * 
	 * @param Ext_Gui2 $oGui
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param string $sDocumentType
	 * @param string $sTypeNumberRange
	 * @return false|Ext_Gui2_Html_Div 
	 */
	public static function getNumberrangeRow(Ext_Gui2 $oGui, Ext_Gui2_Dialog $oDialog, $aTemplateSaveFieldOptions, $sDocumentType, $sTypeNumberRange = false, Ext_Thebing_School $oSchool = null) {

		if(!Access::getInstance()->hasRight('thebing_invoice_numberranges')) {
			return false;
		}

		$bIsNumberRequired = Ext_Thebing_Inquiry_Document::isNumberRequiredForType($sDocumentType, $sTypeNumberRange);

		// Darf kein Pflichtfeld sein, wenn Nummer nicht Pflicht ist
		if(!$bIsNumberRequired) {
			$aTemplateSaveFieldOptions['required'] = false;
		}

		if(!$sTypeNumberRange) {
			$sTypeNumberRange = $sDocumentType;
		}

		if(!($oSchool instanceof Ext_Thebing_School)) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
		}

		/*
		 * In der All-Schools Ansicht würden hier niemals Nummernkreise rauskommen, weil Schul-ID = 0.
		 * Nach Bedarf die Schul-ID aus der aktuellen Entity holen, nicht optimal hier solche "Magie" zu
		 * veranstalten aber wenigstens funktioniert es ... (#9705)
		 */
		if($oSchool->id < 1) {
			$oEntity = $oGui->getWDBasic();
			if($oEntity instanceof Ext_TS_Enquiry_Offer) {
				$oSchool = $oEntity->getSchool();
			} elseif($oEntity instanceof Ext_Thebing_Agency_Manual_Creditnote) {
				$oSchool = $oEntity->getSchool();
				if(!($oSchool instanceof Ext_Thebing_School)) {
					$oEntity = $oGui->getDataObject()->oWDBasic;
					if($oEntity instanceof Ext_Thebing_Agency_Manual_Creditnote) {
						$oSchool = $oEntity->getSchool();
					}
				}
			}
		}

		if(!($oSchool instanceof Ext_Thebing_School)) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
		}

		// TODO $bIsCredit wurde hier benutzt (als $iIsCredit), aber war nicht gesetzt?
		$bIsCredit = false;

		$aNumberranges = (array)Ext_Thebing_Inquiry_Document_Numberrange::getNumberrangesByType($sTypeNumberRange, $bIsCredit);
		$oNumberrange = Ext_Thebing_Inquiry_Document_Numberrange::getObject($sTypeNumberRange, $bIsCredit, $oSchool->id);

		// Prüfen, ob Defaultnummernkreis noch im Array ist
		if(
			$oNumberrange->id > 0 &&
			!array_key_exists($oNumberrange->id, $aNumberranges)
		) {
			$aNumberranges[$oNumberrange->id] = $oNumberrange->name;
			#asort($aNumberranges); // Fällt weg, da Nummernkreise par drag&drop sortierbar sind T3946
		}

		// Wenn keine Nummernkreise verfügbar sind und keine Pflicht sind, dann nicht anzeigen
		if(
			$bIsNumberRequired !== true &&
			empty($aNumberranges)
		) {
			return null;
		}
		
		/*
		 * Wenn ein Nummernkreis benötigt wird ($bIsNumberRequired = true) auf jeden Fall das Select generieren,
		 * auch wenn kein Nummernkreis zum auswählen gefunden wurde
		 */
		$aTemplateSaveFieldOptions['select_options'] = $aNumberranges;
		$aTemplateSaveFieldOptions['default_value'] = $oNumberrange->id;

		return $oDialog->createRow($oGui->t('Nummernkreis'), 'select', $aTemplateSaveFieldOptions);
	}

}
