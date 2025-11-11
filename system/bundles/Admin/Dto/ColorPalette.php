<?php

namespace Admin\Dto;

use Admin\Dto\Color as Color;
use Admin\Dto\ColorPalette\Shade;
use Core\Helper\Color as ColorHelper;
use Illuminate\Support\Arr;

class ColorPalette
{
	public function __construct(
		private int $base,
		private array $palette
	) {}

	public function getBase(): int
	{
		return $this->base;
	}

	public function getBaseColor(): Color
	{
		return $this->palette[$this->base]->getColor();
	}

	/**
	 * @return Shade[]
	 */
	public function getPalette(): array
	{
		return $this->palette;
	}

	public function getShade(int $shade = null): ?Shade
	{
		if ($shade === null) {
			$shade = $this->base;
		}
		return $this->palette[$shade]?? null;
	}

	public function getContrastShade(Shade|string $hex, float $ratio = ColorHelper::CONTRAST_RATIO_ELEMENT, $preferBaseColor = false): Shade
	{
		if ($hex instanceof Shade) {
			$hex = $hex->getColor()->getHex();
		}

		$palette = $this->getHexCodes();

		$baseIndex = array_search($this->base, array_keys($palette));

		if ($baseIndex) {
			// Erzeugt einfach bessere Ergebnis. Von der Basisfarbe zuerst die nachfolgenden Farben prüfen und dann die vorherigen
			$palette = [
				...array_slice($palette, $baseIndex + 1, count($palette)),
				$palette[$this->base],
				...array_reverse(array_slice($palette, 0, $baseIndex))
			];
		}

		if ($preferBaseColor) {
			// Base Color nach vorne bringen damit diese zuerst geprüft wird
			$palette = Arr::prepend($palette, $this->getBaseColor()->getHex());
		}

		$contrast = Color::fromHex(ColorHelper::getContrastColorFromColorPalette($hex, $palette, $ratio));

		if (strtoupper($contrast->getHex()) === '#FFFFFF') {
			$contrastShade = $this->getShade(Arr::first(array_keys($this->palette)));
		} else if (strtoupper($contrast->getHex()) === '#000000') {
			$contrastShade = $this->getShade(Arr::last(array_keys($this->palette)));
		} else {
			$contrastShade = Arr::first($this->palette, fn (Shade $shade) => $shade->getColor()->getHex() === $contrast->getHex());
		}

		return $contrastShade;
	}

	private function getHexCodes(): array
	{
		return array_map(fn (Shade $shade) => $shade->getColor()->getHex(), $this->palette);
	}

}