<?php

class Ext_Thebing_Inquiry_DocumentRepository extends \WDBasic_Repository {

    /**
     * @return Ext_Thebing_Inquiry_Document[]
     */
    public function getUnreleasedDocuments() {

        $sql = "
            SELECT
                `ts_d`.*
            FROM 
                #table `ts_d` LEFT JOIN 
                `ts_documents_release` `ts_dr` ON
                    `ts_dr`.`document_id` = `ts_d`.`id`
            WHERE
                `ts_d`.`active` = 1 AND 
                `ts_d`.`type` IN (:types) AND 
                `ts_dr`.`document_id` IS NULL
            ORDER BY `ts_d`.`id`
        ";

        $types = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice_with_creditnotes_and_without_proforma');
        $types[] = 'creditnote_cancellation';

        $entries = (array)\DB::getPreparedQueryData($sql, [
            'table' => $this->_oEntity->getTableName(),
            'types' => $types
        ]);

        return $this->_getEntities($entries);
    }

}
