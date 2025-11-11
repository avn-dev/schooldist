<?php

namespace TsAccommodation\Generator\Statistic;

use Carbon\Carbon;
use TsAccommodation\Generator\Statistic\Columns\CityTaxColumn;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use TcStatistic\Exception\NoResultsException;
use TcStatistic\Model\Table;
use TsStatistic\Dto\FilterValues;
use TsStatistic\Generator\Statistic\AbstractGenerator;
use TsStatistic\Generator\Tool\Bases\BookingServicePeriod;
use TsStatistic\Generator\Tool\Groupings\Revenue\AccommodationCategory;
use TsStatistic\Model\Filter;
use TsStatistic\Service\Tool\ColumnQueryBuilder;
use TsAccommodation\Handler\ExternalApp\CityTax as CityTaxApp;

class CityTaxReport extends AbstractGenerator {

	protected $aAvailableFilters = [
		Filter\Schools::class,
		Filter\Currency::class
	];

	/**
	 * @inheritdoc
	 */
	public function getTitle() {
		return 'City Tax Report';
	}

	/**
	 * @inheritdoc
	 */
	public function generateDataTable() {

		$config = [
			'fee_city_tax_ids' => [],
			'accommodation_category_ids' => []
		];#(new \Core\Helper\Bundle())->readBundleFile('GlsSchule');
		$base = new BookingServicePeriod();
		$grouping = new AccommodationCategory();

		foreach($this->aFilters['schools'] as $schoolId) {
			$school = \Ext_Thebing_School::getInstance($schoolId);
			$config['fee_city_tax_ids'] = array_merge($config['fee_city_tax_ids'], $school->getMeta(CityTaxApp::KEY_ACCOMMODATION_COSTS, []));
			$config['accommodation_category_ids'] = array_merge($config['accommodation_category_ids'], $school->getMeta(CityTaxApp::KEY_ACCOMMODATION_CATEGORIES, []));
		}

		$cols = [
			'gross_nights_taxable' => new CityTaxColumn($grouping, null, 'gross_nights_taxable'),
			'gross_amount_taxable' => new CityTaxColumn($grouping, null, 'gross_amount_taxable'),
			'gross_amount_fee_taxable' => new CityTaxColumn($grouping, null, 'gross_amount_fee_taxable'),
			'gross_nights_not_taxable' => new CityTaxColumn($grouping, null, 'gross_nights_not_taxable'),
			'gross_amount_not_taxable' => new CityTaxColumn($grouping, null, 'gross_amount_not_taxable'),
//			'gross_amount_fee_not_taxable' => new CityTaxColumn($grouping, null, 'gross_amount_fee_not_taxable')
		];

		$table = new Table\Table();
		$table->setCaption('Summen');

		$queryData = new FilterValues([
			'from' => Carbon::instance($this->aFilters['from']),
			'until' => Carbon::instance($this->aFilters['until']),
			'schools' => $this->aFilters['schools'],
			'currency' => $this->aFilters['currency'],
			'document_types' => \Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice_without_proforma'),
			'accommodation_category_ids' => $config['accommodation_category_ids'],
			'fee_city_tax_ids' => $config['fee_city_tax_ids'],
			'fee_city_tax_type' => 'additional_accommodation'
		]);

		// Aufteilung: Acc-Kategorie => Column
		$data = [];
		$dataDetail = [];

		// ColumnQueryBuilder nutzen für die ganze Rechnungslogik etc.
		foreach ($cols as $colIndex => $col) {

			$builder = new ColumnQueryBuilder($this, $base, $col, $queryData);
			$query = $builder->createQuery();
			$result = $col->getGroupedResult($query, $queryData);

			$result = Arr::first($result, null, []); // Keine Head-Grouping, Ebene entfernen

			// Summe
			foreach ($result as $groupingKey => $groupingData) {
				if (isset($data[$groupingKey][$colIndex])) {
					throw new \RuntimeException();
				}

				$data[$groupingKey][$colIndex] = $groupingData;
			}

			// Detail
			$result2 = $col->getNonSummarizedResult();
			foreach ($result2 as $row) {
				if (!isset($dataDetail[$row['detail_id']])) {
					$dataDetail[$row['detail_id']] = $row;
				}
				$dataDetail[$row['detail_id']][$colIndex] = $row['result'];
			}

		}

		if (empty($data)) {
			throw new NoResultsException();
		}

		// 1. Spalte: Kategorie mit rowspan
		// 2. Spalte: Label
		// 3. Spalte: Wert
		foreach ($data as $groupingKey => $groupingData) {

			foreach ($cols as $colIndex => $col) {

				$row = new Table\Row();
				$table[] = $row;

				if ($colIndex === key($cols)) {
					$cell = new Table\Cell();
					$cell->setHeading(true);
					$cell->setValue($grouping->getLabels()[$groupingKey]);
					$cell->setRowspan(count($cols));
					$row[] = $cell;
				}

				$cell = $col->createCell(true);
				$cell->setValue($col->getTitle());
				$row[] = $cell;

				$cell = $col->createCell();
				$row[] = $cell;

				if (isset($groupingData[$colIndex])) {
					$cell->setValue($groupingData[$colIndex][0]);
					$cell->setComment($groupingData[$colIndex][1]);
				}

				if ($cell->getFormat() === 'number_amount') {
					$cell->setCurrency($this->aFilters['currency']);
				}

			}
		}

		$table2 = $this->createDetailTable($cols, collect($dataDetail));
#__pout(\DB::getQueryHistory(),1);
		return [$table, $table2];
	}

	/**
	 * @param CityTaxColumn[] $cols
	 * @param Collection $data
	 * @return Table\Table
	 */
	private function createDetailTable(array $cols, Collection $data): Table\Table {

		$table = new Table\Table();
		$table->setCaption('Detail');

		$head = new Table\Row();
		$table[] = $head;

		$head[] = new Table\Cell('Kategorie', true);
		$head[] = new Table\Cell('Rechnung', true);
		foreach ($cols as $col) {
			$cell = $col->createCell(true);
			$cell->setValue($col->getTitle());
			$head[] = $cell;
		}

		$data = $data->sortBy('label');

		foreach ($data as $row) {
			$tableRow = new Table\Row();
			$table[] = $tableRow;

			$tableRow[] = new Table\Cell($row['grouping_label']);
			$tableRow[] = new Table\Cell($row['label']);

			foreach ($cols as $colIndex => $col) {
				$cell = $col->createCell();
				$cell->setValue($row[$colIndex]);
				$tableRow[] = $cell;

				if ($cell->getFormat() === 'number_amount') {
					$cell->setCurrency($this->aFilters['currency']);
				}
			}
		}

		return $table;

	}

	public function getBasedOnOptionsForDateFilter() {
		return [
			'service_period' => self::t('Leistungszeitraum')
		];
	}

	public function getInfoTextListItems() {
		return [
			'Es werden nur Werte beachtet, welche in Rechnung gestellt wurden. Beträge beinhalten immer Provision und Steuer.',
			'Bei der Berechnung auf Basis des Leistungszeitraums kann es zu Rundungsfehlern im Centbereich kommen.',
			'Rechnungspositionen werden dann als steuerpflichtig gezählt, sobald eine Rechnung der Buchung die Zusatzgebühr <em>City Tax</em> enthält.',
			'Nächte: Die jeweilige Nacht wird immer für den Tag danach gezählt. Fällt ein Zeitraum nicht komplett in den Filterzeitraum, so wird bei allen Teilzeiträumen, außer dem letzten, die Nacht am Endtag des Teilzeitraums addiert.',
			'Der Zeitraum einer Unterkunft muss mindestens eine Nacht (zwei Tage Leistungszeitraum) betragen.'
		];
	}

}
