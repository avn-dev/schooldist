<input type="hidden" name="class_id" value="{$class->id}" />
<input type="hidden" name="block_id" value="{$block->id}" />

<div class="box">
    <div class="box-body">
        <div class="row">
            <div class="col-xs-12">
                <div class="form-group">
                    <label>{'Name of class'|L10N} *</label>
                    <input type="text" name="class_name" class="form-control" placeholder="{'Name'|L10N}" value="{$values['name']}" {($disabled || !$class->isEditableByTeacher($teacher)) ? 'disabled' : ''}/>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12 col-md-4">
                <div class="form-group">
                    <label>{'Date'|L10N} *</label>
                    {if $view === 'day'}
                        <input type="text" name="date" class="form-control" placeholder="{'Date'|L10N}" value="{$values['date']}" readonly/>
                    {elseif $view === 'week'}
                        <select name="date" class="form-control" {($disabled) ? 'disabled' : ''}>
                            {foreach $datePeriod as $periodDate}
                                <option
                                    value="{$periodDate|format_date}"
                                    {($values['date'] === $periodDate|format_date) ? 'selected' : ''}
                                >
                                    {$periodDate|format_date}
                                </option>
                            {/foreach}
                        </select>
                    {else}
                        <div class="input-group date" data-min="{$datePeriod->first()->toDateString()}" data-max="{$datePeriod->last()->toDateString()}">
                            <div class="input-group-addon">
                                <i class="fa fa-calendar"></i>
                            </div>
                            <input type="text" name="date" class="form-control" placeholder="{'Date'|L10N}" value="{$values['date']}" {($disabled) ? 'disabled' : ''}/>
                        </div>
                    {/if}
                </div>
            </div>
            <div class="col-xs-12 col-md-4">
                <div class="form-group">
                    <label>{'Time'|L10N} *</label>
                    <select id="class_time" name="time" class="form-control" {($disabled) ? 'disabled' : ''}>
                        {foreach $times as $time}
                            <option
                                value="{$time}"
                                {($values['time'] === $time) ? 'selected' : ''}
                            >
                                {$time}
                            </option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="col-xs-12 col-md-4">
                <div class="form-group">
                    <label>{'Lessons'|L10N} *</label>
                    <input type="text" name="lessons" class="form-control" placeholder="{'Lektionen'|L10N}" value="{$values['lessons']}" {($disabled) ? 'disabled' : ''} />
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12">
                <div class="form-group">
                    <label>{'Classroom'|L10N} *</label>
                    <div class="input-group">
                        <div class="input-group-addon">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <select name="room_id" class="form-control" {($disabled) ? 'disabled' : ''}>
                            {foreach $rooms as $room}
                                <option
                                    value="{$room['value']}"
                                    {($values['room_id'] == $room['value']) ? 'selected' : ''}
                                >
                                    {$room['text']}
                                </option>
                            {/foreach}
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{if !$class->exist() || $class->teacher_can_add_students}
    <div class="row">
        <div class="col-xs-12">
            <div class="form-group">
                <label>{'Add students'|L10N}</label>
                <div>
                    <select
                        name="students[]"
                        class="form-control students-select"
                        style="width: 100%"
                        multiple
                        {($disabled) ? 'disabled' : ''}
                    ></select>
                </div>
            </div>
        </div>
    </div>

    {if $class->isEditableByTeacher($teacher)}
        <div class="row">
            <div class="col-xs-12">
                <div class="form-group">
                    <label>{'Course'|L10N} *</label>
                    <select
                        name="course_id"
                        class="form-control"
                        {(!empty($students) || $disabled) ? 'disabled' : ''}
                    ></select>
                </div>
            </div>
        </div>
    {/if}
{/if}