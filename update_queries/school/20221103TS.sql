INSERT INTO `system_config` (`c_key`, `c_value`) VALUES ('ts_setup_wizard_completed', '1');

UPDATE `system_elements` SET `element` = 'bundle', `file` = 'TsWizard', `administrable` = '0' WHERE `file` = 'tswizard';