<?php

/**
 * @property int $id
 * @property int $contact_id
 * @property int $active
 * @property string $nickname
 * @property string $password
 * @property string $credentials_locked
 * @property string $access_code
 */
class Ext_TS_Inquiry_Contact_Login extends Ext_TC_Basic {

	use \Tc\Traits\Username {
		\Tc\Traits\Username::generateUsername as traitGenerateUsername;
	}


	protected $_sTable = 'ts_inquiries_contacts_logins';

	protected $_sTableAlias = 'ts_i_c_l';

	protected $_aJoinedObjects = array(
		'contact' => array(
			'class' => 'Ext_TS_Contact',
			'type' => 'parent',
			'key' => 'contact_id',
		),
        'devices' => array(
            'class' => Ext_TS_Inquiry_Contact_Login_Device::class,
            'type' => 'child',
            'key' => 'login_id',
            'on_delete' => 'cascade'
        )
	);

	/**
	 * Passwort im Objekt speichern, damit im selben Request kein neues generiert wird
	 * @var string
	 */
	private $sPassword = null;

	public $usernameColumn = 'nickname';

	public function __get($sName) {

		Ext_Gui2_Index_Registry::set($this);

		if(
			$sName == 'firstname' ||
			$sName == 'lastname'
		) {
			// TODO: __get() irgendwann entfernen
			throw new RuntimeException('Deprecated getters still used!');

			// Siehe generateLogin()
			$oContact = $this->getContact();
			$mReturn = $oContact->$sName;
		} else {
			$mReturn = parent::__get($sName);
		}

		return $mReturn;
	}

	public function getDevices() {
	    return $this->getJoinedObjectChilds('devices');
    }

	/**
	 * Login-Daten generieren
	 */
	public function generateLogin() {

		// Hier aufrufen, und nicht auf dem Kontakt-Objekt, da checkUniqueUsername() doof ist
		$sName = $this->generateUsername();
		$this->nickname = $sName;

		// Nicht hier ausführen, weil Stellen benötigten unverschlüsseltes Passwort
		//$this->password = $this->generatePassword();

		$this->save();

	}

	/**
	 * Benutzernamen für eine bestimmte WDBasic generieren nach Vor/Nachname (wenn kein Vor/Nachname, wird ein random Wert generiert)
	 *
	 * @return string
	 */
	public function generateUsername() {

		$sName = '';
		$iCount = 0;

		$oContact = $this->getContact();

		// E-Mail als Login verwenden
		$sEmail = $oContact->getFirstEmailAddress(false)?->getEmail();
		if (
			!empty($sEmail) &&
			$this->checkUniqueUsername($sEmail)
		) {
			return $sEmail;
		}

		$sName .= $oContact->firstname;
		$sName .= $oContact->lastname;

		return $this->traitGenerateUsername($sName);

	}

	/**
	 * Generiert ein einzigartiges Benutzerpasswort
	 *
	 * @return string
	 */
	public function generatePassword() {
		$iCount = 0;

		// Im selben Objekt kann das Passwort noch bezogen werden
		if($this->sPassword !== null) {
			return $this->sPassword;
		}

		if ($this->credentials_locked) {
			return 'LOCKED';
		}

		do {
			$iCount++;

			if($iCount == 3000) {
				throw new RuntimeException('Maximum count reached');
			}

			$sPassword = strtolower(\Util::generateRandomString(8));
			$sPasswordSave = md5($sPassword);

		} while(!$this->_checkUnique('password', $sPasswordSave));

		// Passwort speichern
		$this->password = $sPasswordSave;
		$this->save();

		$this->sPassword = $sPassword;

		return $sPassword;
	}

	/**
	 * @param string $sField
	 * @param mixed $mValue
	 * @return bool
	 */
	protected function _checkUnique($sField, $mValue) {

		$sSql = "
			SELECT
				`id`
			FROM
				#table
			WHERE
				#field = :value
			LIMIT
				1
		";

		$aSql = array(
			'table' => $this->_sTable,
			'field' => $sField,
			'value' => $mValue
		);

		$iId = (int)DB::getQueryOne($sSql, $aSql);

		if($iId > 0) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * @return Ext_TS_Contact
	 */
	public function getContact(): Ext_TS_Contact {
		/** @var Ext_TS_Contact $oContact */
		$oContact = $this->getJoinedObject('contact');
		return $oContact;
	}

	protected function _getUserNameColumn() {
		return 'nickname';
	}
}
