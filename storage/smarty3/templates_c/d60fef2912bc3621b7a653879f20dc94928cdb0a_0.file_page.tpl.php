<?php
/* Smarty version 5.5.2, created on 2025-11-11 12:40:18
  from 'file:system/bundles/Gui2/Resources/views/page.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.5.2',
  'unifunc' => 'content_691320a2d14244_84422658',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'd60fef2912bc3621b7a653879f20dc94928cdb0a' => 
    array (
      0 => 'system/bundles/Gui2/Resources/views/page.tpl',
      1 => 1761824552,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_691320a2d14244_84422658 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = '/var/www/html/school/system/bundles/Gui2/Resources/views';
$_smarty_tpl->getInheritance()->init($_smarty_tpl, false);
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<title><?php echo $_smarty_tpl->getValue('oGui')->gui_title;?>
</title>
		<!-- Tell the browser to be responsive to screen width -->
		<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
		
		<?php $_smarty_tpl->renderSubTemplate(Factory::executeStatic('\Admin_Html','getHeadIncludeFile',array(true)), $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), (int) 0, $_smarty_current_dir);
?>

		<?php echo $_smarty_tpl->getValue('aOptions')['additional_top'];?>

		<?php echo $_smarty_tpl->getValue('aOptions')['additional'];?>

		<?php echo $_smarty_tpl->getValue('aOptions')['additional_bottom'];?>


		<?php 
$_smarty_tpl->getInheritance()->instanceBlock($_smarty_tpl, 'Block_70050953691320a2d117e2_98472623', "system_head");
?>

	</head>
	<body class="font-body h-full p-2" data-mode="light">

		<div class="page-loader" <?php echo ($_smarty_tpl->getValue('oGui') && $_smarty_tpl->getValue('oGui')->hasDialogOnlyMode()) ? 'style="height: 0 !important; width: 0 !important; opacity: 0;"' : '';?>
>
			<div class="pl-cube1 pl-cube"></div>
			<div class="pl-cube2 pl-cube"></div>
			<div class="pl-cube4 pl-cube"></div>
			<div class="pl-cube3 pl-cube"></div>
		</div>
		
				
		<section class="text-xs rounded">
			<!-- COLOR PALETTE -->
			<div class="color-palette-box">
			  <div class="" <?php echo ($_smarty_tpl->getValue('oGui') && $_smarty_tpl->getValue('oGui')->hasDialogOnlyMode()) ? 'style="height: 0 !important; width: 0 !important; opacity: 0;"' : '';?>
>
				  <?php 
$_smarty_tpl->getInheritance()->instanceBlock($_smarty_tpl, 'Block_1378737202691320a2d12bf9_31997099', "html");
?>

			  </div>
			  <!-- /.box-body -->
			</div>
		</section>

        <div id="admin-app"></div>

		<?php echo $_smarty_tpl->getValue('sJs');?>


		<?php 
$_smarty_tpl->getInheritance()->instanceBlock($_smarty_tpl, 'Block_1689052791691320a2d13224_12808290', "system_footer");
?>


		<?php echo '<script'; ?>
 type="text/javascript">

			function processLoading() {
				console.debug('processLoading');
			}

			jQuery(function() {
				initPage();
				<?php 
$_smarty_tpl->getInheritance()->instanceBlock($_smarty_tpl, 'Block_302841405691320a2d13754_02724506', "system_footer_ready");
?>

			});

            __ADMIN__.createAdminSlimApp({
                'common.back': '<?php echo Admin\Facades\Admin::translate('Zurück');?>
',
                'common.cancel': '<?php echo Admin\Facades\Admin::translate('Abbrechen');?>
',
                'common.close': '<?php echo Admin\Facades\Admin::translate('Schließen');?>
',
                'common.confirm': '<?php echo Admin\Facades\Admin::translate('Okay');?>
',
            }).mount('#admin-app')

		<?php echo '</script'; ?>
>
		
	</body>
</html>
<?php }
/* {block "system_head"} */
class Block_70050953691320a2d117e2_98472623 extends \Smarty\Runtime\Block
{
public function callBlock(\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = '/var/www/html/school/system/bundles/Gui2/Resources/views';
}
}
/* {/block "system_head"} */
/* {block "html"} */
class Block_1378737202691320a2d12bf9_31997099 extends \Smarty\Runtime\Block
{
public function callBlock(\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = '/var/www/html/school/system/bundles/Gui2/Resources/views';
?>

					  <?php echo $_smarty_tpl->getValue('sHtml');?>

				  <?php
}
}
/* {/block "html"} */
/* {block "system_footer"} */
class Block_1689052791691320a2d13224_12808290 extends \Smarty\Runtime\Block
{
public function callBlock(\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = '/var/www/html/school/system/bundles/Gui2/Resources/views';
}
}
/* {/block "system_footer"} */
/* {block "system_footer_ready"} */
class Block_302841405691320a2d13754_02724506 extends \Smarty\Runtime\Block
{
public function callBlock(\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = '/var/www/html/school/system/bundles/Gui2/Resources/views';
}
}
/* {/block "system_footer_ready"} */
}
