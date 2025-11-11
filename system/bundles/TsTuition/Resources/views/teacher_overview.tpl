{extends file="system/bundles/AdminLte/Resources/views/base.tpl"}

{block name="header"}
    <!-- fullCalendar -->
    <link rel="stylesheet" href="/assets/adminlte/components/fullcalendar/dist/fullcalendar.min.css">
    <link rel="stylesheet" href="/assets/adminlte/components/fullcalendar/dist/fullcalendar.print.min.css" media="print">
    <link rel="stylesheet" href="{route name='TsTuition.ts_tuition_teacher_overview_resources' sFile= 'css/teacher_overview.css'}">
{/block}

{block name="content"}
    <div class="content">
        <div class="box">
            <div class="box-body">

                <div class="row">
                    <div class="col-md-8">

                        <div class="form-group">

                            <select class="form-control" id="teacher-select" onchange="showTeacher()">
                                {html_options options=$aTeachers}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">

                        <div class="form-group">

                            <select class="form-control" id="school-select" onchange="showTeacher(true)">
                                {html_options options=$aSchools selected=$selectedSchool}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">

                        <div class="form-group">

                            <select class="form-control" id="course-category-select" onchange="showTeacher()">
                                {html_options options=$aCourseCategories}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">

                        <div class="form-group">

                            <select class="form-control" id="level-select" onchange="showTeacher()">
                                {html_options options=$aLevels}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">

                        <div class="form-group">

                            <select class="form-control" id="course-language-select" onchange="showTeacher()">
                                {html_options options=$aCourseLanguages}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">

                        <div class="form-group">

                            <select class="form-control" id="planned-teachers-select" onchange="showTeacher()">
                                {html_options options=$plannedTeachers selected='planned_teachers'}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">

                        <div class="form-group">

                            <select class="form-control" id="too-many-or-too-little-lessons-select" onchange="showTeacher()">
                                {html_options options=$tooManyOrTooLittleLessons}
                            </select>
                        </div>
                    </div>
                </div>

                    <div id="week-selection">
                        <button type="button" class="btn btn-xs btn-default" id="prev-week">
                            <span class="fa fa-arrow-left"></span>
                        </button>
                        <span id="week-date">
                            <span id="week-start">{Ext_Thebing_Format::LocalDate($dWeekFrom)}</span> – <span id="week-end">{Ext_Thebing_Format::LocalDate($dWeekUntil)}</span>
                        </span>
                        <button type="button" class="btn btn-xs btn-default" id="next-week">
                            <span class="fa fa-arrow-right"></span>
                        </button>

                        <input type="hidden" value="{$dWeekFrom->format('Y-m-d')}" id="week-date-input">
                    </div>
                <div id="teachers">

                </div>
            </div>
        </div>
    </div>

{/block}

{block name="footer"}
	
	<script>
	var sDateFormat = '{$sSchoolDateFormatMoment}';		
	</script>

    <!-- MomentJS -->
    <script src="/assets/adminlte/components/moment/moment.js"></script>
    <!-- fullCalendar -->
    <script src="/assets/adminlte/components/fullcalendar/dist/fullcalendar.min.js"></script>
    <script src="{route name='TsTuition.ts_tuition_teacher_overview_resources' sFile= 'js/teacher_overview.js'}"></script>
{/block}