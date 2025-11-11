INSERT INTO `system_elements` (`id`, `title`, `description`, `element`, `category`, `file`, `version`, `parent`, `image`, `documentation`, `template`, `sql`, `administrable`, `include_backend`, `include_frontend`, `include_mode`, `active`) VALUES (NULL, 'TcFrontend', '', 'bundle', 'Fidelo', 'TcFrontend', '0.01', '', '', '', '', '', '0', '0', '0', '0', '1');

ALTER TABLE `tc_frontend_combinations_items` CHANGE `item` `item` VARCHAR(100) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL;

ALTER TABLE `tc_frontend_combinations_items` CHANGE `item_value` `item_value` VARCHAR(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL;
