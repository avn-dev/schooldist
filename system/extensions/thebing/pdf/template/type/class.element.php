<?php 

class Ext_Thebing_Pdf_Template_Type_Element extends Ext_Thebing_Basic {

	protected $_sTable = 'kolumbus_pdf_templates_types_elements';

	protected $_aFormat = array(
		'page_numbers' => array(
			'validate'			=> 'REGEX',
			'validate_value'	=> '([0-9]+,?)+'
		)
	);

	protected static $_aCache = array();

	public function getValue($sLang, $iTemplate){

		if(!isset(self::$_aCache['value'][$iTemplate])) {

			$sSql = "
						SELECT
							*
						FROM
							`kolumbus_pdf_templates_types_elements_values`
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
		if($this->element_type != 'html') {
			$oPurify = new \Core\Service\HtmlPurifier();
			$mValue = $oPurify->purify($mValue);
		}

		$sSql = " REPLACE INTO 
						`kolumbus_pdf_templates_types_elements_values`
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

	/**
	 * Prüft, ob der Block auf der Seite angezeigt werden soll
	 * @param int $iPage
	 * @return boolean
	 */
	public function checkDisplayOnPage($iPage) {
		
		$bReturn = false;		
		
		if($this->page == 'individual') {
			
			$aNumbers = explode(',', $this->page_numbers);
			
			if(in_array($iPage, $aNumbers)) {
				$bReturn = true;
			}
			
		}
		
		return $bReturn;
		
	}

}