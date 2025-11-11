<?php
class Ext_Gui2_Bar_Pagination extends Ext_Gui2_Config_Basic {

	// Konfigurationswerte setzten
	protected $_aConfig = array(
								'element_type'	 => 'pagination',
								'id'			 => '',
								'html'			 => '',
								'only_pagecount' => 0,
								'limit_selection'=> 0,
								'limited_selection_options' => array(),
								'access'		 => '',
								'default_limit'	 => 0
								);

	static protected $iPagination = 1;

	public function __construct($bOnlyPageCount = false, $bLimitSelection = false) {

		// Blätterleisten durchzählen
		$sHash = 'pagination_'.self::$iPagination;

		if($bOnlyPageCount) {
			$this->only_pagecount = 1;
		}
		
		if($bLimitSelection) {
			$this->limit_selection = 1;
		}
		
		$this->_aConfig['limited_selection_options'] = array(
			array(
				'value'=>10,
				'text'=>'10'
			),
			array(
				'value'=>30,
				'text'=>'30'
			),
			array(
				'value'=>50,
				'text'=>'50'
			),
			array(
				'value'=>100,
				'text'=>'100'
			),
			array(
				'value'=>200,
				'text'=>'200'
			),
			array(
				'value'=>500,
				'text'=>'500'
			),
			array(
				'value'=>1000,
				'text'=>'1000'
			),
		);
		
		$this->_aConfig['html'] 	= '';
		$this->_aConfig['id'] 		= $sHash;

		self::$iPagination++;

	}

	/**
	 * Set einen Label vor das Icon
	 * @param $sLabel
	 */

	public function getElementData(){
		return $this->_aConfig;
	}

}
