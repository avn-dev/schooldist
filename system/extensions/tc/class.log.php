<?php

class Ext_TC_Log extends Log {

	const ERROR = 'ERROR';
	const ADDED = 'ADDED';
	const UPDATED = 'UPDATED';
	const DELETED = 'DELETED';
	const ACCESS_RIGHTS_SAVED = 'ACCESS_RIGHTS_SAVED';

	const UPDATE_STARTED = 'UPDATE_STARTED';
	const UPDATE_SUCCESS = 'UPDATE_SUCCESS';
	const UPDATE_ERROR = 'UPDATE_ERROR';

	const HOLIDAY_COURSE_DATES_CHANGED = 'HOLIDAY_COURSE_DATES_CHANGED';
	const HOLIDAY_COURSE_DATES_EXTENDED = 'HOLIDAY_COURSE_DATES_EXTENDED';
	const HOLIDAY_COURSE_DATES_CROPED = 'HOLIDAY_COURSE_DATES_CROPED';
	const HOLIDAY_SPLIT_COURSE = 'HOLIDAY_SPLIT_COURSE';
	const HOLIDAY_SPLIT_ACCOMMODATION = 'HOLIDAY_SPLIT_ACCOMMODATION';
	const HOLIDAY_DELETED = 'HOLIDAY_DELETED';

	const INQUIRY_CONFIRMED = 'INQUIRY_CONFIRMED';
	const INQUIRY_CUSTOMER_ADDED = 'INQUIRY_CUSTOMER_ADDED';
	const INQUIRY_CUSTOMER_CHANGED = 'INQUIRY_CUSTOMER_CHANGED';

	const ACCOMMODATION_ALLOCATION_SAVED = 'ACCOMMODATION_ALLOCATION_SAVED';

	const DOCUMENT_CREATE_CREDIT_AND_INVOICE = 'DOCUMENT_CREATE_CREDIT_AND_INVOICE';
	const DOCUMENT_PDF_CREATED = 'DOCUMENT_PDF_CREATED';
	const DOCUMENT_CREATE_PROFORMA = 'DOCUMENT_CREATE_PROFORMA';
	const DOCUMENT_CONVERT_PROFORMA_TO_INVOICE = 'DOCUMENT_CONVERT_PROFORMA_TO_INVOICE';

	const PDF_CREATED = 'PDF_CREATED';

	/**
	 * Liefert alle Konstanten
	 * @return array
	 */
	public static function getActions()
	{
		$oReflectionClass = new ReflectionClass(Ext_TC_Factory::getClassName('Ext_TC_Log'));
		$aConstants = $oReflectionClass->getConstants();
		return $aConstants;
	}

	/**
	 * Loggt einen Fehler nach "error.log"
	 * @global array $_VARS
	 * @param string $sMessage
	 * @param mixed $mOptional
	 */
	public static function error($sMessage, $mOptional = array())
	{
		global $_VARS;

		//$oLog = self::getLogger('error');
		$oLog = self::getLogger(); // error.log schaut sich eh keiner an und wird so gut wie nicht benutzt
		if(!is_array($mOptional)) {
			$mOptional = array($mOptional);
		}
		$oLog->addDebug($sMessage, $mOptional);
		$oLog->addInfo('VARS', $_VARS);
	}

	/**
	 * Loggt die Aktionen einer Entity "entity.log"
	 *
	 * @param WDBasic $oObject
	 * @param string $sAction
	 * @param array $aIntersectionData
	 */
	public static function logEntityAction(WDBasic $oObject, $sAction, $aIntersectionData = []) {

		$access = Access::getInstance();
		
		$userId = 0;
		$passkey = null;
		
		if($access instanceof Access_Backend) {
			$userId = $access->id;
			$passkey = $access->getAccessPasskey();
		}

		$oMonolog = self::getLogger('entity');
		$oMonolog->debug($oObject->getClassName().'::'.$oObject->getId(), array($sAction));
		if($sAction == self::UPDATED) {
			$oMonolog->info('Intersection Data', $oObject->cleanData($aIntersectionData));
		} else if($sAction == self::ADDED) {
			$oMonolog->info('Data', $oObject->cleanData($oObject->getData()));
			$oMonolog->info('Jointables', (array)$oObject->getJoinTableValues());
		}
		
		if(isset($_REQUEST['save']) && is_array($_REQUEST['save'])) {
			$_REQUEST['save'] = $oObject->cleanData($_REQUEST['save']);
		}

		$oMonolog->info('VARS', $_REQUEST);
		$oMonolog->info('Additional', array(
			'user_id' => $userId,
			'passkey' => $passkey,
			'school_id' => \Core\Handler\SessionHandler::getInstance()->get('sid'),
			'backtrace' => Util::getBacktrace()
		));

	}

	/**
	 * Loggt in die »default.log«
	 * @param string $sClassName
	 * @param int $iClassDataId
	 * @param string $sAction
	 * @param mixed $sOptionalInfos
	 */
	public static function w($sClassName, $iClassDataId, $sAction, $sOptionalInfos = '')
	{
		global $user_data, $_VARS;

		if(is_array($sOptionalInfos)) {
			$sOptionalInfos = json_encode($sOptionalInfos);
		}

		// addDebug() erwartet ein Array!
		$aEntry = (array)$sOptionalInfos;

		$aEntry['request'] = $_VARS;
		$aEntry['user_id'] = $user_data['id'];
		$aEntry['backtrace'] = Util::getBacktrace();
		
		$oMonolog = self::getLogger();
		$oMonolog->error($sClassName.'::'.$iClassDataId.'::'.$sAction, $aEntry);

	}

}