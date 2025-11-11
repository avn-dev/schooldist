<?php

namespace TsAccommodation\Entity\Request;

use TsAccommodation\Entity\Request;

class Recipient extends \Ext_Thebing_Basic {

	use \Tc\Traits\Placeholder, \Ts\Traits\EntityCommunication;

	protected $_sTable = 'ts_accommodation_requests_recipients';
	protected $_sTableAlias	= 'ts_arr';
	
	protected $_sPlaceholderClass = \TsAccommodation\Service\Placeholder\Request\RecipientPlaceholder::class;

	protected $_aJoinedObjects = [
        'request' => [
            'class' => Request::class,
            'type' => 'parent',
            'key' => 'request_id',
            'on_delete' => 'cascade',
			'bidirectional' => true
        ],
        'provider' => [
            'class' => \Ext_Thebing_Accommodation::class,
            'type' => 'parent',
            'key' => 'accommodation_provider_id'
        ]
    ];
	 
	public function getAcceptLink() {
		return \Core\Helper\Routing::generateUrl('TsAccommodationLogin.accommodation_request_availability', ['task'=>'accept', 'key'=>$this->key]);
	}
	 
	public function getRejectLink() {
		return \Core\Helper\Routing::generateUrl('TsAccommodationLogin.accommodation_request_availability', ['task'=>'reject', 'key'=>$this->key]);
	}

	public function setAdditionalMessageRelations(array &$relations) {
		
		$relations[] = [
			'relation'=> \Ext_Thebing_Accommodation::class,
			'relation_id' => $this->getJoinedObject('provider')->id
		];
		
		$inquiryAccommodation = $this->getJoinedObject('request')->getJoinedObject('inquiry_accommodation');
		
		$relations[] = [
			'relation'=> \Ext_TS_Inquiry::class,
			'relation_id' => $inquiryAccommodation->getInquiry()->id
		];
		
	}

}
