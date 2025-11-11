{extends file="system/bundles/AdminLte/Resources/views/base.tpl"}

{block name="header"}

	<link rel="stylesheet" href="/admin/assets/css/admin.css">
	<link rel="stylesheet" href="/external-apps/resources/css/main.css">
	
{/block}

{block name="content"}
	
	<div id="externalApps">
	
		<div class="col-md-2 col-xs-12 categories-container">
			<div class="search">
				<input type="text" class="form-control" placeholder="{'Suche'|L10N}" v-model="search_string" @keyup="search" :disabled="disabled" />
			</div>
			<ul class="categories">
				<li v-for="category in categories" :key="category.key" v-bind:class="{literal}{'btn-primary': category.key === active_category}{/literal}" @click="loadCategory(category.key)">
					{{ category.title }}
				</li>
			</ul>	
		</div>

		<div class="col-md-10 col-xs-12 apps-container">
			<div class="row my-apps">				
				<fieldset>
					<legend class="my-apps-legend" @click="toggleMyAppsContainer">
						<i v-if="my_apps_open" class="fa fa-angle-down"></i>
						<i v-else class="fa fa-angle-right"></i>
						{'Installierte Apps'|L10N} ({{ my_apps.length }})
					</legend>
					<div v-if="my_apps_open">
						<p v-if="my_apps.length === 0" class="loading-message">{'Es wurden noch keine Apps installiert'|L10N}</p>
						<App v-else v-for="app in my_apps"  :key="app.key"
							v-bind:app="app"
							v-bind:categories="categories"
							v-bind:disabled="disabled"
							v-bind:l10n="l10n"
							v-on:action="appAction"
						>
						</App>
					</div>
				</fieldset>
			</div>
			<div class="row apps">
				<fieldset>
					<legend>{'Verfügbare Apps'|L10N}</legend>
					<div v-if="loading" class="loading"><i class="fa fa-spinner fa-pulse"></i></div>
					<p v-else-if="apps.length === 0" class="loading-message">{'Es wurden keine Apps gefunden'|L10N}</p>

					<App v-else v-for="app in apps" :key="app.key"
						v-bind:app="app"
						v-bind:categories="categories"
						v-bind:disabled="disabled"
						v-bind:l10n="l10n"
						v-on:action="appAction"
					>							
					</App>
				</fieldset>
				<!-- ./col -->
			</div>
			<!-- /.box -->
		</div>

		<div id="AppModal" class="modal fade" tabindex="-1" role="dialog">
			<div class="modal-dialog" role="document">
			  <div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" aria-label="Close" @click="closeModal"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title">{'App Informationen'|L10N}</h4>
					</div>
					<div class="modal-body system-message">
						<div class="message-title media">
							<div class="media-left">
								<i :class="getSelectedAppProperty('icon')"></i>
							</div>
							<div class="media-body">
								<h3>{{ getSelectedAppProperty('title') }}</h3>
								<span>{{ getSelectedAppProperty('category') }}</span>
							</div>
						</div>
						<p class="message-description" v-html="getSelectedAppProperty('description')"></p>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" @click="closeModal">{'Zurück'|L10N}</button>
						<button v-if="selected_app && selected_app.id === null" type="button" class="btn btn-success" @click="installAppFromModal">
							<i v-if="selected_app && selected_app.processing === 'install'" class="fa fa-spinner fa-pulse"></i> 
							{{ l10n.action.install }}
						</button>
					</div>
				  </div><!-- /.modal-content -->
			</div><!-- /.modal-dialog -->
		</div><!-- /.modal -->

		<div id="ErrorModal" class="modal modal-danger fade">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title">{'Es ist ein Fehler aufgetreten'|L10N}</h4>
					</div>
					<div class="modal-body">
						<p>{{ error_message }}</p>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-outline pull-left" data-dismiss="modal">{{ l10n.action.close }}</button>
					</div>
				</div>
				<!-- /.modal-content -->
			</div>
			<!-- /.modal-dialog -->
		</div>

	</div>
								
{/block}

{block name="footer"}

	<script>
		const categories = {$aCategories|@json_encode};
		const routes = {$aRoutes|@json_encode};
		const my_apps = {$aMyApps|@json_encode};
		const apps = {$aApps|@json_encode};
		const l10n = {$aL10n|@json_encode};
	</script>
	
	<script src="https://cdn.jsdelivr.net/npm/vue@2.5.17/dist/vue.js"></script>
	<script src="/external-apps/resources/js/application.js"></script>
	
{/block}
