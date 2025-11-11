<?php

class Ext_TS_WDMVC_Token extends Ext_TC_WDMVC_Token {

	/**
	 * @TODO Auf Klassen (Services) umstellen
	 *
	 * @return array
	 */
	public static function getApplications() {

		$aApplications = parent::getApplications();

		if(Ext_Thebing_Access::hasRight('thebing_api_gel')) {
			$aApplications['ts_api_gel'] = L10N::t('API: Gel', 'API');
		}
//		if(Ext_Thebing_Access::hasRight('thebing_api_latest_bookings')) {
//			$aApplications['ts_api_latestbookings'] = L10N::t('API: Latest Bookings', 'API');
//		}
		$aApplications['ts_api_enquiries'] = L10N::t('API: Enquiries', 'API');
		$aApplications['ts_api_bitrix'] = L10N::t('API: Bitrix', 'API');
		$aApplications['ts_api_bookings'] = L10N::t('API: Bookings', 'API');
		$aApplications['ts_api_booking_details'] = L10N::t('API: Booking details', 'API');
		$aApplications['ts_api_payments'] = L10N::t('API: Payments', 'API');
		$aApplications['ts_api_placementtest'] = L10N::t('API: Placementtest', 'API');
		if (\TcExternalApps\Service\AppService::hasApp(\TsTuition\Handler\HalloAiApp::APP_NAME)) {
			$aApplications['ts_api_halloai'] = L10N::t('API: Hallo.ai', 'API');
		}
		$aApplications['ts_api_custom_fields'] = L10N::t('API: Custom Fields', 'API');

		asort($aApplications);
		
		return $aApplications;
	}
	
}