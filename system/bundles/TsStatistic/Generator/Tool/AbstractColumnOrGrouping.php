<?php

namespace TsStatistic\Generator\Tool;

use TcStatistic\Controller\StatisticController;

abstract class AbstractColumnOrGrouping {

	/**
	 * Titel dieser Gruppierung
	 *
	 * @return string
	 */
	abstract public function getTitle();

	/**
	 * Zelle generieren für Renderer-Tabelle
	 *
	 * @param bool $bHeadCell
	 * @param string $sType
	 * @return mixed
	 */
	abstract public function createCell($bHeadCell = false, $sType = 'light');

	/**
	 * @see \TsStatistic\Service\Tool\ColumnQueryBuilder
	 *
	 * @return array
	 */
	public function getJoinParts() {
		return [];
	}

	/**
	 * @see \TsStatistic\Service\Tool\ColumnQueryBuilder
	 *
	 * @return array
	 */
	public function getJoinPartsAdditions() {
		return [];
	}

	/**
	 * Farbe für diese Spalte (im Header)
	 *
	 * Bei einer vorhandenen Gruppierung wird die Farbe durch die Gruppierung überschrieben
	 * außer das ist in der Column anders eingestellt ($this->bOverwriteGroupingColumn).
	 *
	 * @return string
	 */
	public function getColumnColor() {
		return 'general';
	}

	/**
	 * @param string $translation
	 * @return string
	 */
	public static function t($translation) {
		return \Factory::executeStatic('\\'.StatisticController::class, 't', [$translation]);
	}

}
