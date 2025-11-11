<?php

return [

	'commands' => [
		OpenBanking\Command\FinApiBackgroundUpdate::class
	],

	'webpack' => [
		['entry' => 'js/finAPI/external_app.ts', 'output' => '&', 'config' => 'backend'],
	],

];