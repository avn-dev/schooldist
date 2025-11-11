<?php

use Communication\Interfaces\Notifications\NotificationRoute;

/**
 * @property string $id
 * @property string $changed
 * @property string $created
 * @property string $valid_until
 * @property string $active
 * @property string $creator_id
 * @property string $editor_id
 * @property string $email
 * @property string $master
 * @property string $type
 */
class Ext_TC_Email_Address extends Ext_TC_Basic implements Ext_TC_Frontend_Form_Entity_Interface, NotificationRoute {

	/**
	 * @var string
	 */
	protected $_sTable = 'tc_emailaddresses';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'tc_e';

	/**
	 * @var array
	 */
	protected $_aMappingClass = array(
		'customer_index' => 'Ext_TC_Email_Address_Mapping'	,
		'frontend_form' => 'Ext_TC_Email_Address_Mapping_Frontend'	
	);

	/**
	 * @var string
	 */
	protected $_sPlaceholderClass = 'Ext_TC_Placeholder_Email';

	/**
	 * @var array
	 */
	protected $_aFormat = array(
		'email' => array(
			'validate'	=> 'MAIL'
		)
	);

	protected $_aJoinTables = [
		'contacts' => [
			'table' => 'tc_contacts_to_emailaddresses',
			'foreign_key_field' => 'contact_id',
			'primary_key_field' => 'emailaddress_id',
			'class' => \Ext_TC_Contact::class,
			'autoload' => false,
			'readonly' => true,
		],
	];

	/**
	 * Für Index null zurück liefern, damit _exists_ korrekt funktioniert
	 *
	 * @return null|string
	 */
	public function getEmail() {

		if(empty($this->email)) {
			return null;
		}

		return $this->email;

	}

	/**
	 * Alle verfügbaren Beschreibungen für die Art einer E-Mail
	 *
	 * @param string $sLanguage
	 * @param boolean $bFrontendTranslation
	 * @return array
	 */
	public static function getTypes($sLanguage = '', $bFrontendTranslation = false) {

		$aTypes = array(
			'private' => 'E-Mail Privat',
			'business' => 'E-Mail Geschäftlich',
			'emergency' => 'E-Mail Notfall'
		);

		$aReturnTypes = array();
		foreach($aTypes as $sKey => $sValue) {
			if($bFrontendTranslation) {
				// Frontend Übersetzung
				$aReturnTypes[$sKey] = Ext_TC_Placeholder_Abstract::translateFrontend($sValue, $sLanguage);
			} else {
				// Backend Übersetzung
				$aReturnTypes[$sKey] = Ext_TC_L10N::t($sValue, $sLanguage);
			}
		}

		return $aReturnTypes;
	}

	/**
	 * Liefert die Beschreibung für die Art einer E-Mail (z.B. Privat)
	 *
	 * @param string $sLanguage
	 * @param boolean $bFrontendTranslation
	 * @return string
	 */
	public function getDescription($sLanguage = '', $bFrontendTranslation = false) {

		$aTypes = Ext_TC_Contact_Detail::getTypes($sLanguage, $bFrontendTranslation);

		$sDescription = '';
		if(isset($aTypes[$this->type])) {
			$sDescription = $aTypes[$this->type];
		}

		return $sDescription;
	}
	
	/**
	 * @see Interface
	 */
	public function addChild($sChildIdentifier, $sJoinedObjectCacheKey) {
		return false;
	}
	
	/**
	 * @see Interface
	 */
	public function validate($bThrowExceptions = false) {
		return parent::validate($bThrowExceptions);
	}

	/**
	 * @see Interface
	 */
	public function removeChild($sChildIdentifier, $iCount) {
		
	}

	public function getContact(): ?\Ext_TC_Contact
	{
		if (!empty($contacts = $this->getJoinTableObjects('contacts'))) {
			return \Illuminate\Support\Arr::first($contacts);
		}

		return null;
	}

	public function toNotificationRoute(string $channel)
	{
		return [$this->email, $this->getContact()?->getName()];
	}

	public function getNotificationName(string $channel): ?string
	{
		return $this->email;
	}

}