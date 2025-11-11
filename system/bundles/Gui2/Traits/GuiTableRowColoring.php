<?php

namespace Gui2\Traits;

trait GuiTableRowColoring
{
	private function applyColor(string $hex)
	{
		return 'background-color: '.$hex.';';
		return sprintf(
			'background-color: %s; border-color: %s;',
			$hex,
			\Core\Helper\Color::changeLuminance($hex, -0.1)
		);
	}
}