<?php

namespace TcComplaints\Entity;

/**
 * Class Complaint
 * @package TcComplaints\Entity
 *
 * @property string id
 * @property string changed
 * @property string created
 * @property string active
 * @property string creator_id
 * @property string editor_id
 * @property string inquiry_id
 * @property string category_id
 * @property string sub_category_id
 * @property string complaint_id
 * @property string latest_comment_id
 * @property string type
 * @property string type_id
 * @property string complaint_date
 */
class Complaint extends \Ext_TC_Basic {

	// Tabellennamen
	protected $_sTable = 'tc_complaints';

	// Tabellen alias
	protected $_sTableAlias = 'tc_cs';

	protected $_aJoinedObjects = array(
		'history' => array(
			'class' => '\TcComplaints\Entity\ComplaintHistory',
			'type' => 'child',
			'key' => 'complaint_id',
			'check_active' => true,
			'orderby' => false,
			'on_delete' => 'cascade'
		)
	);

	/**
	 * Gibt die Kinder Elemente des aktuellen Objektes wieder
	 *
	 * @return \TcComplaints\Entity\ComplaintHistory[]
	 */
	public function getComplaintHistory() {
		return $this->getJoinedObjectChilds('history', true);
	}

	/**
	 * @return \TcComplaints\Entity\Category
	 * @throws \Exception
	 */
	public function getCategory() {
		return \TcComplaints\Entity\Category::getInstance($this->category_id);
	}

	/**
	 * @param string $sName
	 * @return string
	 */
	public function __get($sName) {

		if($sName == 'customer_name') {
			return \Factory::executeStatic('\\'.\TcComplaints\Entity\Complaint::class, 'getDialogTitle', array($sName, $this));
		}

		return parent::__get($sName);		
	}

	/**
	 * Letzter Eintrag der Historie (Kommentar) dieser Beschwerde
	 *
	 * @return \TcComplaints\Entity\ComplaintHistory
	 */
	public function getLatestHistoryObject() {
		/** @var \TcComplaints\Entity\ComplaintHistory $oComment */
		$oComment = \Factory::getInstance('\\'.\TcComplaints\Entity\ComplaintHistory::class, $this->latest_comment_id);
		if($oComment->complaint_id != $this->id) {
			throw new \RuntimeException('Complaint comment '.$oComment->id.' does not belong to complaint '.$this->id.'!');
		}
		return $oComment;
	}

	/**
	 * Query (und komische Logik) für Follow-Up-Datum
	 *
	 * @param $sField
	 * @return string
	 */
	public function getFollowUpQuery($sField) {

		return "
			SELECT
				`tc_ch`.`followup`
			FROM
				`tc_complaints_histories` `tc_ch`
			WHERE
				`tc_ch`.`complaint_id` = {$sField} AND
				`tc_ch`.`followup` IS NOT NULL AND
				UNIX_TIMESTAMP(`tc_ch`.`followup`) > 0 AND
				`tc_ch`.`active` = 1
			ORDER BY
				`tc_ch`.`id` DESC
			LIMIT
				1		
		";

	}

	/**
	 * Follow-Up-Datum (steht in irgendeinem Kommentar)
	 *
	 * @return \DateTime|null
	 */
	public function getFollowUpDate() {

		$sSql = $this->getFollowUpQuery($this->id);
		$sDate = \DB::getQueryOne($sSql);

		if($sDate !== null) {
			return new \DateTime($sDate);
		}

		return null;

	}

}