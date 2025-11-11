<?php
/* Smarty version 5.5.2, created on 2025-11-11 12:40:18
  from 'file:system/bundles/AdminLte/Resources/views/footer.js.inc.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.5.2',
  'unifunc' => 'content_691320a2d048e5_59628056',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'c3ae9942a182746079c82117f72c4e62f13d751b' => 
    array (
      0 => 'system/bundles/AdminLte/Resources/views/footer.js.inc.tpl',
      1 => 1760096297,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_691320a2d048e5_59628056 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = '/var/www/html/school/system/bundles/AdminLte/Resources/views';
?>		<!-- jQuery 2.1.4 -->
		<?php echo '<script'; ?>
 src="/assets/core/jquery/jquery.min.js?v=<?php echo \System::d('version');?>
"><?php echo '</script'; ?>
>
		<?php if ((true && ($_smarty_tpl->hasVariable('bJqueryNoConflict') && null !== ($_smarty_tpl->getValue('bJqueryNoConflict') ?? null))) && $_smarty_tpl->getValue('bJqueryNoConflict')) {?>
		<?php echo '<script'; ?>
>$j = jQuery.noConflict();<?php echo '</script'; ?>
>
		<?php }?>
		<!-- Bootstrap 3.3.5 -->
		<?php echo '<script'; ?>
 src="/assets/adminlte/bootstrap/js/bootstrap.min.js?v=<?php echo \System::d('version');?>
"><?php echo '</script'; ?>
>
		<!-- FastClick -->
		<?php echo '<script'; ?>
 src="/assets/adminlte/components/fastclick/lib/fastclick.js?v=<?php echo \System::d('version');?>
"><?php echo '</script'; ?>
>
		<!-- AdminLTE App -->
		<?php echo '<script'; ?>
 src="/assets/adminlte/js/adminlte.min.js?v=<?php echo \System::d('version');?>
"><?php echo '</script'; ?>
>
		<!-- Sparkline -->
		<?php echo '<script'; ?>
 src="/assets/adminlte/components/jquery-sparkline/dist/jquery.sparkline.min.js?v=<?php echo \System::d('version');?>
"><?php echo '</script'; ?>
>
		<!-- SlimScroll 1.3.0 -->
		<?php echo '<script'; ?>
 src="/assets/adminlte/components/jquery-slimscroll/jquery.slimscroll.min.js?v=<?php echo \System::d('version');?>
"><?php echo '</script'; ?>
>
		<!-- ChartJS -->
		<?php echo '<script'; ?>
 src="/admin/assets/js/Chart.min.js?v=<?php echo \System::d('version');?>
"><?php echo '</script'; ?>
>
		<!-- Toastr -->
		<?php echo '<script'; ?>
 src="/admin/assets/js/toastr.min.js?v=<?php echo \System::d('version');?>
"><?php echo '</script'; ?>
>
		<!-- Bootstrap datepicker -->
		<?php echo '<script'; ?>
 src="/assets/adminlte/components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js?v=<?php echo \System::d('version');?>
"><?php echo '</script'; ?>
>
		<?php $_smarty_tpl->assign('sDatePickerLanguage', \System::getInterfaceLanguage(), false, NULL);?>
		<?php if ($_smarty_tpl->getValue('sDatePickerLanguage') === 'en') {?>
			<?php $_smarty_tpl->assign('sDatePickerLanguage', 'en-GB', false, NULL);?>
		<?php }?>
		<?php echo '<script'; ?>
 src="/assets/adminlte/components/bootstrap-datepicker/dist/locales/bootstrap-datepicker.<?php echo $_smarty_tpl->getValue('sDatePickerLanguage');?>
.min.js?v=<?php echo \System::d('version');?>
"><?php echo '</script'; ?>
>

		<?php echo '<script'; ?>
 src="/assets/adminlte/components/bootstrap-colorpicker/dist/js/bootstrap-colorpicker.min.js?v=<?php echo \System::d('version');?>
"><?php echo '</script'; ?>
>
		
		<?php echo '<script'; ?>
>
			var bPageLoaderDisabled = false;
			jQuery(function() {
				jQuery('.page-loader').hide();
				jQuery(window).on('beforeunload', function(){
					if(!bPageLoaderDisabled) {
						jQuery('.page-loader').show();
					}
				});
			});
		<?php echo '</script'; ?>
><?php }
}
