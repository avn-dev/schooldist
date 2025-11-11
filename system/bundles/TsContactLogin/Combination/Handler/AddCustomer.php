<?php

namespace TsContactLogin\Combination\Handler;

use DB;
use Exception;
use MVC_Request;
use TsRegistrationForm\Helper\BuildInquiryHelper;
use TsRegistrationForm\Helper\FormValidatorHelper;
use TsRegistrationForm\Helper\ServiceDatesHelper;
use TsRegistrationForm\Service\InquiryBuilder;
use TsRegistrationForm\Generator\CombinationGenerator;

class AddCustomer extends HandlerAbstract {
	protected function handle(): void {

		if ($this->login->getRequest()->exists("addCustomer")) {

			$allVars = $this->login->getRequest()->getAll();
			$schools = \Ext_Thebing_Client::getSchoolList(true);
			$school = null;
			if (
				isset($allVars['addCustomer']['customer_0']['school_id']) &&
				isset($schools[(int)$allVars['addCustomer']['customer_0']['school_id']])
			) {
				$school = \Ext_Thebing_School::getInstance((int)$allVars['addCustomer']['customer_0']['school_id']);
			}

			if (!$school) {
				throw new Exception("School not found");
			}

			/*
			 * Get one of existing Inquiries, does not matter wich one, all should have same data
			 */
			$booker = $this->login->getInquiry()->getBooker();
			$parent = $this->login->getInquiry()->getParent();
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
				$newInquiry->addJoinTableObject('bookers', $booker);
				if ($parent) {
					$newInquiry->setJoinedObjectChild('other_contacts', $parent);
				}
				$journey = $newInquiry->getJourney();
				$journey->school_id = $school->id;
				$journey->type = \Ext_TS_Inquiry_Journey::TYPE_BOOKING;
				$journey->productline_id = $school->getProductLineId();
				$contact = $newInquiry->getCustomer();
				$contact->bCheckGender = false;
				$contact->firstname = $allVars['addCustomer']['customer_0']['firstname'];
				$contact->lastname = $allVars['addCustomer']['customer_0']['lastname'];
				$contact->birthday = $allVars['addCustomer']['customer_0']['birthday'];
				$contact->gender = (int)$allVars['addCustomer']['customer_0']['gender'];
				$contact->language = $allVars['addCustomer']['customer_0']['language'];
				$contact->nationality = $allVars['addCustomer']['customer_0']['nationality'];
				$contact->corresponding_language = $school->getLanguage();
				$customerNumberGenerator = new \Ext_Thebing_Customer_CustomerNumber($newInquiry);
				$customerNumberGenerator->saveCustomerNumber(false, false);

				$validateInquiry = $newInquiry->validate();
				if ($validateInquiry === true) {
					$newInquiry->save();
					\Ext_Gui2_Index_Stack::add('ts_inquiry', $newInquiry->getId(), 1);
					\Ext_Gui2_Index_Stack::executeCache();
					\Ext_Gui2_Index_Stack::save();
					$this->login->addInquiry($newInquiry);
					$this->login->addTraveller($contact);
				} else {
					// Frontend validation. Errors here are not meant for customer.
				}
			} catch (Exception $e) {
				// Frontend validation. Errors here are not meant for customer.
			}
			new Personal($this->login);

		}
	}
}