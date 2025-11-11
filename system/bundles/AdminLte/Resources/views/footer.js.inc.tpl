		<!-- jQuery 2.1.4 -->
		<script src="/assets/core/jquery/jquery.min.js?v={\System::d('version')}"></script>
		{if isset($bJqueryNoConflict) && $bJqueryNoConflict}
		<script>$j = jQuery.noConflict();</script>
		{/if}
		<!-- Bootstrap 3.3.5 -->
		<script src="/assets/adminlte/bootstrap/js/bootstrap.min.js?v={\System::d('version')}"></script>
		<!-- FastClick -->
		<script src="/assets/adminlte/components/fastclick/lib/fastclick.js?v={\System::d('version')}"></script>
		<!-- AdminLTE App -->
		<script src="/assets/adminlte/js/adminlte.min.js?v={\System::d('version')}"></script>
		<!-- Sparkline -->
		<script src="/assets/adminlte/components/jquery-sparkline/dist/jquery.sparkline.min.js?v={\System::d('version')}"></script>
		<!-- SlimScroll 1.3.0 -->
		<script src="/assets/adminlte/components/jquery-slimscroll/jquery.slimscroll.min.js?v={\System::d('version')}"></script>
		<!-- ChartJS -->
		<script src="/admin/assets/js/Chart.min.js?v={\System::d('version')}"></script>
		<!-- Toastr -->
		<script src="/admin/assets/js/toastr.min.js?v={\System::d('version')}"></script>
		<!-- Bootstrap datepicker -->
		<script src="/assets/adminlte/components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js?v={\System::d('version')}"></script>
		{assign var=sDatePickerLanguage value=\System::getInterfaceLanguage()}
		{if $sDatePickerLanguage === 'en'}
			{assign var=sDatePickerLanguage value='en-GB'}
		{/if}
		<script src="/assets/adminlte/components/bootstrap-datepicker/dist/locales/bootstrap-datepicker.{$sDatePickerLanguage}.min.js?v={\System::d('version')}"></script>

		<script src="/assets/adminlte/components/bootstrap-colorpicker/dist/js/bootstrap-colorpicker.min.js?v={\System::d('version')}"></script>
		
		<script>
			var bPageLoaderDisabled = false;
			jQuery(function() {
				jQuery('.page-loader').hide();
				jQuery(window).on('beforeunload', function(){
					if(!bPageLoaderDisabled) {
						jQuery('.page-loader').show();
					}
				});
			});
		</script>