<?php

use Symfony\Component\Mime\Email;

/**
 * Die ganze IMAP-Synchronisation in eine eigene (Service-)Klasse auslagern
 */
class Ext_TC_Communication_Imap extends Ext_TC_Communication_EmailAccount {
	
	use \Tc\Traits\Filename;
	
	protected $_sDir = null;

	/**
	 * @var \Log
	 */
	protected $oLog;

	protected $countMails = null;

	public function __construct($iAccountId = 0) {
		
		parent::__construct($iAccountId);

		if(
			$this->id > 0 &&
			$this->imap != 1
		) {
			throw new Exception('Only e-mail-accounts with imap activated are allowed to use Ext_TC_Communication_Imap!');
		}
	}

	private function logger() {
		if ($this->oLog === null) {
			$this->oLog = Log::getLogger('imap');
		}

		return $this->oLog;
	}

	/**
	 * @return \Illuminate\Support\Collection
	 */
	public static function getAccounts() {

		return self::query()
			->where('imap', 1)
			->get();

	}
	
	public function setAttachmentDir($messageId) {

		$sBaseDir = \Illuminate\Support\Str::start(Ext_TC_Communication::getUploadPath('in'), '/');

		$sDir = \Util::getDocumentRoot(false).$sBaseDir.$this->imap_host.'/'.$this->imap_user.'/'.$messageId.'/';

		$sDir = Util::getCleanPath($sDir);
		$bCheck = Util::checkDir($sDir);
		if($bCheck === true) {
			$this->_sDir = $sDir;
		}

		return $this->_sDir;

	}

	public function getMails() {

		[$mails, $loadedMails] = $this->_getMails();
		return $mails;

	}

	public function checkEmails() {

		// Hier sollten zwei Verbindungen aufgebaut werden (_getMails, getSentMails) aber wir behandeln das hier für den Lock als eine
		$connectionId = \Communication\Services\ConnectionLock::lock($this);

		$start = microtime(true);

		$loaded = $synced = $failed = 0;

		$this->logger()->info('Start check mails', array('account' => $this->email));

		try {

			// Eingehende E-Mails
			[$aSyncedMails, $loadedMails] = $this->_getMails();

			$loaded += $loadedMails->count();

			$this->logger()->info('Check mails', array('account'=>$this->email,'mails'=>count((array)$aSyncedMails),'memory'=> memory_get_usage()));

			// Hook
			$aHook = array(
				'account' => &$this,
				'mails' => &$aSyncedMails
			);
			\System::wd()->executeHook('tc_communication_imap_checkmails', $aHook);

			// TODO das dürfte doch nie passieren
			if(!is_array($aSyncedMails)) {
				\Communication\Services\ConnectionLock::unlock($this, $connectionId);
				return [0, 0, 0];
			}

			foreach($aSyncedMails as &$aMail) {
				try {
					$this->addMail($aMail);
					++$synced;
				} catch(\Exception $e) {
					__pout($e);
					++$failed;
				}
			}

			// Ausgehende E-Mails
			if($this->imap_sync_sent_mail == 1) {

				$this->logger()->info('Start check sent mails', array('account'=>$this->email));

				[$sentMails, $loadedMails] = $this->getSentMails();

				$loaded += $loadedMails->count();

				$this->logger()->info('Sent mails', array('account'=>$this->email, 'mails'=>count((array)$sentMails),'memory'=> memory_get_usage()));

				foreach($sentMails as &$sentMail) {
					try {
						$this->addMail($sentMail, 'out');
						++$synced;
					} catch(Exception $e) {
						__pout($e);
						++$failed;
					}
				}
			}

		} catch (\Throwable $e) {

			$this->logger()->error('Check mails failed', ['account' => $this->email, 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
			\Communication\Services\ConnectionLock::unlock($this, $connectionId);

			throw $e;
		}

		$end = microtime(true);

		$this->logger()->info('End check mails', ['account' => $this->email, 'loaded' => $loaded, 'synced' => $synced, 'failed' => $failed, 'duration' => ($end - $start)]);

		return [$loaded, $synced, $failed];
	}
	
	protected function addMail($aMail, $direction = 'in') {
		
		$sRelationClass = Factory::getClassName('Ext_TC_Communication_EmailAccount');
		$bExisting = false;

		if (
			isset($aMail['message_entity']) &&
			$aMail['message_entity'] instanceof \Ext_TC_Communication_Message
		) {
			$oMessage = $aMail['message_entity'];
			$bExisting = true;
		} else {
			/* @var Ext_TC_Communication_Message $oMessage */
			$oMessage = \Factory::getObject('Ext_TC_Communication_Message');
			$oMessage->setAddresses($aMail['addresses']);
			$oMessage->imap_message_id = $aMail['message_id'];
			$oMessage->uid = $aMail['uid'];
			$oMessage->account_id = $this->id;
		}

		$oMessage->type = 'email';
		$oMessage->direction = $direction;
		$oMessage->date = $aMail['udate'];
		$oMessage->content = $aMail['content'];
		$oMessage->content_type = $aMail['content_type'];
		$oMessage->subject = $aMail['subject'];

		if(!empty($aMail['folder'])) {
			$oMessage->folder = $aMail['folder'];
		}

		$oMessage->relations = [
			[
				'relation' => $sRelationClass,
				'relation_id' => $this->id
			]
		];

		$oMessage->save();

		// Dateien erst speichern, wenn ID da ist
		if(
			!$bExisting && 
			!empty($aMail['attachments'])
		) {

			$this->setAttachmentDir($oMessage->id);
			
			foreach ($aMail['attachments'] as $iAttachment => $oAttachment) {

				// Dateiname bereinigen und eindeutig machen
				$cleanFilename = \Util::getCleanFileName($oAttachment->getName());

				$pathInfo = pathinfo($cleanFilename);

				$uniqueFilename = $this->addCounter($pathInfo['filename'], $this->_sDir, '.'.$pathInfo['extension']).'.'.$pathInfo['extension'];

				$sFilepath = $this->_sDir.$uniqueFilename;

				$oAttachment->save($this->_sDir, $uniqueFilename);

				Util::changeFileMode($sFilepath);

				$messageAttachment = $oMessage->getJoinedObjectChild('files');
				$messageAttachment->file = str_replace(Ext_TC_Util::getDocumentRoot(), '/', $sFilepath);
				$messageAttachment->name = $oAttachment->getName();

			}

			$oMessage->save(false);

			// In dem Objekt sind die Anhänge als JoinedObject und JoinTable. Wenn man das nach dem Speichern nicht einaml lädt werden die gelöscht.
			$oMessage = Ext_TC_Factory::getInstance('Ext_TC_Communication_Message', $oMessage->id);

		}

		// Inhalt und Betreff auf Message Code prüfen
		$oMessage->checkCode();

	}

	public function checkConnection($bWithFolders = true) {
		
		$sError = null;

		try {
			$client = $this->getImapClient();
			$client->checkConnection();

			if($bWithFolders) {
				$mImapFolder = $this->checkImapFolder();

				if($mImapFolder !== true) {
					$sError .= $mImapFolder;
				}

				$mImapSentFolder = $this->checkImapSentFolder();

				if($mImapSentFolder !== true) {
					$sError .= $mImapSentFolder;
				}
			}

		} catch (\Throwable $e) {
			$sError .= $e->getMessage();
		}

		if($sError === null) {
			return true;
		}
		
		return $sError;
	}
	
	public function checkImapFolder() {

		// Prüfen, ob individueller Ordner lesbar ist
		if($this->imap_folder != '') {

			try {
				$folder = $this->getImapClient()->getFolder($this->imap_folder);

				if ($folder) {
					return is_array($folder->examine());
				} else {
					return L10N::t('Die E-Mails konnten aus dem angegeben Ordner nicht abgerufen werden.', 'Communication');
				}
			} catch (\Throwable $e) {
				return $e->getMessage();
			}

		}

		return true;
	}
	
	public function checkImapSentFolder() {

		// Test: Ausgehende Mail speichern
		if($this->imap_append_sent_mail == 1) {

			$oMessage = new Email();
			$oMessage->from('support@fidelo.com');
			$oMessage->subject('Fidelo - Test E-Mail');
			$oMessage->text(L10N::t('Diese E-Mail-Nachricht wurde von Fidelo automatisch während des Testens der IMAP-Einstellungen erstellt.'));

			$bAppend = $this->appendSentMail($oMessage);

			if($bAppend !== true) {
				$sError = L10N::t('Die E-Mail konnte nicht in den angegebenen Ordner "Gesendete Elemente" verschoben werden.', 'Communication');
				$sError .= $this->getImapFoldersMessage();
				return $sError;
			}
		}

		return true;
	}
	
	protected function getImapFoldersMessage() {

		$sMessage = '';
		$aFolders = $this->getFolders();
		if(!empty($aFolders)) {
			$sMessage .= '<br>'.L10N::t('Vorhandene Ordner', 'Communication').': ';
			$sMessage .= implode(', ', $aFolders);
		}

		return $sMessage;
	}

	public function countMails() {
		
		if($this->countMails !== null) {
			return $this->countMails;
		}
		
		$client = $this->getImapClient();

		$folderName = $this->imap_folder;
		
		if (empty($folderName)) {
			$folderName = '';
		}

		if (null === $folder = $client->getFolder($folderName)) {
			Log::getLogger('imap')->error('Folder not found ', ['client' => $client->username . '@' . $client->host, 'folder' => $folderName, 'method' => __METHOD__]);
			$this->countMails = 0;
			return $this->countMails;
		}

		$folderDetails = $folder->examine();

		if(!empty($folderDetails['exists'])) {
			$this->countMails = (int)$folderDetails['exists'];
		}

		return $this->countMails;
	}
	
	public function getSentMails() {
		
		// Kann viel Speicher verbrauchen weil alles in ein Array geschrieben wird
		set_time_limit(14400);
		ini_set("memory_limit", '4G');
		
		$oClient = $this->getImapClient();

		// Fallback, falls erster Sync dieses Accounts
		if(!empty($this->imap_sent_mail_latest_sync)) {
			$latestSync = new \Carbon\Carbon($this->imap_sent_mail_latest_sync);
		} else {
			$latestSync = new \Carbon\Carbon('1 hour ago');
		}

		// Fallback, falls letzter Sync zu lange her ist
		$defaultLatestSync = new \Carbon\Carbon('1 day ago');

		if($latestSync < $defaultLatestSync) {
			$latestSync = $defaultLatestSync;
		}

		$thisSync = new \Carbon\Carbon();
		
		$oMessageCollection = self::search($oClient, 'ALL', $this->imap_sent_mail_folder, $latestSync);

		$this->logger()->info('Sent mail IDs', array('account' => $this->email, 'mails' => $oMessageCollection->count(), 'latest_sync'=>$this->imap_sent_mail_latest_sync));

		// Zeitpunkt merken für den nächsten Durchlauf
		$this->imap_sent_mail_latest_sync = $thisSync->toIso8601String();
		$this->save();

        $aSyncedMails = $this->processMessageCollection($oMessageCollection);

		// als delete markierte Mails in der Mailbox löschen
		$oClient->expunge();
		// Mailboxhandle schliessen
		$oClient->disconnect();

		return [$aSyncedMails, $oMessageCollection];
	}
	
	protected function _getMails() {

		// Kann viel Speicher verbrauchen weil alles in ein Array geschrieben wird
		set_time_limit(14400);
		ini_set("memory_limit", '4G');
		
		$client = $this->getImapClient();

        if (!empty($this->imap_filter)) {
            $oMessageCollection = self::search($client, $this->imap_filter, $this->imap_folder);
        } else {
			$this->logger()->info('Missing imap filter', ['account_id' => $this->id]);
            $oMessageCollection = new \Webklex\PHPIMAP\Support\MessageCollection();
        }

		$aSyncedMails = $this->processMessageCollection($oMessageCollection);

		// als delete markierte Mails in der Mailbox löschen
		$client->expunge();
		// Mailboxhandle schliessen
		$client->disconnect();

	    return [$aSyncedMails, $oMessageCollection];
	}

    public function processMessageCollection(\Webklex\PHPIMAP\Support\MessageCollection $messageCollection): array {

        if ($messageCollection->isEmpty()) {
            return [];
        }

        $imapMessageIds = $messageCollection->map(fn ($message) => $message->getMessageId()?->first())
			->filter(fn ($messageId) => !empty($messageId));

		// Vorhandene Entitäten laden
        $messageEntities = Ext_TC_Communication_Message::getRepository()
            ->findByImapMessageIds($this, $imapMessageIds);

        $syncedMails = array();
        foreach ($messageCollection as $message) {

            $messageId = $message->getMessageId()?->first();
			$messageEntity = null;

			if (!empty($messageId)) {
				$messageEntity = $messageEntities->get($messageId);
				if ($messageEntity === null) {
					$messageEntity = $messageEntities->get('<'.$messageId.'>');
				}
            }

            $mail = $this->getMailData($message, $messageEntity);
            if($mail !== null) {
				$syncedMails[] = $mail;
            }

        }

        return $syncedMails;
    }

	protected function getMailData(\Webklex\PHPIMAP\Message $oMessage, Ext_TC_Communication_Message $oMessageEntity = null, $bForce = false) {

		$aMail = array();

		// UID
		$aMail['uid'] = $oMessage->getUid();
		$aMail['message_id'] = $oMessage->getMessageId()?->first();
		$aMail['folder'] = $oMessage->getFolder()->name;

		// Wenn keine UID im Header vorhanden, dann eigene generieren
		if(empty($aMail['message_id'])) {
			$sFromEmail = $oMessage->getFrom()->first()->mail;

			$sFromEmail = str_replace('|', '@', $sFromEmail);

			$sMailDate = $oMessage->getDate()->first()->toRssString();

			$aMail['message_id'] = \Util::getCleanFileName($sMailDate).'@'.$sFromEmail;
		}

		$oImapMessage = Ext_TC_Communication_Imap_Message::getInstance($oMessage);

        // Prüfen ob die E-Mail schon in der DB ist
        if(
			$bForce !== true &&
			$oMessageEntity !== null
		) {
			// IMAP-Status abgleichen
			$oImapMessage->syncFlags($oMessageEntity);
			#return;
		}

		$aMail['message_entity'] = $oMessageEntity;
		
        // E-Mail komplett laden
        $oMessage->parseBody();

		/* @var \Webklex\PHPIMAP\Header $oHeader */
		$oHeader = $oMessage->getHeader();
		$sCharset = $oHeader->get('charset')?->toString();

		if(empty($sCharset)) {

			$sHeader = $oHeader->raw;

			// get encoding charset
			preg_match('/charset=(("?)([^\2;\n]+)(\2|;|\n))/is', $sHeader, $aMatches);

			$sCharset = trim($aMatches[3]);
			if(
				!isset($aMatches[3]) || 
				trim($aMatches[3]) == ''
			) {

				unset($aMatches);
				preg_match('/(From: =\?(.*)\?)/isU', $sHeader, $aMatches);
				$sCharset = $aMatches[2];
				if(
					!isset($aMatches[2]) || 
					trim($aMatches[2]) == ''
				) {

					$sCharset = 'UTF-8';

				}
			}

		}

		if ($oMessage->hasHTMLBody()) {
			$sElement = $this->decodePart(null, $sCharset, $oMessage->getHTMLBody());
			$aMail['content'] = $sElement;
			$aMail['content_type'] = 'html';
		} else {
			$sElement = $this->decodePart(null, $sCharset, $oMessage->getTextBody());
			$aMail['content'] = $sElement;
			$aMail['content_type'] = 'text';
		}

		$aMail['attachments'] = $oMessage->getAttachments();

		$aMail['charset'] = $sCharset;

		$readAddresses = function ($attribute) {
			if (!$attribute) return [];
			return $attribute->toArray();
		};

		$aAddresses = [
			'to' => $readAddresses($oMessage->to),
			'from' => $readAddresses($oMessage->from),
			'reply_to' => $readAddresses($oMessage->reply_to),
			'cc' => $readAddresses($oMessage->cc),
			'bcc' => $readAddresses($oMessage->bcc),
			'sender' => $readAddresses($oMessage->sender)
		];

		$aMail['addresses'] = array();
		foreach($aAddresses as $sType=>$aAddress) {
			foreach($aAddress as $oAddress) {
				$aMail['addresses'][] = array(
					'type' => $sType,
					'email' => $oAddress->mailbox.'@'.$oAddress->host,
					'name' => $this->_decodeHeaderString($oAddress->personal, $sCharset)
				);
			}
		}

		// Subject aus den Daten extrahieren
		$sSubject = $this->_decodeHeaderString($oMessage->getSubject()?->first(), $sCharset);
		$aMail['subject'] = $sSubject;

		// Date
		$aMail['date'] = $oMessage->getDate()->first()->format("Y-m-d H:i:s");
		$aMail['udate'] = $oMessage->getDate()->first()->getTimestamp();

		// jetzt abgearbeitete Mail zum löschen markieren
		if($this->imap_closure == 'delete') {
			$oMessage->delete();
		} elseif($this->imap_closure == 'seen') {
			$oMessage->setFlag('Seen');
		} elseif($this->imap_closure == 'unseen') {
			$oMessage->unsetFlag('Seen');
		} else {
			// Google Mail markiert beim Abruf der Header eine E-Mail als gelesen, obwohl sie es noch gar nicht ist
			if($oImapMessage->isUnseen()) {
				$oImapMessage->setFlag('unseen', true);
			}
		}

		return $aMail;
	}
	
	protected function decodePart($iEncoding, $sCharset, $sElement) {

		/**
		 * Das decoden passiert bereits in der Library
		 * \Webklex\PHPIMAP\Message::decodeString()
		 */
		/*switch($iEncoding) {
			case 1:
				$sElement = imap_8bit($sElement);
				break;
			case 2:
				$sElement = imap_binary($sElement);
				break;
			case 3:
				$sElement = imap_base64($sElement);
				break;
			case 4:
				$sElement = imap_qprint($sElement);
				break;
			default:
				$sElement = $sElement;
				break;
		}*/

		$sElement = iconv($sCharset, 'UTF-8//IGNORE', $sElement);
		
		return $sElement;
	}

	protected function _getParameters($oStructure) {
		$aReturn = array();
		if(!empty($oStructure->dparameters)) {
			$aParameters = $oStructure->dparameters;
		}
		foreach((array)$aParameters as $aParameter) {
		   	$aReturn[mb_strtolower($aParameter->attribute)] = $aParameter->value;
		}
		if(!empty($oStructure->parameters)) {
			$aParameters = $oStructure->parameters;
		}
		foreach((array)$aParameters as $aParameter) {
		   	$aReturn[mb_strtolower($aParameter->attribute)] = $aParameter->value;
		}
		return $aReturn;
	}

	protected function _getEncoding($iEncoding) {
		$aEncoding = array("7bit", "8bit", "binary", "base64", "quoted-printable", "other");

		$sEncoding = $aEncoding[$iEncoding];

		return $sEncoding;

	}

	/**
	 * Kovertiert einen Headerstring
	 * @param string $sString
	 * @param string $sDefaultCharset
	 * @return string
	 */
	protected function _decodeHeaderString($sString, $sDefaultCharset) {

		$aDecode = imap_mime_header_decode($sString);
		
		$sReturn = '';
		
		// Einzelne Parts durchlaufen und je nach Charset konvertieren
		foreach($aDecode as $oDecode) {

			$sDecodeCharset = $oDecode->charset;
			if($sDecodeCharset == 'default') {
				$sDecodeCharset = $sDefaultCharset;
			}
			
			$sReturn .= iconv($sDecodeCharset, 'UTF-8//TRANSLIT', $oDecode->text);
			
		}
		
		return $sReturn;		
		
	}
	
	protected function _encodeString($sString, $iEncoding) {

		if($iEncoding == 0) {
           	$sString = imap_8bit($sString);
        } elseif ($iEncoding == 1)
        {
          	$sString = imap_8bit($sString);
        }
        elseif ($iEncoding == 2)
        {
           $sString = imap_binary($sString);
        }
        elseif ($iEncoding == 3)
        {
       		$sString=imap_base64($sString);
       	}
        elseif ($iEncoding == 4)
        {
           	$sString = imap_qprint($sString);
        }
        elseif ($iEncoding == 5)
        {
         	$sString = imap_base64($sString);
        }
        return $sString;
	}

	protected function _parse($structure) {

		// message types
		$type = array("text", "multipart", "message", "application", "audio", "image", "video", "other");
		// message encodings
		$encoding = array("7bit", "8bit", "binary", "base64", "quoted-printable", "other");

		// create an array to hold message sections
		$ret = array();

		// split structure into parts
		$parts = $structure->parts;

		for($x=0; $x<sizeof($parts); $x++)
		{
		  	$ret[$x]["pid"] = ($x+1);

		  	$Object = $parts[$x];

		  	// default to text
		  	if ($Object->type == "") { $Object->type = 0; }
		  	$ret[$x]["type"] = $type[$Object->type] . "/" . mb_strtolower($Object->subtype);

		 	// default to 7bit
		  if ($Object->encoding == "") { $Object->encoding = 0; }
		  $ret[$x]["encoding"] = $encoding[$Object->encoding];

		  $ret[$x]["size"] = mb_strtolower($Object->bytes);

		  $ret[$x]["disposition"] = mb_strtolower($Object->disposition);

			if (mb_strtolower($Object->disposition) == "attachment") {
				$params = $Object->dparameters;
		  	foreach ($params as $p) {
		    	$ret[$x][$p->attribute] = $p->value;
		    }
		  }
		}

		return $ret;
	}

	/**
	 * Holt eine IMAP-Message-Instanz
	 * 
	 * @param mixed $mId Message-ID oder MsgNo
	 * @param string $sIdType id oder number
	 * @return Ext_TC_Communication_Imap_Message
	 */
	public function getMessageInstance($mId, $sIdType = 'message_id', $sFolder = 'INBOX') {

		$client = $this->getImapClient();

		if (null === $folder = $client->getFolder($sFolder)) {
			$this->logger()->error('Folder not found ', ['client' => $client->username.'@'.$client->host, 'folder' => $sFolder, 'method' => __METHOD__]);
			return null;
		}

		if($sIdType == 'number') {

			// TODO keine Verwendung gefunden
			//$mId = (int)$mId;
			//$oImapMessage = Ext_TC_Communication_Imap_Message::getInstance($mId, $this);
			
		} elseif($sIdType === 'uid') {

			$message = $folder->query()->getMessageByUid($mId);

			if($message) {
				return Ext_TC_Communication_Imap_Message::getInstance($message);
			}

		} else {

			$message = $folder->query()->getMessageByMsgn($mId);

			if($message) {
				return Ext_TC_Communication_Imap_Message::getInstance($message);
			}

		}

		return null;
	}

	/**
	 * Sucht die aktuelle MsgNo einer E-Mail über die Message-ID
	 * 
	 * MsgNo, UID und Message-ID sind verschiedene Dinge!
	 * 
	 * @param string $sMessageId
	 * @return string|bool
	 */
	public function getNumberOfMessage($sMessageId) {
		/* @var \Webklex\PHPIMAP\Message $oMessage */
		$oMessage = self::search($this->getImapClient())->reverse()
			->chunk(1000)
			->first(fn (\Webklex\PHPIMAP\Message $oMessage) => $oMessage->getMessageId()->first() === $sMessageId);

		if ($oMessage) {
			return $oMessage->getMessageNo()->first();
		}

		return false;
	}

	protected static function search(\Webklex\PHPIMAP\Client $client, $criteria = 'ALL', string $folderName = 'INBOX', \Carbon\Carbon $since = null): \Webklex\PHPIMAP\Support\MessageCollection {

		if (empty($folderName)) {
			$folderName = '';
		}

		if (null === $folder = $client->getFolder($folderName)) {
			Log::getLogger('imap')->error('Folder not found ', ['client' => $client->username . '@' . $client->host, 'folder' => $folderName, 'method' => __METHOD__]);
			return new \Webklex\PHPIMAP\Support\MessageCollection();
		}

		if($since === null) {
			$since = new \Carbon\Carbon('14 days ago');
		}

        $messageCollection = new \Webklex\PHPIMAP\Support\MessageCollection();

        $folder->query()
            ->fetchBody(false)
            ->since($since)
            ->where($criteria)
            ->chunked(function ($messages) use (&$messageCollection) {
                $messageCollection = $messageCollection->merge($messages);
            }, 1000);

		return $messageCollection;
	}

	/**
	 * Speichert die E-Mail in den angegebenen Ordner
	 * 
	 * @param \Symfony\Component\Mime\Email $oMessage
	 * @return bool
	 */
	public function appendSentMail(Email|\Symfony\Component\Mailer\SentMessage $oMessage) {

		$folder = $this->getImapClient()->getFolder($this->imap_sent_mail_folder);

		if (!$folder) {
			$this->logger()->error('Append sent mail failed', ['account_id' => $this->id, 'folder' => $this->imap_sent_mail_folder]);
			return false;
		}

		try {
			$success = $folder->appendMessage($oMessage->toString());
			return (bool)$success;
		} catch (\Throwable $e) {
			$this->logger()->error('Append sent mail failed', ['account_id' => $this->id, 'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
			__pout($e);
			$success = false;
		}

		return $success;
	}
	
	/**
	 * Gibt ein Array mit den Postfachnamen zurück
	 * @return array
	 */
	public function getFolders() {

		try {
			$client = $this->getImapClient();
			$folderCollection = $client->getFolders();
		} catch (\Throwable $e) {
			$this->logger()->error('Get folders failed ', ['account_id' => $this->id, 'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
			__pout($e);
			return false;
		}

		return $folderCollection->map(fn (\Webklex\PHPIMAP\Folder $folder) => ltrim($folder->name, '.'))
			->toArray();
	}

}
