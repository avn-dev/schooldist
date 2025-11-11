<?php

namespace TcStatistic\Generator\Statistic;

use Carbon\CarbonPeriod;
use \Core\Helper\DateTime;
use \TcStatistic\Generator\Table\AbstractTable;
use \TcStatistic\Model\Table;
use \TcStatistic\Exception\InvalidDateException;

abstract class AbstractGenerator {

	const RENDERER_HTML = '\TcStatistic\Generator\Table\Html';
	const RENDERER_EXPORT = '\TcStatistic\Generator\Table\Excel';

	/**
	 * @TODO Umstellen auf Filter-Models
	 *
	 * Filter-Daten
	 *
	 * @var \DateTime[]|mixed[]
	 */
	protected $aFilters = [];

	/**
	 * Verfügbare Filter in der Statistik
	 *
	 * @var array
	 */
	protected $aAvailableFilters = [];

	/**
	 * Titel der Statistik (für die Anzeige)
	 *
	 * @return string
	 */
	abstract public function getTitle();

	/**
	 * Daten-Array für Renderer
	 *
	 * @return \TcStatistic\Model\Table\Table|\TcStatistic\Model\Table\Table[]
	 */
	abstract protected function generateDataTable();

	/**
	 * @return \TcStatistic\Model\Filter\AbstractFilter[]
	 */
	public function getFilters() {

		$aFilters = [];
		foreach($this->aAvailableFilters as $mFilter) {
			if($mFilter instanceof \TcStatistic\Model\Filter\AbstractFilter) {
				$oFilter = $mFilter;
			} else {
				$mFilter = '\\'.$mFilter;
				/** @var \TcStatistic\Model\Filter\AbstractFilter $oFilter */
				$oFilter = new $mFilter();
			}
			$aFilters[$oFilter->getKey()] = $oFilter;
		}

		return $aFilters;

	}

	/**
	 * Filter parsen und setzen
	 *
	 * @param \MVC_Request $oRequest
	 * @throws InvalidDateException
	 */
	public function setFilterValues(\MVC_Request $oRequest) {

		/** @var \Ext_Gui2_View_Format_Date $oDateFormat */
		$oDateFormat = \Factory::getObject('Ext_TC_Gui2_Format_Date');

		$mDates = DateTime::createDatesFromTimefilterInput($oRequest->input('filter_date_from'), $oRequest->input('filter_date_until'), $oDateFormat);
		if(is_string($mDates)) {
			throw new InvalidDateException();
		}

		if($oRequest->exists('filter_date_based_on')) {
			$this->aFilters['based_on'] = $oRequest->input('filter_date_based_on');
		}

		$mDates->until->setTime(23, 59, 59);

		$this->aFilters['from'] = $mDates->from;
		$this->aFilters['until'] = $mDates->until;

		$aFilters = $this->getFilters();
		foreach($aFilters as $oFilter) {
			$this->aFilters[$oFilter->getKey()] = $oFilter->getRequestValue($oRequest);
		}

	}

	/**
	 * HTML-Generator erstellen
	 *
	 * @see generateViewGenerator()
	 * @return \TcStatistic\Generator\Table\Html
	 */
	final public function createHtmlGenerator() {
		$aTable = $this->generateDataTable();
		$sClass = static::RENDERER_HTML; /** @var \TcStatistic\Generator\Table\Html $oGenerator */
		$oGenerator = new $sClass($aTable);
		return $oGenerator;
	}

	/**
	 * Excel-Generator erstellen
	 *
	 * @see generateViewGenerator()
	 * @return \TcStatistic\Generator\Table\Excel
	 */
	final public function createExcelGenerator() {
		$aTable = $this->generateDataTable();
		$sClass = static::RENDERER_EXPORT;
		$oGenerator = new $sClass($aTable); /** @var \TcStatistic\Generator\Table\Excel $oGenerator */
		$oGenerator->setTitle($this->getTitle());
		$oGenerator->setFileName($this->getTitle());
		return $oGenerator;
	}

	/**
	 * Generator generieren
	 *
	 * Kann abgeleitet werden um z.B. PHPExcel-Objekt zu manipulieren oder komplett eigenes Excel zu erstellen
	 *
	 * @param \TcStatistic\Generator\Table\AbstractTable $oGenerator
	 * @return mixed
	 */
	public function generateViewGenerator(AbstractTable $oGenerator) {
		return $oGenerator->generate();
	}

	/**
	 * Farben für die Spalten
	 *
	 * Dunklere Farben sind etwa 21% dunkler als die hellen Farben.
	 * Formel: sqrt((R*R*.241)+(G*G*.691)+(B*B*.068))
	 * Tool: http://www.cssfontstack.com/oldsites/hexcolortool/ (Regler mit DevTools verändern)
	 *
	 * @return array
	 */
	public static function getColumnColors() {

		return [
			'booking' => [
				'color_dark' => '#81F781',
				'color_light' => '#CCFFAA'
			],
			'enquiry' => [
				'color_dark' => '#C97474',
				'color_light' => '#FFAAAA'
			],
			'agency' => [
				'color_dark' => '#F7BE81',
				'color_light' => '#FFCCAA'
			],
			'service' => [
				'color_dark' => '#81F7F3',
				'color_light' => '#CCEEFF'
			],
			'revenue' => [
				'color_dark' => '#F5A9F2',
				'color_light' => '#FFDDEE'
			],
			'payment' => [
				'color_light' => '#DDAAFF',
				'color_dark' => '#A774C9'
			],
			'margin' => [
				'color_dark' => '#66CCCC',
				'color_light' => '#66FFCC'
			],
			'general' => [
				'color_dark' => '#DEDEDE',
				'color_light' => '#EFEFEF'
			]
		];

	}

	/**
	 * Farbe aus Array ermitteln
	 *
	 * @param $sColor
	 * @param string $sType
	 * @return string
	 */
	public static function getColumnColor($sColor, $sType='light') {

		$sType = 'color_'.$sType;
		$aColors = static::getColumnColors();

		if(!isset($aColors[$sColor][$sType])) {
			throw new \InvalidArgumentException('Color doesn\'t exist: '.$sColor.'_'.$sType);
		}

		return $aColors[$sColor][$sType];

	}

	/**
	 * Spalten, welche diese Statistik besitzt
	 *
	 * @return \TcStatistic\Model\Statistic\Column[]|\TcStatistic\Model\Statistic\Column[][]
	 */
	protected function getColumns() {
		return [];
	}

	/**
	 * Liefert Listenpunkte für die blaue Infobox unter der Statistik
	 *
	 * @return array
	 */
	public function getInfoTextListItems() {
		return [];
	}

	/**
	 * Optionen für basierend auf Filter
	 *
	 * @return array
	 */
	public function getBasedOnOptionsForDateFilter() {
		return [];
	}

	public function createDateFilterPeriod(): ?CarbonPeriod {
		return null;
	}

	/**
	 * Zusatzfilter der Statistik werden sofort angezeigt
	 *
	 * @return bool
	 */
	public function isShowingFiltersInitially() {
		return false;
	}

	/**
	 * Backend-Übersetzung
	 *
	 * @param $sTranslation
	 * @return string
	 */
	public static function t($sTranslation) {
		return \TcStatistic\Controller\StatisticController::t($sTranslation);
	}

	/**
	 * Standard-Header generieren
	 *
	 * @return Table\Row|Table\Row[]
	 */
	protected function generateHeaderRow() {
		$oRow = new Table\Row();
		$oRow->setRowSet('head');

		foreach($this->getColumns() as $oColumn) {
			$oRow[] = $oColumn->createCell(true);
		}

		return $oRow;
	}

}
