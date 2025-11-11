<?php

namespace TsStatistic\Generator\Statistic;

use Carbon\Carbon;
use Core\DTO\DateRange;
use Core\Helper\DateTime;
use TcStatistic\Exception\NoResultsException;
use TcStatistic\Model\Table;
use TsStatistic\Dto\FilterValues;
use TsStatistic\Generator\Tool\Bases;
use TsStatistic\Generator\Tool\Columns;
use TsStatistic\Generator\Tool\Groupings;
use TsStatistic\Model\Filter;
use TsStatistic\Service\Tool\ColumnQueryBuilder;

/**
 * Neues Statistik-Tool
 *
 * Ticket #12099 – Neues Statistik-Tool
 */
class Tool extends AbstractGenerator {

	protected $aAvailableFilters = [
		Filter\Schools::class,
		Filter\InvoiceType::class,
		Filter\Currency::class,
		Filter\Cancellation::class
	];

	/**
	 * Eine Spalte der Statistik hat eine Gruppierung
	 *
	 * @var bool
	 */
	private $bColumnWithGrouping = false;

	/**
	 * Eine Spalte der Statistik ist summierbar
	 *
	 * @var bool
	 */
	private $bColumnWithSum = false;

	/**
	 * @var array
	 */
	private $aHeadGroupingIds = [null];

	/**
	 * @var Bases\BaseInterface
	 */
	private $base;

	/**
	 * Gibt an, ob bei einer Obergruppierung diese auf der Y-Achse angezeigt werden.
	 * Das funktioniert nur bei keinem Intervall (komplett).
	 *
	 * @see getYAxisData()
	 * @var bool
	 */
	private $bHeadGroupingOnYAxis = false;

	/**
	 * Wenn alle Spalten dieselbe Gruppierung haben, wir die Gruppierung nur einmal angezeigt.
	 * Das macht die Statistik viel übersichtlicher, den Code aber auch komplexer.
	 *
	 * @var bool
	 */
	private $bSingleColumnGrouping = false;

	/**
	 * @TODO: Entfernen
	 *
	 * @var array
	 */
	private $aConfig = [];

	/**
	 * @TODO Nur temporär eingebaut und wird entfernt, sobald das Interface kommt
	 *
	 * @param string|null $sKey
	 */
	public function __construct($sKey = null) {

		if($sKey === null) {
			$sKey = $_GET['key'];
		}

		$aConfig = require(\Util::getDocumentRoot().'system/bundles/TsStatistic/Resources/config/tool.php');

		if(empty($aConfig[$sKey])) {
			throw new \UnexpectedValueException('Configuration "'.$sKey.'" is unknown!');
		}

		$this->aConfig = $aConfig[$sKey];

		// TODO Filter müsste man unsichtbar machen können, damit Filter standardmäßig doch angewendet wird
		// Das trifft auf Rechnungsstatus und Stornierungen zu, weil das dann immer den Default-Wert filtern sollte
		if(!empty($this->aConfig['filters'])) {
			$this->aAvailableFilters = $this->aConfig['filters'];
		}

		$this->bHeadGroupingOnYAxis = (bool)$this->aConfig['grouping_on_y_axis'];

		if ($this->aConfig['split_by_service_period']) {
			$this->base = new Bases\BookingServicePeriod();
		} else {
			$this->base = new Bases\Booking();
		}

		\Ext_TC_Util::setMySqlGroupConcatMaxLength();

	}

	/**
	 * @inheritdoc
	 */
	public function getTitle() {
		return $this->aConfig['title'];
	}

	/**
	 * @inheritdoc
	 */
	public function generateDataTable() {

		$aColumnGroupings = [];

		// Statistik-Konfiguration initialisieren (wird ersetzt, wenn das Interface kommt)
		foreach($this->aConfig['columns'] as $iKey => $aColumnConfig) {

			$oGrouping = null;
			if(!empty($aColumnConfig['grouping'])) {
				$oGrouping = new $aColumnConfig['grouping']();
			}

			$sConfiguration = null;
			if(isset($aColumnConfig['configuration'])) {
				$sConfiguration = $aColumnConfig['configuration'];
			}

			/** @var Columns\AbstractColumn $oColumn */
			$oColumn = new $aColumnConfig['class']($oGrouping, $this->aConfig['grouping'], $sConfiguration);
			$this->aConfig['columns'][$iKey]['object'] = $oColumn;

			if (!in_array(get_class($this->base), $oColumn->getAvailableBases())) {
				throw new \RuntimeException('Column '.get_class($oColumn).' can not be used with base '.get_class($this->base));
			}

			if(
				$oColumn->hasGrouping() &&
				!in_array(get_class($oGrouping), $oColumn->getAvailableGroupings())
			) {
				throw new \RuntimeException('Grouping '.get_class($oGrouping).' not available for column '.get_class($oColumn));
			}

			// TODO Wenn alle Spalten mit Gruppierung leer sind, darf das nicht auf true stehen (rowspan)
			if($oColumn->hasGrouping()) {
				$this->bColumnWithGrouping = true;
			}

			if($oColumn->isSummable()) {
				$this->bColumnWithSum = true;
			}

			if($oGrouping !== null) {
				$aColumnGroupings[get_class($oGrouping)] = true;
			} else {
				$aColumnGroupings[get_class($oColumn)] = true;
			}

		}

		// Siehe $bSingleColumnGrouping
		// TODO Evtl. einstellbar machen, wenn Bedingung erfüllt ist
		if(
			count($aColumnGroupings) === 1 &&
			is_subclass_of(key($aColumnGroupings), Groupings\AllLabelsInterface::class)
		) {
			$this->bSingleColumnGrouping = true;
		}

		// Daten pro Zeitraum pro Spalte
		$aData = [];
		$bHasData = false;
		$aDateRangeRows = $this->getDateRanges($this->aConfig['interval']);
		foreach($aDateRangeRows as $iDateRangeKey => $oDateRange) {
			$aQueryData = $this->getQueryData($oDateRange->from, $oDateRange->until, $this->aConfig['columns']);
			$aData[$iDateRangeKey] = $aQueryData;
			foreach($aQueryData as $aQueryDataRow) {
				if(!empty($aQueryDataRow)) {
					$bHasData = true;
					break;
				}
			}
		}

		if(!$bHasData) {
			throw new NoResultsException();
		}

		$oTable = new Table\Table();

		$this->setHeaderRows($oTable, $this->aConfig['columns'], $this->aConfig['grouping']);

		// Obergruppierung auf Y-Achse (links) anzeigen: Hier müssen nur die Daten neu gruppiert werden
		if($this->bHeadGroupingOnYAxis) {

			if(count($aDateRangeRows) > 1) {
				throw new \RuntimeException('Head grouping on Y axis but more than one date row!');
			}

			$aDataRows = $this->aConfig['grouping']->getLabels();
			$aData = $this->getYAxisData($aData);

			if(\System::d('debugmode') == 2) {
				foreach($aDataRows as $sHeadGroupingId => &$sHeadGroupingLabel) {
					$sHeadGroupingLabel = (string)$sHeadGroupingLabel.' ('.$sHeadGroupingId.')';
				}
			}

			// Zeitraum in den Block oben links schreiben, sonst wäre das nirgends vorhanden
			$oTopLeftCell = $oTable[0][0]; /** @var Table\Cell $oTopLeftCell */
			$oTopLeftCell->setValue($this->formatDateRange($aDateRangeRows[0]));

		} else {
			$aDataRows = $aDateRangeRows;
		}

		$this->setDataRows($oTable, $this->aConfig['columns'], $aDataRows, $aData);

		return $oTable;

	}

	/**
	 * Column-Querys ausführen
	 *
	 * @param \DateTime $dFrom
	 * @param \DateTime $dUntil
	 * @param array $aColumns
	 * @return array
	 */
	protected function getQueryData(\DateTime $dFrom, \DateTime $dUntil, array $aColumns) {

		$aSql = new FilterValues();
		$aSql->put('from', Carbon::instance($dFrom));
		$aSql->put('until', Carbon::instance($dUntil));
		$aSql->put('schools', $this->aFilters['schools']);
		$aSql->put('currency', $this->aFilters['currency']);
		$aSql->put('document_types', \Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice_with_creditnotes_and_without_proforma'));

		// TODO Entfernen, wenn Werte auf Filter-Models umgestellt werden
		foreach($this->getFilters() as $oFilter) {
			if(!isset($aSql[$oFilter->getKey()])) {
				$aSql[$oFilter->getKey()] = $this->aFilters[$oFilter->getKey()];
			}
		}

		$aResult = [];
		foreach($aColumns as $iKey => $aColumnConfig) {
			/** @var Columns\AbstractColumn $oColumn */
			$oColumn = $aColumnConfig['object'];

			$oBuilder = new ColumnQueryBuilder($this, $this->base, $oColumn, $aSql);
			$sSql = $oBuilder->createQuery();

			try {
				$aResult[$iKey] = $oColumn->getGroupedResult($sSql, $aSql);
			} catch(\DB_QueryFailedException $e) {
				__pout(\DB::getDefaultConnection()->getLastQuery());
				throw $e;
			}

		}

		return $aResult;

	}

	/**
	 * @param string $sType
	 * @return DateRange[]
	 */
	protected function getDateRanges($sType) {

		switch($sType) {
			case 'completely':
				return [new DateRange($this->aFilters['from'], $this->aFilters['until'])];
			case 'yearly':
				return DateTime::getYearPeriods($this->aFilters['from'], $this->aFilters['until'], false);
			case 'monthly':
				return DateTime::getMonthPeriods($this->aFilters['from'], $this->aFilters['until'], false);
			case 'weekly':
				return DateTime::getWeekPeriods($this->aFilters['from'], $this->aFilters['until'], false);
			default:
				throw new \InvalidArgumentException('Unknown interval type: '.$sType);
		}

	}

	/**
	 * Kopfzeilen setzen
	 *
	 * Dies können 1-4 Zeilen sein, je nach Konfiguration der Statistik und der Spalten
	 *
	 * @param Table\Table $oTable
	 * @param array $aColumns
	 * @param Groupings\AbstractGrouping|null $oHeadGrouping
	 */
	protected function setHeaderRows(Table\Table $oTable, array $aColumns, Groupings\AbstractGrouping $oHeadGrouping = null) {

		// Obergruppierung
		if(
			$oHeadGrouping !== null &&
			!$this->bHeadGroupingOnYAxis
		) {

			$this->checkLabelCartesianProduct($oHeadGrouping, $aColumns);

//			$oRow = new Table\Row();
//			$oRow->setRowSet('head');
//			$oTable[] = $oRow;

			// Erste Zeile: Titel
//			$oTopHeadGroupingCell = new Table\Cell($oHeadGrouping->getTitle(), true);
//			$oTopHeadGroupingCell->setBackground(AbstractGenerator::getColumnColor($oHeadGrouping->getColumnColor(), 'dark'));
//			$oTopHeadGroupingCell->setColspan(0);
//			$oTopLeftCell->setRowspan($this->bColumnWithGrouping ? 4 : 3);
//			$oRow[] = $oTopHeadGroupingCell;

			$oRow = new Table\Row();
			$oRow->setRowSet('head');
			$oTable[] = $oRow;

			// Zweite Zeile: Labels
			foreach($oHeadGrouping->getLabels() as $sHeadGroupingId => $sLabel) {
				if(\System::d('debugmode') == 2) {
					$sLabel .= ' ('.$sHeadGroupingId.')';
				}

				$oCell = new Table\Cell($sLabel, true);
				$oCell->setBackground(AbstractGenerator::getColumnColor($oHeadGrouping->getColumnColor(), $this->bSingleColumnGrouping ? 'dark' : 'light'));
				$oCell->setColspan(0);

				// Colspans für beide Zeilen kalkulieren
				foreach($aColumns as $iKey => $aColumnConfig) {
					/** @var Columns\AbstractColumn $oColumn */
					$oColumn = $aColumnConfig['object'];

					$iLabelsCount = 1;
					if($oColumn->hasGrouping()) {
						$iLabelsCount = count($oColumn->getGrouping()->getLabelsByGroupingCohesion($sHeadGroupingId));
					}

					$oCell->setColspan($oCell->getColspan() + $iLabelsCount);
//					$oTopHeadGroupingCell->setColspan($oTopHeadGroupingCell->getColspan() + $iLabelsCount);
				}

				$oRow[] = $oCell;
			}

			$this->aHeadGroupingIds = array_keys($oHeadGrouping->getLabels());

		}

		// Label für Spalte oder Gruppierung einer Spalte
		$oTable[] = $this->create2ndHeaderRow($aColumns);

		// Label für Spalte (wenn diese eine Gruppierung hat)
		$oRow = $this->create3rdHeaderRow($aColumns);
		if($oRow !== null) {
			$oTable[] = $oRow;
		}

		// Grauer Block oben links
		$oTopLeftCell = new Table\Cell(null, true);
		$oTopLeftCell->setRowspan(count($oTable));
		$oTable[0]->prepend($oTopLeftCell);

	}

	/**
	 * Label für Spalte oder Gruppierung einer Spalte
	 *
	 * @param array $aColumns
	 * @return array|Table\Row
	 */
	private function create2ndHeaderRow(array $aColumns) {

		$oRow = new Table\Row();
		$oRow->setRowSet('head');

		if($this->bSingleColumnGrouping) {

			/** @var Columns\AbstractColumn $oFirstColumn */
			$oFirstColumn = reset(array_column($aColumns, 'object'));

			foreach($this->aHeadGroupingIds as $sHeadGroupingId) {

				$aLabels = $oFirstColumn->getGrouping()->getAllLabels();

				if (empty($aLabels)) {
					throw new \LogicException('getAllLabels must return at least one result or must throw NoResultsException');
				}

				foreach($aLabels as $iLabelId => $sLabel) {

					if(\System::d('debugmode') == 2) {
						$sLabel .= ' ('.$iLabelId.')';
					}

					$oCell = new Table\Cell($sLabel, true);
					$oCell->setBackground(AbstractGenerator::getColumnColor($oFirstColumn->getGrouping()->getColumnColor()));
					$oCell->setColspan(count($aColumns));
					$oRow[] = $oCell;

				}
			}

		} else {

			foreach($this->aHeadGroupingIds as $sHeadGroupingId) {
				foreach($aColumns as $iKey => $aColumnConfig) {
					/** @var Columns\AbstractColumn $oColumn */
					$oColumn = $aColumnConfig['object'];

					if($oColumn->hasGrouping()) {

						$oCell = $oColumn->createCell(true, 'dark');
						$oCell->setValue($oColumn->getGrouping()->getTitle().' / '.$oColumn->getTitle());

						if($sHeadGroupingId === null) {
							$oCell->setColspan(count($oColumn->getGrouping()->getLabels()));
						} else {
							$oCell->setColspan(count($oColumn->getGrouping()->getLabelsByGroupingCohesion($sHeadGroupingId)));
						}

						// Keine Daten in dieser Gruppierung vorhanden, daher überspringen
						if($oCell->getColspan() === 0) {
							continue;
						}

					} else {
						$oCell = $oColumn->createCell(true);
						$oCell->setValue($oColumn->getTitle());
						if($this->bColumnWithGrouping) {
							$oCell->setRowspan(2);
						}
					}

					$oRow[] = $oCell;
				}
			}

		}

		return $oRow;

	}

	/**
	 * Label für Spalte (wenn diese eine Gruppierung hat)
	 *
	 * @param array $aColumns
	 * @return array|Table\Row|null
	 */
	private function create3rdHeaderRow(array $aColumns) {

		$oRow = null;

		if($this->bSingleColumnGrouping) {

			$oRow = new Table\Row();
			$oRow->setRowSet('head');

			/** @var Columns\AbstractColumn $oFirstColumn */
			$oFirstColumn = reset(array_column($aColumns, 'object'));

			foreach($this->aHeadGroupingIds as $sHeadGroupingId) {

				$aLabels = $oFirstColumn->getGrouping()->getAllLabels();

				foreach($aLabels as $sLabel) {
					foreach($aColumns as $aColumnConfig) {
						/** @var Columns\AbstractColumn $oColumn */
						$oColumn = $aColumnConfig['object'];
						$oCell = $oColumn->createCell(true);
						$oCell->setValue($oColumn->getTitle());
						$oRow[] = $oCell;
					}
				}
			}

		} else {

			// Labels für Gruppierung (wenn Spalte eine hat und es überhaupt eine Spalte mit Gruppierung gibt)
			if($this->bColumnWithGrouping) {

				$oRow = new Table\Row();
				$oRow->setRowSet('head');

				foreach($this->aHeadGroupingIds as $sHeadGroupingId) {
					foreach($aColumns as $iKey => $aColumnConfig) {

						/** @var Columns\AbstractColumn $oColumn */
						$oColumn = $aColumnConfig['object'];

						if(!$oColumn->hasGrouping()) {
							continue;
						}

						if($sHeadGroupingId === null) {
							$aLabels = $oColumn->getGrouping()->getLabels();
						} else {
							$aLabels = $oColumn->getGrouping()->getLabelsByGroupingCohesion($sHeadGroupingId);
						}

						foreach($aLabels as $iLabelId => $sLabel) {

							if(\System::d('debugmode') == 2) {
								$sLabel .= ' ('.$iLabelId.')';
							}

							$oCell = $oColumn->createCell(true);
							$oCell->setValue($sLabel);
							$oRow[] = $oCell;
						}
					}
				}
			}
		}

		return $oRow;

	}

	/**
	 * Datenzeile und ggf. Summenzeile setzen
	 *
	 * @param Table\Table $oTable
	 * @param array $aColumns
	 * @param array $aDataRows Datumsangaben oder Obergruppierung (bei Intervall komplett)
	 * @param array $aData
	 */
	protected function setDataRows(Table\Table $oTable, array $aColumns, array $aDataRows, array $aData) {

		// Datenzeilen
		foreach($aDataRows as $sDataRowKey => $mDataRowLabel) {

			$oRow = new Table\Row();
			$oTable[] = $oRow;

			if($mDataRowLabel instanceof DateRange) {
				$oCell = new Table\Cell($this->formatDateRange($mDataRowLabel), true);
			} else {
				$oCell = new Table\Cell($mDataRowLabel, true);
			}

			$oRow[] = $oCell;

			foreach($this->aHeadGroupingIds as $sHeadGroupingId) {

				$aLabels = [null => null];
				if($this->bSingleColumnGrouping) {
					$oFirstColumn = reset(array_column($aColumns, 'object'));
					$aLabels = $oFirstColumn->getGrouping()->getAllLabels();
				}

				// Enthält nur mehr als ein Element, wenn Labels oben stehen (bSingleColumnGrouping = true)
				foreach(array_keys($aLabels) as $sGroupingId) {

					foreach($aColumns as $iKey => $aColumnConfig) {

						/** @var Columns\AbstractColumn $oColumn */
						$oColumn = $aColumnConfig['object'];

						if($this->bSingleColumnGrouping) {
							$aLabels = [$sGroupingId => $sGroupingId];
						} else {
							$aLabels = [null => null];
							if($oColumn->hasGrouping()) {
								if($sHeadGroupingId === null) {
									$aLabels = $oColumn->getGrouping()->getLabels();
								} else {
									$aLabels = $oColumn->getGrouping()->getLabelsByGroupingCohesion($sHeadGroupingId);
								}
							}
						}

						foreach(array_keys($aLabels) as $sGroupingId) {
							$oCell = $oColumn->createCell();
							$oCell->setValue($aData[$sDataRowKey][$iKey][$sHeadGroupingId][$sGroupingId][0]);
							$oCell->setComment($aData[$sDataRowKey][$iKey][$sHeadGroupingId][$sGroupingId][1]);
							$oColumn->setTotalSumValue($sHeadGroupingId, $sGroupingId, $oCell->getValue());

							if($oCell->getFormat() === 'number_amount') {
								$oCell->setCurrency($this->aFilters['currency']);
							}

							$oRow[] = $oCell;
						}

					}

				}

			}

		}

		// Summenzeile
		if(
			$this->bColumnWithSum &&
			count($aDataRows) > 1
		) {

			$oRow = new Table\Row();
			$oRow->setRowSet('foot');
			$oTable[] = $oRow;

			$oCell = new Table\Cell(self::t('Summe'), true);
			$oRow[] = $oCell;

			foreach($this->aHeadGroupingIds as $sHeadGroupingId) {

				$aLabels = [null => null];
				if($this->bSingleColumnGrouping) {
					$oFirstColumn = reset(array_column($aColumns, 'object'));
					$aLabels = $oFirstColumn->getGrouping()->getAllLabels();
				}

				// Enthält nur mehr als ein Element, wenn Labels oben stehen (bSingleColumnGrouping = true)
				foreach(array_keys($aLabels) as $sGroupingId) {

					foreach($aColumns as $iKey => $aColumnConfig) {

						/** @var Columns\AbstractColumn $oColumn */
						$oColumn = $aColumnConfig['object'];

						if($this->bSingleColumnGrouping) {
							$aLabels = [$sGroupingId => $sGroupingId];
						} else {
							$aLabels = [null => null];
							if($oColumn->hasGrouping()) {
								if($sHeadGroupingId === null) {
									$aLabels = $oColumn->getGrouping()->getLabels();
								} else {
									$aLabels = $oColumn->getGrouping()->getLabelsByGroupingCohesion($sHeadGroupingId);
								}
							}
						}

						foreach(array_keys($aLabels) as $sGroupingId) {
							if($oColumn->isSummable()) {
								$oCell = new Table\Cell($oColumn->getTotalSumValue($sHeadGroupingId, $sGroupingId), false, $oColumn->getFormat());

								if($oCell->getFormat() === 'number_amount') {
									$oCell->setCurrency($this->aFilters['currency']);
								}

								$oCell->setFontStyle('bold');
							} else {
								$oCell = new Table\Cell(null);
							}

							$oCell->setBackground($this->getColumnColor('general'));
							$oRow[] = $oCell;
						}
					}

				}

			}
		}
	}

	/**
	 * Daten neu gruppieren, wenn Obergruppierung auf Y-Achse angezeigt wird
	 *
	 * Das funktioniert, indem die Obergruppierung nun den Row-Key darstellt
	 * (der eigentlich das DateRange-Objekt wäre) und der eigentliche Key
	 * für die Obergruppierung entfernt wird, da diese Daten bei den Headern
	 * auch nicht durchlaufen werden.
	 *
	 * @param array $aData
	 * @return array
	 */
	private function getYAxisData(array $aData) {

		$aNewData = [];
		foreach($aData[0] as $iColumnKey => $aData1) {
			foreach($aData1 as $sHeadGroupingId => $aData2) {
				$aNewData[$sHeadGroupingId][$iColumnKey][null] = $aData2;
			}
		}
		return $aNewData;

	}

	/**
	 * @TODO Einstellbar machen: Entweder Einstellung für Grouping oder bSingleColumnGrouping = true
	 *
	 * Bei vorhandener Obergruppierung werden bei Spaltengruppierungen nur die Labels angezeigt, die tatsächlich
	 * in den Rows selektiert wurden. Um pro Obergruppierungs-Label immer alle Labels einer Spaltengruppierung
	 * anzuzeigen, muss ein vollständiges kartesisches Produkt erzeugt werden, damit die Colspans auch korrekt
	 * funktionieren.
	 *
	 * @param Groupings\AbstractGrouping $oHeadGrouping
	 * @param array $aColumns
	 */
	private function checkLabelCartesianProduct(Groupings\AbstractGrouping $oHeadGrouping, array $aColumns) {

		if(!$this->bSingleColumnGrouping) {
			return;
		}

		foreach($aColumns as $aColumnConfig) {

			/** @var Columns\AbstractColumn $oColumn */
			$oColumn = $aColumnConfig['object'];

			if(
				true // Erst einmal nicht einstellbar, sondern nur mit bSingleColumnGrouping – braucht eh immer alle Labels
//				$oColumn->hasGrouping() &&
//				!empty($aColumnConfig['configuration_grouping']) &&
//				$aColumnConfig['configuration_grouping'] === 'all_labels'
			) {
				$oGrouping = $oColumn->getGrouping();
				if(!$oGrouping instanceof Groupings\AllLabelsInterface) {
					throw new \RuntimeException('Grouping '.get_class($oColumn->getGrouping()).' does not implement '.Groupings\AllLabelsInterface::class);
				}

				$aLabels = array_keys($oGrouping->getAllLabels());
				foreach(array_keys($oHeadGrouping->getLabels()) as $sHeadGroupingId) {
					foreach($aLabels as $sLabelId) {
						$oColumn->getGrouping()->setGroupingCohesion($sHeadGroupingId, $sLabelId);
					}
				}
			}
		}

	}

	/**
	 * @param DateRange $oDateRange
	 * @return string
	 */
	private function formatDateRange(DateRange $oDateRange) {
		return \Ext_Thebing_Format::LocalDate($oDateRange->from).' - '.\Ext_Thebing_Format::LocalDate($oDateRange->until);
	}

	/**
	 * @inheritdoc
	 */
	public function getBasedOnOptionsForDateFilter() {

		if ($this->base instanceof Bases\BookingServicePeriod) {
			return [
				'service_period' => self::t('Leistungszeitraum')
			];
		}

		return [
			'registration_date' => self::t('Buchungsdatum')
		];

	}

	/**
	 * @inheritdoc
	 */
//	public function getInfoTextListItems() {
//
//		if($this->bSplitByServicePeriod) {
//			return ['Bei der Berechnung auf Basis des Leistungszeitraums kann es zu Rundungsfehlern im Centbereich kommen.'];
//		}
//
//		return [];
//	}


}
