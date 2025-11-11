<?php

class Ext_Gui2_Dialog_TabArea extends Ext_Gui2_Dialog_Basic {

	public $aTabs = array();
	public $aContent = array();
	public $iTabId = null;

	public static $iTabCounter = 0;

	public function __construct($iTabId = null){

		$this->iTabId = $iTabId;
		self::$iTabCounter++;
		parent::__construct();

	}

	public function createTab($sTitel, $bReadOnly = false){

		$oLi = new Ext_Gui2_Html_Li();
		$oLi->title = $sTitel;

		if(empty($this->aTabs)){
			$oLi->class = 'active';
		}else{
			$oLi->class = '';
		}

		/*$sClass = $sId = 'tab_area_li';
		$sDivContentId = 'tab_area_content';

		if(is_int($this->iTabId)) {
			$sId .= '_'.$this->iTabId;
			$sDivContentId .= '_'.$this->iTabId;
		}

		$sId .= '_'.self::$iTabCounter;
		$sDivContentId .= '_'.self::$iTabCounter.'_'.count($this->aContent);

		$oLi->class = $sClass;
		$oLi->class = $sId;
		$oLi->id = $sId.'_'.count($this->aTabs);*/

		$this->aTabs[] = $oLi;

		// Content
		$oDivContent = new Ext_Gui2_Html_Div();
		$oDivContent->class = 'tab-pane fade';
		$oDivContent->role = 'tabpanel';

		if(empty($this->aContent)){
			$oDivContent->class = ' in active';
		}

		#$oDivContent->id = 'tab_area_content'.$this->getIDSuffix(count($this->aContent));
		$this->aContent[] = $oDivContent;

		return $oDivContent;
	}

	public function getIDSuffix($iContentId = false)
	{
		$sSuffix = '';

		if(is_int($this->iTabId)) {
			$sSuffix .= '_'.$this->iTabId;
		}

		$sSuffix .= '_'.self::$iTabCounter;

		if(is_int($iContentId)) {
			$sSuffix .= '_'.$iContentId;
		}

		return $sSuffix;
	}

	public function generateHTML() {

		$oAreaDiv = new Ext_Gui2_Html_Div();
		$oAreaDiv->class = 'GUIDialogTabArea clearfix';
		
		$oTabDiv = new Ext_Gui2_Html_Div();
		
		$oTabDiv->id = 'tab_area'.$this->getIDSuffix(false);
		$oTabDiv->class = 'tab_area';
		
		$oContentDiv = new Ext_Gui2_Html_Div();
		//$oContentDiv->style		= 'border: 1px solid #CCC;';
		$oContentDiv->class = 'tab-content';
		
		// Möglichen Scrollbalken gar nicht erst erzeugen
		$oContentDiv->style = 'overflow: visible;';

		$oTemplateTabDiv = new Ext_Gui2_Html_Div();
		$oTemplateTabDiv->class = 'nav-tabs-custom';
		$oTemplateTabDiv->style = 'border-top: none; margin: 0px;';

		$oUl = new Ext_Gui2_Html_Ul();
		$oUl->class = 'nav nav-tabs';

		foreach((array)$this->aTabs as $i => $oLi){

			$oContent = $this->aContent[$i];
			$sContentId = 'tab_area_content'.$this->getIDSuffix($i);
			$oContent->removeAttribute('id');
			$oContent->id = $sContentId;

			$sClass1 = 'tab_area_li';
			$sClass2 = $sClass1.$this->getIDSuffix();
			$sId = 'tab_area_li'.$this->getIDSuffix($i);

			#$oLi->removeAttribute('class');
			#$oLi->removeAttribute('id');

			$oLi->class = $sClass1;
			$oLi->class = $sClass2;
			#$oLi->id = $sId;

			$sA = '<a href="#'.$sContentId.'" id="'.$sId.'" aria-controls="'.$sContentId.'" role="tab" data-toggle="tab">'.$oLi->title.'</a>';
			
			$oLi->setElement($sA);
			$oUl->setElement($oLi);

		}

		$oTemplateTabDiv->setElement($oUl);

		$oDivClean = new Ext_Gui2_Html_Div();
		$oDivClean->class = 'divCleaner';
		$oTemplateTabDiv->setElement($oDivClean);

		$oTabDiv->setElement($oTemplateTabDiv);

		foreach((array)$this->aContent as $oDiv){
			$oContentDiv->setElement($oDiv);
		}

		self::$iTabCounter++;

		$oAreaDiv->setElement($oTabDiv);
		$oAreaDiv->setElement($oContentDiv);
		
		$sHtml = $oAreaDiv->generateHTML();

		return $sHtml;
	}

}
