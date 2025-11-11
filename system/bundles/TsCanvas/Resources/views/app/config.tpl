{extends file="system/bundles/AdminLte/Resources/views/base.tpl"}

{block name="content"}
	<section class="content">
		
		{foreach $oSession->getFlashBag()->get('error', array()) as $sMessage}
			<div class="alert alert-danger alert-dismissible">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
				<i class="icon fa fa-check"></i> {$sMessage}
			</div>
		{/foreach}
		
		<div class="box box-default">
			<div class="box-header with-border">
				<h3 class="box-title">{'Canvas'|L10N}</h3>
			</div>
			<div class="box-body">

				<form class="form-horizontal" action="{route name="admin_canvas_activate_save"}">
					<div class="box-body">
						<div class="form-group">
							<label for="canvas_url" class="col-sm-2 control-label">{'URL'|L10N}</label>
							<div class="col-sm-10">
								<input type="text" name="canvas_url" class="form-control" id="canvas_url" placeholder="{'z.B.'|L10N} https://your_domain.instructure.com/api/v1" value="{$sUrl}">
							</div>
						</div>
						<div class="form-group">
							<label for="canvas_token" class="col-sm-2 control-label">{'Access Token'|L10N}</label>

							<div class="col-sm-10">
								<input type="text" name="canvas_token" class="form-control" id="canvas_token" value="{$sAccessToken}">
							</div>
						</div>

					</div>
					<!-- /.box-body -->
					<div class="box-footer">
						<a href="{route name="ts_external_apps"}" class="btn btn-default">{'Zurück'|L10N}</a>
						<button type="submit" class="btn btn-info pull-right">{'Speichern'|L10N}</button>
					</div>
					<!-- /.box-footer -->
				</form>

			</div>
		</div>

    </section>
{/block}

{block name="footer"}
    
{/block}