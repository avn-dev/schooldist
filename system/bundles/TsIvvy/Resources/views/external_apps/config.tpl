{function displayField label="" type="" nameAttr="" value=""}
	<div class="form-group">
		<label for="{$nameAttr}" class="col-sm-2 control-label">{$label}</label>
		<div class="col-sm-10">
			{if $type === 'input'}
				<input type="text" name="{$nameAttr}" class="form-control" id="{$nameAttr}" value="{$value}">
			{elseif $type === 'checkbox'}
				<input type="hidden" name="{$nameAttr}" id="{$nameAttr}" value="0">
				<input type="checkbox" name="{$nameAttr}" id="{$nameAttr}" value="1" {if $value == 1}checked{/if}>
			{elseif $type === 'select'}
				<select name="{$nameAttr}" id="{$nameAttr}" class="form-control">
					<option value="0"></option>
					{foreach $options as $option => $text}
						<option value="{$option}" {if $value == $option}selected{/if}>{$text}</option>
					{/foreach}
				</select>
			{else}
				Unknown
			{/if}
		</div>
	</div>
{/function}

<form class="form-horizontal" method="post" action="{route name="TcExternalApps.save" sAppKey=$appKey}">
	<div class="box-body">

		{assign var=apiKey value=System::d(TsIvvy\Handler\ExternalApp::CONFIG_ACCESS_KEY, '')|escape}
		{assign var=apiSecret value=System::d(TsIvvy\Handler\ExternalApp::CONFIG_ACCESS_SECRET, '')|escape}

		<div class="box-group">
			
            {call displayField label="{"Region"|L10N}" type="select" nameAttr="config[{TsIvvy\Handler\ExternalApp::CONFIG_REGION}]" value="{System::d(TsIvvy\Handler\ExternalApp::CONFIG_REGION, 'UK')|escape}" options=TsIvvy\Api::API_ENDPOINTS}
            {call displayField label="{"Api-Key"|L10N}" type="input" nameAttr="config[{TsIvvy\Handler\ExternalApp::CONFIG_ACCESS_KEY}]" value="{$apiKey}"}
            {call displayField label="{"Api-Secret"|L10N}" type="input" nameAttr="config[{TsIvvy\Handler\ExternalApp::CONFIG_ACCESS_SECRET}]" value="{$apiSecret}"}

			{if !empty($error)}
				<div class="alert alert-danger">
					<i class="icon fa fa-check"></i> {$error}
				</div>
			{/if}

		</div>

		{if !empty($apiKey) && !empty($apiSecret)}

			<div class="box-group">

				{call displayField label="{"Blockierungskategorie (Parkplatz)"|L10N}" type="select" nameAttr="config[{TsIvvy\Handler\ExternalApp::CONFIG_ABSENCE_CATEGORY}]" value="{TsIvvy\Handler\ExternalApp::getAbsenceCategory()|escape}" options=$absenceCategories}
				{call displayField label="{"Aufbauzeit (Minuten)"|L10N}" type="input" nameAttr="config[{TsIvvy\Handler\ExternalApp::CONFIG_SETUP_TIME}]" value="{TsIvvy\Handler\ExternalApp::getSetupTime()|escape}"}
				{call displayField label="{"Abbauzeit (Minuten)"|L10N}" type="input" nameAttr="config[{TsIvvy\Handler\ExternalApp::CONFIG_SETDOWN_TIME}]" value="{TsIvvy\Handler\ExternalApp::getSetdownTime()|escape}"}

				<h3>{'Räume'|L10N}</h3>

				{if !empty($ivvyRooms)}

					<div class="box-group" id="accordion">

						{foreach $fideloRooms as $fideloRoom}

							<div class="panel box {$fideloRoom['box_class']}">
								<div class="box-header with-border">
									<h4 class="box-title">
										<a data-toggle="collapse" data-parent="#accordion" href="#collapse_{$fideloRoom['box_id']}">
											{$fideloRoom['box_title']}
										</a>
									</h4>
								</div>
								<div id="collapse_{$fideloRoom['box_id']}" class="panel-collapse collapse">
									<div class="box-body">

										<div class="row">
										{foreach $fideloRoom['rooms'] as $roomId => $roomName}

											<div class="form-group col-sm-4">
												<label for="config[{$fideloRoom['room_prefix']}{$roomId}]" class="col-sm-4 control-label">
													{$roomName}
												</label>

												<div class="col-sm-8">
													<div class="input-group">

														{assign var=savedValue value=System::d({$fideloRoom['room_prefix']|cat:$roomId}, "")}

														<select name="config[{$fideloRoom['room_prefix']}{$roomId}]" id="config[{$fideloRoom['room_prefix']}{$roomId}]" class="form-control">
															<option value=""></option>
															{foreach $ivvyRooms as $ivvyId => $ivvyName}
																<option value="{$ivvyId}" {if $savedValue == $ivvyId}selected{/if}>{$ivvyName}</option>
															{/foreach}
														</select>
													</div>
												</div>
											</div>
										{/foreach}
										</div>

									</div>
								</div>
							</div>

						{/foreach}

					</div>

				{else}
					<div class="alert alert-danger">
						<i class="icon fa fa-info"></i> {"Es wurden keine Räume in Ivvy gefunden "|L10N}
					</div>
				{/if}

				<h3>{'Benutzer'|L10N}</h3>

				{if !empty($ivvyUsers)}

					<div class="box-group" id="accordion">

						{call displayField label="{"Standardbenutzer"|L10N}" type="select" nameAttr="config[{TsIvvy\Handler\ExternalApp::CONFIG_DEFAULT_USER}]" value="{System::d(TsIvvy\Handler\ExternalApp::CONFIG_DEFAULT_USER, '')|escape}" options=$ivvyUsers}

						<table class="table">

							<tr>
								<th>&nbsp;</th>
								<th>{'Koordinator'|L10N}</th>
							</tr>

							{foreach $fideloUsers as $fideloUserId => $fideloUser}

								<tr>
									<th>{$fideloUser}</th>
									<td>
										{assign var=saved value=System::d(TsIvvy\Handler\ExternalApp::CONFIG_USER|cat:$fideloUserId, "")}

										<select name="config[{TsIvvy\Handler\ExternalApp::CONFIG_USER}{$fideloUserId}]" id="config[{TsIvvy\Handler\ExternalApp::CONFIG_USER}{$fideloUserId}]" class="form-control">
											<option value=""></option>
											{foreach $ivvyUsers as $ivvyId => $ivvyName}
												<option value="{$ivvyId}" {if $saved == $ivvyId}selected{/if}>{$ivvyName}</option>
											{/foreach}
										</select>
									</td>
								</tr>

							{/foreach}

						</table>

					</div>

				{else}
					<div class="alert alert-danger">
						<i class="icon fa fa-info"></i> {"Es wurden keine Benutzer in Ivvy gefunden "|L10N}
					</div>
				{/if}

			</div>

		{else}
			<div class="alert alert-info">
				<i class="icon fa fa-info"></i> {"Bitte speichern Sie zuerst die Zugangsdaten"|L10N}
			</div>
		{/if}

	</div>
	<!-- /.box-body -->
	<div class="box-footer">
		<a href="{route name="TcExternalApps.list"}" class="btn btn-default">{'Zurück'|L10N}</a>
		<button type="submit" class="btn btn-primary pull-right">{'Speichern'|L10N}</button>
	</div>
	<!-- /.box-footer -->

</form>


{if !empty($apiKey) && !empty($apiSecret)}
	</div>

	<div class="box box-default">
		<div class="box-header with-border">
			<h3 class="box-title">{'Synchronisation'|L10N}</h3>
		</div>
		<form class="form-horizontal" method="post" action="{route name="TsIvvy.app.sync"}">
			<div class="box-body">
				{call displayField label="{"Zeitraum"|L10N}" type="select" nameAttr="timeframe" options=['last_month' => {"Letzer Monat"|L10N}, 'last_3_months' => {"Letzten 3 Monate"|L10N}, 'last_6_months' => {"Letzten 6 Monate"|L10N}]}
			</div>
			<div class="box-footer">
				<button type="submit" class="btn btn-primary pull-right">{'Synchronisieren'|L10N}</button>
			</div>
		</form>
	</div>
{/if}

