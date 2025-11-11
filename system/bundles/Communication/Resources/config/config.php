<?php

return [

	'parallel_processing_mapping' => [
		'imap-sync' => [
			'class' => \Communication\Handler\ParallelProcessing\ImapSync::class
		]
	],
	'commands' => [
		\Communication\Commands\ImapSync::class,
		\Communication\Commands\ImapStats::class,
	],
	'tailwind' => [
		'content' => [
			'./system/bundles/Communication/Resources/views/email_layout_pics.tpl'
		]
	]
];