<?php

namespace TsContactLogin\Combination\Handler;

use Exception;
use TsRegistrationForm\Generator\CombinationGenerator;

class BookClass extends HandlerAbstract
{
	/**
	 * @return void
	 * @throws Exception
	 */
	protected function handle(): void
	{
		$allVars = $this->login->getRequest()->getAll();
		$customers = $this->login->getTravellers();
		$customer = $customers[$allVars['customerId']] ?? null;
		if ($customer) {
			$inquiries = $customer->getInquiries(false, true);
			$inquiry = null;
			foreach ($inquiries as $existingInquiry) {
				if (
					$existingInquiry->getSchool()->getId() == $allVars['schoolId'] &&
					empty($existingInquiry->service_from) &&
					$existingInquiry->active
				) {
					$inquiry = $existingInquiry;
				}
			}
			if (is_null($inquiry)) {
				$school = \Ext_Thebing_School::getInstance((int)$allVars['schoolId']);
				$client = $school->getClient();
				$inboxList = $client->getInboxList();
				$inbox = reset($inboxList);
				/*
				* Creating new Inquiry
				*/
				$newInquiry = new \Ext_TS_Inquiry();
				$newInquiry->type = \Ext_TS_Inquiry::TYPE_BOOKING;
				$newInquiry->payment_method = 1;
				$newInquiry->currency_id = $school->getCurrency();
				$newInquiry->inbox = $inbox['short'];

				try {
					$newInquiry->addJoinTableObject('travellers', $customer);
					$newInquiry->addJoinTableObject('bookers', $inquiries[0]->getBooker());
					$parent = $inquiries[0]->getParent();
					if (!is_null($parent)) {
						$newInquiry->setJoinedObjectChild('other_contacts', $parent);
					}
					$journey = $newInquiry->getJourney();
					$journey->school_id = $school->id;
					$journey->type = \Ext_TS_Inquiry_Journey::TYPE_BOOKING;
					$journey->productline_id = $school->getProductLineId();

					$validateInquiry = $newInquiry->validate();
					if ($validateInquiry === true) {
						$newInquiry->save();
						\Ext_Gui2_Index_Stack::add('ts_inquiry', $newInquiry->getId(), 1);
						\Ext_Gui2_Index_Stack::executeCache();
						\Ext_Gui2_Index_Stack::save();
						$inquiry = $newInquiry;
					} else {
						throw new \InvalidArgumentException('Validate Inquiry failed.');
					}
				} catch (Exception $e) {
					throw new \InvalidArgumentException('Creating Inquiry failed.');
				}
			}

			if ($inquiry) {
				$combination = \Ext_TC_Frontend_Combination::getUsageObjectByKey($allVars['combination']);
				if (!$combination instanceof CombinationGenerator) {
					throw new \InvalidArgumentException('Combination does not exist or is of wrong type.');
				}
				/** @var \TsFrontend\Entity\InquiryFormProcess $process */
				$process = \TsFrontend\Entity\InquiryFormProcess::query()
					->where('inquiry_id', $inquiry->id)
					->where('combination_id', $combination->getCombination()->getId())
					->where('valid_until', '>=', date('Y-m-d'))
					->where(function (\Core\Database\WDBasic\Builder $query) {
						$query->whereNull('submitted')
							->orWhere('multiple', 1);
					})
					->first();

				if ($process === null) {
					$process = new \TsFrontend\Entity\InquiryFormProcess();
					$process->inquiry_id = $inquiry->id;
					$process->combination_id = $combination->getCombination()->getId();
					$process->valid_until = \Carbon\Carbon::now()->addYear()->toDateString(); // Aktuell immer ein Jahr Gültigkeit
					$process->multiple = 0;
					$process->save();
				}

				$this->login->assign('inquiry', $inquiry);
				$this->login->assign('process', $process);
				$this->login->assign('combination', $allVars['combination']);
			}
		}
	}
}