<?php

/**
 * E-Mail Layouts (HTML)
 */
class Ext_TC_Communication_Template_Email_Layout extends Ext_TC_Basic {

	protected $_sTable = 'tc_communication_templates_layouts';
	
	protected $_sTableAlias = 'tc_ctel';
	
	protected $_aJoinTables = array(

	);
	
	public static function getSelectOptions()
	{
		$oSelf = new self;
		$aList = $oSelf->getArrayList(true);
		return $aList;
	}

	public function generateContent(string $content)
	{
		$html = $this->html;

		$signaturePlaceholders = \Factory::getObject(\Ext_TC_User_Signature::class)
			->getPlaceholderObject()
			->getPlaceholders();

		$usedPlaceholder = \Illuminate\Support\Arr::first(array_keys($signaturePlaceholders), fn ($placeholder) => str_contains($html, $placeholder));

		if(!$usedPlaceholder && !str_contains($html, '{email_signature}')) {
			$html = str_replace('{email_content}', '{email_content}{email_signature}', $html);
		}

		$content = str_replace('{email_content}', $content, $html);
		return $content;
	}

}
