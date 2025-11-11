<?php

namespace Gui2\Controller;

use Core\Exception\ReportErrorException;
use Util;
use Ext_Gui2;

/**
 * v6
 */
class RequestController extends \MVC_Abstract_Controller {

	public function handle() {

		$bDirectOutput = true;
		
		// Ekelhaft aber notwendig (Abwärtskompatibilität)
		global $_VARS, $user_data;
		$_VARS = $this->_oRequest->getAll();
		$user_data = \Access::getInstance()->getUserData();

		$iTime = microtime(1);
		$iTotalTime = microtime(1);

		ignore_user_abort(true);

		if($this->_oAccess->hasRight("control") !== true) {
			throw new \RuntimeException('No access');
		}

		$oMonitoringService = new \Gui2\Service\MonitoringService($_VARS, $iTime);

		if($_VARS['hash']) {

			if(isset($_VARS['gui_debugmode']) && $_VARS['gui_debugmode'] == 1){
				$iNowTime = Util::getMicrotime();
				__pout('Laden der Main Inc/Frontend Inc: '.($iNowTime - $iTime));
				$iTime = $iNowTime;
			}

			$oGui = Ext_Gui2::getClass($_VARS['hash'], $_VARS['instance_hash']);
			$oGui->setRequest($this->_oRequest);

			if(isset($_VARS['gui_debugmode']) && $_VARS['gui_debugmode'] == 1) {
				$iNowTime = Util::getMicrotime();
				__pout('Laden der GUI 2 aus der Session: '.($iNowTime - $iTime));
				$iTime = $iNowTime;
			}

			// Access-Objekt: Wird nur erzeugt, wenn per Container/DI angefragt
			app()->singleton(\Access_Backend::class, function () {
				return \Access_Backend::getInstance();
			});

			try {

				// Ausgabe abfangen um Fehler bei Index-Stack verarbeiten zu können
				ob_start();
				$oGui->switchAjaxRequest($_VARS);
				$sContent = ob_get_clean();

				if(isset($_VARS['gui_debugmode']) && $_VARS['gui_debugmode'] == 1) {
					$iNowTime = Util::getMicrotime();
					__pout('switchAjaxRequest: '.($iNowTime - $iTime));
					$iTime = $iNowTime;
				}

				\Core\Facade\SequentialProcessing::execute();

				// Persister vor Stack ausführen
				$oPersister = \WDBasic_Persister::getInstance();
				$oPersister->save();
				
				// @TODO Auf SequentialProcessing umstellen
				$bSuccess = \Ext_Gui2_Index_Stack::executeCache();

				if($bSuccess) {
					$bSuccess = \Ext_Gui2_Index_Stack::save();
				}

				if(isset($_VARS['gui_debugmode']) && $_VARS['gui_debugmode'] == 1) {
					$iNowTime = Util::getMicrotime();
					__pout('Index Stack abarbeiten: '.($iNowTime - $iTime));
					$iTime = $iNowTime;
				}

				if(!$bSuccess) {
					$aTransfer = array();
					$aTransfer['action'] = 'showError';
					$aTransfer['error'] = array(\L10N::t('Es ist ein Fehler beim Aktualisieren des Indexes aufgetreten!'));
					$this->_oView->setAll($aTransfer);
					$bDirectOutput = false;
				}

			} catch (\Throwable $exc) {

				$this->_oView->setHTTPCode(500);

//				if (function_exists('xdebug_break')) {
//					xdebug_break();
//				}

		        if(\System::d('debugmode') > 0) {

					// __pout auf Whoops\Exception\ErrorException funktioniert manchmal nicht (read-only-Property)
					__pout(get_class($exc).': '.$exc->getMessage());
					__pout('File: '.$exc->getFile());
					__pout('Line: '.$exc->getLine());
					__pout($exc->getTraceAsString());

					if ($exc instanceof \Elastica\Exception\ResponseException) {
						__pout($exc->getRequest());
					}

		        } else {

					$errorLog = \Log::getLogger('default', 'gui2');
					$errorLog->error('Throwable', [get_class($exc), $exc->getMessage(), $exc->getFile(), $exc->getLine(), $exc->getTraceAsString()]);

					if ($exc instanceof ReportErrorException) {
						(new \Core\Exception\ExceptionHandler)->report($exc);
					}
				}

				$aTransfer = array();
				$aTransfer['action'] = 'showError';
				$aTransfer['error'] = array(\L10N::t('Es ist ein Fehler aufgetreten!'), (string)$exc->getMessage());
				$this->_oView->setAll($aTransfer);
				$bDirectOutput = false;

			}

			if(
				isset($_VARS['gui_debugmode']) && 
				$_VARS['gui_debugmode'] == 1 &&
				isset($_VARS['query_history'])
			) {
				
				$aQueryHistory = \DB::getQueryHistory();
				
				__out($aQueryHistory);

				$iTotalDBTime = 0;
				$aQueryDiff = array();
				foreach((array)$aQueryHistory as $iKey => $aData){

					$iTotalDBTime += $aData['duration'];
					$sKey = md5($aData['query']);

					if(!isset($aQueryDiff[$sKey]['explain'] )){
						try {
							$sExplain = \DB::getQueryData('EXPLAIN '.$aData['query']);
						} catch (\Exception $exc) {
							$sExplain = '';
						}
					} else {
						$sExplain = $aQueryDiff[$sKey]['explain'];
					}

					$aQueryDiff[$sKey]['query'] = $aData['query'];
					$aQueryDiff[$sKey]['count']++;
					$aQueryDiff[$sKey]['duration'] += $aData['duration'];
					$aQueryDiff[$sKey]['class'][] = $aData['class'];
					$aQueryDiff[$sKey]['class'] = array_unique($aQueryDiff[$sKey]['class']);
					$aQueryDiff[$sKey]['explain'] = $sExplain;
				}

				usort($aQueryDiff, function($a, $b){ 
					if($a['count'] > $b['count']){
						return -1;
					} else if($a['count'] < $b['count']){
						return 1;
					} else {
						return 0;
					}
				}
				);
				__out($iTotalDBTime);
				__out($aQueryDiff); 
			}

			if($bDirectOutput === true) {
				echo $sContent;
			}

			// Gui2 am Leben halten, wenn der Ping noch existiert
			if(
				isset($_VARS['instance_hash']) &&
				ctype_alnum($_VARS['hash']) &&
				ctype_alnum($_VARS['instance_hash'])
			) {
				\Ext_Gui2_GarbageCollector::touchSession($_VARS['hash'], $_VARS['instance_hash']);

				if(isset($_VARS['gui_debugmode']) && $_VARS['gui_debugmode'] == 1){
					$iNowTime = Util::getMicrotime();
					__pout('Ext_Gui2_GarbageCollector::touchSession: '.($iNowTime - $iTime));
					$iTime = $iNowTime;
				}

			}

		} else {
			$this->set('error', 'No hash or hash is expired!');
		}

		if(isset($_VARS['gui_debugmode']) && $_VARS['gui_debugmode'] == 1) {
			$iNowTime = Util::getMicrotime();
			__pout('Total: '.($iNowTime - $iTotalTime));
		}

		$oMonitoringService->save();

		if($bDirectOutput === true) {
			die();
		}
		
	}

	
}