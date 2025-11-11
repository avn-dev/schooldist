ALTER TABLE `kolumbus_rooms` DROP `beds_comment`;
ALTER TABLE `kolumbus_rooms` ADD `single_beds_comment` VARCHAR(255) NOT NULL AFTER `double_beds`;
ALTER TABLE `kolumbus_rooms` ADD `double_beds_comment` VARCHAR(255) NOT NULL AFTER `single_beds_comment`;