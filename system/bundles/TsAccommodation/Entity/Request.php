<?php

namespace TsAccommodation\Entity;

use TsAccommodation\Entity\Request\Recipient;

class Request extends \Ext_Thebing_Basic {

	protected $_sTable = 'ts_accommodation_requests';
	protected $_sTableAlias	= 'ts_ar';
	
	protected $_sPlaceholderClass = \TsAccommodation\Service\Placeholder\RequestPlaceholder::class;

	protected $_aJoinedObjects = [
        'recipients' => [
            'class' => Recipient::class,
            'type' => 'child',
            'key' => 'request_id',
            'on_delete' => 'cascade',
			'bidirectional' => true
        ],
        'inquiry_accommodation' => [
            'class' => \Ext_TS_Inquiry_Journey_Accommodation::class,
            'type' => 'parent',
            'key' => 'inquiry_accommodation_id'
        ]
    ];
	
	public function getInquiryAccommodation():\Ext_TS_Inquiry_Journey_Accommodation {
		
		return $this->getJoinedObject('inquiry_accommodation');
	}
	
	public function isAccepted() {

		$checkAccepted = self::query()
			->select('ts_ar.*')
			->join('ts_accommodation_requests_recipients as ts_arr', 'ts_ar.id', '=', 'ts_arr.request_id')
			->where('ts_ar.inquiry_accommodation_id', '=', $this->inquiry_accommodation_id)
			->where('ts_arr.accepted', '!=' , null)
			->get();

		if($checkAccepted->count() > 0) {
			return true;
		}
		
		return false;
	}
	
}
