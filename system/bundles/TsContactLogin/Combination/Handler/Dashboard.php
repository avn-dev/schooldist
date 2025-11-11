<?php
namespace TsContactLogin\Combination\Handler;

class Dashboard extends HandlerAbstract {

	protected function getCustomerInfo(\Ext_TS_Contact $customer): array {
		$infos = [];
		$infos['customerObject'] = $customer;
		$infos['name'] =  $customer->firstname." ".$customer->lastname;
		$infos['inquiries'] = [];
		$inquiry_ids = $customer->getInquiries(false, false);
		$inquiries = $this->login->getActiveInquiries();
		foreach ($inquiry_ids as $inquiry_id) {
			if (!empty($inquiries[$inquiry_id])) {
				$infos['inquiries'][] = $inquiries[$inquiry_id];
			}
		}
		return $infos;
	}

	protected function handle(): void {
		$this->login->setTask('showIndexData');
		$infos = [];
		$contact = $this->login->getContact();
		$this->assign('name', $contact->firstname." ".$contact->lastname);
		if ($this->login->getLoginType() == 'booker') {
			$travellers = $this->login->getTravellers();
			foreach ($travellers as $traveller) {
				$infos[$traveller->id] = $this->getCustomerInfo($traveller);
				usort($infos[$traveller->id]['inquiries'], fn($a, $b) => $a->getFirstCourseStart(true) <=> $b->getFirstCourseStart(true));
			}
		} else {
			$customer = \Ext_TS_Inquiry_Contact_Traveller::getInstance($this->login->getContactId());
			$infos[$customer->id] = $this->getCustomerInfo($customer);
		}
		$this->assign('dashInfos', $infos);
	}

}