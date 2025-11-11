<?php

/**
 * @param string $id
 * @param string $changed
 * @param string $created
 * @param string $valid_until
 * @param string $active
 * @param string $editor_id
 * @param string $creator_id
 * @param string $name
 * @param string $number_of_ratings
 * @param string $type
 */
class Ext_TC_Marketing_Feedback_Rating extends Ext_TC_Basic {

    /**
     * @var string
     */
    protected $_sTable = 'tc_feedback_ratings';

    /**
     * @var string
     */
    protected $_sTableAlias = 'tc_fr';

    /**
     * @var array
     */
    protected $_aFormat = array(
		'number_of_ratings' => array(
			'validate'	=> 'INT_NOTNEGATIVE'
		)
	);

    /**
     * @var array
     */
    protected $_aJoinedObjects = array(
		'childs' => array(
			'class'		   => 'Ext_TC_Marketing_Feedback_Rating_Child',
			'key'		   => 'rating_id',
			'check_active' => true,
			'type'		   => 'child'
		)
	);

	/**
	 * Liefert alle Childs
	 *
	 * @return Ext_TC_Marketing_Feedback_Rating_Child[]
	 */
	public function getChildElements() {
		$aChilds = (array)$this->getJoinedObjectChilds('childs', true);
		return $aChilds;
	}

	/**
	 * Liefert ein Child eines Ratings anhand eines Wertes
	 *
	 * @param int $iRating
	 * @return Ext_TC_Marketing_Feedback_Rating_Child|null
	 */
	public function getChildByRating($iRating) {
		$oChild = $this->getJoinedObjectChildByValue('childs', 'rating', $iRating);
		return $oChild;
	}
	
	/**
	 * Liefert eine Auswahl von Themen für ein Select
	 *
	 * @param bool $bAddEmptyItem
	 * @return array
	 */
	public static function getSelectOptions($bAddEmptyItem = false) {

		$oTemp = new self();
		$aList = (array) $oTemp->getArrayList(true);
		
		if($bAddEmptyItem) {
			$aList = Ext_TC_Util::addEmptyItem($aList);
		}
		
		return $aList;
	}

	/**
	 * @return int
	 */
	public function getMaxValue() {

		$iMaxValue = (int)DB::getQueryOne("SELECT MAX(`rating`) FROM `tc_feedback_ratings_childs` WHERE `rating_id` = :rating_id", array(
			'rating_id' => $this->getId()
		));

		return $iMaxValue;
	}
	
}
