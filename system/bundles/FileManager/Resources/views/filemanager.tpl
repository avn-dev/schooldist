{extends file="system/bundles/AdminLte/Resources/views/base.tpl"}

{block name="header"}
		<!-- Dropzone -->
		<link rel="stylesheet" href="/assets/dropzone/dropzone.min.css">
		<!-- Tags Input -->
		<link rel="stylesheet" href="/assets/filemanager/css/bootstrap-tagsinput.css">
		<!-- Overlay -->
		<link rel="stylesheet" href="/assets/filemanager/css/overlay.css">
{/block}

{block name="content"}
		
		<script>
			var sClass = '{$sClass|escape:"javascript"}';
			var iId = '{$iId|escape:"javascript"}';
		</script>

		<!-- Content Header (Page header) -->
		<section class="content-header">
		  <h1>
			{'Dateiverwaltung'|L10N}
			<small>{$oEntity->getName()}</small>
		  </h1>
		</section>

		<!-- Main content -->
		<section class="content">

			<div class="row">
				<div class="col-md-6">
					<div class="box box-default collapsed-box">
						<div class="box-header with-border">
						  <h3 class="box-title">{'Hochladen'|L10N}</h3>

						  <div class="box-tools pull-right">
							<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i>
							</button>
						  </div>
						  <!-- /.box-tools -->
						</div>
						<!-- /.box-header -->
						<div class="box-body">
							<form action="/file-manager/interface/save" class="dropzone" id="filemanager-dropzone" style="border: 0;">
								<input type="hidden" name="entity" value="{$sClass|escape}">
								<input type="hidden" name="id" value="{$iId|escape}">
								<div class="dz-message needsclick">
									{'Ziehen Sie Dateien in dieses Feld oder klicken Sie hier, um Dateien hochzuladen.'|L10N}
								</div>
							</form>
						</div>
						<!-- /.box-body -->
					</div>
				</div>
				<div class="col-md-6">
					<div id="tagsinput-container" class="box box-default collapsed-box">
						<div class="box-header with-border">
						  <h3 class="box-title">{'Verfügbare Tags'|L10N}</h3>

						  <div class="box-tools pull-right">
							<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i>
							</button>
						  </div>
						  <!-- /.box-tools -->
						</div>
						<!-- /.box-header -->
						<div class="box-body" style="display: none;">
							<select id="tagsinput-values" multiple data-role="tagsinput">
								{html_options values=$aTags output=$aTags}
							</select>
							<button id="tagsinput-save" class="btn btn-default">{'Speichern'|L10N}</button>
						</div>
						<!-- /.box-body -->
						<div id="tagsinput-loading" class="overlay" style="display: none;">
							<i class="fa fa-refresh fa-spin"></i>
						</div>
					</div>
				</div>
			</div>

			<div class="box box-default">
				<div class="box-header with-border">
				  <h3 class="box-title">{'Dateien'|L10N}</h3>

				  <div class="box-tools pull-right">
					<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
					</button>
				  </div>
				  <!-- /.box-tools -->
				</div>
				<!-- /.box-header -->
				<div class="box-body kom-element-overlay">

					<ul class="mailbox-attachments clearfix" id="filemanager-container">
						
					</ul>

				</div>
				<!-- /.box-body -->
				<div id="container-loading" class="overlay" style="display: none;">
					<i class="fa fa-refresh fa-spin"></i>
				</div>
			</div>

			<div id="form-template">

				<div class="form-box">
					
					<form>
						<div class="nav-tabs-custom">
							<ul class="nav nav-tabs">
								{foreach $aLanguages as $sIso=>$sLanguage}
								<li class="{if $sLanguage@index == 0}active{/if}"><a href="#tab_{$sIso}" data-toggle="tab"><img src="{\Util::getFlagIcon($sIso)}"></a></li>
								{/foreach}
							</ul>
							<div class="tab-content">
								{foreach $aLanguages as $sIso=>$sLanguage}
								<div class="tab-pane {if $sLanguage@index == 0}active{/if}" id="tab_{$sIso}">
									<div class="form-group">
										<input type="text" name="title[{$sIso}]" class="input-title form-control input-sm" placeholder="{'Titel'|L10N}">
									</div>
									<div class="form-group">
										<textarea name="description[{$sIso}]" class="input-description form-control input-sm" placeholder="{'Beschreibung'|L10N}"></textarea>
									</div>
									<div class="form-group">
										<input type="text" name="source[{$sIso}]" class="input-source form-control input-sm" placeholder="{'Bildquelle'|L10N}">
									</div>

									<div class="form-group pull-right">
										<button class="cancel btn btn-default btn-xs">{'Abbrechen'|L10N}</button>
										<button class="save btn btn-primary btn-xs">{'Speichern'|L10N}</button>
									</div>

								</div>
								{/foreach}
							</div>
							<!-- /.tab-content -->
						</div>
					</form>
				</div>
			</div>

		</section>

		<script>
		
			var oTranslations = {
				delete_confirm: '{'Möchten Sie diese Datei wirklich löschen?'|L10N}',
			};
	
		</script>
{/block}
					
{block name="footer"}
		<script src="/assets/adminlte/components/jquery-ui/jquery-ui.min.js?v={\System::d('version')}"></script>
		<!-- Dropzone -->
		<script src="/assets/dropzone/dropzone.min.js?v={\System::d('version')}"></script>
		<!-- Tags Input -->
		<script src="/assets/filemanager/js/bootstrap-tagsinput.min.js?v={\System::d('version')}"></script>
		<!-- Filemanager -->
		<script src="/assets/filemanager/js/filemanager.js?v={\System::d('version')}"></script>
{/block}
