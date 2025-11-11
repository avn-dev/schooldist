<?php

class Ext_TS_Enquiry_Group extends Ext_Thebing_Inquiry_Group {

//	const JOIN_CONTACTS = 'contacts';

//	protected $sNumberrangeClass = Ts\Service\Numberrange\BookingGroup::class;

//	protected $_aJoinTables = [
//		self::JOIN_CONTACTS => [
//			'table'	=> 'ts_groups_to_contacts',
//			'foreign_key_field'	=> 'contact_id',
//			'primary_key_field'	=> 'group_id',
//			'class'	=> 'Ext_TS_Group_Contact',
//			'static_key_fields' => ['type' => 'inquiry'],
//			'autoload' => true
//		]
//	];

//class Ext_TS_Enquiry_Group extends Ext_TS_Group {
//
//	protected $_aJoinTables = array(
//		'enquiry' => array(
//			'table'				=> 'ts_enquiries_to_groups',
//			'foreign_key_field'	=> 'enquiry_id',
//	 		'primary_key_field'	=> 'group_id',
//			'class'				=> 'Ext_TS_Enquiry',
//			'autoload'			=> true,
//			'readonly' => true // Wichtig, da diese JoinTable bei der Enquiry als bidirectional angelegt ist
//		)
//	);
//
//	/**
//	 * Liefert das Anfragenobjekt der Gruppe
//	 * @return Ext_TS_Enquiry
//	 */
//	public function getEnquiry() {
//
//		$aEnquiries = $this->getJoinTableObjects('enquiry');
//		$oEnquiry = null;
//		// Eine Gruppe gehört momentan nur zu EINER Anfrage
//		if(!empty($aEnquiries)){
//			$oEnquiry = reset($aEnquiries);
//		}
//
//		return $oEnquiry;
//	}

	/**
	 * @return Ext_TS_Group_Contact[]|Ext_TS_Inquiry[]
	 */
	public function getMembers() {
		return $this->getJoinTableObjects(self::JOIN_CONTACTS);
	}
	
	/**
	 * Anzahl der Mitglieder in der Gruppe
	 * 
	 * @return int 
	 */
	public function countAllMembers() {

		$aContacts = $this->getMembers();
		$iMemberCount = count($aContacts);
		
		return $iMemberCount;
	}
	
	/**
	 *
	 * @return Ext_TS_Group_Contact[]
	 */
	public function getGuides() {
		$aGuides = $this->getMembersByFilter('detail_guide', 1);
		return $aGuides;
	}
	
	/**
	 *
	 * @return Ext_TS_Group_Contact[]
	 */
	public function getNotGuideMembers() {
		$aNotGuides	= $this->getMembersByFilter('detail_guide', 0);
		return $aNotGuides;
	}

	/**
	 * @param $sFilter
	 * @param $mValue
	 * @return Ext_TS_Group_Contact[]
	 */
	public function getMembersByFilter($sFilter, $mValue) {

		$aFilteredMembers = array();
		$aMembers = (array)$this->getMembers();
		
		foreach($aMembers as $oMember) {
			if($oMember->$sFilter == $mValue) {
				$aFilteredMembers[$oMember->id] = $oMember;
			}
		}
		
		return $aFilteredMembers;
	}
	
	/**
	 * Anzahl der Guides in der Gruppe
	 * 
	 * @return int 
	 */
	public function countGuides() {

		$aGuides = $this->getGuides();
		$iCountGuides = count($aGuides);

		return $iCountGuides;
	}
	
	/**
	 * Anzahl der nicht-Guides in der Gruppe
	 * 
	 * @return int 
	 */
	public function countNonGuideMembers() {

		$aNotGuides = $this->getNotGuideMembers();
		$iCountNotGuides = count($aNotGuides);

		return $iCountNotGuides;
	}
	
	/**
	 * Ermittelt die Inquiry mit den meisten Dokumenten
	 * @return Ext_TS_Inquiry
	 */
	public function getMainDocumentInquiry() {
		$oInquiry = $this->getEnquiry();
		return $oInquiry;

	}

	public function isInquiryBelongingToDocument(Ext_TS_Inquiry_Abstract $oInquiry, Ext_Thebing_Inquiry_Document $oDocument) {
		return true;
	}

}