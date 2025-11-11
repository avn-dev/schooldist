<style>
    .fidelo_main_container {
        margin-bottom: 50px;
    }

    .fidelo_button {
        padding-left: 15px;
        padding-right: 15px;
        background-color: #01c1b8;
        border: 0px;
        border-radius: 3px;
        color: white;
        box-shadow: 0 4px 4px 0 rgba(0, 0, 0, 0.24), 0 4px 4px 0 rgba(0, 0, 0, 0.19);
    }

    .fidelo_menu {
        background-color: #01c1b8 !important;
        padding: 20px;
        border-radius: 6px;
    }

    .fidelo_menu ul {
        padding: 0px;
        list-style-type: none;
        margin: 0;
        padding: 0;
    }

    .fidelo_menu li {
        padding: 10px;
    }

    .fidelo_menu_active {
        color: #ffffff !important;
    }

    .fidelo_menu {
        font-size: 14px;
        display: flex;
    }

    a:hover {
        background-color: unset !important;
        color: unset !important;
    }

    .fidelo_menu a {
        cursor: pointer;
        color: #dbdbdb;
        text-decoration: none;
        font-family: Open Sans, Arial, sans-serif;
        padding-bottom: 35px;
        font-weight: 600;
    }

    .fidelo_shadow_box {
        line-height: 1.4em;
        font-family: 'Lato', Helvetica, Arial, Lucida, sans-serif;
        font-size: 18px;
        line-height: 1.4em;
        background-color: #FFFFFF;
        border-radius: 14px 14px 14px 14px;
        overflow: hidden;
        padding-top: 23px !important;
        padding-right: 16px !important;
        padding-bottom: 23px !important;
        padding-left: 16px !important;
        margin-top: 8px !important;
        box-shadow: 0px 2px 18px 0px rgba(0, 0, 0, 0.3);
    }

    .fidelo_text {
        color: #000000;
        text-decoration: none;
        font-family: 'Lato', Helvetica, Arial, Lucida, sans-serif;
        font-size: 13px;
    }

    .fidelo_h2 {
        font-family: 'Lato', Helvetica, Arial, Lucida, sans-serif;
        font-weight: 700;
        font-size: 18px;
        color: #2B8180 !important;
        line-height: 1.3em;
    }

    .fidelo_background {
        background-color: #01c1b8 !important;
        padding: 20px;
        border-radius: 6px;
        margin: 10px;
    }

    form {
        /*box-sizing: border-box;
        padding: 2rem;
        border-radius: 1rem;
        background-color: hsl(0, 0%, 100%);
        border: 4px solid hsl(0, 0%, 90%);*/
    }

    .accordion {
        margin-bottom: 24px;
    }

    .accordion-container {
        width: 100%;
        margin: 0 auto;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        overflow: hidden;
        min-width: 600px;
    }


    .accordion-item {
        width: 100%;
    }

    .accordion-trigger {
        width: 100%;
        display: block;
        background-color: #01c1b8 !important;
        color: #ffffff;
        padding: 12px;
        /*font-size: 14px;
        font-weight: 500;
        font-family: 'Inter', sans-serif;*/
        text-align: left;
        border: none;
        display: flex;
        gap: 16px;
        justify-content: space-between;
        cursor: pointer;
    }

    .accordion-icon {
        transition: transform 0.5s;
    }

    .accordion-item[open] .accordion-icon {
        transform: rotate(45deg);
    }

    .accordion-item:not(:first-of-type) .accordion-trigger {
        border-top: 3px solid #eaeaea;
    }

    .accordion-content p {
        margin: 24px;
    }

    div.settings {
        display: grid;
        grid-template-columns: max-content max-content;
        grid-gap: 5px;
        padding: 25px;
    }

    div.settings label {
        text-align: right;
    }

    div.settings label:after {
        content: ":";
    }

    .fidelo_table_search {
        width: 100%;
        height: 24px;
        padding: 6px 12px;
    }
</style>
<script>
    function billingSearch(table_search, table_name) {
        const searchInput = document.getElementById(table_search);
        const billingTable = document.getElementById(table_name);
        const searchTerm = searchInput.value.toLowerCase();
        let rowNr = 0;
        for (let row of billingTable.rows) {
            rowNr++;
            if (rowNr === 1) {
                continue;
            }
            row.style.display = 'none';
            for (let cell of row.cells) {
                if (cell.innerText.toLowerCase().includes(searchTerm)) {
                    row.style.display = '';
                    /*if (row === active_row) {
                        highlightRow(null);
                    }*/
                }
            }
        }
    }

    function sortTable(table, col, reverse) {
        let tb = table.tBodies[0],
            tr = Array.prototype.slice.call(tb.rows, 0),
            i;
        reverse = -((+reverse) || -1);
        tr = tr.sort(function (a, b) {
            if (a.classList.contains('fidelo_sortlast')) {
                return true;
            }
            if (b.classList.contains('fidelo_sortlast')) {
                return false;
            }
            return reverse * (a.cells[col].textContent.trim().localeCompare(b.cells[col].textContent.trim()));
        });
        for (i = 0; i < tr.length; ++i) tb.appendChild(tr[i]);
    }

    function makeSortable(table_name) {
        let table = document.getElementById(table_name);
		if (!table) {
			return;
		}
        let th = table.tHead, i;
        if (th) {
            if (th.rows[0]) {
                th = th.rows[0];
                if (th.cells) {
                    th = th.cells;
                    if (th) i = th.length;
                    else return; // if no `<thead>` then do nothing
                    while (--i >= 0) (function (i) {
                        let dir = 1;
                        th[i].addEventListener('click', function () {
                            sortTable(table, i, (dir = 1 - dir))
                        });
                    }(i));
                }
            }
        }
    }
    let dateError = '{'Please enter a valid date'|L10N}';
    function validateAddCustomer() {
        if (!document.getElementById('customer_0_birthday').value) {
            return dateError;
        }
        if (document.getElementById('customer_0_birthday').value > '{\Carbon\Carbon::now()->format("Y-m-d")}') {
            return dateError;
        }
        if (document.getElementById('customer_0_birthday').value < '1900-01-01') {
            return dateError;
        }
        if (!document.getElementById('customer_0_firstname').value) {
            return '{'Please enter a first name'|L10N}';
        }
        if (!document.getElementById('customer_0_lastname').value) {
            return '{'Please enter a last name'|L10N}';
        }
        if (document.getElementById('customer_0_language').value == 0) {
            return '{'Please choose a language'|L10N}';
        }
        if (document.getElementById('customer_0_school_id').value == 0) {
            return '{'Please choose a school'|L10N}';
        }
        return true;
    }

    function sendAddCustomerForm() {
        let validate = validateAddCustomer();
        if (validate !== true) {
            document.getElementById('addCustomerError').innerHTML = validate;
            return;
        }
        document.addCustomerForm.submit();
    }

    /*let active_row = null;

    function highlightRow(tr_el) {
        let i;
        for (i = 0; active_row && i < active_row.children.length; i++) {
            active_row.children[i].style.removeProperty("background-color");
        }
        active_row = tr_el;
        for (i = 0; active_row && i < active_row.children.length; i++) {
            active_row.children[i].style.backgroundColor = '#ddd';
        }
    }*/
</script>
{assign var=newBookingCombination value='LC7GK25HXXKLGW29'}
<!-- Navigation -->
<div class="fidelo_main">
    {if $loggedIn == 1}
        <div class="" style="float:left;">
            <nav class="fidelo_menu">
                <ul class="nav">
                    <li><a class="{if $sTask=='showIndexData'}fidelo_menu_active{/if}" href="?task=showIndexData">{'Overview'|L10N}</a></li>
                    <li><a class="{if $sTask=='showSchoolData'}fidelo_menu_active{/if}" href="?task=showSchoolData">{'School'|L10N}</a></li>
                    <li><a class="{if $sTask=='showPersonalData'}fidelo_menu_active{/if}" href="?task=showPersonalData">{'Personal Data'|L10N}</a></li>
                    <li><a class="{if $sTask=='showBookingData'}fidelo_menu_active{/if}" href="?task=showBookingData">{'Bookings Data'|L10N}</a></li>
                    <li><a class="{if $sTask=='showDocuments'}fidelo_menu_active{/if}" href="?task=showDocuments">{'Downloads'|L10N}</a></li>
                    <li><a class="{if $sTask=='showBillingData'}fidelo_menu_active{/if}" href="?task=showBillingData">{'Payments'|L10N}</a></li>
                    <li><a class="" href="?task=logout&logout=ok">{'Logout'|L10N}</a></li>
                </ul>
            </nav>
        </div>
    {/if}
    <div class="fidelo_main_container" style="padding-left:40px;display: flex; flex-direction: column;">
        <div style="width:100%;float: none">
            {if $sTask == 'showPersonalData' || $sTask == 'addCustomer'}

                <!-- Show Personal Data -->
                <div>
                    <div class="accordion-container">
                        <form method="POST" name="addCustomerForm" action="?task=addCustomer">
                            <details class="accordion-item">
                                {assign var=valerrorKey value="customer_0"}
                                <summary class="accordion-trigger">
                                    <span class="accordion-title"
                                          style="{if $aValErrors[$valerrorKey] } color:red; {/if}">{'New Child'|L10N}</span>
                                    <span class="accordion-icon" aria-hidden="true">&plus;</span>
                                </summary>
                                <div class="accordion-content settings">
                                    {*<div style="max-width: fit-content;margin-left: auto;margin-right: auto;">
                                        <fidelo-widget></fidelo-widget>
                                    </div>
                                    <script src="/assets/tc-frontend/js/widget.js?c=6K2Z4GM25X2XAN3N"></script>*}
                                    <label>{'First name'|L10N}</label> <input id="customer_0_firstname" name="addCustomer[customer_0][firstname]"
                                                                              value=""/>
                                    <label>{'Last name'|L10N}</label> <input id="customer_0_lastname" name="addCustomer[customer_0][lastname]"
                                                                             value=""/>
                                    <label>{'Date of birth'|L10N}</label> <input type="date" id="customer_0_birthday"
                                                                                 name="addCustomer[customer_0][birthday]"
                                                                                 value="" min='1900-01-01' max='{\Carbon\Carbon::now()->format("Y-m-d")}'"/>
                                    <label>{'Gender'|L10N}</label>
                                    <select name="addCustomer[customer_0][gender]">
                                        {foreach Ext_TC_Util::getGenders(false) as $genderId => $genderName}
                                            <option value="{$genderId}">{$genderName|L10N}</option>
                                        {/foreach}
                                    </select>
                                    <label>{'Nationality'|L10N}</label>
                                    <select name="addCustomer[customer_0][nationality]">
                                        {foreach Ext_Thebing_Nationality::getNationalities(true, $language) as $nationCode => $nationName}
                                            <option value="{$nationCode}">{$nationName}</option>
                                        {/foreach}
                                    </select>
                                    <label>{'Language'|L10N}</label>
                                    <select id="customer_0_language" name="addCustomer[customer_0][language]">
                                        {foreach Ext_Thebing_Data::getLanguageSkills(true, $language) as $languageCode => $languageName}
                                            <option value="{$languageCode}">{$languageName}</option>
                                        {/foreach}
                                    </select>
                                    {if count($schools) > 1 || true}
                                    <label>{'School'|L10N}</label>
                                    <select id="customer_0_school_id" name="addCustomer[customer_0][school_id]">
                                        {foreach $schools as $schoolId => $school}
                                            <option value="{$schoolId}">{$school->getName()}</option>
                                        {/foreach}
                                    </select>
                                    {/if}
                                    {*<label  style="{if $aValErrors[$valerrorKey] && in_array('INVALID_MAIL', $aValErrors[$valerrorKey]) } color:red; {/if}">{'E-Mail'|L10N}</label> <input name="save[customer_0][E-Mail]" value=""/>
                                    <label>{'Phone'|L10N}</label> <input name="save[customer_0][phone_private]" value=""/>
                                    <label>{'Mobile'|L10N}</label> <input name="save[customer_0][phone_mobile]" value=""/>
                                    <label>{'Address'|L10N}</label> <input name="save[customer_0][address]" value=""/>
                                    <label>{'Zip code'|L10N}</label> <input name="save[customer_0][zip]" value=""/>
                                    <label>{'City'|L10N}</label> <input name="save[customer_0][city]" value=""/>
                                    <label>{'State'|L10N}</label> <input name="save[customer_0][state]" value=""/>
                                    <label>{'Country'|L10N}</label>
                                    <select name="save[customer_0][country]">
                                        {foreach $oLocaleService->getCountries($sLanguage) as $k => $c }
                                            <option value="{$k}" >{$c}</option>
                                        {/foreach}
                                    </select>*}
                                    <div style="text-align: center;">
                                        <br/>
                                        <button class="fidelo_button" type="button" onclick="sendAddCustomerForm()">{'Add'|L10N}</button>
                                        <br/>
                                        <div id="addCustomerError" style="color:red"></div>
                                        <br/>
                                    </div>
                                </div>
                            </details>
                            <br/>
                        </form>
                        <form method="POST" action="?task=showPersonalData">
                            {if $booker }
                                <details class="accordion-item">
                                    {assign var=valerrorKey value="booker"}
                                    <summary class="accordion-trigger">
                                        <span class="accordion-title"
                                              style="{if $valErrors[$valerrorKey] } color:red; {/if}">{'Family Data'|L10N}</span>
                                        <span class="accordion-icon" aria-hidden="true">&plus;</span>
                                    </summary>
                                    <div class="accordion-content settings">
                                        {assign var=addressBilling value=$booker->getAddress('billing') }
                                        <label>{'First name'|L10N}</label> <input name="save[booker][firstname]"
                                                                                  value="{$booker->firstname}"/>
                                        <label>{'Last name'|L10N}</label> <input name="save[booker][lastname]"
                                                                                 value="{$booker->lastname}"/>
                                        <label>{'Phone'|L10N}</label> <input name="save[booker][phone_private]"
                                                                             value="{$booker->getDetail('phone_private')}"/>
                                        <label style="{if $valErrors[$valerrorKey] && in_array('INVALID_MAIL', $valErrors[$valerrorKey]) } color:red; {/if}">{'E-Mail'|L10N}</label>
                                        <input name="save[booker][email]" value="{$booker->email}"/>
                                        <label>{'Address'|L10N}</label> <input name="save[booker][address]"
                                                                               value="{$addressBilling->address}"/>
                                        <label>{'Zip code'|L10N}</label> <input name="save[booker][zip]"
                                                                                value="{$addressBilling->zip}"/>
                                        <label>{'City'|L10N}</label> <input name="save[booker][city]"
                                                                            value="{$addressBilling->city}"/>
                                        <label>{'State'|L10N}</label> <input name="save[booker][state]"
                                                                             value="{$addressBilling->state}"/>
                                        <label>{'Country'|L10N}</label>
                                        <select name="save[booker][country]">
                                            {foreach $localeService->getCountries($language) as $countryCode => $countryName }
                                                {assign var=selected value=''}
                                                {if $countryCode == $addressBilling->country_iso}
                                                    {assign var=selected value='selected'}
                                                {/if}
                                                <option value="{$countryCode}" {$selected}>{$countryName}</option>
                                            {/foreach}
                                        </select>
                                    </div>
                                </details>
                            {/if}
                            <details class="accordion-item">
                                {assign var=valerrorKey value="emergency"}
                                <summary class="accordion-trigger">
                                    <span class="accordion-title"
                                          style="{if $valErrors[$valerrorKey] } color:red; {/if}">{'Emergency contact'|L10N}</span>
                                    <span class="accordion-icon" aria-hidden="true">&plus;</span>
                                </summary>
                                <div class="accordion-content settings">
                                    <label>{'First name'|L10N}</label> <input name="save[emergency][firstname]"
                                                                              value="{$emergencyContact->firstname}"/>
                                    <label>{'Last name'|L10N}</label> <input name="save[emergency][lastname]"
                                                                             value="{$emergencyContact->lastname}"/>
                                    <label>{'Phone'|L10N}</label> <input name="save[emergency][phone_private]"
                                                                         value="{$emergencyContact->getDetail('phone_private')}"/>
                                    <label style="{if $valErrors[$valerrorKey] && in_array('INVALID_MAIL', $valErrors[$valerrorKey]) } color:red; {/if}">{'E-Mail'|L10N}</label>
                                    <input name="save[emergency][email]" value="{$emergencyContact->email}"/>
                                </div>
                            </details>
                            {foreach $travellers as $customer}
                                <details class="accordion-item">
                                    {assign var=valerrorKey value="travellers[`$customer->id`]"}
                                    <summary class="accordion-trigger">
                                        <span class="accordion-title"
                                              style="{if $valErrors[$valerrorKey] } color:red; {/if}">{'Child'|L10N} {$customer->firstname} {$customer->lastname}</span>
                                        <span class="accordion-icon" aria-hidden="true">&plus;</span>
                                    </summary>
                                    <div class="accordion-content settings">
                                        <label>{'First name'|L10N}</label> <input
                                                name="save[customer_{$customer->id}][firstname]"
                                                value="{$customer->firstname}"/>
                                        <label>{'Last name'|L10N}</label> <input
                                                name="save[customer_{$customer->id}][lastname]"
                                                value="{$customer->lastname}"/>
                                        <label style="{if $valErrors[$valerrorKey] && in_array('INVALID_DATE_PAST', $valErrors[$valerrorKey]) } color:red; {/if}">{'Date of birth'|L10N}</label>
                                        <input type="date" id="customer_{$customer->id}_birthday"
                                               name="save[customer_{$customer->id}][birthday]"
                                               value="{$customer->birthday}"/>
                                        <label>{'Gender'|L10N}</label>
                                        <select name="save[customer_{$customer->id}][gender]">
                                            {foreach Ext_TC_Util::getGenders(false) as $genderId => $genderName}
                                                {assign var=selected value=''}
                                                {if $genderId == $customer->gender}
                                                    {assign var=selected value='selected'}
                                                {/if}
                                                <option value="{$genderId}" {$selected}>{$genderName|L10N}</option>
                                            {/foreach}
                                        </select>
                                        <label>{'Nationality'|L10N}</label>
                                        <select name="save[customer_{$customer->id}][nationality]">
                                            {foreach Ext_Thebing_Nationality::getNationalities(true, $language) as $nationCode => $nationName}
                                                {assign var=selected value=''}
                                                {if $nationCode == $customer->nationality}
                                                    {assign var=selected value='selected'}
                                                {/if}
                                                <option value="{$nationCode}" {$selected}>{$nationName}</option>
                                            {/foreach}
                                        </select>
                                        <label>{'Language'|L10N}</label>
                                        <select name="save[customer_{$customer->id}][language]">
                                            {foreach Ext_Thebing_Data::getLanguageSkills(true, $language) as $languageCode => $languageName}
                                                {assign var=selected value=''}
                                                {if $languageCode == $customer->language}
                                                    {assign var=selected value='selected'}
                                                {/if}
                                                <option value="{$languageCode}" {$selected}>{$languageName}</option>
                                            {/foreach}
                                        </select>
                                        {*<label  style="{if $aValErrors[$valerrorKey] && in_array('INVALID_MAIL', $aValErrors[$valerrorKey]) } color:red; {/if}">{'E-Mail'|L10N}</label> <input name="save[customer_{$oCustomer->id}][E-Mail]" value="{$oCustomer->E-Mail}"/>
                                        <label>{'Phone'|L10N}</label> <input name="save[customer_{$oCustomer->id}][phone_private]" value="{$oCustomer->getDetail('phone_private')}"/>
                                        <label>{'Mobile'|L10N}</label> <input name="save[customer_{$oCustomer->id}][phone_mobile]" value="{$oCustomer->getDetail('phone_mobile')}"/>
                                        {assign var=oAddressContact value=$oCustomer->getAddress('contact') }
                                        <label>{'Address'|L10N}</label> <input name="save[customer_{$oCustomer->id}][address]" value="{$oAddressContact->address}"/>
                                        <label>{'Zip code'|L10N}</label> <input name="save[customer_{$oCustomer->id}][zip]" value="{$oAddressContact->zip}"/>
                                        <label>{'City'|L10N}</label> <input name="save[customer_{$oCustomer->id}][city]" value="{$oAddressContact->city}"/>
                                        <label>{'State'|L10N}</label> <input name="save[customer_{$oCustomer->id}][state]" value="{$oAddressContact->state}"/>
                                        <label>{'Country'|L10N}</label>
                                        <select name="save[customer_{$oCustomer->id}][country]">
                                            {foreach $oLocaleService->getCountries($sLanguage) as $k => $c }
                                                {assign var=sSelected value=''}
                                                {if $k == $oAddressContact->country_iso}
                                                    {assign var=sSelected value='selected'}
                                                {/if}
                                                <option value="{$k}" {$sSelected}>{$c}</option>
                                            {/foreach}
                                        </select>*}
                                    </div>
                                </details>
                            {/foreach}
                            <div style="text-align: center;">
                                <br/>
                                <button class="fidelo_button" type="submit">{'Save'|L10N}</button>
                                <br/><br/>
                            </div>
                        </form>
                    </div>
                </div>
            {elseif $sTask == 'newBooking'}
				<div>
					<noscript>Please enable JavaScript to continue with this form.</noscript><fidelo-widget></fidelo-widget>
					<script src="/assets/tc-frontend/js/widget.js?c={$newBookingCombination}&booking={$process->key}"></script>
				</div>
            {elseif $sTask == 'showBookingData'}
                <script>
                    window.onload = (event) => {
                        makeSortable('courses_table');
                        makeSortable('transfers_table');
                        makeSortable('accommodations_table');
                        makeSortable('insurances_table');
                        makeSortable('activities_table');
                    }
                </script>
				<div class="accordion-container">
					<details class="accordion-item">
						<summary class="accordion-trigger">
							<span class="accordion-title">{'Book a Class'|L10N}</span>
							<span class="accordion-icon" aria-hidden="true">&plus;</span>
						</summary>
						<form class="" action="?" method="get">
							<div class="accordion-content settings">
								<input type="hidden" name="combination" value="{$newBookingCombination}">
								<input type="hidden" name="task" value="newBooking">
								<label>{'School'|L10N}</label>
								<select name="schoolId">
									{foreach $schools as $schoolId => $schoolName}
									<option value="{$schoolId}">{$schoolName}</option>
									{/foreach}
								</select>
								<label>{'Child'|L10N}</label>
								<select name="customerId">
									{foreach $travellers as $customer}
									<option value="{$customer->id}">{$customer->getName()}</option>
									{/foreach}
								</select>
								<div style="text-align:center; padding:20px">
									<button class="fidelo_button" type="submit">{'Choose Class'|L10N}</button>
								</div>
							</div>
						</form>
					</details>
				</div>
                <div style="padding-top: 30px;">
                    <form class="" action="?task=showBookingData" method="post">
                        <select class="" name="student_booking" onChange="this.form.submit();">
                            {foreach $bookings as $booking}
                                {assign var=selected value=''}
                                {assign var=customer value=$booking->getCustomer()}
                                {if $currentInquiryId == $booking->id}
                                    {assign var=selected value='selected'}
                                {/if}
                                <option value="{$booking->id}" {$selected} >{'Booking'|L10N} {$booking->getFirstCourseStart(true)|date_format:'%d.%m.%Y'} {$customer->firstname} {$customer->lastname}</option>
                            {/foreach}
                        </select>
                    </form>
                    <br/>
                </div>
                <!-- Show Booking Data -->
                <div class="accordion-container">
                    <!-- Courses -->
                    {if $coursesData}
                    <details class="accordion-item">
                        <summary class="accordion-trigger">
                            <span class="accordion-title">{'Courses'|L10N}</span>
                            <span class="accordion-icon" aria-hidden="true">&plus;</span>
                        </summary>
                        <div class="accordion-content">
                            <div>
                                <label>{'Search'|L10N}:</label><input id="courses_search" class="fidelo_table_search"
                                                                     onkeyup="billingSearch('courses_search', 'courses_table')">
                            </div>
                            <table class="table" id="courses_table">
                                <thead>
                                <tr>
                                    <th style="cursor: pointer">{'Course'|L10N}</th>
                                    <th style="cursor: pointer">{'Level'|L10N}</th>
                                    <th style="cursor: pointer">{'Weeks'|L10N}</th>
                                    <th style="cursor: pointer">{'From'|L10N}</th>
                                    <th style="cursor: pointer">{'Until'|L10N}</th>
                                </tr>
                                </thead>
                                {foreach $coursesData as $id => $courseData}
                                    <tr>
                                        <td>{$courseData['name']}</td>
                                        <td>{$courseData['level']}</td>
                                        <td>{$courseData['weeks']}</td>
                                        <td>{$courseData['from']}</td>
                                        <td>{$courseData['until']}</td>
                                    </tr>
                                {/foreach}
                            </table>
                        </div>
                    </details>
                    {/if}
                    <!-- Accommodations -->
                    {if $accommodationsData}
                        <details class="accordion-item">
                            <summary class="accordion-trigger">
                                <span class="accordion-title">{'Accommodation'|L10N}</span>
                                <span class="accordion-icon" aria-hidden="true">&plus;</span>
                            </summary>
                            <div class="accordion-content">
                                <div>
                                    <label>{'Search'|L10N}:</label><input id="accommodations_search"
                                                                         class="fidelo_table_search"
                                                                         onkeyup="billingSearch('accommodations_search', 'accommodations_table')">
                                </div>
                                <table class="table" id="accommodations_table">
                                    <thead>
                                    <tr>
                                        <th style="cursor: pointer">{'Type'|L10N}</th>
                                        <th style="cursor: pointer">{'Room'|L10N}</th>
                                        <th style="cursor: pointer">{'Meal'|L10N}</th>
                                        <th style="cursor: pointer">{'Weeks'|L10N}</th>
                                        <th style="cursor: pointer">{'From'|L10N}</th>
                                        <th style="cursor: pointer">{'Until'|L10N}</th>
                                    </tr>
                                    </thead>
                                    {foreach $accommodationsData as $id => $accommodation}
                                        <tr>
                                            <td>{$accommodation['name']}</td>
                                            <td>{$accommodation['room']}</td>
                                            <td>{$accommodation['meal']}</td>
                                            <td>{$accommodation['weeks']}</td>
                                            <td>{$accommodation['from']|date_format:'%d.%m.%Y'}</td>
                                            <td>{$accommodation['until']|date_format:'%d.%m.%Y'}</td>
                                        </tr>
                                    {/foreach}
                                </table>
                            </div>
                        </details>
                    {/if}
                    <!-- Transfer-->
                    {if $transferData['arrival'] || $transferData['departure'] || $transferData['additional']}
                    <details class="accordion-item">
                        <summary class="accordion-trigger">
                            <span class="accordion-title">{'Transfer'|L10N}</span>
                            <span class="accordion-icon" aria-hidden="true">&plus;</span>
                        </summary>
                        <div class="accordion-content">
                            <div>
                                <label>{'Search'|L10N}:</label><input id="transfers_search" class="fidelo_table_search"
                                                                     onkeyup="billingSearch('transfers_search', 'transfers_table')">
                            </div>
                            <table class="table" id="transfers_table">
                                <thead>
                                <tr>
                                    <th style="cursor: pointer">{'Pick Up'|L10N}</th>
                                    <th style="cursor: pointer">{'Drop off'|L10N}</th>
                                    <th style="cursor: pointer">{'Airline'|L10N}</th>
                                    <th style="cursor: pointer">{'Flight Number'|L10N}</th>
                                    <th style="cursor: pointer">{'Date'|L10N}</th>
                                    <th style="cursor: pointer">{'Arrival time'|L10N}</th>
                                    <th style="cursor: pointer">{'Pick up time'|L10N}</th>
                                </tr>
                                </thead>
                                {if $transferData['arrival']}
                                    <tr>
                                        <td>{$transferData['arrival']['pickup']}</td>
                                        <td>{$transferData['arrival']['drop_off']}</td>
                                        <td>{$transferData['arrival']['airline']}</td>
                                        <td>{$transferData['arrival']['flight_number']}</td>
                                        <td>{$transferData['arrival']['date']|date_format:'%d.%m.%Y'}</td>
                                        <td>{$transferData['arrival']['arrival_time']}</td>
                                        <td>{$transferData['arrival']['pickup_time']}</td>
                                    </tr>
                                {/if}
                                {if $transferData['departure']}
                                    <tr>
                                        <td>{$transferData['departure']['pickup']}</td>
                                        <td>{$transferData['departure']['drop_off']}</td>
                                        <td>{$transferData['departure']['airline']}</td>
                                        <td>{$transferData['departure']['flight_number']}</td>
                                        <td>{$transferData['departure']['date']|date_format:'%d.%m.%Y'}</td>
                                        <td>{$transferData['departure']['arrival_time']}</td>
                                        <td>{$transferData['departure']['pickup_time']}</td>
                                    </tr>
                                {/if}
                                {foreach $transferData['additional'] as $transfer}
                                    <tr>
                                        <td>{$transfer['pickup']}</td>
                                        <td>{$transfer['drop_off']}</td>
                                        <td>{$transfer['airline']}</td>
                                        <td>{$transfer['flight_number']}</td>
                                        <td>{$transfer['date']|date_format:'%d.%m.%Y'}</td>
                                        <td>{$transfer['arrival_time']}</td>
                                        <td>{$transfer['pickup_time']}</td>
                                    </tr>
                                {/foreach}
                            </table>
                        </div>
                    </details>
                    {/if}
                    <!-- Insurances -->
                    {if $insuranceDetails}
                    <details class="accordion-item">
                        <summary class="accordion-trigger">
                            <span class="accordion-title">{'Insurances'|L10N}</span>
                            <span class="accordion-icon" aria-hidden="true">&plus;</span>
                        </summary>
                        <div class="accordion-content">
                            <div>
                                <label>{'Search'|L10N}:</label><input id="insurances_search" class="fidelo_table_search"
                                                                     onkeyup="billingSearch('insurances_search', 'insurances_table')">
                            </div>
                            <table class="table" id="insurances_table">
                                <thead>
                                <tr>
                                    <th style="cursor: pointer">{'Insurance'|L10N}</th>
                                    <th style="cursor: pointer">{'From'|L10N}</th>
                                    <th style="cursor: pointer">{'Until'|L10N}</th>
                                </tr>
                                </thead>
                                {foreach $insuranceDetails as $id => $insurance}
                                    <tr>
                                        <td>{$insurance['insurance']}</td>
                                        <td>{$insurance['from']|date_format:'%d.%m.%Y'}</td>
                                        <td>{$insurance['until']|date_format:'%d.%m.%Y'}</td>
                                    </tr>
                                {/foreach}
                            </table>
                        </div>
                    </details>
                    {/if}
                    <!-- Activities -->
                    {if $activityDetails}
                    <details class="accordion-item">
                        <summary class="accordion-trigger">
                            <span class="accordion-title">{'Activities'|L10N}</span>
                            <span class="accordion-icon" aria-hidden="true">&plus;</span>
                        </summary>
                        <div class="accordion-content">
                            <div>
                                <label>{'Search'|L10N}:</label><input id="activities_search" class="fidelo_table_search"
                                                                     onkeyup="billingSearch('activities_search', 'activities_table')">
                            </div>
                            <table class="table" id="activities_table">
                                <tr>
                                    <th style="cursor: pointer">{'Activity'|L10N}</th>
                                </tr>
                                {foreach $activityDetails as $id => $activity}
                                    <tr>
                                        <td>{$activity['info']}</td>
                                    </tr>
                                {/foreach}
                            </table>
                        </div>
                    </details>
                    {/if}
                </div>
            {elseif $sTask == 'showDocuments'}
                <script>
                    window.onload = (event) => {
                        makeSortable('invoices_table');
                        makeSortable('payments_table');
                        makeSortable('additions_table');
                    }
                </script>
                <div>
                    <form class="" action="?task=showDocuments" method="post">
                        <select class="" name="student_booking" onChange="this.form.submit();">
                            {foreach $bookings as $booking}
                                {assign var=selected value=''}
                                {assign var=customer value=$booking->getCustomer()}
                                {if $currentInquiryId == $booking->id}
                                    {assign var=selected value='selected'}
                                {/if}
                                <option value="{$booking->id}" {$selected} >{'Booking'|L10N} {$booking->getFirstCourseStart(true)|date_format:'%d.%m.%Y'} {$customer->firstname} {$customer->lastname}</option>
                            {/foreach}
                        </select>
                    </form>
                    <br/>
                </div>
                <!-- Show Documents -->
                <div class="accordion-container">
                    <details class="accordion-item">
                        <summary class="accordion-trigger">
                            <span class="accordion-title">{'Invoice Documents'|L10N}</span>
                            <span class="accordion-icon" aria-hidden="true">&plus;</span>
                        </summary>
                        <div class="accordion-content">
                            <div>
                                <label>{'Search'|L10N}:</label><input id="invoices_search" class="fidelo_table_search"
                                                                     onkeyup="billingSearch('invoices_search', 'invoices_table')">
                            </div>
                            <table class="table" id="invoices_table">
                                <thead>
                                <tr>
                                    <th style="cursor: pointer">{'Inv No'|L10N}</th>
                                    <th style="cursor: pointer">{'Due Date'|L10N}</th>
                                    <th style="cursor: pointer">{'Amount'|L10N}</th>
                                    <th style="cursor: pointer">{'PDF'|L10N}</th>
                                </tr>
                                </thead>
                                {foreach $invoices as $invoice}
                                    <tr>
                                        <td>{$invoice['number']}</td>
                                        <td>{$invoice['date']}</td>
                                        <td>{$invoice['amount']}</td>
                                        <td>
                                            {if $invoice['url'] !=='' }
                                                <a href="{$invoice['url']}" target="_blank"><img
                                                            src="../icef_login/page_white_acrobat.png"
                                                            style="margin-top: 2px;" alt="PDF"/></a>
                                            {/if}
                                        </td>
                                    </tr>
                                {/foreach}
                            </table>
                        </div>
                    </details>
                    <details class="accordion-item">
                        <summary class="accordion-trigger">
                            <span class="accordion-title">{'Payment Receipts'|L10N}</span>
                            <span class="accordion-icon" aria-hidden="true">&plus;</span>
                        </summary>
                        <div class="accordion-content">
                            <div>
                                <label>{'Search'|L10N}:</label><input id="payments_search" class="fidelo_table_search"
                                                                     onkeyup="billingSearch('payments_search', 'payments_table')">
                            </div>
                            <table class="table" id="payments_table">
                                <thead>
                                <tr>
                                    <th style="cursor: pointer">{'Inv No'|L10N}</th>
                                    <th style="cursor: pointer">{'Date'|L10N}</th>
                                    <th style="cursor: pointer">{'Amount'|L10N}</th>
                                    <th style="cursor: pointer">{'PDF'|L10N}</th>
                                </tr>
                                </thead>
                                {foreach $payments as $payment}
                                    <tr>
                                        <td>{$payment['number']}</td>
                                        <td>{$payment['date']}</td>
                                        <td>{$payment['amount']}</td>
                                        <td>
                                            {if $payment['url'] !=='' }
                                                <a href="{$payment['url']}" target="_blank"><img
                                                            src="../icef_login/page_white_acrobat.png"
                                                            style="margin-top: 2px;" alt="PDF"/></a>
                                            {/if}
                                        </td>
                                    </tr>
                                {/foreach}
                            </table>
                        </div>
                    </details>
                    <details class="accordion-item">
                        <summary class="accordion-trigger">
                            <span class="accordion-title">{'General Documents'|L10N}</span>
                            <span class="accordion-icon" aria-hidden="true">&plus;</span>
                        </summary>
                        <div class="accordion-content">
                            <div>
                                <label>{'Search'|L10N}:</label><input id="additions_search" class="fidelo_table_search"
                                                                     onkeyup="billingSearch('additions_search', 'additions_table')">
                            </div>
                            <table class="table" id="additions_table">
                                <thead>
                                <tr>
                                    <th style="cursor: pointer">{'Type'|L10N}</th>
                                    <th style="cursor: pointer">{'Date'|L10N}</th>
                                    <th style="cursor: pointer">{'PDF'|L10N}</th>
                                </tr>
                                </thead>
                                {foreach $additions as $addition}
                                    <tr>
                                        <td>{$addition['document']}</td>
                                        <td>{$addition['date']}</td>
                                        <td>
                                            {if $addition['url'] !=='' }
                                                <a href="{$addition['url']}" target="_blank"><img
                                                            src="../icef_login/page_white_acrobat.png"
                                                            style="margin-top: 2px;" alt="PDF"/></a>
                                            {/if}
                                        </td>
                                    </tr>
                                {/foreach}
                            </table>
                        </div>
                    </details>
                </div>
            {elseif $sTask == 'login'}
                <!-- --------------------------------------------------------------
             *	Login Form
             ------------------------------------------------------------------ -->
                <div style="padding:20px;display:flex;flex-direction:row;justify-content: center;">
                    <div class="" style="background-color: #ffffff">
                        <form action="?" method="post" style="margin: 0px;">
                            <input type="hidden" value="1" name="loginmodul">
                            <input type="hidden" value="{$iTableId}" name="table_number">
                            <fieldset>
                                <div class="form-row">
                                    <!-- Username -->
                                    <label for="input_username">{'Username'|L10N}</label>
                                    <input type="text" class="form-control" id="input_username"
                                           name="customer_login_1"/>
                                </div>
                                <div class="form-row">
                                    <!-- Password -->
                                    <label for="input_password">{'Password'|L10N}</label>
                                    <input type="password" class="form-control" id="input_password"
                                           name="customer_login_3"/>
                                </div>
                                <span class="row">
							<a class="" style="text-align: center"
                               href="?task=requestPassword">{'Forgotten password'|L10N}</a>
						</span>
                                <br/>
                                <!-- submit -->
                                <button type="submit" class="fidelo_button">{'Login'|L10N}</button>
                            </fieldset>
                        </form>
                    </div>
                    <div style="width:100px"></div>
                    <div class="" style="background-color: #ffffff;text-align: center"><br/><br/>
                        <div class="fidelo_h2">{'Not a user yet. Create an account now!'|L10N}</div>
                        <br/><br/>
                        <button class="fidelo_button"
                                onclick="document.location.href='/signup'">{'Register'|L10N}</button>
                    </div>
                </div>
            {elseif $sTask == 'requestPassword'}
                <!-- --------------------------------------------------------------
            *	Resend password
            ------------------------------------------------------------------ -->
                <div class="content">
                    <div class="box grid3" style="width: 290px">
                        <div class="box-content">
                            <form action="?task=sendPassword" method="post" style="margin: 0px;">
                                <fieldset>
                                    <div class="student_fields student_form">
                                        <!-- E-Mail -->
                                        <div class="form-row">
                                            <!-- Username -->
                                            <label for="input_username">{'Username'|L10N}</label>
                                            <input type="text" class="form-control" id="input_username" name="user"/>
                                        </div>
                                    </div>
                                    <!-- submit -->
                                    <span class="row">
                                        <a class="btn-block btn-default form-control" style="text-align: center"
                                           href="?">{'Back'|L10N}</a>
                                        <br/>
                                        <button type="submit"
                                                class="btn-block btn-primary form-control">{'Request password'|L10N}</button>
                                    </span>
                                </fieldset>
                            </form>
                        </div>
                    </div>
                </div>
            {elseif $sTask == 'sendPassword'}
                <!-- --------------------------------------------------------------
            *	Password successfully
            ------------------------------------------------------------------ -->
            {elseif $sTask == 'showIndexData'}
                <!-- Show Index Data -->
                <div class="fidelo_background">
                    <div class="fidelo_h2">{'Wellcome'|L10N} {$name},</div>
                    <div class="fidelo_text" style="color:#ffffff">{'Manage your kids enrolment and stay on top of your pending payments.'|L10N}
                    </div>
                </div>
                <div style="padding-top:20px;{*display:flex;flex-direction:row;justify-content: space-around;*}">
                    {foreach $dashInfos as $info}
                        <div class="fidelo_background" style="margin">
                            <div class="fidelo_h2">{'Student'|L10N} {$info['name']} ({$info['customerObject']->getCustomerNumber()})</div>
                            <div class="fidelo_text" style="color:#ffffff;padding-top:10px">
                                {assign var=rowCounter value=0}
                                {foreach $info['inquiries'] as $inquiry}
                                    {if $inquiry && $inquiry->getLatestCourseEnd() > $now}
                                        {assign var=rowCounter value=$rowCounter+1}
                                        {if $rowCounter > 1}
                                            <hr class="solid">
                                        {/if}
                                        <div class="panel-body">
                                            {if $inquiry->number}
                                            <p>{$inquiry->number} </p>
                                            {/if}
                                            {assign var=courses value=$inquiry->getCourses(true)}
                                            {if $courses}
                                                <p>
                                                    {'Courses'|L10N}<br/>
                                                    {foreach $courses as $course}
                                                        {$course->getInfo($inquiry->getSchool()->id, $language)}
                                                        <br/>
                                                    {/foreach}
                                                </p>
                                            {/if}
                                            {assign var=accomodations value=$inquiry->getAccommodations(true)}
                                            {if $accomodations}
                                                <p>
                                                    {'Accommodations'|L10N}<br/>
                                                    {foreach $accomodations as $accommodation}
                                                        <!--<img src="../icef_login/32_ac_accommodation.png"/>-->
                                                        {$accommodation->getInfo($inquiry->getSchool()->id, $language)}
                                                    {/foreach}
                                                </p>
                                            {/if}
                                            {assign var=transfers value=$inquiry->getTransfers()}
                                            {if $transfers}
                                                <p>
                                                    {'Transfers'|L10N}<br/>
                                                    {foreach $transfers as $transfer}
                                                        <!--<img src="../icef_login/pickup.png"/>-->
                                                        {$transfer->getName(null, 1, $language)}
                                                    {/foreach}
                                                </p>
                                            {/if}
                                            {assign var=insurances value=$inquiry->getInsurances()}
                                            {if $insurances}
                                                <p>
                                                    {'Insurances'|L10N}<br/>
                                                    {foreach $insurances as $insurance}
                                                        <!--<img src="../icef_login/pickup.png"/>-->
                                                        {$insurance->getInsuranceName($language)}
                                                    {/foreach}
                                                </p>
                                            {/if}
                                            {assign var=activities value=$inquiry->getActivities(true)}
                                            {if $activities}
                                                <p>
                                                    {'Activities'|L10N}<br/>
                                                    {foreach $activities as $activity}
                                                        <!--<img src="../icef_login/pickup.png"/>-->
                                                        {$activity->getInfo($language)}
                                                    {/foreach}
                                                </p>
                                            {/if}
                                        </div>
                                    {/if}
                                {/foreach}
                            </div>
                        </div>
                    {/foreach}
                </div>
            {elseif $sTask == 'showSchoolData'}
                <!-- Show School Data -->
                <div class="fidelo_background" style="">
                    <div class="fidelo_h2">{'General Information'|L10N}</div>
                    <br/>
                    <div class="fidelo_text" style="color:#ffffff">
                        {assign var=rowCounter value=0}
                        {foreach $schools as $school}
                            {if $rowCounter > 1}
                                <hr class="solid">
                            {/if}
                        <p>
                            <h1 class="fidelo_h2">{$school['name']}</h1>
                            <br/>
                            {$school['address']} {$school['addressAdditional']}<br/>
                            {$school['zip']} {$school['city']} {$school['country']}<br/>
                            <br/>
                            {if $school['phone1']}
                            {'Telefon1'|L10N}: {$school['phone1']}<br/>
                            {/if}
                            {if $school['phone2']}
                            {'Telefon2'|L10N}: {$school['phone2']}<br/>
                            {/if}
                            {if $school['fax']}
                            {'Fax'|L10N}: {$school['fax']}<br/>
                            {/if}
                            {if $school['mail']}
                            {'E-Mail'|L10N}: {$school['mail']}<br/>
                            {/if}
                            <br/>
                            {if $school['url']}
                            <a href="{$school['url']}">{$school['url']}</a>
                            {/if}
                            <br/>
                        </p>
                        {/foreach}
                    </div>
                </div>
            {elseif $sTask == 'resetPassword'}
                <!-- Password Reset -->
                <div class="content">
                    <div class="box grid3" style="width: 290px">
                        <h3 class="box-head">{'Please enter new password'|L10N}</h3>
                        <form style="margin: 0px;" method="post" action="?task=executeChangePassword">
                            <fieldset>
                                <div class="student_fields student_form">
                                    <!-- E-Mail -->
                                    <div class="divFormElement">
                                        <label for="new_password">{'Password'|L10N}</label>
                                        <input type="password" name="new_password" id="new_password"
                                               class="form-control"/>
                                    </div>
                                    <div class="divFormElement">
                                        <label for="new_password_repeat">{'Repeat password'|L10N}</label>
                                        <input type="password" name="new_password_repeat" id="new_password_repeat"
                                               class="form-control"/>
                                    </div>
                                </div>
                                <input type="hidden" name="hash" value="{$hash}"/>
                                <!-- submit -->
                                <span class="row">
                                    <button type="submit" class="btn btn-primary">{'Change password'|L10N}</button>
                                </span>
                            </fieldset>
                        </form>
                    </div>
                </div>
            {elseif $sTask == 'showMails'}
                <!-- Communication -->
                <div class="content">
                    <div class="box box-table">
                        <div class="search">
                            <form action="{$sBaseUrl}task=showMails" method="post">
                                <label>Search</label>
                                <input type="text" name="search" value="{$sSearch}"/>
                                <div class="clearer"/>
                            </form>
                        </div>
                        {$sMailsData}
                    </div>
                </div>
            {elseif $sTask == 'showBillingData'}
                <script>
                    window.onload = (event) => {
                        makeSortable('billing_table');
                    }
                </script>
                <div class="row" style="min-width: 800px">
                    <div class="col-lg-12 col-md-12 col-xs-12 col-sm-12">
                        <div class="panel panel-default">
                            <div class="panel-heading" style="background-color: #01c1b8; color: white">
                                {'Payments'|L10N}
                            </div>
                            <div class="panel-body">
                                <div>
                                    <label>{'Search'|L10N}:</label><input id="billing_search" class="fidelo_table_search"
                                                                         onkeyup="billingSearch('billing_search', 'billing_table')">
                                </div>
                                <table class="table" id="billing_table">
                                    <thead>
                                    <tr>
                                        <th style="cursor: pointer">{'Booking'|L10N}</th>
                                        <th style="cursor: pointer">{'Item'|L10N}</th>
                                        <th style="cursor: pointer">{'Name'|L10N}</th>
                                        <th style="cursor: pointer">{'Amount'|L10N}</th>
                                        <th style="cursor: pointer">{'Payed'|L10N}</th>
                                        <th style="cursor: pointer">{'Balance'|L10N}</th>
                                    </tr>
                                    </thead>
                                    {foreach $data['invoices'] as $item}
                                        <tr>
                                            <td>{$item['booking_created']|date_format:'%d.%m.%Y'}
                                                - {$item['customer']}</td>
                                            <td>{$item['number']}</td>
                                            <td>{$item['label']}</td>
                                            <td>{$item['amount']}</td>
                                            <td>{$item['payed']}</td>
                                            <td>{$item['balance']}</td>
                                        </tr>
                                    {/foreach}
                                    {*{if $data['overpayment']}
                                        <tr class="fidelo_sortlast">
                                            <td>{$data['overpayment']['number']}</td>
                                            <td></td>
                                            <td></td>
                                            <td>{$data['overpayment']['amount']}</td>
                                            <td>{$data['overpayment']['payed']}</td>
                                            <td>{$data['overpayment']['balance']}</td>
                                        </tr>
                                    {/if}*}
                                    <tr class="fidelo_sortlast">
                                        <td>{$data['total']['number']}</td>
                                        <td></td>
                                        <td></td>
                                        <td>{$data['total']['amount']}</td>
                                        <td>{$data['total']['payed']}</td>
                                        <td>{$data['total']['balance']}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            {/if}
            <!-- Reports -->
            <div class="student_errors">
                <ul>
                    {foreach $oMessage->getMessages('error') as $error}
                        <li>{'Error'|L10N}: {$error}</li>
                    {/foreach}
                    {foreach $oMessage->getMessages('hint') as $hint}
                        <li>{'Hint'|L10N}: {$hint}</li>
                    {/foreach}
                    {foreach $oMessage->getMessages('info') as $info}
                        <li>{'Info'|L10N}: {$info}</li>
                    {/foreach}
                </ul>
            </div>
        </div>
    </div>
</div>