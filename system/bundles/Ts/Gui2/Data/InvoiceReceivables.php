<?php

namespace Ts\Gui2\Data;

use WDDate;

class InvoiceReceivables extends \Ext_Thebing_Gui2_Data
{
    public static function getOrderby()
    {
        return ['kid.created'=>'DESC'];
    }

    public static function getWhere()
    {
		if(!\Ext_Thebing_System::isAllSchools()) {
			return ['ts_i_j.school_id' => \Core\Handler\SessionHandler::getInstance()->get('sid')];
		}
		return [];
    }

    public static function getBookingTypes(\Ext_Thebing_Gui2 $oGui)
    {
        $aBookingTypes = [
            1 => $oGui->t('Agenturbucher'),
            2 => $oGui->t('Direktbucher')
        ];

        return $aBookingTypes;
    }

    public static function getFilterBasedOn(\Ext_Thebing_Gui2 $oGui)
    {
        $oFilterBasedOn = [
            'service_start' => $oGui->t('Leistungsbeginn'),
            'invoice_created' => $oGui->t('Rechnungserstellung'),
            'service_date' => $oGui->t('Leistungsdatum'),
            'course_start' => $oGui->t('Kursstart'),
            'course_end' => $oGui->t('Kursende'),
            'accommodation_start' => $oGui->t('Unterkunftsstart'),
            'accommodation_end' => $oGui->t('Unterkunftsende'),
            'service_end' => $oGui->t('Leistungsende'),
            'final_payment_date' => $oGui->t('Restzahlungsdatum')
        ];
        asort($oFilterBasedOn);

        return $oFilterBasedOn;
    }

    public static function getDefaultFilterFrom()
    {
        $oWdDate = new WDDate();
        $oFormat = new \Ext_Thebing_Gui2_Format_Date();
        $oWdDate->sub(7, WDDate::DAY);
        $sFilterStart = $oFormat->formatByValue($oWdDate->get(WDDate::DB_DATE));

        return $sFilterStart;
    }

    public static function getDefaultFilterUntil()
    {
        $oWdDate = new WDDate();
        $oFormat = new \Ext_Thebing_Gui2_Format_Date();
        $oWdDate->set(time(), WDDate::TIMESTAMP);
        $sFilterEnd = $oFormat->formatByValue($oWdDate->get(WDDate::DB_DATE));

        return $sFilterEnd;
    }

	public static function getDocumentFilterOptions(\Ext_Thebing_Gui2 $oGui)
	{
		$options = [
			'invoices' => $oGui->t('nur Rechnungen'),
			'proforma' => $oGui->t('nur Proforma')
		];

		return $options;
	}

    public static function getDocumentFilterQueries()
    {

        $queries = [
            'all' => " `kid`.`type` IN (".\Ext_Thebing_Inquiry_Document_Search::getTypeDataAsString('invoice').") ",
            'invoices' => " `kid`.`type` IN (".\Ext_Thebing_Inquiry_Document_Search::getTypeDataAsString('invoice_without_proforma').") ",
            'proforma' => " `kid`.`type` IN (".\Ext_Thebing_Inquiry_Document_Search::getTypeDataAsString('proforma').") "
        ];

        return $queries;
    }

}