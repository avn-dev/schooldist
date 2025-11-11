<?php

namespace TsContactLogin\Combination\Handler;

use Exception;
use MVC_Request;

class Personal extends HandlerAbstract {
	protected function handle(): void {
		$allVars = $this->login->getRequest()->getAll();
		$validations = [];
		$schools = [];
		$inquiries = $this->login->getInquiries();
		$parent = null;
		foreach ($inquiries as $inquiry) {
			$school = $inquiry->getSchool();
			$schools[$school->id] = $school;

			if (is_null($parent)) {
				// Einen existierenden Parent Kontakt finden
				$parent = $inquiry->getParent();
			}
		}

		// Wenn kein Parent existiert muss ein neuer erstellt werden damit alle den gleichen haben
		if (is_null($parent)) {
			$parent = $this->login->getInquiry()->getParent(true);
		}

		// Save data
		if ($this->login->getRequest()->exists("save")) {
			foreach ($inquiries as $inquiry) {
				$customer = $inquiry->getCustomer();
				$customerAddress = $customer->getAddress('contact');
				$booker = $inquiry->getBooker();
				if ($booker) {
					$bookerAddress = $booker->getAddress('billing');
				}
				// Prüfen ob schon ein Parent existiert. Falls nicht, den vorhanden Parent aus anderen Buchungen übernehmen
				$existingParent = $inquiry->getParent();
				if (is_null($existingParent)) {
					$inquiry->setJoinedObjectChild('other_contacts', $parent);
				}

				// Customer data
				if (isset($allVars['save']['customer_' . $customer->id])) {
					foreach ($allVars['save']['customer_' . $customer->id] as $field => $var) {
						switch ($field) {
							case 'salutation':
							case 'firstname':
							case 'lastname':
							case 'title':
							case 'gender':
							case 'birthday':
							case 'nationality':
							case 'language':
							case 'email':
								$customer->$field = $var;
								break;
							case 'phone_private':
							case 'phone_mobile':
								$customer->setDetail($field, $var);
								break;
							case 'address':
							case 'address_add':
							case 'city':
							case 'state':
							case 'zip':
								$customerAddress->$field = $var;
						}
					}
				}

				// Booker data
				if (isset($allVars['save']['booker']) && $booker) {
					foreach ($allVars['save']['booker'] as $field => $var) {
						switch ($field) {
							case 'salutation':
							case 'firstname':
							case 'lastname':
							case 'title':
							case 'gender':
							case 'birthday':
							case 'nationality':
							case 'language':
							case 'email':
								$booker->$field = $var;
								break;
							case 'phone_private':
							case 'phone_mobile':
								$booker->setDetail($field, $var);
								break;
							case 'address':
							case 'address_add':
							case 'city':
							case 'state':
							case 'zip':
								$bookerAddress->$field = $var;
						}
					}
				}

				// Emergency contact data
				if (isset($allVars['save']['emergency'])) {
					foreach ($allVars['save']['emergency'] as $field => $var) {
						switch ($field) {
							case 'salutation':
							case 'firstname':
							case 'lastname':
							case 'email':
								$parent->$field = $var;
								break;
							case 'phone_private':
							case 'phone_mobile':
								$parent->setDetail($field, $var);
								break;
						}
					}
				}

				// Validate
				try {
					$validateInquiry = $inquiry->validate();
					if ($validateInquiry !== true) {
						$validations[$inquiry->id] = $validateInquiry;
					}
				} catch (Exception $e) {
					// TODO
					return;
				}
			}
			$validationsForTemplate = [];
			if (empty($validations)) {
				foreach ($inquiries as $inquiry) {
					$inquiry->save();
				}
			} else {
				// Create validation errors for template
				foreach ($validations as $inquiryValidation) {
					foreach ($inquiryValidation as $errorObjectKey => $error) {
						if (str_contains($errorObjectKey, '[other_contacts]')) { //error was in emergency contact
							$validationsForTemplate['emergency'][] = $error[0];
						} elseif (str_contains($errorObjectKey, 'travellers')) { //error was in a traveller
							$validationsForTemplate[explode(".", $errorObjectKey)[0]][] = $error[0];
						} elseif (str_contains($errorObjectKey, 'bookers')) {
							$validationsForTemplate['booker'][] = $error[0];
						}
					}
				}
			}
		}

		// Set template data
		$this->login->assign('valErrors', $validationsForTemplate);
		$this->login->setTask('showPersonalData');
		$this->login->assign('travellers', $this->login->getTravellers());
		$inquiry = $this->login->getInquiry();
		$this->login->assign('booker', $inquiry->getBooker());
		$this->login->assign('schools', $schools);
		$this->login->assign('emergencyContact', $inquiry->getParent());
	}

}