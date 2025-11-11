ALTER TABLE `kolumbus_inquiries_payments`
    CHANGE `sender` `sender` ENUM('customer','agency','school','sponsor') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    CHANGE `receiver` `receiver` ENUM('customer','agency','school','sponsor') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;