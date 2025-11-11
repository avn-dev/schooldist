CREATE TABLE `tc_employees_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `editor_id` int(11) NOT NULL,
  `creator_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `tc_employees_categories`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `tc_employees_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

CREATE TABLE `tc_employees_to_categories` (
  `employee_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tc_employees_categories_to_functions` (
  `category_id` int(11) NOT NULL,
  `function` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
