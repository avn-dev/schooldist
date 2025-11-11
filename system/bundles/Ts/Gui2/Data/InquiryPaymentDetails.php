<?php

namespace Ts\Gui2\Data;

class InquiryPaymentDetails extends \Ext_Thebing_Gui2_Data
{
    public static function getOrderby()
    {
        return['kip.created' => 'ASC'];
    }

    public static function getWhere()
    {
		if(!\Ext_Thebing_System::isAllSchools()) {
			return ['ts_ij.school_id' => \Core\Handler\SessionHandler::getInstance()->get('sid')];
		}
		return [];
    }

    public static function getDefaultFilterFrom()
    {
        $oFormat = new \Ext_Thebing_Gui2_Format_Date();
        $aFilterDates = \Ext_TS_Accounting_Payment::getFilterDates();

        return $oFormat->formatByValue($aFilterDates['from']);
    }

    public static function getDefaultFilterUntil()
    {
        $oFormat = new \Ext_Thebing_Gui2_Format_Date();
        $aFilterDates = \Ext_TS_Accounting_Payment::getFilterDates();

        return $oFormat->formatByValue($aFilterDates['until']);
    }

    public static function getSchoolOptions()
    {
        $oClient = \Ext_Thebing_Client::getFirstClient();
        $aSchools = $oClient->getSchools(true);

        return $aSchools;
    }

    public static function getBookingTypes(\Ext_Thebing_Gui2 $oGui)
    {
		return [
			1 => $oGui->t('Agenturbucher'),
			2 => $oGui->t('Direktbucher')
		];
    }

    public static function getAgenciesOptions()
    {

        $oClient = \Ext_Thebing_Client::getFirstClient();
        $aAgencies = $oClient->getAgencies(true);

        return $aAgencies;
    }

    public static function getAgencyCategoriesOptions()
    {

        $oClient = \Ext_Thebing_Client::getFirstClient();
        $aAgencyCategories	= $oClient->getAgenciesCategoriesList();

        return $aAgencyCategories;
    }

    public static function getPaymentMethodsOptions()
    {
        if(\Ext_Thebing_System::isAllSchools()) {
            $aPaymentMethods = \Ext_Thebing_Admin_Payment::getPaymentMethods(true);
        } else {
            $oSchool = \Ext_Thebing_School::getSchoolFromSession();
            $aPaymentMethods = \Ext_Thebing_Admin_Payment::getPaymentMethods(true, [$oSchool->id]);
        }

        return $aPaymentMethods;
    }

    public static function getSalespersonSelectOptions()
    {
        $aSalespersonSelect = \Factory::executeStatic('Ext_TC_User', 'getSalesPersonsForSelect');

        return $aSalespersonSelect;
    }

    public static function getInboxesOptions()
    {
        $oClient = \Ext_Thebing_Client::getFirstClient();
        $aInboxes = $oClient->getInboxList(true, true);

        return $aInboxes;
    }

}