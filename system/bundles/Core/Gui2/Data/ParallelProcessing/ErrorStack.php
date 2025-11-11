<?php

namespace Core\Gui2\Data\ParallelProcessing;

use Core\Entity\ParallelProcessing\ErrorStack as ErrorStackEntity;
use Core\Service\ParallelProcessingService;
use Core\Exception\ParallelProcessing\TaskException;

class ErrorStack extends \Ext_Gui2_Data {
	
	/**
	 * @param \Ext_Gui2 $oGui
	 * @return \Ext_Gui2_Dialog
	 */
	public static function getDialog(\Ext_Gui2 $oGui) {
		
		$oDialog = $oGui->createDialog($oGui->t('Fehlermeldung ansehen'), $oGui->t('Fehlermeldung ansehen'));
		$oDialog->no_padding = 1;
		$oDialog->save_button = false;
		
		return $oDialog;
	}
	
	/**
	 * @param array $_VARS
	 */
	public function switchAjaxRequest($_VARS) {
		$aSelectedIds = (array)$_VARS['id'];
		
		if(
			$_VARS['task'] == 'confirm' &&
			$_VARS['action'] == 'execute_task'
		) {	
			
			$oParallelProcessingService = new ParallelProcessingService();

			$oAccordion = new \Ext_Gui2_Dialog_Accordion('error_stack');
			
			$bClose = false;
			if(count($aSelectedIds) > 1) {
				$bClose = true;
			}
			
			foreach($aSelectedIds as $iSelectedId) {
				
				$oTask = ErrorStackEntity::getInstance($iSelectedId);
				$aExceptionData = array();

				try {
					$oParallelProcessingService->executeTask($oTask->getData(), false);
				} catch (TaskException $e) {
					$aExceptionData = $e->getErrorData();					
				}
				
				if(!empty($aExceptionData)) {
					$sIcon = '<i class="fa fa-minus-circle fa-colored"></i>';
				} else {
					$sIcon = '<i class="fa fa-check"></i>';
				}
				
				$oElement = $oAccordion->createElement($sIcon.' '.$oParallelProcessingService->getLabelForType($oTask->type), [
					'close' => $bClose
				]);
								
				if(!empty($aExceptionData)) {
								
					$iExecutionCount = (int) $oTask->execution_count;
					$oTask->execution_count = $iExecutionCount + 1;
					$oTask->save();

					if(!empty($aExceptionData)) {
						if(\System::d('debugmode') !== 2) {
							unset($aExceptionData['trace']);
						}
						$oElement->setContent('<div class="designDiv" style="overflow: auto;"><pre>'.print_r($aExceptionData, true).'</pre></div>');
					}

				} else {	

					$oTask->delete();

					$oElement->setContent('<div style="padding: 5px;">'.$this->t('Der Task wurde erfolgreich abgeschlossen und gelöscht.').'</div>');

				}
				
				$oAccordion->addElement($oElement);
			}

			$aTransfer = array();			
			
			$aTransfer['action'] = 'openDialog';
			$aTransfer['load_table']= 1;

			$aTransfer['data']['id']	= 'ID_'.implode('_', $aSelectedIds);
			$aTransfer['data']['title'] = $this->t('Abgearbeitete Tasks');
			$aTransfer['data']['width'] = 900;
			$aTransfer['data']['html'] = $oAccordion->generateHtml();
			
			echo json_encode($aTransfer);
			
		} else {
			parent::switchAjaxRequest($_VARS);
		}
	}
	
	/**
	 * @param string $sIconAction
	 * @param \Ext_Gui2_Dialog $oDialog
	 * @param array $aSelectedIds
	 * @param string $sAdditional
	 * @return array
	 */
	public function getDialogHTML(&$sIconAction, &$oDialog, $aSelectedIds = array(), $sAdditional = false) {

		if($sIconAction === 'show_error') {
			$oDialog->aElements = array();
			$iSelectedId = (int) reset($aSelectedIds);			
			
			$oTask = ErrorStackEntity::getInstance($iSelectedId);
			
			$oParallelProcessingService = new ParallelProcessingService();
			$oTypeHandler = $oParallelProcessingService->getTypeHandler($oTask->type);
			
			$aData = json_decode($oTask->data, true);
			$aErrorData = json_decode($oTask->error_data, true);
			
			$sTaskLabel = $oTypeHandler->getLabel();
			$sErrorDescription = $oTypeHandler->getErrorDescription($aData, $aErrorData);
			
			$oPre = new \Ext_Gui2_Html_Div();
			
			if(!empty($sErrorDescription)) {
				$oDialog->setElement($oDialog->createNotification($sTaskLabel, $sErrorDescription));
			}

			if(\System::d('debugmode') !== 2) {
				unset($aErrorData['trace']);
			}

			$oPre->setElement('<div class="designDiv" style="overflow: auto;"><pre>'.print_r($aErrorData, true).'</pre></div>');

			$oDialog->setElement($oPre);

			$oDialog->width = 700;
			$oDialog->height = 500;
			
		}
		
		$aData = parent::getDialogHTML($sIconAction, $oDialog, $aSelectedIds, $sAdditional);
		
		return $aData;
	}
	
	public static function getOrderBy() {
		return array('created' => 'DESC');
	}

	public static function getTypeFilterEntries() {

		return ErrorStackEntity::query()->pluck('type')
			->mapWithKeys(function ($type) {
				return [$type => (new \Core\Gui2\Format\ParallelProcessing\Label)->formatByValue($type)];
			})
			->prepend('', '')
			->toArray();

	}

}
