<?php

class Ext_TS_Inquiry_TuitionIndex_Week extends Ext_TS_Inquiry_TuitionIndex_AbstractWeek {

	protected $sTable = 'ts_inquiries_tuition_index';

	/**
	 * @inheritdoc
	 */
	public function getSaveData() {

		$aData = parent::getSaveData();

		if(!$this->oEntity instanceof Ext_TS_Inquiry) {
			throw new BadMethodCallException('Entity is not valid');
		}

		$aData['inquiry_id'] = $this->oEntity->id;

		return $aData;

	}

}
