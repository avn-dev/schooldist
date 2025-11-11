<?php

namespace TsStatistic\Generator\Statistic\Agency;

use \TcStatistic\Exception\NoResultsException;
use \TsStatistic\Generator\Statistic\AbstractGenerator;
use \TcStatistic\Model\Statistic\Column;
use \TcStatistic\Model\Table;
use \TsStatistic\Model\Filter;
use \TsStatistic\Service\DocumentItemAmount;

/**
 * Zahlungseinnahmen pro Agentur
 *
 * https://redmine.thebing.com/issues/11560
 */
class PaymentRevenue extends AbstractGenerator {

	protected $aAvailableFilters = [
		Filter\Schools::class,
		Filter\Agency\Country::class,
	];

	/**
	 * @var
	 */
	protected $iCurrencyId;

	/**
	 * @inheritdoc
	 */
	public function getTitle() {
		return self::t('Agenturumsatz (Zahlungen)');
	}

	/**
	 * @inheritdoc
	 */
	protected function getColumns() {
		$aColumns = [];

//		if(\System::d('debugmode') == 2) {
//			$oColumn = new Column('debug', 'Debug (Inquiry-IDs)');
//			$oColumn->setBackground('general');
//			$aColumns[] = $oColumn;
//		}

		$oColumn = new Column('agency_name', self::t('Agenturname'));
		$oColumn->setBackground('agency');
		$aColumns[] = $oColumn;

		$oColumn = new Column('agency_country_name', self::t('Agenturland'));
		$oColumn->setBackground('agency');
		$aColumns[] = $oColumn;

		$oColumn = new Column('student_count', self::t('Schüler gesamt'));
		$oColumn->setBackground('booking');
		$aColumns[] = $oColumn;

		$oColumn = new Column('student_weeks', self::t('Kurswochen absolut'));
		$oColumn->setBackground('booking');
		$aColumns[] = $oColumn;

		$oColumn = new Column('amount_gross', self::t('Umsatz - gesamt (brutto, exkl. Steuern)'), 'number_amount');
		$oColumn->setBackground('payment');
		$aColumns[] = $oColumn;

		$oColumn = new Column('amount_commission', self::t('Umsatz - Provision'), 'number_amount');
		$oColumn->setBackground('payment');
		$aColumns[] = $oColumn;

		$oColumn = new Column('amount_net', self::t('Umsatz - gesamt (netto, exkl. Steuern)'), 'number_amount');
		$oColumn->setBackground('payment');
		$aColumns[] = $oColumn;

		$oColumn = new Column('amount_course_net', self::t('Umsatz - Kurs (netto, exkl. Steuern)'), 'number_amount');
		$oColumn->setBackground('payment');
		$aColumns[] = $oColumn;

		$oColumn = new Column('amount_accommodation_net', self::t('Umsatz - Unterkunft (netto, exkl. Steuern)'), 'number_amount');
		$oColumn->setBackground('payment');
		$aColumns[] = $oColumn;

		$oColumn = new Column('amount_other_net', self::t('Umsatz - Rest (netto, exkl. Steuern)'), 'number_amount');
		$oColumn->setBackground('payment');
		$aColumns[] = $oColumn;
		return $aColumns;
	}

	/**
	 * Gruppierung nach Payment-ID und Dokument-ID, da die Summen pro Service-Typ berechnet werden müssen
	 *
	 * @return array
	 */
	protected function getQueryData() {

		$sInterfaceLanguage = \System::getInterfaceLanguage();

		// 1024 Zeichen sind zu wenig für items_payed
		\DB::executeQuery("SET SESSION group_concat_max_len = 1048576");

		$sWhere = "";
		if(!empty($this->aFilters['agency_country'])) {
			$sWhere .= " AND `ka`.`ext_6` = :agency_country ";
		}

		$sSql = "
			SELECT
				`ts_i`.`id` `inquiry_id`,
				`ts_i`.`currency_id` `currency_id`,
				`kip`.`id` `payment_id`,
				/*`kipo`.`amount_inquiry` `overpayment_amount`,*/
				IFNULL(`kid`.`id`, 0) `document_id`,
				`kid`.`type` `document_type`,
				`ka`.`id` `agency_id`,
				`ka`.`ext_1` `agency_name`,
				`ka`.`ext_6` `agency_country`,
				`dc_ka`.`cn_short_{$sInterfaceLanguage}` `agency_country_name`,
				`ts_iti`.`total_course_weeks`,
				GROUP_CONCAT(DISTINCT CONCAT_WS('{|}', `kidvi_kipi`.`id`, `kidvi_kipi`.`type`, `kidvi_kipi`.`tax`, `kidvi_kipi`.`additional_info`, `kipi`.`item_id`, `kipi`.`amount_inquiry`) SEPARATOR '{|:|}') `items_payed`,
				GROUP_CONCAT(DISTINCT `kid`.`id`) `items_document_ids`,
				GROUP_CONCAT(DISTINCT `kid_cn`.`id`) `items_creditnote_ids`
			FROM
				`kolumbus_inquiries_payments` `kip` INNER JOIN
				(
					`kolumbus_inquiries_payments_items` `kipi` INNER JOIN
					`kolumbus_inquiries_documents_versions_items` `kidvi_kipi` INNER JOIN
					`kolumbus_inquiries_documents_versions` `kidv` INNER JOIN
					`kolumbus_inquiries_documents` `kid`
				) ON
					`kipi`.`payment_id` = `kip`.`id` AND
					`kipi`.`active` = 1 AND
					`kidvi_kipi`.`id` = `kipi`.`item_id` AND
					`kidv`.`id` = `kidvi_kipi`.`version_id` AND
					`kid`.`id` = `kidv`.`document_id` AND
					`kid`.`active` = 1 AND
					/* Keine Creditnote-Zahlungen */
					`kid`.`type` IN ( :invoice_types ) LEFT JOIN
				(
					`ts_documents_to_documents` `ts_dtd_cn` INNER JOIN
					`kolumbus_inquiries_documents` `kid_cn`
				) ON
					`ts_dtd_cn`.`parent_document_id` = `kid`.`id` AND
					`ts_dtd_cn`.`type` = 'creditnote' AND
					`kid_cn`.`id` = `ts_dtd_cn`.`child_document_id` /*LEFT JOIN
				`kolumbus_inquiries_payments_overpayment` `kipo` ON
					`kipo`.`payment_id` = `kip`.`id` AND
					`kipo`.`active` = 1*/ INNER JOIN
				`ts_inquiries` `ts_i` ON
					/* Overpayments können auch ohne Payment-Items existieren */
					`ts_i`.`id` = IF(`kid`.`entity` = '".\Ext_TS_Inquiry::class."' AND `kid`.`entity_id` IS NOT NULL, `kid`.`entity_id`, `kip`.`inquiry_id`) AND
					`ts_i`.`active` = 1 AND
					`ts_i`.`agency_id` > 0 AND
					`ts_i`.`canceled` = 0 INNER JOIN 
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
					`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_ij`.`active` = 1 INNER JOIN
				`ts_companies` `ka` ON
					`ka`.`id` = `ts_i`.`agency_id` LEFT JOIN
				`data_countries` `dc_ka` ON
					`dc_ka`.`cn_iso_2` = `ka`.`ext_6` LEFT JOIN
				`ts_inquiries_tuition_index` `ts_iti` ON
					`ts_iti`.`inquiry_id` = `ts_i`.`id` AND
					/* Erste Woche nehmen, da in allen Rows dasselbe in den Total-Spalten drin steht */
					`ts_iti`.`current_week` = 1
			WHERE
				`kip`.`active` = 1 AND
				`kip`.`date` BETWEEN :from AND :until AND
				`ts_ij`.`school_id` IN (:schools)
				{$sWhere}
			GROUP BY
				`payment_id`,
				`document_id`
		";

		$aResult = (array)\DB::getQueryRows($sSql, [
			'from' => $this->aFilters['from']->format('Y-m-d'),
			'until' => $this->aFilters['until']->format('Y-m-d'),
			'schools' => $this->aFilters['schools'],
			'agency_country' => $this->aFilters['agency_country'],
			'invoice_types' => \Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice_without_proforma'),
		]);

		return $aResult;

	}

	/**
	 * @return array
	 */
	protected function prepareData() {

		$aQueryData = $this->getQueryData();

		if(empty($aQueryData)) {
			throw new NoResultsException();
		}

		$aGroupedData = [];

		// $aRow = GROUP BY `payment_id`, `document_id`
		foreach($aQueryData as $aRow) {

			if($this->iCurrencyId === null) {
				$this->iCurrencyId = (int)$aRow['currency_id'];
			}
			if($this->iCurrencyId !== (int)$aRow['currency_id']) {
				throw new \RuntimeException(__CLASS__.' does not work with multiple currencies!');
			}

			if(!isset($aGroupedData[$aRow['agency_id']])) {
				$aGroupedData[$aRow['agency_id']]['agency_name'] = $aRow['agency_name'];
				$aGroupedData[$aRow['agency_id']]['agency_country'] = $aRow['agency_country'];
				$aGroupedData[$aRow['agency_id']]['agency_country_name'] = $aRow['agency_country_name'];

				$aGroupedData[$aRow['agency_id']]['amount_gross'] = 0;
				$aGroupedData[$aRow['agency_id']]['amount_commission'] = 0;
				$aGroupedData[$aRow['agency_id']]['amount_net'] = 0;
				$aGroupedData[$aRow['agency_id']]['amount_course_net'] = 0;
				$aGroupedData[$aRow['agency_id']]['amount_accommodation_net'] = 0;
				$aGroupedData[$aRow['agency_id']]['amount_other_net'] = 0;
			}

			// Über inquiry_id Schüleranzahl und Wochen eindeutig/EINMAL zählen
			$aGroupedData[$aRow['agency_id']]['student_count'][$aRow['inquiry_id']] = 1;
			$aGroupedData[$aRow['agency_id']]['student_weeks'][$aRow['inquiry_id']] = (int)$aRow['total_course_weeks'];

			$aAmountsPayed = $this->getDocumentPayedServiceTypes($aRow);

			$aGroupedData[$aRow['agency_id']]['amount_gross'] += array_sum($aAmountsPayed['gross']);
			$aGroupedData[$aRow['agency_id']]['amount_net'] += array_sum($aAmountsPayed['net']);
			$aGroupedData[$aRow['agency_id']]['amount_commission'] += array_sum($aAmountsPayed['commission']);

			foreach(array_keys($this->getDocumentSumArray()) as $sServiceType) {
				$aGroupedData[$aRow['agency_id']]['amount_'.$sServiceType.'_net'] += $aAmountsPayed['net'][$sServiceType];
			}

		}

		foreach($aGroupedData as $iAgencyId => $aAgencyData) {
			$aGroupedData[$iAgencyId]['debug'] = join(', ', array_keys($aGroupedData[$iAgencyId]['student_count']));
			foreach(['student_count', 'student_weeks'] as $sField) {
				$aGroupedData[$iAgencyId][$sField] = array_sum($aAgencyData[$sField]);
			}

			// Eigentlich sollte man alle Beträge nochmal ausrechnen können, aber wenn CNs fehlen, geht das natürlich nicht
			if(round($aAgencyData['amount_gross'], 2) - round($aAgencyData['amount_commission'], 2) != round($aAgencyData['amount_net'], 2)) {
				$aGroupedData[$iAgencyId]['agency_name'] = $aAgencyData['agency_name'].' *';
			}
		}

		// Absteigend nach Nettoumsatz sortieren
		uasort($aGroupedData, function($aAgencyData1, $aAgencyData2) {
			return $aAgencyData1['amount_net'] < $aAgencyData2['amount_net'];
		});

		return $aGroupedData;

	}

	/**
	 * @return array
	 */
	protected function getDocumentSumArray() {
		return ['course' => 0, 'accommodation' => 0, 'other' => 0];
	}
	
	/**
	 * Hier passiert wieder einmal Magie: Bezahlte Beträge ausrechnen, die es so gar nicht gibt
	 *
	 * In der Software wird bei brutto der Bruttowert und bei netto der Nettowert bezahlt.
	 * Die Provision und der jeweils andere Wert werden niemals bezahlt. Hier bleibt also (mal wieder)
	 * keine andere Möglichkeit, als den bezahlten Betrag als Faktor auf die anderen Werte umzurechnen.
	 * Das funktioniert auch nur, weil hier Items nicht im Detail betrachtet werden, sondern alle Kurse,
	 * Unterkünfte und der Rest einfach zusammensummiert werden. Am besten wäre hier so etwas wie eine
	 * origin_item_id (wegen CNs), wie es diese Spalte auch in der Agentursoftware gibt, da in
	 * einer Bruttorechnung weder Provision noch Nettobeträge enthalten sind.
	 *
	 * Hier müssen demnach erst einmal die bezahlten Items durchgegangen werden. Da die Steuer
	 * immer mitbezahlt wird, muss diese auch in jedem Fall entfernt werden (Unterschied zu kidvi).
	 * Special-Positionen sind wieder einmal ein Sonderfall, da hier der Typ (Kurs, Unterkunft) für
	 * die Summierung benötigt wird und es keine Index-Spalten gibt.
	 *
	 * Danach müssen alle Items aller Dokumente, welche der Zahlung zugewiesen sind, durchlaufen werden,
	 * um pro Service-Typ eine Summe bilden zu können. Da, wie oben erwähnt, Bruttorechnungen keine Provision
	 * haben, muss hier zusätzlich dann auf die CNs ausgewichen werden, wo ebenso dann die fehlenden Beträge
	 * summiert werden. Wenn es (noch) keine CN gibt, gibt es keine CN. Provision und Netto sind dann 0.
	 *
	 * Jetzt kann man erst ausrechnen. wie viel Prozent brutto oder netto (abhängig vom Dokument) bei
	 * diesem Dokument bereits bezahlt wurden. Mit diesem Faktor kann man den bezahlten Wert der Werte,
	 * die niemals bezahlt werden, herzaubern.
	 *
	 * @param array $aRow
	 * @return array
	 * @throws \AssertionError
	 */
	protected function getDocumentPayedServiceTypes(array $aRow) {

		$oItemAmountService = new DocumentItemAmount();
		$cGetItemAmount = function(array $aItem, $sType) use ($oItemAmountService) {
			$oItemAmountService->sAmountType = $sType;
			return $oItemAmountService->calculate($aItem);
		};

		// Items auf Spalten aufteilen
		$cMapItemType = function($sType) {
			switch($sType) {
				case 'course':
					return 'course';
				case 'accommodation':
				case 'extra_nights':
				case 'extra_weeks':
					return 'accommodation';
				default:
					return 'other';
			}
		};

		$sDocumentType = 'gross';
		if(strpos($aRow['document_type'], 'netto') !== false) {
			$sDocumentType = 'net';
		}

		// Bezahlte Items pro Service-Typ (Kurs, Unterkunft, Rest) berechnen
		$aPayedItems = explode('{|:|}', $aRow['items_payed']);
		$aAmountsPayed = ['gross' => $this->getDocumentSumArray(), 'net' => $this->getDocumentSumArray(), 'commission' => $this->getDocumentSumArray()];
		foreach($aPayedItems as $sPayedItem) {
			$aPayedItem = explode('{|}', $sPayedItem);
			if(count($aPayedItem) !== 6) {
				throw new \AssertionError('items_payed has wrong item count'); // CONCAT_WS
			}
			list($iItemId, $sItemType, $fTaxPercent, $sAdditionalInfo, $iItemIdPayment, $fPayedAmount) = $aPayedItem;

			if($iItemId != $iItemIdPayment) {
				throw new \AssertionError('$iItemId != $iItemIdPayment');
			}

			// Egal, ob Steuern inklusive oder exklusive: Im Payment-Item ist die Steuer IMMER mit dabei
			if($fTaxPercent > 0) {
				$fPayedAmount -= $fPayedAmount - ($fPayedAmount / ($fTaxPercent / 100 + 1));
			}

			// Ewiger Sonderfall: Special
			if($sItemType === 'special') {
				$aAdditionalInfo = json_decode($sAdditionalInfo, true);
				if(empty($aAdditionalInfo['type'])) {
					// Ab Śpätsommer 2013 sollte der Typ eigentlich vorhanden sein
					throw new \RuntimeException('Special item has no type in additional_info!');
				}

				$sItemType = $aAdditionalInfo['type'];
			}

			$aAmountsPayed[$sDocumentType][$cMapItemType($sItemType)] += (float)$fPayedAmount;
		}

		// Dokument-Items pro Service-Typ (Kurs, Unterkunft, Rest berechnen
		$aDocumentIds = explode(',', $aRow['items_document_ids']);
		$aAmountsDocument = ['gross' => $this->getDocumentSumArray(), 'net' => $this->getDocumentSumArray(), 'commission' => $this->getDocumentSumArray()];
		foreach($aDocumentIds as $iDocumentId) {
			$aItems = $this->getItemsOfDocument($iDocumentId);
			foreach($aItems as $aItem) {

				$aAmountsDocument['gross'][$cMapItemType($aItem['item_type'])] += $cGetItemAmount($aItem, 'gross');

				// Nur bei Nettorechnung dürfen die Beiträge direkt ergänzt werden
				if($sDocumentType === 'net') {
					$aAmountsDocument['net'][$cMapItemType($aItem['item_type'])] += $cGetItemAmount($aItem, 'net');
					$aAmountsDocument['commission'][$cMapItemType($aItem['item_type'])] += $cGetItemAmount($aItem, 'commission');
				}

			}
		}

		// Bei Bruttorechnungen mit CNs: Nettobetrag und Provision ergänzen
		if(!empty($aRow['items_creditnote_ids'])) {
			if($sDocumentType === 'net') {
				throw new \AssertionError('Net invoice has creditnote?');
			}

			$aCreditnoteIds = explode(',', $aRow['items_creditnote_ids']);
			foreach($aCreditnoteIds as $iCreditnoteId) {
				$aItems = $this->getItemsOfDocument($iCreditnoteId);
				foreach($aItems as $aItem) {
					$aAmountsDocument['net'][$cMapItemType($aItem['item_type'])] += $cGetItemAmount($aItem, 'net');
					$aAmountsDocument['commission'][$cMapItemType($aItem['item_type'])] += $cGetItemAmount($aItem, 'commission');
				}
			}
		}

		// Bezahlte Provisions- und Nettobeträge herzaubern
		foreach(array_keys($this->getDocumentSumArray()) as $sServiceType) {

			$fFactor = 0; // Bei brutto 0 sind die anderen Werte auch 0
			$fDocumentAmount = $aAmountsDocument[$sDocumentType][$sServiceType];
			if(abs(round($fDocumentAmount, 5)) != 0) {
				$fFactor = $aAmountsPayed[$sDocumentType][$sServiceType] / $fDocumentAmount;
			}

			$aAmountsPayed['gross'][$sServiceType] = $aAmountsDocument['gross'][$sServiceType] * $fFactor;
			$aAmountsPayed['net'][$sServiceType] = $aAmountsDocument['net'][$sServiceType] * $fFactor;
			$aAmountsPayed['commission'][$sServiceType] = $aAmountsDocument['commission'][$sServiceType] * $fFactor;

		}

		return $aAmountsPayed;

	}

	/**
	 * Items pro Dokument, da die ganzen Spalten ansonsten über GROUP_CONCAT() abgerufen werden müssten
	 *
	 * @param int $iDocumentId
	 * @return array
	 */
	protected function getItemsOfDocument($iDocumentId) {

		$sSql = "
			SELECT
				`kid`.`type` `document_type`,
				`kidv`.`tax` `item_tax_type`,
				`kidvi`.`tax` `item_tax`,
				`kidvi`.`id` `item_id`,
				`kidvi`.`type` `item_type`,
				`kidvi`.`amount` `item_amount`,
				`kidvi`.`amount_net` `item_amount_net`,
				`kidvi`.`amount_discount` `item_amount_discount`,
				`kidvi`.`amount_provision` `item_amount_commission`,
				`kidvi`.`index_special_amount_gross` `item_index_special_amount_gross`,
				`kidvi`.`index_special_amount_net` `item_index_special_amount_net`,
				`kidvi`.`index_special_amount_gross_vat` `item_index_special_amount_gross_vat`,
				`kidvi`.`index_special_amount_net_vat` `item_index_special_amount_net_vat`,
				`kidvi`.`additional_info`
			FROM
				`kolumbus_inquiries_documents` `kid` INNER JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					/* Sollte keine Probleme machen, da die Payment Items bei neuer Version umgeschrieben werden */
					`kidv`.`id` = `kid`.`latest_version` INNER JOIN
				`kolumbus_inquiries_documents_versions_items` `kidvi` ON
					`kidvi`.`version_id` = `kidv`.`id`
			WHERE
				`kid`.`id` = :document_id
		";

		return (array)\DB::getQueryRows($sSql, ['document_id' => $iDocumentId]);

	}

	/**
	 * @inheritdoc
	 */
	public function generateDataTable() {

		$aColumns = $this->getColumns();
		$aData = $this->prepareData();

		$oTable = new Table\Table();
		$oTable[] = $this->generateHeaderRow();

		foreach($aData as $aAgencyData) {
			$oRow = new Table\Row();
			$oTable[] = $oRow;
			foreach($aColumns as $oColumn) {
				$oCell = $oColumn->createCell();
				$oCell->setValue($aAgencyData[$oColumn->getKey()]);
				$oCell->setCurrency($this->iCurrencyId);
				$oRow[] = $oCell;
			}
		}

		return $oTable;

	}

	/**
	 * @inheritdoc
	 */
	public function getFilters() {
		$aFilters = parent::getFilters();
		$aFilters['schools']->setAllDefaultValues();
		return $aFilters;
	}

	/**
	 * @inheritdoc
	 */
	public function getBasedOnOptionsForDateFilter() {
		return [
			'payment_date' => self::t('Zahlungszeitraum')
		];
	}

	/**
	 * @inheritdoc
	 */
	public function getInfoTextListItems() {
		return [
			self::t('Diese Statistik basiert auf den tatsächlichen Zahlungen und beachtet keine Überbezahlungen, welche keiner Rechnungsposition zugewiesen sind.'),
			self::t('Mit einem Stern markierte Agenturen weisen darauf hin, dass es bei dieser Agentur Schüler ohne Gutschriften gibt und diese die Berechnung von Provision und Nettobetrag verfälschen.')
		];
	}

}
