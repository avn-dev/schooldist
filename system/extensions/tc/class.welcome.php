<?php

use Admin\Helper\Welcome;
use Illuminate\Support\Facades\Notification;

class Ext_TC_Welcome extends Welcome {
	
	public static function sortBirthday($a, $b) {

		$oDateA = new WDDate($a['sort_birthday'], WDDate::DB_DATE);
		$oDateA->set(date('Y'), WDDate::YEAR);
		$oDateB = new WDDate($b['sort_birthday'], WDDate::DB_DATE);
		$oDateB->set(date('Y'), WDDate::YEAR);
		
		$iCompare = $oDateA->compare($oDateB);
		
		return $iCompare;

	}

	public static function readFile($bCaching = false, $bNotify = true) {
		global $system_data;

		$sCacheKey = 'tc_welcome_news_'.$system_data['systemlanguage'];

		if ($bCaching && !empty($cache = \WDCache::get($sCacheKey, true))) {
			return $cache;
		}

		$aEntries = array();

		$aVariables = array();
		$aVariables['language'] = $system_data['systemlanguage'];

		$oUpdate = new Ext_TC_Update('update', $system_data['license']);

		$sContent = $oUpdate->getFileContents('/rss_news.php', $aVariables);

		if(!empty($sContent)){

			$bParse = true;

			try{
				$oXML = new SimpleXMLElement($sContent);
			}catch(Exception $e){
				$bParse = false;
			}

			if($bParse){
				foreach ($oXML->channel->item as $item) {

					$iDate = strtotime((string)$item->pubDate);

					$aEntries[] = array(
						'key' => (string)$item->key,
						'type' => (string)$item->type,
						'title' => (string)$item->title,
						'image' => (string)$item->image,
						'important' => (int)$item->important,
						'date' => $iDate,
						'content'=> (string)$item->description
					);
				}
			}
		}

		$payload = [\Carbon\Carbon::now()->getTimestamp(), $aEntries];

		\WDCache::set($sCacheKey, 60*60, $payload, true);

		if ($bNotify) {
			\Tc\Service\SystemEvents::dispatchNewsEvents();
		}

		return $payload;
	}

	/*public static function checkAnnouncements($bCaching = false) {
		
		$aEntries = self::readFile($bCaching);
		
		$aNews = array_filter($aEntries, function($aEntry) {
			return $aEntry['type'] === 'announcement';
		});
		
		return $aNews;
	}*/
	
	public static function checkNews($bCaching = false) {
		
		[$dateAsOf, $aEntries] = self::readFile($bCaching);
		
		$aNews = array_filter($aEntries, function($aEntry) {
			return $aEntry['type'] === 'news';
		});
		
		return [$dateAsOf, $aNews];
	}

	public static function checkImportantNews($bCaching = false) {
		[$dateAsOf, $aNews] = self::checkNews($bCaching);
		return array_filter($aNews, function($aEntry) {
			return (bool)$aEntry['important'];
		});
	}
	
	public static function getNewsContent() {

		[$dateAsOf, $aNews] = self::checkNews();

		if(empty($aNews)) {
			return '';
		}

		$oFormat = Ext_TC_Factory::getObject('Ext_TC_Gui2_Format_Date_Time');
		$aDummy = array();
		$oDummy = new stdClass;

		$sContent = '<div class="news" id="news-accordion" role="tablist" aria-multiselectable="true">';
		
		foreach((array)$aNews as $iItem=>$aItem) {
			
			$sContent .= '<div class="news-item">';
			$sContent .= '<div class="news-header" role="tab" id="heading-'.$iItem.'">';
			$sContent .= '<h4 class="collapsed" role="button" data-toggle="collapse" data-parent="#news-accordion" href="#collapse-'.$iItem.'" aria-expanded="false" aria-controls="collapse-'.$iItem.'">';
			
			if($aItem['important']) {
				$sContent .= '<i class="fa fa-star"></i> ';
			}
			
			$sContent .= $aItem['title'].' <small>'.$oFormat->format($aItem['date'], $oDummy, $aDummy).'</small>';
			#$sContent .= '</a>';
			$sContent .= '<div class="pull-right down"><i class="fa fa-angle-down"></i></div>';
			$sContent .= '<div class="pull-right up"><i class="fa fa-angle-up"></i></div>';
			$sContent .= '</h4>';
			$sContent .= '</div>';
			$sContent .= '<div id="collapse-'.$iItem.'" class="news-body collapse" role="tabpanel" aria-labelledby="heading-'.$iItem.'">';
			$sContent .= $aItem['content'];
			$sContent .= '</div>';
			$sContent .= '</div>';
						
		}

		$sContent .= '</div>';

		return $sContent;
	}

	public static function getSystemInfo() {

		$sErrorColor = '#FF0000';
		
		$oSystemData = new Ext_TC_Systeminformation();
		$aSystemData = $oSystemData->getInformation();

		$aMapping = $oSystemData->getDescriptionMapping();
		
		$sStyle = '';
		if(!empty($oSystemData->aNotifications)) {
			$sStyle .= 'border-bottom: 1px solid #CCC;';
		}		

		$sTableHead = '';
		$sTableHead .= '<table class="table table-hover" style="'.$sStyle.'">';
		
		$sTableBody = $sTableHead;
		foreach($aSystemData as $sKey => $aValue) {
			
			$sSize = '';
			if($aValue['type'] == 'byte') {
				$sSize = \Util::formatFilesize($aValue['current']);
			} else if($aValue['type'] == 'percent') {
				$sSize = $aValue['current'] . ' %';
			} else if($aValue['type'] == 'int') {
				$sSize = $aValue['current'];
			}
			
			$aSize = explode(' ', $sSize);
			if(count($aSize) == 2) {
				$sSize = Ext_TC_Number::format($aSize[0]) . ' ' . $aSize[1];
			}
				
			if(!empty($oSystemData->aNotifications[$sKey])) {
				$sSize = '<span style="color: '.$sErrorColor.';">'.$sSize.'</span>';
			}
			
			$sAdditional = '';
			$aAdditional = [];
			if($sKey == 'internal_memory_used') {
				$aAdditional[] = Util::formatFilesize($aValue['total']);
			}
			
			if(!empty($aValue['additional'])) {
				$aAdditional[] = $aValue['additional'];
			}

			if(!empty($aAdditional)) {
				$sAdditional = ' ('.implode(', ', $aAdditional).')';
			}
			
			$sTableBody .= '<tr><td>'.L10N::t($aMapping[$sKey]) . $sAdditional.':</td>';
			
			$sProcessbar = '';
			if(!empty($aValue['total'])) {
				$sProcessbar = Ext_TC_Util::buildProgressBar($aValue['current'], $aValue['total']);				
			}
			$sTableBody .= '<td style="width: 10%;">'.$sProcessbar.'</td>';
			
			$sTableBody .= '<td style="text-align: right; width: 15%;">'.$sSize.'</td></tr>';			
		}
		$sTableBody .= '</table>';

		$sNotifications = '';
		if(!empty($oSystemData->aNotifications)) {

			$sDescription = '<ul>';
			
			foreach($oSystemData->aNotifications as $sError) {
				$sDescription .= '<li>'.$sError.'</li>';
			}
			
			$sDescription .= '</ul>';
			
			$sNotifications .= '<br/>';
			$sNotifications .= self::createWarning(L10N::t('Bitte beachten Sie die folgenden Warnungen:'), $sDescription);

		}
		
		$sTable = $sTableBody . $sNotifications;
		
		return $sTable;		
	}
		
	public static function getAutoImapContent() {
		
		$sTableBody = '';
		
		$oSystemData = new Ext_TC_Systeminformation();
		$aAccounts = $oSystemData->getAutoImapSize();
		
		if(!empty($aAccounts)) {

			$sTableBody .= '<table class="table table-hover">';
			$sTableBody .= '<tr class="noHighlight borderBottom"><th>'. L10N::t('E-Mail-Konto').'</th><th class="text-right">'. L10N::t('Anzahl E-Mails').'</th></tr>';

			$bShowWarning = false;

			foreach($aAccounts as $aAccount) {
				$sClass = '';
				if($aAccount['mails'] > 100) {
					$sClass = 'bg-danger';
					$bShowWarning = true;
				}
				
				if(!empty($aAccount['error'])) {
					$sClass = 'bg-danger';
					$aAccount['mails'] = $aAccount['error'];
				}
				
				$sTableBody .= '<tr class="'.$sClass.'"><td>'.$aAccount['account'].'</td><td class="text-right">'.$aAccount['mails'].'</td></tr>';
			}
			$sTableBody .= '</table>';
			
			if($bShowWarning) {
				$sTableBody .= self::createWarning(L10N::t('Bitte beachten Sie die folgenden Warnungen:'), L10N::t('Es sind E-Mail-Konten mit zu vielen E-Mails vorhanden. Das kann zu Problemen mit der Geschwindigkeit der Anwendung führen!'));
			}
			
		} else {
			$sTableBody .= L10N::t('Es sind keine E-Mail-Konten für den automatischen E-Mail-Abruf verfügbar.');
		}
		
		return $sTableBody;
	}
	
	public static function createWarning($sWarning, $sDescription=null) {
		
		$oDialog = new Ext_Gui2_Dialog();
		$oNotification = $oDialog->createNotification($sWarning, $sDescription, 'warning', ['dismissible' => false]);
		$oNotification->style = 'width:auto;';

		return $oNotification->generateHTML();
	}
	
	/**
	 * @return string
	 */
	public static function license() {
		
		$sOutput = '';
		$sOutput .= L10N::t('Lizenzschlüssel', 'Framework').': '.System::d('license').'';
		return $sOutput;

	}
	
	public static function getWishes() {
		
		
		$oHelper = new Tc\Helper\Wishlist;
		$sOutput = $oHelper->getWelcomeBox();

		return $sOutput;
	}

}
