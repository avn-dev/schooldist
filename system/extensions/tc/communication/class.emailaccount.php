<?php

use Api\Factory\OAuth2Provider;
use Core\Service\NotificationService;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

/**
 * @property int $id
 * @property string $changed (TIMESTAMP)
 * @property string $created (TIMESTAMP)
 * @property int $active
 * @property int $editor_id
 * @property int $creator_id
 * @property string $email
 * @property int $smtp
 * @property string $smtp_host
 * @property string $smtp_user
 * @property string $smtp_pass
 * @property int $smtp_port
 * @property string $smtp_connection
 * @property int $imap
 * @property string $imap_user
 * @property string $imap_pass
 * @property string $imap_host
 * @property int $imap_port
 * @property string $imap_connection
 * @property string $imap_filter
 * @property string $imap_folder
 * @property string $imap_closure
 * @property string $imap_sent_mail_folder
 * @property int $imap_append_sent_mail
 */
class Ext_TC_Communication_EmailAccount extends Ext_TC_Basic {

	protected $_sTable = 'tc_communication_emailaccounts';
	protected $_sTableAlias = 'tc_ce';
	public $sFromName = '';
	public $sUserName = '';

	protected $_sName = '';
	private static $_oUserOptions = null;

	public $bValidateSettings = true;

	/**
	 * @var \Webklex\PHPIMAP\Client
	 */
	protected $imapClient;

	protected $_aFormat = [
		'email' => [
			'required' => true,
			'validate' => 'MAIL',
		],
		'smtp_pass' => [
			'format' => 'ENCRYPTED',
		],
		'imap_pass' => [
			'format' => 'ENCRYPTED',
		],
		'oauth2_data' => [
			'format' => 'ENCRYPTED',
		]
	];

	protected $_aAttributes = [
		'imap_sync_sent_mail' => [
			'type' => 'int'
		],
		'imap_sent_mail_latest_sync' => [
			'type' => 'text'
		]
	];
	
	protected $toBeCleaned = [
		'smtp_pass',
		'imap_pass'
	];

	public function __toString() {
		return $this->email;
	}

	public function getOAuth2ClientAuth(): ?\Api\Client\OAuth2\ClientAuth
	{
		if (empty($data = $this->getOAuth2Data())) return null;

		try {
			return \Api\Client\OAuth2\ClientAuth::fromArray($data);
		} catch (\Throwable) {}

		if (!empty($this->oauth2_provider)) {
			// Fallback
			return OAuth2Provider::getProviderClientAuth($this->oauth2_provider);
		}

		return null;
	}

	public function getOAuth2Data(): ?array {

		if (empty($this->oauth2_data)) return null;

		if (!is_array($data = json_decode($this->oauth2_data, true))) {
			NotificationService::getLogger('MailAccount')->error('Unable to decode access token', ['account_id' => $this->id, 'json_error' => json_last_error()]);
			return null;
		}

		return $data;
	}

	public function getOAuth2AccessToken(): ?\League\OAuth2\Client\Token\AccessToken {

		if (empty($data = $this->getOAuth2Data())) {
			return null;
		}

		try {

			$token = new \League\OAuth2\Client\Token\AccessToken($data);

			if ($token->hasExpired()) {

				$auth = $this->getOAuth2ClientAuth();

				$newToken = \Api\Service\OAuth2\Token::refresh($this->oauth2_provider, $token, $auth);

				if ($newToken) {
					$this->bValidateSettings = false;
					$this->setOAuth2AccessToken($this->oauth2_provider, $newToken, $auth)->save();

					$token = $newToken;
				}
			}
		} catch (\Throwable $e) {
			NotificationService::getLogger('MailAccount')->error('Unable get access token', ['account_id' => $this->id, 'message' => $e->getMessage()]);
			return null;
		}

		return $token;
	}

	public function setOAuth2AccessToken(string $provider, \League\OAuth2\Client\Token\AccessToken $accessToken, \Api\Client\OAuth2\ClientAuth $auth): static {
		$this->oauth2_provider = $provider;
		$this->oauth2_data = json_encode([
			...$accessToken->jsonSerialize(),
			...$auth->toArray()
		]);
		return $this;
	}

	public static function getConnectionTypes($sType = 'smtp') {

		$aTypes = [];
		$aTypes[''] = '';
		if($sType == 'imap') {
			//$aTypes['/pop3/notls'] = L10N::t('POP3, ohne TLS', Ext_TC_Communication::sL10NPath);
			$aTypes['/imap/notls/readonly'] = L10N::t('IMAP, ohne TLS, schreibgeschützt', Ext_TC_Communication::sL10NPath);
			$aTypes['/imap/tls/readonly'] = L10N::t('IMAP, mit TLS, schreibgeschützt', Ext_TC_Communication::sL10NPath);
			$aTypes['/imap/ssl/readonly'] = L10N::t('IMAP, mit SSL, schreibgeschützt', Ext_TC_Communication::sL10NPath);
			$aTypes['/imap/notls'] = L10N::t('IMAP, ohne TLS', Ext_TC_Communication::sL10NPath);
			$aTypes['/imap/tls'] = L10N::t('IMAP, mit TLS', Ext_TC_Communication::sL10NPath);
			$aTypes['/imap/ssl'] = L10N::t('IMAP, mit SSL', Ext_TC_Communication::sL10NPath);
		} else {
			$aTypes['SSL'] = L10N::t('SSL', Ext_TC_Communication::sL10NPath);
			$aTypes['TLS'] = L10N::t('TLS', Ext_TC_Communication::sL10NPath);
		}

		return $aTypes;

	}

	public static function getAuthTypes($sType = 'smtp') {
		$aTypes = [];
		$aTypes['password'] = L10N::t('Passwort (normal)', Ext_TC_Communication::sL10NPath);
		$aTypes['oauth2'] = L10N::t('OAuth2', Ext_TC_Communication::sL10NPath);
		return $aTypes;
	}

	public static function getImapFilter() {
		$aFilter = [];
		$aFilter['UNSEEN'] = L10N::t('Ungelesene E-Mails', Ext_TC_Communication::sL10NPath);
		$aFilter['UNDELETED'] = L10N::t('Alle E-Mails die nicht als gelöscht markiert wurden', Ext_TC_Communication::sL10NPath);
		$aFilter['FLAGGED'] = L10N::t('Alle markierten E-Mails', Ext_TC_Communication::sL10NPath);
		return $aFilter;
	}

	public static function getClosureOptions() {
		$aOptions = [];
		$aOptions['nothing'] = L10N::t('Keine Änderung', Ext_TC_Communication::sL10NPath);
		$aOptions['seen'] = L10N::t('Als gelesen markieren', Ext_TC_Communication::sL10NPath);
		$aOptions['unseen'] = L10N::t('Als ungelesen markieren', Ext_TC_Communication::sL10NPath);
		$aOptions['delete'] = L10N::t('E-Mails löschen', Ext_TC_Communication::sL10NPath);
		return $aOptions;
	}

	public static function getSelection() {		

		$sSql = "
			SELECT
				`id`,
				`email`
			FROM
				`tc_communication_emailaccounts`
			WHERE 
				`active` = 1
			ORDER BY
				`email` 
		";
		$aSelection = DB::getQueryPairs($sSql);

		$aSelection = [''=>''] + (array)$aSelection;

		return $aSelection;

	}

	/**
	 * @param bool $bACL Holt die Accounts anhand der Access Control List
	 * @param int $iUserID Anderer User für die ACL
	 * @return array 
	 */
	public static function getSelectOptions($bACL = false, $iUserID = null) {

		if(!$bACL) {
			$oSelf = new self;
			$aReturn = $oSelf->getArrayList(true, 'email');
		} else {
			$oMatrix = new Ext_TC_Communication_EmailAccount_AccessMatrix($iUserID);
			$aReturn = $oMatrix->getListByUserRight();
		}

		return $aReturn;

	}

	/**
	 * Führt Standardchecks aus und prüft optional SMTP Einstellungen
	 * 
	 * @param type $bThrowExceptions
	 * @return boolean|array 
	 */
	public function validate($bThrowExceptions = false) {

		$aErrors = parent::validate($bThrowExceptions);

		if(
			$aErrors === true &&
			$this->bValidateSettings
		) {

			if (
				$this->smtp_auth === 'oauth2' &&
				\Api\Helper\MailserverOAuth2::getByHost($this->smtp_host) === null
			) {
				$aErrors = ['smtp_auth' => ['UNKNOWN_OAUTH2_PROVIDER']];
			}

			if (
				$this->imap &&
				$this->imap_auth === 'oauth2' &&
				\Api\Helper\MailserverOAuth2::getByHost($this->imap_host) === null
			) {
				$aErrors = ['imap_auth' => ['UNKNOWN_OAUTH2_PROVIDER']];
			}

			/*// SMTP immer prüfen (#9370)
			$mCheck = $this->checkSmtp();

			if($mCheck !== true) {
				$aErrors = ['SMTP_FAILED' => $mCheck];
			}

			// Wenn IMAP aktiviert ist, Daten prüfen
			if($this->imap == 1) {

				// IMAP prüfen geht nur über IMAP Objekt
				$oImap = Ext_TC_Communication_Imap::getObjectFromArray($this->_aData);

				$mCheck = $oImap->checkConnection();

				if($mCheck !== true) {
					$aErrors = ['IMAP_FAILED' => $mCheck];
				}

			}*/

		}

		return $aErrors;

	}

	public function checkSmtp(): bool|string {

		$oMessage = new Email();

		$mailManager = new \Illuminate\Mail\MailManager(app());

		$mailConfig = app()['config']['mail'];
		$config = $mailConfig['mailers']['smtp'];
		$config['host'] = $this->smtp_host;
		$config['port'] = $this->smtp_port;
		$config['encryption'] = strtolower($this->smtp_connection);
		$config['username'] = $this->smtp_user;

		try {

			if ($this->smtp_auth === 'oauth2') {
				if (empty($accessToken = $this->getOAuth2AccessToken())) {
					throw new \Communication\Exceptions\Mail\InvalidOauth2AccessToken($this);
				}
				$config['password'] = $accessToken->getToken();
			} else {
				$config['password'] = $this->smtp_pass;
			}

			$oTransport = $mailManager->createSymfonyTransport($config);

			if (
				$this->smtp_auth === 'oauth2' &&
				$oTransport instanceof \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport
			) {
				// TODO: Entfernen mit Symfony-Mailer 6.3
				\WDMail::fixOauthEsmtpTransportAuthenticators($oTransport);
			}

			if ($oTransport instanceof \Symfony\Component\Mailer\Transport\Smtp\SmtpTransport) {
				$oTransport->setLocalDomain(\Util::getHost());
			}

			$oMailer = new Mailer($oTransport);

			// Serverkommunikation zum debuggen speichern
			#$oLogger = new Swift_Plugins_Loggers_ArrayLogger();
			#$oMailer->registerPlugin(new Swift_Plugins_LoggerPlugin($oLogger));

			$oMessage->from($this->email);
			$oMessage->to($this->email);

			$oMessage->subject(L10N::t('Fidelo - Test E-Mail'));
			$oMessage->text(L10N::t('Diese E-Mail-Nachricht wurde von Fidelo automatisch während des Testens der SMTP-Einstellungen gesendet.'));

			// Senden
			$oMailer->send($oMessage);

		} catch(\Exception $exc) {
			$sError = $exc->getMessage();
			// Serverkommunikation ausgeben
			$sError .= '<br><br>Log:';
			$sError .= '<pre style="height: 150px; overflow-y: scroll; font-size: 0.9em;">'.htmlentities($sError).'</pre>';
		}

		if(empty($sError)) {
			return true;
		} else {
			return $sError;
		}

	}

	/**
	 * Holt die Absenderoptionen, die beim Nutzer eingestellt wurden
	 *
	 * • Wenn send_email_account = user, dann kein SMTP
	 * • Wenn send_email_account = system und standard_email_account, dann dieser Account
	 * • Wenn send_email_account = system und nicht standard_email_account, dann kein SMTP
	 * • Wenn send_email_account = account (bzw. ID), dann dieser Account
	 *
	 * Äquivalent von Ext_Thebing_Mail::g()
	 *
	 * @param WDMail $oMail
	 * @param Ext_TC_User|null $oUser
	 * @return Ext_TC_Communication_EmailAccount|null
	 */
	public static function getUserOptions(WDMail $oMail, $oUser = null) {

		// Das darf nicht gecached werden, z.b. je nach iSubjectId kommen hier ja ganz andere Ergebnisse raus
		//if(self::$_oUserOptions instanceof self) {
		//	return self::$_oUserOptions;
		//}

		$mDefaultFrom = $oMail->from;
		$mEmail = '';
		$sName = '';
		$sUserName = '';

		$sObjectName = Factory::executeStatic(\Ext_TC_Object::class, 'getCommunicationName');
		$mSubObject = $oMail->iSubjectId;

		$oAccess = \Access::getInstance();
		
		if($oUser !== null) {

			// Absender-Objekt wurde übergeben
			$mAccount = property_exists($oUser, 'send_email_account') ? $oUser->send_email_account : 'system';

		} elseif(
			$oAccess instanceof \Access_Backend &&
			$oAccess->checkValidAccess() === true
		) {

			// Wenn im Framework eingeloggt, dann Sende-Account aus aktuellem User-Objekt holen
			$oUser = $oAccess->getUser();

			// Typ user, system oder Account-ID (account)
			$mAccount = property_exists($oUser, 'send_email_account') ? $oUser->send_email_account : 'system';

		} else {

			$mAccount = 'system';

		}

		// Account ID merken, falls vorhanden
		if(is_numeric($mAccount)) {
			$iAccountId = (int)$mAccount;
		} else {
			$iAccountId = 0;
		}

		// Namen bilden, falls noch nicht vorhanden
		if(empty($sName)) {

			if(!empty($mSubObject)) {

				// Namen des SubObjectes holen
				$oSubObject = Ext_TC_Factory::getInstance('Ext_TC_SubObject', $mSubObject);
				$sName = $oSubObject->getEmailSenderName();

				if(empty($sName)) {
					$sName = $sObjectName;
				}

			} else {

				$sName = $sObjectName;

			}

			// Falls Frameworkuser-Objekt vorhanden, Namen des Users anhängen
			if($oUser instanceof Ext_TC_User) {
				$sName .= ' - '.$oUser->name;
				$sUserName = $oUser->name;
			}

		}

		// Schauen, ob Typ System
		if($mAccount === 'system') {

			$mTempEmailAccount = 0;

			// Schauen, ob bei dem SubObject ein E-Mail-Konto eingestellt wurde
			if(!empty($mSubObject)) {
				$oSubObject = Ext_TC_Factory::getInstance('Ext_TC_SubObject', $mSubObject);
				$mTempEmailAccount = $oSubObject->getEmailAccount();
			}

			// Wenn noch kein E-Mail-Konto angegeben wurde das Standard-E-Mail-Konto auslesen
			if($mTempEmailAccount == 0) {
				$mTempEmailAccount = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getStandardEmailAccount');
			}

			if(is_numeric($mTempEmailAccount)) {
				$iAccountId = (int) $mTempEmailAccount;
			}

			if($iAccountId == 0) {					
				// Ansonsten Fallback auf Standard-E-Mail-Adresse des Hauptobjektes
				$mEmail = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getStandardEmailAddress');					
			}

		} elseif($mAccount === 'user') {

			$mEmail = $oUser->email;

		}

		// Objekt erzeugen
		if($iAccountId > 0) {
			$oSelf = new self($iAccountId);
			$oSelf->sUserName = $sUserName;
			$oSelf->sFromName = $sName;
		} else {

			// Wenn Empfänger vorhanden, dann Werte auslesen
			if(!empty($mDefaultFrom)) {
				if(is_string($mDefaultFrom)) {
					$aMatch = [];
					preg_match("/From: (.*?) <(.*?)>/", $mDefaultFrom, $aMatch);
					$sDefaultName = $aMatch[1];
					$mDefaultEmail = $aMatch[2];
				} else if(is_array($mDefaultFrom)) {
					$sDefaultName = reset($mDefaultFrom);
					$mDefaultEmail = reset(array_keys($mDefaultFrom));
				}
				if(empty($sName)) {
					$sName = $sDefaultName;
				}
				if(empty($mEmail)) {
					$mEmail = $mDefaultEmail;
				}
			}
			
			$oSelf = new self();
			$oSelf->sUserName = $sUserName;
			$oSelf->sFromName = $sName;
			$oSelf->email = $mEmail;
			$oSelf->smtp = 0;
		}

		//self::$_oUserOptions = $oSelf;

		return $oSelf;
	}

	/**
	 * Speichert beim Erstellen eines Accounts den Ersteller in der ACL
	 *
	 * @param bool $bLog
	 * @return Ext_TC_Communication_EmailAccount 
	 */
	public function save($bLog = true) {

		$bNew = false;
		if($this->id == 0) {
			$bNew = true;
		}

		// Immer externen Mailserver zum Senden verwenden (#9370)
		$this->smtp = 1;

		if ($this->exist()) {
			if (
				$this->smtp_host !== $this->getOriginalData('smtp_host') ||
				$this->imap_host !== $this->getOriginalData('imap_host')
			) {
				$this->oauth2_data = null;
			}
		}

		parent::save($bLog);

		if(
			$bNew === true &&
			$this->active == 1
		) {
			$oAccessMatrix = new Ext_TC_Communication_EmailAccount_AccessMatrix;
			$oAccessMatrix->createOwnerRight($this->id);
		}

		return $this;

	}

	/**
	 * @inheritdoc
	 */
	public function delete() {

		// Passwörter löschen!
		$this->smtp_pass = '';
		$this->imap_pass = '';
		$this->oauth2_data = null;

		return parent::delete();

	}

	/**
	 * Provides additional information on dependencies that would make a delete fail.
	 * Returns null in Agency for now.
	 * @return array|null
	 */
	public function getUse(): array|null {
		return null;
	}

	public function getImapClient(): \Webklex\PHPIMAP\Client {

		if($this->imapClient instanceof \Webklex\PHPIMAP\Client) {
			$this->imapClient->checkConnection();
			return $this->imapClient;
		}

		$clientManager = new \Webklex\PHPIMAP\ClientManager();

		$config = [
			'host' => $this->imap_host,
			'port' => $this->imap_port,
			'validate_cert' => true,
			'protocol' => 'imap',
			'username' => $this->imap_user,
			'password' => $this->imap_pass,
			'timeout' => 10,
			'options' => [
				'fetch_body'  => false
			]
		];

		// Default: ssl
		if (str_contains($this->imap_connection, '/ssl')) {
			$config['encryption'] = 'ssl';
		} else if (str_contains($this->imap_connection, '/tls')) {
			$config['encryption'] = 'tls';
		} else if (str_contains($this->imap_connection, '/notls')) {
			$config['encryption'] = 'notls';
		} else {
			//$config['encryption'] = false;
		}

		if ($this->imap_auth === 'oauth2') {
			$config['authentication'] = 'oauth';
			$config['password'] = $this->getOAuth2AccessToken()?->getToken();
		}

		$this->imapClient = $clientManager->make($config);

		return $this->imapClient;
	}

	public function disconnectImapClient() {
		
		if($this->imapClient instanceof \Webklex\PHPIMAP\Client) {
			
			try {
				$this->imapClient->disconnect();
			} catch (\Throwable $ex) {
			}
			
		}
		
	}
	
	protected function autoDiscoverDelimiter(\Webklex\PHPIMAP\Client $client) {

		$cacheKey = __METHOD__.$this->id;

		$delimiter = \WDCache::get($cacheKey);

		if($delimiter === null) {

			// Default
			$delimiter = '/';

            // false muss gesetzt sein, ansonsten kommt es zu komischen Fehlern (BAD Command Error. 11 - BYE Connection closed. 14)
			foreach($client->getFolders(false) as $folder) {
				if(isset($folder->delimiter)) {
					$delimiter = $folder->delimiter;
					break;
				}
			}

			Log::getLogger('imap')->info('Auto discover delimiter', ['account'=>$this->id, 'email'=>$this->email, 'delimiter'=>$delimiter]);

			\WDCache::set($cacheKey, 60*60*24*7, $delimiter);

		}

		return $delimiter;
	}


	/**
	 * Thebing-Logik: Semikolon statt Komma (Outlook-Style)
	 */
	public static function splitEmails($sEmails) {
		$sEmails = preg_replace("/;\s*;/", "", $sEmails);
		return preg_split("/\s*;\s*/", $sEmails);
	}

}
