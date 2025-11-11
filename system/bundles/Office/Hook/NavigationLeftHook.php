<?php

namespace Office\Hook;

class NavigationLeftHook extends \Core\Service\Hook\AbstractHook {
	
	public function run(array &$mixInput) {
		
		if($mixInput['name'] == 'office') {

			/*
			 * Array with office navigation items
			 */
			$arrOfficeNavigation = array();

			$arrOfficeNavigation[10] = array("Übersicht",				"/admin/extensions/office.html", 0, "office", "/admin/extensions/office/images/office.png", 'office');

			$arrOfficeNavigation[20] = array("Dokumente",				"/admin/extensions/office_documents.html", 0, "office_documents", "/admin/extensions/office/images/office_documents.png", 'office.documents');
			$arrOfficeNavigation[30] = array("Projekte",				"/admin/extensions/office_projects.html", 0, "office_projects", "/admin/extensions/office/images/office_projects.png", 'office.projects');
			$arrOfficeNavigation[35] = array("Verträge",				"/admin/extensions/office_contracts.html", 0, "office_contracts", "/admin/extensions/office/images/office_contracts.png", 'office.contracts');

			$arrOfficeNavigation[50] = array("Tickets",					"/admin/extensions/office_tickets.html", 0, "office_tickets", "/admin/extensions/office/images/office_tickets.png", 'office.tickets');

			$arrOfficeNavigation[60] = array("Kunden",					"/admin/extensions/office_customers.html", 0, "office_customers", "/admin/extensions/office/images/office_customers.png", 'office.customers');

			$arrOfficeNavigation[61] = array("Abrechnungsliste",		"/wdmvc/gui2/page/office_settlement_list", 0, "office_settlement_list", "/admin/extensions/office/images/money.png", 'office.settlement_list');

			$arrOfficeNavigation[80] = array("Produkte",				"/wdmvc/gui2/page/office_products", 0, "office_articles", "/admin/extensions/office/images/office_articles.png", 'office.articles');

			$arrOfficeNavigation[70] = array("Mitarbeiter",				"/admin/extensions/office_employees.html", 0, "office_employees", "/admin/extensions/office/images/office_employees.png", 'office.employees');
			$arrOfficeNavigation[71] = array("Pflege",					"/admin/extensions/office_employees.html", 1, "office_employees", "/admin/extensions/office/images/office_employees.png", 'office.employees.list');
			$arrOfficeNavigation[72] = array("Zeiterfassung",			"/admin/extensions/office_timeclock.html", 1, "office_timeclock", "/admin/extensions/office/images/office_timeclock.png", 'office.timeclock');
			$arrOfficeNavigation[74] = array("Abwesenheit",				"/admin/extensions/office_absence.html", 1, "office_absence", "/admin/extensions/office/images/office_absence.png", 'office.absence');
			$arrOfficeNavigation[76] = array("Urlaub",					"/admin/extensions/office/employees_holidays.html", 1, "office_employees", "/admin/extensions/office/images/office_absence.png", 'office.employees.holidays');
			$arrOfficeNavigation[78] = array("Anschaffungen",			"/admin/extensions/office/employees_items.html", 1, "office_employees", "/admin/extensions/office/images/office_employees.png", 'office.employees.items');

			// sk@plan-i.de
			//$arrOfficeNavigation[85] = array("Kalender",				"/admin/extensions/office_calendar.html", 0, "office", "/admin/extensions/office/images/office_calendar.png");

			$arrOfficeNavigation[90] = array("Auswertungen",			"/admin/extensions/office_reports.html", 0, "office_reports", "/admin/extensions/office/images/office_reports.png", 'office.reports');
			$arrOfficeNavigation[91] = array("Monatsauswertung",		"/admin/extensions/office_reports.html?view=month", 1, "office_reports", "/admin/extensions/office/images/office_reports.png", 'office.reports.month');
			$arrOfficeNavigation[92] = array("Kundenauswertung",		"/admin/extensions/office_reports.html?view=customers", 1, "office_reports", "/admin/extensions/office/images/office_reports.png", 'office.reports.customers');
			$arrOfficeNavigation[93] = array("Geschäftsentwicklung",	"/admin/extensions/office_reports.html?view=development", 1, "office_reports", "/admin/extensions/office/images/office_reports.png", 'office.reports.development');
			$arrOfficeNavigation[94] = array("Auftragsbestand",			"/admin/extensions/office_reports.html?view=orderbook", 1, "office_reports", "/admin/extensions/office/images/office_reports.png", 'office.reports.orderbook');
			$arrOfficeNavigation[95] = array("Arbeitszeiten",			"/admin/extensions/office_employee_worktimes.html", 1, "office_absence", "/admin/extensions/office/images/office_reports.png", 'office.reports.employees.worktimes');
			$arrOfficeNavigation[96] = array("Projekte",				"/admin/extensions/office_project_stats.html", 1, "office_reports", "/admin/extensions/office/images/office_reports.png", 'office.reports.projects');
			$arrOfficeNavigation[97] = array("Fehlzeiten",				"/admin/extensions/office_employee_freetimes.html", 1, "office_employee_freetimes", "/admin/extensions/office/images/office_reports.png", 'office.reports.employees.freetimes');

			$arrOfficeNavigation[140] = array("Konfiguration",					"/admin/extensions/office_config.html?task=admin&admin=config", 0, "office_config", "/admin/extensions/office/images/office_config.png", 'office.config');
			$arrOfficeNavigation[150] = array("Einstellungen",					"/admin/extensions/office_config.html?task=admin&admin=config", 1, "office_config", "/admin/extensions/office/images/office_config.png", 'office.config.list');
			$arrOfficeNavigation[180] = array("Dokumente Schriftarten",			"/admin/extensions/office_config.html?task=admin&admin=fonts", 1, "office_config", "/admin/extensions/office/images/office_config.png", 'office.config.fonts');
			$arrOfficeNavigation[190] = array("Dokumente Signaturen",			"/admin/extensions/office_config.html?task=admin&admin=signatures", 1, "office_config", "/admin/extensions/office/images/office_signatures.png", 'office.config.signatures');
			$arrOfficeNavigation[200] = array("Dokumente Textbausteine",		"/admin/extensions/office_config.html?task=admin&admin=template", 1, "office_config", "/admin/extensions/office/images/office_config.png", 'office.config.templates');
			$arrOfficeNavigation[210] = array("Dokumente Vorlagen",				"/admin/extensions/office_config.html?task=admin&admin=forms", 1, "office_config", "/admin/extensions/office/images/office_forms.png", 'office.config.forms');
			$arrOfficeNavigation[220] = array("Dokumente Zahlungsbedingungen",	"/admin/extensions/office_payment_terms.html", 1, "office_config", "/admin/extensions/office/images/office_payment_terms.png", 'office.config.paymentterms');
			//$arrOfficeNavigation[] = array("-------------",					"", 1, "office", "");
			$arrOfficeNavigation[230] = array("Projekte Projektkategorien",		"/admin/extensions/office_project_categories.html", 1, "office_config", "/admin/extensions/office/images/office_config.png", 'office.project.categories');
			$arrOfficeNavigation[240] = array("Projekte Tätigkeitskategorien",	"/admin/extensions/office_project_activities.html", 1, "office_config", "/admin/extensions/office/images/office_config.png", 'office.project.activities');
			//$arrOfficeNavigation[] = array("-------------",					"", 1, "office", "");
			$arrOfficeNavigation[250] = array("Zeiterfassung Feiertage",		"/admin/extensions/office_timeclock_holidays.html", 1, "office_config", "/admin/extensions/office/images/office_config.png", 'office.timeclock.holidays');
			//$arrOfficeNavigation[] = array("-------------",					"", 1, "office", "");
			$arrOfficeNavigation[258] = array("Kunden » Gruppen",				"/admin/extensions/office/customer_groups.html", 1, "office_customers", "/admin/extensions/office/images/office_config.png", 'office.customers.groups');
			#$arrOfficeNavigation[282] = array("Kunden » Steuerkategorien",		"/admin/extensions/office/tax_categories.html", 1, "office_config", "/admin/extensions/office/images/office_config.png");
			#$arrOfficeNavigation[284] = array("Kunden » Steuersätze",			"/admin/extensions/office/tax_rates.html", 1, "office_config", "/admin/extensions/office/images/office_config.png");
			//$arrOfficeNavigation[] = array("-------------",					"", 1, "office", "");
			$arrOfficeNavigation[290] = array("Mitarbeiter Datenbank",			"/admin/extensions/office_projects_config.html?action=database", 1, "office_config", "/admin/extensions/office/images/office_forms.png", 'office.project.database');
			$arrOfficeNavigation[300] = array("Mitarbeiter Gruppen",			"/admin/extensions/office_project_employees_groups.html", 1, "office_config", "/admin/extensions/office/images/office_forms.png", 'office.employees.groups');
			$arrOfficeNavigation[310] = array("Erlöskonten",					"/wdmvc/gui2/page/office_revenue_accounts", 1, "office_config", "/admin/extensions/office/images/office_config.png", 'office.config.revenue_accounts');
			$arrOfficeNavigation[320] = array("Erlöskonten Zuweisungen",		"/admin/extensions/office/revenue_accounts.php", 1, "office_documents", "/admin/extensions/office/images/office_config.png", 'office.config.revenue_accounts.list');

			$arrOfficeNavigation[330] = array("Produktbereiche",				"/wdmvc/gui2/page/office_product_areas", 1, "office_config", "/admin/extensions/office/images/office_config.png", 'office.config.product_areas');

			//$arrOfficeNavigation[] = array("Mitarbeiter Bewertungseinstellungen",	"/admin/extensions/consulimus_office_project_rates.html", 1, "office", "/admin/extensions/office/images/office_forms.png");

			\System::wd()->executeHook('office_navigation_left', $arrOfficeNavigation);
			ksort($arrOfficeNavigation);
			$arrOfficeNavigation = array_values($arrOfficeNavigation);

			$mixInput['childs'] = $arrOfficeNavigation;

		}
		
	}
	
}
