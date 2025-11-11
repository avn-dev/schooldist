{extends file="system/bundles/TsAccommodationLogin/Resources/views/layout/master.tpl"}

{block name="title"}{'Payments'|L10N} - {$oSchool->name|L10N}{/block}

{block name="content"}

    {$null = null}

    {assign var=dateFormat value=Ext_Thebing_Format::getDateFormat($oSchool->id)}

    <div class="content-header">
        <h1>{'Payments'|L10N}</h1>
    </div>

    <div class="content">
        {foreach $groupings as $grouping}
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{$grouping->date|date_format:$dateFormat}</h3>
                <div class="box-tools pull-right">
                    {if $grouping->file}
                    <a target="_blank" href="{route name="TsAccommodationLogin.accommodation_payments_pdf" groupingId = $grouping->id}"><i class="fa fa-file-pdf-o"></i></a>
                    {/if}
                </div>
            </div>

            <div class="box-body table-responsive no-padding">

                <table class="table table-striped table-hover">
                    <tr>
                        <th style="width:200px;">{'Payment'|L10N}</th>
                        <th style="width:auto;">{'Customer'|L10N}</th>
                        <th style="width:250px;">{'Service'|L10N}</th>
                        <th style="width:150px;"class="text-right">{'Amount'|L10N}</th>
                    </tr>
                    {$payments = $grouping->getJoinedObjectChilds('payments')}
                    {foreach $payments as $payment}
                        {$customer = Ext_TS_Inquiry_Contact_Traveller::getInstance($payment->customer_id)}
                        {$customerName = $customer->getName()}
                        {$resultData = [
                        'currency_id' => $payment->payment_currency_id
                        ]}
                        {$oMealCategory = Ext_Thebing_Accommodation_Meal::getInstance($payment->meal_id)}
                        {$oRoom = Ext_Thebing_Accommodation_Room::getInstance($payment->room_id)}
                        {$oRoomCategory = Ext_Thebing_Accommodation_Roomtype::getInstance($oRoom->type_id)}
                    <tr>
                        <td>{$payment->timepoint|date_format:$dateFormat} - {$payment->until|date_format:$dateFormat}</td>
                        <td>{$customerName}</td>
                        <td>{$oRoomCategory->getName()} / {$oMealCategory->getName()}</td>
                        <td class="text-right">{$formatAmount->format($payment->amount, $null, $resultData)}</td>
                    </tr>
                    {/foreach}
                    <tr>
                        <th>{'Total'|L10N}</th>
                        <td></td>
                        <td></td>
                        <th class="text-right">{$formatAmount->format($grouping->amount, $null, $resultData)}</th>
                    </tr>
                </table>
            </div>
        </div>
        {/foreach}
    </div>

{/block}


