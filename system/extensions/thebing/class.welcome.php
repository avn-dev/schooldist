<?php

class Ext_Thebing_Welcome extends Ext_TC_Welcome {

	const TRANSLATION_PATH = 'Thebing » Welcome';

	public static function bookings() {		
		
		$aTotal = array();
		$iTime = time();
		$iNow = time();

		$iTodayStart 	= mktime(0, 0, 0, date("m", $iTime), date("d", $iTime), date("Y", $iTime));
		$iTodayEnd		= mktime(23, 59, 59, date("m", $iTime), date("d", $iTime), date("Y", $iTime));

		$iMonthStart 	= mktime(0, 0, 0, date("m", $iTime), 1, date("Y", $iTime));
		$iMonthEnd		= mktime(23, 59, 59, date("m", $iTime), date("t", $iTime), date("Y", $iTime));

		$iYearStart 	= mktime(0, 0, 0, 1, 1, date("Y", $iTime));
		$iYearEnd		= mktime(23, 59, 59, 12, 31, date("Y", $iTime));

		// causes a bug: on 'March 31', '30'... this returns 'March 3', '2',... and NOT 'Febr. 28', '27'...
		// $iTime = strtotime("last month",  $iTime);
		// see also : http://de.php.net/manual/de/function.strtotime.php#97075
		// [BB]
		$iLastMonthStart 	= mktime(0, 0, 0, (date("m", $iTime)-1), 1, date("Y", $iTime));
		if ( (date("L", $iTime) == 1) && ((date("m", $iTime)-1) == 2) ) {
		    $iLastMonthDay = 29;
		} else if ( (date("L", $iTime) == 0) && ((date("m", $iTime)-1) == 2) ) {
		    $iLastMonthDay = 28;
		} else {
		    $iLastMonthDay = date("t", $iTime);
		}
		$iLastMonthEnd		= mktime(23, 59, 59, (date("m", $iTime)-1), $iLastMonthDay, date("Y", $iTime));
		
		$iTime = strtotime("last year",  $iTime);
		$iLastYearStart 	= mktime(0, 0, 0, 1, 1, (date("Y", $iTime)));

		$oWDDate = new WDDate($iLastYearStart, WDDate::TIMESTAMP);
		$oWDDate->add(1, WDDate::YEAR);
		$oWDDate->sub(1, WDDate::DAY);
		$iLastYearEnd		= $oWDDate->get(WDDate::TIMESTAMP);

		$sContent = '';
		$sContent .= '<table class="table table-hover">';
		$sContent .= '<tr class="noHighlight">';
		$sContent .= '<th>'.L10N::t('School', 'Thebing » Welcome').'</th>';
		$sContent .= '<th colspan="2">'.L10N::t('Today', 'Thebing » Welcome').'</th>';
		$sContent .= '<th colspan="2">'.L10N::t('Current month', 'Thebing » Welcome').'</th>';
		$sContent .= '<th colspan="2">'.L10N::t('Last month', 'Thebing » Welcome').'</th>';
		$sContent .= '<th colspan="2">'.L10N::t('Current year', 'Thebing » Welcome').'</th>';
		$sContent .= '<th colspan="2">'.L10N::t('Last Year', 'Thebing » Welcome').'</th>';
		$sContent .= '</tr>';
		$sContent .= '<tr class="noHighlight borderBottom">';
		$sContent .= '<th style="width: auto;">&nbsp;</th>';
		$sContent .= '<td style="width: 60px;">'.L10N::t('Bookings', 'Thebing » Welcome').'</td>';
		$sContent .= '<td style="width: 60px;">'.L10N::t('Wochen', 'Thebing » Welcome').'</td>';
		$sContent .= '<td style="width: 60px;">'.L10N::t('Bookings', 'Thebing » Welcome').'</td>';
		$sContent .= '<td style="width: 60px;">'.L10N::t('Wochen', 'Thebing » Welcome').'</td>';
		$sContent .= '<td style="width: 60px;">'.L10N::t('Bookings', 'Thebing » Welcome').'</td>';
		$sContent .= '<td style="width: 60px;">'.L10N::t('Wochen', 'Thebing » Welcome').'</td>';
		$sContent .= '<td style="width: 60px;">'.L10N::t('Bookings', 'Thebing » Welcome').'</td>';
		$sContent .= '<td style="width: 60px;">'.L10N::t('Wochen', 'Thebing » Welcome').'</td>';
		$sContent .= '<td style="width: 60px;">'.L10N::t('Bookings', 'Thebing » Welcome').'</td>';
		$sContent .= '<td style="width: 60px;">'.L10N::t('Wochen', 'Thebing » Welcome').'</td>';
		$sContent .= '</tr>';
		
		$aSchools = Ext_Thebing_Client::getSchoolList();
		
		foreach((array)$aSchools as $aSchool) {

			$oSchool = Ext_Thebing_School::getInstance( $aSchool['id']);
			
			$aToday = $oSchool->getBookingStats($iTodayStart, $iTodayEnd);
			$aMonth = $oSchool->getBookingStats($iMonthStart, $iMonthEnd);
			$aYear = $oSchool->getBookingStats($iYearStart, $iYearEnd);
			$aLastMonth = $oSchool->getBookingStats($iLastMonthStart, $iLastMonthEnd);
			$aLastYear = $oSchool->getBookingStats($iLastYearStart, $iLastYearEnd);

			$sContent .= '<tr class="borderLeft">';
			$sContent .= '<td class="noBorder">'.$aSchool['ext_1'].'</td>';
			$sContent .= '<td style="text-align: right;">'.Ext_Thebing_Format::Int($aToday['bookings']).'</td>';
			$sContent .= '<td style="text-align: right;">'.Ext_Thebing_Format::Int($aToday['weeks']).'</td>';
			$sContent .= '<td style="text-align: right;">'.Ext_Thebing_Format::Int($aMonth['bookings']).'</td>';
			$sContent .= '<td style="text-align: right;">'.Ext_Thebing_Format::Int($aMonth['weeks']).'</td>';
			$sContent .= '<td style="text-align: right;">'.Ext_Thebing_Format::Int($aLastMonth['bookings']).'</td>';
			$sContent .= '<td style="text-align: right;">'.Ext_Thebing_Format::Int($aLastMonth['weeks']).'</td>';
			$sContent .= '<td style="text-align: right;">'.Ext_Thebing_Format::Int($aYear['bookings']).'</td>';
			$sContent .= '<td style="text-align: right;">'.Ext_Thebing_Format::Int($aYear['weeks']).'</td>';
			$sContent .= '<td style="text-align: right;">'.Ext_Thebing_Format::Int($aLastYear['bookings']).'</td>';
			$sContent .= '<td style="text-align: right;">'.Ext_Thebing_Format::Int($aLastYear['weeks']).'</td>';
			$sContent .= '</tr>';
			
			$aTotal['today']['bookings'] += $aToday['bookings'];
			$aTotal['today']['weeks'] += $aToday['weeks'];
			$aTotal['month']['bookings'] += $aMonth['bookings'];
			$aTotal['month']['weeks'] += $aMonth['weeks'];
			$aTotal['last_month']['bookings'] += $aLastMonth['bookings'];
			$aTotal['last_month']['weeks'] += $aLastMonth['weeks'];
			$aTotal['year']['bookings'] += $aYear['bookings'];
			$aTotal['year']['weeks'] += $aYear['weeks'];
			$aTotal['last_year']['bookings'] += $aLastYear['bookings'];
			$aTotal['last_year']['weeks'] += $aLastYear['weeks'];
			
		}
		
		$sContent .= '<tr class="noHighlight borderTop">';
		$sContent .= '<th>'.L10N::t('Total', 'Thebing » Welcome').'</th>';
		$sContent .= '<th style="text-align: right;">'.Ext_Thebing_Format::Int($aTotal['today']['bookings']).'</th>';
		$sContent .= '<th style="text-align: right;">'.Ext_Thebing_Format::Int($aTotal['today']['weeks']).'</th>';
		$sContent .= '<th style="text-align: right;">'.Ext_Thebing_Format::Int($aTotal['month']['bookings']).'</th>';
		$sContent .= '<th style="text-align: right;">'.Ext_Thebing_Format::Int($aTotal['month']['weeks']).'</th>';
		$sContent .= '<th style="text-align: right;">'.Ext_Thebing_Format::Int($aTotal['last_month']['bookings']).'</th>';
		$sContent .= '<th style="text-align: right;">'.Ext_Thebing_Format::Int($aTotal['last_month']['weeks']).'</th>';
		$sContent .= '<th style="text-align: right;">'.Ext_Thebing_Format::Int($aTotal['year']['bookings']).'</th>';
		$sContent .= '<th style="text-align: right;">'.Ext_Thebing_Format::Int($aTotal['year']['weeks']).'</th>';
		$sContent .= '<th style="text-align: right;">'.Ext_Thebing_Format::Int($aTotal['last_year']['bookings']).'</th>';
		$sContent .= '<th style="text-align: right;">'.Ext_Thebing_Format::Int($aTotal['last_year']['weeks']).'</th>';
		$sContent .= '</tr>';
		$sContent .= '</table>';
		
		return $sContent;
		
	}

	
	public static function groups_details() {
	
		// Current week
		$aTimestamps = Ext_Thebing_Util::getWeekTimestamps();
		$iCurrentWeekStart 	= $aTimestamps['start'];
		$iCurrentWeekEnd	= $aTimestamps['end'];
		
		$sContent = '';
		
		$aDays = Ext_Thebing_Util::getDays("%a");

		$aSchools = Ext_Thebing_Client::getSchoolList();
		
		foreach((array)$aSchools as $aSchool) {
			
			$oSchool = Ext_Thebing_School::getInstance($aSchool['id']);
			$aWeekBlocks = $oSchool->getWeekBlocks($iCurrentWeekStart, $iCurrentWeekEnd);

			if(empty($aWeekBlocks)) {
				//continue;
			}

			$sContent .= '<h2>'.$aSchool['ext_1'].'</h2>';

			$sContent .= '<table class="table table-hover">';
			$sContent .= '<tr class="noHighlight borderBottom">';
			$sContent .= '<th style="width: 80px;">'.L10N::t('Level', 'Thebing » Welcome').'</th>';
			$sContent .= '<th style="width: 280px;">'.L10N::t('Course', 'Thebing » Welcome').'</th>';
			$sContent .= '<th style="width: auto;">'.L10N::t('Teacher', 'Thebing » Welcome').'</th>';
			$sContent .= '<th style="width: 80px;">'.L10N::t('Students', 'Thebing » Welcome').'</th>';
			$sContent .= '<th style="width: 120px;">'.L10N::t('Days', 'Thebing » Welcome').'</th>';
			$sContent .= '<th style="width: 80px;">'.L10N::t('Time', 'Thebing » Welcome').'</th>';
			$sContent .= '</tr>';

			foreach((array)$aWeekBlocks as $aWeekBlock) {
				
				$oBlock = Ext_Thebing_School_Tuition_Block::getInstance($aWeekBlock['id']);

				$aTemp = array();
				foreach((array)$oBlock->days as $iDay) {
					$aTemp[] = $aDays[$iDay];
				}
				$sWeekdays = implode(", ", $aTemp);
				
				$aTemp = array();
				$aCourseInfos = (array)$oBlock->getCourses();
				foreach($aCourseInfos as $aCourse) {
					$aTemp[] = $aCourse['course'];
				}
				$sCourses = implode(", ", $aTemp);
				
				$sContent .= '<tr class="borderLeft">';
				$sContent .= '<td class="noBorder">'.$oBlock->level_short.'</td>';
				$sContent .= '<td>'.$sCourses.'</td>';
				$sContent .= '<td>'.$oBlock->teacher_lastname.', '.$oBlock->teacher_firstname.'</td>';
				$sContent .= '<td>'.$oBlock->students_total.' / '.$oBlock->students_max.'&nbsp;</td>';
				$sContent .= '<td>'.$sWeekdays.'</td>';
				$sContent .= '<td>'.date('H:i', $oBlock->from).' - '.date('H:i', $oBlock->until).'</td>';
				$sContent .= '</tr>';
			}

			$sContent .= '</table>';

		}
		
		return $sContent;
		
	}

	static public function sortAgencies($aA, $aB) {
		if ($aA['stats']['current_year']['weeks'] == $aB['stats']['current_year']['weeks']) {
			return 0;
		}
		return ($aA['stats']['current_year']['weeks'] > $aB['stats']['current_year']['weeks']) ? -1 : 1;
	}

	public static function agencies() {

		$aTotal = array();
		$iTime = time();
		$iNow = time();

		$iYearStart 	= mktime(0, 0, 0, 1, 1, date("Y", $iTime));
		$iYearEnd		= mktime(23, 59, 59, date("m", $iNow), date("d", $iNow), date("Y", $iTime));

		$iTime = strtotime("last year");
		$iLastYearStart 	= mktime(0, 0, 0, 1, 1, date("Y", $iTime));
		$iLastYearEnd		= mktime(23, 59, 59, date("m", $iNow), date("d", $iNow), date("Y", $iTime));

		$aSchools = Ext_Thebing_Client::getSchoolList();
		$aSchools = array_slice($aSchools, 0, 4);

		$sContent = '';
		$sContent .= '<table class="table table-hover">';
		$sContent .= '<tr class="noHighlight">';
		$sContent .= '<th>'.L10N::t('Agentur', 'Thebing » Welcome').'</th>';

		foreach((array)$aSchools as $aSchool) {
			$sContent .= '<th colspan="2">'.Ext_Thebing_Util::stripString($aSchool['ext_1'], 16).'</th>';
		}
		
		$sContent .= '<th colspan="2">'.L10N::t('Total this year', 'Thebing » Welcome').'</th>';
		$sContent .= '<th colspan="2">'.L10N::t('Total last Year', 'Thebing » Welcome').'</th>';
		$sContent .= '</tr>';
		$sContent .= '<tr class="noHighlight borderBottom">';
		$sContent .= '<td style="width: auto;">&nbsp;</td>';
		
		foreach((array)$aSchools as $aSchool) {
			$sContent .= '<td style="width: 70px;">'.L10N::t('Buchungen', 'Thebing » Welcome').'</td>';
			$sContent .= '<td style="width: 70px;">'.L10N::t('Wochen', 'Thebing » Welcome').'</td>';
		}
		
		$sContent .= '<td style="width: 70px;">'.L10N::t('Buchungen', 'Thebing » Welcome').'</td>';
		$sContent .= '<td style="width: 70px;">'.L10N::t('Wochen', 'Thebing » Welcome').'</td>';
		$sContent .= '<td style="width: 70px;">'.L10N::t('Buchungen', 'Thebing » Welcome').'</td>';
		$sContent .= '<td style="width: 70px;">'.L10N::t('Wochen', 'Thebing » Welcome').'</td>';
		$sContent .= '</tr>';

		$aAgencies = Ext_Thebing_Client::getFirstClient()->getAgencies(true);

		foreach((array)$aAgencies as $iAgencyId=>$sAgency) {
			
			$aAgencies[$iAgencyId] = array();
			
			$aAgencies[$iAgencyId]['name'] = $sAgency;
			
			$oAgency = Ext_Thebing_Agency::getInstance($iAgencyId);
			
			$aAgencies[$iAgencyId]['object'] = $oAgency;

			$aAgencies[$iAgencyId]['stats']['current_year'] = $oAgency->getBookingStats($iYearStart, $iYearEnd);
			$aAgencies[$iAgencyId]['stats']['last_year'] = $oAgency->getBookingStats($iLastYearStart, $iLastYearEnd);

		}

		uasort($aAgencies, ['Ext_Thebing_Welcome', 'sortAgencies']);
		$aAgencies = array_slice($aAgencies, 0, 10);

		foreach((array)$aAgencies as $iId=>$aAgency) {

			$sContent .= '<tr class="borderLeft">';
			$sContent .= '<td class="noBorder">'.$aAgency['name'].'</td>';

			$oAgency = $aAgency['object'];

			foreach((array)$aSchools as $aSchool) {

				$aStats = $oAgency->getBookingStats($iYearStart, $iYearEnd, $aSchool['id']);
				
				$sContent .= '<td style="text-align: right;">'.Ext_Thebing_Format::Int($aStats['bookings']).'</td>';
				$sContent .= '<td style="text-align: right;">'.Ext_Thebing_Format::Int($aStats['weeks']).'</td>';
				
			}
			// 01.01.2010- 22.02.2010 current year time range
			$sContent .= '<td style="text-align: right;">'.Ext_Thebing_Format::Int($aAgency['stats']['current_year']['bookings']).'</td>';
			$sContent .= '<td style="text-align: right;">'.Ext_Thebing_Format::Int($aAgency['stats']['current_year']['weeks']).'</td>';
			// 01.01.2009 - 22.02.2009 Last Year time range
			$sContent .= '<td style="text-align: right;">'.Ext_Thebing_Format::Int($aAgency['stats']['last_year']['bookings']).'</td>';
			$sContent .= '<td style="text-align: right;">'.Ext_Thebing_Format::Int($aAgency['stats']['last_year']['weeks']).'</td>';
			
			$sContent .= '</tr>';
			
		}

		$sContent .= '</table>';
		
		return $sContent;
	}
	
	public static function pending_payments() {
		
		$aTotal = array();
		
		$aSchools = Ext_Thebing_Client::getSchoolList();
		
		$iTotalAmount = 0;
		$iTotalPendingAmount = 0;
		
		$aFinalData = array();
		$aTotalAmount = array();
		foreach((array)$aSchools as $aSchool) {
			//$oSchool = Ext_Thebing_School::getInstance( $aSchool['id']);
			//$aInquirys = $oSchool->getInquirys();

			$sSql = " SELECT
						`ki`.`id`,
						`ki`.`amount_payed`,
						`ki`.`currency_id` `currency_id`
					FROM
						`ts_inquiries` `ki` INNER JOIN
						`ts_inquiries_journeys` `ts_i_j` ON
							`ts_i_j`.`inquiry_id` = `ki`.`id` AND
							`ts_i_j`.`active` = 1
					WHERE
						`ts_i_j`.`school_id` = :school_id AND
						`ki`.`confirmed` > 0 AND
						`ki`.`active` = 1";
			$aSql = array('school_id' => (int)$aSchool['id']);
			$aInquirys = DB::getPreparedQueryData($sSql, $aSql);

			foreach((array)$aInquirys as $aInquiry){

				$aTemp = array();
				$iAmount = $aInquiry['amount'];
				$iPayedAmount = $aInquiry['amount_payed'];
				$iAmount = $iAmount - $iPayedAmount;
				$iOpenAmount = 0;
				$iPendingAmount = 0;

				if($aInquiry['amount_finalpay_due'] < time() && $aInquiry['amount_finalpay_due'] > 0 && finalpay <= 0){
					$iPendingAmount = $iAmount;
				} elseif($aInquiry['amount_finalpay_due'] > 0 && $aInquiry['amount_finalpay'] <= 0) {
					$iOpenAmount = $iAmount;
				}
				
				$aFinalData[$aSchool['id']][$aInquiry['currency_id']]['openAmount'] += $iOpenAmount;
				$aFinalData[$aSchool['id']][$aInquiry['currency_id']]['pendingAmount'] += $iPendingAmount;
				$aTotalAmount[$aInquiry['currency_id']]['openAmount'] += $iOpenAmount;
				$aTotalAmount[$aInquiry['currency_id']]['pendingAmount'] += $iPendingAmount;
				
			}
		}
		foreach((array)$aTotalAmount as $iCurrency => $aData){
			if($aData['openAmount'] == 0 && $aData['pendingAmount'] == 0 ){
				unset($aTotalAmount[$iCurrency]);
			}
		}
		$sContent = '';
		$sContent .= '<table class="table table-hover">';
			$sContent .= '<tr class="noHighlight borderBottom">';
				$sContent .= '<th style="width: auto;">'.L10N::t('School', 'Thebing » Welcome').'</th>';
				foreach($aTotalAmount as $iCurrency => $aData){
					$sCurrency = '';
					$aCurrency[$iCurrency] = Ext_Thebing_Currency_Util::getCurrencyDataById($iCurrency);
					$sContent .= '<th style="width: 160px;">'.L10N::t('Fälliger Betrag', 'Thebing » Welcome').' '.$aCurrency[$iCurrency]['sign'].'</th>';
					$sContent .= '<th style="width: 160px;">'.L10N::t('Noch offen, nicht fällig', 'Thebing » Welcome').' '.$aCurrency[$iCurrency]['sign'].'</th>';
				}
			$sContent .= '</tr>';
		
		foreach((array)$aFinalData as $iSchool => $aCurrencys){
			$oSchool = Ext_Thebing_School::getInstance( $iSchool);
			$sContent .= '<tr class="borderLeft">';
				$sContent .= '<td class="noBorder">'.$oSchool->ext_1.'</td>';
				foreach($aTotalAmount as $iCurrency => $aData){
					$aData = (array)$aFinalData[$iSchool][$iCurrency];
					$sContent .= '<td style="text-align: right;">'.Ext_Thebing_Format::Number($aData['pendingAmount'],$iCurrency).'</td>';
					$sContent .= '<td style="text-align: right;">'.Ext_Thebing_Format::Number($aData['openAmount'],$iCurrency).'</td>';
				}
			$sContent .= '</tr>';
		}
		
		$sContent .= '<tr class="noHighlight borderTop">';
			$sContent .= '<th>'.L10N::t('Total', 'Thebing » Welcome').'</th>';
			foreach((array)$aTotalAmount as $iCurrency => $aData){
				$sContent .= '<td style="text-align: right;">'.Ext_Thebing_Format::Number($aData['pendingAmount'],$iCurrency).'</td>';
				$sContent .= '<td style="text-align: right;">'.Ext_Thebing_Format::Number($aData['openAmount'],$iCurrency).'</td>';
			}
		$sContent .= '</tr>';
		$sContent .= '</table>';
		
		return $sContent;
		
	}
	
	
	public static function students() {
		
		$aTotal = array();
		$aWeeks = array();
		$iTime = time();
		for($i=0; $i<=5; $i++) {
			$aWeek = Ext_Thebing_Util::getWeekTimestamps($iTime);
			$aWeeks[$aWeek['start']] = $aWeek;
			$iTime += (60*60*24*7);
		}
		
		$sContent = '';
		$sContent .= '<table class="table table-hover">';
		$sContent .= '<tr class="noHighlight borderBottom">';
		$sContent .= '<th style="width: auto;">'.L10N::t('School', 'Thebing » Welcome').'</th>';
		foreach((array)$aWeeks as $aWeek) {
			$sContent .= '<th style="width: 50px;">'.L10N::t('KW', 'Thebing » Welcome').' '.strftime("%V", $aWeek['start']).'</th>';
		}
		$sContent .= '</tr>';
		
		$aSchools = Ext_Thebing_Client::getSchoolList();
		
		foreach((array)$aSchools as $aSchool) {
			$oSchool = Ext_Thebing_School::getInstance( $aSchool['id']);
			
			$sContent .= '<tr class="borderLeft">';
			$sContent .= '<td class="noBorder">'.$aSchool['ext_1'].'</td>';
			foreach((array)$aWeeks as $iKey=>$aWeek) {
				$iStudents = $oSchool->getStudentStats($aWeek['start'], $aWeek['end']);
				$sContent .= '<td style="text-align: right;">'.Ext_Thebing_Format::Int($iStudents).'</td>';
				$aTotal[$iKey] += $iStudents;
			}
			$sContent .= '</tr>';
		}
		
		$sContent .= '<tr class="noHighlight borderTop">';
		$sContent .= '<th>'.L10N::t('Total', 'Thebing » Welcome').'</th>';
		foreach((array)$aWeeks as $iKey=>$aWeek) {
			$sContent .= '<th style="text-align: right;">'.Ext_Thebing_Format::Int($aTotal[$iKey]).'</th>';
		}
		$sContent .= '</tr>';
		$sContent .= '</table>';
		
		return $sContent;
		
	}
	
	public static function birthdays() {
		
		// Funktionen geben nun nur noch ein Array mit dem Benötigtem zurück und nicht weiter eine ganze Instanz (dgierling)
		$aCustomers		= Ext_Thebing_Customer_Search::getBirthdays(0, 0, true);
		$aSystemUsers	= Ext_Thebing_System_User_Search::getBirthdays();
		$aTeachers = Ext_Thebing_Teacher::getBirthdays();

		$oDate			= new WDDate();
		$oDate->set('00:00:00', WDDate::TIMES);
		$oDateTemp		= new WDDate();
		$aUsers = array();
		
		foreach($aCustomers as $aCustomer) {
		
			$aTemp = array();
			$aTemp['name'] = $aCustomer['lastname'].', '.$aCustomer['firstname'];
			$aTemp['sort_birthday'] = $aCustomer['birthday'];
			$aTemp['birthday'] = Ext_Thebing_Format::LocalDate($aCustomer['birthday']);

			$oDateTemp->set($aCustomer['birthday'], WDDate::DB_DATE);
			$iAge = $oDate->getDiff(WDDate::YEAR, $oDateTemp);

			$aTemp['age'] = $iAge;
			$aTemp['type'] = 'customer';
			$aUsers[] = $aTemp;
		}

		foreach($aSystemUsers as $aSystemUser) {
			$aTemp = array();
			$aTemp['name'] = $aSystemUser['firstname'].' '.$aSystemUser['lastname'];
			$aTemp['sort_birthday'] = $aSystemUser['birthday'];
			$aTemp['birthday'] = Ext_Thebing_Format::LocalDate($aSystemUser['birthday']);

			$oDateTemp->set($aSystemUser['birthday'], WDDate::DB_DATE);
			$iAge = $oDate->getDiff(WDDate::YEAR, $oDateTemp);

			$aTemp['age'] = $iAge;
			$aTemp['type'] = 'systemuser';
			$aUsers[] = $aTemp;
		}

		foreach($aTeachers as $aTeacher) {
			$aTemp = array();
			$aTemp['name'] = $aTeacher['firstname'].' '.$aTeacher['lastname'];
			$aTemp['sort_birthday'] = $aTeacher['birthday'];
			$aTemp['birthday'] = Ext_Thebing_Format::LocalDate($aTeacher['birthday']);
			
			$oDateTemp->set($aTeacher['birthday'], WDDate::DB_DATE);
			$iAge = $oDate->getDiff(WDDate::YEAR, $oDateTemp);
			
			$aTemp['age'] = $iAge;
			$aTemp['type'] = 'teacher';
			$aUsers[] = $aTemp;
		}

		if(!empty($aUsers)) {

			usort($aUsers, array('Ext_Thebing_Welcome', 'sortBirthday'));

			$aColors = array(
				'customer' => Ext_Thebing_Util::getColor('soft_orange', 15),
				'teacher' => Ext_Thebing_Util::getColor('soft_blue', 15),
				'systemuser' => Ext_Thebing_Util::getColor('soft_green', 15)
			);

			$sContent = '<style>.legend_color { margin-top: 3px; margin-right: 5px; display: block; float: left; height: 12px; width: 12px;} .legend_label { margin-right: 5px; display: block; float: left; height: 12px;}</style>';
			$sContent .= '<table class="table table-hover">';
			$sContent .= '<tr class="noHighlight">';
			$sContent .= '<th style="width: auto;">'.L10N::t('Name', 'Thebing » Welcome').'</th>';
			$sContent .= '<th style="width: 80px;">'.L10N::t('Geburtsdatum', 'Thebing » Welcome').'</th>';
			$sContent .= '<th style="width: 40px;">'.L10N::t('Alter', 'Thebing » Welcome').'</th>';
			$sContent .= '</tr>';
			
			foreach((array)$aUsers as $aUser){
				$sContent .= '<tr style="background-color:'.$aColors[$aUser['type']].'">';
				$sContent .= '<td>'.$aUser['name'].'</td>';
				$sContent .= '<td>'.$aUser['birthday'].'</td>';
				$sContent .= '<td style="text-align: right;">'.$aUser['age'].'</td>';
				$sContent .= '</tr>';
			}
			$sContent .= '</tr>';
			$sContent .= '<tr class="borderTop noHighlight"><td colspan="3">';

			$sContent .= '<div class="note"><span class="legend_label">'.L10N::t('Schüler', 'Thebing » Welcome').'</span> <span class="legend_color" style="background-color:'.$aColors['customer'].';"></span> <span class="legend_label">'.L10N::t('Lehrer', 'Thebing » Welcome').'</span> <span class="legend_color" style="background-color:'.$aColors['teacher'].';"></span> <span class="legend_label">'.L10N::t('Benutzer', 'Thebing » Welcome').'</span> <span class="legend_color" style="background-color:'.$aColors['systemuser'].';"></span></div>';
			$sContent .= '</td></tr>';
			$sContent .= '</table>';
		
		} else {

			$sContent = '<p>'.L10N::t('In den nächsten 14 Tagen wurden keine Geburtstage gefunden.', 'Thebing » Welcome').'</p>';

		}

		return $sContent;
	}

	public static function pending_confirmations() {
		
		$aSchools = Ext_Thebing_Client::getSchoolList();
		$sContent = '';
		$sContent .= '<table class="table table-hover">';
		$sContent .= '<tr class="noHighlight borderBottom">';
		$sContent .= '<th style="width: auto;">'.L10N::t('School', 'Thebing » Welcome').'</th>';
		$sContent .= '<th class="" style="width:100px;">< 24h</th>';
		$sContent .= '<th class="" style="width:100px;">> 24h < 48h</th>';
		$sContent .= '<th class="" style="width:100px;">> 48h</th>';
		$sContent .= '<th class="" style="width:100px;">'.L10N::t('total', 'Thebing » Welcome').'</th>';
		$aTotal = array();
		foreach((array)$aSchools as $aSchool) {
			$oSchool = Ext_Thebing_School::getInstance( $aSchool['id']);
			$aIqnuirys = $oSchool->getPendingConfirmationInquirys();
			$aData = array();
			foreach($aIqnuirys as $oIqnuiry){
				
				$i24 = strtotime('- 1 Day');
				$i48 = strtotime('- 1 Day');
				
				// Sonntag ( 2 Tage abziehen da SA und SO nicht mitbrechnet werden sollen )
				if(date('w',$oIqnuiry->created) == 0){
					$i24 = strtotime('- 2 Day',$i24);
					$i48 = strtotime('- 2 Day',$i48);
				}
				// Samstag ( 1 Tage abziehen da SA nicht mitbrechnet werden soll )
				if(date('w',$oIqnuiry->created) == 6){
					$i24 = strtotime('- 1 Day',$i24);
					$i48 = strtotime('- 1 Day',$i48);
				}
				
				if($oIqnuiry->created >= $i24){
					$aData[0]++; 
					$aData[3]++;
					$aTotal[0]++; 
					$aTotal[3]++;
				}
				if($oIqnuiry->created < $i24 && $oIqnuiry->created >= $i48){
					$aData[1]++; 
					$aData[3]++;
					$aTotal[1]++; 
					$aTotal[3]++;
				}
				if($oIqnuiry->created < $i48){
					$aData[2]++; 
					$aData[3]++;
					$aTotal[2]++; 
					$aTotal[3]++;
				}
			}
			$sContent .= '<tr class="borderLeft">';
			$sContent .= '<td class="noBorder">'.$aSchool['ext_1'].'</td>';
			$sContent .= '<td style="text-align:right;">'.(int)$aData[0].'</td>';
			$sContent .= '<td style="text-align:right;">'.(int)$aData[1].'</td>';
			$sContent .= '<td style="text-align:right;">'.(int)$aData[2].'</td>';
			$sContent .= '<td style="text-align:right;">'.(int)$aData[3].'</td>';
			$sContent .= '</tr>';
		}
		
		$sContent .= '<tr class="noHighlight borderTop">';
		$sContent .= '<th style="text-align:right;">'.L10N::t('Total', 'Thebing » Welcome').'</th>';
		$sContent .= '<td style="text-align:right;">'.(int)$aTotal[0].'</td>';
		$sContent .= '<td style="text-align:right;">'.(int)$aTotal[1].'</td>';
		$sContent .= '<td style="text-align:right;">'.(int)$aTotal[2].'</td>';
		$sContent .= '<td style="text-align:right;">'.(int)$aTotal[3].'</td>';
		$sContent .= '</tr>';
		$sContent .= '</table>';

		return $sContent;
		
	}

	public static function generateStudentsInSchoolStatistic($bForceRefresh=false) {
		
		$aCache = WDCache::get('thebing_welcome_students_course_related');

		// Muss manuell gecached werden wegen Rechteprüfung auf Schule
		if(
			$aCache === null ||
			// TODO über Parameter lösen
			$_GET['refresh_welcome'] === 'both_16' ||
			$bForceRefresh === true
		) {
			// Cache immer auf leer setzen, damit veraltete Wochen rausfliegen
			$aCache = [];

			$dFrom = (new DateTime())->modify('monday this week');
			$dUntil = (new DateTime())->modify('sunday this week');
			$dUntil->add(new DateInterval('P9W')); // Sind 10 Wochen wegen Endtag Sonntag

			// Wochen von diesem Jahr und gleiche Kalenderwochen aus dem Vorjahr
			$aWeeksThisYear = iterator_to_array(new DatePeriod($dFrom, new DateInterval('P1W'), $dUntil));
			$aWeeksLastYear = [];
			foreach($aWeeksThisYear as $dDate) {
				$dNewDate = new DateTime();
				$dNewDate->setISODate($dDate->format('Y') - 1, (int)$dDate->format('W'), 1);
				$aWeeksLastYear[] = $dNewDate;
			}
			
			$aCache['weeks_this_year'] = $aWeeksThisYear;
			$aCache['weeks_last_year'] = $aWeeksLastYear;
			
			$aSchools = Ext_Thebing_Client::getSchoolList(false, 0, true);
			foreach($aSchools as $oSchool) {

				/**
				 * Closure zum Ermitteln der Schülerzahlen
				 *
				 * @param string $sKey
				 * @param DateTime[] $aDates
				 */
				$oGetStudentCount = function($sKey, $aDates) use(&$aCache, $oSchool) {
					foreach($aDates as $dDate) {
						$dTmpFrom = $dDate->modify('monday this week');
						$dTmpUntil = clone $dTmpFrom;
						$dTmpUntil = $dTmpUntil->modify('sunday this week');
						$aCache['schools'][$oSchool->id][$sKey][$dTmpFrom->format('W')] = (int)$oSchool->getStudentStats($dTmpFrom->getTimestamp(), $dTmpUntil->getTimestamp());
					}
				};

				// Schülerzahlen für dieses und letztes Jahr
				$oGetStudentCount('current', $aWeeksThisYear);
				$oGetStudentCount('last', $aWeeksLastYear);

				// Differenzen ausrechnen
				foreach($aCache['schools'][$oSchool->id]['current'] as $iWeek => $iStudentCount) {

					if(
						// Hier kann neben 0 auch NULL drin stehen (Query lieferte gar nichts)
						!empty($aCache['schools'][$oSchool->id]['last'][$iWeek]) &&
						$aCache['schools'][$oSchool->id]['last'][$iWeek] !== 0
					) {
						// Differenz in Prozent ausrechnen
						$fDiff = round((100 / $aCache['schools'][$oSchool->id]['last'][$iWeek] * $iStudentCount) - 100);
					} else {
						// Fall für: Division durch 0
						$fDiff = null;
					}

					$aCache['schools'][$oSchool->id]['diff'][$iWeek] = $fDiff;
				}
			}

			$aCache['last_updated'] = time();
			WDCache::set('thebing_welcome_students_course_related', 60*60*24*7, $aCache);

		}
		
		return $aCache;
	}
	
	/**
	 * Schüler in der Schule (kursbezogen)
	 *
	 * @return string
	 */
	public static function getStudentsInSchoolStatistic($bForceRefresh=false) {

		$aLabels = array(
			'current' => L10N::t('Aktuelles Jahr', self::TRANSLATION_PATH),
			'last' => L10N::t('Letztes Jahr', self::TRANSLATION_PATH),
			'diff' => L10N::t('Differenz', self::TRANSLATION_PATH)
		);

		$aCache = self::generateStudentsInSchoolStatistic($bForceRefresh);

		$aWeeksThisYear = $aCache['weeks_this_year'];

		// Beginn Tabellenkopf
		$sContent = '<table class="table table-hover">';
		$sContent .= '<thead>';
		$sContent .= '<tr class="noHighlight">';
		$sContent .= '<th colspan="2">'.L10N::t('Schule', self::TRANSLATION_PATH).'</th>';

		foreach($aWeeksThisYear as $dDate) {
			$sContent .= '<th style="width: 60px">'.L10N::t('Woche', self::TRANSLATION_PATH).' '.$dDate->format('W').'</th>';
		}

		$sContent .= '</tr>';
		$sContent .= '</thead>';

		// Beginn Tabellenkörper
		$sContent .= '<tbody>';

		foreach($aCache['schools'] as $iSchoolId => $aStudentCounts) {

			$oSchool = Ext_Thebing_School::getInstance($iSchoolId);

			// Beginn von der Ausgabe der Werte
			$bFirst = true;
			foreach($aStudentCounts as $sLabelKey => $aWeekData) {

				$sContent .= '<tr class="borderTop">';

				// Namen der Schule nur einmal anzeigen
				if($bFirst) {
					$sContent .= '<td rowspan="3">'.$oSchool->getName().'</td>';
				}

				$sContent .= '<td>'.$aLabels[$sLabelKey].'</td>';

				foreach($aWeekData as $iWeek => $iNumber) {

					// Werte formatieren
					if($sLabelKey === 'diff') {
						if($iNumber === null) {
							$sValue = '–';
						} else {
							$sValue = $iNumber.'&thinsp;%';
							if($iNumber < 0) {
								$sValue = '<span style="color: red;">'.$sValue.'</span>';
							} elseif($iNumber > 0) {
								$sValue = '<span style="color: green;">'.$sValue.'</span>';
							}
						}
					} else {
						$sValue = Ext_Thebing_Format::Int($iNumber);
					}

					$sContent .= '<td>'.$sValue.'</td>';
				}

				$sContent .= '</tr>';
				$bFirst = false;
			}

		}

		$sContent .= '</tbody>';
		$sContent .= '</table>';

		#$sContent .= '<div class="note">'.L10N::t('Stand', self::TRANSLATION_PATH).': '.Ext_Thebing_Format::LocalDateTime($aCache['last_updated']).'</div>';

		return $sContent;
	}

	public static function generatePendingHousingPlacementsStatistic($bForceRefresh=false) {

		$oFrom = new DateTime('00:00:00');
		$oUntil = new DateTime('23:59:59');

		$oUntil->add(new DateInterval('P10W'));

		$oWeeksThisYear = new DatePeriod($oFrom, new DateInterval('P1W'), $oUntil);

		$aCache = WDCache::get('thebing_welcome_pending_housing_placements');

		// Muss manuell gecached werden wegen Rechteprüfung auf Schule
		if(
			$aCache === null ||
			// TODO über Parameter lösen
			$_GET['refresh_welcome'] === 'both_17' ||
			$bForceRefresh === true
		) {
			$aCache = ['schools' => []];

			$aSchools = Ext_Thebing_Client::getSchoolList(false, 0, true);

			foreach($aSchools as $oSchool) {

				// Schülerzahlen ermitteln: Gebuchte und zugewiesene Unterkünfte
				foreach(array(false, true) as $bAllocated) {
					foreach($oWeeksThisYear as $oDate) {

						// Auf aktuelle Woche setzen
						$oTmpFrom = $oDate->modify('monday this week');
						$oTmpFrom->setTime(0, 0, 0);
						$oTmpUntil = clone $oTmpFrom;
						$oTmpUntil = $oTmpUntil->modify('sunday this week');
						$oTmpUntil->setTime(23, 59, 59);

						$sKey = 'booked';
						if($bAllocated) {
							$sKey = 'allocated';
						}

						$aCount = $oSchool->getBookedAccommodationCountPerAccommodationCategory($oTmpFrom, $oTmpUntil, $bAllocated);
						foreach($aCount as $iCategoryId => $iCount) {

							// Array direkt hierarisch korrekt aufbauen
							$aCache['schools'][$oSchool->id][$iCategoryId][$sKey][$oTmpFrom->format('W')] = $iCount;

							// Ermitteln, welche Unterkunftskategorien überhaupt Daten haben
							if($iCount > 0) {
								$aCache['shown_categories'][$oSchool->id][$iCategoryId] = 1;
							}
						}
					}
				}
			}

			$aCache['last_update'] = time();
			WDCache::set('thebing_welcome_pending_housing_placements', 60 * 60 * 24 * 7, $aCache);

		}

		return $aCache;
	}
	
	/**
	 * Offene Unterkunftszuweisungen
	 *
	 * @return string
	 */
	public static function getPendingHousingPlacementsStatistic() {

		$aLabels = array(
			'booked' => L10N::t('Gebucht', self::TRANSLATION_PATH),
			'allocated' => L10N::t('Zugewiesen', self::TRANSLATION_PATH),
			'open' => L10N::t('Offen', self::TRANSLATION_PATH)
		);

		$oFrom = new DateTime('00:00:00');
		$oUntil = new DateTime('23:59:59');

		$oUntil->add(new DateInterval('P10W'));

		$oWeeksThisYear = new DatePeriod($oFrom, new DateInterval('P1W'), $oUntil);

		$aCache = self::generatePendingHousingPlacementsStatistic();

		// Beginn Tabellenkopf
		$sContent = '<table class="table table-hover">';

		// Header nur anzeigen, wenn auch irgendwelche Daten (Kategorien) angezeigt werden
		if(!empty($aCache['shown_categories'])) {
			$sContent .= '<thead>';
			$sContent .= '<tr class="noHighlight">';
			$sContent .= '<th colspan="3">'.L10N::t('Schule', self::TRANSLATION_PATH).'</th>';

			foreach($oWeeksThisYear as $oDate) {
				$sContent .= '<th style="width: 60px">'.L10N::t('Woche', self::TRANSLATION_PATH).' '.$oDate->format('W') . '</th>';
			}

			$sContent .= '</tr>';
			$sContent .= '</thead>';
		}

		// Beginn Tabellenkörper
		$sContent .= '<tbody>';

		foreach($aCache['schools'] as $iSchoolId => $aCategoryData) {

			$oSchool = Ext_Thebing_School::getInstance($iSchoolId);
			$aAccommodationCategories = $oSchool->getAccommodationCategoriesList(true);

			// Beginn der Ausgabe von den Werten
			$bShowSchool = true;
			foreach($aCategoryData as $iCategoryId => $aRow) {

				// Unterkunftskategorien, welche lediglich 0er-Zellen haben, überspringen
				if(!isset($aCache['shown_categories'][$oSchool->id][$iCategoryId])) {
					continue;
				}

				// »Offen« ausrechnen: Noch zuzuweisende Unterkunftsbuchungen
				foreach($aRow['booked'] as $iWeek => $iAccoBookingCount) {
					$aRow['open'][$iWeek] = $iAccoBookingCount - $aRow['allocated'][$iWeek];
				}

				// Array durchlaufen: booked => [Week => Count]
				$iLastCategoryId = 0;
				foreach($aRow as $sLabelKey => $aWeeksAndCount) {

					$sContent .= '<tr class="borderTop">';

					// Namen der Schule nur einmal anzeigen
					if($bShowSchool) {
						$sContent .= '<td rowspan="'.(count($aCache['shown_categories'][$oSchool->id]) * 3).'">'.$oSchool->getName().'</td>';
						$bShowSchool = false;
					}

					// Name der Unterkunftskategorie
					if($iLastCategoryId != $iCategoryId) {
						$sContent .= '<td rowspan="3">'.$aAccommodationCategories[$iCategoryId].'</td>';
						$iLastCategoryId = $iCategoryId;
					}

					// Gebucht, zugewiesen, offen
					$sContent .= '<td>'.$aLabels[$sLabelKey].'</td>';

					// Wochen über Iterator durchlaufen, da Daten-Array die Struktur nicht enthält (0 ausgelassen)
					foreach($oWeeksThisYear as $oDate) {
						$iWeek = $oDate->format('W');
						$iCount = 0;

						if(isset($aWeeksAndCount[$iWeek])) {
							$iCount = $aWeeksAndCount[$iWeek];
						}

						$sCount = Ext_Thebing_Format::Int($iCount);

						// Offene Unterkunftszuweisungen sollen rot dargestellt werden
						if(
							$sLabelKey === 'open' &&
							$iCount >= 1
						) {
							$sCount = '<span style="color: red">'.$sCount.'</span>';
						}

						$sContent .= '<td>'.$sCount.'</td>';
					}

					$sContent .= '</tr>';
				}
			}

		}

		// Da leere Kategorien ausgeblendet werden, kann auch alles leer sein
		if(empty($aCache['shown_categories'])) {
			$sContent .= '<tr><td style="text-align: center; font-weight: bold;">';
			$sContent .= L10N::t('Keine Daten verfügbar.', self::TRANSLATION_PATH);
			$sContent .= '</td></tr>';
		}

		$sContent .= '</tbody>';
		$sContent .= '</table>';

		#$sContent .= '<div class="note">'.L10N::t('Stand', self::TRANSLATION_PATH).': '.Ext_Thebing_Format::LocalDateTime($aCache['last_update']).'</div>';

		return $sContent;
	}

}