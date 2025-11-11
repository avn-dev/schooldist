<?php

use \Core\Traits\UniqueKeyTrait;

/**
 * @property string id
 * @property string changed
 * @property string created
 * @property string active
 * @property string overall_satisfaction
 * @property string contact_id
 * @property string journey_id
 * @property string questionary_id
 * @property string invited
 * @property string started
 * @property string answered
 * @property string link_key
 * @property string email
 */
class Ext_TC_Marketing_Feedback_Questionary_Process extends Ext_TC_Basic {

	use UniqueKeyTrait;

	// Tabellenname
	protected $_sTable = 'tc_feedback_questionaries_processes';
	
	protected $_sTableAlias = 'tc_fqp';

	protected $_aJoinedObjects = array(
		'results' => array(
			'class' => 'Ext_TC_Marketing_Feedback_Questionary_Process_Result',
			'key' => 'questionary_process_id',
			'type' => 'child',
			'check_active' => true,
			'on_delete' => 'cascade'
		),
		'notice' => array(
			'class' => 'Ext_TC_Marketing_Feedback_Questionary_Notice',
			'key' => 'questionary_process_id',
			'type' => 'child',
			'check_active' => true,
			'on_delete' => 'cascade'
		)
	);

	/**
	 * Gibt ein Joined-Object zurück des Typs Process-Result
	 *
	 * @return Ext_TC_Marketing_Feedback_Questionary_Process_Result
	 */
	public function getJoinedObjectProcessResult() {
		$oProcessResult = $this->getJoinedObjectChild('results');
		return $oProcessResult;
	}

	/**
	 * Gibt ein Array von Ergebnissen zurück
	 *
	 * @return Ext_TC_Marketing_Feedback_Questionary_Process_Result[]
	 */
	public function getResults() {
		$aChilds = $this->getJoinedObjectChilds('results', true);
		return $aChilds;
	}

	/**
	 * @return Ext_TC_Marketing_Feedback_Questionary
	 */
	public function getQuestionary() {
		return Ext_TC_Marketing_Feedback_Questionary::getInstance($this->questionary_id);
	}

	/**
	 * @return Ext_TS_Inquiry_Journey|Ext_TA_Inquiry_Journey
	 */
	public function getJourney() {
		return Factory::getInstance('Ext_TC_Journey', $this->journey_id);
	}

	/**
	 * @return Ext_TC_Contact
	 */
	public function getContact() {
		return Ext_TC_Contact::getInstance($this->contact_id);
	}

	public function __get($sName) {

		if ($sName === 'customer_name') {
			return $this->getContact()->getName();
		}

		return parent::__get($sName);
	}

}
