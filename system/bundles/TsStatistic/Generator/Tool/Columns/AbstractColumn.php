<?php

namespace TsStatistic\Generator\Tool\Columns;

use TcStatistic\Generator\Statistic\AbstractGenerator;
use TcStatistic\Model\Table\Cell;
use TsStatistic\Dto\FilterValues;
use TsStatistic\Generator\Tool\AbstractColumnOrGrouping;
use TsStatistic\Generator\Tool\Bases\BaseInterface;
use TsStatistic\Generator\Tool\Bases\BookingServicePeriod;
use TsStatistic\Generator\Tool\Groupings\AbstractGrouping;

abstract class AbstractColumn extends AbstractColumnOrGrouping {

	/**
	 * @var BaseInterface
	 */
	protected $base;

	/**
	 * @var null|AbstractGrouping
	 */
	protected $grouping;

	/**
	 * @var null|AbstractGrouping
	 */
	protected $headGrouping;

	/**
	 * @TODO Sollte refaktorisiert werden, wenn die Anforderungen besser bekannt sind
	 *
	 * @var null|string
	 */
	protected $sConfiguration;

	/**
	 * @var array
	 */
	protected $aTotalSum = [];

	/**
	 * @TODO Wofür genau wurde das benötigt?
	 *
	 * @var bool
	 */
	protected $bOverwriteGroupingColumn = false;

	/**
	 * @return string[]
	 */
	abstract public function getAvailableBases();

	/**
	 * @return string[]
	 */
	abstract public function getAvailableGroupings();

	/**
	 * SELECT-Part dieser Column
	 *
	 * @return string
	 */
	abstract public function getSelect();

	/**
	 * @TODO $grouping und $headGrouping entfernen und über Setter setzen
	 * @TODO $sConfiguration zum Array machen
	 *
	 * @param AbstractGrouping $grouping
	 * @param AbstractGrouping $headGrouping
	 * @param string $configuration
	 */
	public function __construct($grouping = null, $headGrouping = null, $configuration = null) {
		$this->grouping = $grouping;
		$this->headGrouping = $headGrouping;
		$this->sConfiguration = $configuration;
	}

	/**
	 * @param BaseInterface $base
	 */
	public function setBase(BaseInterface $base) {
		$this->base = $base;
	}

	/**
	 * @return AbstractGrouping
	 */
	public function getGrouping() {
		return $this->grouping;
	}

	/**
	 * @return bool
	 */
	public function hasGrouping() {
		return $this->grouping !== null;
	}

	/**
	 * @return AbstractGrouping
	 */
	public function getHeadGrouping() {
		return $this->headGrouping;
	}

	public function getGroupBy() {
		return [];
	}

	/**
	 * Query ausführen und Möglichkeit der Manipulation bieten
	 *
	 * @TODO $sqlData refaktorisieren (vor allem from_datetime/until_datetime)
	 *
	 * @param string $sql
	 * @param FilterValues $values
	 * @return array
	 */
	public function getResult(string $sql, FilterValues $values) {
		return (array)\DB::getQueryRows($sql, $values->toSqlData());
	}

	public function prepareResult(array $result, FilterValues $values) {
		return $result;
	}

	/**
	 * Query ausführen und Rohdaten bereits nach Gruppierungen gruppieren
	 *
	 * @param string $sSql
	 * @param FilterValues $aSql
	 * @return array
	 */
	final public function getGroupedResult($sSql, FilterValues $aSql) {

		$aGroupedResult = [];
		$aResult = $this->getResult($sSql, $aSql);
		$aResult = $this->prepareResult($aResult, $aSql);

		foreach($aResult as $aRow) {

			if($this->headGrouping !== null) {
				$this->headGrouping->setLabel($aRow['head_grouping_id'], $aRow['head_grouping_label']);
			}

			if($this->hasGrouping()) {
				$this->grouping->setLabel($aRow['grouping_id'], $aRow['grouping_label']);
				if($this->headGrouping !== null) {
					$this->grouping->setGroupingCohesion($aRow['head_grouping_id'], $aRow['grouping_id']);
				}
			}

			if(!empty($aRow['label'])) {
				$aItems = $aRow['label'];
				if (!is_array($aRow['label'])) {
					// Labels kommen direkt per GROUP_CONCAT aus dem Query
					$aItems = explode(',', rtrim($aRow['label'], ','));
				}
				$aItems = array_unique($aItems);
				sort($aItems);
				$aRow['label'] = join(', ', $aItems);
			}

			$aGroupedResult[$aRow['head_grouping_id']][$aRow['grouping_id']] = [$aRow['result'], $aRow['label']];

		}

		return $aGroupedResult;

	}

	public function getFormat() {
		return null;
	}

	public function isSummable() {
		return false;
	}

	// TODO Entfernen oder refaktorisieren
	public function getConfiguration() {
		return $this->sConfiguration;
	}

	public function getConfigurationOptions() {
		return [];
	}

	/**
	 * @inheritdoc
	 */
	public function createCell($bHeadCell = false, $sType = 'light') {

		$oCell = new Cell(null, $bHeadCell);

		if(!$bHeadCell) {
			$oCell->setFormat($this->getFormat());
		}

		if($bHeadCell) {
			if(
				!$this->bOverwriteGroupingColumn &&
				$this->grouping !== null &&
				$this->grouping->getColumnColor() !== null
			) {
				$oCell->setBackground(AbstractGenerator::getColumnColor($this->grouping->getColumnColor(), $sType));
			} elseif($this->getColumnColor() !== null) {
				$oCell->setBackground(AbstractGenerator::getColumnColor($this->getColumnColor(), $sType));
			}
		}

		return $oCell;

	}

	/**
	 * Summe bilden bei Spalten, die Daten zwecks Manipulation nicht direkt aggregiert holen
	 *
	 * @param array $aResult
	 * @return array
	 */
	protected function buildSum(array $aResult) {

		$aNewResult = [];

		foreach($aResult as $aRow) {

			$sKey = $aRow['grouping_id'].'_'.$aRow['head_grouping_id'];

			if(!isset($aNewResult[$sKey])) {
				$aNewResult[$sKey] = [
					'result' => 0,
					'label' => [],
					'grouping_id' => $aRow['grouping_id'],
					'grouping_label' => $aRow['grouping_label'],
					'head_grouping_id' => $aRow['head_grouping_id'],
					'head_grouping_label' => $aRow['head_grouping_label'],
				];
			}

			$aNewResult[$sKey]['result'] += $aRow['result'] ?? 0.0;

			if(
				!$this->base instanceof BookingServicePeriod ||
				// Bei Splittung nach Leistungszeitraum gibt es viele nicht relevante Werte mit 0
				abs(round($aRow['result'] ?? 0, 2)) > 0
			) {
				//$aNewResult[$sKey]['label'] .= $aRow['label'].',';
				$aNewResult[$sKey]['label'][] = $aRow['label'];
			}

		}

		return $aNewResult;

	}

	/**
	 * Spalte prozentual umrechnen
	 *
	 * @param array $aResult
	 * @return array
	 */
	protected function buildPercentSum(array $aResult) {

		// Wenn es keine Gruppierung gibt, machen prozentuale Werte auch keinen Sinn
		if($this->grouping === null) {
			return $aResult;
		}

		// Prozente richten sich bei Obergrupperierung nicht auf die ganze Zeile
		$cCreateKey = function(array $aRow) {
			return $aRow['head_grouping_id'];
		};

		$aSums = [];
		foreach($aResult as $aRow) {
			$sKey = $cCreateKey($aRow);

			if(!isset($aSums[$sKey])) {
				$aSums[$sKey] = 0;
			}

			$aSums[$sKey] += $aRow['result'];
		}

		foreach($aResult as &$axRow) {
			$sKey = $cCreateKey($axRow);
			$axRow['result'] = $axRow['result'] / $aSums[$sKey] * 100;
		}

		return $aResult;

	}

	/**
	 * @param string $sHeadGroupingId
	 * @param string $sGroupingId
	 * @param $fValue
	 */
	public function setTotalSumValue($sHeadGroupingId, $sGroupingId, $fValue) {
		if(!$this->isSummable()) {
			return;
		}

		$sKey = $sHeadGroupingId.'_'.$sGroupingId;
		if(!isset($this->aTotalSum[$sKey])) {
			$this->aTotalSum[$sKey] = 0;
		}

		$this->aTotalSum[$sKey] += $fValue;
	}

	/**
	 * @param string $sHeadGroupingId
	 * @param string $sGroupingId
	 * @return float
	 */
	public function getTotalSumValue($sHeadGroupingId, $sGroupingId) {
		return $this->aTotalSum[$sHeadGroupingId.'_'.$sGroupingId];
	}

	public function getSqlWherePart() {
		return "";
	}

}
