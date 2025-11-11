<?php

include(\Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");

Ext_Thebing_Access::accesschecker('thebing_management_reports_standard2');

Ext_Thebing_Management_Statistic_Static_UkQuartlerlyReport::display(); 