<?php

namespace TsComplaints\Service;

use TcComplaints\Entity;
use TcComplaints\Entity\ComplaintHistory;
use TcComplaints\Gui2\Format;
use TsComplaints\Entity\Complaint;

class OldPlaceholder extends \Ext_Thebing_Placeholder {

	/**
	 * @var Complaint
	 */
	protected $oComplaint;

	/**
	 * @var ComplaintHistory
	 */
	protected $oComplaintCommentLoop;

	/**
	 * @param Complaint $oComplaint
	 */
	public function  __construct(Complaint $oComplaint = null) {
		parent::__construct();
		$this->oComplaint = $oComplaint;
	}

	/**
	 * @param string $sType
	 * @return array
	 */
	public function getPlaceholders($sType = '') {

		$aPlaceholders = [
			[
				'section' => \L10N::t('Beschwerden', \TsComplaints\Gui2\Data\Complaint::TRANSLATION_PATH),
				'placeholders' => array(
					'complaint_category' => \L10N::t('Kategorie', \TsComplaints\Gui2\Data\Complaint::TRANSLATION_PATH),
					'complaint_subcategory' => \L10N::t('Unterkategorie', \TsComplaints\Gui2\Data\Complaint::TRANSLATION_PATH),
					'complaint_area' => \L10N::t('Bereich', \TsComplaints\Gui2\Data\Complaint::TRANSLATION_PATH),
					'complaint_provider' => \L10N::t('Anbieter', \TsComplaints\Gui2\Data\Complaint::TRANSLATION_PATH),
					'complaint_status' => \L10N::t('Status', \TsComplaints\Gui2\Data\Complaint::TRANSLATION_PATH),
					'complaint_date' => \L10N::t('Datum', \TsComplaints\Gui2\Data\Complaint::TRANSLATION_PATH),
					'complaint_follow_up' => \L10N::t('Nachhaken', \TsComplaints\Gui2\Data\Complaint::TRANSLATION_PATH),
				)
			],
			[
				'section' => \L10N::t('Beschwerde-Kommentare', \TsComplaints\Gui2\Data\Complaint::TRANSLATION_PATH),
				'placeholders' => [
					'start_loop_complaint_notes}.....{end_loop_complaint_notes' =>  \L10N::t('Durchläuft die Kommentare der Beschwerde', \TsComplaints\Gui2\Data\Complaint::TRANSLATION_PATH),
					'complaint_note_note' => \L10N::t('Kommentar', \TsComplaints\Gui2\Data\Complaint::TRANSLATION_PATH),
					'complaint_note_type' => \L10N::t('Art des Kommentars', \TsComplaints\Gui2\Data\Complaint::TRANSLATION_PATH)
				]
			]
		];

		return $aPlaceholders;

	}

	/**
	 * @inheritdoc
	 */
	protected function _helperReplaceVars($sText, $iOptionalId = 0) {

		$sText = parent::_helperReplaceVars($sText, $iOptionalId);

		$sText = preg_replace_callback('@\{start_loop_complaint_notes\}(.*)\{end_loop_complaint_notes\}@ims', [$this, 'helperReplaceComplaintCommentsLoop'], $sText);

		$sText = $this->_helperReplaceVars2($sText, $iOptionalId);

		return $sText;
	}

	/**
	 * Muss für die reingehackten Schleifen gemacht werden
	 *
	 * @inheritdoc
	 */
	public function replace($sText = '', $iPlaceholderLib = 1, $iOptionalId = 0) {
		$this->_iPlaceholderLib = $iPlaceholderLib;
		$sReturn = $this->_helperReplaceVars($sText, $iOptionalId);
		return $sReturn;
	}

	/**
	 * Muss für die reingehackten Schleifen gemacht werden
	 *
	 * @inheritdoc
	 */
	protected function _getPlaceholderValue($sField, $iOptionalParentId = 0, $aPlaceholder=array()) {
		$mValue = $this->_getReplaceValue($sField, $aPlaceholder);
		return $mValue;
	}

	protected function helperReplaceComplaintCommentsLoop(array $aText) {
		$sText = '';
		if(!$this->oComplaint) {
			return $sText;
		}

		$aComments = $this->oComplaint->getComplaintHistory();
		foreach($aComments as $oComment) {
			$this->oComplaintCommentLoop = $oComment;
			$sText .= $this->_helperReplaceVars($aText[1]);
		}

		$this->oComplaintCommentLoop = null;

		return $sText;
	}

	/**
	 * @param string $sPlaceholder
	 * @param array $aPlaceholder
	 * @return string
	 */
	protected function _getReplaceValue($sPlaceholder, array $aPlaceholder) {

		$sValue = '';
		if(empty($this->oComplaint)) {
			return $sValue;
		}

		switch($sPlaceholder) {
			case 'complaint_category':
				$sValue = Entity\Category::getInstance($this->oComplaint->category_id)->title;
				break;
			case 'complaint_subcategory':
				$sValue = Entity\SubCategory::getInstance($this->oComplaint->sub_category_id)->title;
				break;
			case 'complaint_area':
				$oFormat = new Format\DependencyOnFormat();
				$sValue = $oFormat->format($this->oComplaint->getCategory()->type);
				break;
			case 'complaint_provider':
				$oFormat = new Format\DependencyPersonalNames();
				$oDummy = null;
				$sValue = $oFormat->format($this->oComplaint->type_id, $oDummy, $this->oComplaint->getData());
				break;
			case 'complaint_status':
				$oFormat = new Format\ComplaintState();
				$oDummy = null;
				$aData = ['state' => $this->oComplaint->getLatestHistoryObject()->state];
				$sValue = $oFormat->format(null, $oDummy, $aData);
				break;
			case 'complaint_date':
				$sValue = (new \Ext_Thebing_Gui2_Format_Date())->format($this->oComplaint->complaint_date);
				break;
			case 'complaint_follow_up':
				$dDate = $this->oComplaint->getFollowUpDate();
				if($dDate !== null) {
					$sValue = (new \Ext_Thebing_Gui2_Format_Date())->format($dDate);
				}
				break;
			case 'complaint_note_note':
				if($this->oComplaintCommentLoop instanceof ComplaintHistory) {
					$sValue = $this->oComplaintCommentLoop->comment;
				}
				break;
			case 'complaint_note_type':
				if($this->oComplaintCommentLoop instanceof ComplaintHistory) {
					$aTypes = \TsComplaints\Gui2\Data\ComplaintHistory::getCommentaryType($this->sTemplateLanguage);
					if(isset($aTypes[$this->oComplaintCommentLoop->comment_type])) {
						$sValue = $aTypes[$this->oComplaintCommentLoop->comment_type];
					}
				}
				break;
			default:

				$oInquiry = $this->oComplaint->getInquiry();
				$oInquiryPlaceholder = new \Ext_Thebing_Inquiry_Placeholder($oInquiry->id);
				$sValue = $oInquiryPlaceholder->searchPlaceholderValue($sPlaceholder, 0, $aPlaceholder);

		}

		return $sValue;

	}

}
