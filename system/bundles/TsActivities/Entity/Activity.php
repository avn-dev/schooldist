<?php

namespace TsActivities\Entity;

use FileManager\Entity\File;
use FileManager\Traits\FileManagerTrait;
use Illuminate\Database\Query\JoinClause;

/**
 * @property string $id
 * @property string $short
 * @property string $without_course
 * @property string $billing_period
 * @property string $availability
 * @property string $min_students
 * @property string $max_students
 * @property string $changed
 * @property string $created
 * @property string $active
 * @property string $creator_id
 * @property string $editor_id
 * @property string $valid_until
 * @property string $position
 * @property string $free_of_charge
 * @property string $show_for_free
 * @property array $ts_act_i18n
 * @property array $pdf_templates
 * @property string $frontend_icon_class
 *
 * @method static ActivityRepository getRepository()
 */
class Activity extends \Ext_Thebing_Basic {

    use FileManagerTrait;
	use \Core\Traits\WdBasic\TransientTrait;

    const APP_IMAGE_TAG = 'App-Image';

    const AVAILABILITY_ALWAYS = 'always_available';

	const AVAILABILITY_LIMITED = 'limited_availability';

	/**
	 * @var string
	 */
	protected $_sTable = 'ts_activities';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'ts_act';

	protected $_sPlaceholderClass = \TsActivities\Entity\Activity\ActivityEntityPlaceholders::class;

	protected $_aJoinTables = [
		'ts_act_i18n' => [
			'table' => 'ts_activities_i18n',
			'foreign_key_field' => ['language_iso', 'name', 'description', 'description_short'],
			'primary_key_field' => 'activity_id',
		],
		'pdf_templates' => [
			'table' => 'kolumbus_pdf_templates_services',
			'class' => 'Ext_Thebing_Pdf_Template',
			'primary_key_field' => 'service_id',
			'foreign_key_field' => 'template_id',
			'static_key_fields'	=> ['service_type' => 'activity'],
			'autoload' => false
		],
		'providers' => [
			'table' => 'ts_activities_to_activities_providers',
			'class' => '\TsActivities\Entity\Activity\Provider',
			'foreign_key_field' => 'provider_id',
			'primary_key_field' => 'activity_id',
			'on_delete' => 'no_action',
			'cloneable' => false
		]
	];

	protected $_aJoinedObjects = [
		'schools' => [
			'class' => '\TsActivities\Entity\Activity\ActivitySchool',
			'type' => 'child',
			'key' => 'activity_id',
			'query' => true
		],
		'validities' => [
			'class' => '\TsActivities\Entity\Activity\Validity',
			'type' => 'child',
			'key' => 'activity_id'
		]
	];

	protected $_aAttributes = [
		'cost_center' => [
			'type' => 'text'
		],
		'frontend_icon_class' => [
			'type' => 'text'
		]
	];
	
	/**
	 * @param string $sName
	 * @return mixed|string
	 * @throws \ErrorException
	 */
	public function __get($sName) {

		if(strpos($sName, 'name_') === 0) {
			$sLanguage = str_replace('name_', '', $sName);
			return $this->getName($sLanguage);
		}

		return parent::__get($sName);

	}
	
	/**
	 * @param string $sLanguage
	 * @return mixed|string
	 * @throws \Exception
	 */
	public function getName($sLanguage = '') {

		if($sLanguage == '') {
			$sLanguage = \Ext_TC_System::getInterfaceLanguage();
		}

		$sName = $this->getI18NName('ts_act_i18n', 'name', $sLanguage);

		return $sName;

	}

    /**
     * @param string $sLanguage
     * @return mixed|string
     * @throws \Exception
     */
    public function getDescription($sLanguage = ''): string {

        if(empty($sLanguage)) {
            $sLanguage = \Ext_TC_System::getInterfaceLanguage();
        }

        return $this->getI18NName('ts_act_i18n', 'description', $sLanguage);
    }

	public function getShortDescription($sLanguage = ''): string {

		if(empty($sLanguage)) {
			$sLanguage = \Ext_TC_System::getInterfaceLanguage();
		}

		return $this->getI18NName('ts_act_i18n', 'description_short', $sLanguage);
	}

	public function save($bLog = true) {

    	$aI18N = $this->ts_act_i18n;
    	foreach ($aI18N as $iKey => $aData) {
    		if (!empty($aData['description'])) {
				$oPurifier = new \Core\Service\HtmlPurifier(\Core\Service\HtmlPurifier::SET_FRONTEND);
				$aI18N[$iKey]['description'] = $oPurifier->purify($aData['description']);
			}
		}
		$this->ts_act_i18n = $aI18N;

		return parent::save($bLog);

	}

	public function getFileManagerEntityPath(): string {
    	// Abhängigkeit auf proxy.fidelo.com!
		return \Util::getCleanFilename('Ts\Activity');
	}

	public function validate($bThrowExceptions = false) {
		$validate = parent::validate($bThrowExceptions);

		if (
			$validate === true &&
			$this->active == 0 &&
			self::getRepository()->countUsage($this) > 0
		) {
			$validate = ['ACTIVITY_IN_USE'];
		}

		return $validate;

	}

	/**
	 * Returns all providers for this activity
	 * @param bool $asList
	 * @return \TsActivities\Entity\Activity\Provider[]
	 */
	public function getProviders(bool $asList = false): array {
		$providers = $this->getJoinTableObjects('providers');
		if ($asList) {
			$providers = collect($providers)->keyBy('id')->map(fn ($provider) => $provider->getName())->toArray();
		}
		return $providers;
	}

	/**
     * Liefert das Anzeigebild für die App
     *
     * @return File|null
     */
    public function getAppImage(): ?File {
        return $this->getFirstFile(Activity::APP_IMAGE_TAG);
    }

	public function isCalculatedWeekly(): bool {
	    return $this->billing_period === 'payment_per_week';
    }

    public function isCalculatedPerBlock(): bool {
        return $this->billing_period === 'payment_per_block';
    }

    public function isFreeOfCharge(): bool {
	    return (int)$this->free_of_charge === 1;
    }

    public function showWithoutPrice(): bool {
        return (int)$this->show_for_free === 1;
    }

    public function needsCourses(): bool {
        return (int)$this->without_course === 0;
    }

	/**
	 * @return \TsActivities\Entity\Activity\ActivitySchool[]
	 */
	public function getSchoolSettings(): array {
	    return $this->getJoinedObjectChilds('schools');
    }

    /**
	 * @return \TsActivities\Entity\Activity\Validity[]
	 */
	public function getValidities(): array {
		return $this->getJoinedObjectChilds('validities');
	}

    /**
     * Prüft ob die Aktivität für eine Buchung verfügbar ist (Schule, Kurse)
     *
     * @param \Ext_TS_Inquiry $inquiry
     * @return bool
     * @throws \Exception
     */
    public function isValidForInquiry(\Ext_TS_Inquiry $inquiry): bool {

        $school = $inquiry->getSchool();

		$courses = collect();
        collect($inquiry->getCourses())
            ->map(function($inquiryCourse) use ($courses) {
				$courses[$inquiryCourse->course_id] = $inquiryCourse->course_id;
			});

        $settings = $this->getSchoolSettings();

        foreach($settings as $setting) {
            if($setting->school_id == $school->getId()) {
                if(
					!$this->needsCourses() ||
					$courses->intersect($setting->courses)->isNotEmpty()
				) {
                    return true;
                }
            }
        }

        return false;
    }

	public static function getActivitiesForSelect($language = '') {

		if ($language == '') {
			$language = \Ext_Thebing_Util::getInterfaceLanguage();
		}

		$sql = " 
			SELECT
				`ts_ac`.`id`,
				`ts_act_i18n`.`name`,
				GROUP_CONCAT(cdb2.short ORDER BY cdb2.position SEPARATOR ', ') schools
			FROM
			    `ts_activities` `ts_ac` INNER JOIN
				ts_activities_schools ts_acts ON
					ts_acts.activity_id = ts_ac.id INNER JOIN
			    customer_db_2 cdb2 ON
			        cdb2.id = ts_acts.school_id LEFT JOIN
			    `ts_activities_i18n` `ts_act_i18n` ON
					`ts_act_i18n`.`activity_id` = `ts_ac`.`id` AND
					`ts_act_i18n`.`language_iso` = :language
			WHERE
				`ts_ac`.`active` = 1 AND (
					`ts_ac`.`valid_until` = '0000-00-00' OR
					`ts_ac`.`valid_until` >= CURDATE()
				)
			GROUP BY
			    ts_ac.id
		";

		return array_map(function (array $row) {
			// Name konkatenieren, da auch beim Kontext einer Schule immer alle Aktivitäten angezeigt werden
			return $row['name'].' ('.$row['schools'].')';
		}, (array)\DB::getQueryRowsAssoc($sql, compact('language')));

	}

}
