<?php

namespace TsAccommodation\Service;

use \Carbon\Carbon;
use \TsAccommodation\Handler\ExternalApp\CityTax as CityTaxApp;

class CityTax {
	
	private $itemIsArray = false;
	
	private function harmoniseItems(&$items) {
		
		$first = reset($items);
		
		if($first instanceof \Ext_Thebing_Inquiry_Document_Version_Item) {
			return $items;
		}

		$this->itemIsArray = true;

		$newItems = [];
		foreach($items as &$tmpItem) {
			$item = new \TsAccommodation\Model\Item;
			$item->index_from = $tmpItem['from']; 
			$item->index_until = $tmpItem['until'];
			$item->description = $tmpItem['description'];
			$item->type = $tmpItem['type'];
			$item->type_id = $tmpItem['type_id'];
			$item->type_object_id = $tmpItem['type_object_id'];
			$item->amount = $tmpItem['amount'];
			$item->amount_provision = $tmpItem['amount_provision'];
			$item->amount_net = $tmpItem['amount_net'];
			$item->tax_category = $tmpItem['tax_category'];
			$item->tax = $tmpItem['tax'];
			$item->additional_info = $tmpItem['additional_info'];
			$item->parent_booking_id = $tmpItem['parent_booking_id'];
			
			// Damit die Werte wieder in das Ursprungsarray gesetzt werden.
			$item->original = &$tmpItem;

			$newItems[] = $item;
		}
		
		unset($tmpItem);
		
		return $newItems;
	}
	
	/**
	 * Die Methode ist so ausgelegt, dass sie auch City-Tax-Items aktualisieren kann, daher der Zugriff auf die 
	 * im Item gespeicherten Infos
	 * 
	 * @param array $mixedItems
	 * @param \Ext_Thebing_Inquiry_Document $document
	 * @param \DateTime $now
	 * @return null|array
	 */
	public function handle(array &$mixedItems, \Ext_Thebing_Inquiry_Document $document, \DateTime $now=null) {

		$school = $document->getSchool();
		
		$items = $this->harmoniseItems($mixedItems);

		$items = collect($items); /** @var \Ext_Thebing_Inquiry_Document_Version_Item[] $items */
				
		$config = [];#(new \Core\Helper\Bundle())->readBundleFile('TsAccommodation')['city_tax'];
		$config['fee_city_tax_ids'] = $school->getMeta(CityTaxApp::KEY_ACCOMMODATION_COSTS);
		$config['fee_city_tax_type'] = 'additional_accommodation';
		$config['accommodation_category_ids'] = $school->getMeta(CityTaxApp::KEY_ACCOMMODATION_CATEGORIES);
		$config['percentage_city_tax'] = $school->getMeta(CityTaxApp::KEY_CITY_TAX_CURRENT);

		$noCityTaxMaxDays = (int)$school->getMeta(CityTaxApp::KEY_CITY_TAX_CALCULATE_MAX_DAYS);

		// Wenn Einstellungen fehlen kann hier abgebrochen werden.
		if(
			empty($config['fee_city_tax_ids']) ||
			empty($config['accommodation_category_ids']) ||
			empty($config['percentage_city_tax'])
		) {
			return null;
		}
				
		$cityTaxItems = [];
		foreach($items as $item) {
			if(
				$item->type === $config['fee_city_tax_type'] && 
				in_array($item->type_id, $config['fee_city_tax_ids'])
			) {
				$item->amount = 0;
				$item->amount_net = 0;
				$cityTaxItems[$item->parent_booking_id] = $item;
			}
		}

		// Passendes Item kommt nicht vor
		if (empty($cityTaxItems)) {
			return null;
		}
		
		$firstCityTaxItem = reset($cityTaxItems);

		// Zeitraum und Betrag der Unterkunfts-Items in Array schreiben
		$accommodationRelatedItems = [];
		foreach ($items as $item) {
			if (
				// Auch in CityTaxReport/CityTaxColumn
				in_array($item->type, ['accommodation', 'extra_nights', 'extra_weeks']) &&
				in_array($item->type_object_id, $config['accommodation_category_ids'])
			) {

				$accommodationRelatedItems[] = [
					'type_id' => $item->type_id,
					'from' => new Carbon($item->index_from),
					'until' => new Carbon($item->index_until),
					'amount' => $item->getAmount(),
					'amountNet' => $item->getAmount('net'),
					'amountCommission' => $item->getAmount('commission')
				];
				
			}
		}
		
		// Zeitlich sortieren
		usort($accommodationRelatedItems, function($a, $b) {

			if ($a['from'] == $b['from']) {
				return 0;
			}
			return ($a['from'] < $b['from']) ? -1 : 1;
			
		});
		
		// Das gibt es aktuell nicht als Einstellung, ist nur für Abwärtskompabilität vorhanden
		$calculationMaxDays = (int)$school->getMeta(CityTaxApp::KEY_CITY_TAX_CALCULATE_DAYS);
		if(empty($calculationMaxDays)) {
			$calculationMaxDays = null;
		}
		if(isset($firstCityTaxItem->additional_info['city_tax'][0]['calculate_max_days'])) {
			$calculationMaxDays = $firstCityTaxItem->additional_info['city_tax'][0]['calculate_max_days'];
		}
		
		$enhancedAccommodationItems = [];
		$prevUntil = null;
		$remaining = $calculationMaxDays;

		foreach ($accommodationRelatedItems as $accommodationRelatedItem) {
			// Bei Lücke: Topf zurücksetzen
			if (
				!$prevUntil || 
				!$prevUntil->equalTo($accommodationRelatedItem['from'])
			) {
				$remaining = $calculationMaxDays;
			}

			$days = $accommodationRelatedItem['from']->diffInDays($accommodationRelatedItem['until']);

			$accommodationRelatedItem['total_days'] = $days;
			if (
				(
					$remaining !== null &&
					$remaining <= 0
				) || 
				$days <= 0
			) {
				// nichts mehr anrechenbar
				$accommodationRelatedItem['factor'] = 0.0;
				$accommodationRelatedItem['calc_days'] = 0;
			} else {
				if($remaining === null) {
					$calc_days = $days;
				} else {
					$calc_days = min($days, $remaining);
					$remaining -= $calc_days;
				}        
				$accommodationRelatedItem['factor'] = $calc_days / $days;   // genau dieser Faktor wird später verwendet
				$accommodationRelatedItem['calc_days'] = $calc_days;        
			}

			$enhancedAccommodationItems[] = $accommodationRelatedItem;
			$prevUntil = $accommodationRelatedItem['until'];
		}

		if(empty($enhancedAccommodationItems)) {
			return null;
		}

		// Wurde im City-Tax Item schon ein Factor festgelegt?
		if(isset($firstCityTaxItem->additional_info['city_tax'][0]['city_tax'])) {
			
			$taxFactor = $firstCityTaxItem->additional_info['city_tax'][0]['city_tax'];
			
		} else {
			
			$taxFactor = $school->getMeta(CityTaxApp::KEY_CITY_TAX_CURRENT)/100;

			$newTaxFactor = $school->getMeta(CityTaxApp::KEY_CITY_TAX_NEW)/100;		
			$newFactorValidFrom = $school->getMeta(CityTaxApp::KEY_CITY_TAX_NEW_VALID_FROM);

			if(
				!empty($newFactorValidFrom) &&
				!empty($newTaxFactor)
			) {
				$newFactorValidFrom = new \DateTime($newFactorValidFrom);

				if(!$now instanceof \DateTime) {
					$now = new \DateTime;
				}
				if($now >= $newFactorValidFrom) {
					$taxFactor = $newTaxFactor;
				}
			}
			
		}

		// Da alles neu berechnet wird, muss diese Info zurückgesetzt werden
		foreach($cityTaxItems as $cityTaxItem) {
			$additionalInfo = $cityTaxItem->additional_info;	
			$additionalInfo['city_tax'] = [];			
			$cityTaxItem->additional_info = $additionalInfo;
		}

		#$calculations = [];
		foreach ($enhancedAccommodationItems as $item) {

			$amount = 0;
			$amountNet = 0;
			$amountCommission = 0;

			$cityTaxFrom = $cityTaxUntil = null;
			
			// Wenn es nicht pro Unterkunftsposition eine City-Tax-Position gibt
			$cityTax = $cityTaxItems[$item['type_id']] ?? $firstCityTaxItem;
			
			if(empty($cityTax->index_from)) {
				$cityTaxFrom = $item['from'];
			} else {
				$cityTaxFrom = new Carbon($cityTax->index_from);
			}
			
			$cityTaxFrom = min($item['from'], $cityTaxFrom);
			
			if(empty($cityTax->index_until)) {
				$cityTaxUntil = $item['until'];
			} else {
				$cityTaxUntil = new Carbon($cityTax->index_until);
			}
			
			$cityTaxUntil = max($item['until'], $cityTaxUntil);
			
			$calculationDays = $item['calc_days'];
			$days = $item['total_days'];
			
			// Nur die ersten 21 Tage berechnen
			$factor = $item['factor'];

			if(
				$noCityTaxMaxDays > 0 &&
				$days > $noCityTaxMaxDays
			) {
				continue;
			}

			$additionalInfo = $cityTax->additional_info;
	
			$additionalInfo['city_tax'][] = [
				'total_amount' => $item['amount'],
				'total_amount_net' => $item['amountNet'],
				'total_amount_commission' => $item['amountCommission'],
				'factor' => $factor,
				'city_tax' => $taxFactor,
				'total_days' => $days,
				'days' => $calculationDays,
				'calculate_max_days' => $calculationMaxDays,
				'accommodation_from' => $item['from']->toDateString(),
				'accommodation_until' => $item['until']->toDateString(),
				'inquiry_accommodation_id' => $item['type_id']
			];
			
			$amount += $item['amount'] * $factor;
			$amountNet += $item['amountNet'] * $factor;
			$amountCommission += $item['amountCommission'] * $factor;

			$amountCityTax = round($amount * $taxFactor, 2);

			$cityTax->additional_info = $additionalInfo;
			$cityTax->index_from = $cityTaxFrom->toDateString();
			$cityTax->index_until = $cityTaxUntil->toDateString();
			$cityTax->amount = $cityTax->amount + $amountCityTax;
			$cityTax->amount_net = $cityTax->amount_net + $amountCityTax;
			#$cityTax->amount_provision = 0;

		}

		// Leere City-Tax Items entfernen
		foreach ($mixedItems as $key => $item) {

			if(
				is_object($item) &&
				$item->type === $config['fee_city_tax_type'] && 
				in_array($item->type_id, $config['fee_city_tax_ids']) &&
				bccomp($item->amount, 0, 5) === 0
			) {
				unset($mixedItems[$key]);
			} elseif(
				is_array($item) &&
				$item['type'] === $config['fee_city_tax_type'] && 
				in_array($item['type_id'], $config['fee_city_tax_ids']) &&
				bccomp($item['amount'], 0, 5) === 0
			) {
				unset($mixedItems[$key]);
			}
		}

		return $cityTaxItems;
	}
	
}
