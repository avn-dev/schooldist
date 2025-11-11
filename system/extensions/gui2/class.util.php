<?php

/**
 * Utility-Klasse von Gui2
 */
class Ext_Gui2_Util {

	/*
	 * Definiert die spaltenbreiten der GUI Tabellen
	 */
	public static function getTableColumnWidth($sKey){

		switch ($sKey){
			// Datum
			case 'date':
				return 90;
				break;
			// Generelle Namen / Titel von Items (kurze Bezeichnungen)
			case 'short_name':
				return 80;
				break;
			// Generelle Namen / Titel von Items
			case 'agency_name':
			case 'group_name':
			case 'name':
				return 150;
				break;
			case 'char';
				return 35;
				break;
			// Zweistelliger ISO-Kürzel
			case 'iso';
				return 50;
				break;
			case 'id';
				return 60;
				break;
			case 'number';
			case 'document_number';
			case 'customer_number';
				return 120;
				break;
			case 'comment':
				return 200;
				break;
			// TODO: Entfernen
			case 'familyinfo':
				return 150;
				break; 
			case 'long_description':
				return 300;
				break;
			case 'extra_long_description':
				return 500;
				break;
			case 'icon':
				return 40;
				break;
			case 'niveau':
				return 100;
				break;
			case 'gender';
				return 80;
				break;
			case 'age';
				return 50;
				break;
			case 'nationality':
				return 100;
				break;
			case 'language':
				return 100;
				break;
			// Beträge
			case 'amount':
				return 120;
				break;
			// Datum kurz
			case 'date_short':
				return 60;
				break;
			// Datums periode
			case 'date_period':
				return 160;
				break;
			// TODO: Entfernen
			case 'week_title':
				return 250;
				break;
			// Datum mit Benutzer
			case 'date_user':
				return 250;
				break;
			// Uhrzeit
			case 'time':
				return 60;
				break;
			// Datum - Uhrzeit
			case 'date_time':
				return 120;
				break;
			// Anzahl von Items
			case 'count':
				return 100;
				break;
			// Ja oder Nein / 0 oder 1
			case 'yes_no':
				return 50;
				break;
			case 'transfer':
				return 120;
				break;
			//Kunden- oder Benutzername
			case 'user_name':
			case 'person_name':
			case 'customer_name':
				return 180;
				break;
			// Kunden Mail
			case 'email':
				return 200;
				break;
			// Telefon, Mobil, Fax
			case 'phone':
				return 160;
				break;
			case 'group_short':
			case 'agency_short':
				return 60;
				break;
			case 'zip':
				return 60;
				break;
			// TODO: Entfernen
			case 'group_course_options':
			case 'group_accommodation_options':
			case 'group_transfer_option':
				return 130;
				break;
			// TODO: Entfernen
			case 'courseweek_from_until':
				return 80;
				break;
			// TODO: Entfernen
			case 'nights':
				return 50;
				break;
			// Default
			default:
				return 120;
		}

	}

	/*
	 * Funktion liefert global die Colorcodes für das System zurück
	 */
	public static function getColor($sUse=false, $iFactor=100) {

		$aColors = array();
		$aColors['green']			= '#80ff80'; // #66E275
		$aColors['payed']			= $aColors['green'];
		$aColors['lightgreen']		= '#CCFFAA';
		$aColors['marked']			= '#DDFFAC'; // #66E275
		$aColors['red']				= '#ff8080'; // #FF7A73
		$aColors['red_font']		= '#FF0000';
		$aColors['orange']			= '#FFD373';
		$aColors['yellow']			= '#ffff99'; // #FF7A73
		$aColors['highlight']		= '#22bbff'; // Blau
		$aColors['selected']		= '#a7cdf0'; //'#ffd373';
		$aColors['inactive']		= '#666';
		$aColors['changed']			= '#FFDDEE';
		$aColors['storno']			= '#CCEEFF';
		$aColors['substitute_full']	= '#FF0000';
		$aColors['substitute_part']	= '#FFB900';
		$aColors['substitute_teacher'] = '#2E97E0';

		$aColors['soft_orange']			= '#E0642E';
		$aColors['soft_yellow']			= '#E0D62E';
		$aColors['soft_blue']			= '#2E97E0';
		$aColors['soft_purple']			= '#B02EE0';
		$aColors['soft_green']			= '#BCE02E';

		$aColors['good']			= '#c6efce';
		$aColors['good_font']		= '#006100';
		$aColors['neutral']			= '#ffeb9c';
		$aColors['neutral_font']	= '#9c6500';
		$aColors['bad']				= '#ffc7ce';
		$aColors['bad_font']		= '#9c0006';

		$aColors['accent1']			= '#4f81bd';
		$aColors['accent2']			= '#c0504d';
		$aColors['accent3']			= '#9bbb59';
		$aColors['accent4']			= '#8064a2';
		$aColors['accent5']			= '#4bacc6';
		$aColors['accent6']			= '#f79646';
		
		// Matching
		$aColors['matching_share']	= '#80ff80';
		$aColors['matching_male']	= '#22BBFF';
		$aColors['matching_female']	= '#FF8080';

		$aColors['new'] = '#FFCCAA'; // rot
		$aColors['edit'] = '#FFFF99'; // gelb
		$aColors['delete'] = '#FFFF99'; // gelb
		$aColors['old'] = '#CCFFAA'; // grün

		$aColors['inactive_font']	= '#666666';

		if(!$sUse) {
			return $aColors;
		} else {

			if(!isset ($aColors[$sUse])){
				return false;
			}

			if($iFactor < 100) {
				// Deckkraft anpassen
				$aRGB = \Core\Helper\Color::convertHex2RGB($aColors[$sUse]);

				$iMul = (100 - $iFactor) / 100;

				foreach((array)$aRGB as $sKey=>$iValue) {
					$aRGB[$sKey] = round($iValue + ((255 - $iValue) * $iMul));
				}

				$aColors[$sUse] = \Core\Helper\Color::convertRGB2Hex($aRGB);

			}

			return $aColors[$sUse];
		}

	}

	/**
	 * @TODO Eigentlich müsste man alle Einträge löschen können, bei denen das Icon nicht durch FA ersetzt wurde
	 */
	public static function getIcon($sUse=false) {

		$aIcons = array();
		$aIcons['confirm']		= 'fa-check';
		$aIcons['delete']		= 'fa-minus-circle';
		$aIcons['add']			= 'fa-plus-circle';
		$aIcons['edit']			= 'fa-pencil';
		$aIcons['save']			= 'fa-save';
		$aIcons['csv']		= 'fa-table';

		$aIcons['cancel']	= 'fa-times';
		$aIcons['info']		= 'fa-info-circle';

		$aIcons['calendar']		= 'fa-calendar';
		$aIcons['access']		='fa-key';
		$aIcons['money']		='fa-money';
		$aIcons['paperplane']		='fa-paper-plane';
		$aIcons['coins']		='/admin/extensions/tc/icons/coins.png';
		$aIcons['copy_down']	='/admin/extensions/tc/icons/arrow_down.png';
		$aIcons['copy_left']	='/admin/extensions/tc/icons/arrow_left.png';
		$aIcons['eye']			='/admin/extensions/tc/icons/eye.png';
		$aIcons['on']			='/admin/extensions/tc/icons/lightbulb.png';
		$aIcons['off']			='/admin/extensions/tc/icons/lightbulb_off.png';
		$aIcons['export']		='fa-file-excel';
		$aIcons['search']		='fa-search';
		$aIcons['error']		='fa-exclamation-triangle';
		$aIcons['group']		='fa-users';
		$aIcons['door_open']    ='/admin/extensions/tc/icons/door_open.png';
		
		$aIcons['application_view_column'] = 'fa-table';

		$aIcons['page']			='fa-file-o';
		$aIcons['page_add']		='fa-plus-square-o';
		$aIcons['page_delete']	='fa-user-times';
		$aIcons['page_copy']	='fa-copy';
		$aIcons['page_refresh']	='fa-refresh';
		$aIcons['page_go']		='fa-exchange';

		$aIcons['pdf']			='fa-file-pdf-o';
		$aIcons['pdf_inactive'] ='fa-file-pdf-o';

		$aIcons['page_edit'] = 'far fa-edit';
		
		$aIcons['plugin_add']	='/admin/extensions/tc/icons/plugin_add.png';
		
		$aIcons['bullet_black']	='/admin/extensions/tc/icons/bullet_black.png';
		$aIcons['bullet_blue']	='/admin/extensions/tc/icons/bullet_blue.png';
		$aIcons['bullet_orange']='/admin/extensions/tc/icons/bullet_orange.png';
		$aIcons['bullet_pink']	='/admin/extensions/tc/icons/bullet_pink.png';
		$aIcons['bullet_purple']='/admin/extensions/tc/icons/bullet_purple.png';
		$aIcons['bullet_red']	='/admin/extensions/tc/icons/bullet_red.png';
		$aIcons['bullet_error']	='/admin/extensions/tc/icons/bullet_error.png';

		$aIcons['brick_go']		='/admin/extensions/tc/icons/brick_go.png';
		$aIcons['brick_add']	='/admin/extensions/tc/icons/brick_add.png';
		$aIcons['brick_delete']	='/admin/extensions/tc/icons/brick_delete.png';

		$aIcons['allocate']		= 'fa-share-alt';		
		
		$aIcons['web']			= 'fa-globe';		
		$aIcons['info']			= 'fa-info-circle';
		$aIcons['settings']		= 'fa-cog';		
		
		$aIcons['refresh']		= 'fa-refresh';
		$aIcons['in']			= 'fa-arrow-right';		
		$aIcons['out']			= 'fa-arrow-left';
		
		$aIcons['attachment']	= 'fa-paperclip';		
		
		$aIcons['back']			= '/admin/media/control_rewind.png';
		$aIcons['next']			= '/admin/media/control_fastforward.png';

		$aIcons['print']        = 'fa-print';
        
		$aIcons['bomb']         ='/admin/extensions/tc/icons/bomb.png';
		$aIcons['broom']        ='fa-paint-brush';
		$aIcons['cog_go']       ='fa-cog';
		$aIcons['timeline_marker']       ='fa-history';
		
		$aIcons['font_add']		= 'fa-font';

		$aIcons['h3']			= 'fa-header';

		if(!$sUse) {
			return $aIcons;
		} else {
			return $aIcons[$sUse];
		}

	}

	/**
	 * @todo Kommentar!
	 * @param $aArray
	 * @param string $sText
	 * @param int $sValue
	 * @return array
	 */
	public static function addLabelItem($aArray, string $sText='', int|string $sValue=0):array {

		$sText = '-- '.$sText.' --';

		$aArray = Util::addEmptyItem($aArray, $sText, $sValue);

		return $aArray;

	}

	/**
	 * Ermittelt anhand des KalenderWertes den Wochentag
	 *
	 * @param int $iTyp
	 *    0 = Date DD.MM.YYYY
	 *    1 = datetime
	 *    2 = date
	 * 	  3 = timestamp
	 * @param $sValue
	 * @return int
	 * @throws Exception
	 */
	public static function getWeekDay($iTyp = 0, $sValue) {

		if($sValue == '') {
			return -1;
		}

		$oDate = new WDDate();
		switch($iTyp) {
			case 1: $oDate->set($sValue, WDDate::DB_DATETIME); break;
			case 2: $oDate->set($sValue, WDDate::DB_DATE); break;
			case 3: $oDate->set($sValue, WDDate::TIMESTAMP); break;
			default: throw new Exception("Invalid Date Format");
		}

		$iWeekday = $oDate->get(WDDate::WEEKDAY);

		return $iWeekday;
	}

	/**
	 * @param string $sString
	 * @param int $iLength
	 * @param string $sEtc
	 * @param bool $bBreakWords
	 * @param bool $bMiddle
	 * @return mixed|string
	 */
	public static function truncateString($sString, $iLength, $sEtc = '...', $bBreakWords = false, $bMiddle = false) {

		if($iLength == 0) {
			return '';
		}

		if(mb_strlen($sString) > $iLength) {
			$iLength -= mb_strlen($sEtc);
			if (!$bBreakWords && !$bMiddle) {
				$sString = preg_replace('/\s+?(\S+)?$/', '', mb_substr($sString, 0, $iLength+1));
			}
			if(!$bMiddle) {
				return mb_substr($sString, 0, $iLength).$sEtc;
			} else {
				return mb_substr($sString, 0, $iLength/2) . $sEtc . mb_substr($sString, -$iLength/2);
			}
		} else {
			return $sString;
		}

	}

}