<?php

namespace Office\Controller;

use MVC_Abstract_Controller;
use WDDate;
use Access;
use classExtension_Office;
use classExtensionDao_Office;
use Ext_Office_Reports;
use Ext_Office_Contract;
use Ext_Office_Tickets_Backend;
use Ext_Office_TicketsLight;

include_once(\Util::getDocumentRoot()."system/includes/functions.inc.php");
include_once(\Util::getDocumentRoot()."system/extensions/office/office.inc.php");

class OverviewController extends MVC_Abstract_Controller{

	/**
	 * Login für diesen Controller ausschalten.
	 */
	protected $_sAccessRight = false;

	/**
	 * Zugriffsbeschränkung per Token ausschalten.
	 */
	protected $_bUseToken = false;

	public function postRefreshOverviewAction() {

        // originally declared in admin/includes/office.inc.php and
        // system/extensions/office/office.inc.php
        $objOffice = new classExtension_Office;
        $aConfigData = $objOffice->getConfigData();
        $objOfficeDao = new classExtensionDao_Office($aConfigData);
        $aTypeNames = array("letter" => "Brief", "fax" => "Fax", "offer" => "Angebot", "account" => "Rechnung", "credit" => "Gutschrift", "cancellation_invoice" => "Stornorechnung", "reminder" => "Mahnung", "contract" => "Dauerauftrag");

		header('Access-Control-Allow-Origin: *');

        $this->set('overview_charts', null);
        $this->set('overview_detail', null);
        $this->set('overview_payment', null);
        $this->set('overview_due_tickets', null);
        $this->set('overview_due_contracts_content', null);
        $this->set('overview_due_contracts_total', null);
		$this->set('overview_receivables_content', null);
		$this->set('overview_receivables_total', null);
        $this->set('overview_liabilities_content', null);
        $this->set('overview_liabilities_total', null);
        $this->set('overview_tickets_content', null);
        $this->set('overview_tickets_total', null);
        
        $oAccess = Access::getInstance();
        if (!$oAccess->hasRight('office_reports')) {
            return;
        }

        // ===== gather report data =====

        $oReports = new Ext_Office_Reports($aConfigData);

        // get timeframe
        $intMonthStart = mktime(0, 0, 0, date("m"), 1, date("Y"));
        $intMonthEnd = mktime(23, 59, 59, date("m"), date("t"), date("Y"));
        $intLastMonthEnd = ($intMonthStart - 1);
        $intLastMonthStart = mktime(0, 0, 0, date("m", $intLastMonthEnd), 1, date("Y", $intLastMonthEnd));
        $intYearStart = mktime(0, 0, 0, 1, 1, date("Y"));
        $intYearEnd = mktime(23, 59, 59, 12, 31, date("Y"));
        $intLastYearStart = mktime(0, 0, 0, 1, 1, date("Y") - 1);
        $intLastYearEnd = mktime(23, 59, 59, 12, 31, date("Y") - 1);

        // get invoice sum of current month
        $arrStatsMonthInvoice = $oReports->getDocumentStats('account', $intMonthStart, $intMonthEnd, array('hide' => array('draft')));
        $arrStatsMonthCredit = $oReports->getDocumentStats('credit', $intMonthStart, $intMonthEnd, array('hide' => array('draft')));
        $arrStatsMonthCancellationInvoice = $oReports->getDocumentStats('cancellation_invoice', $intMonthStart, $intMonthEnd, array('hide' => array('draft')));
        $arrStatsMonthOffer = $oReports->getDocumentStats('offer', $intMonthStart, $intMonthEnd, array('show' => array('released')));
        $arrStatsMonthAcceptedOffer = $oReports->countAcceptedOffers($intMonthStart, $intMonthEnd);
        $arrStatsMonthPayments = $oReports->getPaymentsInPeriod($intMonthStart, $intMonthEnd);

        // get invoice sum of last month
        $arrStatsLastMonthInvoice = $oReports->getDocumentStats('account', $intLastMonthStart, $intLastMonthEnd, array('hide' => array('draft')));
        $arrStatsLastMonthCredit = $oReports->getDocumentStats('credit', $intLastMonthStart, $intLastMonthEnd, array('hide' => array('draft')));
        $arrStatsLastMonthCancellationInvoice = $oReports->getDocumentStats('cancellation_invoice', $intLastMonthStart, $intLastMonthEnd, array('hide' => array('draft')));
        $arrStatsLastMonthOffer = $oReports->getDocumentStats('offer', $intLastMonthStart, $intLastMonthEnd, array('show' => array('released')));
        $arrStatsLastMonthAcceptedOffer = $oReports->countAcceptedOffers($intLastMonthStart, $intLastMonthEnd);
        $arrStatsLastMonthPayments = $oReports->getPaymentsInPeriod($intLastMonthStart, $intLastMonthEnd);

        // get invoice sum of current year
        $arrStatsYearInvoice = $oReports->getDocumentStats('account', $intYearStart, $intYearEnd, array('hide' => array('draft')));
        $arrStatsYearCredit = $oReports->getDocumentStats('credit', $intYearStart, $intYearEnd, array('hide' => array('draft')));
        $arrStatsYearCancellationInvoice = $oReports->getDocumentStats('cancellation_invoice', $intYearStart, $intYearEnd, array('hide' => array('draft')));
        $arrStatsYearOffer = $oReports->getDocumentStats('offer', $intYearStart, $intYearEnd, array('show' => array('released')));
        $arrStatsYearAcceptedOffer = $oReports->countAcceptedOffers($intYearStart, $intYearEnd);
        $arrStatsYearPayments = $oReports->getPaymentsInPeriod($intYearStart, $intYearEnd);

        // get invoice sum of last year
        $arrStatsLastYearInvoice = $oReports->getDocumentStats('account', $intLastYearStart, $intLastYearEnd, array('hide' => array('draft')));
        $arrStatsLastYearCredit = $oReports->getDocumentStats('credit', $intLastYearStart, $intLastYearEnd, array('hide' => array('draft')));
        $arrStatsLastYearCancellationInvoice = $oReports->getDocumentStats('cancellation_invoice', $intLastYearStart, $intLastYearEnd, array('hide' => array('draft')));
        $arrStatsLastYearOffer = $oReports->getDocumentStats('offer', $intLastYearStart, $intLastYearEnd, array('show' => array('released')));
        $arrStatsLastYearAcceptedOffer = $oReports->countAcceptedOffers($intLastYearStart, $intLastYearEnd);
        $arrStatsLastYearPayments = $oReports->getPaymentsInPeriod($intLastYearStart, $intLastYearEnd);

        $arrReceivables = $objOfficeDao->getReceivables();
        $arrLiabilities = $objOfficeDao->getLiabilities();
        $intReceivables = 0;
        $intPayables = 0;
        foreach ((array)$arrReceivables as $arrReceivable) {
            $intReceivables += $arrReceivable['receivable'];
        }
        foreach ((array)$arrLiabilities as $arrLiability) {
            $intLiabilities += $arrLiability['receivable'];
        }

        $intPaymentsBalance = $intReceivables - $intLiabilities;

        $oDate = new WDDate();
        $aMonth = $oDate->getMonthLimits();
        $iEndOfMonth = $aMonth['end'];
        $iNow = time();

        $iIncludeDays = (date("d", $iEndOfMonth) - date("d", $iNow));
        $iIncludeDays = (date("j", $iEndOfMonth) - date("j", $iNow));
        if ($iIncludeDays < 14) {
            $iIncludeDays = 14;
        }

        $oContract 	= new Ext_Office_Contract();
        $aContracts = $oContract->getDueList('company', false, $iIncludeDays);

        $oTicket = new Ext_Office_Tickets_Backend();
        $aTicketsStats = $oTicket->getStartPageStatsList();

/* ( Die Tabelle "office_ticket_light_config" gibt es nicht, deswegen kann das nicht funktionieren )
        $oTickets = new Ext_Office_TicketsLight();
        $aTickets = $oTickets->getTickets(array());
        $aPriorities = $oTickets->getPriorities();
*/

        // ===== overview_charts =====

        $aOverviewCharts = array(
            'chart_month' => array(),
            'y_ticks_month' => array(),
            'chart_quarter' => array()
        );
        $iMax = 0;
		$intChartTimeStart = $intYearStart;
		for ($i = 0; $i < 12; $i++) {
            $intChartTimeEnd = mktime(23, 59, 59, date("m", $intChartTimeStart), date("t", $intChartTimeStart), date("Y", $intChartTimeStart));
            $arrChartInvoice = $oReports->getDocumentStats('account', $intChartTimeStart, $intChartTimeEnd, array('show' => array('released', 'paid'), 'OR_type' => array('credit', 'cancellation_invoice')));
            $aOverviewCharts['chart_month'][$i] = array(
                'label' => strftime("%b", $intChartTimeStart),
                'value' => round($arrChartInvoice['sum_net'])
            );
            if ($iMax) {
                $iMax = max($iMax, $arrChartInvoice['sum_net']); 
            } else {
                $iMax = $arrChartInvoice['sum_net'];
            }
            $intChartTimeStart = ($intChartTimeEnd + 1);
        }
        $iTick = ($iMax / 5 * 2);
        $iLength = strlen(ceil($iTick));
        $iOp = bcpow(10, ($iLength - 1));
        $iTick = ceil($iTick / $iOp) * $iOp / 2;
        for ($i = 0; $i < 5; $i++) {
            $aOverviewCharts['y_ticks_month'][$i] = array(
                'label' => ($iTick * ($i + 1)),
                'v' => ($iTick * ($i + 1))
            );
        }
        $intChartTimeStart = $intYearStart;
        for ($i = 0; $i < 4; $i++) {
            $intChartTimeEnd = strtotime("+3 month", $intChartTimeStart)-1;
            $arrChartInvoice = $oReports->getDocumentStats('account', $intChartTimeStart, $intChartTimeEnd, array('show'=>array('released', 'paid'), 'OR_type'=>array('credit', 'cancellation_invoice')));
            $aOverviewCharts['chart_quarter'][$i] = array(
                'label' => strftime("%b", $intChartTimeStart).' - '.strftime("%b", $intChartTimeEnd),
                'value' => round($arrChartInvoice['sum_net'])
            );
            $intChartTimeStart = ($intChartTimeEnd + 1);
        }
        $this->set('overview_charts', $aOverviewCharts);

        // ===== overview_detail =====

        $sOverviewDetail = '
            <table cellpadding="0" cellspacing="0" border="0" class="tbl100 highlightRows">
                <colgroup>
                    <col width="28%"/>
                    <col width="4%"/>
                    <col width="14%"/>
                    <col width="4%"/>
                    <col width="14%"/>
                    <col width="4%"/>
                    <col width="14%"/>
                    <col width="4%"/>
                    <col width="14%"/>
                </colgroup>
                <tr class="borderBottom">
                    <th>Zeitraum</th>
                    <th colspan="2">'.strftime("%b %Y", $intMonthEnd).'</th>
                    <th colspan="2">'.strftime("%b %Y", $intLastMonthEnd).'</th>
                    <th colspan="2">'.strftime("%Y", $intYearEnd).'</th>
                    <th colspan="2">'.strftime("%Y", $intLastYearEnd).'</th>
                </tr>
                <tr class="borderLeft">
                    <td class="noBorder">Rechnungen (netto)</td>
                    <td align="right">'.(int)$arrStatsMonthInvoice['count'].'</td>
                    <td align="right">'.number_format($arrStatsMonthInvoice['sum_net'], 2, ",", ".").' &euro;</td>
                    <td align="right">'.(int)$arrStatsLastMonthInvoice['count'].'</td>
                    <td align="right">'.number_format($arrStatsLastMonthInvoice['sum_net'], 2, ",", ".").' &euro;</td>
                    <td align="right">'.(int)$arrStatsYearInvoice['count'].'</td>
                    <td align="right">'.number_format($arrStatsYearInvoice['sum_net'], 2, ",", ".").' &euro;</td>
                    <td align="right">'.(int)$arrStatsLastYearInvoice['count'].'</td>
                    <td align="right">'.number_format($arrStatsLastYearInvoice['sum_net'], 2, ",", ".").' &euro;</td>
                </tr>
                <tr class="borderLeft">
                    <td class="noBorder">Stornorechnungen (netto)</td>
                    <td align="right">'.(int)$arrStatsMonthCancellationInvoice['count'].'</td>
                    <td align="right">'.number_format($arrStatsMonthCancellationInvoice['sum_net'], 2, ",", ".").' &euro;</td>
                    <td align="right">'.(int)$arrStatsLastMonthCancellationInvoice['count'].'</td>
                    <td align="right">'.number_format($arrStatsLastMonthCancellationInvoice['sum_net'], 2, ",", ".").' &euro;</td>
                    <td align="right">'.(int)$arrStatsYearCancellationInvoice['count'].'</td>
                    <td align="right">'.number_format($arrStatsYearCancellationInvoice['sum_net'], 2, ",", ".").' &euro;</td>
                    <td align="right">'.(int)$arrStatsLastYearCancellationInvoice['count'].'</td>
                    <td align="right">'.number_format($arrStatsLastYearCancellationInvoice['sum_net'], 2, ",", ".").' &euro;</td>
                </tr>
                <tr class="borderLeft">
                    <td class="noBorder">Gutschriften (netto)</td>
                    <td align="right">'.(int)$arrStatsMonthCredit['count'].'</td>
                    <td align="right">'.number_format($arrStatsMonthCredit['sum_net'], 2, ",", ".").' &euro;</td>
                    <td align="right">'.(int)$arrStatsLastMonthCredit['count'].'</td>
                    <td align="right">'.number_format($arrStatsLastMonthCredit['sum_net'], 2, ",", ".").' &euro;</td>
                    <td align="right">'.(int)$arrStatsYearCredit['count'].'</td>
                    <td align="right">'.number_format($arrStatsYearCredit['sum_net'], 2, ",", ".").' &euro;</td>
                    <td align="right">'.(int)$arrStatsLastYearCredit['count'].'</td>
                    <td align="right">'.number_format($arrStatsLastYearCredit['sum_net'], 2, ",", ".").' &euro;</td>
                </tr>
                <tr class="borderLeft borderTop">
                    <th style="border-left:0;">Umsatz (netto)</th>
                    <th style="text-align:right;" colspan="2">'.number_format(($arrStatsMonthInvoice['sum_net'] + $arrStatsMonthCredit['sum_net'] + $arrStatsMonthCancellationInvoice['sum_net']), 2, ",", ".").' &euro;</th>
                    <th style="text-align:right;" colspan="2">'.number_format(($arrStatsLastMonthInvoice['sum_net'] + $arrStatsLastMonthCredit['sum_net'] + $arrStatsLastMonthCancellationInvoice['sum_net']), 2, ",", ".").' &euro;</th>
                    <th style="text-align:right;" colspan="2">'.number_format(($arrStatsYearInvoice['sum_net'] + $arrStatsYearCredit['sum_net'] + $arrStatsYearCancellationInvoice['sum_net']), 2, ",", ".").' &euro;</th>
                    <th style="text-align:right;" colspan="2">'.number_format(($arrStatsLastYearInvoice['sum_net'] + $arrStatsLastYearCredit['sum_net'] + $arrStatsLastYearCancellationInvoice['sum_net']), 2, ",", ".").' &euro;</th>
                </tr>
                <tr class="borderLeft">
                    <td class="noBorder">&nbsp;</td>
                    <td colspan="2">&nbsp;</td>
                    <td colspan="2">&nbsp;</td>
                    <td colspan="2">&nbsp;</td>
                    <td colspan="2">&nbsp;</td>
                </tr>
                <tr class="borderLeft">
                    <th class="noBorder">Zahlungseingang</th>
                    <td align="right" colspan="2">'.number_format($arrStatsMonthPayments['sum'], 2, ",", ".").' &euro;</td>
                    <td align="right" colspan="2">'.number_format($arrStatsLastMonthPayments['sum'], 2, ",", ".").' &euro;</td>
                    <td align="right" colspan="2">'.number_format($arrStatsYearPayments['sum'], 2, ",", ".").' &euro;</td>
                    <td align="right" colspan="2">'.number_format($arrStatsLastYearPayments['sum'], 2, ",", ".").' &euro;</td>
                </tr>
                <tr class="borderLeft">
                    <th class="noBorder">Zahlungseingang (ohne USt.)</th>
                    <td align="right" colspan="2">'.number_format($arrStatsMonthPayments['sum_net'], 2, ",", ".").' &euro;</td>
                    <td align="right" colspan="2">'.number_format($arrStatsLastMonthPayments['sum_net'], 2, ",", ".").' &euro;</td>
                    <td align="right" colspan="2">'.number_format($arrStatsYearPayments['sum_net'], 2, ",", ".").' &euro;</td>
                    <td align="right" colspan="2">'.number_format($arrStatsLastYearPayments['sum_net'], 2, ",", ".").' &euro;</td>
                </tr>
                <tr class="borderLeft">
                    <td class="noBorder">&nbsp;</td>
                    <td colspan="2">&nbsp;</td>
                    <td colspan="2">&nbsp;</td>
                    <td colspan="2">&nbsp;</td>
                    <td colspan="2">&nbsp;</td>
                </tr>
                <tr class="borderLeft">
                    <th class="noBorder">Beauftragte Angebote (netto)</th>
                    <td align="right" colspan="2">'.number_format($arrStatsMonthAcceptedOffer['sum_net'], 2, ",", ".").' &euro;</td>
                    <td align="right" colspan="2">'.number_format($arrStatsLastMonthAcceptedOffer['sum_net'], 2, ",", ".").' &euro;</td>
                    <td align="right" colspan="2">'.number_format($arrStatsYearAcceptedOffer['sum_net'], 2, ",", ".").' &euro;</td>
                    <td align="right" colspan="2">'.number_format($arrStatsLastYearAcceptedOffer['sum_net'], 2, ",", ".").' &euro;</td>
                </tr>
            </table>
        ';
        $this->set('overview_detail', $sOverviewDetail);

        // ===== overview_payment =====

        $sOverviewPayment = '
            <table cellpadding="0" cellspacing="0" border="0" class="tbl100 highlightRows">
                <tr>
                    <th>Forderungen</th>
                    <td style="text-align: right;">'.number_format($intReceivables, 2, ",", ".").' &euro;</td>
                </tr>
                <tr>
                    <th>Verbindlichkeiten</th>
                    <td style="text-align: right;">'.number_format(($intLiabilities * -1), 2, ",", ".").' &euro;</td>
                </tr>
                <tr class="noHighlight borderTop">
                    <th>Gesamt</th>
                    <th style="text-align: right;">'.number_format($intPaymentsBalance, 2, ",", ".").' &euro;</th>
                </tr>
            </table>
        ';
        $this->set('overview_payment', $sOverviewPayment);

        // ===== overview_due_tickets =====

/* ( Die Tabelle "office_ticket_light_config" gibt es nicht, deswegen kann das nicht funktionieren )
        if (count($aTickets) > 0) {
            $sOverviewDueTickets = '
                <table cellpadding="0" cellspacing="0" border="0" class="tbl100 highlightRows">
                    <tr class="noHighlight">
                        <th style="width:15%;">Priorität</th>
                        <th>Titel</th>
                        <th style="width:15%;">Fälligkeit</th>
                    </tr>
            ';
            foreach((array)$aTickets as $aValue) {
                $sOverviewDueTickets .= '
                    <tr>
                        <td '.$oTickets->getPriorityColorStyle($aValue['priority']).'>'.$aPriorities[$aValue['priority']].'</td>
                        <td><a href="/admin/extensions/office_tickets.html?task=detail&id='.$aValue['id'].'">'.$aValue['headline'].'</a></td>
                        <td style="color:red; text-align:right;">'.date('d.m.Y', $aValue['due_date']).'</td>
                    </tr>
                ';
            }
            $sOverviewDueTickets .= '
                </table>
            ';
            $this->set('overview_due_tickets', $sOverviewDueTickets);
        }
*/

        // ===== overview_due_contracts_* =====

        if (count($aContracts) > 0) {
            $sOverviewDueContractsContent = '
                    <table id="overview_due_contracts_table" class="table table-bordered table-striped">
				<thead>
                <tr class="noHighlight borderBottom">
                        <th style="width: 80px;">Fälligkeit</th>
                        <th style="width: auto;">Kunde</th>
                        <th style="width: 200px;">Artikel</th>
                        <th style="width: 80px;">Betrag</th>
                    </tr>
				</thead>
				<tbody>
            ';
            foreach ((array)$aContracts as $aContract) {
                $sStyle = '';
                if ($aContract['due_date'] > time()) {
                    $sStyle = 'style="color: #999;"';
                }
                $sOverviewDueContractsContent .= '
                    <tr class="borderLeft" '.$sStyle.'>
                        <td class="noBorder">'.date('d.m.Y', $aContract['due_date']).'</td>
                        <td>'.$aContract['company'].'</td>
                        <td>'.$aContract['product'].'</td>
                        <td style="text-align: right;">'.number_format($aContract['total'], 2, ",", ".").' &euro;</td>
                    </tr>
                ';
                if ($aContract['due_date'] <= time()) {
                    $iContractsTotal += $aContract['total'];
                }
            }
            $sOverviewDueContractsTotal = number_format($iContractsTotal, 2, ",", ".").' &euro;';
			
			$sOverviewDueContractsContent .= '</tbody><tfoot>
								<tr class="noHighlight borderTop">
									<th>Gesamt</th>
									<th colspan="9" style="text-align: right;">'.$sOverviewDueContractsTotal.'</th>
								</tr>
							</tfoot></table>';
			
            $this->set('overview_due_contracts_content', $sOverviewDueContractsContent);
        }

        // ===== overview_receivables_* =====

        if (!empty($arrReceivables)) {
            $sOverviewReceivablesContent = '
				<table id="overview_receivables_table" class="table table-bordered table-striped">
				<thead>
                <tr class="noHighlight borderBottom">
                    <th style="width:auto;">Kunde</th>
                    <th style="width:20px;">&nbsp;</th>
                    <th style="width:80px;">Nummer</th>
                    <th style="width:100px;">Art</th>
                    <th style="width:80px;">Datum</th>
                    <th style="width:80px;">Fälligkeit</th>
                    <th style="width:60px;">Mahnstufe</th>
                    <th style="width:110px;">Bezahlt</th>
                    <th style="width:20px;">&nbsp;</th>
                    <th style="width:110px;">Offen</th>
                </tr>
				</thead>
				<tbody>
            ';
            foreach ((array)$arrReceivables as $arrReceivable) {
                $bDue = false;
                if ($arrReceivable['due']) {
                    $bDue = true;
                }
                $sDueDate = '';
                if ($arrReceivable['due_date']) {
                    $sDueDate = date('d.m.Y', $arrReceivable['due_date']);
                }
                $sOverviewReceivablesContent .= '
                    <tr '.(($bDue)?'style="color:red;"':'').'>
                        <td>'.$arrReceivable['name'].'</td>
                        <td style="text-align:center;"><img src="/admin/media/acrobat.png" alt="PDF Öffnen" onClick="bolReadyState=0;window.open(\'office/office.php?action=openPdf&document_id='.$arrReceivable['id'].'\');"/></td>
                        <td style="text-align:right;">'.$arrReceivable['number'].'</td>
                        <td class="">'.$aTypeNames[$arrReceivable['type']].'</td>
                        <td>'.date('d.m.Y', $arrReceivable['date']).'</td>
                        <td>'.$sDueDate.'</td>
                        <td style="text-align:center;">'.(int)$arrReceivable['dunning_level'].'</td>
                        <td style="text-align:right;">'.number_format($arrReceivable['payed'], 2, ",", ".").' &euro;</td>
                        <td style="text-align:center;"><img src="/admin/extensions/office/images/import.png" alt="Zahlung eintragen" onClick="openDialog(\''.number_format($arrReceivable['receivable'], 2, ",", ".").'\', '.(int)$arrReceivable['id'].', \''.number_format($arrReceivable['cash_discount_receivable'], 2, ",", ".").'\', \''.number_format($arrReceivable['cash_discount'], 2, ",", ".").'\');"/></td>
                        <td style="text-align:right;">'.number_format($arrReceivable['receivable'], 2, ",", ".").' &euro;</td>
                    </tr>
                ';
            }
			 
            $sOverviewReceivablesTotal = number_format($intReceivables, 2, ",", ".").' &euro;';
			
			$sOverviewReceivablesContent .= '</tbody><tfoot>
								<tr class="noHighlight borderTop">
									<th>Gesamt</th>
									<th id="overview_receivables_total" colspan="9" style="text-align: right;">'.$sOverviewReceivablesTotal.'</th>
								</tr>
							</tfoot></table>';
			
            $this->set('overview_receivables_content', $sOverviewReceivablesContent);
			
        }

        // ===== overview_liabilities_* =====

        if (!empty($arrLiabilities)) {
            $sOverviewLiabilitiesContent = '
                <table id="overview_liabilities_table" class="table table-bordered table-striped">
				<thead>
                <tr class="noHighlight borderBottom">
                    <th style="width:auto;">Kunde</th>
                    <th style="width:20px;">&nbsp;</th>
                    <th style="width:80px;">Nummer</th>
                    <th style="width:100px;">Art</th>
                    <th style="width:80px;">Datum</th>
                    <th style="width:80px;">Fälligkeit</th>
                    <th style="width:60px;">Mahnstufe</th>
                    <th style="width:110px;">Bezahlt</th>
                    <th style="width:20px;">&nbsp;</th>
                    <th style="width:110px;">Offen</th>
                </tr>
				</thead>
				<tbody>
            ';
            foreach ((array)$arrLiabilities as $arrLiability) {
                $bDue = false;
                if ($arrLiability['due']) {
                    $bDue = true;
                }
                $sDueDate = '';
                if ($arrLiability['due_date']) {
                    $sDueDate = date('d.m.Y', $arrLiability['due_date']);
                }
                $sOverviewLiabilitiesContent .= '
                    <tr class="borderLeft" '.(($bDue)?'style="color:red;"':'').'>
                        <td class="noBorder">'.$arrLiability['name'].'</td>
                        <td style="text-align:center;"><img src="/admin/media/acrobat.png" alt="PDF Öffnen" onClick="bolReadyState=0;window.open(\'office/office.php?action=openPdf&document_id='.$arrLiability['id'].'\');"/></td>
                        <td class="noBorder" style="text-align:right;">'.$arrLiability['number'].'</td>
                        <td class="">'.$aTypeNames[$arrLiability['type']].'</td>
                        <td>'.date('d.m.Y', $arrLiability['date']).'</td>
                        <td>'.$sDueDate.'</td>
                        <td style="text-align:center;">'.(int)$arrLiability['dunning_level'].'</td>
                        <td style="text-align:right;">'.number_format($arrLiability['payed'], 2, ",", ".").' &euro;</td>
                        <td style="text-align:center;"><img src="/admin/extensions/office/images/import.png" alt="Zahlung eintragen" onClick="openDialog(\''.number_format($arrLiability['receivable'], 2, ",", ".").'\', '.(int)$arrLiability['id'].');"/></td>
                        <td class="noBorder" style="text-align:right;">'.number_format($arrLiability['receivable'], 2, ",", ".").' &euro;</td>
                    </tr>
                ';
            }
			
            $sOverviewLiabilitiesTotal = number_format($intLiabilities, 2, ",", ".").' &euro;';
			$sOverviewReceivablesContent .= '</tbody><tfoot>
								<tr class="noHighlight borderTop">
									<th>Gesamt</th>
									<th colspan="9" style="text-align: right;">'.$sOverviewLiabilitiesTotal.'</th>
								</tr>
							</tfoot></table>';
			
            $this->set('overview_liabilities_content', $sOverviewLiabilitiesContent);
        }

        // ===== overview_tickets_* =====

        $sOverviewTicketsContent = '
            <tr class="noHighlight">
                <th style="width:160px;">Kunde</th>
                <th style="width:auto;">Projekt</th>
                <th style="width:50px;">Tickets</th>
                <th style="width:130px;" colspan="2">Aufwandsschätzung</th>
                <th style="width:120px;" colspan="2">Zeiterfassung</th>
                <th style="width:100px;">Betrag</th>
            </tr>
            <tr class="noHighlight borderBottom">
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td style="width:65px;">Stunden</td>
                <td style="width:65px;">Betrag</td>
                <td style="width:60px;">Brutto</td>
                <td style="width:60px;">Netto</td>
                <td>&nbsp;</td>
            </tr>
        ';
        $fTotalPrice = 0;
        foreach ((array)$aTicketsStats as $aTicket) {
            $sOverviewTicketsContent .= '
                <tr class="borderLeft">
                    <td class="noBorder">'.$aTicket['customer'].'</td>
                    <td>'.$aTicket['project'].'</td>
                    <td style="text-align:right;">'.(int)$aTicket['count'].'<br/><span style="color:#999;">'.(int)$aTicket['outstanding_count'].'</span></td>
                    <td style="text-align:right;">'.number_format($aTicket['hours'], 2, ',', '.').'<br/><span style="color:#999;">'.number_format($aTicket['outstanding_hours'], 2, ',', '.').'</span></td>
                    <td style="text-align:right;">'.number_format($aTicket['money'], 2, ',', '.').' €<br/><span style="color:#999;">'.number_format($aTicket['outstanding_money'], 2, ',', '.').' €</span></td>
                    <td style="text-align:right;">'.$aTicket['total'].'<br/><span style="color:#999;">'.$aTicket['outstanding_total'].'</span></td>
                    <td style="text-align:right;">'.$aTicket['factored'].'<br/><span style="color:#999;">'.$aTicket['outstanding_factored'].'</span></td>
                    <td style="text-align:right;">'.number_format($aTicket['price'], 2, ',', '.').' €<br/><span style="color:#999;">'.number_format($aTicket['outstanding_price'], 2, ',', '.').' €</span></td>
                </tr>
            ';
            $fTotalPrice += $aTicket['price'];
            $fTotalOutstandingPrice += $aTicket['outstanding_price'];
        }
        $sOverviewTicketsTotal = number_format($fTotalPrice, 2, ',', '.').' €<br/><span style="color:#999;">'.number_format($fTotalOutstandingPrice, 2, ',', '.').' €</span>';
        $this->set('overview_tickets_content', $sOverviewTicketsContent);
        $this->set('overview_tickets_total', $sOverviewTicketsTotal);

	}

}
