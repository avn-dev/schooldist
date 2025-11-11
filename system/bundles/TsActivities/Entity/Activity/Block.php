<?php

namespace TsActivities\Entity\Activity;

use TsActivities\Entity\Activity;

/**
 * @property string $id
 * @property string $changed
 * @property string $created
 * @property string $active
 * @property string $school_id Schule, in welcher der Block angelegt oder editiert wurde
 * @property string $editor_id
 * @property string $creator_id
 * @property string $name
 * @property string $weeks
 * @property string $start_week
 * @property string $repeat_weeks
 * @property string $frontend_release
 * @property int|null $frontend_min_bookable_days_ahead
 * @property int|null $frontend_min_visible_days_ahead
 * @property string $advertise
 * @property string[] $activities
 * @property array $accompanying_persons
 * @property string $provider_id
 * @method static BlockRepository getRepository()
 */
class Block extends \Ext_Thebing_Basic {

	const FRONTEND_VISIBLE = 'visible';
	const FRONTEND_BOOKABLE = 'bookable';

	protected $_sTable = 'ts_activities_blocks';

	protected $_sEditorIdColumn = 'editor_id';

	protected $_sPlaceholderClass = \TsActivities\Service\Placeholder\Block::class;

	protected $_sTableAlias = 'ts_actb';

	protected $_aFormat = [
		'school_id' => [
			'validate' => 'INT_POSITIVE'
		],
		'weeks' => [
			'validate' => 'INT_POSITIVE'
		],
		'repeat_weeks' => [
			'validate' => 'INT_POSITIVE'
		]
	];
	/**
	 * @var array
	 */
	protected $_aJoinedObjects = [
		'days' => [
			'class' => Activity\BlockDay::class,
			'key' => 'block_id',
			'check_active' => true,
			'type' => 'child',
			'on_delete' => 'cascade'
		],
		'travellers' => [
			'class' => Activity\BlockTraveller::class,
			'key' => 'block_id',
			'type' => 'child',
			'on_delete' => 'cascade'
		],
		'provider' => [
			'class' => Activity\Provider::class,
			'key' => 'provider_id',
			'type' => 'parent'
		],
	];
	/**
	 * @var array
	 */
	protected $_aJoinTables = [
		'activities' => [
			'table' => 'ts_activities_blocks_to_activities',
            'class' => Activity::class,
			'primary_key_field' => 'block_id',
			'foreign_key_field' => 'activity_id',
			'on_delete' => 'delete'
		],
	];

	protected $_aAttributes = [
		'frontend_min_visible_days_ahead' => [
			'type' => 'int'
		]
	];

	public function getName() {
		return $this->name;
	}

	/**
	 * @return mixed
	 */
	public function getStartWeek() {
		return $this->start_week;
	}

    /**
     * @return BlockDay[]
     */
	public function getDays() {
        return $this->getJoinedObjectChilds('days', true);
    }

	/**
	 * Returns the provider for the activities of this block
	 * @return ?Activity\Provider
	 */
	public function getProvider(): ?Activity\Provider {
		return $this->getJoinedObject('provider');
	}

//	/**
//	 * @return string
//	 */
//	public function getWeekDay() {
//		$sWeekday = \Ext_Thebing_Util::getDays('%A', reset($this->getJoinedObjectChilds('days')));
//		return \L10N::t($sWeekday, \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH);
//	}
//
//	/**
//	 * @return string
//	 * @throws \Exception
//	 */
//	public function getDuration() {
//		$aDays = reset($this->getJoinedObjectChilds('days'));
//		$dStartTime = new \DateTime($aDays->start_time);
//		$dEndTime = new \DateTime($aDays->end_time);
//		$dDuration = $dStartTime->diff($dEndTime);
//		return $dDuration->format("%H:%I:%S");
//	}
//
//	/**
//	 * @return mixed
//	 */
//	public function getDescription() {
//		$aDays = $this->getJoinedObjectChilds('days');
//		$sComment = $aDays->comment;
//		return $sComment;
//	}
//
//	/**
//	 * @return string
//	 * @throws \Exception
//	 */
//	public function getBlockDate() {
//		$dStartDate = new DateTime($this->start_date);
//		$aDays = reset($this->getJoinedObjectChilds('days'));
//		$iWeekday = ($aDays->weekday)-1;
//		$dStartDate->modify('+'.$iWeekday.'day');
//		$sStartDate = $dStartDate->format('Y-m-d');
//		return $sStartDate;
//	}
//
//	/**
//	 * @return mixed
//	 */
//	public function getStartTime() {
//		$aDays = reset($this->getJoinedObjectChilds('days'));
//		return $aDays->start_time;
//	}
//
//	/**
//	 * @return mixed
//	 */
//	public function getEndTime() {
//		$aDays = reset($this->getJoinedObjectChilds('days'));
//		return $aDays->end_time;
//	}

	/**
	 * @param bool $bThrowExceptions
	 * @return array|bool
	 */
	public function validate($bThrowExceptions = false) {
		$mErrors = parent::validate($bThrowExceptions);

		if($mErrors === true) {
			$mErrors = [];

			if($this->weeks < $this->repeat_weeks) {
				$mErrors['weeks'] = 'WEEKS_BIGGER_THAN_REPEAT_WEEKS';
			}

			// Prüfe die Tage-Voraus-Einstellungen
			if (
				$this->frontend_min_bookable_days_ahead !== null &&
				$this->frontend_min_visible_days_ahead !== null &&
				$this->frontend_min_bookable_days_ahead >= $this->frontend_min_visible_days_ahead
			) {
				$mErrors['frontend_min_bookable_days_ahead'] = 'VISIBLE_DAYS_MUST_BE_GREATER';
			}

			if(empty($mErrors)) {
				return true;
			}
		}

		return $mErrors;
	}

    /**
     * Liefert alle zugewiesenen Aktivitäten
     *
     * @return Activity[]
     */
	public function getActivities() {
	    return $this->getJoinTableObjects('activities');
    }

    /**
     * Liefert die erste Aktivität des Blocks
     *
     * @return Activity
     */
    public function getFirstActivity() {
        $activities = $this->getActivities();
        return reset($activities);
    }

    /**
     * Prüft ob der Block für eine Buchung verfügbar ist (Schule, Kurse)
     *
     * @todo caching
     * @param \Ext_TS_Inquiry $inquiry
     * @return bool
     */
	public function isAvailableForInquiry(\Ext_TS_Inquiry $inquiry): bool {

	    $activities = $this->getActivities();

	    foreach($activities as $activity) {
	        if(!$activity->isValidForInquiry($inquiry)) {
	            return false;
            }
        }

	    return true;
    }

    /**
     * Prüft ob der Block noch freie Plätze in einer Woche hat
     *
     * @param Activity $activity
     * @param \DateTime $week
     * @return bool
     */
    public function hasFreeSeats(Activity $activity, \DateTime $week): bool {

        $freeSeats = $this->getFreeSeats($activity, $week);

        return (
            $freeSeats === null ||
            $freeSeats > 0
        );
    }

    /**
     * Liefert die Anzahl der verfügbaren Plätze für eine Woche
	 *
     * @param Activity $activity
     * @param \DateTime $week
     * @return int|string
     */
    public function getFreeSeats(Activity $activity, \DateTime $week): ?int {

        if (empty($activity->max_students)) {
            return null;
        }

        // die Zuweisungen der Schüler werden immer mit Montag abgespeichert
        if((int)$week->format('N') !== 1){
            $week->modify('last monday');
        }

        $students = BlockTraveller::getRepository()->getAllocatedStudents($this->getId(), $week);
        $free = (int)$activity->max_students - count($students);

        return $free;
    }

//    /**
//     * Liefert die min. und max. Anzahl von Schülern für diesen Block
//     * -> "*" bedeutet unendlich viele Plätze
//     *
//     * @return int[]
//     */
	/*public function getMinAndMaxStudents() {

	    $activities = $this->getActivities();
        $minmax = ['min' => 0, 'max' => 0];

	    foreach($activities as $activity) {
	        if($activity->min_students > $minmax['min']) {
                $minmax['min'] = (int) $activity->min_students;
            }
            if(
                $activity->max_students > 0 &&
                (
                    $minmax['max'] === 0 ||
                    $activity->max_students < $minmax['max']
                )

            ) {
                $minmax['max'] = (int) $activity->max_students;
            }
        }

	    // Bei max = 0 sind unendlich viele Plätze frei
	    if($minmax['max'] === 0) $minmax['max'] = '*';

	    return $minmax;
    }*/

	public function getStartDate($assignmentWeek = false) {

		$earliestDay = $this->getEarliestDayAsNumber()-1;

		if (!$assignmentWeek) {
			$week = $this->start_week;
		} else {
			$week = $assignmentWeek;
		}

		return (new \DateTime($week))->add(new \DateInterval('P' . $earliestDay . 'D'));
	}

	public function getEarliestDayAsNumber() {
		$days = $this->getDays();

		$iteration = 0;
		foreach ($days as $day) {
			$currentDay = $day->day;
			if (
				$lastDay > $currentDay ||
				$iteration === 0
			) {
				$earliestDay = $currentDay;
			}
			$lastDay = $day->day;
			$iteration++;
		}

		return $earliestDay;
	}

}