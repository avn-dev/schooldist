<?php

namespace TsStatistic\Generator\Statistic\Payment;

use Core\DTO\DateRange;
use TcStatistic\Exception\NoResultsException;
use TsStatistic\Generator\Statistic\AbstractGenerator;
use TcStatistic\Model\Statistic\Column;
use TcStatistic\Model\Table;
use TsStatistic\Model\Filter;
use TsStatistic\Service\DocumentItemAmount;

/**
 * @TODO Teilweiser Merge mit DebtorReport
 *
 * https://redmine.fidelo.com/issues/14519
 */
class DeferredIncome extends AbstractGenerator {

	protected $aAvailableFilters = [
		Filter\Schools::class,
		Filter\Currency::class
	];

	private $aGroupColumns = [
		'invoice_number',
		'payment_method',
		'payment_date',
		'payment_sender',
		'payment_comment'
	];

	/**
	 * @inheritdoc
	 */
	public function getTitle() {
		return self::t('Aufgeschobene Zahlungen');
	}

	/**
	 * @inheritdoc
	 */
	protected function getColumns() {

		$aColumns = [];

		if(\System::d('debugmode') == 2) {
			$oColumn = new Column('inquiry_id', 'ID');
			$oColumn->setBackground('general');
			$aColumns[] = $oColumn;
		}

		$oColumn = new Column('customer_number', self::t('Kundennummer'));
		$oColumn->setBackground('booking');
		$aColumns[] = $oColumn;

		$oColumn = new Column('customer_name', self::t('Name'));
		$oColumn->setBackground('booking');
		$aColumns[] = $oColumn;

		$oColumn = new Column('agency_name', self::t('Agentur'));
		$oColumn->setBackground('booking');
		$aColumns[] = $oColumn;

		$oColumn = new Column('customer_status', self::t('Schülerstatus'));
		$oColumn->setBackground('booking');
		$aColumns[] = $oColumn;

		$oColumn = new Column('invoice_number', self::t('Rechnungsnummern'));
		$oColumn->setBackground('booking');
		$aColumns[] = $oColumn;

		$oColumn = new Column('service_start', self::t('Startdatum (Kurs)'), 'date');
		$oColumn->setBackground('service');
		$aColumns[] = $oColumn;

		$oColumn = new Column('service_end', self::t('Enddatum (Kurs)'), 'date');
		$oColumn->setBackground('service');
		$aColumns[] = $oColumn;

		$oColumn = new Column('amount_to_date', self::t('Rechnungsbetrag (bis Enddatum)'), 'number_amount');
		$oColumn->setBackground('revenue');
		$aColumns[] = $oColumn;

		$oColumn = new Column('amount_payed', self::t('Bezahlter Betrag (bis Enddatum)'), 'number_amount');
		$oColumn->setBackground('payment');
		$oColumn->bFormatNullValue = false;
		$aColumns[] = $oColumn;

		$oColumn = new Column('payment_method', self::t('Zahlungsmethode'));
		$oColumn->setBackground('payment');
		$aColumns[] = $oColumn;

		$oColumn = new Column('payment_date', self::t('Datum der Bezahlung'));
		$oColumn->setBackground('payment');
		$aColumns[] = $oColumn;

		$oColumn = new Column('payment_sender', self::t('Bezahlt von'));
		$oColumn->setBackground('payment');
		$aColumns[] = $oColumn;

		$oColumn = new Column('amount_deferred', self::t('Aufgeschobener Betrag'), 'number_amount');
		$oColumn->setBackground('payment');
		$aColumns[] = $oColumn;

		$oColumn = new Column('payment_comment', self::t('Kommentar'));
		$oColumn->setBackground('payment');
		$aColumns[] = $oColumn;

		return $aColumns;

	}

	/**
	 * @return array
	 */
	protected function getQueryData() {

		$sSelect = $this->getItemAmountService()->getFieldsSqlSelect();

		$sSql = "
			SELECT
			    `ts_i`.`id` `inquiry_id`,
				GROUP_CONCAT(DISTINCT CONCAT(`kipi`.`id`, ',', `kipi`.`amount_inquiry`) SEPARATOR ';') `item_payments`,
			    GROUP_CONCAT(DISTINCT `kid`.`document_number` ) `invoice_number_merged`,
			    GROUP_CONCAT(DISTINCT `kpm`.`name`) `payment_method_merged`,
			    GROUP_CONCAT(DISTINCT `kip`.`date`) `payment_date_merged`,
			    GROUP_CONCAT(DISTINCT `kip`.`sender`) `payment_sender_merged`,
			    GROUP_CONCAT(DISTINCT `kip`.`comment`) `payment_comment_merged`,
			    CONCAT(`tc_c`.`lastname`, ', ', `tc_c`.`firstname`) `customer_name`,
			    MIN(`ts_ijc`.`from`) `service_start`,
			    MAX(`ts_ijc`.`until`) `service_end`,
			    `tc_cn`.`number` `customer_number`,
			    `ka`.`ext_1` `agency_name`,
			    `kss`.`text` `customer_status`,
			   {$sSelect}
			FROM
				`ts_inquiries` `ts_i` INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
				    `ts_ij`.`inquiry_id` = `ts_i`.`id` AND
				    `ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
				    `ts_ij`.`active` = 1 AND
				    `ts_ij`.`school_id` IN (:schools) INNER JOIN
				`customer_db_2` `cdb2` ON
				    `cdb2`.`id` = `ts_ij`.`school_id` INNER JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`entity` = '".\Ext_TS_Inquiry::class."' AND
					`kid`.`entity_id` = `ts_i`.`id` AND
					`kid`.`active` = 1 AND
					`kid`.`type` IN (:document_types) INNER JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
				    `kidv`.`id` = `kid`.`latest_version` INNER JOIN
				`kolumbus_inquiries_documents_versions_items` `kidvi` ON
					`kidvi`.`version_id` = `kidv`.`id` AND
					`kidvi`.`active` = 1 AND
					`kidvi`.`onPdf` = 1 INNER JOIN (
					    `kolumbus_inquiries_payments_items` `kipi` INNER JOIN
					    `kolumbus_inquiries_payments` `kip`
					) ON
						`kipi`.`item_id` = `kidvi`.`id` AND
						`kipi`.`active` = 1 AND
						`kip`.`id` = `kipi`.`payment_id` AND
						`kip`.`active` = 1 AND
						`kip`.`date` <= :until LEFT JOIN
				`kolumbus_payment_method` `kpm` ON
				    `kpm`.`id` = `kip`.`method_id` LEFT JOIN
				`kolumbus_costs` `kc` ON
				    `kidvi`.`type` IN ('additional_general', 'additional_course', 'additional_accommodation') AND
				    `kc`.`id` = `kidvi`.`type_id` INNER JOIN
				`ts_inquiries_to_contacts` `ts_itc` ON
					`ts_itc`.`inquiry_id` = `ts_i`.`id` AND
					`ts_itc`.`type` = 'traveller' INNER JOIN
				`tc_contacts` `tc_c` ON
					`tc_c`.`id` = `ts_itc`.`contact_id` LEFT JOIN
				`tc_contacts_numbers` `tc_cn` ON
				    `tc_cn`.`contact_id` = `tc_c`.`id` LEFT JOIN
				`ts_inquiries_journeys_courses` `ts_ijc` ON
					`ts_ijc`.`journey_id` = `ts_ij`.`id` AND
					`ts_ijc`.`active` = 1 AND
					`ts_ijc`.`visible` = 1 LEFT JOIN
				`ts_companies` `ka` ON
					`ka`.`id` = `ts_i`.`agency_id` LEFT JOIN
				`kolumbus_student_status` `kss` ON
					`kss`.`id` = `ts_i`.`status_id`
			WHERE
				`ts_i`.`active` = 1 AND
			    `ts_i`.`has_invoice` = 1 AND
			    `ts_i`.`currency_id` = :currency_id AND
			    `ts_i`.`service_from` <= :until AND
			    `ts_i`.`service_until` >= :from
			GROUP BY
				`kidvi`.`id`
		";

		$aResult = (array)\DB::getQueryRows($sSql, [
			'from' => $this->aFilters['from']->format('Y-m-d'),
			'until' => $this->aFilters['until']->format('Y-m-d'),
			'currency_id' => $this->aFilters['currency'],
			'schools' => $this->aFilters['schools'],
			'document_types' => \Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice_without_proforma'),
		]);

		return $aResult;

	}

	/**
	 * @return array
	 */
	protected function prepareData() {

		$aQueryData = $this->getQueryData();
		$aGroupedData = [];

		if(empty($aQueryData)) {
			throw new NoResultsException();
		}

		foreach($aQueryData as $aItem) {

			$iInquiryId = $aItem['inquiry_id'];

			// Items gruppieren nach Buchung
			if(!isset($aGroupedData[$iInquiryId])) {
				$this->prepareInquiry($aItem);
				$aGroupedData[$iInquiryId] = $aItem;
			}

			$dItemFrom = new \DateTime($aItem['item_from']);
			$dItemUntil = new \DateTime($aItem['item_until']);

			$oItemAmountService = $this->getItemAmountService();

			// In #12092 wurde eine neue Logik eingebaut für die Splittung von Kursen
			// Diese Logik muss hier auch angewendet werden, sonst gibt es Lücken bei den taggenauen Kalkulationen!
			if($aItem['item_type'] === 'course') {
				$oItemAmountService->setCourseServicePeriod($aItem, $dItemFrom, $dItemUntil);
			}

			$oItemAmountService->oServicePeriodSplitDateRange = new DateRange($dItemFrom, $this->aFilters['until']);

			$fItemAmount = $oItemAmountService->calculate($aItem);

			$aGroupedData[$iInquiryId]['amount_to_date'] += $fItemAmount;

			$fAmountPayed = 0;
			$aPayments = explode(';', $aItem['item_payments']);
			foreach($aPayments as $sPayment) {
				list(, $sAmount) = explode(',', $sPayment);
				$fAmountPayed += (float)$sAmount;
			}

			// Steuer muss immer abgezogen werden, da diese immer mit bezahlt wird
			$fAmountPayed -= $fAmountPayed - ($fAmountPayed / ($aItem['item_tax'] / 100 + 1));
			$aGroupedData[$iInquiryId]['amount_payed'] += $fAmountPayed;

			$aGroupedData[$iInquiryId]['amount_deferred'] = $fAmountPayed - $fItemAmount;

			foreach($this->aGroupColumns as $sColumn) {
				$aGroupedData[$iInquiryId][$sColumn] = array_map(function($sValue) use($sColumn) {
					if($sColumn === 'payment_date') {
						return \Ext_Thebing_Format::LocalDate($sValue);
					} elseif($sColumn === 'payment_sender') {
						return \Ext_Thebing_Inquiry_Payment::getSenderOptions()[$sValue];
					}
					return $sValue;
				}, explode(',', $aItem[$sColumn.'_merged']));
			}

		}

		foreach($aGroupedData as $iInquiryId => $aData) {
			if(round($aData['amount_deferred'], 2) <= 0) {
				unset($aGroupedData[$iInquiryId]);
				continue;
			}

			foreach($this->aGroupColumns as $sColumn) {
				$aGroupedData[$iInquiryId][$sColumn] = join(', ', $aData[$sColumn]);
			}
		}

		if(empty($aGroupedData)) {
			throw new NoResultsException();
		}

		return $aGroupedData;

	}

	protected function prepareInquiry(array &$aItem) {

		if(
			!empty($aItem['service_start']) &&
			!empty($aItem['service_end'])
		) {
			$aItem['service_start'] = new \DateTime($aItem['service_start']);
			$aItem['service_end'] = new \DateTime($aItem['service_end']);
		}

		$aItem['amount_to_date'] = 0;
		$aItem['amount_payed'] = 0;
		$aItem['amount_deferred'] = 0;

		foreach($this->aGroupColumns as $sColumn) {
			$aItem[$sColumn] = [];
		}

	}

	/**
	 * @inheritdoc
	 */
	public function generateDataTable() {

		$aColumns = $this->getColumns();
		$aData = $this->prepareData();

		$oTable = new Table\Table();
		$oTable[] = $this->generateHeaderRow();

		foreach($aData as $aRow) {
			$oRow = new Table\Row();
			$oTable[] = $oRow;
			foreach($aColumns as $oColumn) {
				$oCell = $oColumn->createCell();
				$oCell->setValue($aRow[$oColumn->getKey()]);
				if($oColumn->getFormat() === 'number_amount') {
					$oCell->setCurrency($this->aFilters['currency']);
				}
				$oRow[] = $oCell;
			}
		}

		return $oTable;

	}

	/**
	 * @inheritdoc
	 */
	public function getBasedOnOptionsForDateFilter() {
		return [
			'service_period' => self::t('Leistungszeitraum')
		];
	}

	/**
	 * @inheritdoc
	 */
	public function getInfoTextListItems() {
		return [
			self::t('Der bezahlte Betrag bezieht sich auf das Enddatum und wird auf Basis der bezahlten Leistungen errechnet. Überbezahlungen und nicht korrekt zugewiesene Beträge werden ignoriert.')
		];
	}

	/**
	 * @return DocumentItemAmount
	 */
	private function getItemAmountService() {

		$oService = new DocumentItemAmount();
		$oService->bSplitByServicePeriod = true;
		return $oService;

	}

}
