<?php

class Ext_Newsletter_Gui2 extends Ext_Gui2_Data
{
	/**
	 * See parent
	 */
	public function switchAjaxRequest($_VARS)
	{
		if($_VARS['action'] == 'invertActive')
		{
			$this->_invertActive($_VARS['id']);

			$_VARS['task'] = 'loadTable';
		}

		$aTransfer = $this->_switchAjaxRequest($_VARS);

		echo json_encode($aTransfer);
	}


	/**
	 * See parent
	 */
	protected function deleteRow($iRowId)
	{
		$this->_deleteRecipient($iRowId);

		return true;
	}


	/**
	 * Physical delete the resipients
	 * 
	 * @param array $aIDs
	 */
	protected function _deleteRecipient($aIDs = array())
	{
		if(!empty($aIDs))
		{
			$sSQL = "
				DELETE FROM
					`newsletter2_recipients`
				WHERE
					`id` IN(:aIDs)
			";
			$aSQL = array('aIDs' => $aIDs);
			DB::executePreparedQuery($sSQL, $aSQL);
		}
	}


	/**
	 * Invert active state
	 * 
	 * @param array $aIDs
	 */
	protected function _invertActive($aIDs = array())
	{
		if(!empty($aIDs))
		{
			$sSQL = "
				UPDATE
					`newsletter2_recipients`
				SET
					`active` = !`active`
				WHERE
					`id` IN(:aIDs)
			";
			$aSQL = array('aIDs' => $aIDs);
			DB::executePreparedQuery($sSQL, $aSQL);
		}
	}
}