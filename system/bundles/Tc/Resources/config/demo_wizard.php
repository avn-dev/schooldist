<?php

return [
	'step_1' => [
		'type' => 'step',
		'title' => 'Step #1', 'class' => Step::class,
	],
	'block_2' => [
		'type' => 'block',
		'title' => 'Block #2',
		'icon' => 'fa fa-cog',
		'elements' => [
			'loop_1' => [
				'type' => 'block',
				'title' => 'Loop #1',
				'class' => LoopBlock::class,
				'elements' => [
					'step_1' => [
						'type' => 'step',
						'title' => 'Step #1', 'class' => Element\Step::class
					],
					'loop_2' => [
						'type' => 'block',
						'title' => 'Loop #2',
						'class' => LoopBlock2::class,
						'elements' => [
							'step_1' => [
								'type' => 'step',
								'title' => 'Step #1', 'class' => Step::class
							],
							'step_2' => [
								'type' => 'step',
								'title' => 'Step #2', 'class' => Step::class
							]
						]
					],
					'step_3' => [
						'type' => 'step',
						'title' => 'Step #3', 'class' => Step::class
					]
				]
			],
			'block_2' => [
				'type' => 'block',
				'title' => 'Block #2',
				'elements' => [
					'step_1' => ['type' => 'step', 'title' => 'Step #1', 'class' => Step::class],
				]
			],
			'step_3' => ['type' => 'step', 'title' => 'Step #3', 'class' => Step::class],
		]
	],
	'step_3' => [
		'type' => 'step',
		'title' => 'Step #3', 'class' => Step::class
	],
];