<?php

class Ext_Elearning_Exam_Comment extends WDBasic {

	protected $_sTable = 'elearning_exams_comments';

	public function __construct($iDataID=0) {
		parent::__construct($iDataID, $this->_sTable, true);
	}

	public function send($sEmail) {

		$oExam = new Ext_Elearning_Exam($this->exam_id);

		$sSubject = 'Es wurde ein Kommentar zu dem E-Learning-Test "'.$oExam->name.'" abgegeben';
		$sContent = 'Zeitpunkt: '.strftime("%x %X", $this->created);
		$sContent .= "\n\n";

		$sContent .= "Kommentar:\n".$this->comment;

		wdmail($sEmail, $sSubject, $sContent);

	}

}