<?php

namespace Admin\Traits;

use Admin\Enums\InterfaceColorScheme as ColorScheme;

trait InterfaceColorScheme
{
	protected function getColorScheme(\User $user = null, ColorScheme $default = ColorScheme::AUTO): ColorScheme
	{
		return ColorScheme::LIGHT;
		$colorScheme = $this->getSystemColorScheme($default);

		if ($user) {
			// Benutzereinstellung verwenden
			$colorScheme = $this->getUserColorScheme($user, $colorScheme);
		}

		return $colorScheme;
	}

	protected function getSystemColorScheme(ColorScheme $default = ColorScheme::AUTO): ColorScheme
	{
		if (!empty($mode = \System::d('color_scheme', null))) {
			return ColorScheme::from($mode);
		}
		return $default;
	}

	protected function getUserColorScheme(\User $user, ColorScheme $default = ColorScheme::AUTO): ColorScheme
	{
		return $user->getInterfaceColorScheme() ?: $default;
	}
}