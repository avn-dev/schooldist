<?php

return [
	'webpack' => [
		['entry' => 'js/reporting.ts', 'output' => '&', 'config' => 'backend', 'library' => ['name' => ['__FIDELO__', 'TsReporting'], 'type' => 'assign-properties']],
		['entry' => 'scss/reporting_legacy.scss', 'output' => '&', 'config' => 'backend']
	]
];
