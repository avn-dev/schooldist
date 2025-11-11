<?php

namespace TsStatistic\Generator\Tool\Groupings;

use TcStatistic\Model\Table\Cell;
use TsStatistic\Dto\FilterValues;
use TsStatistic\Generator\Statistic\AbstractGenerator;
use TsStatistic\Generator\Tool\AbstractColumnOrGrouping;

abstract class AbstractGrouping extends AbstractColumnOrGrouping {

	/**
	 * Objekt-ID => Objekt-Label
	 *
	 * @var array
	 */
	protected $aLabels = [];

	/**
	 * Verknüpfungen zwischen Obergruppierung und Gruppierung (in der Gruppierung)
	 *
	 * Der Sinn hiervon ist, dass man weiß, welche Items der Gruppierung zum Item der Obergruppierung gehören.
	 * Es sollen nämlich nicht alle Spalten jeder Gruppierung jedes Mal angezeigt werden, sondern nur für die
	 * Items, wo auch Daten existieren (keine leeren Spalten).
	 *
	 * @var array
	 */
	protected $aGroupingCohesion = [];

	/**
	 * @var FilterValues
	 */
	protected $filterValues;

	/**
	 * DB-Feld für die ID eines Unterobjekts dieser Gruppierung
	 *
	 * @return string
	 */
	abstract public function getSelectFieldForId();

	/**
	 * DB-Feld für das Label eines Unterobjekts dieser Gruppierung
	 *
	 * Im Gegensatz zum alten Tool muss das nicht zwingend aus der Datenbank kommen,
	 * sondern kann über getLabels() noch manipuliert werden.
	 *
	 * @see getLabels()
	 * @return string
	 */
	abstract public function getSelectFieldForLabel();

	/**
	 * Gibt an, ob diese Gruppierung als Obergruppierung verwenden werden kann
	 *
	 * Theoretisch kann jede Gruppierung als Obergruppierung verwendet werden,
	 * allerdings müssen dafür ggf. die Spalten konfiguriert werden, damit
	 * auch die gewünschten Daten angezeigt werden (z.B. Revenue).
	 *
	 * @return bool
	 */
	public function isHeadGrouping() {
		return false;
	}

	/**
	 * Die Instanz dieser Gruppierung dient gleichzeitig als Sammelstelle aller
	 * gefundenen Objekte dieser Gruppierung (abhängig von der Spalte).
	 *
	 * @param string|int $sGroupingId ID oder anderer Key (z.B. ISO-Code bei Nationalitäten)
	 * @param string $sLabel
	 */
	public function setLabel($sGroupingId, $sLabel) {
		$this->aLabels[$sGroupingId] = $sLabel;
	}

	/**
	 * Methode kann abgeleitet werden um Labels noch mit PHP manipulieren zu können
	 *
	 * @return string[]
	 */
	public function getLabels() {
		asort($this->aLabels);
		return $this->aLabels;
	}

	// TODO Wenn das benötigt wird, muss das anders gelöst werden, da eine Gruppierung auch Obergruppierung sein kann
//	public function getGroupBy() {
//		// Im QueryBuilder definiert
//		return ["`grouping_id`"];
//	}

	/**
	 * Verknüpfung zwischen Obergruppierung und Gruppierung setzen
	 *
	 * @see aGroupingCohesion
	 * @param string $sHeadGroupingId
	 * @param string $sSubGroupingId
	 */
	public function setGroupingCohesion($sHeadGroupingId, $sSubGroupingId) {
		$this->aGroupingCohesion[$sHeadGroupingId][$sSubGroupingId] = true;
	}

	/**
	 * Labels filtern anhand Verknüpfung zwischen Obergruppierung und Gruppierung
	 *
	 * @see aGroupingCohesion
	 * @param string $sHeadGroupingId
	 * @return string[]
	 */
	public function getLabelsByGroupingCohesion($sHeadGroupingId) {
		return array_filter($this->getLabels(), function($sGroupingId) use ($sHeadGroupingId) {
			return isset($this->aGroupingCohesion[$sHeadGroupingId][$sGroupingId]);
		}, ARRAY_FILTER_USE_KEY);
	}

	/**
	 * @inheritdoc
	 */
	public function createCell($bHeadCell = false, $sType = 'light') {
		$oCell = new Cell(null, true);
		$oCell->setBackground(AbstractGenerator::getColumnColor($this->getColumnColor(), $sType));
		return $oCell;
	}

	public function setFilterValues(FilterValues $values) {
		$this->filterValues = $values;
	}

}
