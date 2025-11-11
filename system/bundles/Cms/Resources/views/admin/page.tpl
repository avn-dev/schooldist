<link rel="stylesheet" href="/assets/cms/css/admin.css">

{assign var=sContainerId value='cms-page-container-'|cat:\Util::generateRandomString(4)}

<div class="modal fade" tabindex="-1" role="dialog" id="cms-modal">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-body" style="padding:0;">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="position: absolute;right: 16px;top: 9px;"><span aria-hidden="true">&times;</span></button>
				<iframe id="cms-modal-iframe" src="about:blank" style="width:100%;"></iframe>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<section class="content" id="{$sContainerId}">
	<div class="row form-inline">
		<div class="col-sm-4 col-md-2 cms-page-tree">
			<div class="toolbar">
				<div class="form-group">
					<select class="site_id form-control">
						{html_options options=$aSites selected=$iSiteId}
						<option disabled>──────────</option>
						<option value="0">{'Seitenvorlagen'|L10N}</option>
					</select>
				</div>
				<div class="form-group">
					<select class="language form-control">
					</select>
				</div>
			</div>
			<div class="page-tree"></div>
		</div>
		<div class="col-sm-8 col-md-10 cms-page-edit">
			<div class="nav-tabs-custom">
				<ul class="nav nav-tabs">
					<li><a href="javascript:void(null);" class="text-muted toggle-page-tree" title="{'Sitemap ein-/ausklappen'|L10N}"><i class="fa fa-bars"></i></a></li>
					<li><a href="javascript:void(null);" class="cms-index" title="{'Übersicht öffnen'|L10N}"><i class="fa fa-home"></i></a></li>
					<li><a href="javascript:void(null);" class="cms-sitemap" title="{'Seitenverwaltung öffnen'|L10N}"><i class="fa fa-sitemap"></i></a></li>
					<li><a href="javascript:void(null);" class="cms-add-page" title="{'Neue Seite erstellen'|L10N}"><i class="fa fa-plus"></i></a></li>
					<li class="page-action"><a href="#mode_live" data-mode="live" data-toggle="tab">{'Online'|L10N}</a></li>
					<li class="page-action"><a href="#mode_preview" data-mode="preview" data-toggle="tab">{'Vorschau'|L10N}</a></li>
					<li class="page-action"><a href="#mode_edit" data-mode="edit" data-toggle="tab">{'Editieren'|L10N}</a></li>
					<li class="page-action"><a href="#mode_settings" data-mode="settings" data-toggle="tab">{'Eigenschaften'|L10N}</a></li>
					<li class="page-action"><a href="#mode_structure" data-mode="structure" data-toggle="tab">{'Struktur'|L10N}</a></li>
					<li id="publish_accept" class="publish-btn accept pull-right" style="display:none;"><a href="javascript:void(null);"><i class="fa fa-check" style="color:green;"></i> {'Freischalten'|L10N}</a></li>
					<li id="publish_deny" class="publish-btn deny pull-right" style="display:none;"><a href="javascript:void(null);"><i class="fa fa-close" style="color:red;"></i> {'Ablehnen'|L10N}</a></li>
					<li id="publish_nochanges" class="publish-btn nochanges pull-right" style="display:none;"><a href="javascript:void(null);" disabled><i class="fa fa-info"></i> {'Keine Änderungen zum Freischalten vorhanden'|L10N}</a></li>
					<li id="publish_changes" class="publish-btn changes pull-right" style="display:none;"><a href="javascript:void(null);" disabled><i class="fa fa-info"></i> {'Änderungen zum Freischalten vorhanden'|L10N}</a></li>
				  {*<li class="dropdown">
					<a class="dropdown-toggle" data-toggle="dropdown" href="#">
					  Dropdown <span class="caret"></span>
					</a>
					<ul class="dropdown-menu">
					  <li role="presentation"><a role="menuitem" tabindex="-1" href="#">Action</a></li>
					  <li role="presentation"><a role="menuitem" tabindex="-1" href="#">Another action</a></li>
					  <li role="presentation"><a role="menuitem" tabindex="-1" href="#">Something else here</a></li>
					  <li role="presentation" class="divider"></li>
					  <li role="presentation"><a role="menuitem" tabindex="-1" href="#">Separated link</a></li>
					</ul>
				  </li>
				  <li><a href="#" class="text-muted"><i class="fa fa-gear"></i></a></li>*}
				</ul>

				<!-- /.tab-content -->
			</div>
			<iframe name="cms-page-frame" class="cms-page-frame" src="/admin/extensions/cms/edit_index.html" style="width:100%"></iframe>
		</div>
	</div>
</section>

<script src="/assets/cms/js/bootstrap-treeview.min.js?v={\System::d('version')}"></script>
<script src="/assets/cms/js/page.js?v={\System::d('version')}"></script>

<script>
	Page.initialize($('#{$sContainerId}'));
</script>