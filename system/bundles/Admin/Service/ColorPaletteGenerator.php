<?php

namespace Admin\Service;

use Admin\Dto\Color;
use Admin\Dto\ColorPalette\Shade;
use Core\Helper\Color as ColorHelper;
use Illuminate\Support\Arr;

class ColorPaletteGenerator
{
	const TAILWIND_SHADES = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];

	private $thresholdLightest = 0.95;
	private $thresholdDarkest = 0.1;

	public function __construct(
		private Color $color,
		private ?int $base = null,
		private array $shades = self::TAILWIND_SHADES,
	) {
		if (empty($base)) {
			$this->base = $this->findBase($this->shades);
		}
	}

	public function getBase(): int
	{
		return $this->base;
	}

	public function generate(): \Admin\Dto\ColorPalette
	{
		$baseHsl = $this->color->getHsl();

		$lightnessLevels = $this->buildLightnessLevels();
		$palette = [];

		foreach ($lightnessLevels as $shade => $lightness) {
			$palette[$shade] = new Shade(
				$shade,
				Color::fromHsl($baseHsl[0], $baseHsl[1], $lightness)
			);
		}

		return new \Admin\Dto\ColorPalette($this->base, $palette);
	}

	private function findBase(array $shades): int
	{
		$lightness = floatval(1 - round(ColorHelper::getLightness('#'.$this->color->getHex()), 1));

		if ($lightness > 0 && $lightness < 1) {
			[, $decimal] = sscanf((string)$lightness, '%d.%d');
			$base = $shades[(int)$decimal];
		} else if ((int)$lightness === 1) {
			$base = Arr::last($shades);
		} else {
			$base = Arr::first($shades);
		}

		return $base;
	}

	private function buildLightnessLevels(): array
	{
		$baseHsl = $this->color->getHsl();

		$steps = ($this->thresholdLightest - $this->thresholdDarkest) / (count($this->shades) - 1);
		$levels = [];

		foreach (array_reverse($this->shades) as $index => $shade) {
			$levels[$shade] = round(($this->thresholdDarkest + ($steps * $index)) * 100, 2);
		}

		$levels[$this->base] = $baseHsl[2];

		ksort($levels);

		return $levels;
	}
}