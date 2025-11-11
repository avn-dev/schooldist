{extends file="system/bundles/TsTeacherLogin/Resources/views/layout/login.tpl"}

{block 'login'}
    <p class="login-box-msg">{'Please enter your user data down below.'|L10N}</p>

    <form action="{route name='TsTeacherLogin.teacher'}" method="post">
        <div>
            <input value="32" name="table_number" type="hidden">
            <input value="1" name="loginmodul" type="hidden">
        </div>
        <div class="form-group has-feedback">
            <input type="text" name="customer_login_1" class="form-control" placeholder="{'E-Mail or username'|L10N}">
            <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
        </div>
        <div class="form-group has-feedback">
            <input type="password" name="customer_login_3" class="form-control" placeholder="{'Password'|L10N}">
            <span class="glyphicon glyphicon-lock form-control-feedback"></span>
        </div>
        <div class="form-group has-feedback">
            <select name="frontendlanguage" id="language" class="form-control">
                {foreach $aLanguages as $sIso => $sLanguage}
                    <option value="{$sIso}" {if $sIso === \System::getInterfaceLanguage()}selected{/if}>{$sLanguage}</option>
                {/foreach}
            </select>
        </div>
        <div class="row">
            <div class="col-xs-8">
                <div class="checkbox">
                    <label>
                        <input class="checkbox" type="checkbox" name="customer_login_remember_password"> {'Save password'|L10N}
                    </label>
                </div>
            </div>
            <!-- /.col -->
            <div class="col-xs-4">
                <button type="submit" class="btn btn-primary btn-block btn-flat">{'Login'|L10N}</button>
            </div>
            <!-- /.col -->
        </div>
    </form>
    <a href="{route name='TsTeacherLogin.teacher_forgot_password'}">{'Forgot password'|L10N}</a><br>
{/block}