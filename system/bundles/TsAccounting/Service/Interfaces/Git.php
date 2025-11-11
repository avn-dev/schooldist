<?php

namespace TsAccounting\Service\Interfaces;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use TsAccounting\Dto\BookingStack\ExportFileContent;

class Git extends AbstractInterface
{
	
	public function generateDocumentItemStackEntries(array &$entries, \Ext_Thebing_Inquiry_Document_Version_Item $item) {

		if($item->type === 'course') {

			$version = $item->getVersion();
			$allItems = $version->getJoinedObjectChilds('items');
			
			$hasNegativeAdditional = !empty(array_filter($allItems, function ($item) {
				return str_starts_with($item->type, 'additional_') && $item->amount < 0;
			}));
			
			$traveller = $item->getInquiry()?->getTraveller();
			$bookedService = $item->getJourneyService();

			$course =& $entries[0];
			
			// Ersten Eintrag dreimal anhängen
			$tarifEntry = $studentNameEntry = $locationEntry = $emptyEntry = reset($entries);

			$course['git_type'] = 'course';
			$tarifEntry['git_type'] = 'tarif';
			$studentNameEntry['git_type'] = 'student';
			$locationEntry['git_type'] = 'location';
			$emptyEntry['git_type'] = 'empty';

			$tarifEntry['cost_center'] = $this->company->cost_center;
			$studentNameEntry['cost_center'] = $this->company->cost_center;
			$locationEntry['cost_center'] = $this->company->cost_center;
			$emptyEntry['cost_center'] = $this->company->cost_center;
			
			if($item->amount_discount == 0) {
				
				$tarifEntry['description'] = 'Tarif Normal';
				$tarifEntry['amount'] = 0;
				$tarifEntry['amount_default_currency'] = 0;
				
			} else {
				
				$discountEntry = $entries[1];
				
				$tarifEntry['amount'] = $discountEntry['amount'];
				$tarifEntry['amount_default_currency'] = $discountEntry['amount_default_currency'];
				
			}
			
			$emptyEntry['amount'] = 0;
			$emptyEntry['amount_default_currency'] = 0;
			$emptyEntry['description'] = '';
			
			$studentNameEntry['amount'] = 0;
			$studentNameEntry['amount_default_currency'] = 0;
			$studentNameEntry['description'] = $traveller->firstname.' '.$traveller->lastname;
			
			$locationEntry['amount'] = 0;
			$locationEntry['amount_default_currency'] = 0;
			$locationEntry['description'] = $bookedService->getCourseLanguageName('en');
			
//			if(!$hasNegativeAdditional) {
//				$entries[] = $tarifEntry;
//			}

			$entries[] = $studentNameEntry;
			$entries[] = $locationEntry;
			$entries[] = $emptyEntry;
			
		}
		
	}

	public function generateDocumentStackEntries(array &$entries) {

		$otherCourseItems = [];
	
		// Zuerst manuelle Kurs-Items entfernen
		foreach($entries as $entryKey=>$item) {

			if(
				isset($item['git_type']) &&
				in_array($item['git_type'], ['tarif','student','location','empty'])
			) {

				$otherCourseItems[] = $item;
				unset($entries[$entryKey]);

			}

		}
	
		// Manuelle Kurs-Items am Ende wieder anfügen
		foreach($otherCourseItems as $otherCourseItem) {
			$entries[] = $otherCourseItem;
		}

	}
	
}
