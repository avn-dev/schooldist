<?php

namespace TsRegistrationForm\Factory;

use TsFrontend\Entity\BookingTemplate;
use TsFrontend\Entity\InquiryFormProcess;
use TsRegistrationForm\Generator\CombinationGenerator;
use TsRegistrationForm\Helper\BuildInquiryHelper;

class BookingByKeyFactory {

	private CombinationGenerator $combination;

	public function __construct(CombinationGenerator $combination) {
		$this->combination = $combination;
	}

	public function make(?string $key): ?\Ext_TS_Inquiry {

		if (empty($key)) {
			return null;
		}

		// Bei Formular für neue Buchung nach Buchungs-Template schauen
		if ($this->combination->getForm()->isCreatingBooking()) {

			/** @var BookingTemplate $template */
			$template = BookingTemplate::query()
				->where('form_id', $this->combination->getForm()->id)
				->where('key', $key)
				->whereIn('school_id', $this->combination->getForm()->schools)
				->first();

			if ($template !== null) {
				return $this->prepareInquiry($template->createBooking($this->combination), $template);
			}

		} else {
			// Ansonsten wird Prozess für bestehende Buchung benötigt

			/** @var InquiryFormProcess $process */
			$process = InquiryFormProcess::query()
				->where('combination_id', $this->combination->getCombination()->id)
				->where('key', $key)
				->where('valid_until', '>=', date('Y-m-d'))
				->where(function (\Core\Database\WDBasic\Builder $query) {
					$query->whereNull('submitted')
						->orWhere('multiple', 1);
				})
				->first();

			if ($process !== null) {
				return $this->prepareInquiry($process->getInquiry(), $process);
			}

		}

		return null;

	}

	private function prepareInquiry(\Ext_TS_Inquiry $inquiry, BookingTemplate|InquiryFormProcess $process): \Ext_TS_Inquiry {

		$helper = new BuildInquiryHelper($this->combination);
		$helper->prepareInquiry($inquiry);

		$this->combination->getBookingGenerator()->setProcess($process);

		// Flex-Werte setzen für getObjectByAlias()
		if ($process instanceof InquiryFormProcess) {
			$inquiry->transients['flex'] = new \ArrayObject($inquiry->getFlexValues(), \ArrayObject::ARRAY_AS_PROPS);
		}

		return $inquiry;

	}

}