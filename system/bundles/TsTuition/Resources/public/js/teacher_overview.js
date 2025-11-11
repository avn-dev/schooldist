$( document ).ready(function() {

	var sWeek = $('#week-date-input').val();

	if ($('#school-select').val() === 'all_schools') {
		getWeekBlocks(sWeek);
	} else {
		getWeekBlocks(sWeek, parseInt($('#school-select').val(), 10));
	}

	$('#prev-week').click(function() {

		var dWeekFrom = moment($('#week-date-input').val());
		dWeekFrom.subtract(7, 'days');

		var sWeekFromInput = dWeekFrom.format('YYYY-MM-DD').toString();
		$('#week-date-input').val(sWeekFromInput);

		if ($('#school-select').val() === 'all_schools') {
			getWeekBlocks(sWeekFromInput);
		} else {
			getWeekBlocks(sWeekFromInput, parseInt($('#school-select').val(), 10));
		}

		var sWeekFrom = dWeekFrom.format(sDateFormat).toString();
		$('#week-start').text(sWeekFrom);

		var dWeekUntil = dWeekFrom.add(6, 'days');
		var sWeekUntil = dWeekUntil.format(sDateFormat).toString();
		$('#week-end').text(sWeekUntil);

	});

	$('#next-week').click(function() {

		var dWeekFrom = moment($('#week-date-input').val());
		dWeekFrom.add(7, 'days');

		var sWeekFromInput = dWeekFrom.format('YYYY-MM-DD').toString();
		$('#week-date-input').val(sWeekFromInput);

		if ($('#school-select').val() === 'all_schools') {
			getWeekBlocks(sWeekFromInput);
		} else {
			getWeekBlocks(sWeekFromInput, parseInt($('#school-select').val(), 10));
		}

		var sWeekFrom = dWeekFrom.format(sDateFormat).toString();
		$('#week-start').text(sWeekFrom);

		var dWeekUntil = dWeekFrom.add(6, 'days');
		var sWeekUntil = dWeekUntil.format(sDateFormat).toString();
		$('#week-end').text(sWeekUntil);

	});

});

function getWeekBlocks(sWeek, schoolId = null) {

	$('.page-loader').show();

	var route = '/admin/ts/teacher-overview/ajax/' + sWeek;
	if (schoolId != null) {
		route += '/' + schoolId;
	}
	$.post(
		route,
		function (oData) {

			$('#teachers').html(oData.html);
			$('.day-box').tooltip({html: true});

			showTeacher();

			$('.page-loader').hide();

		}
	);

}

function showTeacher($schoolSelect = false) {

	var sSelectedTeacher = $('#teacher-select').find(':selected').val();
	var sSelectedSchool = $('#school-select').find(':selected').val();
	var sSelectedCourseCategory = $('#course-category-select').find(':selected').val();
	var sSelectedLevel = $('#level-select').find(':selected').val();
	var sSelectedCourseLanguage = $('#course-language-select').find(':selected').val();
	var sSelectedPlannedTeachers = $('#planned-teachers-select').find(':selected').val();
	var sSelectedTooManyOrTooLittleLessons = $('#too-many-or-too-little-lessons-select').find(':selected').val();

	$('.box-group').each(function() {

		if(
			(
				sSelectedCourseCategory === 'all_course_categorys' ||
				$(this).data('course-category-ids').includes(Number.parseInt(sSelectedCourseCategory))
			) &&
			(
				sSelectedLevel === 'all_levels' ||
				$(this).data('level-ids').includes(Number.parseInt(sSelectedLevel))
			) &&
			(
				sSelectedCourseLanguage === 'all_course_languages' ||
				$(this).data('course-language-ids').includes(Number.parseInt(sSelectedCourseLanguage))
			) &&
			(
				sSelectedTeacher === 'all_teachers' ||
				$(this).data('teacher-id') == sSelectedTeacher
			) &&
			(
				sSelectedSchool === 'all_schools' ||
				$(this).data('school-ids').includes(Number.parseInt(sSelectedSchool))
			) &&
			(
				sSelectedPlannedTeachers === 'planned_and_not_planned_teachers' ||
				$(this).data('planned-teacher') === 'planned'
			) &&
			( 	// Wenn der Filter "nicht aktiv" ist (default) oder die Lektionen von dem Lehrer passend zu dem Filter sind
				// -> (zu viele- oder zu wenige Lektionen)
				sSelectedTooManyOrTooLittleLessons === 'all_amount_lessons' ||
				sSelectedTooManyOrTooLittleLessons === 'too_many_lessons' &&
				$(this).data('too-many-or-too-little-lessons') === 'too_many' ||
				sSelectedTooManyOrTooLittleLessons === 'too_little_lessons' &&
				$(this).data('too-many-or-too-little-lessons') === 'too_little'
			)
		) {
			$(this).show();
		} else {
			$(this).hide();
		}

	});

	if ($schoolSelect) {

		var sWeek = $('#week-date-input').val();
		// Wenn eine andere Schule ausgewählt wurde, auch noch die Klassen ändern
		if (sSelectedSchool === 'all_schools') {
			getWeekBlocks(sWeek);
		} else {
			getWeekBlocks(sWeek, parseInt(sSelectedSchool, 10));
		}
	}

}