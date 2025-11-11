<?php

namespace TsStatistic\Service\Tool;

use TsStatistic\Dto\FilterValues;
use TsStatistic\Generator\Statistic\AbstractGenerator;
use TsStatistic\Generator\Tool\Bases;
use TsStatistic\Generator\Tool\Columns\AbstractColumn;
use TsStatistic\Generator\Tool\Groupings\AbstractGrouping;
use TsStatistic\Model\Filter\Tool\FilterInterface;

class ColumnQueryBuilder {

	/** @var AbstractGenerator */
	private $generator;

	/**
	 * @var Bases\BaseInterface
	 */
	private $base;

	/** @var AbstractColumn */
	private $column;

	/** @var AbstractGrouping */
	private $grouping;

	/** @var AbstractGrouping */
	private $headGrouping;

	/** @var FilterValues */
	private $filterValues;

	/** @var array */
	private $aJoinPartValues = [
		'parts' => [],
		'additions' => []
	];

	/**
	 * Platzhalter für $aJoinParts, die zum Entfernen bekannt sein müssen, wenn diese nicht genutzt werden
	 *
	 * @var array
	 */
	private $aJoinPartsPlaceholders = [
		'JOIN_ACCOMMODATIONS',
		'JOIN_COURSES',
		'JOIN_JOURNEY_ACCOMMODATIONS',
		'JOIN_JOURNEY_COURSES',
		'JOIN_DOCUMENTS', // Zusätzliche Join-Bedingungen für die Dokument-Tabelle
		'JOIN_ITEMS', // Zusätzliche Join-Bedingungen für die Item-Tabelle
		'JOIN_ITEMS_JOINS' // Zusätzliche Joins unter der Item-Tabelle
	];

	/**
	 * @TODO Evtl. zu Objekten machen, die man dann in den JoinParts angibt und konfigurieren kann
	 *    - Jedenfalls sinnvoller, als das ableiten beider Methoden mit .= oder nicht .=, nicht zusammenhängende Parts usw. (Konflikte)
	 *    - Außerdem könnten Parts, die Abhänigkeiten haben, direkt in dieser Klasse konfigurierbar machen, wie auch unwichtigere Parts
	 *
	 * Wie im alten Tool: Teil-Blöcke, die von Spalten und Gruppierungen in den Query eingefügt werden können
	 *
	 * @var array
	 */
	private $aJoinParts = [
		'contact_number' => " LEFT JOIN
			`tc_contacts_numbers` `tc_cn` ON
				`tc_cn`.`contact_id` = `tc_c`.`id`
		",
		'accommodation' => " INNER JOIN
			`ts_inquiries_journeys_accommodations` `ts_ija` ON
				`ts_ija`.`journey_id` = `ts_ij`.`id` AND
				`ts_ija`.`active` = 1 AND
				`ts_ija`.`visible` = 1 
				{JOIN_JOURNEY_ACCOMMODATIONS} INNER JOIN
			`kolumbus_accommodations_categories` `kac` ON
				`kac`.`id` = `ts_ija`.`accommodation_id`
			{JOIN_ACCOMMODATIONS}
		",
		'course' => " INNER JOIN
			`ts_inquiries_journeys_courses` `ts_ijc` ON
				`ts_ijc`.`journey_id` = `ts_ij`.`id` AND
				`ts_ijc`.`active` = 1 AND
				`ts_ijc`.`visible` = 1 AND
				`ts_ijc`.`for_tuition` = 1
				{JOIN_JOURNEY_COURSES} INNER JOIN
			`kolumbus_tuition_courses` `ktc` ON
				`ktc`.`id` = `ts_ijc`.`course_id` INNER JOIN
			`ts_tuition_coursecategories` `ktcc` ON
				`ktcc`.`id` = `ktc`.`category_id`
			{JOIN_COURSES}
		",
		'document' => " INNER JOIN
			`kolumbus_inquiries_documents` `kid` ON
				`kid`.`entity` = '".\Ext_TS_Inquiry::class."' AND
				`kid`.`entity_id` = `ts_i`.`id` AND
				`kid`.`active` = 1 AND
				`kid`.`type` IN ( :document_types ) AND
				/* Credits immer ignorieren */
				`kid`.`is_credit` = 0 
				{JOIN_DOCUMENTS} LEFT JOIN
			`ts_documents_to_documents` `ts_dtd_creditnotes` ON
				`ts_dtd_creditnotes`.`parent_document_id` = `kid`.`id` AND
				`ts_dtd_creditnotes`.`type` = 'creditnote' LEFT JOIN
			/* Credit-Creditnotes haben weder is_credit = 1 noch werden die mit der Ursprungs-CN verknüpft */
			/* Scheinbar wird eine Creditnote aber immer mit credit und creditnote mit der Ursprungsrechnung verknüpft */
			`ts_documents_to_documents` `ts_dtd_creditnotes2` ON
				`kid`.`type` = 'creditnote' AND
				`ts_dtd_creditnotes2`.`child_document_id` = `kid`.`id` AND
				`ts_dtd_creditnotes2`.`type` = 'creditnote' LEFT JOIN
			`ts_documents_to_documents` `ts_dtd_credit` ON
				`ts_dtd_credit`.`parent_document_id` = IFNULL(`ts_dtd_creditnotes2`.`parent_document_id`, `kid`.`id`) AND
				`ts_dtd_credit`.`type` = 'credit' INNER JOIN
			`kolumbus_inquiries_documents_versions` `kidv` ON
				`kidv`.`id` = `kid`.`latest_version` AND
				`kidv`.`active` = 1 INNER JOIN
			`kolumbus_inquiries_documents_versions_items` `kidvi` ON
				`kidvi`.`version_id` = `kidv`.`id` AND
				`kidvi`.`active` = 1 AND
				`kidvi`.`onPdf` = 1 AND
				/* Rechnungen, die eine Agentur-Creditnote haben, immer ignorieren - hier wird die CN benutzt */
				`ts_dtd_creditnotes`.`child_document_id` IS NULL AND
				/* Rechnungen, die eine Credit haben, immer rauswerfen */
				`ts_dtd_credit`.`child_document_id` IS NULL
				{JOIN_ITEMS}
				{JOIN_ITEMS_JOINS}
		"
	];

	/**
	 * @param AbstractGenerator $generator
	 * @param Bases\BaseInterface $base
	 * @param AbstractColumn $column
	 * @param FilterValues $filterValues
	 */
	public function __construct(AbstractGenerator $generator, Bases\BaseInterface $base, AbstractColumn $column, FilterValues $filterValues) {

		$this->generator = $generator;
		$this->base = $base;
		$this->column = $column;
		$this->grouping = $column->getGrouping();
		$this->headGrouping = $column->getHeadGrouping();
		$this->filterValues = $filterValues;

		$this->column->setBase($base);

		if ($this->grouping !== null) {
			$this->grouping->setFilterValues($filterValues);
		}

	}

	/**
	 * Query-SQL für Column generieren
	 *
	 * @return string
	 */
	public function createQuery() {

		$select = sprintf("/* Column: %s (%s) */\n", get_class($this->column), str_replace(':', '=', $this->column->getConfiguration()));
		$select .= $this->column->getSelect();

		if($this->headGrouping !== null) {
			$select .= ", ".$this->headGrouping->getSelectFieldForId()." `head_grouping_id`";
			$select .= ", ".$this->headGrouping->getSelectFieldForLabel()." `head_grouping_label`";
		} else {
			$select .= ", NULL `head_grouping_id`, NULL `head_grouping_label`";
		}

		if($this->grouping !== null) {
			$select .= ", ".$this->grouping->getSelectFieldForId()." `grouping_id`";
			$select .= ", ".$this->grouping->getSelectFieldForLabel()." `grouping_label`";
		} else {
			$select .= ", NULL `grouping_id`, NULL `grouping_label`";
		}

		$where = $this->generateWhere(); // Muss wegen Filter-Ergänzungen vorher passieren
		$joins = $this->generateJoins();
		$groupBy = $this->generateGroupBy();

		return $this->base->getQuery($select, $joins, $where, $groupBy);

	}

	/**
	 * Joins für SQL generieren
	 *
	 * @return string
	 */
	private function generateJoins() {

		$sJoins = "";
		$aQueryParts = $this->aJoinPartValues['parts'];

		if($this->grouping !== null) {
			$aQueryParts = array_merge($aQueryParts, $this->grouping->getJoinParts());
		}

		$aQueryParts = array_merge($aQueryParts, $this->column->getJoinParts());

		$aQueryParts = array_unique($aQueryParts);

		foreach($aQueryParts as $sPart) {

			if(!isset($this->aJoinParts[$sPart])) {
				throw new \RuntimeException('Unknown query part: '.$sPart);
			}

			$sJoins .= $this->replaceJoinPartsPlaceholder($this->aJoinParts[$sPart]);

		}

		return $sJoins;

	}

	/**
	 * Angegebene Platzhalter ersetzen (Hook zum Hinzufügen von Join-Optionen)
	 *
	 * @param string $sJoinPart
	 * @return string
	 */
	private function replaceJoinPartsPlaceholder($sJoinPart) {

		$aAdditions = $this->aJoinPartValues['additions'];

		if($this->grouping !== null) {
			foreach($this->grouping->getJoinPartsAdditions() as $sPlaceholder => $sSqlAddition) {
				$aAdditions[$sPlaceholder] .= $sSqlAddition;
			}
		}

		foreach($this->column->getJoinPartsAdditions() as $sPlaceholder => $sSqlAddition) {
			$aAdditions[$sPlaceholder] .= $sSqlAddition;
		}

		if(!empty($aAdditions)) {
			foreach($aAdditions as $sPlaceholder => $sSqlAddition) {
				$sJoinPart = str_replace('{'.$sPlaceholder.'}', $sSqlAddition, $sJoinPart);
			}
		}

		// Verbliebene Platzhalter entfernen
		foreach($this->aJoinPartsPlaceholders as $sPlaceholder) {
			$sJoinPart = str_replace('{'.$sPlaceholder.'}', '', $sJoinPart);
		}

		return $sJoinPart;

	}

	/**
	 * @return string
	 */
	private function generateWhere() {

		$sWhere = "";

//		if($this->aSqlPlaceholders['split_by_service_period']) {
//			$sWhere .= " AND
//				`ts_i`.`service_from` <= :until AND
//				`ts_i`.`service_until` >= :from
//			";
//		} else {
//			$sWhere .= " AND `ts_i`.`created` BETWEEN :from AND :until ";
//		}

		// TODO Instanzen nur einmal erzeugen
		foreach($this->generator->getFilters() as $oFilter) {
			if($oFilter instanceof FilterInterface) {
				$this->aJoinPartValues['parts'] = array_merge($this->aJoinPartValues['parts'], $oFilter->getJoinParts());
				foreach($oFilter->getJoinPartsAdditions() as $sPlaceholder => $sSqlAddition) {
					$this->aJoinPartValues['additions'][$sPlaceholder] .= $sSqlAddition;
				}
				$sWhere .= $oFilter->getSqlWherePart();
			}
		}

		// TODO Entfernen und in Filter einbauen
		if($this->filterValues['invoice_type'] === 'invoice') {
			$sWhere .= " AND `ts_i`.`has_invoice` = 1 ";
		} elseif($this->filterValues['invoice_type'] === 'proforma') {
			$sWhere .= " AND (`ts_i`.`has_invoice` = 0 AND `ts_i`.`has_proforma` = 1) ";
		} elseif($this->filterValues['invoice_type'] === 'proforma_or_invoice') {
			$sWhere .= " AND (`ts_i`.`has_invoice` = 1 OR `ts_i`.`has_proforma` = 1) ";
		}

		// TODO Entfernen und in Filter einbauen
		if($this->filterValues['cancellation'] === 'no') {
			$sWhere .= " AND `ts_i`.`canceled` = 0 ";
		}

		$sWhere .= $this->column->getSqlWherePart();

		return $sWhere;

	}

	/**
	 * @return string
	 */
	private function generateGroupBy() {

		$aGroupBy = [];

		if($this->headGrouping !== null) {
			$aGroupBy[] = '`head_grouping_id`';
		}

		if($this->grouping !== null) {
			$aGroupBy[] = '`grouping_id`';
		}

		$aGroupBy = array_merge($aGroupBy, $this->column->getGroupBy());

		return join(', ', $aGroupBy);

	}

}
