{extends file="system/bundles/TsTeacherLogin/Resources/views/layout/master.tpl"}

{block name="title"}{'My data'|L10N}{/block}

{block name="content"}
    <div class="content-header">
        <h1>{'Personal information'|L10N}</h1>
    </div>
    <div class="content">
        <div class="box">
            <!-- form start -->
            <form class="form-horizontal" action="{route name='TsTeacherLogin.teacher_data_save'}" method="post">
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

                    {assign var="aErrors" value=$oSession->getFlashBag()->get('errors', array())}

                    <div class="form-group">
                        <label for="nationality" class="col-sm-2 control-label">{'Nationality'|L10N}</label>

                        <div class="col-sm-10">
                            <select class="form-control" id="nationality" name="nationality">
                                {foreach $aNationalities as $sKey => $sLabel}
                                    <option value="{$sKey}" {if $sKey == $oTeacher->nationality}selected{/if}>{$sLabel}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="mother_tongue" class="col-sm-2 control-label">{'Mother tongue'|L10N}</label>

                        <div class="col-sm-10">
                            <select class="form-control" id="mother_tongue" name="mother_tongue">
                                {foreach $aLanguages as $sKey => $sLabel}
                                    <option value="{$sKey}" {if $sKey == $oTeacher->mother_tongue}selected{/if}>{$sLabel}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="street" class="col-sm-2 control-label">{'Street'|L10N}</label>

                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="street" name="street" value="{$oTeacher->street}" placeholder="{'Street'|L10N}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="additional_address" class="col-sm-2 control-label">{'Adresszusatz'|L10N}</label>

                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="additional_address" name="additional_address" value="{$oTeacher->additional_address}" placeholder="{'Adresszusatz'|L10N}">
                        </div>
                    </div>
                    <div class="form-group{if $aErrors.zip} has-error{/if}">
                        <label for="zip" class="col-sm-2 control-label">{'Postcode'|L10N}</label>

                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="zip" name="zip" value="{$oTeacher->zip}">
                            {if $aErrors.zip}<span class="help-block">{$aErrors.zip}</span>{/if}
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="city" class="col-sm-2 control-label">{'City'|L10N}</label>

                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="city" name="city" value="{$oTeacher->city}" placeholder="{'City'|L10N}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="state" class="col-sm-2 control-label">{'State'|L10N}</label>

                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="state" name="state" value="{$oTeacher->state}" placeholder="{'State'|L10N}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="country_id" class="col-sm-2 control-label">{'Country'|L10N}</label>

                        <div class="col-sm-10">

                            <select class="form-control" id="country_id" name="country_id">
                                {foreach $aCountries as $sKey => $sLabel}
                                    <option value="{$sKey}" {if $sKey == $oTeacher->country_id}selected{/if}>{$sLabel}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group{if $aErrors.phone} has-error{/if}">
                        <label for="phone" class="col-sm-2 control-label">{'Phone'|L10N}</label>

                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="phone" name="phone" value="{$oTeacher->phone}" placeholder="{'Phone'|L10N}">
                            {if $aErrors.phone}<span class="help-block">{$aErrors.phone}</span>{/if}
                        </div>
                    </div>
                    <div class="form-group{if $aErrors.phone_business} has-error{/if}">
                        <label for="phone_business" class="col-sm-2 control-label">{'Business phone'|L10N}</label>

                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="phone_business" name="phone_business" value="{$oTeacher->phone_business}" placeholder="{'Business phone'|L10N}">
                            {if $aErrors.phone_business}<span class="help-block">{$aErrors.phone_business}</span>{/if}
                        </div>
                    </div>
                    <div class="form-group{if $aErrors.mobile_phone} has-error{/if}">
                        <label for="mobile_phone" class="col-sm-2 control-label">{'Mobile'|L10N}</label>

                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="mobile_phone" name="mobile_phone" value="{$oTeacher->mobile_phone}" placeholder="{'Mobile'|L10N}">
                            {if $aErrors.mobile_phone}<span class="help-block">{$aErrors.mobile_phone}</span>{/if}
                        </div>
                    </div>
                    <div class="form-group{if $aErrors.fax} has-error{/if}">
                        <label for="fax" class="col-sm-2 control-label">{'Fax'|L10N}</label>

                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="fax" name="fax" value="{$oTeacher->fax}" placeholder="{'Fax'|L10N}">
                            {if $aErrors.fax}<span class="help-block">{$aErrors.fax}</span>{/if}
                        </div>
                    </div>
                    <div class="form-group{if $aErrors.email} has-error{/if}">
                        <label for="email" class="col-sm-2 control-label">{'E-Mail'|L10N}</label>

                        <div class="col-sm-10">
                            <input type="email" class="form-control" id="email" name="email" value="{$oTeacher->email}" placeholder="{'E-Mail'|L10N}">
                            {if $aErrors.email}<span class="help-block">{$aErrors.email}</span>{/if}
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="skype" class="col-sm-2 control-label">{'Skype'|L10N}</label>

                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="skype" name="skype" value="{$oTeacher->skype}" placeholder="{'Skype'|L10N}">
                        </div>
                    </div>
                </div>
                <!-- /.box-body -->
                <div class="box-footer">
                    <button type="submit" class="btn btn-primary pull-right">{'Save changes'|L10N}</button>
                </div>
                <!-- /.box-footer -->
            </form>
        </div>

        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">{'Change password'|L10N}</h3>
            </div>
            <!-- /.box-header -->
            <!-- form start -->
            <form class="form-horizontal" action="{route name='TsTeacherLogin.teacher_password_save'}" method="post">
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
		new TsTeacherLogin.MyData();
    </script>
{/block}