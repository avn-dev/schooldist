<div style="width: 900px;">

<h3>Urlaub</h3>
<table style="border: 1px solid #CCC;">
	<tr>
		<td style="width: 120px;"><b>Gesamturlaub</b></td>
		<td style="text-align: right;">{$aHolidayData[0].holiday|number_format:2:",":"."}</td>
	</tr>
	<tr>
		<td style="width: 120px;"><b>Urlaub genommen</b></td>
		<td style="text-align: right;">{$aHolidayData[0].togetherholidays|number_format:2:",":"."}</td>
	</tr>
	<tr>	
		<td style="width: 120px;"><b>Resturlaub</b></td>
		<td style="text-align: right;">{$aHolidayData[0].holiday-$aHolidayData[0].togetherholidays|number_format:2:",":"."}</td>
	</tr>
</table>

<h3>Auswertung</h3>
{if $sFlag == 1}
	<form method="post" action="{$smarty.server.PHP_SELF}">
		<div>
			<input type="hidden" name="o_tc_id" value="{$aChangeData.id}" />
			<table style="width:100%; border: 1px solid #CCC;">
				<tr>
					<td><b>Projekt:</b>&nbsp;</td>
					<td>
						{if $sTodo == 'request_new'}
							<input type="hidden" name="todo" value="request_new" />
							<select name="o_tc_projects" onchange="this.form.submit();">
								{foreach from=$aNewEntryProjects item=sProject key=iKey}
									<option value="{$iKey}" {if $iKey == $iSelNewPro}selected="selected"{/if}>
										{$sProject}
									</option>
								{/foreach}
							</select>
						{elseif $sTodo == 'request_change'}
							{*$aChangeData.project*}
							<input type="hidden" name="o_tc_project_id" value="{$aChangeData.project_id}" />
							<input type="hidden" name="todo" value="request_change" />
							<select name="o_tc_projects" onchange="this.form.submit();">
								{foreach from=$aNewEntryProjects item=sProject key=iKey}
									<option value="{$iKey}" {if $iKey == $aChangeData.project_id}selected="selected"{/if}>
										{$sProject}
									</option>
								{/foreach}
							</select>
						{/if}
					</td>
				</tr>
				<tr>
					<td><b>Tätigkeit:</b>&nbsp;</td>
					<td>
						<select name="o_tc_activities">
							{foreach from=$aActivities item=aActivity key=iKey}
								<option value="{$aActivity.id}" {if $aActivity.id == $aChangeData.position_id}selected="selected"{/if}>
									{if $aActivity.alias != ''}
										{$aActivity.alias} -
									{/if}
									{$aActivity.title}
								</option>
							{/foreach}
						</select>
					</td>
				</tr>
				<tr>
					<td><b>Start:</b>&nbsp;</td>
					<td>
						<input name="o_tc_date_from" class="form-control" value="{$aChangeData.start|date_format:'%d.%m.%Y'}" style="width: 100px;"/> (dd.mm.YYYY)
						/
						<input name="o_tc_time_from" class="form-control" value="{$aChangeData.start|date_format:'%H:%M'}" style="width: 60px;"/> (hh:mm)
					</td>
				</tr>
				<tr>
					<td><b>Ende:</b>&nbsp;</td>
					<td>
						<input name="o_tc_date_till" class="form-control" value="{$aChangeData.end|date_format:'%d.%m.%Y'}" style="width: 100px;"/> (dd.mm.YYYY)
						/
						<input name="o_tc_time_till" class="form-control" value="{$aChangeData.end|date_format:'%H:%M'}" style="width: 60px;"/> (hh:mm)
					</td>
				</tr>
				<tr>
					<td><b>Kommentar:</b>&nbsp;</td>
					<td>
						<textarea name="o_tc_comment" class="form-control" rows="0" cols="0" style="width:100%; height:50px;" required>{$aChangeData.comment|escape}</textarea>
					</td>
				</tr>
				<tr>
					<td></td>
					<td>
						{if $sTodo == 'request_new'}
							<input type="submit" name="save_request_new" class="btn btn-primary" value="Speichern" />
							<input type="submit" name="reset_request_new" class="btn btn-default" value="Abbrechen" />
						{elseif $sTodo == 'request_change'}
							<input type="submit" name="save_request_change" class="btn btn-primary" value="Speichern" />
							<input type="submit" name="reset_request_change" class="btn btn-default" value="Abbrechen" />
						{/if}
					</td>
				</tr>
			</table>
		</div>
	</form>
{/if}

<br />

{if $sFlag != 1}
	<div style="float: left;">
		<form method="post" action="">
			<input type="hidden" id="o_tc_action" name="action" value="show_timeclock_times" />
			Datum: <input class="txt" name="o_tc_from" class="form-control" value="{$sFrom}" style="width: 65px;" />
			- <input class="txt" name="o_tc_till" class="form-control" value="{$sTill}" style="width: 65px;" />
			Projekt: <select class="form-control" name="o_tc_projects" onchange="this.form.submit();" style="width:120px;">
				{foreach from=$aProjects item=sProject key=iKey}
					<option value="{$iKey}" {if $iKey == $iProjectID}selected="selected"{/if}>{$sProject}</option>
				{/foreach}
			</select>
			<input class="btn btn-primary" type="submit" name="refresh" value="anzeigen" />
		</form>
	</div>

	<div style="float: right;">
		<form method="post" action="">
			<input type="hidden" name="todo" value="request_new" />
			<input class="btn btn-default" type="submit" value="Neuer Eintrag" />
		</form>
	</div>

	<div style="clear: both;"></div>

	<br />

	{if $iDateError == 1}
		<div style="text-align:center; color:red;">
			<b>Datumsfehler! Speichern fehlgeschlagen.</b>
		</div>
	{/if}

	<form method="post" action="#page:1249:pagelink#">
		<input type="hidden" name="action" value="prepare_invoice" />
		<table style="width:100%; border: 1px solid #CCC;">
			<tr>
				<td style="width: 30px;">&nbsp;</td>
				<td style="width: 200px;"><b>Datum</b></td>
				<td style="width: auto;"><b>Projekt/Tätigkeit</b></td>
				<td style="width: 50px;"><b>Start</b></td>
				<td style="width: 50px;"><b>Ende</b></td>
				<td style="width: 110px;"><b>Aktion</b></td>
			</tr>
			<tr>
				<td colspan="7"><hr style="margin:0px; padding:0px; border-bottom:1px solid #CCC ; border-top:0px; border-left:0px; border-right:0px; line-height:0px; height:1px; display:block;"></td>
			<tr>

			{assign var='lastDate' value=''}
			{foreach from=$aTimes item=aTime name=entry}
				{assign var='sCurrentDay' value=$aTime.start|date_format:'%Y-%m-%d'}
				<tr {if ($aTime.start|date_format:'%Y%m%d') != ($aTime.end|date_format:'%Y%m%d')}style="color: red!important;"{/if}>
					<td>
						{if $aTime.action == 'accepted'}
							<img src="/admin/media/office_finished.png" />
						{elseif $aTime.action == 'delete'}
							<img src="/admin/media/bullet_red.png" />
						{elseif $aTime.action == 'new'}
							<img src="/admin/media/bullet_green.png" />
						{elseif $aTime.action == 'change'}
							<img src="/admin/media/bullet_orange.png" />
						{elseif $aTime.action == 'declined'}
							<img src="/admin/media/stop.png" />
						{else}
							&nbsp;
						{/if}
					</td>
					<td>
						{if $lastDate != ($aTime.start|date_format:'%d.%m.%Y')}
							<strong>{$aTime.start|date_format:'%d.%m.%Y'}</strong><br/>
							<span style="{if $aDayTimes.$sCurrentDay.break.O < 900}color: red;{/if}">Pause: {$aDayTimes.$sCurrentDay.break.T}</span>, 
							Total: {$aDayTimes.$sCurrentDay.net.T}
						{else}
							&nbsp;
						{/if}
						{assign var='lastDate' value=$aTime.start|date_format:'%d.%m.%Y'}
					</td>
					<td>{$aTime.project} / <br/>
						{if $aTime.alias != ''}
							{$aTime.alias} - 
						{/if}
						{$aTime.title}
						{if $aTime.comment != ''}
							<div style="padding: 0 3px; background-color:#FFFFAA; font-size:10px;">{$aTime.comment}</div>
						{/if}
					</td>
					<td>{$aTime.start|date_format:'%H:%M:%S'}</td>
					<td>
						{if $aTime.end != 0}
							{$aTime.end|date_format:'%H:%M:%S'}
						{else}
							läuft
						{/if}
					</td>
					<td>
						<a href="?todo=request_change&amp;tc_id={$aTime.id}">Ändern</a>|<a href="?todo=request_delete&amp;tc_id={$aTime.id}">Löschen</a>
					</td>
				</tr>
				{if !$smarty.foreach.entry.last}
					<tr>
						<td colspan="6"><hr style="margin:0px; padding:0px; border-bottom:1px dashed #CCC ; border-top:0px; border-left:0px; border-right:0px; line-height:0px; height:1px; display:block;"></td>
					<tr>
				{else}
					<tr>
						<td colspan="6"><hr style="margin:0px; padding:0px; border-bottom:1px solid #CCC ; border-top:0px; border-left:0px; border-right:0px; line-height:0px; height:1px; display:block;"></td>
					<tr>
					<tr>
						<td colspan="5" style="text-align:right;">
							<b>Gesamt:</b>
						</td>
						<td style="text-align:right;">
							<b>{$aWorkTimes.between.H}:{$aWorkTimes.between.M}:{$aWorkTimes.between.S} Std.</b>
						</td>
					</tr>
					{if !empty($aWorkTimes.soll)}
						<tr>
							<td style="text-align:right;" colspan="5">
								<b>Soll:</b>
							</td>
							<td style="text-align:right;">
								<b>{$aWorkTimes.soll.H}:{$aWorkTimes.soll.M}:{$aWorkTimes.soll.S} Std.</b>
							</td>
						</tr>
					{/if}
				{/if}
			{/foreach}
		</table>
	</form>

	<div style="position:relative; top:-10px;">
		<b>Änderungen:</b> |
		<img src="/admin/media/office_finished.png" alt="" style="position:relative; top:4px;" />akzeptiert |
		<img src="/admin/media/stop.png" alt="" style="position:relative; top:4px;" /> abgelehnt |
		<img src="/admin/media/bullet_green.png" alt="" style="position:relative; top:4px;" />neu |
		<img src="/admin/media/bullet_orange.png" alt="" style="position:relative; top:4px;" />offen |
		<img src="/admin/media/bullet_red.png" alt="" style="position:relative; top:4px;" >gelöscht |
	</div>
{/if}
</div>