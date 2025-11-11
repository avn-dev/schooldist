{extends file="system/bundles/TsAccommodationLogin/Resources/views/layout/master.tpl"}

{block name="title"}{'Profile'|L10N} - {$oSchool->name|L10N}{/block}

{block name="content"}
    <div class="content-header">
        <h1>{'Profile'|L10N}</h1>
    </div>
    <div class="content">
       	<div class="box box-default">
			<div class="box-header with-border">
				<h3 class="box-title">{'My data'|L10N}</h3>
			</div>

			<div class="box-body">
				
				 {foreach $oSession->getFlashBag()->get('error', array()) as $sMessage}
					<div class="alert alert-danger alert-dismissible">
						<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
						<i class="icon fa fa-exclamation"></i> {$sMessage}
					</div>
				{/foreach}
				{foreach $oSession->getFlashBag()->get('success', array()) as $sMessage}
					<div class="alert alert-success alert-dismissible">
						<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
						<i class="icon fa fa-check"></i> {$sMessage}
					</div>
				{/foreach}
				
				{if $aErrors}
					<p class="pError">{'Please fill out all required fields.'|L10N}</p>
				{/if}

				<form class="form-horizontal" method="post" name="booking_form" action="{route name="TsAccommodationLogin.accommodation_data_save"}">
					<input type="hidden" name="view" value="profile" />
					<input type="hidden" name="task" value="save" />

					<fieldset class="fieldsetDetail">

					{include file="../script.form_fields.tpl"}

					</fieldset>

                    <div class="box-footer">
                        <input type="Submit" class='btn btn-primary pull-right' value="{'Save my data'|L10N}">
                    </div>

				</div>
			</form>
		</div>
				
					
		<div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">{'Change password'|L10N}</h3>
            </div>
            <!-- /.box-header -->
            <!-- form start -->
            <form class="form-horizontal" action="{route name='TsAccommodationLogin.accommodation_password_save'}" method="post">
                <div class="box-body">
                    {foreach $oSession->getFlashBag()->get('password_error', array()) as $sMessage}
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <i class="icon fa fa-exclamation"></i> {$sMessage}
                        </div>
                    {/foreach}
                    {foreach $oSession->getFlashBag()->get('password_success', array()) as $sMessage}
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <i class="icon fa fa-check"></i> {$sMessage}
                        </div>
                    {/foreach}

                    <div class="form-group {if $aPasswordErrors.password_old} has-error{/if}">
                        <label for="password_old" class="col-sm-2 control-label">{'Current password'|L10N}</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control" name="password_old" id="password_old">
                            {if $aPasswordErrors.password_old}<span class="help-block">{$aPasswordErrors.password_old}</span>{/if}
                        </div>
                    </div>
                    <div class="form-group {if $aPasswordErrors.password_new} has-error{/if}">
                        <label for="password_new" class="col-sm-2 control-label">{'New password'|L10N}</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control" name="password_new" id="password_new">
                            {if $aPasswordErrors.password_new}<span class="help-block">{$aPasswordErrors.password_new}</span>{/if}
                            {include file="system/bundles/Admin/Resources/views/forgot_password_strength.tpl"}
                        </div>
                    </div>
                    <div class="form-group {if $aPasswordErrors.password_check} has-error{/if}">
                        <label for="password_check" class="col-sm-2 control-label">{'Confirm password'|L10N}</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control" name="password_check" id="password_check">
                            {if $aPasswordErrors.password_check}<span class="help-block">{$aPasswordErrors.password_check}</span>{/if}
                        </div>
                    </div>

                    <div class="box-footer">
                        <input type="Submit" class='btn btn-primary pull-right' value="{'Save new password'|L10N}">
                    </div>
                </div>
                <!-- /.box-body -->
            </form>
        </div>
					
    </div>
{/block}


{block name="footer_js"}
    <script>
		new TsAccommodationLogin.MyData();
    </script>
{/block}