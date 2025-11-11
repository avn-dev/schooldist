<?php

return [
	'parallel_processing_mapping' => [
		'index' => [
			'class' => Gui2\Handler\ParallelProcessing\Index::class
		],
		'registry' => [
			'class' => Gui2\Handler\ParallelProcessing\Registry::class
		]
	],

	'webpack' => [
		['entry' => 'js/gui2.ts', 'output' => '&', 'config' => 'backend', 'library' => ['name' => '__FIDELO__', 'type' => 'assign-properties']],
		['entry' => 'scss/gui2.scss', 'output' => '&', 'config' => 'backend']
	],

	'tailwind' => [
		'content' => [
			'./system/legacy/admin/extensions/gui2/simple-dialog.js'
		]
	]
];