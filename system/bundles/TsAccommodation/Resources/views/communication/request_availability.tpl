
{if $providers}

	<style>
		.box-filter .box-header label {
			font-weight: normal;
		}
	</style>
	
	{if $inquiryAccommodation->comment || $matching->acc_allergies}
	<div class="box">
		<div class="box-header">
			<h3 class="box-title">{'Informationen'|L10N:$l10n_path}</h3>
		</div>

		<div class="box-body">
			<dl>
			{if $inquiryAccommodation->comment}
				<dt>{'Kommentar'|L10N:$l10n_path}</dt>
				<dd>{$inquiryAccommodation->comment|nl2br}</dd>
			{/if}
			{if $matching->acc_allergies}
				<dt>{'Allergien'|L10N:$l10n_path}</dt>
				<dd>{$matching->acc_allergies|nl2br}</dd>
			{/if}
			</dl>
		</div>
					
	</div>
	{/if}

	<div class="box">
		<div class="box-header">
			<h3 class="box-title">{'Kommunikation'|L10N:$l10n_path}</h3>
		</div>

		<div class="box-body">
			
			{if $templateOptions}
			
			<div class="form-group">
				<label for="template_id" class="col-sm-2 control-label">{'Vorlage'|L10N:$l10n_path}</label>
				<div class="col-sm-10">
					<select class="form-control" id="template_id" name="template_id">
						  {html_options options=$templateOptions}
					</select>						
				</div>
			</div>
					
			{else}
				
				<div class="callout callout-danger">
				<p>{'Es konnte keine entsprechende E-Mail-Vorlage gefunden werden. Bitte legen Sie zunächst eine Vorlage an.'|L10N:$l10n_path}</p>
				</div>
				
			{/if}
		</div>
					
	</div>

	<div class="box box-filter">
		<div class="box-header">
			<h3 class="box-title">{'Passende Unterkunftsanbieter'|L10N:$l10n_path}</h3>
			<div class="box-tools form-inline">
				<label><input type="checkbox" id="ignore_roomtype" name="ignore_roomtype" class="filter-backend" value="1" {if $ignore_roomtype}checked{/if}> {'Raumbuchung ignorieren'|L10N:$l10n_path}</label>
				<label><input type="checkbox" id="ignore_category" name="ignore_category" class="filter-backend" value="1" {if $ignore_category}checked{/if}> {'Gebuchte Kategorie ignorieren'|L10N:$l10n_path}</label>
				<label><input type="checkbox" id="ignore_requirement" name="ignore_requirement" class="filter-frontend" value="1" {if $ignore_requirement}checked{/if}> {'Dokumentgültigkeit ignorieren'|L10N:$l10n_path}</label>
				
				<div class="input-group input-group-sm hidden-xs" style="width: 150px;">
					<input type="text" id="provider_search" name="provider_search" class="form-control pull-right" value="{$provider_search|escape}" placeholder="{'Suche'|L10N:$l10n_path}">
					<div class="input-group-btn">
						<button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
					</div>
				</div>
			</div>
		</div>

		<div class="box-body table-responsive no-padding">
			<table id="provider_table" class="table table-hover">
				<thead>
					<tr>
						<th style="width:30px;"><input type="checkbox" id="provider-checkbox-checkall"></th>
						<th style="width:auto;">{'Anbieter'|L10N:$l10n_path}</th>
						<th style="width:200px;">{'Kommentar'|L10N:$l10n_path}</th>
						<th style="width:130px;">{'Angefragt'|L10N:$l10n_path}</th>
						<th style="width:130px;">{'Abgesagt'|L10N:$l10n_path}</th>
						<th style="width:130px;">{'Bestätigt'|L10N:$l10n_path}</th>
					</tr>
				</thead>
				<tbody>

				{foreach $providers as $provider}
					<tr class="{if $provider.requirement_missing || $provider.requirement_expired}requirement-invalid{/if}" {if $provider.requirement_missing || $provider.requirement_expired}style="display:none;"{/if}>
						<td>
							{if empty($provider.email)}
							<input type="checkbox" disabled title="{'Keine E-Mail-Adresse hinterlegt'|L10N:$l10n_path}">
							{else}
							<input type="checkbox" class="provider-checkbox" name="providers[]" value="{$provider.id}">
							{/if}
						</td>
						<td class="{if $provider.requirement_missing || $provider.requirement_expired}bg-danger{/if}">{$provider.ext_33} {if $provider.email}({$provider.email}){/if}</td>
						<td class="overflow: hidden;white-space: nowrap;text-overflow: ellipsis;">{$provider.ext_34|nl2br}</td>
						<td class="{if $providerStatus[$provider.id]['sent']}bg-info{/if}">{if $providerStatus[$provider.id]['sent']}{$providerStatus[$provider.id]['sent']|date_format:'%x %X'}{/if}</td>
						<td class="{if $providerStatus[$provider.id]['rejected']}bg-danger{/if}">{if $providerStatus[$provider.id]['rejected']}{$providerStatus[$provider.id]['rejected']|date_format:'%x %X'}{/if}</td>
						<td class="{if $providerStatus[$provider.id]['accepted']}bg-success{/if}">{if $providerStatus[$provider.id]['accepted']}{$providerStatus[$provider.id]['accepted']|date_format:'%x %X'}{/if}</td>
					</tr>
				{/foreach}
	
				</tbody>
			</table>
		</div>

	</div>

{else}
	
	<div class="callout callout-warning">
		<p>{'Es konnten keine passenden Unterkunftsanbieter ermittelt werden.'|L10N:$l10n_path}</p>
	</div>

{/if}

