<?php

namespace TsAccommodation\Entity\Cleaning;

use TsAccommodation\Entity\Cleaning\Type\Cycle;

class Type extends \Ext_Thebing_Basic {

	/**
	 * @var string
	 */
	protected $_sTable = 'ts_accommodation_cleaning_types';

	/**
	 * @var string
	 */
	protected $_sTableAlias	= 'ts_act';

	/**
	 * @var array
	 */
	protected $_aFormat = array(
		'name' => array(
			'required'	=> true,
		),
        'short' => array(
            'required'	=> true,
        ),
	);

    protected $_aJoinTables = [
        'schools' => [
            'table' => 'ts_accommodation_cleaning_types_to_schools',
            'class' => \Ext_Thebing_School::class,
            'foreign_key_field' => 'school_id',
            'primary_key_field' => 'type_id',
        ],
        'accommodation_categories' => [
            'table' => 'ts_accommodation_cleaning_types_to_accommodation_categories',
            'class' => \Ext_Thebing_Accommodation_Category::class,
            'foreign_key_field' => 'category_id',
            'primary_key_field' => 'type_id',
        ],
        'rooms' => [
            'table' => 'ts_accommodation_cleaning_types_to_rooms',
            'class' => \Ext_Thebing_Accommodation_Room::class,
            'foreign_key_field' => 'room_id',
            'primary_key_field' => 'type_id',
        ],
    ];

    protected $_aJoinedObjects = [
        'cycles' => [
            'class' => Cycle::class,
            'type' => 'child',
            'key' => 'type_id',
            'check_active' => true,
            'on_delete' => 'cascade'
        ]
    ];

    public function getShortName(): string {
        return $this->short;
    }

    public function getSchools() {
        return $this->getJoinTableObjects('schools');
    }

    /**
     * @return array|\Ext_Thebing_Accommodation_Category[]
     */
    public function getAccommodationCategories() {
        return $this->getJoinTableObjects('accommodation_categories');
    }

    public function getRooms() {
        return $this->getJoinTableObjects('rooms');
    }

    public function getCycles(): array {
        return $this->getJoinedObjectChilds('cycles');
    }

    public function getRoomCleaningCycles(): array {
        return array_filter($this->getCycles(), function (Cycle $cycle) {
            return $cycle->isOnceRoomClean();
        });
    }

    public function getBedCleaningCycles(): array {
        return array_filter($this->getCycles(), function (Cycle $cycle) {
            return !$cycle->isOnceRoomClean();
        });
    }

}
