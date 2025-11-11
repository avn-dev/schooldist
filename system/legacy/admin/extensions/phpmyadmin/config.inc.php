<?php

/**
 * Auto-Signon
 */
$cfg['blowfish_secret'] = 'gomohu45';
$cfg['LoginCookieValidity'] = 4 * 3600;
$cfg['CheckConfigurationPermissions'] = false;
$cfg['AllowThirdPartyFraming'] = true;
$i = 0;

$i++;
$cfg['Servers'][$i]['auth_type'] = 'signon';
$cfg['Servers'][$i]['SignonURL'] = '/admin/extensions/phpmyadmin.html';
$cfg['Servers'][$i]['LogoutURL'] = '/admin/extensions/phpmyadmin.html';
$cfg['Servers'][$i]['SignonSession'] = 'PHPSESSID';
$cfg['Servers'][$i]['AllowNoPassword'] = true;

$cfg['Servers'][$i]['host'] = 'localhost';
$cfg['Servers'][$i]['connect_type'] = 'tcp';
$cfg['Servers'][$i]['compress'] = false;
$cfg['Servers'][$i]['extension'] = 'mysql';
$cfg['Servers'][$i]['LoginCookieValidity'] = 4 * 3600;

$cfg['AjaxEnable'] = false;
$cfg['UploadDir'] = '';
$cfg['SaveDir'] = '';

$cfg['FirstLevelNavigationItems'] = 9999;
$cfg['MaxNavigationItems'] = 9999;
$cfg['MaxDbList'] = 9999;
$cfg['MaxTableList'] = 9999;
$cfg['LeftFrameDBSeparator'] = '';
$cfg['LeftFrameTableSeparator']= '';
$cfg['LeftFrameTableLevel'] = '0';
 