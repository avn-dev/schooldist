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
    <!-- Dropzone -->
    <link rel="stylesheet" href="/assets/dropzone/dropzone.min.css">
	
	<link rel="stylesheet" href="{route name='TsTeacherLogin.teacher_resources' sFile= 'css/summernote.min.css'}">
	
    <link rel="stylesheet" href="{route name='TsAccommodationLogin.accommodation_resources' sFile= 'css/style.css'}">
	{block name="header_css"}{/block}
    <style>
        {\System::getSystemColorStyles()}
    </style>
</head>
<body class="hold-transition skin-blue fixed sidebar-mini">
    <div class="wrapper" style="height: auto; min-height: 100%;">

        {include file="system/bundles/TsAccommodationLogin/Resources/views/layout/topbar.tpl"}

        <aside class="main-sidebar">
            <!-- sidebar: style can be found in sidebar.less -->
            <section class="sidebar">
                <!-- sidebar menu: : style can be found in sidebar.less -->
                <ul class="sidebar-menu tree" data-widget="tree">
                    <li><a href="{route name='TsAccommodationLogin.accommodation'}"><i class="fa fa-dashboard"></i> <span>{'Dashboard'|L10N}</span></a></li>
                    {if !in_array('profile', $deactivatedPages)}
                        <li><a href="{route name='TsAccommodationLogin.accommodation_data'}"><i class="fa fa-address-card"></i> <span>{'Profile'|L10N}</span></a></li>
                    {/if}
                    {if !in_array('availabillity_requests', $deactivatedPages)}
                        <li><a href="{route name='TsAccommodationLogin.accommodation_requests'}"><i class="fa fa-table"></i> <span>{'Availability requests'|L10N}</span></a></li>
                    {/if}
                    {if !in_array('payments', $deactivatedPages)}
                        <li><a href="{route name='TsAccommodationLogin.accommodation_payments'}"><i class="fa fa-table"></i> <span>{'Payments'|L10N}</span></a></li>
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
    <!-- fullCalendar -->
    <script src="/assets/adminlte/components/fullcalendar/dist/fullcalendar.min.js"></script>
    <script src="/admin/assets/js/zxcvbn.js?v={\System::d('version')}"></script>
    <script src="/admin/assets/js/lib.js?v={\System::d('version')}"></script>
    <script src='/assets/adminlte/components/fullcalendar/dist/locale-all.js'></script>
    <script src="{route name='TsAccommodationLogin.accommodation_resources' sFile= 'js/TsAccommodationLogin.accommodation_login.js'}"></script>
    <script>
		TsAccommodationLogin.translations = {$aTranslations|json_encode};
    </script>
    <script src="{route name='TsTeacherLogin.teacher_resources' sFile= 'js/summernote.min.js'}"></script>
    <script src="{route name='TsTeacherLogin.teacher_resources' sFile= 'js/lang/summernote-de-DE.min.js'}"></script>
	
    <script>
    
		$(document).ready(function() {
			$('.summernote').summernote({
			toolbar: [
				['style', ['style']],
				['font', ['bold', 'underline', 'clear']],
				['color', ['color']],
				['para', ['ul', 'ol', 'paragraph']],
				['table', ['table']],
				['insert', ['link']],
				['view', ['fullscreen']],
			  ]
			});
		});
		
    </script>		
		
    {block name="footer_js"}
	
	{/block}
    </body>
</html>