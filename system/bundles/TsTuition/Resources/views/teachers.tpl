{if !empty($aTeachersData)}
    {foreach $aTeachersData as $iTeacherId => $aTeacherData}
        {$planned = 'not_planned'}
        {if $aTeacherData['weekly_class_duration'] > 0}
            {$planned = 'planned'}
        {/if}
        {$tooManyOrTooLittleLessons = ''}
        {if $planned === 'planned'}
            {if $aTeacherData['weekly_lessons'] > $aTeachersTargetLessons[$iTeacherId]}
                {$tooManyOrTooLittleLessons = 'too_many'}
            {elseif $aTeacherData['weekly_lessons'] < $aTeachersTargetLessons[$iTeacherId]}
                {$tooManyOrTooLittleLessons = 'too_little'}
            {/if}
        {/if}
        <div class="box-group" data-teacher-id="{$iTeacherId}"
             data-school-ids="{$aTeacherData['teacher_schools']|json_encode}"
             data-course-language-ids="{$aTeacherData['teacher_course_languages']|json_encode}"
             data-level-ids="{$aTeacherData['teacher_levels']|json_encode}"
             data-course-category-ids="{$aTeacherData['teacher_course_categories']|json_encode}"
             data-planned-teacher="{$planned}"
             data-too-many-or-too-little-lessons="{$tooManyOrTooLittleLessons}"
        >
            <div class="panel box box-primary">
                <a data-toggle="collapse" href="#{$iTeacherId}" aria-expanded="true">
                    <div class="box-header with-border">
                        <h4 class="box-title">
                            {$aTeacherData['teacher_name']}
                        </h4>
                        <i class="fa fa-arrow-circle-down pull-right"></i>
                        <span class="pull-right">
                            {'Klassendauer'|L10N}: {Ext_Thebing_Format::Number($aTeacherData['weekly_class_duration'], null, null, false)}   /
                            {'Lektionen'|L10N}: {Ext_Thebing_Format::Number($aTeacherData['weekly_lessons'], null, null, false)} /
                            {'Stunden'|L10N}: {Ext_Thebing_Format::Number($aTeacherData['weekly_hours'], null, null, false)}
                            {if isset($aTeachersTargetLessons[$iTeacherId])}
                                 / {'Geplant'|L10N}: {Ext_Thebing_Format::Number($aTeachersTargetLessons[$iTeacherId], null, null, false)}
                            {/if}
                            &nbsp;
                        </span>
                    </div>
                </a>
                <div id="{$iTeacherId}" class="panel-collapse collapse" aria-expanded="false">
                    <div class="box-body">
                        <div class="days-container">
                            {foreach $aDaysData as $iDay => $aDay}
                                <div class="day-box"
                                    {if isset($aTeachersAbsence[$iTeacherId][$iDay])}
                                        style="background-color: {$aTeachersAbsence[$iTeacherId][$iDay]['color']}"
                                        data-toggle="tooltip" data-placement="left"
                                        title="{$aTeachersAbsence[$iTeacherId][$iDay]['comment']}"
                                    {elseif isset($aHolidays[$iDay])}
                                        style="background-color: {$aHolidays[$iDay]['color']}"
                                    {/if}
                                >
                                    <h3>{$aDay['day_name']} <br>{$aDay['day_date']}</h3>
                                    {if $aTeacherData['days'][$iDay] !== NULL}
                                        {if !empty($aTeacherData['days'][$iDay]['blocks'])}
                                            <small>{'Lektionen'|L10N}: {Ext_Thebing_Format::Number($aTeacherData['days'][$iDay]['daily_lessons'], null, null, false)}, {'Stunden'|L10N}: {Ext_Thebing_Format::Number($aTeacherData['days'][$iDay]['daily_hours'], null, null, false)}</small>
                                            {foreach $aTeacherData['days'][$iDay]['blocks'] as $aBlock}
                                                <div class="block-box" style="background-color: {$aBlock['class_color']}">
                                                    <p>{$aBlock['time_from']} - {$aBlock['time_until']}</p>
                                                    <p>{$aBlock['class']}</p>
                                                    <p>{if !empty($aBlock['room'])}{$aBlock['room']}{else}{'Kein Raum'|L10N}{/if}</p>
                                                    <p>{'Lektionen'|L10N}: {Ext_Thebing_Format::Number($aBlock['lessons'], null, null, false)}</p>
                                                    <p>{'Schüler'|L10N}: {$aBlock['count_students']}</p>
                                                    {if $aBlock['level']}<p>{'Level'|L10N}: {$aBlock['level']}</p>{/if}
                                                </div>
                                            {/foreach}
                                        {/if}
                                    {else}
                                        <br>
                                        {if isset($aTeachersAbsence[$iTeacherId][$iDay])}
                                            <p class="category-name">{$aTeachersAbsence[$iTeacherId][$iDay]['category_name']}</p>
                                        {elseif $aHolidays[$iDay]}
                                            <p class="category-name">{$aHolidays[$iDay]['name']}</p>
                                        {else}
                                            <i class="fa fa-calendar-times-o day-off"></i>
                                        {/if}
                                    {/if}
                                </div>
                            {/foreach}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {/foreach}
{else}
    <br>
    <div class="alert alert-info alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        <i class="icon fa fa-exclamation"></i> {'Es gibt keine Blöcke in dieser Woche!'|L10N}
    </div>
{/if}