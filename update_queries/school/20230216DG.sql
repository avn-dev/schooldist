ALTER TABLE `ts_inquiries_matching_data`
    CHANGE `cats` `cats` TINYINT(1) NOT NULL DEFAULT '0',
    CHANGE `dogs` `dogs` TINYINT(1) NOT NULL DEFAULT '0',
    CHANGE `pets` `pets` TINYINT(1) NOT NULL DEFAULT '0',
    CHANGE `smoker` `smoker` TINYINT(1) NOT NULL DEFAULT '0',
    CHANGE `distance_to_school` `distance_to_school` TINYINT(1) NOT NULL DEFAULT '0',
    CHANGE `air_conditioner` `air_conditioner` TINYINT(1) NOT NULL DEFAULT '0',
    CHANGE `bath` `bath` TINYINT(1) NOT NULL DEFAULT '0',
    CHANGE `family_age` `family_age` TINYINT(1) NOT NULL DEFAULT '0',
    CHANGE `family_kids` `family_kids` TINYINT(1) NOT NULL DEFAULT '0',
    CHANGE `internet` `internet` TINYINT(1) NOT NULL DEFAULT '0',
    CHANGE `acc_vegetarian` `acc_vegetarian` TINYINT(1) NOT NULL DEFAULT '0',
    CHANGE `acc_muslim_diat` `acc_muslim_diat` TINYINT(1) NOT NULL DEFAULT '0',
    CHANGE `acc_smoker` `acc_smoker` TINYINT(1) NOT NULL DEFAULT '0';