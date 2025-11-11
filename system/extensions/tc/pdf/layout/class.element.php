<?php 

class Ext_TC_Pdf_Layout_Element extends Ext_TC_Basic {

	protected $_sTable = 'tc_pdf_layouts_elements';
	protected $_sTableAlias = 'tc_ple';

	protected $_aFormat = array(

							);

	protected static $_aCache = array();
							
	public function getValue($sLang, $iTemplate){

		if(!isset(self::$_aCache['value'][$iTemplate])) {

			$sSql = "
						SELECT
							*
						FROM
							`tc_pdf_layouts_elements_values`
						WHERE
							`template_id` = :template_id";
			$aSql = array(
				'template_id' => (int)$iTemplate
			);
			$aResult = DB::getPreparedQueryData($sSql, $aSql);
			$aItems = (array)$aResult;

			foreach((array)$aItems as $aItem) {
				self::$_aCache['value'][$iTemplate][$aItem['element_id']][$aItem['language_iso']] = $aItem['value'];
			}

		}

		$sBack = self::$_aCache['value'][$iTemplate][$this->id][$sLang];

		return $sBack;
					
	}
	
	public function saveValue($sLang, $iTemplate, $mValue){

		// War vorher in der getValue
		if($this->wysiwyg == 0) {
			$oPurify = new Ext_TC_Purifier([]);
			$mValue = $oPurify->purify($mValue);
		}

		$sSql = " REPLACE INTO 
						`tc_pdf_layouts_elements_values`
					SET
						`element_id` = :element_id ,
						`template_id` = :template_id ,
						`language_iso` = :language_iso,
						`value` = :value,
						`created` = NOW()";
		$aSql = array(
						'element_id' => (int)$this->id,
						'template_id' => (int)$iTemplate,
						'language_iso' => $sLang,
						'value' => (string)$mValue // Bei neuen Sprachen sonst NULL
					);
		
		DB::executePreparedQuery($sSql, $aSql);

	}

	public function getLayout() {
		
		$oLayout = Ext_TC_Pdf_Layout::getInstance($this->layout_id);
		
		return $oLayout;
		
	}
	
}