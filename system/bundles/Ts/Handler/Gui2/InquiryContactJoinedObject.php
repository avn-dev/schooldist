<?php

namespace Ts\Handler\Gui2;

class InquiryContactJoinedObject extends \Gui2\Handler\JoinedObject {
	
	public function get(\WDBasic $entity): iterable {
		
		$data = \DB::table('tc_contacts', 'tc_c_e')
			->select('tc_c_e.*', 'ts_itc.type')
			->join('ts_inquiries_to_contacts as ts_itc', 'tc_c_e.id', '=', 'ts_itc.contact_id')
			->whereIn('ts_itc.type', \Ext_TS_Inquiry_Index_Gui2_Data::getOtherContactsTypes())
			->where('ts_itc.inquiry_id', '=', $entity->id)
			->get();
		
		$contacts = [];
		foreach($data as $contactData) {
			
			$type = \Illuminate\Support\Arr::pull($contactData, 'type');
			
			$contact = \Ext_TS_Inquiry_Contact_Emergency::getObjectFromArray($contactData);
			$contact->type = $type;
			
			$contacts[] = $contact;
			
		}
		
		return $contacts;		
	}

	public function delete(\WDBasic $entity, \WDBasic $child) {

		\DB::executePreparedQuery('DELETE FROM ts_inquiries_to_contacts WHERE inquiry_id = :inquiry_id AND contact_id = :contact_id', [
			'inquiry_id' => $entity->id,
			'contact_id' => $child->id
		]);
		
		$child->delete();
		
	}
		
	public function save(\WDBasic $entity, \WDBasic $child) {
		
		if($child->isEmpty()) {
			$child->delete();
			return;
		}
		
		$validate = $child->validate();
		
		if($validate === true) {		
			
			$child->save();
			
			\DB::insertData('ts_inquiries_to_contacts', [
				'inquiry_id' => $entity->id,
				'contact_id' => $child->id,
				'type' => $child->type
			], true, true);
			
		}
		
	}
	
	public function validate(\WDBasic $entity, \WDBasic $child): mixed {
		
		$validate = $child->validate();
		
		if($validate !== true) {
			foreach($validate as $errorKey=>$error) {
				if(strpos($errorKey, 'email') !== false) {
					$validate['[email][tc_c_e]['.$child->getId().'][other_contacts]'] = $error;
					unset($validate[$errorKey]);
				}
			}
		}
		
		return $validate;
		# [contacts_to_emailaddresses[4194].tc_e.email]
		# [email][tc_c_e][3352][other_contacts]
		# 
		# 
		# save[b56eab683e450abb7100bfa45fc238fd][ID_2947][email][tc_c_e][3352][other_contacts]
		
	}
		
}
