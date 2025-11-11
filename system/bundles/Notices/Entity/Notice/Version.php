<?php

namespace Notices\Entity\Notice;

class Version extends \WDBasic {

	protected $_sTable = 'notices_versions';
	protected $_sTableAlias = 'nv';

	protected $_aJoinedObjects = [
		'creator' => [
			'class' => 'User',
			'key' => 'creator_id',
			'type' => 'parent',
			'readonly' => true
		],
		'notice' => [
			'class' => '\Notices\Entity\Notice',
			'key' => 'notice_id',
			'type' => 'parent',
			'readonly' => true
		]
	];

	public function save() {

		parent::save();

		\DB::updateData('notices', ['latest_version_id'=>$this->id], ['id'=>$this->notice_id]);

		return $this;
	}

}