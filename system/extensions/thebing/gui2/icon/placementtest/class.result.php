<?php


class Ext_Thebing_Gui2_Icon_Placementtest_Result extends Ext_Gui2_View_Icon_Abstract
{
	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement)
	{
		if(
			$oElement->task == 'deleteRow'
		)
		{
			if(is_array($aRowData))
			{
				$aRowData = (array)reset($aRowData);
				if(
					!empty($aSelectedIds) &&
					array_key_exists('placementtest_result_id', $aRowData)
				)
				{
					$iPlacementtestResultID = (int)$aRowData['placementtest_result_id'];
					if( 0 >= $iPlacementtestResultID )
					{
						return 0;
					}
				}
				else
				{
					return 0;
				}
			}
			else
			{
				return 0;
			}
		}
		elseif($oElement->task == 'openDialog' && $oElement->action == 'edit')
		{
			if( 1 != count($aRowData) )
			{
				return 0;
			}
		}
		elseif(
			($oElement->task == 'openDialog' && $oElement->action == 'additional_document') ||
			($oElement->task == 'request' && $oElement->action == 'openDocumentPdf')
		)
		{
			$aRowDataForSwitch = array($aRowData);
			$oIconClass = new Ext_Thebing_Gui2_Icon_Inbox('inquiry_id');
			
			return $oIconClass->getStatus($aSelectedIds, $aRowDataForSwitch, $oElement);
		}

		return 1;
	}
}
