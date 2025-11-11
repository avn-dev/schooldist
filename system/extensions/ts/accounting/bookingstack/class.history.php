<?php

/**
 * Class Ext_TS_Accounting_BookingStack_History
 */
class Ext_TS_Accounting_BookingStack_History extends Ext_TC_Basic
{
	/**
	 * Tabellenname
	 *
	 * @var string
	 */
	protected $_sTable = 'ts_booking_stack_histories';

	/**
	 * Tabellen alias
	 *
	 * @var string
	 */
	protected $_sTableAlias = 'ts_bsh';

	public function touchDownload(\User $user)
	{
		$this->last_download = $user->id.'|'.time();
		return $this;
	}

	public function getLastDownload(): ?array
	{
		if (empty($this->last_download)) {
			return null;
		}
		return explode('|', $this->last_download);
	}

	/**
	 * @param $aSqlParts
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {
		parent::manipulateSqlParts($aSqlParts, $sView);

		$aSqlParts['select'] .= ",
			`k_id`.`document_number`
		";

		$aSqlParts['from'] .= "
			LEFT JOIN `ts_documents_booking_stack_histories` `ts_dbsh` ON
				`ts_dbsh`.`history_id` = `ts_bsh`.`id` LEFT JOIN
			`kolumbus_inquiries_documents` `k_id` ON
				`k_id`.`id` = `ts_dbsh`.`document_id` AND
				`k_id`.`active`= 1
		";

		$aSqlParts['groupby'] .= " `ts_bsh`.`id` ";
	}
}