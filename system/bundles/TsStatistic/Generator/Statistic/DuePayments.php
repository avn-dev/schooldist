<?php

namespace TsStatistic\Generator\Statistic;

use \Elastica\Query;
use \ElasticaAdapter\Facade\Elastica;
use \TcStatistic\Exception\NoResultsException;
use \TcStatistic\Model\Statistic\Column;
use \TcStatistic\Model\Table;
use \TsStatistic\Model\Filter;

/**
 * CEL – Offene Zahlungen
 *
 * https://redmine.thebing.com/redmine/issues/7245
 *
 * Statistik ruft die zu anzeigenden Dokumente aus dem Index ab.
 *
 * Der Zeitraumfilter basiert auf den Datumsangaben der angegebenen Fälligkeiten.
 *
 * Die relativen Tagesdifferenzen werden vom aktuellen Tag aus berechnet,
 * sodass bpsw. eine Statistik aus der Zukunft leere Spalten bei den Fälligkeiten hat.
 */
class DuePayments extends AbstractGenerator {

	protected $aAvailableFilters = [
		Filter\Schools::class,
		Filter\InvoiceType::class,
		Filter\Currency::class
	];

	/**
	 * @var array
	 */
	protected $aCurrencies = [];

	/**
	 * @var array
	 */
	protected $aSums = [];

	/**
	 * @inheritdoc
	 */
	public function getTitle() {
		return self::t('Offene Zahlungen');
	}

	/**
	 * @inheritdoc
	 */
	protected function getColumns() {
		$aColumns = [];

		if(\System::d('debugmode') == 2) {
			$oColumn = new Column('debug', 'Debug');
			$oColumn->setBackground('general');
			$aColumns[] = $oColumn;
		}

		$oColumn = new Column('school_name', self::t('Schule'));
		$oColumn->setBackground('booking');
		$aColumns[] = $oColumn;

		$oColumn = new Column('document_number', self::t('Rechnungsnummer'));
		$oColumn->setBackground('booking');
		$aColumns[] = $oColumn;

		$oColumn = new Column('agency_name', self::t('Agentur'));
		$oColumn->setBackground('agency');
		$aColumns[] = $oColumn;

		$oColumn = new Column('addressee', self::t('Adressat'));
		$oColumn->setBackground('booking');
		$aColumns[] = $oColumn;

		$oColumn = new Column('customer_status', self::t('Schülerstatus'));
		$oColumn->setBackground('booking');
		$aColumns[] = $oColumn;

		$oColumn = new Column('document_type', self::t('Dokument-Typ'));
		$oColumn->setBackground('booking');
		$aColumns[] = $oColumn;

		$oColumn = new Column('customer_name', self::t('Name'));
		$oColumn->setBackground('booking');
		$aColumns[] = $oColumn;

		$oColumn = new Column('amount_total', self::t('Total erwarteter Betrag'), 'number_amount');
		$oColumn->setBackground('revenue');
		$aColumns[] = $oColumn;

		$oColumn = new Column('amount_due', self::t('Fälliger Betrag'), 'number_amount');
		$oColumn->setBackground('payment');
		$oColumn->bFormatNullValue = false;
		$aColumns[] = $oColumn;

		$oColumn = new Column('amount_open', self::t('Offener Betrag (Dokument)'), 'number_amount');
		$oColumn->setBackground('payment');
		$aColumns[] = $oColumn;

		$oColumn = new Column('amount_open_inquiry', self::t('Offener Betrag (Buchung)'), 'number_amount');
		$oColumn->setBackground('payment');
		$aColumns[] = $oColumn;

		$oColumn = new Column('due_type', self::t('Fälligkeitstyp'));
		$oColumn->setBackground('payment');
		$aColumns[] = $oColumn;

		$oColumn = new Column('due_date', self::t('Fälligkeitsdatum'), 'date');
		$oColumn->setBackground('payment');
		$aColumns[] = $oColumn;

		$oColumn = new Column('service_start', self::t('Globales Startdatum'), 'date');
		$oColumn->setBackground('service');
		$aColumns[] = $oColumn;

		$oColumn = new Column('document_comment', self::t('Rechnungsnotiz'));
		$oColumn->setBackground('booking');
		$aColumns[] = $oColumn;

		$oColumn = new Column('days_since_due', self::t('Anzahl der Tage seit Fälligkeit'), 'number_int');
		$oColumn->setBackground('payment');
		$oColumn->bFormatNullValue = false;
		$aColumns[] = $oColumn;

		$oColumn = new Column('days_until_service_start', self::t('Tage bis zum globalen Startdatum'), 'number_int');
		$oColumn->setBackground('service');
		$oColumn->bFormatNullValue = false;
		$aColumns[] = $oColumn;

		$oColumn = new Column('payment_reminder_date', self::t('Zahlungserinnerung - Datum'), 'date');
		$oColumn->setBackground('payment');
		$aColumns[] = $oColumn;

		$oColumn = new Column('payment_reminder_count', self::t('Zahlungserinnerung - Anzahl'), 'number_int');
		$oColumn->setBackground('payment');
		$oColumn->bFormatNullValue = false;
		$aColumns[] = $oColumn;

		$aHookData = [
			'type' => 'columns',
			'columns' => &$aColumns
		];

		\System::wd()->executeHook('ts_statistic_due_payments', $aHookData);

		return $aColumns;
	}

	/**
	 * Fällige Dokumente über Elasticsearch ermitteln, da das als MySQL sonst ein langsames HAVING wäre
	 *
	 * @return array
	 */
	protected function getDocuments() {

		$oSearch = new Elastica(Elastica::buildIndexName('ts_document'));

		// Rechnungstyp-Filter (für diese Statistik muss eine Proforma oder Rechnung vorhanden sein)
		if($this->aFilters['invoice_type'] !== 'all') {
			if($this->aFilters['invoice_type'] === 'invoice') {
				$sInvoiceType = 'invoice_without_proforma';
			} elseif($this->aFilters['invoice_type'] === 'proforma') {
				$sInvoiceType = 'proforma';
			} else {
				$sInvoiceType = 'invoice';
			}

			$oBool = new Query\BoolQuery();
			foreach(\Ext_Thebing_Inquiry_Document_Search::getTypeData($sInvoiceType) as $sType) {
				$oQuery = new Query\Term();
				$oQuery->setTerm('type_original', $sType);
				$oBool->addShould($oQuery);
			}
			$oBool->setMinimumShouldMatch(1);
			$oSearch->addQuery($oBool);
		}

		$oQuery = new Query\Term();
		$oQuery->setTerm('currency_id', $this->aFilters['currency']);
		$oSearch->addQuery($oQuery);

		$aCriteria = [
			'lte' => $this->aFilters['until']->format('Y-m-d'),
			'gte' => $this->aFilters['from']->format('Y-m-d')
		];
		$oDueQuery = \Ext_TS_Inquiry_Index_Gui2_Data::getPaymentDueQuery($aCriteria);
		$oSearch->addQuery($oDueQuery);

		$oBool = new Query\BoolQuery();
		foreach($this->aFilters['schools'] as $iSchoolId) {
			$oQuery = new Query\Term();
			$oQuery->setTerm('school_id', $iSchoolId);
			$oBool->addShould($oQuery);
		}
		$oBool->setMinimumShouldMatch(1);
		$oSearch->addQuery($oBool);

		$oSearch->setFields(['amount_total_original', 'amount_open_original']);
		$oSearch->setLimit(10000);
		$aResult = $oSearch->search();

		$aDocuments = [];
		foreach($aResult['hits'] as $aHit) {
			foreach($aHit['fields'] as &$mValue) {
				if(is_array($mValue)) {
					$mValue = reset($mValue);
				}
			}

			$aDocuments[$aHit['_id']] = $aHit['fields'];
		}

		return $aDocuments;

	}

	/**
	 * @param array $aDocumentIds
	 * @return array
	 */
	protected function getQueryData(array $aDocumentIds) {

		$sSql = "
			SELECT
				`ts_i`.`id` `inquiry_id`,
				`ts_i`.`currency_id`,
				`kid`.`id` `document_id`,
				`kid`.`type` `document_type`,
				`kid`.`document_number`,
				`kidv`.`comment` `document_comment`,
				`kidv`.`amount_prepay_due`,
				`kidv`.`amount_finalpay_due`,
				`kidv`.`amount_prepay`,
				`ts_idva`.`type` `address_type`,
				`ts_i`.`service_from` `inquiry_service_start`,
				`cdb2`.`ext_1` `school_name`,
				CONCAT(`tc_c`.`lastname`, ', ', `tc_c`.`firstname`) `customer_name`,
				`ka`.`id` `agency_id`,
				`ka`.`ext_2` `agency_name`,
				`kss`.`text` `customer_status`,
				(
					SELECT
						MIN(`kidvi`.`index_from`)
					FROM
						`kolumbus_inquiries_documents_versions_items` `kidvi` INNER JOIN
						`ts_inquiries_journeys_courses` `ts_ijc` ON
							`ts_ijc`.`id` = `kidvi`.`type_id`
					WHERE
						`kidvi`.`version_id` = `kidv`.`id` AND
						`kidvi`.`active` = 1 AND
						`kidvi`.`onPdf` = 1 AND
						`kidvi`.`type` = 'course'
				) `document_min_course_start_date`,
				(
					SELECT
						CONCAT(
							COUNT(*),
							'|',
							MAX(DATE(`kipr`.`date`))
						)
					FROM
						`kolumbus_inquiries_payments_reminders` `kipr`
					WHERE
						`kipr`.`inquiry_id` = `ts_i`.`id`
				) `payment_reminder`
			FROM
				`kolumbus_inquiries_documents` `kid` INNER JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidv`.`id` = `kid`.`latest_version` INNER JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `kid`.`inquiry_id` INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
					`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_ij`.`active` = 1 INNER JOIN
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` = `ts_ij`.`school_id` INNER JOIN
				`ts_inquiries_to_contacts` `ts_itc` ON
					`ts_itc`.`inquiry_id` = `ts_i`.`id` AND
					`ts_itc`.`type` = 'traveller' INNER JOIN
				`tc_contacts` `tc_c` ON
					`tc_c`.`id` = `ts_itc`.`contact_id` AND
					`tc_c`.`active` = 1 LEFT JOIN
				`ts_inquiries_documents_versions_addresses` `ts_idva` ON
					`ts_idva`.`version_id` = `kidv`.`id` LEFT JOIN
				`ts_companies` `ka` ON
					`ka`.`id` = `ts_i`.`agency_id` LEFT JOIN
				`kolumbus_student_status` `kss` ON
					`kss`.`id` = `ts_i`.`status_id` AND
					`kss`.`active` = 1
			WHERE
				`kid`.`id` IN (:document_ids)
			GROUP BY
				`kid`.`id`
			ORDER BY
				`document_number`
		";

		$aResult = (array)\DB::getQueryRows($sSql, ['document_ids' => $aDocumentIds]);

		if(count($aDocumentIds) !== count($aResult)) {
			throw new \RuntimeException('Mismatch of Elasticsearch result and query result!');
		}

		return $aResult;

	}

	/**
	 * Daten vorbereiten
	 *
	 * @return array
	 */
	protected function prepareData() {

		$aData = [];
		$aDocuments = $this->getDocuments();
		$aDocumentIds = array_keys($aDocuments);

		if(empty($aDocuments)) {
			throw new NoResultsException();
		}

		$aQueryData = $this->getQueryData($aDocumentIds);

		$oDocumentTypeFormat = new \Ext_TS_Document_Release_Gui2_Format_DocType();

		foreach($aQueryData as $aDocument) {

			$iDocumentId = $aDocument['document_id'];
			$oInquiry = \Ext_TS_Inquiry::getInstance($aDocument['inquiry_id']);

			$aDocument['debug'] = 'Inq '.$aDocument['inquiry_id'].', Doc '.$aDocument['document_id'];

			// Beträge werden aus dem Index genommen
			$aDocument['amount_total'] = $aDocuments[$iDocumentId]['amount_total_original'];
			$aDocument['amount_open'] = $aDocuments[$iDocumentId]['amount_open_original'];

			$aDocument['document_comment'] = htmlspecialchars($aDocument['document_comment']);
			$aDocument['document_type'] = $oDocumentTypeFormat->format($aDocument['document_type']);

			$aDocument['amount_open_inquiry'] = $oInquiry->getOpenPaymentAmount();

			if(!empty($aDocument['address_type'])) {
				$oDocumentAddress = new \Ext_Thebing_Document_Address($oInquiry);
				$aDocument['addressee'] = $oDocumentAddress->getTypeLabel($aDocument['address_type']);
			} else {
				$aDocument['addressee'] = '';
			}

			// Bei Dokumenten ohne Kurs-Position (z.B. Diff) ist globales Starttdatum der Start der Buchung
			if(!empty($aDocument['document_min_course_start_date'])) {
				$aDocument['service_start'] = $aDocument['document_min_course_start_date'];
			} else {
				$aDocument['service_start'] = $aDocument['inquiry_service_start'];
			}

			// Wenn alle Leistungen auf der Rechnung deaktiviert wurden, ist auch service_from leer
			if($aDocument['service_start'] !== '0000-00-00') {
				$dNow = new \DateTime();
				$oDiff = $dNow->diff(new \DateTime($aDocument['service_start']));
				$aDocument['days_until_service_start'] = $oDiff->format('%r%a');
				$aDocument['service_start'] = new \DateTime($aDocument['service_start']);
			} else {
				$aDocument['service_start'] = null;
			}

			if($aDocument['payment_reminder'] !== null) {
				list($iReminders, $sDate) = explode('|', $aDocument['payment_reminder'], 2);
				$aDocument['payment_reminder_count'] = $iReminders;
				$aDocument['payment_reminder_date'] = new \DateTime($sDate);
			}

			$this->setDueData($aDocument);

			$this->aSums['amount_total'] += $aDocument['amount_total'];
			$this->aSums['amount_open'] += $aDocument['amount_open'];
			$this->aSums['amount_open_inquiry'] += $aDocument['amount_open_inquiry'];
			$this->aCurrencies[$aDocument['currency_id']] = true;

			$aHookData = [
				'type' => 'data',
				'document' => &$aDocument
			];

			\System::wd()->executeHook('ts_statistic_due_payments', $aHookData);

			$aData[$iDocumentId] = $aDocument;
		}

		return $aData;

	}

	/**
	 * Spalten für Fälligkeiten ermitteln und setzen
	 *
	 * @param array $aDocument
	 */
	protected function setDueData(array &$aDocument) {

		$dNow = new \DateTime();
		$bPrepayDue = $bFinalpayDue = false;
		$dPrepayDue = $dFinalpayDue = null;
		$fFinalPayAmount = $aDocument['amount_total'] - $aDocument['amount_prepay'];

		if(
			$aDocument['amount_prepay_due'] !== '0000-00-00' &&
			abs($aDocument['amount_prepay']) > 0 &&
			$aDocument['amount_open'] > $aDocument['amount_prepay']
		) {
			$dPrepayDue = new \DateTime($aDocument['amount_prepay_due']);
			if($dNow > $dPrepayDue) {
				$bPrepayDue = true;
			}
		}

		if(
			abs($fFinalPayAmount) > 0 &&
			$aDocument['amount_open'] > 0
		) {
			$dFinalpayDue = new \DateTime($aDocument['amount_finalpay_due']);
			if($dNow > $dFinalpayDue) {
				$bFinalpayDue = true;
			}
		}

		if(
			$bPrepayDue &&
			$bFinalpayDue
		) {
			$aDocument['due_type'] = static::t('Alles');
			$dDueDate = $dFinalpayDue;
			$fDueAmount = $aDocument['amount_prepay'] + $fFinalPayAmount;
		} elseif($bPrepayDue) {
			$aDocument['due_type'] = static::t('Anzahlung');
			$dDueDate = $dPrepayDue;
			$fDueAmount = $aDocument['amount_prepay'];
		} elseif($bFinalpayDue) {
			$aDocument['due_type'] = static::t('Restzahlung');
			$dDueDate = $dFinalpayDue;
			$fDueAmount = $fFinalPayAmount;
		} else {
			// Liegt der Zeitraum der Statistik in der Zukunft, können hier keine Daten ermittelt werden
			return;
		}

		// Bereits bezahlten Betrag vom fälligen Betrag abziehen
		$fPayedAmount = $aDocument['amount_total'] - $aDocument['amount_open'];

		$aDocument['due_date'] = $dDueDate;
		$aDocument['days_since_due'] = $dNow->diff($dDueDate)->format('%a');
		$aDocument['amount_due'] = $fDueAmount - $fPayedAmount;

		$this->aSums['amount_due'] += $fDueAmount;

	}

	/**
	 * Summen-Zeile
	 *
	 * @param \TcStatistic\Model\Table\Table $oTable
	 */
	protected function setSumRow(Table\Table $oTable) {

		// Vorsorglich abgefangen, damit nicht mehrere Währungen summiert werden
		if(count($this->aCurrencies) > 1) {
			return;
		}

		$aColumns = $this->getColumns();
		$oRow = new Table\Row();
		$oRow->setRowSet('foot');

		foreach($aColumns as $oColumn) {
			$oCell = new Table\Cell(null, true);

			if(isset($this->aSums[$oColumn->getKey()])) {
				$oCell->setValue($this->aSums[$oColumn->getKey()]);
				$oCell->setFormat('number_amount');
				$oCell->setCurrency(key($this->aCurrencies));
			}

			$oRow[] = $oCell;
		}

		$oTable[] = $oRow;

	}

	/**
	 * @inheritdoc
	 */
	public function generateDataTable() {

		$oTable = new Table\Table();
		$oTable[] = $this->generateHeaderRow();
		$aColumns = $this->getColumns();

		$aPreparedData = $this->prepareData();
		foreach($aPreparedData as $iDocumentId => $aCellData) {
			$oRow = new Table\Row();
			foreach($aColumns as $oColumn) {
				$oCell = $oColumn->createCell();

				if(isset($aCellData[$oColumn->getKey()])) {
					$oCell->setValue($aCellData[$oColumn->getKey()]);
				}

				if($oColumn->getFormat() === 'number_amount') {
					$oCell->setCurrency($aCellData['currency_id']);
				}

				$oRow[] = $oCell;
			}

			$oTable[] = $oRow;
		}

		$this->setSumRow($oTable);

		return $oTable;

	}

	/**
	 * @inheritdoc
	 */
	public function getFilters() {
		$aFilters = parent::getFilters();
		$aFilters['invoice_type']->setDefaultValue('invoice');
		return $aFilters;
	}

	/**
	 * @inheritdoc
	 */
	public function getBasedOnOptionsForDateFilter() {
		return [
			'due_dates' => self::t('Fälligkeitsdaten'),
		];
	}

}
