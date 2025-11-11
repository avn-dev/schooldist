<?php

/**
 * Eine Nachricht in dem IMAP-Konto
 */
class Ext_TC_Communication_Imap_Message {

	protected static $_aCache = array();
	
	public function __construct(private \Webklex\PHPIMAP\Message $oMessage) {}
	
	/**
	 * @return Ext_TC_Communication_Imap_Message
	 * @see Ext_TC_Communication_Imap::getMessageInstance()
	 */
	public static function getInstance(\Webklex\PHPIMAP\Message $oMessage)
	{
		return new self($oMessage);
	}
	
	/**
	 * Geänderte Eigenschaften auf den IMAP-Server übertragen
	 * (wird ohnehin beim Schließen der Verbindung gemacht)
	 */
	public function save()
	{
		$this->oMessage->getClient()->expunge();
	}
	
	public function getHeader($sName)
	{
		return $this->oMessage->getHeader()->get($sName);
	}
	
	public function updateHeader($oHeader) {
		$this->_sId = $oHeader->message_id;
		self::$_aCache[$this->_sId]['header'] = $oHeader;
	}

	/**
	 * Setzt ein Flag auf die IMAP-Message
	 * 
	 * @param string $sName
	 * @param mixed $mValue 
	 */
	public function setFlag($sName, $mValue) {

		if($sName === 'unseen') {
			if($mValue) {
				$this->oMessage->setFlag('Seen');
			} else {
				$this->oMessage->unsetFlag('Seen');
			}
		} elseif($sName === 'answered') {
			if($mValue) {
				$this->oMessage->setFlag('Answered');
			} else {
				$this->oMessage->unsetFlag('Answered');
			}
		} elseif($sName === 'flagged') {
			if($mValue) {
				$this->oMessage->setFlag('Flagged');
			} else {
				$this->oMessage->unsetFlag('Flagged');
			}
		}
		
	}
	
	/**
	 * Löscht die Nachricht auf dem Server
	 * 
	 * Sollte dies NICHT funktionieren, ist die MessageBox eventuell readonly.
	 *  Dies ist überprüfbar mit imap_last_error() nach dem Aufruf dieser Methode.
	 */
	public function delete()
	{
		$this->oMessage->delete(false);
		$this->save();
	}
	
	public function isUnseen() {
		
		if(
			$this->getHeader('Unseen') === 'U' ||
			$this->getHeader('Recent') === 'N'
		) {
			return true;
		}
		
		return false;
	}

	/**
	 * Synchronisiert die IMAP-Flags mit der angegebenen Nachricht, dabei
	 *	werden die Flags vom IMAP gesetzt.
	 * @param Ext_TC_Communication_Message $oMessage 
	 */
	public function syncFlags(Ext_TC_Communication_Message $oMessage) {

		// Hat sich was geändert?
		$aOldIncoming = $oMessage->incoming;
		if(!empty($aOldIncoming)) {
			$aOldIncoming = reset($aOldIncoming);
		}

        $oMessage->uid = $this->oMessage->uid;

        if($oMessage->direction === 'in') {
            if($this->getHeader('Unseen') === ' ') {
                $oMessage->setFlag('unseen', false);
            } else {
                $oMessage->setFlag('unseen', true);
            }
            if($this->getHeader('Answered') === 'A') {
                $oMessage->setFlag('answered', true);
            } else {
                $oMessage->setFlag('answered', false);
            }
            if($this->getHeader('Flagged') === 'F') {
                $oMessage->setFlag('flagged', true);
            } else {
                $oMessage->setFlag('flagged', false);
            }
		}

		$aNewIncoming = $oMessage->incoming;
		if(!empty($aNewIncoming)) {
			$aNewIncoming = reset($aNewIncoming);
		}

		$aChanges = array_diff_assoc($aNewIncoming, $aOldIncoming);

		// Nur speichern, wenn es eine Änderung bei den Flags gab
		if(!empty($aChanges)) {
			$oMessage->save();
		}

	}
	
}
