<?php

use Tc\Events\EmailAccountError;

/**
 * WDMail-Ableitung
 */
class Ext_TC_Communication_WDMail extends WDMail {

	public static $lastError = '';

//    public static function fixMultipleMails($aMails){
//        $aTempMails = array();
//		foreach((array)$aMails as $sKey => $sMails){
//			$aToParts = explode(',', $sMails);
//			foreach($aToParts as $mKey => $sToPart)
//			{
//				if(empty($sToPart))
//				{
//					unset($aToParts[$mKey]);
//				}
//			}
//			$aTempMails = array_merge($aTempMails, $aToParts);
//		}
//
//        return $aTempMails;
//    }
    
	/**
	 * Backend-Hook »wdmail_send«
	 * @param WDMail $oMail
	 */
	public static function setMailSenderObject(WDMail $oMail) {
        
		$aUserOptionsParameter = array($oMail);
		if(
			(
				$oMail->from_user instanceof Ext_TC_User || (
					// User-Klasse leitet aus diversen Gründen nicht von TC-Klasse ab
					Ext_TC_Util::getSystem() === 'school' &&
					$oMail->from_user instanceof Ext_Thebing_User
				)
			) &&
			$oMail->from_user->id > 0
		) {
			$aUserOptionsParameter[] = $oMail->from_user;
		}


		$oAccount = Factory::executeStatic(\Ext_TC_Communication_EmailAccount::class, 'getUserOptions', $aUserOptionsParameter);

		// Erst hier steht der tatsächliche Absender fest
		$oMail->sender_object = $oAccount;

	}

	/**
	 * Backend-Hook »wdmail_send«
	 * @param WDMail $oMail
	 */
	public static function manipulateClass(WDMail $oMail) {

		$oApp = app();

		if (!$oMail->sender_object) {
			self::setMailSenderObject($oMail);
		}

		$oAccount = $oMail->sender_object;
		/* @var Ext_TC_Communication_EmailAccount $oAccount */

		$connectionId = \Communication\Services\ConnectionLock::lock($oAccount);

		// Muss Array sein
		if(!is_array($oMail->to)) {
			$oMail->to = array($oMail->to);
		}

		// Das Trennzeichen wurde irgendwann mal auf ; geändert (inkorrekt, weil Outlook-Stil) und getrennt werden muss an höherer Stelle
//		// Kommagetrennte Mails können nicht versendet werden!
//		$aTempMails = array();
//		foreach($oMail->to as $sKey => $sMails){
//			$aToParts = explode(',', $sMails);
//			$aTempMails = array_merge($aTempMails, $aToParts);
//		}
//		$oMail->to = self::fixMultipleMails($oMail->to);

		$mailConfig = $oApp['config']['mail'];

		$mailManager = new \Illuminate\Mail\MailManager($oApp);

		$exception = null;

		if ($oAccount->smtp) {
			$config = $mailConfig['mailers']['smtp'];
			$config['host'] = $oAccount->smtp_host;
			$config['port'] = $oAccount->smtp_port;
			$config['encryption'] = strtolower($oAccount->smtp_connection);
			$config['username'] = $oAccount->smtp_user;

			if ($oAccount->smtp_auth === 'oauth2') {
				if (empty($accessToken = $oAccount->getOAuth2AccessToken())) {
					$oMail->logger()->error('Missing oauth2 access token', ['account_id' => $oAccount->id]);
                    EmailAccountError::dispatch($oAccount, 'Missing oauth2 access token');

					if ($oMail->hasThrowablesEnabled()) {
						throw new \Communication\Exceptions\Mail\InvalidOauth2AccessToken($oAccount);
					}

					$oMail->bSuccess = false;
					$oMail->bSkipSend = true;
					return;
				}
				$config['password'] = $accessToken->getToken();
			} else {
				$config['password'] = $oAccount->smtp_pass;
			}

		} else {
			$config = $mailConfig['mailers']['sendmail'];
		}

		$oTransport = $mailManager->createSymfonyTransport($config);

		if (
			$oAccount->smtp_auth === 'oauth2' &&
			$oTransport instanceof \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport
		) {
			// TODO: Entfernen mit Symfony-Mailer 6.3
			\WDMail::fixOauthEsmtpTransportAuthenticators($oTransport);
		}

		if ($oTransport instanceof \Symfony\Component\Mailer\Transport\Smtp\SmtpTransport) {
			$oTransport->setLocalDomain(\Util::getHost());
		}

		$oMessage = new \Symfony\Component\Mime\Email();
		$oMessageHeaders = $oMessage->getHeaders();

		// Header setzen
		$aHeaders = preg_split("/\r?\n/", $oMail->header);

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
				if($oMessageHeader instanceof \Symfony\Component\Mime\Header\UnstructuredHeader) {
					$oMessageHeader->setValue($sHeaderValue);
				} elseif($oMessageHeader === null) {
					$oMessageHeaders->addTextHeader($sHeaderName, $sHeaderValue);
				}
			}

		}

		foreach (\Illuminate\Support\Arr::wrap($oMail->to) as $mToEmailAddress) {
			// string or \Symfony\Component\Mime\Address
			$oMessage->addTo($mToEmailAddress);
		}

		// Achtung: Fallback
		// Wird keine E-Mail gefunden, wird die E-Mail aus der system_config genommen.
		// Diese E-Mail kann man nicht in der Thebing Software einstellen!
		if(!$oAccount->email) {
			$oAccount->email = System::d('admin_email');
		}

		if($oAccount->email) {
			$oMessage->addFrom(new \Symfony\Component\Mime\Address($oAccount->email, $oAccount->sFromName));
			$oMessage->replyTo(new \Symfony\Component\Mime\Address($oAccount->email, $oAccount->sFromName));
			$oMessage->returnPath(new \Symfony\Component\Mime\Address($oAccount->email, $oAccount->sFromName));
		}

		// Individuelle Replyto-Adresse
		if(
			$oMail->replyto !== null &&
			$oMail->replyto !== false
		) {
			foreach (\Illuminate\Support\Arr::wrap($oMail->replyto) as $mReplyToEmailAddress) {
				// string or \Symfony\Component\Mime\Address
				$oMessage->addReplyTo($mReplyToEmailAddress);
			}
		}

		$oMessage->subject($oMail->subject);
		
		// Inhalt
		$sHTML = $oMail->html;
		$sText = $oMail->text;
		
		if(empty($sHTML)) {

			$oMessage->text($sText);
			
		} else {

			$oMessage->html($sHTML);

			if(!empty($sText)) {
				$oMessage->attach($sText, null, 'text/plain');
			}
			
		}
		
		// Attachments
		if (is_array($oMail->attachments)) {
			foreach ($oMail->attachments as $sKey => $mValue) {
				
				if (
					$mValue instanceof attachment ||
					$mValue instanceof fileAttachment
				) {
					// TODO was ist das?

					//$oAttachment = new Swift_Attachment($mValue->data, $mValue->name, "automatic/name");
					
				} else {

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
			
		}
		
		// CC/BCC
		$sCc = \Illuminate\Support\Arr::wrap($oMail->cc);
		$sBcc = \Illuminate\Support\Arr::wrap($oMail->bcc);

		if(!empty($sCc)) {
			//$aCc = self::fixMultipleMails($sCc);
			foreach ($sCc as $sSingleCc) {
				$oMessage->addCc($sSingleCc);
			}
		}

		if(!empty($sBcc)) {
			//$aBcc = self::fixMultipleMails($sBcc);
			foreach ($sBcc as $sSingleBcc) {
				$oMessage->addBcc($sSingleBcc);
			}
		}

		// WDMail Logging
		$mFrom = $oMessage->getFrom();
		if(is_array($mFrom)) {
			$mCurrent = current($mFrom);
			if ($mCurrent instanceof \Symfony\Component\Mime\Address) {
				// TODO $mCurrent->toString()
				$mFrom = $mCurrent->getName().' <'.$mCurrent->getAddress().'>';
			} else {
				$mFrom = $mCurrent.' <'.key($mFrom).'>';
			}
		}
		$oMail->from = $mFrom;

		if($oMail->priority !== null) {
			$oMessage->priority($oMail->priority);
		}
		
		/**
		 * Bug in SwiftMailer, daher Error Reporting für diese Methode ausschalten
		 * Bug existiert immer noch: https://github.com/swiftmailer/swiftmailer/issues/1262
		 */
		try {
			$oMail->header = $oMessageHeaders->toString();
		} catch (\Throwable $e) {}

		try {

			// Senden
			$oMessage = $oTransport->send($oMessage);

			$oMail->bSuccess = true;
			
		} catch (\Throwable $ex) {
			/*
			 * Exception abfangen da sonst ggf. Skripte abstürzen nur weil eine Mail
			 * nicht verschickt werden konnte (z.B. wenn der Mailserver nicht erreichbar war)
			 */
			$sErrorMessage = $ex->getMessage();
			self::$lastError = $sErrorMessage;

			$sDump = '';
			if ($ex instanceof \Symfony\Component\Mailer\Exception\TransportException) {
				$sDump = $ex->getDebug();
			}

			$oMail->logger()
				->error('MAIL DELIVERY FAILED', ['transport' => $oTransport::class, 'from' => $mFrom, 'dump' => $sDump, 'vars' => $_REQUEST, 'error' => $ex->getMessage()]);

			if(System::d('debugmode') > 0) {
				__pout($ex);
			}

			$oMail->bSuccess = false;

			$exception = $ex;
		}

		\Communication\Services\ConnectionLock::unlock($oAccount, $connectionId);

		if($oMail->bSuccess) {

			// Optional in IMAP-Ordner verschieben
			if(
				$oAccount->imap == 1 &&
				$oAccount->imap_append_sent_mail == 1
			) {
				/** @var Ext_TC_Communication_Imap $oImap */
				$oImap = Ext_TC_Communication_Imap::getObjectFromArray($oAccount->aData); 
				$bAppend = $oImap->appendSentMail($oMessage);

				if($bAppend !== true) {
					$oMail->logger()->error('IMAP: Append sent mail failed', ['account' => $oAccount->email, 'append' => $bAppend]);
				}
			}
			
		}

		$oMail->bSkipSend = true;

		$oMail->message = $oMessage;

		if ($oMail->hasThrowablesEnabled() && $exception) {
			throw $exception;
		}

	}



}
