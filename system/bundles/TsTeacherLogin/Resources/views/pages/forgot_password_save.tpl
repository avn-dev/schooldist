{extends file="system/bundles/TsTeacherLogin/Resources/views/layout/login.tpl"}

{block 'login'}

    <style>
        .progress-label {
            margin-bottom: 15px;
            font-size: 0.9em;
        }
    </style>

    <p class="login-box-msg">{'Please enter a new password'|L10N}</p>
    <form action="{$sFormAction}" method="post">
        <div class="form-group has-feedback">
            <input type="password" class="form-control" name="password_new" id="password_new" placeholder="{'Password'|L10N}">
        </div>
        {include file="system/bundles/Admin/Resources/views/forgot_password_strength.tpl"}
        <div class="form-group has-feedback">
            <input type="password" name="password_check" id="password_check" class="form-control" placeholder="{'Confirm password'|L10N}">
        </div>
        <div class="row">
            <div class="col-xs-12">
                <button type="submit" class="btn btn-primary pull-right btn-flat">{'Save new password'|L10N}</button>
            </div><!-- /.col -->
        </div>
    </form>

    <a href="{route name='TsTeacherLogin.teacher_forgot_password'}">{'Back'|L10N}</a>

{/block}