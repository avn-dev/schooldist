<?php

namespace Ts\Entity\AccommodationProvider\Payment;

/**
 * @property int $id
 * @property int $changed Timestamp
 * @property int $created Timestamp
 * @property int $active
 * @property int $editor_id Bearbeiter
 * @property int $creator_id Ersteller
 * @property string $name
 * @property \Ts\Entity\AccommodationProvider\Payment\Category\Period[] $periods
 * @method static \Ts\Entity\AccommodationProvider\Payment\CategoryRepository getRepository()
 */
class Category extends \Ext_Thebing_Basic {

	/**
	 * @var string
	 */
	protected $_sTable = 'ts_accommodation_providers_payment_categories';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'ts_appc';

	/**
	 * @var string
	 */
	protected $_sEditorIdColumn = 'editor_id';

	/**
	 * Der Flag muss auf true gesetzt werden, damit die Update-Spalten sich verändern
	 * wenn Einstellungen geändert werden.
	 *
	 * @var bool
	 */
	protected $bForceUpdateUser = true;

	/**
	 * @var mixed[]
	 */
	protected $_aJoinTables = [
		'schools' => [
			'table' => 'ts_accommodation_provider_payment_categories_schools',
			'foreign_key_field' => 'school_id',
			'primary_key_field' => 'accommodation_provider_payment_category_id'
		],
		'accommodations_validities' => [
			'table' => 'ts_accommodation_providers_payment_categories_validity',
			'foreign_key_field' => 'provider_id',
			'primary_key_field' => 'category_id',
			'check_active' => true,
			'delete_check' => true,
			'autoload' => false
		]
	];

	/**
	 * @var mixed[]
	 */
	protected $_aJoinedObjects = [
		'periods' => [
			'class'	=> '\Ts\Entity\AccommodationProvider\Payment\Category\Period',
			'key' => 'category_id',
			'check_active' => true,
			'type' => 'child',
		],
	];

	/**
	 * {@inheritdoc}
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		parent::manipulateSqlParts($aSqlParts, $sView);

		$aSqlParts['select'] .= ",
			GROUP_CONCAT(DISTINCT `ts_appcp`.`display` SEPARATOR '{||}') `period_displays`,
			GROUP_CONCAT(DISTINCT `kil`.`name` ORDER BY `kil`.`name` SEPARATOR '<br />') `inbox_names`,
			GROUP_CONCAT(DISTINCT `schools`.`school_id`) `schools`
		";

		$aSqlParts['from'] .= " LEFT JOIN
			`ts_accommodation_providers_payment_categories_periods` `ts_appcp` ON
				`ts_appcp`.`category_id` = `ts_appc`.`id` AND
				`ts_appcp`.`active` = 1 LEFT JOIN
			`ts_accommodation_providers_payment_categories_periods_inboxes` `ts_appcpi` ON
				`ts_appcpi`.`period_id` = `ts_appcp`.`id` LEFT JOIN
			`kolumbus_inboxlist` `kil` ON
				`kil`.`id` = `ts_appcpi`.`inbox_id` AND
				`kil`.`active` = 1 LEFT OUTER JOIN
			`ts_accommodation_provider_payment_categories_schools` `filter_schools` ON
				`filter_schools`.`accommodation_provider_payment_category_id` = `ts_appc`.`id`
		";

	}

}
