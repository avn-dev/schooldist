<?php

namespace TsReporting\Entity;

/**
 * @property string|int $id
 * @property string $name
 * @property string $age_from
 * @property string $age_until
 */
class AgeGroup extends \Ext_TC_Basic
{
	protected $_sTable = 'ts_reporting_agegroups';
}