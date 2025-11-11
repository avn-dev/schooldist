<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>{block name="title"}{/block} - {\System::d('project_name')}</title>

    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <!-- Bootstrap 3.3.5 -->
    <link rel="stylesheet" href="/assets/adminlte/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/admin/assets/fontawesome5/css/all.min.css?v={\System::d('version')}">
	<link rel="stylesheet" href="/admin/assets/fontawesome5/css/v4-shims.css?v={\System::d('version')}">
    <!-- fullCalendar -->
    <link rel="stylesheet" href="/assets/adminlte/components/fullcalendar/dist/fullcalendar.min.css">
    <link rel="stylesheet" href="/assets/adminlte/components/fullcalendar/dist/fullcalendar.print.min.css" media="print">
    <!-- jvectormap -->
    <link rel="stylesheet" href="/assets/adminlte/plugins/jvectormap/jquery-jvectormap-1.2.2.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="/assets/adminlte/components/select2/dist/css/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/assets/adminlte/css/AdminLTE.min.css">
    <!-- AdminLTE Skins. Choose a skin from the css/skins
         folder instead of downloading all of them to reduce the load. -->
    <link rel="stylesheet" href="/assets/adminlte/css/skins/skin-blue.min.css">
    <!-- Toastr -->
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
    <!-- daterange picker -->
    <link rel="stylesheet" href="/assets/adminlte/components/bootstrap-daterangepicker/daterangepicker.css">
    <!-- Bootstrap Datepicker -->
    <link rel="stylesheet" type="text/css" href="/assets/adminlte/components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">
    <!-- Bootstrap Colorpicker -->
    <link rel="stylesheet" href="/assets/adminlte/components/bootstrap-colorpicker/dist/css/bootstrap-colorpicker.min.css">
    <!-- Pace Js -->
    <link rel="stylesheet" href="/assets/adminlte/plugins/pace/pace.min.css">
    <!-- Dropzone -->
    <link rel="stylesheet" href="/assets/dropzone/dropzone.min.css">
    <!-- Bootstrap Duallistbox -->
    <link rel="stylesheet" href="{route name='TsTeacherLogin.teacher_resources_duallistbox' sFile='bootstrap-duallistbox.min.css'}">
    <!-- ION RangeSlider -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ion-rangeslider/2.2.0/css/ion.rangeSlider.min.css">
    <!-- RangeSlider Modern Skin -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ion-rangeslider/2.2.0/css/ion.rangeSlider.skinHTML5.min.css">
    <link rel="stylesheet" href="{route name='TsTeacherLogin.teacher_resources' sFile= 'css/style.css'}">
	{block name="header_css"}{/block}
    <style>
        {\System::getSystemColorStyles()}
    </style>
</head>
<body class="hold-transition skin-blue fixed sidebar-mini">
    <div class="wrapper" style="height: auto; min-height: 100%;">

        {include file="system/bundles/TsTeacherLogin/Resources/views/layout/topbar.tpl"}

        <aside class="main-sidebar">
            <!-- sidebar: style can be found in sidebar.less -->
            <section class="sidebar">
                <!-- sidebar menu: : style can be found in sidebar.less -->
                <ul class="sidebar-menu tree" data-widget="tree">
                    <li><a href="{route name='TsTeacherLogin.teacher'}"><i class="fa fa-dashboard"></i> <span>{'Dashboard'|L10N}</span></a></li>
                    {if $oTeacher->access_right_timetable != 0}
                        <li><a href="{route name='TsTeacherLogin.teacher_timetable'}"><i class="fa fa-table"></i> <span>{'Timetable'|L10N}</span></a></li>
                    {/if}
                    {if $oTeacher->access_right_attendance != 0}
                        <li><a href="{route name='TsTeacherLogin.teacher_attendance'}"><i class="fa fa-user"></i> <span>{'Attendance'|L10N}</span></a></li>
                    {/if}
                    {if $oTeacher->access_right_communication != 0}
                        <li><a href="{route name='TsTeacherLogin.teacher_communication'}"><i class="fa fa-envelope"></i> <span>{'Communication'|L10N}</span></a></li>
                    {/if}
                    {if $oTeacher->access_right_reportcards != 0}
                        <li><a href="{route name='TsTeacherLogin.teacher_reportcards'}"><i class="fa fa-book"></i> <span>{'Report Cards'|L10N}</span></a></li>
                    {/if}
                </ul>
            </section>
            <!-- /.sidebar -->
        </aside>
        <div class="content-wrapper">

            {block name="content"}

            {/block}

        </div>
    </div>
    <!-- jQuery 3 -->
    <script src="/assets/adminlte/components//jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap 3.3.5 -->
    <script src="/assets/adminlte/bootstrap/js/bootstrap.min.js"></script>
    <!-- Select2 -->
    <script src="/assets/adminlte/components/select2/dist/js/select2.full.min.js"></script>
    <script src="/assets/adminlte/plugins/input-mask/jquery.inputmask.js"></script>
    <!-- Pace JS -->
    <script src="/assets/adminlte/components/PACE/pace.min.js"></script>
    <!-- SlimScroll -->
    <script src="/assets/adminlte/components/jquery-slimscroll/jquery.slimscroll.min.js"></script>
    <!-- FastClick -->
    <script src="/assets/adminlte/components/fastclick/lib/fastclick.js"></script>
    <!-- AdminLTE App -->
    <script src="/assets/adminlte/js/adminlte.min.js"></script>
    <!-- MomentJS -->
    <script src="/assets/adminlte/components/moment/moment.js"></script>
    <!-- date-range-picker -->
    <script src="/assets/adminlte/components/bootstrap-daterangepicker/daterangepicker.js"></script>
    <!-- bootstrap datepicker -->
    <script src="/assets/adminlte/components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
    {assign var=sDatePickerLanguage value=\System::getInterfaceLanguage()}
    {if $sDatePickerLanguage === 'en'}
        {assign var=sDatePickerLanguage value='en-GB'}
    {/if}
    <script src="/assets/adminlte/components/bootstrap-datepicker/dist/locales/bootstrap-datepicker.{$sDatePickerLanguage}.min.js?v={\System::d('version')}"></script>

    <!-- fullCalendar -->
    <script src="/assets/adminlte/components/fullcalendar/dist/fullcalendar.min.js"></script>
    <script src="/admin/assets/js/zxcvbn.js?v={\System::d('version')}"></script>
    <script src="/admin/assets/js/lib.js?v={\System::d('version')}"></script>
    <script src='/assets/adminlte/components/fullcalendar/dist/locale-all.js'></script>
    <script src="{route name='TsTeacherLogin.teacher_resources' sFile= 'js/teacher_login.js'}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="{route name='TsTeacherLogin.teacher_resources_duallistbox' sFile='jquery.bootstrap-duallistbox.min.js'}"></script>
    <script>
		TsTeacherLogin.translations = {$aTranslations|json_encode};
    </script>
    {block name="footer_js"}{/block}
    </body>
</html>