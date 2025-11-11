<?php

namespace Admin\Helper;

use Admin\Components\Dashboard\SystemUpdateWidgetComponent;
use Admin\Instance;
use Core\Entity\ParallelProcessing\Stack;
use Core\Helper\SystemUpdate;
use Core\Service\SystemEvents;
use Illuminate\Http\Request;
use L10N;
use System;

class Welcome {
	
	protected $_aBoxes = array();
	protected $oAccess;
	protected $oRequest;

	/**
	 * @var \Monolog\Logger
	 */
	protected $oLog;

	public function __construct(\Access_Backend $oAccess=null, Request $oRequest=null) {

		$aBoxes = $this->getBoxes();

		$this->_aBoxes = $aBoxes;

		$this->oAccess = $oAccess;
		$this->oRequest = $oRequest;

		$this->oLog = \Log::getLogger('dashboard');
		
	}
	
	public function getBoxes() {
	
		$aBoxes = [
			'both' => [],
			'left' => [],
			'right' => []
		];

		$aBoxes['left']['10']['title'] = \L10N::t('Lizenzinformationen', 'Framework');
		$aBoxes['left']['10']['function'] = [\Factory::getClassName(\Admin\Helper\Welcome::class), 'license'];
		$aBoxes['left']['10']['handler'] = (new \Admin\Components\Dashboard\Handler(3, 6, true))->min(3, 3);

		$aBoxes['left']['30']['title'] = \L10N::t('Angemeldete Benutzer', 'Framework');
		$aBoxes['left']['30']['function'] = array(\Factory::getClassName(\Admin\Helper\Welcome::class), 'whoisonline');
		$aBoxes['left']['30']['right'] = 'whoisonline';
		$aBoxes['left']['30']['cache_time'] = false;
		$aBoxes['left']['30']['handler'] = (new \Admin\Components\Dashboard\Handler(4, 6, true))->min(2, 3);

		$aBoxes['right']['20']['title'] = \L10N::t('Systemupdates');
		$aBoxes['right']['20']['component'] = SystemUpdateWidgetComponent::class;
		$aBoxes['right']['20']['right'] = 'update';
		$aBoxes['right']['20']['handler'] = (new \Admin\Components\Dashboard\Handler(3, 6, true))->min(2, 2);

		$aBoxes['right']['43']['title'] = \L10N::t('Offene Hintergrundaufgaben', 'Framework');
		$aBoxes['right']['43']['function'] = array(\Factory::getClassName(\Admin\Helper\Welcome::class), 'getParallelProccessingNotification');
		$aBoxes['right']['43']['right'] = 'parallelprocessing';
		$aBoxes['right']['43']['cache_time'] = false;
        $aBoxes['right']['43']['handler'] = (new \Admin\Components\Dashboard\Handler(3, 6))->min(2, 2);

		$aBoxes['right']['50']['title'] = \L10N::t('Letzte Aktionen', 'Framework');
		$aBoxes['right']['50']['function'] = array(\Factory::getClassName(\Admin\Helper\Welcome::class), 'logs');
		$aBoxes['right']['50']['right'] = 'view_logs';
		$aBoxes['right']['50']['cache_time'] = false;
		$aBoxes['right']['50']['handler'] = (new \Admin\Components\Dashboard\Handler(2, 6))->min(2, 2);

		\System::wd()->executeHook('welcome_both', $aBoxes['both']);
		ksort($aBoxes['both']);

		\System::wd()->executeHook('welcome_left', $aBoxes['left']);
		ksort($aBoxes['left']);

		\System::wd()->executeHook('welcome_right', $aBoxes['right']);
		ksort($aBoxes['right']);

		return $aBoxes;
	}

	public function printBoxes($sLocation) {
		
		$aWelcomeSettings = $this->oAccess->getUser()->getAdditional('welcome');

		foreach((array)$this->_aBoxes[$sLocation] as $intKey=>$aBox) {

			if(
				(
					($aBox['show_always'] ?? false) ||
					!isset($aWelcomeSettings) ||
					$aWelcomeSettings[$sLocation][$intKey] === '1'
				) &&
				(
					!isset($aBox['right']) ||
					$this->oAccess->hasRight($aBox['right'])
				)
			) {

				$fStartTime = microtime(true);
				
				$sRefreshKey = $sLocation."_".$intKey;

				$bSkipCache = false;

				if($this->oRequest->input('refresh_welcome') === $sRefreshKey) {
					$bSkipCache = true;
				}

				$oBox = \Admin\Helper\Welcome\Box::getInstance($aBox);
				$oBox->printBox($sRefreshKey, $fStartTime, $bSkipCache);

			}

		}

	}
	
	public function updateCache(Instance $admin, Request $request) {

		$this->oLog->addInfo('Start update cache', ['php-version'=> \Util::getPHPVersion(), 'boxes'=>$this->_aBoxes]);
		
		$aLanguages = \System::getBackendLanguages(true);

		foreach((array)$this->_aBoxes as $sLocation=>$aBoxes) {
			
			foreach($aBoxes as $iBox=>$aBox) {
				
				$oBox = \Admin\Helper\Welcome\Box::getInstance($aBox);
				foreach($aLanguages as $sLanguage=>$sLabel) {

					$this->oLog->addInfo('Update cache '.$oBox->getTitle(), [$sLocation, $sLanguage]);

					$oBox->setLanguage($sLanguage);
					$oBox->updateCache($admin, $request);

				}
				
			}
			
		}

		$this->oLog->addInfo('End update cache');
		
	}
	
	public function getConfig($sLocation) {

		$aBoxes = $this->_aBoxes[$sLocation];
		return $aBoxes;
	}
	
	public static function license() {

		$sOutput = '';
		$sOutput .= '<strong>'.L10N::t('Typ', 'Framework').':</strong> '.System::d('software_name').'<br/>';
		$sOutput .= '<strong>'.L10N::t('Gültig bis', 'Framework').':</strong> '.L10N::t('unbefristet').'<br/>';
		$sOutput .= '<strong>'.L10N::t('Lizenzschlüssel', 'Framework').':</strong> '.System::d('license');

		return $sOutput;
	}
	
	public static function logs() {

		$sOutput = '';

		$sOutput .= '<table class="table table-hover">';
		$sOutput .= '<tr class="noHighlight">';
		$sOutput .= '<th width="20%">'.L10N::t('Benutzer').'</th>';
		$sOutput .= '<th width="50%">'.L10N::t('Aktion').'</th>';
		$sOutput .= '<th width="30%">'.L10N::t('Zeit').'</th>';
		$sOutput .= '</tr>';

		$i=0;
		$arrLogs = \Log::getLogEntries(5);
		foreach($arrLogs as $log) {
			$sOutput .= "<tr>
			<td valign=top>".(($log['user_id'])?$log['name']:L10N::t('System'))."</td>
			<td valign=top>".$log['action']."</td>
			<td valign=top>".$log['ftime']."</td>
			</tr>";
			$i++;
		}
		if($i==0) {
			$sOutput .= "<tr><td colspan=\"3\">".L10N::t('Es liegen keine Einträge vor').".</td></tr>";
		}

		$sOutput .= '</table>';

		return $sOutput;
	}
	
	public static function updates() {

		$aAvailableUpdates = SystemUpdate::getAvailableUpdates();

		$sOutput = '';

		if(empty($aAvailableUpdates)) {
			
			$sOutput .= L10N::t('Ihr System ist auf dem aktuellen Stand!');

		} else {

			$sOutput .= '<ul class="nav nav-pills nav-stacked">';
			foreach($aAvailableUpdates as $aAvailableUpdate) {
				$sOutput .= '<li><a href="javascript:void(0);" onclick="loadContentByUrl(\'admin_update\', \''.\L10N::t('Systemupdate').'\', \'/admin/update.html?extension='.$aAvailableUpdate['extension'].'\');">'.$aAvailableUpdate['label'];
				$sOutput .= '<span class="pull-right text-green"></i> Version: '. $aAvailableUpdate['version'].'</span></a></li>';
			}
			$sOutput .= '</ul>';

			SystemEvents::dispatchSystemUpdates();

		}

		return $sOutput;
	}
	
	public static function whoisonline() {

		$oAccess = \Access::getInstance();
		
		ob_start();
 
		?>
		<div>
			<table class="table table-hover">
				<tr>
					<th style="width:auto;"><?=L10N::t('Name', 'Framework')?></th>
					<th style="width:150px;"><?=L10N::t('Rolle', 'Framework')?></th>
					<th style="width:150px;"><?=L10N::t('letzte Aktion', 'Framework')?></th>
					<!--<th style="width:70px;"><?=L10N::t('Nachricht', 'Framework')?></th>-->
				</tr>
			<?php
			$i=0;
			$arrUser = \Access_Backend::getActiveUser();
            $format = \Factory::getObject(\Ext_Gui2_View_Format_Date_Time::class);

            foreach($arrUser as $log) {
				echo '<tr>
				<td>'.$log['user'].'</td>
				<td>'.$log['role'].'</td>
				<td>'.$format->formatByValue($log['last_action']).'</td>
				<!--<td class="text-center">';
				
				if($log['userid'] != $oAccess->id) {
					echo '<a title="'.L10N::t('Nachricht senden', 'Framework').'" href="javascript:void(0)" onclick="top.openUserMessageDialogSend(\''.$log['user'].'\','.$log['userid'].')"><i title="'.L10N::t('Nachricht senden', 'Framework').'" class="fa fa-fw fa-envelope"></i></a>';
				}

				echo "
						</td>-->
					</tr>";
				
				$i++;
			}
			if($i==0) {
				echo "<tr><td colspan=\"3\">".L10N::t('Es ist momentan kein Benutzer angemeldet', 'Framework').".</td></tr>";
			}
			?>
			</table>
		</div>
		<?php
		$sContent = ob_get_clean();
		return $sContent;
		
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public static function getParallelProccessingNotification() {

		ob_start();

		echo '<div>';

		$oRepository = Stack::getRepository();
		$aParallelProcessings = $oRepository->getReport();

		if(!empty($aParallelProcessings)) {

			echo '<table class="table table-hover">';
			usort($aParallelProcessings, function($aA, $aB) {
				return strcmp($aA['type'], $aB['type']);
			});

			echo '<tr class="noHighlight">';
			echo '<th style="width:auto;">'.L10N::t('Typ', 'Framework').'</th>';
			echo '<th style="width:60px;">'.L10N::t('Anzahl', 'Framework').'</th>';
			echo '</tr>';

			foreach($aParallelProcessings as $aParallelProcessing) {
				echo '<tr><td>'.$aParallelProcessing['type'].'</td><td style="text-align: right;">'.$aParallelProcessing['count_type'].'</td></tr>';
			}

			echo '</table>';
		} else {
			echo L10N::t('Aktuell sind keine Hintergrundaufgaben vorhanden.', 'Framework');
		}

		echo '</div>';

		$sContent = ob_get_clean();

		return $sContent;
	}
	
}
