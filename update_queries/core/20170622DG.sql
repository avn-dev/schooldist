
ALTER TABLE `tc_referrers` ADD `position` MEDIUMINT UNSIGNED NOT NULL AFTER `editor_id`;

ALTER TABLE `tc_referrers` CHANGE `valid_until` `valid_until` DATE NOT NULL DEFAULT '0000-00-00';
