<?php

class Ext_Thebing_Gui2_Format_School_Tuition_Coursename extends Ext_Gui2_View_Format_Abstract{

	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		if (isset($aResultData['extended_due_cancellation']) && (int)$aResultData['extended_due_cancellation'] === 1) {
			$color = Ext_Gui2_Util::getColor('red_font');
			$mValue .= sprintf(' <i class="fa fa-exclamation-circle" style="color: %s"></i>', $color);
		}

		return $mValue;
	}

	/**
	 * @param null $oColumn
	 * @param array $aResultData
	 * @return array
	 */
	public function getTitle(&$oColumn = null, &$aResultData = null) {

		$interfaceLanguage = \Ext_Thebing_School::fetchInterfaceLanguage();
		$tooltip = $aResultData['name_'.$interfaceLanguage];

		if (isset($aResultData['extended_due_cancellation']) && (int)$aResultData['extended_due_cancellation'] === 1) {
			$message = $this->oGui->t('Verlängerte Kurswoche aufgrund von Klassenausfall (Vorheriges Enddatum: %s)');
			$message = sprintf($message, (new Ext_Thebing_Gui2_Format_Date())->format($aResultData['lessons_catch_up_original_until']));

			$color = Ext_Gui2_Util::getColor('red_font');
			$tooltip .= sprintf(' - <span style="color: %s">%s</span>', $color, $message);
		}

		// Zeigt den ganzen Kursnamen an
		$aReturn = array();
		$aReturn['content'] = $tooltip;
		$aReturn['tooltip'] = true;

		return $aReturn;
	}

}
