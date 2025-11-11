<?php


/*
 * -- webDynamics GUI --
 * Björn Goetschke <bg@plan-i.de>
 *
 * copyright by plan-i GmbH
 *
 * Depends on:
 * - webDynamics basic admin functions (admin.inc.php)
 * - webDynamics class: DB
 * - webDynamics class: SmartyWrapper
 */

// Load the admin.inc.php to use the header/footer functions.
include_once(realpath(dirname(__FILE__).'/../').'/admin.inc.php');

// Load the GUI classes.
include_once(realpath(dirname(__FILE__)).'/gui.smartywrapper.php');
include_once(realpath(dirname(__FILE__)).'/gui.element.php');
include_once(realpath(dirname(__FILE__)).'/gui.elementlist.php');
include_once(realpath(dirname(__FILE__)).'/gui.string.php');
include_once(realpath(dirname(__FILE__)).'/gui.escapedstring.php');
include_once(realpath(dirname(__FILE__)).'/gui.adminheader.php');
include_once(realpath(dirname(__FILE__)).'/gui.adminfooter.php');
include_once(realpath(dirname(__FILE__)).'/gui.headline.php');
include_once(realpath(dirname(__FILE__)).'/gui.link.php');
include_once(realpath(dirname(__FILE__)).'/gui.pagelink.php');
include_once(realpath(dirname(__FILE__)).'/gui.image.php');
include_once(realpath(dirname(__FILE__)).'/gui.div.php');
include_once(realpath(dirname(__FILE__)).'/gui.p.php');
include_once(realpath(dirname(__FILE__)).'/gui.span.php');
include_once(realpath(dirname(__FILE__)).'/gui.table.php');
include_once(realpath(dirname(__FILE__)).'/gui.formautocomplete.php');
include_once(realpath(dirname(__FILE__)).'/gui.formsimple.php');
include_once(realpath(dirname(__FILE__)).'/gui.formbutton.php');
include_once(realpath(dirname(__FILE__)).'/gui.formpassword.php');
include_once(realpath(dirname(__FILE__)).'/gui.formcheckbox.php');
include_once(realpath(dirname(__FILE__)).'/gui.formreset.php');
include_once(realpath(dirname(__FILE__)).'/gui.formsubmit.php');
include_once(realpath(dirname(__FILE__)).'/gui.formhidden.php');
include_once(realpath(dirname(__FILE__)).'/gui.formtext.php');
include_once(realpath(dirname(__FILE__)).'/gui.formcalendar.php');
include_once(realpath(dirname(__FILE__)).'/gui.formtextarea.php');
include_once(realpath(dirname(__FILE__)).'/gui.formselect.php');
include_once(realpath(dirname(__FILE__)).'/gui.form.php');
include_once(realpath(dirname(__FILE__)).'/gui.page.php');
include_once(realpath(dirname(__FILE__)).'/gui.extendedform.php');
include_once(realpath(dirname(__FILE__)).'/gui.tabbox.php');
include_once(realpath(dirname(__FILE__)).'/gui.formfile.php');

