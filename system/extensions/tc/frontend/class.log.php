<?php

/**
 * @property int $id
 * @property $created (TIMESTAMP)
 * @property int $combination_id
 * @property int $template_id
 * @property string $session_id
 * @property string $method
 * @property string $url
 * @property string $user_agent
 * @property string $ip VARBINARY(16)
 */
class Ext_TC_Frontend_Log extends Ext_TC_Basic {

	protected $_sTable = 'tc_frontend_log';

	protected $_sTableAlias = 'tc_fl';

	public $bUpdateIndexEntry = false;

	public function __set($sName, $mValue) {

		if (
			$sName === 'ip' &&
			!empty($mValue)
		) {
			// VARBINARY(16)
			$mValue = @inet_pton($mValue);
		}

		parent::__set($sName, $mValue);

	}

	public function manipulateSqlParts(&$aSqlParts, $sView = null) {

		$aSqlParts['select'] .= ",
			`tc_ft`.`name` `template_name`,
			INET6_NTOA(`ip`) `ip`
		";

		$aSqlParts['from'] .= " LEFT JOIN
		 	`tc_frontend_templates` `tc_ft` ON
				`tc_ft`.`id` = `tc_fl`.`template_id`
		";

	}


}
