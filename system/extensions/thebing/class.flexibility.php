<?php 

class Ext_Thebing_Flexibility extends Ext_TC_Flexibility {

	public static function getSectionAllocations() {
		
		$aSectionAllocation = [
			'tuition_attendance' => 'Ext_Thebing_School_Tuition_Allocation',
			'student_record' => 'Ext_TS_Inquiry',
			'tuition_courses' => 'Ext_Thebing_Tuition_Course',
			'tuition_course_languages' => 'Ext_Thebing_Tuition_LevelGroup',
			'accommodation_providers' => \Ext_Thebing_Accommodation::class,
			'accounting_companies' => \TsAccounting\Entity\Company::class,
		];
		
		return $aSectionAllocation;
	}

	public static function getFixFieldSectionLimits() {
		return [
			// Hier müssen PRO KURS Querys ausgeführt werden
			'student_record_journey_course' => 3
		];
	}

	public static function getFieldSectionIdsWithoutListView() {
		return [
//			1, // tuition_courses_general
			26, // admin_users
//			33, // enquiries_groups
			34, // schools_accounting
			35, // marketing_additional_costs
			36, // insurances
			37, // accounting_companies_options
			45, // inquiries_groups_transfer
		];
	}

	public static function getFieldSectionsWithPlaceholders() {
		return [
			1, // tuition_courses_general
			3, // student_record_general
			4, // student_record_course
			5, // student_record_accommodation
			6, // student_record_matching
			7, // student_record_transfer
			8, // teachers_general
			9, // teachers_bank
			10, // teachers_qualification
			11, // tuition_course_categories
			13, // tuition_course_classrooms
			14, // accommodation_providers_general
			15, // accommodation_providers_bank
			16, // accommodations
			17, // roomtypes
			18, // meals
			19, // agencies_details
			20, // agencies_info
			21, // agencies_bank
			22, // agencies_provision
			23, // airports
			24, // transfer_providers
			25, // roomdata
			26, // admin_user
			27, // student_record_visum
			28, // student_record_insurance
			51, // student_record_activities
			29, // accommodation_providers_info
			30, // student_record_upload
			31, // agencies_users_details
			32, // enquiries_enquiries
//			33, // enquiries_groups
			34, // schools_accounting
			38, // tuition_attendance_register
			39, // student_record_visum_status
			42, // inquiries_groups_general
			43, // inquiries_groups_course
			44, // inquiries_groups_accommodation
			45, // inquiries_groups_transfer
			46, // student_record_journey_course
			48, // groups_enquiries_bookings
			52, // tuition_courses_superordinate
			54, // tuition_course_languages
			57, // student_record_sponsoring
			37, // accounting_companies_options
		];
	}

	/**
	 * category => usage
	 * @param Ext_Gui2 $gui
	 * @return array
	 */
	public static function getCategoryUsage(Ext_Gui2 $gui) {
		
		$return = [
			'student_record' => [
				'enquiry' => $gui->t('Anfrage'),
				'booking' => $gui->t('Buchung'),
				'enquiry_booking' => $gui->t('Anfrage und Buchung')
			]
		];
		
		return $return;
	}
	
}
