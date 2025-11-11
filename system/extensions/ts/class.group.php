<?php

/**
 * TODO 16002 Entfernen
 *
 * Achtung: Das hier ist NICHT die Klasse für Gruppenbuchungen!
 * @see Ext_Thebing_Inquiry_Group
 */
class Ext_TS_Group extends Ext_Thebing_Basic implements Ext_TS_Group_Interface{

	public function __construct($iDataID = 0, $sTable = null) {
		throw new LogicException(__CLASS__.' used');
		parent::__construct($iDataID, $sTable);
	}

	use \Ts\Traits\Numberrange;
	
	/**
	 * {@inheritdoc}
	 */
	protected $_sTable = 'ts_groups';

	/**
	 * {@inheritdoc}
	 */
	protected $_sTableAlias = 'ts_g';

	/**
	 * {@inheritdoc}
	 */
	protected $_aFormat = array(
		'changed' => array(
			'format' => 'TIMESTAMP'
		),
		'created' => array(
			'format' => 'TIMESTAMP'
		),
		'name' => array(
			'required' => true
		),
		'name_short' => array(
			'required' => true,
		),
		'limit' => array(
			'required' => true
		)
	);

	/**
	 * @return string
	 */
	public function getShortName(){
		return $this->name_short;
	}
	
	/**
	 * Die Funktion liefert einem alle Gruppenkontakte
	 *
	 * @return Ext_TS_Group_Contact[]
	 */
	public function getContacts() {
		$aContacts = $this->getJoinTableObjects('contacts');
		return $aContacts;
	}

	public function countNonGuideMembers(){

	}

	public function countGuides() {

	}

	public function getMainDocumentInquiry() {

	}
	
	/**
	 * @param bool $bLog
	 * @return type
	 */
	public function save($bLog = true) {
		
		// Gruppennummer bei Anfragen ist optional
		if(System::d('groupnumber_enquiry') === '1') {

			// Nummernkreis erzeugen
			$mNumber = $this->getNumber();

			if(empty($mNumber)) {
				$this->generateNumber();
			}
			
		}

		$aTransfer = parent::save($bLog);

		return $aTransfer;
	}

}
