{extends file="system/bundles/AdminTools/Resources/views/layout.tpl"}

{block name="heading"}
	<h1>Overview</h1>
{/block}

{block name="content"}
	<div class="row">

		<div class="col-md-4 col-sm-6 col-xs-12">
			<div class="info-box">
				<span class="info-box-icon bg-aqua"><i class="fa fa-tty"></i></span>
				<div class="info-box-content" style="display: flex; justify-content: space-between;">
					<form method="post">
						<input type="hidden" name="type" value="debug_mode">
						<span class="info-box-text">Debug-Mode</span>
						<span class="info-box-number">
							{if System::d('debugmode') > 0}
								<span class="text-success">Enabled: {System::d('debugmode')}</span>
							{else}
								<span class="text-danger">Disabled</span>
							{/if}
						</span>
						<button class="btn btn-sm" type="submit">Toggle</button>
					</form>
					<form method="post" style="text-align: right">
						<input type="hidden" name="type" value="debug_ip">
						<span class="info-box-text">Debug-IP</span>
						<span class="info-box-number">
							{if Util::isDebugIP()}
								<span class="text-success">Yes</span>
							{else}
								<span class="text-danger">No</span>
							{/if}
						</span>
                        {if !Util::isDebugIP()}
							<button class="btn btn-sm" type="submit">Add</button>
						{/if}
					</form>
				</div>
			</div>
		</div>

		<div class="overview-buttons col-md-8 col-xs-12">
			<div class="info-box">
				<span class="info-box-icon bg-red"><i class="fa fa-wrench"></i></span>
				<form method="post" class="info-box-content">
					<input type="hidden" name="type" value="button">
					<button name="button" value="clear_cache" class="btn btn-app">
						<i class="fa fa-trash"></i> Clear Cache
					</button>
					<button name="button" value="refresh_routing" class="btn btn-app">
						<i class="fa fa-sync"></i> Routing Refresh
					</button>
					<button name="button" value="refresh_bundles" class="btn btn-app">
						<i class="fa fa-cubes"></i> Bundle Refresh
					</button>
					<button name="button" value="refresh_db_functions" class="btn btn-app">
						<i class="fa fa-database"></i> DB Functions Refresh
					</button>
				</form>
			</div>
		</div>

		<div class="overview-actions col-xs-12">
			<div class="info-box">
				<span class="info-box-icon bg-yellow"><i class="fa fa-tools"></i></span>
				<form method="post" class="info-box-content" style="display: flex; flex-direction: column;">
					<input type="hidden" name="type" value="action">
					<div class="form-group">
						<label class="col-sm-2 control-label">Action</label>
						<div class="col-sm-10">
							<select name="action" class="form-control">
								<option></option>
								{foreach $actions as $group => $items}
									<optgroup label="{$group}">
                                        {foreach $items as $key => $label}
	                                        <option value="{$key}" {if $smarty.post.action === $key}selected{/if}>{$label}</option>
                                        {/foreach}
									</optgroup>
								{/foreach}
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">Value</label>
						<div class="col-sm-10">
							<input name="value" class="form-control" value="{$smarty.post.value|escape:'html'}">
						</div>
					</div>
					<div class="col-md-12">
						<button class="btn btn-primary pull-right">Execute</button>
					</div>
				</form>
                {$action_result}
			</div>
		</div>

		<h3 class="col-xs-12">Index Actions</h3>

		<div class="overview-actions col-xs-12">
			<div class="info-box">
				<span class="info-box-icon bg-red"><i class="fa fa-search"></i></span>
				<form method="post" class="info-box-content" style="display: flex; flex-direction: column;">
					<input type="hidden" name="type" value="index">
					<div class="form-group">
						<label class="col-sm-2 control-label">Index</label>
						<div class="col-sm-10">
							<select name="index" class="form-control">
								<option></option>
                                {foreach $indexes as $key => $label}
									<option value="{$key}">{$label}</option>
                                {/foreach}
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">Action</label>
						<div class="col-sm-10">
							<select name="action" class="form-control">
								<option></option>
								<option value="refresh">Refresh + Fill Stack</option>
								<option value="reset">Reset + Fill Stack</option>
								<option value="reset_no_stack">Reset</option>
							</select>
						</div>
					</div>
					<div class="col-md-12">
						<button class="btn btn-primary pull-right">Execute</button>
					</div>
				</form>
			</div>
		</div>

	</div>
{/block}
