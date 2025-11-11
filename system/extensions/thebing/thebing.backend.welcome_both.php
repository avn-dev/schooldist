<?php

	// News
	$mixInput[0]['title'] = L10N::t('Ankündigungen', 'Thebing » Welcome');
	$mixInput[0]['component'] = \Tc\Admin\Components\Dashboard\NewsWidgetComponent::class;
	$mixInput[0]['class'] = 'box-warning news';
	$mixInput[0]['icon'] = 'fas fa-exclamation-circle';
	$mixInput[0]['show_always'] = 1;
	$mixInput[0]['no_padding'] = true;
	$mixInput[0]['handler'] = (new \Admin\Components\Dashboard\Handler(3, 12, true))
		->min(2, 4)
		->deletable(false);

	DB::setResultType(MYSQL_ASSOC);

	$mixInput[10]['title'] = L10N::t('Bookings', 'Thebing » Welcome');
	$mixInput[10]['function'] = array('Ext_Thebing_Welcome', 'bookings');
	$mixInput[10]['right'] = 'thebing_welcome_bookings';
	$mixInput[10]['handler'] = (new \Admin\Components\Dashboard\Handler(4, 12))->min(3, 4);
	$mixInput[10]['print'] = true;

//	$mixInput[11]['title'] = L10N::t('Groups', 'Thebing » Welcome');
//	$mixInput[11]['title'] =  $mixInput[11]['title'];
//	$mixInput[11]['function'] = array('Ext_Thebing_Welcome', 'groups');
//	$mixInput[11]['right'] = 'thebing_welcome_groups';

//	$mixInput[12]['title'] = L10N::t('Group details', 'Thebing » Welcome');
//	$mixInput[12]['title'] =  $mixInput[12]['title'];
//	$mixInput[12]['function'] = array('Ext_Thebing_Welcome', 'groups_details');
//	$mixInput[12]['right'] = 'thebing_welcome_groups_details';

	$mixInput[13]['title'] = L10N::t('Agencies', 'Thebing » Welcome');
	$mixInput[13]['function'] = array('Ext_Thebing_Welcome', 'agencies');
	$mixInput[13]['right'] = 'thebing_welcome_agencies';
	$mixInput[13]['handler'] = (new \Admin\Components\Dashboard\Handler(6, 12))->min(4, 4);

//	$mixInput[14]['title'] = L10N::t('Pending Payments', 'Thebing » Welcome');
//	$mixInput[14]['title'] =  $mixInput[14]['title'];
//	$mixInput[14]['function'] = array('Ext_Thebing_Welcome', 'pending_payments');
//	$mixInput[14]['right'] = 'thebing_welcome_pending_payments';

//	$mixInput[15]['title'] = L10N::t('Accommodation Occupancy', 'Thebing » Welcome');
//	$mixInput[15]['function'] = array('Ext_Thebing_Welcome', 'accommodation_occupancy');
//	$mixInput[15]['right'] = 'thebing_welcome_accommodation_occupancy';

	$mixInput[16]['title'] = L10N::t('Schüler in der Schule (kursbezogen)', 'Thebing » Welcome');
	$mixInput[16]['function'] = array('Ext_Thebing_Welcome', 'getStudentsInSchoolStatistic');
	$mixInput[16]['right'] = 'thebing_welcome_students_course_related';
	$mixInput[16]['handler'] = (new \Admin\Components\Dashboard\Handler(4, 12))->min(4, 4);
	$mixInput[16]['print'] = true;

	$mixInput[17]['title'] = L10N::t('Offene Unterkunftszuweisungen', 'Thebing » Welcome');
	$mixInput[17]['function'] = array('Ext_Thebing_Welcome', 'getPendingHousingPlacementsStatistic');
	$mixInput[17]['right'] = 'thebing_welcome_pending_housing_placements';
	$mixInput[17]['handler'] = (new \Admin\Components\Dashboard\Handler(4, 12))->min(4, 4);
	$mixInput[17]['print'] = true;
