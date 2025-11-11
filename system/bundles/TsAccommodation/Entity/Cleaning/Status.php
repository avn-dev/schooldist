<?php

namespace TsAccommodation\Entity\Cleaning;

/**
 * @property string $id
 * @property string $changed
 * @property string $created
 * @property string $creator_id
 * @property string $editor_id
 * @property string $active
 * @property string $date
 * @property string $room_id
 * @property string $bed
 * @property string $type_d
 * @property string $cycle_id
 * @property string $status
 * @method static StatusRepository getRepository();
 */
class Status extends \Ext_Thebing_Basic {

    const STATUS_CLEAN = 'clean';
    const STATUS_DIRTY = 'dirty';
    const STATUS_NEEDS_REPAIR = 'repair';
    const STATUS_CHECKED = 'checked';

	/**
	 * @var string
	 */
	protected $_sTable = 'ts_accommodation_cleaning_status';

	/**
	 * @var string
	 */
	protected $_sTableAlias	= 'ts_acs';

    /**
     * @return \DateTime
     */
	public function getDate(): \DateTime {
	    return \DateTime::createFromFormat('Y-m-d', $this->date);
    }

}
