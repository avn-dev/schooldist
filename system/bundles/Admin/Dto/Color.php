<?php

namespace Admin\Dto;

use Core\Helper\Color as ColorHelper;

class Color
{
	const HEX = 'hex';
	const RGB = 'rgb';
	const HSL = 'hsl';

	private ?array $rgb = null;

	public function __construct($rgb, string $type = self::RGB)
	{
		if ($type === self::HEX) {
			$rgb = ColorHelper::convertHex2RGB($rgb);
		} else if ($type === self::HSL) {
			$hsl = array_values($rgb);
			$rgb = ColorHelper::convertHslToRgb($hsl[0], $hsl[1], $hsl[2]);
		}

		if (!is_array($rgb)) {
			throw new \InvalidArgumentException("Invalid color parameters");
		}

		$this->rgb = array_values($rgb);
	}

	public function getRGB(): array
	{
		return $this->rgb;
	}

	public function getHSL(): array
	{
		return array_values(ColorHelper::convertRGBToHSL($this->rgb[0], $this->rgb[1], $this->rgb[2]));
	}

	public function getHex(): string
	{
		return ColorHelper::convertRGB2Hex($this->rgb);
	}

	public static function fromHex(string $hex): static
	{
		return new static($hex, self::HEX);
	}

	public static function fromRgb(int $r, int $g, int $b): static
	{
		return new static([$r, $g, $b], self::RGB);
	}

	public static function fromHsl(int $h, int $s, int $l): static
	{
		return new static([$h, $s, $l], self::HSL);
	}
}