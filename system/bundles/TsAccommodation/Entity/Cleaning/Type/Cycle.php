<?php

namespace TsAccommodation\Entity\Cleaning\Type;

class Cycle extends \Ext_Thebing_Basic {

    const MODE_ONCE_ROOM = 'once_room';
    const MODE_ONCE_BED = 'once_bed';
    const MODE_REGULAR_BED = 'regular_bed';
    const MODE_FIX_BED = 'fix_bed';

    /**
     * @var string
     */
    protected $_sTable = 'ts_accommodation_cleaning_types_cycles';

    /**
     * @var string
     */
    protected $_sTableAlias	= 'ts_actc';

    /**
     * @var array
     */
    protected $_aFormat = [];

    protected $_aJoinTables = [];

    protected $_aJoinedObjects = [];

    public function isOnceRoomClean(): bool {
        return ($this->mode === self::MODE_ONCE_ROOM);
    }

    public function isOnceBedClean(): bool {
        return ($this->mode === self::MODE_ONCE_BED);
    }

    public function isRegularBedClean(): bool {
        return ($this->mode === self::MODE_REGULAR_BED);
    }

    public function isFixBedClean(): bool {
        return ($this->mode === self::MODE_FIX_BED);
    }

    public function isDependendingOnFullCleaning(): bool {
        return ((int)$this->depending === 1);
    }

    /**
     * @param false $bThrowExceptions
     * @return bool|mixed
     * @throws \Exception
     */
    public function validate($bThrowExceptions = false) {

        if(
            $this->isFixBedClean() ||
            $this->isRegularBedClean()
        ) {
            // Wert muss größer 0 sein
            $this->_aFormat['count'] = [
                'validate' => 'INT_POSITIVE'
            ];
        } else if(isset($this->_aFormat['count'])) {
            unset($this->_aFormat['count']);
        }

        return parent::validate($bThrowExceptions);
    }

    /**
     * @param bool $bLog
     * @return \Ext_TC_Basic|Cycle
     * @throws \Exception
     */
    public function save($bLog = true) {

        if($this->isFixBedClean()) {
            $this->count_mode = 'weeks';
            $this->time = '';
        } else if($this->isRegularBedClean()) {
            $this->time = 'after_arrival';
        } else {
            $this->weekday = 0;
            $this->depending = 0;
            $this->depending_days = 0;
        }

        return parent::save($bLog);
    }

}
