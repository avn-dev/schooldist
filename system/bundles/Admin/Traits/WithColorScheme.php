<?php

namespace Admin\Traits;

use Admin\Enums\ColorScheme;
use Illuminate\Http\Request;

trait WithColorScheme
{
	private function getRequestColorScheme(Request $request, ColorScheme $default = ColorScheme::LIGHT): ColorScheme
	{
		if (!empty($colorScheme = $request->header('x-admin-color'))) {
			try {
				return ColorScheme::from($colorScheme);
			} catch (\Throwable $e) {}
		}

		return $default;
	}

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