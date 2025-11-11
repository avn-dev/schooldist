<?php

class Ext_Thebing_Upload_Gui2 extends Ext_TC_Upload_Gui2_Data
{
	public static function getCategories()
    {
		$aCategories = [];
		$aCategories[1] = L10N::t('Templates für PDF', 'Thebing » Admin » Upload');
		$aCategories[2] = L10N::t('Signaturen IMG', 'Thebing » Admin » Upload');
		$aCategories[3] = L10N::t('Anhänge für Kontaktmail', 'Thebing » Admin » Upload');
		$aCategories[4] = L10N::t('Anhänge für Kommunikation bei Rechnungen', 'Thebing » Admin » Upload');
		$aCategories[5] = L10N::t('Downloads (Frontend)', 'Thebing » Admin » Upload'); // War früher »Anhänge für PDFs«, hat aber nie funktioniert #5144
		$aCategories[6] = L10N::t('Bilder', 'Thebing » Admin » Upload');
		return $aCategories;
	}
}