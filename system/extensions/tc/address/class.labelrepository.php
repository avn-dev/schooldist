<?php

class Ext_TC_Address_LabelRepository extends \WDBasic_Repository {

	public function findByObject(\WDBasic $object) {

		$sql = "
			SELECT
				`tc_al`.*
			FROM
				`tc_addresslabels` `tc_al` INNER JOIN
				`tc_addresslabels_to_objects` `tc_alto` ON 
					`tc_alto`.`label_id` = `tc_al`.`id` AND
					`tc_alto`.`object_id` = :object_id
			WHERE 
				`tc_al`.`active` = 1
			GROUP BY 
			         `tc_al`.`id`
		";

		$labels = (array)\DB::getPreparedQueryData($sql, ['object_id' => $object->getId()]);

		return $this->_getEntities($labels);

	}

}
