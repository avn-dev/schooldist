<?php

namespace Ts\Model\Special;

/**
 * @property \Ext_TS_Inquiry $inquiry
 * @property \Ext_Thebing_School_Special $special
 * @property \Ext_Thebing_Special_Block_Block $special_block
 * @property \Ext_Thebing_Basic $object
 * @property \Ts\Entity\Special\Code $code
 */
class InquirySpecial {

	public function getType() {
		
		if(empty($this->object)) {
			return null;
		}

		switch(get_class($this->object)) {
			case \Ext_TS_Inquiry_Journey_Course::class:
				return 'course';
			case \Ext_TS_Inquiry_Journey_Accommodation::class:
				return 'accommodation';
				break;
			case \Ext_Thebing_Transfer_Package::class:
			case \Ext_TS_Inquiry_Journey_Transfer::class:
				return 'transfer';
				break;
			case \Ext_Thebing_School_Additionalcost::class:
				if($this->object->type == \Ext_Thebing_School_Additionalcost::TYPE_COURSE) {
					return 'additional_course';
				} else {
					return 'additional_accommodation';
				}
		}
		
	}
	
	public function getSpecial() {
		return $this->special;
	}
	
	public function getBlock() {
		return $this->special_block;
	}
	
	public function fillPositionObject(\Ext_Thebing_Inquiry_Special_Position $position) {
		
		$position->special_id = $this->special->id;
		$position->special_block_id = $this->special_block->id;
		$position->type = $this->getType();
		$position->type_id = $this->object?->id;
		$position->code_id = $this->code?->id;

	}
	
	public function getKey() {
		
		$objectKey = 0;
		
		if(
			$this->object instanceof \WDBasic && 
			$this->object->exist()
		) {
			$objectKey = $this->object->id;
		} elseif(is_object($this->object)) {
			$objectKey = spl_object_hash($this->object);
		}
		
		return implode('_', [
			$this->special_block->id,
			$this->special->id,
			$this->getType(),
			$objectKey,
		]);
	}
	
	public static function buildFromPosition(\Ext_Thebing_Inquiry_Special_Position $position) {
		
		$inquirySpecial = new self;
		$inquirySpecial->special = \Ext_Thebing_School_Special::getInstance($position->special_id);
		$inquirySpecial->special_block = \Ext_Thebing_Special_Block_Block::getInstance($position->special_block_id);
		$inquirySpecial->object = $position->getTypeObject();
		if(!empty($position->code_id)) {
			$inquirySpecial->code = \Ts\Entity\Special\Code::getInstance($position->code_id);
		}

		return $inquirySpecial;
	}
	
}
