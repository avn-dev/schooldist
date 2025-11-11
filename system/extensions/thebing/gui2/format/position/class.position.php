<?php

class Ext_Thebing_Gui2_Format_Position_Position extends Ext_Thebing_Gui2_Format_Format {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$sName = '';
		switch($mValue){
			case 'transfer':
				$sName = L10N::t('Transfer');
				break;
			case 'additional_course':
				$sName = L10N::t('Zusatzkosten Kurs');
				break;
			case 'insurance':
				$sName = L10N::t('Versicherung');
				break;
			case 'accommodation':
				$sName = L10N::t('Unterkunft');
				break;
			case 'additional_accommodation':
				$sName = L10N::t('Zusatzkosten Unterkunft');
				break;
			case 'additional_general':
				$sName = L10N::t('Generelle Kosten');
				break;
			case 'extra_week':
				$sName = L10N::t('Extrawoche');
				break;
			case 'extra_night':
				$sName = L10N::t('Extranacht');
				break;
			case 'course':
				$sName = L10N::t('Kurs');
				break;
			case 'special':
				$sName = L10N::t('Special');
				break;
			case 'extra':
				$sName = L10N::t('Manuelle Positionen');
				break;
			case 'storno':
				$sName = L10N::t('Allgemeine Stornierung');
				break;
			case 'commission': // Belegtexte
				$sName = L10N::t('Provision');
				break;
			case 'activity': // Belegtexte
				$sName = L10N::t('Aktivität');
				break;;
			case 'invoice': // Belegtexte
				$sName = L10N::t('Rechnung');
				break;
			case 'deposit': // Belegtexte
				$sName = L10N::t('Anzahlung');
			case 'deposit_credit':
				$sName = L10N::t('Gutschrift Anzahlung');
				break;
		}

		return $sName;
	}

}
