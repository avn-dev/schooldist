<?php

namespace TsStudentApp\Helper;

use Core\Helper\Color;

/**
 * App-Farben für Ionic generieren, um CSS-Variablen zu überschreiben
 *
 * @see https://ionicframework.com/docs/theming/color-generator
 */
class AppColor {

	private $school;

	public function __construct(\Ext_Thebing_School $school) {
		$this->school = $school;
	}

	public function generateColorsArray(): array {

		$colors = [];
		$appConfig = $this->school->getAppSettingsConfig();

		$colorPrimary = $appConfig->getValue('color_primary', '', true);
		if (!empty($colorPrimary)) {
			$colors = array_merge($colors, $this->generateColor('primary', $colorPrimary));
		}

		$colorSecondary = $appConfig->getValue('color_secondary', '', true);
		if (!empty($colorPrimary) && !empty($colorSecondary)) {
			$colors = array_merge($colors, $this->generateColor('secondary', $colorSecondary));
		}

		return $colors;

	}

	private function generateColor(string $name, string $color): array {

		return [
			"--ion-color-{$name}" => $color,
			"--ion-color-{$name}-rgb" => join(', ', Color::convertHex2RGB($color)),
			"--ion-color-{$name}-contrast" => '#FFFFFF',
			"--ion-color-{$name}-contrast-rgb" => '255, 255, 255',
			"--ion-color-{$name}-shade" => $this->applyShade($color),
			"--ion-color-{$name}-tint" => $this->applyTint($color)
		];

	}

	private function applyShade(string $color): string {

		// Das erzeugt da selbe Resultat wie SCSS scale-color($color, $lightness: -11.93%)
		// Wiederum erzeugt dies dasselbe Resultat wie der Ionic Color Generator
		// Achtung, der beschriebene SCSS-Wert wird auch so in der App verwendet!
		return Color::changeLuminance($color, -0.115);

	}

	private function applyTint(string $color): string {

		// Das erzeugt das selbe Resultat wie SCSS lighten($primary, 1.5)
		// Der Wert kommt sehr nah an das Resultat vom Ionic Color Generator
		// Achtung, der beschriebene SCSS-Wert wird auch so in der App verwendet!
		return Color::changeLuminance($color, 0.05);

	}


}
