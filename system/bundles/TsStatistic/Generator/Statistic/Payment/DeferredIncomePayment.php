<?php

namespace TsStatistic\Generator\Statistic\Payment;

use Core\Helper\DateTime;
use TcStatistic\Exception\NoResultsException;
use TsStatistic\Generator\Statistic\AbstractGenerator;
use TcStatistic\Model\Statistic\Column;
use TcStatistic\Model\Table;
use TsStatistic\Model\Filter;
use TsStatistic\Service\DocumentItemAmount;

/**
 * https://redmine.fidelo.com/issues/14519
 */
class DeferredIncomePayment extends AbstractGenerator {

	protected $aAvailableFilters = [
		Filter\Schools::class,
		Filter\Currency::class
	];

	public function __construct() {

		$this->aAvailableFilters[] = new class extends \TcStatistic\Model\Filter\AbstractFilter {
			public function getKey() {
				return 'include_zero_amount';
			}
			public function getTitle() {
				return self::t('Zahlungen mit einem Betrag von 0 anzeigen');
			}
			public function getInputType() {
				return 'checkbox';
			}
		};

		$this->aAvailableFilters[] = new class extends \TcStatistic\Model\Filter\AbstractFilter {
			public function getKey() {
				return 'deferred_higher_than_paid';
			}
			public function getTitle() {
				return 'Test: Deferred income > Paid amount';
			}
			public function getInputType() {
				return 'checkbox';
			}
		};

	}

	/**
	 * @inheritdoc
	 */
	public function getTitle() {
		return self::t('Aufgeschobene Zahlungen (Zahlungen)');
	}

	/**
	 * @inheritdoc
	 */
	protected function getColumns() {

		$aColumns = [];

		if(\System::d('debugmode') == 2) {
			$oColumn = new Column('id', 'Inq-ID / Pay-ID');
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

		$oColumn = new Column('service_start', self::t('Startdatum'), 'date');
		$oColumn->setBackground('payment');
		$aColumns[] = $oColumn;

		$oColumn = new Column('service_end', self::t('Enddatum'), 'date');
		$oColumn->setBackground('payment');
		$aColumns[] = $oColumn;

		$oColumn = new Column('invoice_numbers', self::t('Rechnungsnummern'));
		$oColumn->setBackground('payment');
		$aColumns[] = $oColumn;

		$oColumn = new Column('invoice_amount', self::t('Rechnungsbetrag'), 'number_amount');
		$oColumn->setBackground('payment');
		$aColumns[] = $oColumn;

		$oColumn = new Column('payment_amount', self::t('Bezahlter Betrag'), 'number_amount');
		$oColumn->setBackground('payment');
		$aColumns[] = $oColumn;

		$oColumn = new Column('payment_method', self::t('Zahlungsmethode'));
		$oColumn->setBackground('payment');
		$aColumns[] = $oColumn;

		$oColumn = new Column('payment_date', self::t('Datum der Bezahlung'), 'date');
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

		// Basierend auf Zahlungen, da man ansonsten keinen Zeitraumfilter verwenden könnte
		$sSql = "
			SELECT
				`ts_i`.`id` `inquiry_id`,
			    `kid`.`document_number`,
			    `kidvi`.`index_from` `item_from`,
			    `kidvi`.`index_until` `item_until`,
			    CONCAT(`tc_c`.`lastname`, ', ', `tc_c`.`firstname`) `customer_name`,
			    `tc_cn`.`number` `customer_number`,
			    `ka`.`ext_1` `agency_name`,
			    `kss`.`text` `customer_status`,
			    GROUP_CONCAT(
			        DISTINCT CONCAT(
			            `kidvp`.`id`,
			            ',',
			            `kidvp`.`amount_gross` - `kidvp`.`amount_discount_gross`
					) SEPARATOR ';') `document_amount`,
				GROUP_CONCAT(
				    DISTINCT CONCAT_WS(
				        '|',
				        `kip2`.`id`,
				        `kip2`.`date`,
				        `kip2`.`amount_inquiry`,
				        `kipi2`.`amount_inquiry`,
				        `kip2`.`sender`,
				        IFNULL(`kpm`.`name`, ''),
				        `kip2`.`comment`
					) ORDER BY `kip2`.`date`, `kip2`.`id` SEPARATOR '{|}'
				) `item_payments`,
			    {$sSelect}
			FROM
				`kolumbus_inquiries_payments` `kip` INNER JOIN
				`kolumbus_inquiries_payments_items` `kipi` ON
					`kipi`.`payment_id` = `kip`.`id` AND
					`kipi`.`active` = 1 INNER JOIN
				`kolumbus_inquiries_documents_versions_items` `kidvi` ON
					`kidvi`.`id` = `kipi`.`item_id` AND
					`kidvi`.`active` = 1 LEFT JOIN
				`kolumbus_costs` `kc` ON
				    `kidvi`.`type` IN ('additional_general', 'additional_course', 'additional_accommodation') AND
				    `kc`.`id` = `kidvi`.`type_id` INNER JOIN 
				`kolumbus_inquiries_payments_items` `kipi2` ON
					`kipi2`.`item_id` = `kidvi`.`id` AND
					`kipi2`.`active` = 1 INNER JOIN
				`kolumbus_inquiries_payments` `kip2` ON
					`kip2`.`id` = `kipi2`.`payment_id` AND
					`kip2`.`active` = 1 /*AND
					 `kip2`.`date` <= :until*/ LEFT JOIN
				`kolumbus_payment_method` `kpm` ON
					`kpm`.`id` = `kip2`.`method_id` INNER JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidv`.`id` = `kidvi`.`version_id` AND
					`kidv`.`active` = 1 INNER JOIN
				`kolumbus_inquiries_documents_versions_priceindex` `kidvp` ON
				    `kidvp`.`version_id` = `kidv`.`id` AND
				    `kidvp`.`active` = 1 INNER JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`id` = `kidv`.`document_id` AND
					`kid`.`entity` = '".\Ext_TS_Inquiry::class."' AND
					`kid`.`active` = 1 INNER JOIN
				`ts_inquiries` `ts_i` ON
				    `ts_i`.`id` = `kid`.`entity_id` AND
				    `ts_i`.`currency_id` = :currency_id AND
					`ts_i`.`active` = 1 AND 
				    `ts_i`.`confirmed` > 0 INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
				    `ts_ij`.`inquiry_id` = `ts_i`.`id` AND
				    `ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
				    `ts_ij`.`active` = 1 AND
				    `ts_ij`.`school_id` IN (:schools) INNER JOIN
				`customer_db_2` `cdb2` ON
				    `cdb2`.`id` = `ts_ij`.`school_id` INNER JOIN
				`ts_inquiries_to_contacts` `ts_itc` ON
					`ts_itc`.`inquiry_id` = `ts_i`.`id` AND
					`ts_itc`.`type` = 'traveller' INNER JOIN
				`tc_contacts` `tc_c` ON
					`tc_c`.`id` = `ts_itc`.`contact_id` LEFT JOIN
				`tc_contacts_numbers` `tc_cn` ON
				    `tc_cn`.`contact_id` = `tc_c`.`id` LEFT JOIN
				`ts_companies` `ka` ON
					`ka`.`id` = `ts_i`.`agency_id` LEFT JOIN
				`kolumbus_student_status` `kss` ON
					`kss`.`id` = `ts_i`.`status_id`
			WHERE
				`kip`.`active` = 1 AND
				`kip`.`type_id` IN (1, 2) AND
				`kip`.`date` BETWEEN :from AND :until
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

		// Da hier Tage verglichen werden, darf de Uhrzeit nicht beachtet werden
		$dFilterUntil = (clone $this->aFilters['until'])->setTime(0, 0, 0);

		// $aItem = kidvi
		foreach($aQueryData as $aItem) {

			$oItemAmountService = $this->getItemAmountService();

			$dItemFrom = new DateTime($aItem['item_from']);
			$dItemUntil = new DateTime($aItem['item_until']);

			$dPaymentFrom = clone $dItemFrom;
			$iDays = $dItemUntil->diff($dItemFrom)->days + 1;

			$fItemAmount = $oItemAmountService->calculate($aItem);

			$fTotalPayableDays = 0;

			$fTotalItemPaymentAmount = 0;
			$aPayments = explode('{|}', $aItem['item_payments']);

			foreach($aPayments as $iKey => $sPayment) {

				$aPayment = $this->splitPaymentData($sPayment);

				// Refunds (negative Beträge) und reine Überbezahlungen ignorieren
				if(
					$aPayment['item_amount'] < 0 ||
					$fTotalItemPaymentAmount > $fItemAmount
				) {
					unset($aPayments[$iKey]);
					continue;
				}

				$fTotalItemPaymentAmount += $aPayment['item_amount'];

			}

			// Die Tage, die diese Zahlung abdeckt, müssten sich bei Überbezahlung nach eben diesem Betrag richten
			if($fTotalItemPaymentAmount > $fItemAmount) {
				$fAmountPerDay = $fTotalItemPaymentAmount / $iDays;
			} else {
				$fAmountPerDay = $fItemAmount / $iDays;
			}

			$fCurrentTotalItemPaymentAmount = 0;
			foreach($aPayments as $iKey => $sPayment) {

				$aPayment = $this->splitPaymentData($sPayment);
				$iPaymentId = $aPayment['id'];
				$fPaymentItemAmount = $aPayment['item_amount'];

				$fCurrentTotalItemPaymentAmount += $fPaymentItemAmount;

				// Der Rest muss verteilt werden, wenn durch diese Zahlung das Item komplett bezahlt ist (in der Regel letzte Zahlung)
				if($fItemAmount <= $fCurrentTotalItemPaymentAmount) {

					// Bei letzter Zahlung alle Resttage
					$fPayableDays = $iDays - $fTotalPayableDays;
					$dPaymentUntil = $dItemUntil;

					// Bei Überbezahlungen kann nicht mehr zugewiesen werden als werden kann
					if($fPayableDays < 0) {
						$fPayableDays = 0;
					}

				} else {
					$fPayableDays = abs(round($fPaymentItemAmount / $fAmountPerDay));

					// Wenn der Betrag so gering ist, dass nicht mal ein Tag bezahlt werden kann
					if(bccomp($fPayableDays, 0) === 0) {
						$fPayableDays = 1;
					}

					$fTotalPayableDays += $fPayableDays;

					$dPaymentUntil = (clone $dPaymentFrom)->add(new \DateInterval('P'.($fPayableDays - 1).'D'));
				}

				// Wenn Leistung noch nicht begonnen hat, darf auch nichts berechnet werden
				if($dItemFrom > $this->aFilters['until']) {
					$iIntersectionNights = 0;
				} else {
					$iIntersectionNights = DateTime::getDaysInPeriodIntersection($dItemFrom, $this->aFilters['until'], $dPaymentFrom, $dPaymentUntil);

					// Der letzte Tag wird nie mit eingerechnet, da getDaysInPeriodIntersection nur Nächte zählt
					// Damit bei einem vollständigen Zeitraum der letzte Tag gezählt wird, muss in diesem Fall der Wert manuell erhöht werden
					if($dFilterUntil > $dPaymentUntil) {
						$iIntersectionNights++;
					}
				}

				$dPaymentFrom = (clone $dPaymentUntil)->add(new \DateInterval('P1D'));

				// Da der Filter nach <= until aller Zahlungen geht, würden auch alle vergangenen Zahlungen der Buchung angezeigt werden
				if(!(new DateTime($aPayment['date']))->isBetween($this->aFilters['from'], $dFilterUntil)) {
					continue;
				}

				// Pro Zahlung eine Zeile
				if(!isset($aGroupedData[$iPaymentId])) {
					$this->preparePaymentRow($aItem, $aPayment);
					$aGroupedData[$iPaymentId] = $aItem;
				}

				if($iIntersectionNights == 0) {
					// Wenn keine Tage schneiden
					$aGroupedData[$iPaymentId]['amount_deferred'] += $fPaymentItemAmount;
				} elseif(bccomp($fPayableDays, 0) === 0) {
					// Bei Überbezahlungen oder die Zahlung keinen Tag bezahlen kann
					$aGroupedData[$iPaymentId]['amount_deferred'] += 0;
				} else {
					// Anteiligen Betrag basierend auf Filter-Endzeit berechnen
					$aGroupedData[$iPaymentId]['amount_deferred'] += $fPaymentItemAmount - ($fPaymentItemAmount / $fPayableDays * $iIntersectionNights);
				}

				$aGroupedData[$iPaymentId]['service_start'] = min($dItemFrom, $aGroupedData[$iPaymentId]['service_start']);
				$aGroupedData[$iPaymentId]['service_end'] = max($dItemUntil, $aGroupedData[$iPaymentId]['service_end']);
				$aGroupedData[$iPaymentId]['invoice_numbers'][] = $aItem['document_number'];
				$aGroupedData[$iPaymentId]['invoice_amounts'][] = $aItem['document_amount'];

			}

		}

		foreach($aGroupedData as $iPaymentId => $aData) {

			// Zahlungen mit einem Betrag von 0 entfernen
			if(
				empty($this->aFilters['include_zero_amount']) &&
				bccomp($aGroupedData[$iPaymentId]['amount_deferred'], 0) <= 0
			) {
				unset($aGroupedData[$iPaymentId]);
				continue;
			}

			if(
				!empty($this->aFilters['deferred_higher_than_paid']) &&
				$aGroupedData[$iPaymentId]['amount_deferred'] <= $aGroupedData[$iPaymentId]['payment_amount']
			) {
				unset($aGroupedData[$iPaymentId]);
				continue;
			}

			$aGroupedData[$iPaymentId]['invoice_numbers'] = join(', ', array_unique($aData['invoice_numbers']));

			// Rechnungsbeträge summieren
			$aInvoiceAmounts = [];
			foreach($aData['invoice_amounts'] as $sAmounts) {
				$aAmounts = explode(';', $sAmounts);
				foreach($aAmounts as $sAmount) {
					list($iPriceIndexId, $fAmount) = explode(',', $sAmount);
					$aInvoiceAmounts[$iPriceIndexId] = $fAmount;
				}

			}
			$aGroupedData[$iPaymentId]['invoice_amount'] = array_sum($aInvoiceAmounts);
		}

		if(empty($aGroupedData)) {
			throw new NoResultsException();
		}

		return $aGroupedData;

	}

	private function splitPaymentData($sData) {

		$aData = explode('|', $sData, 7);

		return [
			'id' => $aData[0],
			'date' => $aData[1],
			'amount' => $aData[2],
			'item_amount' => $aData[3],
			'sender' => \Ext_Thebing_Inquiry_Payment::getSenderOptions()[$aData[4]],
			'method' => $aData[5],
			'comment' => $aData[6]
		];

	}

	private function preparePaymentRow(&$aItem, $aPaymentData) {

		$aItem['id'] = $aItem['inquiry_id'] . ' / ' . $aPaymentData['id'];
		$aItem['service_start'] = new DateTime($aItem['service_from']);
		$aItem['payment_date'] = new DateTime($aPaymentData['date']);
		$aItem['payment_amount'] = $aPaymentData['amount'];
		$aItem['payment_sender'] = $aPaymentData['sender'];
		$aItem['payment_method'] = $aPaymentData['method'];
		$aItem['payment_comment'] = $aPaymentData['comment'];
		$aItem['invoice_numbers'] = [];
		$aItem['invoice_amounts'] = [];
		$aItem['invoice_amount'] = 0;
		$aItem['service_start'] = new DateTime($aItem['item_from']);
		$aItem['service_end'] = new DateTime($aItem['item_until']);

		$aAmounts = explode(';', $aItem['document_amount']);
		foreach($aAmounts as $sAmount) {
			// ID muss wegen DISTINCT inkludiert werden
			list(, $fAmount) = explode(',', $sAmount);
			$aItem['invoice_amount'] += $fAmount;
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
			'payment_date' => self::t('Zahlungsdatum')
		];
	}

	/**
	 * @inheritdoc
	 */
	public function getInfoTextListItems() {
		return [
			self::t('Pro Zeile wird eine Zahlung angezeigt. Reine Überbezahlungen, negative Beträge und nicht korrekt zugewiesene Beträge werden ignoriert. Auf der Rechnung ausgewiesene Steuern werden immer einkalkuliert. Die Beträge sind immer brutto.'),
			self::t('Startdatum und Enddatum beziehen sich auf die bezahlten Leistungen innerhalb dieser Zahlung.')
		];
	}

	/**
	 * @return DocumentItemAmount
	 */
	private function getItemAmountService() {

		$oService = new DocumentItemAmount();
		$oService->sAmountType = 'gross';
		$oService->iTaxMode = 1;
		return $oService;

	}

}
