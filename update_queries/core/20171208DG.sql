/* In V5 gibt es keinen entsprechenden Ordner */
ALTER TABLE `core_parallel_processing_stack` ADD `execution_count` TINYINT UNSIGNED NOT NULL DEFAULT '0' AFTER `user_id`;
ALTER TABLE `core_parallel_processing_stack_error` CHANGE `execution_count` `execution_count` TINYINT UNSIGNED NOT NULL DEFAULT '0';
