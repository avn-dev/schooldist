<?php

namespace TsRegistrationForm\Dto;

class FrontendCourse extends FrontendService
{
	protected array $hidden = ['class'];

	public string $key;

	public string $type;

	public string $label;

	public array $description_list = [];

	public string $description;

	public ?string $lessons_unit;

	public array $levels;

	public bool $show_level = false;

	public bool $show_duration = true;

	/**
	 * @var int[]|float[]
	 */
	public array $lessons = [];

	public bool $dates_level_dependency = false;

	public bool $dates_language_dependency = false;

	public array $accommodations;

	public array $age;

	public array $blocks;

	public array $dependencies;

	/**
	 *  Generell hat ein Kurs eine Kategorie, kann durch die Kursstruktur aber eine Unterkategorie haben
	 *  (zwei Items dann). Der Wert kann auch leer sein, wenn Kurs in Kursstruktur nicht zugewiesen ist
	 *
	 * @var int[]
	 */
	public array $categories = [];

	/**
	 * @var int[]
	 */
	public array $languages;

	public array $additional_services = [];

	public array $programs;

	public string $dates_key;

	public array $additional = [];

	public array $class = [];

	public bool $bookable_only_in_full = false;

	public function isClass(): bool
	{
		return !empty($this->class);
	}
}
