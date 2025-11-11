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

		<div class="box-group">
			
            {call displayField label="{"URL"|L10N}" type="input" nameAttr="config[{$url_prefix}]" value="{$url|escape}"}
            {call displayField label="{"Client-Token"|L10N}" type="input" nameAttr="config[{$client_token_prefix}]" value="{$clientToken|escape}"}
            {call displayField label="{"Access-Token"|L10N}" type="input" nameAttr="config[{$access_token_prefix}]" value="{$accessToken|escape}"}

		</div>

		<div class="box-group">
			{call displayField label="{"Blockierungskategorie"|L10N}" type="select" nameAttr="config[{$absence_category_prefix}]" value="{$absence_category_id|escape}" options=$absence_categories}
			{call displayField label="{"Service-ID (stay)"|L10N}" type="input" nameAttr="config[{$service_id_prefix}]" value="{$service_id|escape}"}
			{call displayField label="{"Rate-ID"|L10N}" type="input" nameAttr="config[{$rate_id_prefix}]" value="{$rate_id|escape}"}
		</div>

		<div class="box-group">

			<h3>{'Zimmer'|L10N}</h3>

			{foreach $room_types as $room_type}
				{call displayField label=$room_type['name'] type="input" nameAttr="config[{$room_type_prefix}{$room_type['id']}]" value="{$room_type['value']|escape}"}
			{/foreach}
		</div>

		<div class="box-group" id="accordion">

			{foreach $categories as $category}
				<div class="panel box box-default">
					<div class="box-header with-border">
						<h4 class="box-title">
							<input type="hidden" name="config[{$category_prefix}{$category['id']}]" value="0">
							<input type="checkbox" name="config[{$category_prefix}{$category['id']}]" value="1" {if $category['value'] == 1}checked{/if}>
							<a data-toggle="collapse" data-parent="#accordion" href="#collapse_{$category['id']}">
								{$category['name']}
							</a>
						</h4>
					</div>
					<div id="collapse_{$category['id']}" class="panel-collapse collapse">
						<div class="box-body">
							{foreach $providers[$category['id']] as $provider}

								<div class="row">
									<div class="form-group">
										<label for="[{$provider_prefix}{$provider['id']}]" class="col-sm-2 control-label">
											<input type="hidden" name="config[{$provider_prefix}{$provider['id']}]" value="0">
											<input type="checkbox" name="config[{$provider_prefix}{$provider['id']}]" value="1" {if $provider['value'] == 1}checked{/if}>
											{$provider['name']}
										</label>

										<div i class="col-sm-10">
											{foreach $provider['rooms'] as $room}
												<div class="col-sm-3">
													<div class="input-group">
														<span class="input-group-addon">
														  {'Raum'|L10N} {$room['name']}
														</span>
														<input type="text" name="config[{$room_prefix}{$provider['id']}_{$room['id']}]" class="form-control" id="config[{$room_prefix}{$provider['id']}_{$room['id']}]" value="{$room['value']}">
													</div>
												</div>
											{/foreach}
										</div>
									</div>
								</div>
							{/foreach}
						</div>
					</div>
				</div>
			{/foreach}

		</div>

	</div>
	<!-- /.box-body -->
	<div class="box-footer">
		<a href="{route name="TcExternalApps.list"}" class="btn btn-default">{'Zurück'|L10N}</a>
		<button type="submit" class="btn btn-primary pull-right">{'Speichern'|L10N}</button>
	</div>
	<!-- /.box-footer -->
</form>
