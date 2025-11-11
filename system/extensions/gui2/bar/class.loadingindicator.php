<?php

/**
 * Description of classloadingindicator
 *
 * @author Mark Koopmann
 */
class Ext_Gui2_Bar_LoadingIndicator extends Ext_Gui2_Config_Basic {

	// Konfigurationswerte setzten
	protected $_aConfig = array(
								'element_type'	 => 'loading_indicator',
								'id'			 => '',
								'html'			 => '',
								'access'		=> '' // recht
								);

	public function __construct($bOnlyPageCount = false){

		// Blätterleisten durchzählen
		$sHash = 'loading_indicator';

		$this->_aConfig['html'] 	= '
			<div class="guiBarElement">
				<div class="divToolbarIcon">
					<i id="'.$sHash.'" class="fa fa-spinner fa-pulse" style="display: none;"></i>
				</div>
			</div>';
		$this->_aConfig['id'] 		= $sHash;

	}

	public function getElementData(){
		return $this->_aConfig;
	}

}
