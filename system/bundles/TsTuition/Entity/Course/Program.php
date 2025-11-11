<?php

namespace TsTuition\Entity\Course;

use TsTuition\Entity\Course\Program\Service;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * TODO - evtl. from und until als Spalte anlegen und in save() setzen damit nicht immer über die Services gegangen werden muss
 *
 * @property int $course_id
 * @method static ProgramRepository getRepository()
 */
class Program extends \Ext_Thebing_Basic {

	protected $_sTableAlias = 'ts_tcp';

	protected $_sTable = 'ts_tuition_courses_programs';

	protected $_aJoinedObjects = [
		'services' => [
			'class' => Program\Service::class,
			'key' => 'program_id',
			'type' => 'child',
			'orderby' => 'from',
			'orderby_set' => false,
			'check_active' => true,
			'on_delete' => 'cascade'
		]
	];

	public function getName() {
		return $this->getNameFormatted();
	}

	public function getNameFormatted(\Ext_Thebing_Gui2_Format_Date $format = null) {

		if ($format === null) {
			$format = new \Ext_Thebing_Gui2_Format_Date();
		}

		$from = $this->getFrom();
		$until = $this->getUntil();

		if (
			$from !== null &&
			$until !== null
		) {
			return sprintf('%s - %s', $format->formatByValue($from->toDateString()), $format->formatByValue($until));
		}

		// Das sollte nie ein Kunde zu sehen bekommen
		return 'ID - '.$this->getId();
	}

	/**
	 * Start des Programmes
	 *
	 * @return Carbon|null
	 */
	public function getFrom(): ?Carbon {
		$services = $this->getServices();

		if($services->isNotEmpty()) {
			// sind durch $_aJoinedObjects bereits sortiert
			return $services->first()->getFrom();
		}

		return null;
	}

	/**
	 * Ende des Programmes
	 *
	 * @return Carbon|null
	 */
	public function getUntil(): ?Carbon {
		$services = $this->getServices();

		if($services->isNotEmpty()) {
			return $services->sortByDesc(function (Service $service) {
					return $service->getUntil();
				})
				->first()
				->getUntil();
		}

		return null;

	}

	/**
	 * Dauer des Programmes in Wochen
	 *
	 * @return int
	 */
	public function getWeeks(): int {

		if(
			!is_null($from = $this->getFrom()) &&
			!is_null($until = $this->getUntil())
		) {
			return (int)ceil($until->diffInDays($from) / 7);
		}

		return 0;
	}

	/**
	 * Liefert alle Leistungen des Programmes
	 *
	 * @param string|null $type
	 * @return Collection<Service>
	 */
	public function getServices(string $type = null): Collection {
		$services = collect($this->getJoinedObjectChilds('services', true));

		if($type !== null) {
			$services = $services->filter(fn (Service $service) => $service->getType() === $type);
		}

		return $services;
	}

	/**
	 * @return Service
	 */
	public function getFirstService(string $type = null): Program\Service {
		return $this->getServices($type)->first();
	}

	/**
	 * Liefert alle Kurse die zu diesem Programm zugewiesen wurden
	 *
	 * @return \Ext_Thebing_Tuition_Course[]
	 */
	public function getCourses(): Collection {
		return $this->getServices()
			->filter(fn (Service $service) => $service->isCourse())
			->map(fn(Service $service) => $service->getService());
	}

	/**
	 * @return bool
	 */
	public function hasJourneyCourses(): bool {
		return self::getRepository()->hasJourneyCourses((int)$this->getId());
	}

	public function validate($bThrowExceptions = false) {

		$mReturn = parent::validate($bThrowExceptions);

		if($mReturn === true) {
			$mReturn = [];
		}

		if(!$this->isActive() && $this->hasJourneyCourses()) {
			$mReturn[] = 'JOURNEY_COURSES_FOUND';
		}

		if(empty($mReturn)) {
			$mReturn = true;
		}

		return $mReturn;

	}

	public function manipulateSqlParts(&$aSqlParts, $sView = null) {

		$aSqlParts['select'] .= "
			, (
				SELECT `ts_tcps`.`from`
				FROM `ts_tuition_courses_programs_services`	`ts_tcps`
				WHERE `ts_tcps`.`program_id` = `ts_tcp`.`id` 
				ORDER BY `ts_tcps`.`from` ASC
				LIMIT 1
			) `start_date`
			, (
				SELECT `ts_tcps`.`until`
				FROM `ts_tuition_courses_programs_services`	`ts_tcps`
				WHERE `ts_tcps`.`program_id` = `ts_tcp`.`id`
				ORDER BY `ts_tcps`.`until` DESC
				LIMIT 1
			) `end_date`
		";

		$aSqlParts['groupby'] = "`ts_tcp`.`id`";
	}

}
