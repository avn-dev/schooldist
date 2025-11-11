<?php

namespace TsReporting\Entity;

/**
 * @property $id
 * @property $created
 * @property $changed
 * @property $report_id
 * @property $type
 * @property $object
 * @property $config
 */
class ReportSetting extends \Ext_TC_Basic
{
	const TYPE_GROUPING = 'grouping';

	const TYPE_COLUMN = 'column';

	const TYPE_FILTER = 'filter';

	protected $_sTable = 'ts_reporting_reports_settings';
}
