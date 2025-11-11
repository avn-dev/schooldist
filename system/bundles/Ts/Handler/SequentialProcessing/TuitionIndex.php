<?php

namespace Ts\Handler\SequentialProcessing;

use \Core\Handler\SequentialProcessing\TypeHandler;

class TuitionIndex extends TypeHandler {

	/**
	 * @inheritdoc
	 */
	public function execute($oObject) {
		$oTuitionIndex = new \Ext_TS_Inquiry_TuitionIndex($oObject);
		$oTuitionIndex->update();
	}

	/**
	 * @inheritdoc
	 */
	public function check($oObject) {
		return $oObject instanceof \Ext_TS_Inquiry;
	}

}