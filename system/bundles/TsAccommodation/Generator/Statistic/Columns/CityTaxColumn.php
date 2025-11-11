<?php

namespace TsAccommodation\Generator\Statistic\Columns;

use Carbon\Carbon;
use TsStatistic\Dto\FilterValues;
use TsStatistic\Generator\Tool\Columns\Revenue;
use TsStatistic\Service\DocumentItemAmount;
use TsStatistic\Service\NightCalculcator;

class CityTaxColumn extends Revenue {

	private array $result = [];

	public function getTitle() {

		return match ($this->sServiceType) {
			'nights_taxable' => 'Anzahl steuerp. Übernachtungen',
			'amount_taxable' => 'Gesamtsumme steuerp. Übernachtungen',
			'amount_fee_taxable' => 'Gesamtsumme City Tax',
			'nights_not_taxable' => 'Anzahl nicht steuerp. Übernachtungen',
			'amount_not_taxable' => 'Gesamtsumme nicht steuerp. Übernachtungen',
			'amount_fee_not_taxable' => 'Gesamtsumme City Tax nicht steuerp. Übernachtungen'
		};

	}

	/**
	 * Der erste Teil ist eine Kopie von Parent, leicht abgewandelt wg. anderem group by
	 * @see parent
	 * @return string
	 */
	public function getSelect() {

		$select = "
			`kid`.`document_number` `label`,
			`kid`.`type` `document_type`,
			`kidv`.`tax` `item_tax_type`,
			`kidvi`.`id` `item_id`,
			`kidvi`.`type` `item_type`,
			`kidvi`.`index_from` `item_from`,
			`kidvi`.`index_until` `item_until`,
			SUM(`kidvi`.`amount`) `item_amount`,
			SUM(`kidvi`.`amount_net`) `item_amount_net`,
			SUM(`kidvi`.`amount_discount`) `item_amount_discount`,
			SUM(`kidvi`.`amount_provision`) `item_amount_commission`,
			`kidvi`.`tax` `item_tax`,
			SUM(`kidvi`.`index_special_amount_gross`) `item_index_special_amount_gross`,
			SUM(`kidvi`.`index_special_amount_net`) `item_index_special_amount_net`,
			SUM(`kidvi`.`index_special_amount_gross_vat`) `item_index_special_amount_gross_vat`,
			SUM(`kidvi`.`index_special_amount_net_vat`) `item_index_special_amount_net_vat`,
			`kidvi`.`additional_info` `item_additional_info`,
			`cdb2`.`course_startday`,
			NULL `item_costs_calculation`,
			NULL `item_costs_booking_timepoint`
			";

		// Für GLS beginnt die Nacht immer erst am darauffolgenden Tag, d.h. am 28.02. wird die Nacht erst am 01.03. gezählt
		$select .= ",
			CONCAT({$this->grouping->getSelectFieldForId()}, '_', `kidvi`.`id`) `detail_id`,
			`kidvi`.`index_from` + INTERVAL 1 DAY `item_from`,
			`kidvi`.`index_until` + INTERVAL 1 DAY `item_until`,
			JSON_ARRAYAGG(
				DISTINCT
				JSON_OBJECT(
				  'id',           kidvi_city_tax.id,
				  'additional_info', JSON_EXTRACT(kidvi_city_tax.additional_info, '$'),
				  'amount',       kidvi_city_tax.amount,
				  'amount_net',   kidvi_city_tax.amount_net,
				  'index_from',   kidvi_city_tax.index_from,
				  'index_until',  kidvi_city_tax.index_until,
				  'tax',          kidvi_city_tax.tax
				)
			  ) AS city_tax,

			`kidvi_city_tax`.`id` `city_tax_item_id`,
			`kidvi_city_tax`.`additional_info` `city_tax_additional_info`,
			`kidvi_city_tax`.`amount` `city_tax_amount`,
			`kidvi_city_tax`.`amount_net` `city_tax_amount_net`,
			`kidvi_city_tax`.`index_from` `city_tax_from`,
			`kidvi_city_tax`.`index_until` `city_tax_until`,
			`kidvi_city_tax`.`tax` `city_tax_tax`
		";

		return $select;
	}

	public function getJoinParts() {
		return ['document'];
	}

	public function getGroupBy() {
		// City-Tax kommt nur einmal vor, daher alle passenden Unterkunftsitems zusammenfassen
		return ['`kidvi`.`version_id`'];
	}

	public function getJoinPartsAdditions() {

		$additions = [];

		$additions['JOIN_ITEMS'] = " AND
				`kidvi`.`type` IN('accommodation', 'extra_nights', 'extra_weeks') AND
				`kidvi`.`type_object_id` IN (:accommodation_category_ids)
			";
//		switch ($this->sServiceType) {
//			case 'nights_taxable':
//			case 'amount_taxable':
//			case 'nights_not_taxable':
//			case 'amount_not_taxable':
//				// Nur Unterkunftspos. mit den relevanten Kategorien
//				$additions['JOIN_ITEMS'] = " AND
//					`kidvi`.`type` IN('accommodation', 'extra_nights', 'extra_weeks') AND
//					`kidvi`.`type_object_id` IN (:accommodation_category_ids)
//				";
//				break;
//
//			case 'amount_fee_taxable':
//			case 'amount_fee_not_taxable':
//				// Nur Zusatzgebühren vom Typ City Tax
//				$additions['JOIN_ITEMS'] = " AND
//					`kidvi`.`type` = :fee_city_tax_type AND
//					`kidvi`.`type_id` IN (:fee_city_tax_ids)
//				";
//				break;
//		}

		$additions['JOIN_ITEMS_JOINS'] .= " LEFT JOIN
			`kolumbus_inquiries_documents_versions_items` `kidvi_city_tax` ON 
				`kidvi_city_tax`.`version_id` = `kidv`.`id` AND
				`kidvi_city_tax`.`active` = 1 AND
				`kidvi_city_tax`.`onPdf` = 1 AND
				`kidvi_city_tax`.`type` = :fee_city_tax_type AND
				`kidvi_city_tax`.`type_id` IN (:fee_city_tax_ids)
		";

		return $additions;
	}

	public function getSqlWherePart() {

		$where = parent::getSqlWherePart();

//		if (
//			$this->sServiceType === 'nights_not_taxable' ||
//			$this->sServiceType === 'amount_not_taxable' ||
//			$this->sServiceType === 'amount_fee_not_taxable'
//		) {
//			$where .= " AND `kidvi_city_tax`.`id` IS NULL ";
//		} else {
//			$where .= " AND `kidvi_city_tax`.`id` IS NOT NULL ";
//		}
#$where .= " AND kid.document_number IN ('4045980','4050710', '4006478') ";
#$where .= " AND kid.document_number IN ('4055751') ";
		return $where;
	}

	public function mergeCityTaxRows(array $rows): array
	{
		if ($rows === []) {
			return [];
		}

		$first = $rows[array_key_first($rows)];

		$sumAmount    = 0.0;
		$sumAmountNet = 0.0;
		$allCityTax   = [];

		$minFrom  = null; // Carbon|null
		$maxUntil = null; // Carbon|null

		foreach ($rows as $row) {
			// Summen (null wird wie 0 behandelt)
			$sumAmount    += (float)($row['amount']     ?? 0);
			$sumAmountNet += (float)($row['amount_net'] ?? 0);

			// Zeitraum ausweiten
			if (!empty($row['index_from']) && $row['index_from'] !== '0000-00-00') {
				$from = Carbon::parse($row['index_from']);
				$minFrom = $minFrom ? $minFrom->min($from) : $from;
			}
			if (!empty($row['index_until']) && $row['index_until'] !== '0000-00-00') {
				$until = Carbon::parse($row['index_until']);
				$maxUntil = $maxUntil ? $maxUntil->max($until) : $until;
			}

			// City-Tax-Details einsammeln
			if (!empty($row['additional_info']['city_tax']) && is_array($row['additional_info']['city_tax'])) {
				// flach anhängen
				foreach ($row['additional_info']['city_tax'] as $ct) {
					$allCityTax[] = $ct;
				}
			}
		}

		// Ergebnis auf Basis des ersten Eintrags
		$result = $first;

		$result['amount']     = round($sumAmount, 2);
		$result['amount_net'] = round($sumAmountNet, 2);
		if ($minFrom)  { $result['index_from']  = $minFrom->toDateString(); }
		if ($maxUntil) { $result['index_until'] = $maxUntil->toDateString(); }

		// city_tax zusammenführen
		$result['additional_info']['city_tax'] = array_values($allCityTax);

		return $result;
	}
	
	public function prepareResult(array $result, FilterValues $values) {

		$nightCalculator = new NightCalculcator($values->from, $values->until);

		foreach ($result as &$item) {

			$cityTaxItems = json_decode($item['city_tax'], true);
			
			$mergedCityTaxData = $this->mergeCityTaxRows($cityTaxItems);
			
			if(!empty($mergedCityTaxData)) {
				
				$item['city_tax_additional_info'] = json_encode($mergedCityTaxData['additional_info']);
				$item['city_tax_amount'] = $mergedCityTaxData['amount'];
				$item['city_tax_amount_net'] = $mergedCityTaxData['amount_net'];
				$item['city_tax_from'] = $mergedCityTaxData['index_from'];
				$item['city_tax_until'] = $mergedCityTaxData['index_until'];
				$item['city_tax_tax'] = $mergedCityTaxData['tax'];
				
			}
			
			$negate = $item['item_amount'] < 0;
			
			// In der City-Tax steht der Gesamtzeitraum der berücksichtigen Unterkunftsbuchungen drin
			if(
				!empty($item['city_tax_from']) &&
				!empty($item['city_tax_until'])
			) {
				$from = Carbon::parse($item['city_tax_from']);
				$until = Carbon::parse($item['city_tax_until']);
			} else {
				$from = Carbon::parse($item['item_from']);
				$until = Carbon::parse($item['item_until']);
			}
			
			$cityTaxAdditionalInfo = json_decode($item['city_tax_additional_info'], true);
			$cityTaxInfo = $cityTaxAdditionalInfo['city_tax']??[];
			
			// Von-Bis für den Zeitraum für den City-Tax berechnet wurde bzw. nicht berechnet wurde
			$cityTaxFrom = $cityTaxUntil = null;
			
			$calculatedDays = 0;

			if(
				$this->sServiceType === 'nights_taxable' ||
				$this->sServiceType === 'amount_taxable' ||
				$this->sServiceType === 'amount_fee_taxable'
			) {

				$calculatedDays = array_sum(array_column($cityTaxInfo, 'days'));

				$cityTaxFrom = $from->copy();
				$cityTaxUntil = $from->copy()->addDays($calculatedDays);

			} else {
				if(!empty($cityTaxInfo)) {
					$calculatedDays = array_sum(array_column($cityTaxInfo, 'total_days'))-array_sum(array_column($cityTaxInfo, 'days'));
				} else {
					// Wenn keine City-Tax berechnet, dann sind alle Tage der UK ohne Steuer
					$calculatedDays = $from->diffInDays($until);
				}

				$cityTaxFrom = $until->copy()->subDays($calculatedDays);
				$cityTaxUntil = $until->copy();

			}

			// Sicherheitsabfrage
			if($cityTaxFrom < $from) {
				$cityTaxFrom = $from->copy();
			}
			if($cityTaxUntil > $until) {
				$cityTaxUntil = $until->copy();
			}

//__pout($this->sServiceType);
//__pout($item);
//__pout($cityTaxInfo);
//__pout($cityTaxFrom->toDateString());
//__pout($cityTaxUntil->toDateString());

			if(
				$this->sServiceType === 'nights_taxable' ||
				$this->sServiceType === 'nights_not_taxable'
			) {
				
				if($calculatedDays > 0) {
					$item['result'] = $nightCalculator->calculate($cityTaxFrom, $cityTaxUntil, $negate);
				} else {
					$item['result'] = 0;
				}

				continue;
			}
			
			$taxMode = 1;
			
			if($this->sServiceType == 'amount_taxable') {
				$item['item_amount'] = array_sum(array_map(function($item) {
					return $item['total_amount'] * $item['factor'];
				}, $cityTaxInfo));
				$item['item_amount_net'] = array_sum(array_map(function($item) {
					return $item['total_amount_net'] * $item['factor'];
				}, $cityTaxInfo));

			} elseif($this->sServiceType == 'amount_not_taxable') {
				// Nur wenn City-Tax berechnet wurde sind diese Beträge anteilig
				if(!empty($cityTaxInfo)) {
					$item['item_amount'] = array_sum(array_map(function($item) {
						return $item['total_amount'] - ($item['total_amount'] * $item['factor']);
					}, $cityTaxInfo));
					$item['item_amount_net'] = array_sum(array_map(function($item) {
						return $item['total_amount_net'] - ($item['total_amount_net'] * $item['factor']);
					}, $cityTaxInfo));
				}
			} elseif($this->sServiceType == 'amount_fee_taxable') {
								
				if(empty($item['item_tax'])) {
					$taxMode = 0;
				}
				
				$item['item_amount'] = $item['city_tax_amount'];
				$item['item_amount_net'] = $item['city_tax_amount_net'];
				$item['item_tax'] = $item['city_tax_tax'];
				
			}

			$nightsInPeriod = $nightCalculator->calculate($cityTaxFrom, $cityTaxUntil, $negate);

			// Der Splitter von DocumentItemAmount geht nach Tagen und nicht nach Nächten, d.h. es würde immer durch eine Nacht zu viel geteilt werden
			$nightsTotal = $cityTaxFrom->diffInDays($cityTaxUntil);
			if ($negate) {
				$nightsTotal *= -1;
			}

			if (bccomp($nightsTotal, '0', 2) === 0) {
				continue;
			}

			$documentItemAmount = new DocumentItemAmount();
			$documentItemAmount->iTaxMode = 0; // Umsatzsteuer immer rausrechnen (Netto-Beträge)
#__pout($item);
#__pout($nightsTotal.' - '.$nightsInPeriod);
			// Splitten nach Leistungszeitraum auf Basis von NACHT
			$item['result'] = $documentItemAmount->calculate($item) / $nightsTotal * $nightsInPeriod;
//if($item['label'] == '4056680') {
#__pout($item['result']);
//}
		}

		$this->result = $result;

		return $this->buildSum($result);
	}

	public function getNonSummarizedResult(): array {
		return $this->result;
	}

	public function getFormat() {

		if (
			$this->sServiceType === 'nights_taxable' ||
			$this->sServiceType === 'nights_not_taxable'
		) {
			return 'number_int';
		}
		return 'number_amount';

	}

	public function getColumnColor() {
		return 'general';
	}

}
