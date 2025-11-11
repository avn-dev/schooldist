<?php

namespace TsAccommodation\Entity\Cleaning;

use TsAccommodation\Entity\Cleaning\Type;

class TypeRepository extends \WDBasic_Repository {

    /**
     * @param \Ext_Thebing_Accommodation_Allocation $allocation
     * @return array|Type[]
     */
    public function getTypesForAllocation(\Ext_Thebing_Accommodation_Allocation $allocation): array {

        $sql = "
            SELECT
                `ts_act`.*
            FROM
                #table `ts_act` INNER JOIN
                `ts_accommodation_cleaning_types_to_schools` `ts_acts` ON  
                    `ts_acts`.`type_id` = `ts_act`.`id` AND
                    `ts_acts`.`school_id` = :school_id INNER JOIN
                `ts_accommodation_cleaning_types_to_accommodation_categories` `ts_actac` ON  
                    `ts_actac`.`type_id` = `ts_act`.`id` AND
                    `ts_actac`.`category_id` = :category_id  INNER JOIN
                `ts_accommodation_cleaning_types_to_rooms` `ts_actr` ON  
                    `ts_actr`.`type_id` = `ts_act`.`id` AND
                    `ts_actr`.`room_id` = :room_id                 
            WHERE
                `ts_act`.`active` = 1                 
        ";

        $rows = \DB::getPreparedQueryData($sql, [
            'table' => $this->_oEntity->getTableName(),
            'room_id' => $allocation->room_id,
            'category_id' => $allocation->getAccommodationCategory()->getId(),
            'school_id' => $allocation->getInquiry()->getSchool()->getId()
        ]);

        if(!empty($rows)) {
            return $this->_getEntities($rows);
        }

        return [];
    }

    public function getAllRoomIds(): array {

        $sql = "
            SELECT
                `kr`.`id`
            FROM
                `kolumbus_rooms` `kr` INNER JOIN
                `ts_accommodation_cleaning_types_to_rooms` `ts_acttr` ON
                    `ts_acttr`.`room_id` = `kr`.`id` INNER JOIN
                `ts_accommodation_cleaning_types` `ts_act` ON
                    `ts_act`.`id` = `ts_acttr`.`type_id` AND 
                    `ts_act`.`active` = 1
            WHERE
                `kr`.`active` = 1
            GROUP BY
                `kr`.`id`   
            ORDER BY 
                `kr`.`accommodation_id`, `kr`.`position`
        ";

        return (array)\DB::getQueryCol($sql);
    }

    public function getAllSchoolIds(): array {

        $sql = "
            SELECT
                DISTINCT `ts_acts`.`school_id` 
            FROM
                `ts_accommodation_cleaning_types_to_schools` `ts_acts` INNER JOIN
                `ts_accommodation_cleaning_types` `ts_act` ON
                    `ts_act`.`id` = `ts_acts`.`type_id` AND 
                    `ts_act`.`active` = 1
        ";

        return (array)\DB::getQueryCol($sql);
    }

}
