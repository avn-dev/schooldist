<?php

	DB::setResultType(MYSQL_ASSOC);

	$mixInput[5]['title'] = L10N::t('Students', 'Thebing » Welcome');
	$mixInput[5]['function'] = array('Ext_Thebing_Welcome', 'students');
	$mixInput[5]['right'] = 'thebing_welcome_students';
	$mixInput[5]['handler'] = (new \Admin\Components\Dashboard\Handler(4, 6))->min(4, 5);

	// Schulabhängig
	$mixInput[6]['title'] = L10N::t('Birthdays', 'Thebing » Welcome');
	$mixInput[6]['function'] = array('Ext_Thebing_Welcome', 'birthdays');
	$mixInput[6]['right'] = 'thebing_welcome_birthdays';
	$mixInput[6]['icon'] = 'fas fa-birthday-cake';
	$mixInput[6]['object'] = '\TsDashboard\Helper\Box';
	$mixInput[6]['handler'] = (new \Admin\Components\Dashboard\Handler(3, 6))->min(3, 4);

	$mixInput[7]['title'] = L10N::t('Pending Confirmations', 'Thebing » Welcome');
	$mixInput[7]['function'] = array('Ext_Thebing_Welcome', 'pending_confirmations');
	$mixInput[7]['right'] = 'thebing_welcome_pending_confirmations';
	$mixInput[7]['handler'] = (new \Admin\Components\Dashboard\Handler(4, 6))->min(4, 5);

	$mixInput[8]['title'] = L10N::t('Systeminformation', 'Thebing » Welcome');
	$mixInput[8]['function'] = array('Ext_TC_Welcome', 'getSystemInfo');
	$mixInput[8]['right'] = 'thebing_welcome_system_information';
	$mixInput[8]['handler'] = (new \Admin\Components\Dashboard\Handler(5, 6))->min(5, 4);

	$mixInput[9]['title'] = L10N::t('Automatischer E-Mail-Abruf', 'Thebing » Welcome');
	$mixInput[9]['function'] = array('Ext_TC_Welcome', 'getAutoImapContent');
	$mixInput[9]['right'] = 'thebing_welcome_system_information';
	$mixInput[9]['handler'] = (new \Admin\Components\Dashboard\Handler(2, 6))->min(2, 6);

	$mixInput[10]['title'] = L10N::t('Anfragen und Buchungen', 'Thebing » Welcome');
	$mixInput[10]['component'] = \TsDashboard\Admin\Components\EnquiriesAndInquiriesComponent::class;
	$mixInput[10]['handler'] = (new \Admin\Components\Dashboard\Handler(4, 6, true))->min(4, 5);

	$mixInput[11]['title'] = L10N::t('Schüler nach Nationalität', 'Thebing » Welcome');
	$mixInput[11]['component'] = \TsDashboard\Admin\Components\StudentNationalitiesComponent::class;
	$mixInput[11]['handler'] = (new \Admin\Components\Dashboard\Handler(4, 6, true))->min(4, 3);

	$mixInput[12]['title'] = L10N::t('Umgewandelte Anfragen in den letzten 31 Tagen', 'Thebing » Welcome');
	$mixInput[12]['object'] = \TsDashboard\Helper\ConvertedEnquiries::class;

	$mixInput[13]['title'] = L10N::t('Schüler aktuell in der Schule', 'Thebing » Welcome');
	$mixInput[13]['object'] = \TsDashboard\Helper\CurrentStudents::class;
