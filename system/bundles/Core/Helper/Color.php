<?php

namespace Core\Helper;

class Color {

	const SHADES = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];
	const CONTRAST_RATIO_ELEMENT = 3;
	const CONTRAST_RATIO_TEXT = 4.5;

	static public function isLight($sHex) {

		$fLightness = self::getLightness($sHex);

		if($fLightness > 0.72) {
			return true;
		}

		return false;
	}

	public static function getLightness(string $sHex): float {

		$aRgb = \Core\Helper\Color::convertHex2RGB($sHex);

		$fLightness = (0.2126 * $aRgb['red'] + 0.7152 * $aRgb['green'] + 0.0722 * $aRgb['blue']) / 255;

		return $fLightness;
	}

	static public function changeLuminance(string $sHex, float $fPercent) {

		// validate hex string

		$sHex = preg_replace( '/[^0-9a-f]/i', '', $sHex );
		$new_hex = '#';

		if ( strlen( $sHex ) < 6 ) {
			$sHex = $sHex[0] + $sHex[0] + $sHex[1] + $sHex[1] + $sHex[2] + $sHex[2];
		}

		// convert to decimal and change luminosity
		for ($i = 0; $i < 3; $i++) {
			$dec = hexdec( substr( $sHex, $i*2, 2 ) );
			$dec = min( max( 0, $dec + $dec * $fPercent ), 255 ); 
			$new_hex .= str_pad( dechex( $dec ) , 2, 0, STR_PAD_LEFT );
		}		

		return $new_hex;
	}
	
	/*
	 * Funktion liefert global die Colorcodes für das System zurück
	 */
	public static function changeOpacity($sColor, $iFactor) {

		if($iFactor < 100) {
			// Deckkraft anpassen
			$aRGB = self::convertHex2RGB($sColor);

			$iMul = (100 - $iFactor) / 100;

			foreach((array)$aRGB as $sKey=>$iValue) {
				$aRGB[$sKey] = round($iValue + ((255 - $iValue) * $iMul));
			}

			$sColor = self::convertRGB2Hex($aRGB);

		}

		return $sColor;

	}

	public static function convertRGB2Hex($aRGB) {

		$aRGB = array_values($aRGB);

		if(count($aRGB) != 3) {
			return false;
		}

		$sHex = '#';
		for($i = 0; $i<3; $i++) {
			$aRGB[$i] = dechex(($aRGB[$i] <= 0)?0:(($aRGB[$i] >= 255)? 255 : $aRGB[$i]));
		}

		for($i = 0; $i<3; $i++) {
            $sHex .= ((strlen($aRGB[$i]) < 2)?'0':'').$aRGB[$i];
		}

		$sHex = strtoupper($sHex);

		return $sHex;

	}

	/**
	 * Convert a hexa decimal color code to its RGB equivalent
	 *
	 * @param string $hexStr (hexadecimal color value)
	 * @param boolean $returnAsString (if set true, returns the value separated by the separator character. Otherwise returns associative array)
	 * @param string $seperator (to separate RGB values. Applicable only if second parameter is true.)
	 * @return array or string (depending on second parameter. Returns False if invalid hex color value)
	 */
	public static function convertHex2RGB($hexStr, $returnAsString = false, $seperator = ',') {
		$hexStr = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr); // Gets a proper hex string

		$rgbArray = array();
		if (strlen($hexStr) == 6) { //If a proper hex code, convert using bitwise operation. No overhead... faster
			$colorVal = hexdec($hexStr);
			$rgbArray['red'] = 0xFF & ($colorVal >> 0x10);
			$rgbArray['green'] = 0xFF & ($colorVal >> 0x8);
			$rgbArray['blue'] = 0xFF & $colorVal;
		} elseif (strlen($hexStr) == 3) { //if shorthand notation, need some string manipulations
			$rgbArray['red'] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
			$rgbArray['green'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
			$rgbArray['blue'] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
		} else {
			return false; //Invalid hex color code
		}
		return $returnAsString ? implode($seperator, $rgbArray) : $rgbArray; // returns the rgb string or the associative array
	}
	
	
	static public function convertHTMLToRGB($htmlCode) {
		if($htmlCode[0] == '#')
		  $htmlCode = substr($htmlCode, 1);

		if (strlen($htmlCode) == 3)
		{
		  $htmlCode = $htmlCode[0] . $htmlCode[0] . $htmlCode[1] . $htmlCode[1] . $htmlCode[2] . $htmlCode[2];
		}

		$r = hexdec($htmlCode[0] . $htmlCode[1]);
		$g = hexdec($htmlCode[2] . $htmlCode[3]);
		$b = hexdec($htmlCode[4] . $htmlCode[5]);

		return $b + ($g << 0x8) + ($r << 0x10);
	}

	static public function convertRGBToHSL($r, $g, $b) {
		$r /= 255;
		$g /= 255;
		$b /= 255;

		$max = max($r, $g, $b);
		$min = min($r, $g, $b);
		$delta = $max - $min;

		$l = ($max + $min) / 2;
		$h = 0;
		$s = 0;

		if ($delta != 0) {
			$s = $delta / (1 - abs(2 * $l - 1));

			switch ($max) {
				case $r:
					$h = 60 * fmod((($g - $b) / $delta), 6);
					if ($h < 0) $h += 360;
					break;
				case $g:
					$h = 60 * (($b - $r) / $delta + 2);
					break;
				case $b:
					$h = 60 * (($r - $g) / $delta + 4);
					break;
			}
		}

		return [
			'h' => round($h),
			's' => round($s * 100),
			'l' => round($l * 100)
		];
	}

	public static function convertHslToRgb($h, $s, $l) {
		$s /= 100;
		$l /= 100;

		$c = (1 - abs(2 * $l - 1)) * $s;
		$x = $c * (1 - abs(fmod($h / 60, 2) - 1));
		$m = $l - $c / 2;

		if ($h < 60) {
			$r = $c;
			$g = $x;
			$b = 0;
		} elseif ($h < 120) {
			$r = $x;
			$g = $c;
			$b = 0;
		} elseif ($h < 180) {
			$r = 0;
			$g = $c;
			$b = $x;
		} elseif ($h < 240) {
			$r = 0;
			$g = $x;
			$b = $c;
		} elseif ($h < 300) {
			$r = $x;
			$g = 0;
			$b = $c;
		} else {
			$r = $c;
			$g = 0;
			$b = $x;
		}

		return [
			'r' => (int)round(($r + $m) * 255),
			'g' => (int)round(($g + $m) * 255),
			'b' => (int)round(($b + $m) * 255)
		];
	}

	/**
	 * @param string $sColor
	 * @param int $iFactor
	 * @return string
	 */
	static public function applyColorFactor($sColor, $iFactor)
	{
		if($iFactor < 100) {
			// Deckkraft anpassen
			$aRGB = self::convertHex2RGB($sColor);

			$iMul = (100 - $iFactor) / 100;

			foreach((array)$aRGB as $sKey => $iValue) {
				$aRGB[$sKey] = round($iValue + ((255 - $iValue) * $iMul));
			}

			$sColor = self::convertRGB2Hex($aRGB);
		}

		return $sColor;
	}

	public static function getYIQFromHex(string $hex)
	{
		$rgb = self::convertHex2RGB($hex);

		$yiq = (($rgb['red'] * 299) + ($rgb['green'] * 587) + ($rgb['blue'] * 114)) / 1000;

		return $yiq;
	}

	public static function getContrastColor(string $hex): string
	{
		return (self::getYIQFromHex($hex) >= 128) ? '#000000' : '#FFFFFF';
	}

	/**
	 * Kontrastfarbe aus einer Auswahl an Farben heraussuchen
	 * (mindestens 4.5 für Text, 3 für grafische Elemente)
	 * https://www.splitbrain.org/blog/2008-09/18-calculating_color_contrast_with_php
	 *
	 * @param string $hex
	 * @param array $colors
	 * @param float $ratio
	 * @return string
	 */
	public static function getContrastColorFromColorPalette(string $hex, array $colors, float $ratio = self::CONTRAST_RATIO_ELEMENT): string
	{
		foreach ($colors as $color) {
			//$brightness = self::calculateBrightnessDiff($hex, $color);
			$luminosity = self::calculateLuminosityContrast($hex, $color);

			//if ($brightness >= 125) {
			if ($luminosity >= $ratio) {
				return $color;
			}
		}

		return self::getContrastColor($hex);
	}

	public static function calculateBrightnessDiff(string $hex1, string $hex2)
	{
		$rgb1 = self::convertHex2RGB($hex1);
		$rgb2 = self::convertHex2RGB($hex2);

		$brightness1 = (299 * $rgb1['red'] + 587 * $rgb1['green'] + 114 * $rgb1['blue']) / 1000;
		$brightness2 = (299 * $rgb2['red'] + 587 * $rgb2['green'] + 114 * $rgb2['blue']) / 1000;

		return abs($brightness1 - $brightness2);
	}

	public static function calculateLuminosity(string $hex)
	{
		$rgb = self::convertHex2RGB($hex);

		/*$luminosity = 0.2126 * pow($rgb['red'] / 255, 2.2) +
			0.7152 * pow($rgb['green'] / 255, 2.2) +
			0.0722 * pow($rgb['blue'] / 255, 2.2);*/

		$r = $rgb['red'] / 255;
		$g = $rgb['green'] / 255;
		$b = $rgb['blue'] / 255;

		$r = ($r <= 0.03928) ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
		$g = ($g <= 0.03928) ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
		$b = ($b <= 0.03928) ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

		$luminosity = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;

		return $luminosity;
	}

	public static function calculateLuminosityContrast(string $hex1, string $hex2)
	{
		$luminosity1 = self::calculateLuminosity($hex1);
		$luminosity2 = self::calculateLuminosity($hex2);

		if ($luminosity1 > $luminosity2){
			return ($luminosity1 + 0.05) / ($luminosity2 + 0.05);
		}

		return ($luminosity2 + 0.05) / ($luminosity1 + 0.05);
	}

	public static function random(): string
	{
		$random = function () {
			return str_pad(dechex(mt_rand( 0, 255 )), 2, '0', STR_PAD_LEFT);
		};
		return '#'.$random().$random().$random();
	}

}