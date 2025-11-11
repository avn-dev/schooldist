<?php

namespace TsActivities\Entity;

use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

class ActivityRepository extends \WDBasic_Repository {

	private function createQuery(\Ext_Thebing_School $school): \Core\Database\WDBasic\Builder {

		return Activity::query()
			->select(['ts_act.*'])
			->join('ts_activities_schools as ts_acts', 'ts_acts.activity_id', 'ts_act.id')
			->where('ts_acts.school_id', $school->id)
			->groupBy('ts_act.id')
			->orderBy('ts_act.position')
			->onlyValid();

	}

	public function getActivitiesBySchool(\Ext_Thebing_School $school): Collection {

		return $this->createQuery($school)->get();

	}

	public function getSelectOptions(\Ext_Thebing_School $school): array {

		return $this->createQuery($school)
			->select(['ts_act.id', 'ts_act_i18n.name'])
			->leftJoin('ts_activities_i18n as ts_act_i18n', function (JoinClause $join) use ($school) {
				$join->on('ts_act_i18n.activity_id', 'ts_act.id');
				$join->where('language_iso', $school->getInterfaceLanguage());
			})
			->toBase()
			->get()
			->mapWithKeys(fn(array $activity) => [$activity['id'] => $activity['name']])
			->toArray();

	}

	/**
	 * Komisches Objekt für Student Record, da quasi gleiche Daten wieder über diverse Ebenen kommen
	 *
	 * @param \Ext_Thebing_School $school
	 * @return Collection
	 */
	public function getConfigMap(\Ext_Thebing_School $school): Collection {

		$query = $this->createQuery($school);

		return $query
			->select(['ts_act.id', 'billing_period', $query->raw('GROUP_CONCAT(ts_actstc.course_id) as course_ids')])
			->leftJoin('ts_activities_schools_to_courses as ts_actstc', function (JoinClause $join) {
				$join->on('ts_actstc.activity_school_id', 'ts_acts.id');
				$join->where('ts_act.without_course', 0);
			})
			->toBase()
			->get()
			->mapWithKeys(function (array $activity) {
				if ($activity['course_ids']) {
					$activity['course_ids'] = explode(',', $activity['course_ids']);
				}
				return [$activity['id'] => $activity];
			});

	}

	public function countUsage(Activity $activity): int {

		$sql = "
			SELECT 
				COUNT(*) 
			FROM (
				SELECT
					`id`
				FROM
					`ts_inquiries_journeys_activities`
				WHERE
					`activity_id` = :id
				UNION ALL
				SELECT
					`ts_actb`.`id`
				FROM
					`ts_activities_blocks_to_activities` `ts_actbta` INNER JOIN
					`ts_activities_blocks` `ts_actb` ON
						`ts_actb`.`id` = `ts_actbta`.`block_id` AND
						`ts_actb`.`active` = 1
				WHERE
					`ts_actbta`.`activity_id` = :id
			) `c`
		";

		return (int)\DB::getQueryOne($sql, ['id' => $activity->id]);

	}

}