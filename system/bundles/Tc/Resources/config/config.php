<?php

return [

	'parallel_processing_mapping' => [
		'combination-initialize' => [
			'class' => Tc\Handler\ParallelProcessing\Combination\Initialize::class
		],
		'access-update' => [
			'class' => Tc\Handler\ParallelProcessing\Access\Update::class
		],
		// @deprecated
		'communication-message' => [
			'class' => Tc\Handler\ParallelProcessing\Communication\MessageProcess::class
		],
	],
	'commands' => [
		Tc\Command\StoredFunctions::class,
		Tc\Command\Events\ManageableList::class,
		Tc\Command\ResetIndex::class
	],

	'webpack' => [
		['entry' => 'scss/wizard.scss', 'output' => '&', 'config' => 'backend'],
		['entry' => 'scss/communication.scss', 'output' => '&', 'config' => 'backend']
	],

];