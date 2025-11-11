<?php

/**
 * Platzhalter für Bezahlungsübersichten beim Bezahlen von Lehrerbezahlungen (PDF bei den Gruppieren der bezahlten Lehrer)
 */
class Ext_TS_Accounting_Provider_Grouping_Teacher_Placeholder extends Ext_TS_Accounting_Provider_Grouping_Placeholder_ContractBridge
{
	protected $_sSection = 'teachers';
	protected $_sPlaceholderAreaTranslation = 'Lehrer';
}