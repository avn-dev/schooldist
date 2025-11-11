<div class="ui-body ui-body-a ui-corner-all ui-shadow">
	<p>
		<h3>{$sClassName}</h3>
		<table class="table-th-left-aligned" cellpadding="2">
			<tbody>
			<tr>
				<th>{$aTranslations.course}:</th>
				<td>{$sCourse}</td>
			</tr>
			<tr>
				<th>{$aTranslations.time}:</th>
				<td>{$sTime}</td>
			</tr>
			{if $sTeacher != ''}
				<tr>
					<th>{$aTranslations.teacher}:</th>
					<td>{$sTeacher}</td>
				</tr>
			{/if}
			{if $sBuilding != ''}
				<tr>
					<th>{$aTranslations.building}:</th>
					<td>{$sBuilding}</td>
				</tr>
			{/if}
			{if $sClassRoom != ''}
				<tr>
					<th>{$aTranslations.room}:</th>
					<td>{$sClassRoom}</td>
				</tr>
			{/if}
			{if $sAttendanceScore != ""}
				<tr>
					<th>{$aTranslations.score}:</th>
					<td>{$sAttendanceScore}</td>
				</tr>
			{/if}
			{if $sAttendanceNote != ""}
				<tr>
					<th>{$aTranslations.note}:</th>
					<td>{$sAttendanceNote}</td>
				</tr>
			{/if}
			{foreach $aAttendanceFlexFields as $aAttendanceFlexField}
				<tr>
					<th>{$aAttendanceFlexField.name}:</th>
					<td>{$aAttendanceFlexField.value}</td>
				</tr>
			{/foreach}
			{if !empty($aAbsenceDays)}
				<tr>
					<th colspan="2"><h4>{$aTranslations.absence_per_day}</h4></th>
				</tr>
			{/if}
			{foreach $aAbsenceDays as $aAbsenceDay}
				<tr>
					<th>{$aAbsenceDay.name}:</th>
					<td>{$aAbsenceDay.absence_formatted}</td>
				</tr>
			{/foreach}
			</tbody>
		</table>
	</p>
</div>