<?php

namespace Notices\Entity;

class Notice extends \WDBasic {

	protected $_sTable = 'notices';
	protected $_sTableAlias = 'n';

	protected $_aJoinedObjects = [
		'creator' => [
			'class' => 'User',
			'key' => 'creator_id',
			'type' => 'parent',
			'readonly' => true
		],
		'versions' => [
			'class' => 'Notices\Entity\Notice\Version',
			'key' => 'notice_id',
			'type' => 'child',
		],
		'latest_version' => [
			'class' => 'Notices\Entity\Notice\Version',
			'key' => 'latest_version_id',
			'type' => 'parent',
			'readonly' => true
		]
	];

	const LOG_NOTICE_CREATED = 'notices/notice-created';
	const LOG_NOTICE_UPDATED = 'notices/notice-updated';
	const LOG_NOTICE_DELETED = 'notices/notice-deleted';

	public function getLatestVersion()
	{
		return $this->getJoinedObject('latest_version');
	}

}