{if $sOverallAttendance != ''}
	<h3>{$aTranslations.overall_attendance}</h3>
	<div class="ui-body ui-body-a ui-corner-all ui-shadow">
		<p>
			<strong>{$aTranslations.current_overall_attendance}:</strong>
			{$sOverallAttendance}
		</p>
		{if $sOverallAttendanceGlobal != ''}
			<p>
				<strong>{$aTranslations.overall_attendance_all_bookings}:</strong>
				{$sOverallAttendanceGlobal}
			</p>
		{/if}
	</div>
{/if}
<h3>{$aTranslations.attendance_per_course}</h3>
<div class="ui-body ui-body-a ui-corner-all ui-shadow">
	<p>
		<table class="table-th-left-aligned" cellpadding="2">
			<tbody>
				{foreach $aAttendancePerCourse as $aCourse}
					<tr>
						<th>{$aCourse.name}:</th>
						<td>{$aCourse.attendance}</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</p>
</div>
<h3>{$aTranslations.attendance_per_teacher}</h3>
<div class="ui-body ui-body-a ui-corner-all ui-shadow">
	<p>
		<table class="table-th-left-aligned" cellpadding="2">
			<tbody>
				{foreach $aAttendancePerTeacher as $aTeacher}
					<tr>
						<th>{$aTeacher.name}:</th>
						<td>{$aTeacher.attendance}</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</p>
</div>