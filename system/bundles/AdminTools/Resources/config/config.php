<?php

return [
	// Format ist hieran angelehnt: https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Menu-Configuration
	'menu' => [
		[
			'text' => 'Overview',
			'url' => '/admin/tools',
			'icon' => 'fa fa-dashboard'
		],
		[
			'text' => 'Log-Viewer',
			'url' => '/admin/tools/log-viewer',
			'icon' => 'fa fa-list'
		],
		[
			'text' => 'Settings',
			'url' => '/admin/tools/settings',
			'icon' => 'fa fa-cogs'
		],
		[
			'text' => 'Elasticsearch',
			'url' => '/admin/tools/elasticsearch',
			'icon' => 'fa fa-search'
		],
		[
			'text' => 'Legacy Tools',
			'url' => '/admin/tools/legacy-tools',
			'icon' => 'fa fa-wrench'
		],
		[
			'text' => 'Colors',
			'url' => '/admin/tools/colors',
			'icon' => 'fas fa-palette'
		],
		[
			'text' => 'Support Sessions',
			'url' => '/admin/tools/support-sessions',
			'icon' => 'fa fa-life-ring'
		]
	],
	'webpack' => [
		['entry' => 'js/logviewer.js', 'output' => '&', 'config' => 'backend'],
		['entry' => 'js/app.ts', 'output' => '&', 'config' => 'backend'],
		['entry' => 'scss/app.scss', 'output' => '&', 'config' => 'backend']
	],
];