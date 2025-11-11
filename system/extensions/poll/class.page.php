<?php

class Ext_Poll_Page {
	
	protected $_iPoll;
	protected $_iPage;
	
	public function __construct($iPoll, $iPage) {
		$this->_iPoll = (int)$iPoll;
		$this->_iPage = (int)$iPage;
	}
	
	public function getRoutings() {
		
		$sSql = "
			SELECT 
				* 
			FROM 
				poll_routing 
			WHERE 
				`poll_id` = :poll_id AND
				`page_id` = :page_id AND
				`active` = 1
				";

		$aSql = array();
		$aSql['poll_id'] = $this->_iPoll;
		$aSql['page_id'] = $this->_iPage;
		
		$aRoutings = DB::getQueryRows($sSql, $aSql);
		
		return (array)$aRoutings;
		
	}

	public function getPlausichecks() {
 
		$sSql = "
			SELECT 
				* 
			FROM 
				poll_plausichecks 
			WHERE 
				`poll_id` = :poll_id AND
				`page_id` = :page_id AND
				`active` = 1
				";

		$aSql = array();
		$aSql['poll_id'] = $this->_iPoll;
		$aSql['page_id'] = $this->_iPage;

		$aPlausichecks = DB::getQueryRows($sSql, $aSql);

		return (array)$aPlausichecks;
		
	}
	
	public function getQuestions() {

		$aReturn = array();

		$sSql = "SELECT `id` FROM poll_paragraphs WHERE idPoll = :poll_id AND idPage = :page_id ORDER BY `position`";
		$aSql = array(
			'poll_id' => $this->_iPoll,
			'page_id' => $this->_iPage
		);
		$aParagraphs = DB::getQueryCol($sSql, $aSql);

		foreach($aParagraphs as $iParagraphId) {
			// Schleife für alle Fragen des aktuellen Paragraphen
			$sSql = "SELECT * FROM poll_questions WHERE idPoll = :poll_id AND idParagraph = :paragraph_id ORDER BY `position`";
			$aSql = array(
				'poll_id' => $this->_iPoll,
				'paragraph_id' => $iParagraphId
			);
			$aQuestions = (array)DB::getQueryRows($sSql, $aSql);
			$aReturn = array_merge($aReturn, $aQuestions);
		}

		return $aReturn;
	}
	
}