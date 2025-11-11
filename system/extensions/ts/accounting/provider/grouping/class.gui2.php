<?php

/**
 * Class Ext_TS_Accounting_Provider_Grouping_Accommodation_Gui2
 */
class Ext_TS_Accounting_Provider_Grouping_Gui2 extends Ext_Thebing_Gui2 {

	/**
	 * Klassenname der Klasse um die Massenpdfs zu erzeugen.
	 *
	 * @var string
	 */
	protected $multiple_pdf_class = 'Ext_TS_Accounting_Provider_Grouping_Gui2_Pdf_Documents';

	/**
	 * Gibt die Selektieroptionen für den Weiterverarbeitungsfilter zurück
	 *
	 * @param Ext_TS_Accounting_Provider_Grouping_Gui2 $oGui
	 * @return array
	 */
	public static function getSelectOptions(Ext_TS_Accounting_Provider_Grouping_Gui2 $oGui) {
		return [
			'yes' => $oGui->t('Weiterverarbeitet'),
			'no' => $oGui->t('Nicht weiterverarbeitet'),
		];
	}

}