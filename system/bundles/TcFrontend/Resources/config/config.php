<?php

return [
	'webpack' => [
		['entry' => 'js/widget.js', 'output' => '&', 'config' => 'frontend', 'library' => ['name' => '__FIDELO__', 'type' => 'assign-properties']],
		['entry' => '../../../../node_modules/iframe-resizer/js/iframeResizer.js', 'output' => 'js/iframe-resizer.js', 'config' => 'frontend'],
		['entry' => '../../../../node_modules/iframe-resizer/js/iframeResizer.contentWindow.js', 'output' => 'js/iframe-resizer-child.js', 'config' => 'frontend'],
		['entry' => 'js/payment-form/payment-form.js', 'output' => 'js/payment-form.js', 'config' => 'frontend'],
		['entry' => 'scss/payment-form/payment-form.scss', 'output' => 'css/payment-form.css', 'config' => 'frontend'],
		['entry' => 'scss/payment-form/payment-form-bootstrap4.scss', 'output' => 'css/payment-form-bootstrap4.css', 'config' => 'frontend'],
	],
];
