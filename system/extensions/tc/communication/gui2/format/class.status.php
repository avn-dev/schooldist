<?php

/**
 * Formatklasse für Adressen in der History
 */
class Ext_TC_Communication_Gui2_Format_Status extends Ext_Gui2_View_Format_Abstract {

	public function __construct(private readonly \Tc\Service\LanguageAbstract $l10n) {}

	public function format($value, &$column = null, &$resultData = null) {

		if (empty($value)) {
			return '';
		}

		return sprintf('<i class="%s"></i>', \Communication\Enums\MessageStatus::from($value)->getIcon());
	}
	
	/**
	 * Tooltip mit kompletten Adressen
	 * 
	 * @param string $oColumn
	 * @param string $aResultData
	 * @return boolean 
	 */
	public function getTitle(&$column = null, &$resultData = null) {

		$value = $resultData[$column->db_column] ?? null;

		if (empty($value)) {
			return null;
		}

		$label = \Communication\Enums\MessageStatus::from($value)->getLabelText($this->l10n);
		if (!empty($resultData[$value.'_at'])) {
			$format = Factory::getObject(Ext_TC_Gui2_Format_Date_Time::class);
			$label .= sprintf(' (%s)', $format->format($resultData[$value.'_at']));
		}

		$return = [];
		$return['content'] = (string)\Util::getEscapedString($label, 'htmlall');
		$return['tooltip'] = true;

		return $return;
	}

	public function align(&$oColumn = null)
	{
		return 'center';
	}

}
