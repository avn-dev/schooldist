<?php

class Ext_Thebing_Gui2_Format_ConcatComments extends Ext_Thebing_Gui2_Format_Select {

	/**
	 * @var string
	 */
	protected $_sTitleColumn;

	/**
	 * @var bool
	 */
	protected $_bAsTooltip;

	/**
	 * @var mixed
	 */
	protected $_mTruncate;

	/**
	 * @var string
	 */
	protected $sFormatedText;

	/**
	 * @param null $sTitleColumn
	 * @param bool $bAsTooltip
	 * @param bool $mTruncate
	 */
	public function __construct($sTitleColumn, $bAsTooltip=false, $mTruncate=false) {
		$this->_sTitleColumn = $sTitleColumn;
		$this->_bAsTooltip = $bAsTooltip;
		$this->_mTruncate = $mTruncate;
	}

	/**
	 * @param $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 *
	 * @return mixed|string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$aComment = explode('{|}', $mValue);

		$bNotEmpty = false;
		foreach($aComment as $sComment) {
			if($sComment !== '') {
				$bNotEmpty = true;
			}
		}

		if($bNotEmpty === true) {
			$mValue = str_replace('{|}', '<hr />', $mValue);
		} else {
			$mValue = str_replace('{|}', '', $mValue);
		}

		$this->sFormatedText = $mValue;

		if($this->_mTruncate && $this->sFlexType == 'list') {
			$mValue = Ext_Gui2_Util::truncateString($mValue, $this->_mTruncate);
		}

		return $mValue;

	}

	/**
	 * @param null $oColumn
	 * @param null $aResultData
	 *
	 * @return array
	 */
	public function getTitle(&$oColumn = null, &$aResultData = null) {

		$aReturn = array();
		$aReturn['content'] = (string)$this->sFormatedText;
		$aReturn['tooltip'] = (bool)$this->_bAsTooltip;

		return $aReturn;

	}

}