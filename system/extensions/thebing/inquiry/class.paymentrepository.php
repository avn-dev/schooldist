<?php

class Ext_Thebing_Inquiry_PaymentRepository extends \WDBasic_Repository {

    /**
     * @return Ext_Thebing_Inquiry_Payment[]
     */
    public function getUnreleasedPayments() {

        $sql = "
            SELECT
                `ts_ip`.*
            FROM 
                #table `ts_ip` LEFT JOIN 
                `ts_inquiries_payments_release` `ts_ipr` ON
                    `ts_ipr`.`payment_id` = `ts_ip`.`id`
            WHERE
                `ts_ip`.`active` = 1 AND 
                `ts_ipr`.`payment_id` IS NULL
            ORDER BY `ts_ip`.`id`
        ";

        $entries = (array)\DB::getPreparedQueryData($sql, [
            'table' => $this->_oEntity->getTableName(),
        ]);

        return $this->_getEntities($entries);
    }

}
