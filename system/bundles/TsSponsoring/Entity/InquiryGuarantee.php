<?php

namespace TsSponsoring\Entity;

use Carbon\Carbon;

/**
 * @property $id
 * @property $created
 * @property $changed
 * @property $active
 * @property $creator_id
 * @property $editor_id
 * @property $inquiry_id
 * @property $from
 * @property $until
 * @property $path
 */
class InquiryGuarantee extends \Ext_TC_Basic {

	protected $_sTable = 'ts_inquiries_sponsoring_guarantees';

	protected $_sTableAlias = 'ts_isg';

	protected $_sPlaceholderClass = InquiryGuarantee\Placeholder::class;

	protected $_aFormat = [
		'from' => [
			'validate' => 'DATE',
			'required' => true
		],
		'until' => [
			'validate' => 'DATE',
			'required' => true
		]
	];

	public function getFrom(): ?Carbon {
		if($this->from == '0000-00-00'){
			return null;
		}
		return Carbon::parse($this->from);
	}

	public function getUntil(): ?Carbon  {
		if($this->until == '0000-00-00'){
			return null;
		}
		return Carbon::parse($this->until);
	}

	public function hasUpload(): bool {
		return (bool)($this->getUpload() !== null);
	}

	public function getUpload(): ?string {

		if (empty($this->path)) {
			return null;
		}

		return $this->path;
	}

	/**
	 * @inheritdoc
	 */
	public function validate($bThrowExceptions = false) {

		$mValidate = parent::validate($bThrowExceptions);

		if($mValidate === true) {
			if(new \DateTime($this->from) > new \DateTime($this->until)) {
				$mValidate = ['ts_isg.until' => 'INVALID_DATE_UNTIL_BEFORE_FROM'];
			}
		}

		return $mValidate;

	}

}
