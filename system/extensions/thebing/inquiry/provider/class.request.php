<?php
/*
 * Klasse beschreibt alle Tranfer Provider anfragen
 *
 */
class Ext_Thebing_Inquiry_Provider_Request extends Ext_Thebing_Basic {

	// Klassenname

	protected $_sTable = 'kolumbus_inquiries_transfers_provider_request';

	protected static $_sStaticTable = 'kolumbus_inquiries_transfers_provider_request';

	protected $_aFormat = array(
			'changed' => array(
							'format' => 'TIMESTAMP'
			),
			'created' => array(
							'format' => 'TIMESTAMP'
			)
	);

	protected $_aJoinedObjects = array(
        'transfer' => array(
				'class'					=> 'Ext_TS_Inquiry_Journey_Transfer',
				'key'					=> 'transfer_id',
				'check_active'			=> true
        )
    );

	public function getDate(): ?\Carbon\Carbon {
		if (!empty($this->created)) {
			return \Carbon\Carbon::createFromTimestamp($this->created);
		}
		return null;
	}

	/**
	 * Liefert den Transfer zu der Anfrage
	 * @return Ext_TS_Inquiry_Journey_Transfer 
	 */
	public function getTransfer(){
		$oTransfer = $this->getJoinedObject('transfer');

		return $oTransfer;
	}
	
	public function save($bLog = true){

		// Dafür sorgen, dass es nicht 2 Anfragen für den selben Provider gibt
		$sSql = "UPDATE
						`kolumbus_inquiries_transfers_provider_request`
					SET
						`active` = 0
					WHERE
						`transfer_id` = :transfer_id AND
						`provider_type` = :provider_type AND
						`provider_id` = :provider_id AND
						`active` = 1";
		$aSql = array();
		$aSql['transfer_id']	= (int)$this->transfer_id;
		$aSql['provider_type']	= $this->provider_type;
		$aSql['provider_id']	= (int)$this->provider_id;

		DB::executePreparedQuery($sSql,$aSql);

		parent::save($bLog);
	}

}
