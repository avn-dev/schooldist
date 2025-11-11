<?php

namespace TsAccommodation\Dto;

class AccommodationCombination {

	/**
	 * @var \Ext_Thebing_Accommodation_Category
	 */
	public $category;

	/**
	 * @var \Ext_Thebing_Accommodation_Roomtype
	 */
	public $roomtype;

	/**
	 * @var \Ext_Thebing_Accommodation_Meal
	 */
	public $board;

	public function __construct(\Ext_Thebing_Accommodation_Category $category, \Ext_Thebing_Accommodation_Roomtype $roomtype, \Ext_Thebing_Accommodation_Meal $board) {

		$this->category = $category;
		$this->roomtype = $roomtype;
		$this->board = $board;

	}

	public function buildKey(): string {

		return sprintf('%s_%s_%s', $this->category->id, $this->roomtype->id, $this->board->id);

	}

	public function buildLabel(string $language): string {

		return sprintf('%s / %s / %s', $this->category->getShortName($language), $this->roomtype->getShortName($language), $this->board->getShortName($language));

	}

}
