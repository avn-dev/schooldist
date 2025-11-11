<?php

namespace Ts\Handler\SequentialProcessing;

use \Core\Handler\SequentialProcessing\TypeHandler;

class DocumentSaveCheck extends TypeHandler {

	/**
	 * @inheritdoc
	 */
	public function execute($oObject) {

		if(
			(
				$oObject->type == 'brutto' ||
				$oObject->type == 'netto'
			) && 
			$oObject->latest_version == 0
		) {
			
			$oObject->active = 0;
			$oObject->save();
			
			$mail = new \WDMail();
			$mail->subject = 'Dead document - '.\Ext_Thebing_School::getSchoolFromSessionOrFirstSchool()->getName();
			$mail->text = $oObject->instantiateBacktrace."\n\n".print_r($oObject->aData, 1);
			$mail->send(['m.koopmann@fidelo.com']);
			
		}
		
	}

	/**
	 * @inheritdoc
	 */
	public function check($oObject) {
		return $oObject instanceof \Ext_Thebing_Inquiry_Document;
	}

}
