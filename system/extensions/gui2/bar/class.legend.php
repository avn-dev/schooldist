<?php

class Ext_Gui2_Bar_Legend extends Ext_Gui2_Bar {

	public function  __construct(Ext_Gui2 $oGui2) {

		$this->position = 'bottom';
		$this->_aElements[0] = $this->createHtml('');

		parent::__construct($oGui2);

	}

	public function addTitle($sTitle)
	{
		$oDiv = new Ext_Gui2_Html_Div();
		$oDiv->style	= 'float:left;';
		$oDiv->setElement('<strong>'.$sTitle.': </strong>&nbsp;');

		$this->addElement($oDiv->generateHTML());
	}

	public function addInfo($sText, $sColor = '', $mExampleText = false, $bItalicForExampleText = false, $sImage = '') {

		$sHtml = '';

		$oDiv = new Ext_Gui2_Html_Div();
		$oDiv->style = 'float:left;';
		$oDiv->setElement($sText);
		$sHtml .= $oDiv->generateHTML();
		
		if(!empty($sColor)) {

			$oDiv = new Ext_Gui2_Html_Div();
			
			if($mExampleText){
				$sStyle = sprintf('color: %s;', $sColor);
			}else{
				$sStyle = sprintf(
					'background-color: %s; border: 1px solid %s; color: %s;',
					$sColor,
					\Core\Helper\Color::changeLuminance($sColor, -0.1),
					\Core\Helper\Color::changeLuminance($sColor, -0.3)
				);
			}
			
            if(!is_string($mExampleText)){
                $oDiv->class = 'colorkey';
            }

			if($bItalicForExampleText) {
				$sStyle .= 'font-style:italic;';
			}
			
			$oDiv->style	= $sStyle;
			
			if($sImage != '') {
				$oImg = Ext_Gui2_Html::getIconObject($sImage);
				$oDiv->setElement($oImg);
			}

			if(
				$mExampleText &&
				$sImage == ''
			){
                if(is_string($mExampleText)){
                   $oDiv->setElement($mExampleText); 
                } else {
                   $oDiv->setElement('ABC');
                }
			}
						
			$sHtml .= $oDiv->generateHTML();
		}	

		$this->addElement($sHtml);
	}

	public function addElement($sElement) {

		$sElement = (string)$sElement;

		$oHtml = $this->_aElements[0];
		$sHtml = $oHtml->html;
		$sHtml .= $sElement;

		$oHtml = $this->createHtml($sHtml);

		$this->_aElements[0] = $oHtml;

	}

}