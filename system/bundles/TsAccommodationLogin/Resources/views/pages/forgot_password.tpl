{extends file="system/bundles/TsAccommodationLogin/Resources/views/layout/login.tpl"}

{block 'login'}

    <p class="login-box-msg">{'Please enter your e-mail address here'|L10N}</p>
    <form method="post" action="{route name='TsAccommodationLogin.accommodation_reset_password_send'}">
        <div class="form-group has-feedback">
            <input type="email" name="email" id="email" class="form-control" placeholder="{'E-Mail'|L10N}" value="">
            <span class="glyphicon glyphicon-user form-control-feedback"></span>
        </div>
        <div class="row">
            <div class="col-xs-12">
                <button type="submit" class="btn btn-primary pull-right btn-flat">{'Request a new password'|L10N}</button>
            </div><!-- /.col -->
        </div>
    </form>

    <a href="{route name='TsAccommodationLogin.accommodation_login'}">{'Back'|L10N}</a>
{/block}