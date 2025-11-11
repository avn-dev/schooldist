Vue.component('App', {
	template: `
		<div class="col-xs-12 col-md-6 col-lg-3">
			<div class="box box-solid">

				<i class="fa fa-info-circle info-icon" @click="action('modal')"></i>

				<!-- /.box-header -->
				<div class="box-body system-message">
					<div class="message-title media">
						<div class="media-left">
							<i :class="app.icon"></i>
						</div>
						<div class="media-body">
							<h3>{{ app.title }}</h3>
							<span>{{ getCategory(app.category) }}</span>
						</div>
					</div>
					<p class="message-description">{{ app.description_short }}</p>
				</div>
				<div class="box-footer">
					<div v-if="app.price !== null" class="pull-left price">
						<strong>{{ app.price }}</strong>
					</div>
					<div class="pull-right">
						<div v-if="app.id !== null">	
							<a v-bind:href="app.route" class="btn btn-sm btn-default" :disabled="disabled" :title="l10n.action.edit" @click="action('edit')">
								<i v-if="processing === 'edit'" class="fa fa-spinner fa-pulse"></i> 
								<i class="fa fa-cog"></i>
							</a>
							<button type="button" class="btn btn-sm btn-danger" :disabled="disabled" :title="l10n.action.delete" @click="action('delete')">
								<i v-if="processing === 'delete'" class="fa fa-spinner fa-pulse"></i> 
								<i class="fa fa-minus-circle"></i>
							</button>
						</div>
						<div v-else>
							<button type="button" class="btn btn-sm btn-success" :disabled="disabled" :title="l10n.action.install" @click="action('install')">
								<i v-if="processing === 'install'" class="fa fa-spinner fa-pulse"></i> 
								<i class="fa fa-plus-circle"></i>
							</button>
						</div>
					</div>
				</div>
				<!-- /.box-body -->
			</div>
			<!-- /.box -->
		</div>
	`,
	props: ['app', 'l10n' ,'categories', 'disabled'],
	data: function() {
		return {
			
		}
	},
	computed: {
		processing: function() {
			return (this.app.hasOwnProperty('processing')) ? this.app.processing : false;
		}
	},
	methods: {
		action: function(action) {
			this.$emit('action', action, this.app);
		},
		getCategory(category) {
			
			for(var i = 0; i <= this.categories.length;++i) {
				if(this.categories[i]['key'] === category) return this.categories[i]['title'];
			}

			return category;
		}
	}
});

new Vue({
	el: '#externalApps',
	data: function() {
		return {
			loading: false,
			disabled: false,
			my_apps_open: true,
			active_category: 'all',
			search_string: '',
			error_message: '',
			categories: categories,
			routes: routes,
			l10n: l10n,
			my_apps: my_apps,
			apps: apps,
			selected_app: null,
			search_timeout: null
		}
	},
	methods: {
		toggleMyAppsContainer: function() {
			this.my_apps_open = (!this.my_apps_open) ? true : false;
		},
		loadCategory: function(category) {
			
			if(this.disabled) return false;
			
			if(category === this.active_category) return;
			
			this.active_category = category;
			//this.my_apps_open = false;
			
			this.load();
			
		},
		search: function() {
			
			if(this.disabled) return false;
			
			if(this.search_timeout) window.clearTimeout(this.search_timeout);
			
			this.search_timeout = window.setTimeout(() => {	
				//this.my_apps_open = false;
				this.load();			
			}, 300);
			
		},
		load: function() {
			
			this.loading = true;
			this.apps = [];
			
			var data = {
				search: this.search_string,
				category: this.active_category
			};
			
			$.post(
				this.routes.loading,
				data,
				(data) => {
					this.loading = false;
					if(data.hasOwnProperty('apps')) {
						this.apps = data.apps;
					}
					if(data.hasOwnProperty('my_apps')) {
						this.my_apps = data.my_apps;
					}
				},
				'json'
			);

		},
		appAction: function(action, app) {
			
			switch(action) {
				case 'modal':
					this.openModal(app);
					break;
				case 'install':
					this.installApp(app);
					break;
				case 'delete':
					this.deleteApp(app);
					break;
				case 'edit':
					this.updateAppProcessingStatus(app, 'edit');
					break;
				default:
					console.error('Unknown action');
			}
			
		},
		installAppFromModal: function() {
			
			if(!this.selected_app) {
				console.error('No selected app');
				return false;
			}
			
			this.installApp(this.selected_app, false, () => {
				this.closeModal();
			});				
					
		},
		installApp: function(app, needs_confirm, callback) {
			
			if(this.disabled) return false;

			var confirmMessage = this.l10n.install_confirm;
			if (app.price !== null) {
				confirmMessage = this.l10n.install_confirm_with_price;
			}

			if(
				needs_confirm === false ||
				confirm(confirmMessage)
			) {
				this.updateAppProcessingStatus(app, 'install');
				this.disabled = true;
				
				var data = {
					app: app.key
				};
				
				$.post(
					this.routes.install,
					data,
					(data) => {
						
						this.updateAppProcessingStatus(app, false);
						this.disabled = false;
						
						if(
							data.hasOwnProperty('success') &&
							data.success === true
						) {
							var apps = this.apps;
							for(var i = 0; i < this.apps.length; ++i) {
								if(this.apps[i]['key'] === data.app_key) {
									apps.splice(i, 1);
									break;
								}
							}
							this.apps = apps;
					
							this.my_apps_open = true;
							this.my_apps.push(data.app);
						} else {
							this.showError(data.message);
						}
												
						if(typeof callback === 'function') {
							callback();
						}
						
					},
					'json'
				);
				
			}
		},
		deleteApp: function(app) {

			if(this.disabled) return false;

			if(confirm(this.l10n.delete_confirm)) {
		
				this.updateAppProcessingStatus(app, 'delete');
				this.disabled = true;
				
				var data = {
					app: app.key
				};
				
				$.post(
					this.routes.delete,
					data,
					(data) => {
						
						this.updateAppProcessingStatus(app, false);
						this.disabled = false;
						
						if(
							data.hasOwnProperty('success') &&
							data.success === true
						) {
							var aMyApps = this.my_apps;
							for(var i = 0; i < this.my_apps.length; ++i) {
								if(this.my_apps[i]['key'] === data.app) {
									aMyApps.splice(i, 1);
									break;
								}
							}

							this.my_apps = aMyApps;
							this.load();
						} else {
							this.showError(data.message);
						}

					},
					'json'
				);
				
			}
		},
		showError: function(message) {

			console.error(message);

			this.error_message = message;

			$('#ErrorModal').modal('show');

		},
		openModal: function(app) {
			this.selected_app = app;
			
			$('#AppModal').modal('show');
			
		},
		closeModal: function() {
			
			$('#AppModal').modal('hide');
			
			$('#myModal').on('hidden.bs.modal', () => {
				this.selected_app = null;
			});			
		},		
		getSelectedAppProperty: function(property) {
			if(
				this.selected_app &&
				this.selected_app.hasOwnProperty(property)
			) {
				if(property === 'category') {
					return this.getCategory(this.selected_app.category);
				}
		
				return this.selected_app[property];
			}
			
			return '';
		},
		getCategory(category) {
			
			for(var i = 0; i <= this.categories.length;++i) {
				if(this.categories[i]['key'] === category) return this.categories[i]['title'];
			}
			
			return category;
		},
		updateAppProcessingStatus: function(app, status) {
			
			for(var i = 0; i < this.my_apps.length; ++i) {
				if(this.my_apps[i]['key'] === app.key) {
					this.$set(this.my_apps[i], 'processing', status);
					return;
				}
			}

			for(var j = 0; j < this.apps.length; ++j) {
				if(this.apps[j]['key'] === app.key) {
					this.$set(this.apps[j], 'processing', status);
					return;
				}
			}

			if(this.selected_app && this.selected_app.key === app.key) {
				this.$set(this.selected_app, 'processing', status);
			}
			
		}
	}
});
