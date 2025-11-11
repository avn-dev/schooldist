/* Die Data-Felder in beiden Tabellen sind auch MEDIUMTEXT, aber hier wird error_data dann abgeschnitten */
ALTER TABLE `core_parallel_processing_stack_error` CHANGE `error_data` `error_data` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
