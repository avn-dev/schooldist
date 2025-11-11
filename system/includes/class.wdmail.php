<?php

use Core\Service\NotificationService;
use Illuminate\Support\Arr;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\Mime\Header\UnstructuredHeader;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\Mailer;

/**
 * @property string $subject
 * @property string $text
 * @property string $html
 * @property string $replyto
 * @property array $mailConfig
 */
class WDMail {
	
	protected $_sCharset;
	protected $_oMimeMail;
	protected $_sXMailer = 'Fidelo Framework (by Fidelo Software GmbH)';
	protected $_aValues = array();
	protected $_aTo = array();
	protected $_bSuccess = false;
	protected $_bSkipSend = false;

	protected $mailConfig = [];

	protected bool $throwablesEnabled = false;

	protected ?\Psr\Log\LoggerInterface $log = null;

	public $message;
	
	const PRIORITY_HIGHEST = 1;
	const PRIORITY_HIGH = 2;
	const PRIORITY_NORMAL = 3;
	const PRIORITY_LOW = 4;
	const PRIORITY_LOWEST = 5;

	public function __construct() {

		// /config/mail.php
		$this->mailConfig = app()['config']['mail'];
		
	}

	public function setThrowablesEnabled(bool $value): static
	{
		$this->throwablesEnabled = $value;
		return $this;
	}

	public function setLogger(\Psr\Log\LoggerInterface $log): static
	{
		$this->log = $log;
		return $this;
	}

	public function logger(): \Psr\Log\LoggerInterface
	{
		if ($this->log) {
			return $this->log;
		}
		return NotificationService::getLogger('MailChannel');
	}

	public function hasThrowablesEnabled(): bool
	{
		return $this->throwablesEnabled;
	}

	public function __set($sField, $mValue) {

		switch ($sField) {
			case "bSkipSend":
				$this->_bSkipSend = $mValue;
				break;
			case "bSuccess":
				$this->_bSuccess = $mValue;
				break;
			case "x-mailer":
				$this->_sXMailer = $mValue;
				break;
			case "charset":
				if($mValue == "ISO-8859-1") {
					$this->_sCharset = "ISO-8859-1";
				} else {
					$this->_sCharset = "UTF-8";
				}
				break;
			case "header":
				$this->_aValues['header'] = $mValue;
				break;
			case "from":
				$this->_aValues['from'] = $mValue;
				break;
			case "subject":
				$this->_aValues['subject'] = $mValue;
				break;
			case "text":
				$this->_aValues['text'] = $mValue;
				break;
			case "html":
				$this->_aValues['html'] = $mValue;
				break;
			case "attachments":
				$this->_aValues['attachments'] = $mValue;
				break;
			case "replyto":
				$this->_aValues['replyto'] = $mValue;
				break;
			case "returnpath":
				$this->_aValues['returnpath'] = $mValue;
				break;
			case "priority":
				$this->_aValues['priority'] = $mValue;
				break;
			case "cc":
				$this->_aValues['cc'] = $mValue;
				break;
			case "bcc":
				$this->_aValues['bcc'] = $mValue;
				break;
			case "to":
				$this->_aTo = $mValue;
				break;
			case "mailConfig":
				$this->mailConfig = $mValue;
				break;
			default:
				$this->_aValues[$sField] = $mValue;
				break;
		}
	}
	
	public function __get($sField) {

		switch ($sField) {
			case "bSkipSend":
				return $this->_bSkipSend;
			case "bSuccess":
				return $this->_bSuccess;
			case "x-mailer":
				return $this->_sXMailer;
			case "charset":
				return $this->_sCharset;
			case "to":
				return $this->_aTo;
			case 'mailConfig':
				return $this->mailConfig;
			default:
				if(array_key_exists($sField, $this->_aValues)) {
					return $this->_aValues[$sField];
				}
		}
	}

	/**
	 * send email
	 * @param $mTo array or string with recipients email
	 */
	public function send($aTo) {

		$aMailConfig = $this->mailConfig;
		
		if(
			empty($this->_aValues['header']) && 
			empty($this->_aValues['from'])
		) {
			$this->_aValues['from'] = [$aMailConfig['from']['address'] => $aMailConfig['from']['name']];
			if(
				isset($_SERVER['REMOTE_ADDR']) &&
				$_SERVER['REMOTE_ADDR'] != 'console'
			) {
				$this->_aValues['header'] = "X-Sender-IP: ".$_SERVER['REMOTE_ADDR']."\r\n";
			} else {
				$host = gethostname();
				$ip = gethostbyname($host);
				$this->_aValues['header'] = "X-Sender-IP: ".$ip."\r\n";
			}
		}

		$this->_aValues['header'] .= "\r\nX-Mailer: ".$this->_sXMailer;

		$this->_aTo = self::fixMultipleMails(\Illuminate\Support\Arr::wrap($aTo));

		\System::wd()->executeHook('wdmail_send', $this);

		$exception = null;

		if($this->_bSkipSend !== true) {
		
			$oMessage = new Email();

			$oMessageHeaders = $oMessage->getHeaders();

			// Header setzen
			$aHeaders = preg_split("/\r?\n/", $this->header);

			foreach($aHeaders as $sHeader) {

				$aHeader = explode(":", $sHeader);
				$sHeaderName = trim($aHeader[0] ?? '');
				$sHeaderValue = trim($aHeader[1] ?? '');

				if(
					!empty($sHeaderName) && 
					!empty($sHeaderValue)
				) {
					$oMessageHeader = $oMessageHeaders->get($sHeaderName);

					// Nur sonstige Header setzen, keine FROM, CC, etc.
					if($oMessageHeader instanceof UnstructuredHeader) {
						$oMessageHeader->setValue($sHeaderValue);
					} elseif($oMessageHeader === null) {
						$oMessageHeaders->addTextHeader($sHeaderName, $sHeaderValue);
					}
				}

			}

			foreach (\Illuminate\Support\Arr::wrap($this->to) as $mToEmailAddress) {
				// string or \Symfony\Component\Mime\Address
				$oMessage->addTo($mToEmailAddress);
			}

			if(!empty($this->_aValues['from'])) {
				foreach (\Illuminate\Support\Arr::wrap($this->_aValues['from']) as $sFromEmailAddress => $sFromName) {
					if (str_contains($sFromName, '@')) {
						$oMessage->addFrom(new Address($sFromName));
					} else {
						$oMessage->addFrom(new Address($sFromEmailAddress, $sFromName));
					}
				}
			}

			if(!empty($this->_aValues['replyto'])) {
				$oMessage->replyTo($this->_aValues['replyto']);
			}
			
			if(!empty($this->_aValues['returnpath'])) {
				$oMessage->returnPath($this->_aValues['returnpath']);
			}

			$oMessage->subject($this->subject);

			// Inhalt
			$sHTML = $this->html;
			$sText = $this->text;

			if(empty($sHTML)) {
				$oMessage->text($sText);
			} else {
				$oMessage->html($sHTML);

				if(!empty($sText)) {
					$oMessage->attach($sText, null, 'text/plain');
				}
			}

			// Attachments
			if (is_array($this->attachments)) {
				foreach ($this->attachments as $sKey => $mValue) {

					$sFilename = null;
					if(
						!empty($mValue) &&
						is_string($mValue) &&
						!is_numeric($mValue)
					) {
						$sFilename = $mValue;
					}

					$oMessage->attachFromPath($sKey, $sFilename);

				}

			}

			// Prioritoy
			if(!empty($this->_aValues['priority'])) {
				$oMessage->priority($this->_aValues['priority']);
			}
			
			// CC/BCC
			$sCc = $this->cc;
			$sBcc = $this->bcc;

			if(!empty($sCc)) {
				$aCc = self::fixMultipleMails($sCc);
				foreach ($aCc as $sCc) {
					$oMessage->addCc($sCc);
				}
			}

			if(!empty($sBcc)) {
				$aBcc = self::fixMultipleMails($sBcc);
				foreach ($aBcc as $sBcc) {
					$oMessage->addBcc($sBcc);
				}
			}

			// WDMail Logging
			$mFrom = $oMessage->getFrom();

			if(is_array($mFrom)) {
				$mCurrent = current($mFrom);
				if ($mCurrent instanceof Address) {
					// TODO $mCurrent->toString()
					$mFrom = $mCurrent->getName().' <'.$mCurrent->getAddress().'>';
				} else {
					$mFrom = $mCurrent.' <'.key($mFrom).'>';
				}
			}
			$this->from = $mFrom;

			/**
			 * Bug in SwiftMailer, daher Error Reporting für diese Methode ausschalten 
			 */
			$iOldLevel = error_reporting(0);
			$this->header = $oMessageHeaders->toString();
			error_reporting($iOldLevel);

			$oTransport = $this->getTransport($aMailConfig);

			if (
				$oTransport instanceof \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport &&
				$aMailConfig['mailers']['smtp']['auth_mode'] === 'oauth2'
			) {
				// TODO: Entfernen mit Symfony-Mailer 6.3
				\WDMail::fixOauthEsmtpTransportAuthenticators($oTransport);
			}

			if ($oTransport instanceof \Symfony\Component\Mailer\Transport\Smtp\SmtpTransport) {
				$oTransport->setLocalDomain(\Util::getHost());
			}

			try {

				// Senden
				$oMessage = $oTransport->send($oMessage);

				$this->bSuccess = true;

			} catch (\Exception $ex) {
				/*
				 * Exception abfangen da sonst ggf. Skripte abstürzen nur weil eine Mail
				 * nicht verschickt werden konnte (z.B. wenn der Mailserver nicht erreichbar war)
				 */
				$sDump = '';
				if ($ex instanceof \Symfony\Component\Mailer\Exception\TransportException) {
					$sDump = $ex->getDebug();
				}

				$this->logger()->error('MAIL DELIVERY FAILED', ['transport' => $oTransport::class, 'from' => $mFrom, 'dump' => $sDump, 'vars' => $_REQUEST, 'error' => $ex->getMessage()]);

				if(System::d('debugmode') > 0) {
					__pout($ex);
				}

				$exception = $ex;

				$this->bSuccess = false;
			}

			$this->insertLog();

			$this->message = $oMessage;
			
		}

		if ($this->throwablesEnabled && $exception) {
			throw $exception;
		}

		return $this->_bSuccess;
	}

	public function getTransport(array $mailConfig) {

		$mailManager = new \Illuminate\Mail\MailManager(app());

		if (!empty($mailConfig['mailers']['smtp']['host'])) {

			$config = $mailConfig['mailers']['smtp'];

			if (
				$config['auth_mode'] === 'oauth2' &&
				!empty($config['oauth2_token_data'])
			) {
				$token = new AccessToken($config['oauth2_token_data']);

				if ($token->hasExpired()) {
					$token = \Api\Service\OAuth2\Token::refresh($mailConfig['mailers']['smtp']['oauth2_provider'], $token);
					if ($token) {
						\System::s('smtp_oauth2_data', json_encode($token->jsonSerialize()));
					}
				}

				$config['password'] = $token->getToken();
			}

			// Wird nicht mehr benötigt
			$config = Arr::except($config, ['auth_mode', 'oauth2_provider', 'oauth2_token_data']);

		} else {
			$config = $mailConfig['mailers']['sendmail'];
		}

		return $mailManager->createSymfonyTransport($config);

	}

	private function insertLog() {

		$sSubject = 		$this->_aValues['subject'];
		$sText = 			$this->_aValues['text'];
		$sHTML = 			$this->_aValues['html'];
		$sHeader = 			$this->_aValues['header'];
		$strFrom = 			$this->_aValues['from'];
		$strReplyTo = 		$this->_aValues['replyto'];
		$strReturnPath = 	$this->_aValues['returnpath'];

		$strSql = "
					INSERT INTO
						system_maillog
					SET
						`created` 		= NOW(),
						`success` 		= :bolSuccess,
						`to` 			= :strTo,
						`subject` 		= :strSubject,
						`text` 			= :strText,
						`html` 			= :strHTML,
						`header` 		= :strHeader,
						`attachments` 	= :strAttachments,
						`from` 			= :strFrom,
						`replyto` 		= :strReplyTo,
						`returnpath` 	= :strReturnPath
					";

		$convertToString = function ($value) {
			if ($value instanceof Address) {
				return $value->toString();
			} else if (is_array($value)) {
				return serialize($value);
			}
			return (string)$value;
		};

		$arrTransfer = array();
		$arrTransfer['bolSuccess'] 		= (int)$this->_bSuccess;
		$arrTransfer['strTo'] 			= (string)serialize($this->_aTo);
		$arrTransfer['strSubject'] 		= (string)$sSubject;
		$arrTransfer['strText'] 		= (string)$sText;
		$arrTransfer['strHTML'] 		= (string)$sHTML;
		$arrTransfer['strHeader'] 		= (string)$sHeader;
		$arrTransfer['strAttachments'] 	= (string)serialize($this->_aValues['attachments']);
		$arrTransfer['strFrom'] 		= $convertToString($strFrom);
		$arrTransfer['strReplyTo'] 		= $convertToString($strReplyTo);

		$arrTransfer['strReturnPath'] 	= (string)$strReturnPath;
		DB::executePreparedQuery($strSql, $arrTransfer);
		
	}

    public static function fixMultipleMails($aMails) {

        $aTempMails = array();
		foreach((array)$aMails as $sKey => $sMails){
			if ($sMails instanceof \Symfony\Component\Mime\Address) {
				$aTempMails[$sKey] = $sMails;
			} else {
				$aToParts = explode(',', $sMails);
				foreach($aToParts as $mKey => $sToPart) {
					if(empty($sToPart)) {
						unset($aToParts[$mKey]);
					}
				}
				$aTempMails = array_merge($aTempMails, $aToParts);
			}
		}
		
        return $aTempMails;
    }

	/**
	 * TODO: Diese Methode ist überflüssig mit Symfony-Mailer 6.3
	 *
	 * Microsoft braucht aufgrund der ganzen Authenticators in \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport zu
	 * lange um eine Verbindung aufzubauen, OAuth2 passiert leider als letztes:
	 *
	 * $this->authenticators = [
	 * 		new Auth\CramMd5Authenticator(),
	 * 		new Auth\LoginAuthenticator(),
	 * 		new Auth\PlainAuthenticator(),
	 * 		new Auth\XOAuth2Authenticator(),
	 * ];
	 *
	 * https://github.com/symfony/symfony/pull/49900
	 *
	 * @param \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport $transport
	 * @return void
	 */
	public static function fixOauthEsmtpTransportAuthenticators(\Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport $transport) {
		// Alle authenticators zurücksetzen
		$reflection = new \ReflectionClass($transport);
		$property = $reflection->getProperty('authenticators');
		$property->setAccessible(true);
		$property->setValue($transport, []);
		// Nur OAuth ausführen
		$transport->addAuthenticator(new \Symfony\Component\Mailer\Transport\Smtp\Auth\XOAuth2Authenticator());
	}

}
