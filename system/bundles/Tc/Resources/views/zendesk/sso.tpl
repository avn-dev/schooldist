{extends file="system/bundles/AdminLte/Resources/views/base.tpl"}

{block name="content"}

	<!-- Content Header (Page header) -->
		<section class="content-header">
		  <h1>
			{'Unterstützung'|L10N}
		  </h1>
		</section>

		<!-- Main content -->
		<section class="content">
			<div class="box">
	{if $bMissingName}

				<form method="post">

					<div class="box-body">

						<p>{'Bitte überprüfen Sie hier noch einmal die im System gespeicherte Firmenbezeichnung und ändern Sie diese gegebenenfalls:'|L10N}</p>

						<div class="form-group">
							<label for="zendesk_organization" class="col-sm-2 control-label">{'Firmenname'|L10N}</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="zendesk_organization" name="organization" placeholder="Firmenname" value="{$sClientName|escape}">
							</div>
						</div>

					</div>
					<!-- /.box-body -->
					<div class="box-footer">
						<button type="submit" class="btn btn-info pull-right">{'Speichern'|L10N}</button>
					</div>
					<!-- /.box-footer -->
				</form>

	{elseif !empty($aErrors)}

			
				<div class="box-body pad">

					<div class="callout callout-danger">
						<h4>{'ZenDesk - Error'|L10N}</h4>

						<p>{'Folgende Fehler sind während der Verbindung zu ZenDesk aufgetreten:'|L10N}</p>
						<ul>
							{foreach $aErrors as $sError}	
									{$sError}
							{/foreach}
						</ul>
					</div>

				</div>
			
	{/if}

			</div>
			
		</section>
{/block}