<?php

namespace TsRegistrationForm\Helper;

use Illuminate\Support\Arr;
use TsRegistrationForm\Interfaces\RegistrationCombination;

class PriceBlockHelper {

	/**
	 * @var RegistrationCombination
	 */
	private $combination;

	/**
	 * @var \Ext_TS_Inquiry
	 */
	private $inquiry;

	/**
	 * @var \Ext_Thebing_Form_Page_Block
	 */
	private $priceBlock;

	/**
	 * @var array
	 */
	private $config;

	/**
	 * @var \Ext_Thebing_Currency
	 */
	private $currency;

	/**
	 * @var \Ext_Thebing_Gui2_Format_Date
	 */
	private $dateFormat;

	public function __construct(RegistrationCombination $combination, \Ext_TS_Inquiry $inquiry) {

		$this->combination = $combination;
		$this->inquiry = $inquiry;

		$this->config = (new \Core\Helper\Bundle())->readBundleFile('TsRegistrationForm', 'registration');
		$this->currency = $this->inquiry->getCurrency(true);
		$this->currency->bThinspaceSign = true;
		$this->dateFormat = new \Ext_Thebing_Gui2_Format_Date('frontend_date_format', $this->combination->getSchool()->id);

		$priceBlocks = $combination->getForm()->getFilteredBlocks(function (\Ext_Thebing_Form_Page_Block $oBlock) {
			return ($oBlock instanceof \Ext_Thebing_Form_Page_Block_Virtual_Prices_Display);
		});
		$this->priceBlock = reset($priceBlocks); // Logik des ersten Blocks existiert auch nochmal in FormPagesGenerator
		
	}

	public function generatePriceData(): array {

		$items = $this->getGroupedItems();
		$rows = [];

		$this->buildCourseRows($items, $rows);
		$this->buildAccommodationRows($items, $rows);
		$this->buildTransferRows($items, $rows);
		$this->buildInsuranceRows($items, $rows);
		$this->buildFeeRows($items, $rows);
		$this->buildActivityRows($items, $rows);
		$this->buildSpecialRows($items, $rows);

		$itemsFlat = Arr::flatten($items, 1);

		return [
			'total' => $this->getTotalAmount($itemsFlat),
			'deposit' => $this->getDepositAmount($itemsFlat),
			'blocks' => $this->generateBlocks($rows)
		];

	}

	private function getGroupedItems(): array {

		$docType = $this->combination->getForm()->getSchoolSetting($this->combination->getSchool(), 'generate_invoice') ? 'brutto' : 'proforma_brutto';

		$helper = new BuildInquiryHelper($this->combination);
		$docItems = $helper->buildDocumentVersionItems($this->inquiry, $docType);

		$items = array_fill_keys(array_keys($this->config['price_block_mapping']), []);
		foreach ($docItems as $docItem) {
			$items[$docItem['type']][] = $docItem;
		}

		return $items;
	}

	private function buildCourseRows(array $items, array &$rows) {

		foreach ($items['course'] as $item) {

			$course = \Ext_Thebing_Tuition_Course::getInstance($item['type_object_id']);
			$weeks = $item['additional_info']['course_weeks'];
			$units = '';

			if ($item['additional_info']['course_units'] > 0) {
				$units = ' / ' . $item['additional_info']['course_units'] . ' ' . $this->priceBlock->getTranslationChoice('priceUnit', $item['additional_info']['course_units'], $this->combination->getLanguage());
			}

			$subtitle = [];
			if (!empty($item['additional_info']['courselanguage_id']) && count($course->course_languages) > 1) {
				$language = \Ext_Thebing_Tuition_LevelGroup::getInstance($item['additional_info']['courselanguage_id']);
				$subtitle[] = $language->getName($this->combination->getLanguage()->getLanguage());
			}

			$description = '';
			if ($course->getType() !== 'exam') {
				$description = sprintf('%s %s%s', $weeks, $this->priceBlock->getTranslationChoice('priceWeek', $weeks, $this->combination->getLanguage()), $units);
			}

			$rows[$this->getBlock($item)][] = [
				'title' => $course->getFrontendName($this->combination->getLanguage()->getLanguage()) ?: $course->getName($this->combination->getLanguage()->getLanguage()),
				'price' => $this->formatAmount($item['amount_with_tax']),
				'description' => [
					...$subtitle,
					$description,
					$this->formatDaterange($item['from'], $item['until'])
				]
			];

		}

	}

	private function buildAccommodationRows(array $items, array &$rows) {

		$block = $this->combination->getForm()->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS);

		foreach ($items['accommodation'] as $item) {

			$weeks = $item['additional_info']['accommodation_weeks'];
			$category = \Ext_Thebing_Accommodation_Category::getInstance($item['additional_info']['accommodation_category_id']);
			$room = \Ext_Thebing_Accommodation_Roomtype::getInstance($item['additional_info']['accommodation_roomtype_id']);
			$board = \Ext_Thebing_Accommodation_Meal::getInstance($item['additional_info']['accommodation_meal_id']);

			$rows[$this->getBlock($item)][] = [
				'title' => $category->getName($this->combination->getLanguage()->getLanguage()),
				'price' => $this->formatAmount($item['amount_with_tax']),
				'description' => [
					$weeks . ' ' . $this->priceBlock->getTranslationChoice('priceWeek', $weeks, $this->combination->getLanguage()),
					$room->getName($this->combination->getLanguage()->getLanguage()) . ' / ' . $board->getName($this->combination->getLanguage()->getLanguage()),
					$this->formatDaterange($item['from'], $item['until'])
				]
			];
		}

		foreach ($items['extra_nights'] as $item) {

			$nights = $item['additional_info']['nights'];

			$rows[$this->getBlock($item)][] = [
				'title' => $nights . ' ' . $block->getTranslationChoice('extra', $nights, $this->combination->getLanguage()),
				'price' => $this->formatAmount($item['amount_with_tax']),
				'description' => [
					$this->formatDaterange($item['from'], $item['until'])
				]
			];

		}

		foreach ($items['extra_weeks'] as $item) {

			$weeks = $item['additional_info']['extra_weeks'];

			$rows[$this->getBlock($item)][] = [
				'title' => $weeks['additional_info']['extra_weeks'] . ' ' . $block->getTranslationChoice('extraWeek', $weeks, $this->combination->getLanguage()),
				'price' => $this->formatAmount($item['amount_with_tax']),
				'description' => [
					$this->formatDaterange($item['from'], $item['until'])
				]
			];

		}

	}

	private function buildTransferRows(array $items, array &$rows) {

		$block = $this->combination->getForm()->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS);

		foreach ($items['transfer'] as $item) {

			$transferTypes = [];
			if (
				// Anreise oder Abreise (einzelne Positionen)
				!isset($item['additional_info']['transfer_arrival_id']) &&
				!isset($item['additional_info']['transfer_departure_id'])
			) {
				if ($item['additional_info']['transfer_type'] === 'arrival') {
					$title = $block->getTranslation('arrival', $this->combination->getLanguage());
					$transferTypes = [\Ext_TS_Inquiry_Journey_Transfer::TYPE_ARRIVAL];
				} elseif ($item['additional_info']['transfer_type'] === 'departure') {
					$title = $block->getTranslation('departure', $this->combination->getLanguage());
					$transferTypes = [\Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE];
				} else {
					// Sollte eigentlich niemals vorkommen
					$title = 'none';
				}
			} else {
				// Zwei-Wege-Transfer (nur eine Position)
				$title = $block->getTranslation('arrival_departure', $this->combination->getLanguage());
				$transferTypes = [\Ext_TS_Inquiry_Journey_Transfer::TYPE_ARRIVAL, \Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE];
			}

			// Zugehörige Objekte suchen, da der ganze Item-Spaß ansonsten nur mit IDs funktioniert
			// Zudem stehen die Infos ja nicht im Item, da aus zwei Items eins wird, aber an diversen Stellen wieder zwei Items benötigt werden
			/** @var \Ext_TS_Inquiry_Journey_Transfer[] $journeyTransfers */
			$journeyTransfers = array_filter($this->inquiry->getTransfers('', true), function (\Ext_TS_Inquiry_Journey_Transfer $journeyTransfer) use ($transferTypes) {
				return in_array($journeyTransfer->transfer_type, $transferTypes);
			});

			$description = [];
			foreach ($journeyTransfers as $oJourneyTransfer) {
				$date = $oJourneyTransfer->getTransferDate();
				$note = $oJourneyTransfer->getLocationName('start', true, $this->combination->getLanguage()) . ' - ';
				$note .= $oJourneyTransfer->getLocationName('end', true, $this->combination->getLanguage()) . ' ';
				$note .= '(' . $this->dateFormat->format($date) . ')';
				$description[] = $note;
			}

			$rows[$this->getBlock($item)][] = [
				'title' => $title,
				'price' => $this->formatAmount($item['amount_with_tax']),
				'description' => $description
			];

		}

	}

	private function buildInsuranceRows(array $items, array &$rows) {

		foreach ($items['insurance'] as $item) {

			$insurance = \Ext_Thebing_Insurance::getInstance($item['type_object_id']);

			$rows[$this->getBlock($item)][] = [
				'title' => $insurance->getName($this->combination->getLanguage()->getLanguage()),
				'price' => $this->formatAmount($item['amount_with_tax']),
				'description' => [
					$this->formatDaterange($item['from'], $item['until'])
				]
			];

		}

	}

	private function buildFeeRows(array $items, array &$rows) {

		$fees = array_merge($items['additional_course'], $items['additional_accommodation'], $items['additional_general']);

		foreach ($fees as $item) {

			$fee = \Ext_Thebing_School_Additionalcost::getInstance($item['type_id']);
			$type = $fee->charge === 'semi' ? 'extras' : $this->getBlock($item);

			$rows[$type][] = [
				'title' => $fee->getName($this->combination->getLanguage()->getLanguage()),
				'price' => $this->formatAmount($item['amount_with_tax']),
				'description' => []
			];

		}

	}

	private function buildSpecialRows(array $items, array &$rows) {

		foreach ($items['special'] as $item) {

			if($item['object'] instanceof \Ext_TS_Inquiry_Journey_Course) {
				$course = $item['object']->getCourse();
				$description = $course->getFrontendName($this->combination->getLanguage()->getLanguage()) ?: $course->getName($this->combination->getLanguage()->getLanguage());
			} elseif($item['object'] instanceof \Ext_TS_Inquiry_Journey_Accommodation) {
				$room = $item['object']->getRoomType();
				$board = $item['object']->getMeal();
				$description = $room->getName($this->combination->getLanguage()->getLanguage()) . ' / ' . $board->getName($this->combination->getLanguage()->getLanguage());
			} else {
				$description = $item['description'];
			}

			$rows[$this->getBlock($item)][] = [
				'title' => $this->priceBlock->getTranslation('priceSpecial', $this->combination->getLanguage()),
				'price' => $this->formatAmount($item['amount_with_tax']),
				'description' => [
					$description
				]
			];

		}

	}
	
	private function buildActivityRows(array $items, array &$rows) {

		foreach ($items['activity'] as $item) {

			$activity = \TsActivities\Entity\Activity::getInstance($item['type_object_id']);
			$weeks = $item['additional_info']['activity_weeks'];
			$blocks = $item['additional_info']['activity_blocks'];

			$description = sprintf('%d %s', $weeks, $this->priceBlock->getTranslationChoice('priceWeek', $weeks, $this->combination->getLanguage()));
			if (!empty($blocks)) {
				$description .= sprintf(' / %d %s', $blocks, $this->priceBlock->getTranslationChoice('priceUnit', $blocks, $this->combination->getLanguage()));
			}

			$price = $this->formatAmount($item['amount_with_tax']);
			if ($activity->isFreeOfCharge()) {
				$price = $this->combination->getLanguage()->translate('kostenlos');
			}

			$rows[$this->getBlock($item)][] = [
				'title' => $activity->getName($this->combination->getLanguage()->getLanguage()),
				'price' => $price,
				'description' => [
					$description,
					$this->formatDaterange($item['from'], $item['until'])
				]
			];

		}

	}

	private function getTotalAmount(array $items): string {

		$amount = array_sum(array_column($items, 'amount_with_tax'));
		return $this->formatAmount($amount);

	}

	private function getDepositAmount(array $items): ?string {

		$block = $this->combination->getForm()->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_PAYMENT);
		if ($block === null) {
			return null;
		}

		if (!$block->getSetting('pay_deposit')) {
			return null;
		}

		$helper = new BuildInquiryHelper($this->combination);
		$deposit = $helper->generateDepositItem($this->inquiry, collect($items));

		if ($deposit === null) {
			return null;
		}

		return $this->formatAmount($deposit['amount']);

	}

	private function generateBlocks(array $rows): array {

		$blocks = [];
		foreach ($this->config['price_block_blocks'] as $key => $translation) {
			if (!empty($rows[$key])) {
				$blocks[] = [
					'type' => $key,
					'title' => $this->priceBlock->getTranslation($translation, $this->combination->getLanguage()),
					'items' => $rows[$key]
				];
			}
		}

		return $blocks;

	}

	private function getBlock(array $item): string {
		return Arr::get($this->config['price_block_mapping'], $item['type'], 'extras');
	}

	private function formatAmount($amount): string {
		return \Ext_Thebing_Format::Number($amount, $this->currency, $this->combination->getSchool(), true, 2);
	}

	private function formatDaterange($from, $until): string {

		if ($from === $until) {
			return $this->dateFormat->format($from);
		}

		return $this->dateFormat->format($from).' – '.$this->dateFormat->format($until);

	}

}