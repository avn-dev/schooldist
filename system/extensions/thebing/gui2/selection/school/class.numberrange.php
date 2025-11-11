<?php

class Ext_Thebing_Gui2_Selection_School_Numberrange extends Ext_Gui2_View_Selection_Abstract {

	/**
	 * @var string
	 */
	private $sTypeNumberRange = 'manual_creditnote';

	/**
	 * @var bool
	 */
	private $bIsCredit = false;

	/**
	 * @param string $sTypeNumberRange
	 * @param bool $bIsCredit
	 */
	public function __construct($sTypeNumberRange, $bIsCredit = false) {

		$this->sTypeNumberRange = (string)$sTypeNumberRange;
		$this->bIsCredit = (bool)$bIsCredit;

	}

	/**
	 * {@inheritdoc}
	 */
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aNumberranges = [];
		$oSchool = $oWDBasic->getSchool();

		if($oSchool instanceof Ext_Thebing_School) {
			$aNumberranges = (array)Ext_Thebing_Inquiry_Document_Numberrange::getNumberrangesByType($this->sTypeNumberRange, $this->bIsCredit);
			$oNumberrange = Ext_Thebing_Inquiry_Document_Numberrange::getObject($this->sTypeNumberRange, $this->bIsCredit, $oSchool->id);
			if(
				$oNumberrange->id > 0 &&
				!array_key_exists($oNumberrange->id, $aNumberranges)
			) {
				$aNumberranges[$oNumberrange->id] = $oNumberrange->name;
			}
		}

		return $aNumberranges;

	}

}
