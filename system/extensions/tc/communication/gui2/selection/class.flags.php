<?php

use Communication\Facades\Communication;

/**
 * Selection-Klasse für die Markierungen, die abhängig von der Empfängergruppe sind.
 * 
 * Test: TA
 */
class Ext_TC_Communication_Gui2_Selection_Flags extends Ext_Gui2_View_Selection_Abstract
{
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
	{
		$options = [];

		if (
			!empty($oWDBasic->applications) &&
			!empty($oWDBasic->recipients)
		) {
			$allFlags = Communication::getAllFlags();
			$flags = \Factory::executeStatic(\Ext_TC_Communication::class, 'getSelectApplicationFlags')
				->only($oWDBasic->applications);

			foreach ($oWDBasic->applications as $applicationKey) {
				// Alle Markierungen die mit der gewählten Empfängergruppe übereinstimmen
				$applicationFlags = collect($flags->get($applicationKey))
					->filter(fn ($class) =>
						empty($recipientKeys = \Factory::executeStatic($class, 'getRecipientKeys', [$applicationKey])) ||
						!empty(array_intersect($recipientKeys, $oWDBasic->recipients))
					);



				foreach ($allFlags->intersect($applicationFlags) as $key => $class) {
					$options[$key] = \Factory::executeStatic($class, 'getTitle', [$this->_oGui->getLanguageObject(), $applicationKey]);
				}
			}
		}

		return $options;
	}
	
}