<?php

namespace TsCompany\Entity;

/**
 * @property int $active
 * @property string $name
 * @property string $short_name
 * @property string $description
 * @property int $parent_id
 */
class Industry extends \Ext_Thebing_Basic {

	/**
	 * Ausgabeformat des Namen zusammen mit Unterbranche
	 */
	const OUTPUT_NAME_FORMAT = '%s - %s';

	protected $_sTable = 'ts_companies_industries';

	protected $_sTableAlias = 'ts_ci';

	protected $_sPlaceholderClass = \TsCompany\Service\Placeholder\Industry::class;

	protected $_aJoinTables = [
		'companies'	=> [
			'table'				=> 'ts_companies_to_industries',
			'foreign_key_field'	=> 'company_id',
			'primary_key_field'	=> 'industry_id',
			'autoload' => false
		],
	];

	protected $_aJoinedObjects = [
		'sub_industries' => [
			'class' => Industry::class,
			'type' => 'child',
			'key' => 'parent_id',
			'check_active' => true,
			'on_delete' => 'cascade'
		],
		'parent_industry' => [
			'class'	=> Industry::class,
			'key' => 'parent_id',
			'type' => 'parent',
		]
	];

	/**
	 * Prüft ob es sich um eine Unterbranche handelt
	 *
	 * @return bool
	 */
	public function isSubIndustry(): bool {
		return $this->parent_id > 0;
	}

	/**
	 * Name (optional mit Name der Eltern-Branche)
	 *
	 * @param bool $withParent
	 * @return string
	 */
	public function getName(bool $withParent = true): string {

		if ($withParent && $this->isSubIndustry()) {
			return sprintf(self::OUTPUT_NAME_FORMAT, $this->getParentIndustry()?->getName(false), $this->name);
		}

		return $this->name;
	}

	/**
	 * Abkürzung (optional mit Abkürzung der Eltern-Branche)
	 *
	 * @param bool $withParent
	 * @return string
	 */
	public function getShortName(bool $withParent = true): string {

		if ($withParent && $this->isSubIndustry()) {
			return sprintf(self::OUTPUT_NAME_FORMAT, $this->getParentIndustry()?->getShortName(false), $this->short_name);
		}

		return $this->short_name;
	}

	/**
	 * Branche ist zu einer Firma zugewiesen
	 *
	 * @return bool
	 */
	public function isUsedByCompany(): bool {

		$subIndustries = $this->getSubIndustries();

		if(empty($subIndustries)) {
			return !empty($this->companies);
		}

		$subIndustriesWithCompanies = array_filter($subIndustries, function(Industry $subIndustry) {
			return $subIndustry->isUsedByCompany();
		});

		return !empty($subIndustriesWithCompanies);
	}

	/**
	 * Liefert die Elternbranche (falls vorhanden)
	 *
	 * @return Industry|null
	 */
	public function getParentIndustry(): ?Industry {

		if (!$this->isSubIndustry()) {
			return null;
		}

		return $this->getJoinedObject('parent_industry');
	}

	/**
	 * Liefert die Unterbranchen (falls vorhanden)
	 * @return array
	 */
	public function getSubIndustries(): array {
		return $this->getJoinedObjectChilds('sub_industries', true);
	}

	/**
	 * Select-Options der Branchen.
	 *
	 * @param false $short
	 * @return array
	 */
	public static function getSelectOptions($short = false): array {

		/** @var \TsCompany\Entity\Industry[] $list */
		$list = collect(self::getRepository()->findAll());

		$return = [];
		foreach($list as $industry) {

			if ($industry->isSubIndustry()) {
				continue;
			}

			$subIndustries = $list->filter(function(Industry $subIndustry) use($industry) {
				return ($subIndustry->parent_id == $industry->getId());
			});

			$getName = function (Industry $industry, $short) {
				if($short) return $industry->getShortName(false);
				return $industry->getName(false);
			};

			if($subIndustries->isEmpty()) {
				$return[$industry->getId()] = $getName($industry, $short);
			} else {
				foreach($subIndustries as $subIndustry) {
					$return[$subIndustry->getId()] = sprintf(self::OUTPUT_NAME_FORMAT, $getName($industry, $short), $getName($subIndustry, $short));
				}
			}
		}

		return $return;
	}

}
