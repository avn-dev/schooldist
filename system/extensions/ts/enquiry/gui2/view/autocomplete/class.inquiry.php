<?php

use \ElasticaAdapter\Facade\Elastica;
use \Elastica\Query;

/**
 * @TODO Redundanz mit Ext_Thebing_Customer_Search und Ext_Thebing_Examination_Autocomplete
 */
class Ext_TS_Enquiry_Gui2_View_Autocomplete_Inquiry extends Ext_Gui2_View_Autocomplete_Abstract {

	public function __construct(
		private ?string $type = null
	) {}

	public function getOption($aSaveField, $sValue) {

		$sValue = (int)$sValue;
		if(empty($sValue)) {
			return '';
		}

		$oInquiry = Ext_TS_Inquiry::getInstance((int)$sValue);
		$oContact = $oInquiry->getTraveller();
		$sName = $oContact->getCustomerNumber().' '.$oContact->getName();

		return $sName;

	}

	public function getOptions($sInput, $aSelectedIds, $aSaveField) {
		$oSearch = self::buildSearchObject($sInput, $this->type, !empty($aSaveField['autocomplete_exclude_with_storno']));
		$oSearch->setSort('created_original');
		$oSearch->setLimit(100);

		$aResult = $oSearch->search();

		$aInquiries = [];
		foreach($aResult['hits'] as $aHit) {
			foreach($aHit['fields'] as &$mValue) {
				if(is_array($mValue)) {
					$mValue = reset($mValue);
				}
			}

			if ($aHit['fields']['type'] === Ext_TS_Inquiry::TYPE_ENQUIRY_STRING) {
				$sTranslation = L10N::t('Anfrage erstellt', 'Thebing » Enquiries');
			} else {
				$sTranslation = L10N::t('Buchung erstellt', 'Thebing » Enquiries');
			}

			$dDate = new DateTime($aHit['fields']['created_original']);
			$sDate = '('.$sTranslation.': '.Ext_Thebing_Format::LocalDate($dDate).')';

			// $aOptions[$aData['id']] = $aData['costumber_number'].' '.$aData['name'].' '.$oFormat->format($aData['birthday']);
			$aInquiries[$aHit['_id']] = $aHit['fields']['customer_number'].' '.$aHit['fields']['customer_name'].' '.$sDate;
		}

		return $aInquiries;

	}

	public static function buildSearchObject(string $query, string $type = null, $excludeWithStorno = false)
	{
		$oSearch = new Elastica(Elastica::buildIndexName('ts_inquiry'));
		$sSearch = $oSearch->escapeTerm($query);

		if(mb_substr($sSearch, -1) !== '*') {
			$sSearch .= '*';
		}

		$oBool = new Query\BoolQuery();
		$oBool->setMinimumShouldMatch(1);

		$oQuery = new Query\QueryString();
		$oQuery->setQuery($sSearch);
		$oQuery->setDefaultField('document_number_all');
		$oQuery->setDefaultOperator('AND');
		$oBool->addShould($oQuery);

		$oQuery = new Query\QueryString();
		$oQuery->setQuery($sSearch);
		$oQuery->setDefaultField('customer_name');
		$oQuery->setDefaultOperator('AND');
		$oBool->addShould($oQuery);

		$oQuery = new Query\QueryString();
		$oQuery->setQuery($sSearch);
		$oQuery->setDefaultField('email_original');
		$oQuery->setDefaultOperator('AND');
		$oBool->addShould($oQuery);

		$oQuery = new Query\QueryString();
		$oQuery->setQuery($sSearch);
		$oQuery->setDefaultField('customer_number');
		$oQuery->setDefaultOperator('AND');
		$oBool->addShould($oQuery);

		if ($type !== null) {
			// Gezielt nach "booking" oder "enquiry" suchen
			$oQuery = new Query\QueryString();
			$oQuery->setQuery($type);
			$oQuery->setDefaultField('type');
			$oQuery->setDefaultOperator('AND');
			$oBool->addMust($oQuery);
		}

		if ($excludeWithStorno) {
			$oBool->addMustNot(new Query\Term(['invoice_status' => 'cancelled']));
		}

		$oSearch->setFields(['_id', 'customer_number', 'customer_name', 'created_original', 'type']);
		$oSearch->addMustQuery($oBool);

		return $oSearch;
	}

}

