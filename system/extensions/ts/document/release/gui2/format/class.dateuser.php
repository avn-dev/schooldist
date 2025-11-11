<?php

class Ext_TS_Document_Release_Gui2_Format_DateUser extends \TsAccounting\Gui2\Format\Release\DateUser {

	protected $bIsCreditNote;

	public function __construct($bIsCreditNote=false) {
		$this->bIsCreditNote = $bIsCreditNote;
	}

	protected function getEntityObject(int $iId) {

		$oDocument = Ext_Thebing_Inquiry_Document::getInstance($iId);

		// Die Klasse weiß nicht, ob nun ein Timestamp vom Dokument oder von der CN reinkommt
		if($this->bIsCreditNote) {
			$oCreditNote = $oDocument->getCreditNote();
			if($oCreditNote) {
				$oDocument = $oCreditNote;
			}
		}

		return $oDocument;
	}

}
