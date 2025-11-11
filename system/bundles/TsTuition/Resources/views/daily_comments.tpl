<input type="hidden" name="block_id" value="{$blockId|escape}">
{foreach $days as $dayNumber=>$day}

	<h4>{$day}</h4>
	
	{foreach $blocks as $block}
		{if in_array($dayNumber, $block->days)}
			<div class="GUIDialogRow form-group form-group-sm">
				<label class="GUIDialogRowLabelDiv control-label GUIDialogRowLabelDivSmall col-sm-2">{$block->name}<br>{if $block->getTeacher()}{'Lehrer'|L10N}: {$block->getTeacher()->getName()}{/if}</label>
				<div class="GUIDialogRowInputDiv col-sm-10">
					<textarea class="GuiDialogHtmlEditor txt form-control input-sm" style="height:80px;" name="save[{$block->id}][{$dayNumber}][comment]" id="save_{$block->id}_{$dayNumber}">{$block->getUnit($dayNumber)->comment|default:""|escape:"html"}</textarea>
				</div>
			</div>
			<div class="GUIDialogRow form-group form-group-sm">
				<label class="GUIDialogRowLabelDiv control-label GUIDialogRowLabelDivSmall col-sm-2">{'Unterrichtseinheit hat nicht stattgefunden'|L10N}</label>
				<div class="GUIDialogRowInputDiv col-sm-10">
					<input type="hidden" class="txt form-control input-sm" name="save[{$block->id}][{$dayNumber}][state]" value="0">
					<input type="checkbox" name="save[{$block->id}][{$dayNumber}][state]" id="save_{$block->id}_{$dayNumber}_state" class="cancel-block-day" value="1" {if $block->getUnit($dayNumber)->isCancelled()}checked{/if} {if $block->getUnit($dayNumber)->hasTakenPlace()}disabled{/if}>
				</div>
			</div>
			<div class="GUIDialogRow form-group form-group-sm">
				<label class="GUIDialogRowLabelDiv control-label GUIDialogRowLabelDivSmall col-sm-2">{'Status Kommentar'|L10N}</label>
				<div class="GUIDialogRowInputDiv col-sm-10">
					<textarea class="txt form-control input-sm" style="height:80px;" name="save[{$block->id}][{$dayNumber}][state_comment]" id="save_{$block->id}_{$dayNumber}_state_comment">{$block->getUnit($dayNumber)->state_comment|escape}</textarea>
				</div>
			</div>
				
			{$allocationsWithAbsence = []}
			{foreach $block->getAllocations() as $allocation}
				{$attendance = $allocation->getAttendance()}
				{if $attendance && $attendance->isAbsent($dayNumber)}
					{$allocationsWithAbsence[] = $allocation}
				{/if}
			{/foreach}
				
			{if !empty($allocationsWithAbsence)}
						
			<div class="GUIDialogRow form-group form-group-sm">
				<label class="GUIDialogRowLabelDiv control-label GUIDialogRowLabelDivSmall col-sm-2">{'Abwesenheitsgründe'|L10N}</label>
				<div class="GUIDialogRowInputDiv col-sm-10 form-horizontal" style="columns: 3;">
					
					{foreach $allocationsWithAbsence as $allocation}
						{$attendance = $allocation->getAttendance()}
						<div class="form-group" style="width: 100%;">
							<label for="absence-reason-{$allocation->id}-{$dayNumber}" class="col-sm-7 control-label">{$allocation->customer_name}</label>
							<div class="col-sm-5">
								<select class="form-control input-sm" id="absence-reason-{$allocation->id}-{$dayNumber}" name="absence_reason[{$allocation->id}][{$dayNumber}]">
									{html_options options=$absenceReasons selected=$attendance->absence_reasons[$dayNumber]}
								</select>
							</div>
						</div>
					{/foreach}
					
				</div>
			</div>
						
			{/if}
				
		{/if}
	{/foreach}
	
{/foreach}