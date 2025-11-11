UPDATE `tc_gui2_filtersets_bars_elements_basedon` SET `base_on` = 'type_status' WHERE `base_on` IN ('type_filter', 'type_filter_overview');
UPDATE `tc_gui2_filtersets_bars_elements_basedon` SET `base_on` = 'is_invoice' WHERE `base_on` = 'filter_proforma';
