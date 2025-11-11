{extends file="system/bundles/AdminLte/Resources/views/base.tpl"}

{block name="header"}
	<style type="text/css">
		.bootstrap-tagsinput {
			min-width: 100%;
			padding: 3px 4px 6px 4px;
			line-height: 23px;
		}
		.bootstrap-tagsinput .label {
			font-size: 13px;
			font-weight: normal;
		}
	</style>
	<link rel="stylesheet" href="/assets-public/ts/css/bootstrap-multiselect.css">
	<link rel="stylesheet" href="/assets/adminlte/components/datatables.net-bs/css/dataTables.bootstrap.min.css">
{/block}

{block name="content"}
		<script>
			var sClass = '{$sClass|escape:"javascript"}';
			var iId = '{$iId|escape:"javascript"}';
		</script>

		<!-- Content Header (Page header) -->
		<section class="content-header">
		  <h1>
			{'Vertriebsmitarbeiter'|L10N} "{$sUser}"
		  </h1>
		</section>
		
		<!-- Main content -->
		<section class="content">

			{if $bSaved}
			<div class="alert alert-success alert-dismissible">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
				{'Ihre Änderungen wurden erfolgreich gespeichert!'|L10N}
			</div>
			{/if}
			
			<div class="box">
				<form method="post" id="salesperson_form" action="/wdmvc/ts/salesperson/setup" class="form-horizontal">
					<input type="hidden" id="task" name="task" value="save">
					<input type="hidden" id="id" name="id" value="{$iId|escape}">
					<input type="hidden" id="remove" name="remove_setting" value="">
					<div class="box-body pad">

						{foreach $aSalesPersonSettings as $iSettingId => $aSalesPersonSetting}

							<div class="box box-setting">
								<div class="box-header with-border">
									<h3 class="box-title"></h3>
									<div class="box-tools pull-right">
										<button type="submit" class="btn btn-box-tool remove_settings" data-setting="{$iSettingId}"><i class="fa fa-times"></i></button>
									</div>
									<!-- /.box-tools -->
								</div>
								<!-- /.box-header -->
								<div class="box-body">

									<div class="form-group">
										<label class="col-sm-2 control-label">{'Schulen'|L10N}</label>
										<div class="col-sm-10">
											<select id="schools_{$iSettingId}" multiple class="form-control multiselect" name="schools[{$iSettingId}][]">
												{if isset($aSchools)}
													{foreach $aSchools as $iSchoolId => $sSchoolName}
														<option value="{$iSchoolId}" {if isset($aSalesPersonSetting['schools'][$iSchoolId])} selected="selected" {/if}>{$sSchoolName}</option>
													{/foreach}
												{/if}
											</select>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-2 control-label">{'Agenturen'|L10N}</label>
										<div class="col-sm-10">
											<input id="agencies_{$iSettingId}" name="agencies[{$iSettingId}]" class="agencies" type="text" value="">
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-2 control-label">{'Nationalitäten'|L10N}</label>
										<div class="col-sm-10">
											<input id="nationalities_{$iSettingId}" name="nationalities[{$iSettingId}]" class="nationalities" type="text" value="">
										</div>
									</div>
								</div>
								<!-- /.box-body -->
							</div>

						{/foreach}

					</div>
					<div class="box-footer">
						<button id="add_new_setting" type="submit" class="btn btn-default pull-left">{'Einstellung hinzufügen'|L10N}</button>
						<button type="submit" class="btn btn-primary pull-right">{'Speichern'|L10N}</button>
					</div>
				</form>
			</div>
					
			<div id="box-agencies" class="box box-default collapsed-box">
				<div class="box-header with-border">
					<h3 class="box-title">{'Übersicht Agenturen'|L10N}</h3>

					<div class="box-tools pull-right">
					  <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i>
					  </button>
					</div>
					<!-- /.box-tools -->
				</div>
				<!-- /.box-header -->
				<div id="container-agencies" class="box-body table-responsive" style="display: none;">
					&nbsp;<div class="overlay">
						<i class="fa fa-refresh fa-spin"></i>
					</div>
				</div>
				<!-- /.box-body -->
			</div>		
					
			<div id="box-nationalities" class="box box-default collapsed-box">
				<div class="box-header with-border">
					<h3 class="box-title">{'Übersicht Nationalitäten'|L10N}</h3>

					<div class="box-tools pull-right">
					  <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i>
					  </button>
					</div>
					<!-- /.box-tools -->
				</div>
				<!-- /.box-header -->
				<div id="container-nationalities" class="box-body table-responsive" style="display: none;">
					&nbsp;<div class="overlay">
						<i class="fa fa-refresh fa-spin"></i>
					</div>
				</div>
				<!-- /.box-body -->
			</div>		

		</section>
{/block}
					
{block name="footer"}
	<script src="/assets-public/ts/js/bootstrap-multiselect.js"></script>
	<script src="/assets/filemanager/js/bootstrap-tagsinput.js"></script>
	<script src="/assets/typeahead/bootstrap3-typeahead.js"></script>
	<script src="/assets/adminlte/components/datatables.net/js/jquery.dataTables.min.js"></script>
	<script src="/assets/adminlte/components/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
	<script>

		$(function() {

			$('.multiselect').multiselect(
				{
					allSelectedText: '{'Alle ausgewählt'|L10N}',
					nonSelectedText: '{'Keine ausgewählt'|L10N}'
				}
			);

			$('.remove_settings').click(function() {
				var oTaskInput = $('#task');
				oTaskInput.val('remove-setting');
				var input = $('#remove');
				input.val($(this).data('setting'));
			});

			$('#add_new_setting').click(function() {
				var input = $('#task');
				input.val('add_settings');
			});

			$('#salesperson_form').on('keyup keypress', function(e) {
				var keyCode = e.keyCode || e.which;
				if (keyCode === 13) {
					e.preventDefault();
					return false;
				}
			});
			{foreach $aSalesPersonSettings as $iSettingId => $aSalesPersonSetting}
				$('#agencies_{$iSettingId}').tagsinput({
					tagClass: 'tag label label-primary',
					typeahead: {
						items: 12,
						source: function(sQuery) {
							var aSchoolIds = $('#schools_{$iSettingId}').val();
							aSchoolIds = aSchoolIds.join(',');
							return $.get('/wdmvc/ts/salesperson/agencies?user_id={$iId}&setting_id={$iSettingId}&schools='+aSchoolIds+'&query='+sQuery);
						},
						displayText: function(mItem) {
							if($.type(mItem) === "string") {
								return mItem;
							} else {
								return mItem.text;
							}
						},
						afterSelect: function(value) {
							this.$element.val('');
						}
					},
					itemValue: 'value',
					itemText: 'text',
					freeInput: false
				});

				{foreach $aSalesPersonSetting['agencies'] as $iAgencyId=>$sAgency}
					$('#agencies_{$iSettingId}').tagsinput('add', { "value": "{$iAgencyId}" , "text": "{$sAgency}"});
				{/foreach}

				$('#nationalities_{$iSettingId}').tagsinput({
					tagClass: 'tag label label-primary',
					typeahead: {
						items: 12,
						source: function(sQuery) {
							var aSchoolIds = $('#schools_{$iSettingId}').val();
							aSchoolIds = aSchoolIds.join(',');
							return $.get('/wdmvc/ts/salesperson/nationalities?user_id={$iId}&setting_id={$iSettingId}&schools='+aSchoolIds+'&query='+sQuery);
						},
						displayText: function(mItem) {
							if($.type(mItem) === "string") {
								return mItem;
							} else {
								return mItem.text;
							}
						},
						afterSelect: function(value) {
							this.$element.val('');
						}
					},
					itemValue: 'value',
					itemText: 'text',
					freeInput: false
				});
				{foreach $aSalesPersonSetting['nationalities'] as $sCountryIso => $sCountry}
					$('#nationalities_{$iSettingId}').tagsinput('add', { "value": "{$sCountryIso}" , "text": "{$sCountry}"});
				{/foreach}
			{/foreach}

			$('#box-agencies, #box-nationalities').on('expanded.boxwidget', function() {

				var oBox = $(this);

				var sType = oBox.prop('id').replace(/box\-/, '');

				var oContainer = $('#container-'+sType);

				oContainer.load('/wdmvc/ts/salesperson/overview/' + sType);

			});

			$('#box-agencies, #box-nationalities').on('collapsed.boxwidget', function() {

				var oBox = $(this);

				var sType = oBox.prop('id').replace(/box\-/, '');

				var oContainer = $('#container-'+sType);

				oContainer.html('&nbsp;<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>');

			});

		});

	</script>
{/block}