<?php

/**
 * Class Ext_Thebing_Client_Inbox
 *
 * @property int $id
 * @property int $client_id
 * @property int $active
 * @property int $creator_id
 * @property int $editor_id
 * @property int $changed
 * @property int $created
 * @property int $user_id
 * @property string $name
 * @property string $short
 * @property int $status
 */
class Ext_Thebing_Client_Inbox extends Ext_Thebing_Basic {

	/**
	 * Tabellenname
	 *
	 * @var string
	 */
	protected $_sTable = 'kolumbus_inboxlist';

	/**
	 * @param string $sName
	 * @param mixed $mValue
	 */
    public function __set($sName, $mValue) {
      
        if($sName == 'name' && $this->short == ""){
            $sShort = uniqid();
            $sShort = substr($sShort, 0, 10);
            $this->short = $sShort;
        }
        
        parent::__set($sName, $mValue);
    }

	/**
	 * @param string $sShort
	 * @return static
	 */
	public static function getByShort($sShort) {
		$oRepo = (new self())->getRepository();
		return $oRepo->findOneBy(['short' => $sShort]);
	}
	
	public function save($bLog = true) {

		$aReturn = parent::save($bLog);
		
		// Inboxen werden in der Navi angezeigt, daher Cache leeren
		\Admin\Helper\Navigation::clearCache();
		
		return $aReturn;
	}
	
	public function delete() {
		
		$sqlQuery = "SELECT * FROM `ts_inquiries` WHERE `active` = 1 AND `inbox` = :inbox LIMIT 1";
		$sqlData = [
			'inbox' => $this->short
		];
		$checkInquiries = \DB::getQueryRows($sqlQuery, $sqlData);

		if(!empty($checkInquiries)) {
			$aErrors['inquiries'][] = 'EXISTING_JOINED_ITEMS';
		}
		
		if(empty($aErrors)) {
			return parent::delete();
		}

		return $aErrors;
	}
	
}
