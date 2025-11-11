{extends file="system/bundles/AdminLte/Resources/views/base.tpl"}

{block name="header"}
    <link rel="stylesheet" href="/ts/activities/resources/css/activity_prices.css">
    <script>
		function reloadSeason() {
			document.prices.submit();
		}

		function reloadPrices() {
			document.prices.submit();
		}
    </script>
    <style>
        .not-available,
        .txt.amount.not-available {
            color: #999999;
        }
        .amount {
            text-align: right;
            width: 150px;
        }
        .table > tbody > tr > td,
        .table > tbody > tr > th {
            line-height: 32px;
        }
        .table {
            width: initial;
        }
        .heading {
            padding-bottom: 20px;
        }
    </style>
{/block}

{block name="content"}

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1><h1 class="heading">{'Marketing » Kosten & Preise » Preise - Aktivitäten'|L10N}</h1></h1>

        {foreach $oSession->getFlashBag()->get('error') as $sMessage}
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <i class="icon fa fa-exclamation"></i> {$sMessage}
            </div>
        {/foreach}
        {foreach $oSession->getFlashBag()->get('success') as $sMessage}
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <i class="icon fa fa-check"></i> {$sMessage}
            </div>
        {/foreach}

    </section>
    <!-- Main content -->
    <section class="content">

    <form method="post" name="prices" action="{route name='TsActivities.prices'}" class="ajax_submit" onkeydown="if (event.keyCode == 13) return false;">
        <div class="box">
            <div class="box-body table-responsive">
                <table cellpadding="5" cellspacing="0" class="table" style="width:100%;">
                    <tr>
                        <th><label class="divToolbarLabelGroup">{'Saison'|L10N}</label></th>
                        <td>
                            <select name="season" onchange="reloadSeason()" class="form-control" style="width:300px">
                                <option value="0">-- {'Saison auswählen'|L10N} --</option>
                                {html_options options=$aSeasons selected=$iSeasonId}
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label class="divToolbarLabelGroup">{'Währung'|L10N}</label></th>
                        <td>
                            <select name="currency" onchange="reloadPrices()" class="form-control" style="width:300px">
                                <option value="0">-- {'Währung auswählen'|L10N} --</option>
                                {html_options options=$aCurrencies selected=$iCurrencyId}
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        {if $aActivities}
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">{'Aktivitätspreise'|L10N}</h3>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-hover">
                    {foreach $aActivities as $oActivity}
						{if !$oActivity->isFreeOfCharge()}
                        <tr>
                            <th colspan="2">
                                <label class="divToolbarLabelGroup">{$oActivity->getName()}</label>
                            </th>
                        </tr>
                        <tr>
                            <td>
                                {$aBillingPeriods[$oActivity->billing_period]}
                            </td>
                            <td>
                                <input type="text" class="txt form-control amount prices" name="amount[{$oActivity->id}][{$iCurrencyId}][{$iSeasonId}]" value="{$aPrice[$oActivity->id]}">
                            </td>
                        </tr>
						{/if}
                    {/foreach}
                </table>
            </div>
        </div>
        <br/>
        <button type="submit" onkeydown="if (event.keyCode == 13) return false;" onclick="document.prices.action = '{route name='TsActivities.prices_save'}'" class="btn btn-primary pull-right">{'Speichern'|L10N}</button>
        <br/><br/>
        {/if}
    </form>
    </div>
    </section>
{/block}