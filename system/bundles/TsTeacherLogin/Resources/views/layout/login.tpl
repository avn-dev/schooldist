<!DOCTYPE html>
    <html>
        <head>
            <title>{'Teacher login'|L10N} - {\System::d('project_name')}</title>
            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <!-- Tell the browser to be responsive to screen width -->
            <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
            <link rel="stylesheet" href="/assets/adminlte/bootstrap/css/bootstrap.min.css">
            <link rel="stylesheet" href="/assets/adminlte/css/AdminLTE.min.css">
            <link rel="stylesheet" href="/admin/assets/fontawesome5/css/all.min.css?v={\System::d('version')}">
			<link rel="stylesheet" href="/admin/assets/fontawesome5/css/v4-shims.css?v={\System::d('version')}">
            <link rel="stylesheet" href="{route name='TsTeacherLogin.teacher_resources' sFile= 'css/style.css'}">
        </head>
        <body class="hold-transition login-page">
            <div class="login-box">
                <div class="login-logo">
                    <img src="{$aLogos['login_logo']}" alt="{\System::d('project_name')}">
                </div><!-- /.login-logo -->
                <div class="login-box-body">
                    {if $oAccess and $oAccess->getLastErrorCode()}
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <i class="icon fa fa-ban"></i> {$oAccess->getLastErrorCode()|L10N}
                        </div>
                    {/if}
                    {foreach $oSession->getFlashBag()->get('error', array()) as $sMessage}
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <i class="icon fa fa-exclamation"></i>
                            {if $sMessage == 'wrong_data'}
                                {'Login failed! The entered data is not valid.'|L10N}
                            {else}
                                {$sMessage}
                            {/if}
                        </div>
                    {/foreach}
                    {foreach $oSession->getFlashBag()->get('success', array()) as $sMessage}
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <i class="icon fa fa-check"></i> {$sMessage}
                        </div>
                    {/foreach}

                    {block 'login'}

                    {/block}

                    {if !System::d('ignore_copyright')}
                        <div class="login-box-footer">
                            {if System::d('software_producer')}
                                &copy; 2001-{$smarty.now|date_format:'%Y'} by {System::d('software_producer')}
                            {else}
                                &copy; 2001-{$smarty.now|date_format:'%Y'}
                            {/if}
                        </div>
                    {/if}

                </div>
                <!-- /.login-box-body -->
            </div>
            <!-- /.login-box -->

            <!-- jQuery 3 -->
            <script src="/assets/adminlte/components//jquery/dist/jquery.min.js"></script>
            <!-- Bootstrap 3.3.5 -->
            <script src="/assets/adminlte/bootstrap/js/bootstrap.min.js"></script>
            <!-- SlimScroll -->
            <script src="/assets/adminlte/components/jquery-slimscroll/jquery.slimscroll.min.js"></script>
            <!-- FastClick -->
            <script src="/assets/adminlte/components/fastclick/lib/fastclick.js"></script>
            <!-- AdminLTE App -->
            <script src="/assets/adminlte/js/adminlte.min.js"></script>
            <script src="/admin/assets/js/lib.js?v={\System::d('version')}"></script>
            <script src="/admin/assets/js/zxcvbn.js?v={\System::d('version')}"></script>
            <script src="{route name='TsTeacherLogin.teacher_resources' sFile= 'js/teacher_login.js'}"></script>
            <script>
				// Sprachauswahl
				$('#language').change(function(oSelect) {
					document.location.href = '/teacher/change-language/'+$(this).val();
				});
				window.initPasswordInputs();
            </script>

        </body>
    </html>