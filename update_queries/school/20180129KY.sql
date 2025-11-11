CREATE TABLE `ts_accommodation_providers_to_contacts` (
  `accommodation_provider_id` int(10) UNSIGNED NOT NULL,
  `contact_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `ts_accommodation_providers_to_contacts`
  ADD PRIMARY KEY (`accommodation_provider_id`,`contact_id`);

CREATE TABLE `ts_accommodation_providers_requirements_documents_to_members` (
  `document_id` int(10) NOT NULL,
  `contact_id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;