<#form#>
	<section id="contact" class="tp--section tp--contact tp--padding-lg">

      <div class="container">

        <div class="row">

          <div class="col-sm-6 col-sm-offset-3">
            
			<form id="contact-form" method="post" action="<#PHP_SELF#>" role="form">
				<input type="hidden" name="fo_action" value="send">
				
				<div class="messages">
					<#message#>
				</div>

				<div class="controls">

					<#elements#>

					<div class="row">
						<div class="col-md-12 text-center">
							<input type="submit" class="btn btn-primary btn-lg btn-send" value="#page:_LANG['Absenden']#">
						</div>
					</div>

				</div>

            </form>

          </div>

        </div><!-- /.row -->
      </div>

	</section>
<#/form#>

<#confirm#>

	<section id="contact" class="tp--section tp--contact tp--padding-lg">

		<div class="container">

			<div class="row">

				<div class="col-sm-6 col-sm-offset-3">

					<div class="alert alert-success alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button><#message#></div>

					<dl class="dl-horizontal">
						<#fields#>
						<dt><#field_name#></dt>
						<dd><#field_data#></dd>
						<#/fields#>
					</dl>

				</div>

			</div><!-- /.row -->
		</div>

	</section>
<#/confirm#>

<#onlytext#>
<tr <#display_condition_check#>>
	<td class="formItemBorder">&nbsp;</td>
	<td class="formItem" colspan="2"><#title#><#info#></td>
</tr> 
<#/onlytext#> 
<#onlytitle#>
<tr <#display_condition_check#>>
	<td class="formTitleBorder">&nbsp;</td>
	<td class="formTitle" colspan="2"><#title#><#info#></td>
</tr> 
<#/onlytitle#> 

<#text#>

				<div class="row" <#display_condition_check#>>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="form_field_<#id#>"><#title#><#info#></label>
							<input type="text" id="form_field_<#id#>" class="form-control" name="<#name#>" value="<#value#>" <#option#> <#if::check::start#>required<#if::check::end#>>
                            <div class="help-block with-errors"></div>
                        </div>
                    </div>
                </div>

<#/text#> 

<#hidden#> 
<tr <#display_condition_check#>>
	<td class="formItemBorder">&nbsp;</td>
	<td class="formItem" colspan="2"><#title#><#info#><input type="hidden" class="txt" name="<#name#>" value="<#value#>" <#option#>></td>
</tr> 
<#/hidden#> 
<#smalltext#> 
<tr <#display_condition_check#>>
	<td class="formItemBorder">&nbsp;</td>
	<td class="formItem" width="180"><#title#><#info#></td>
	<td class="formItem"><input type="text" style="width:300px;" class="txt" name="<#name#>" value="<#value#>" <#option#>></td>
</tr>
<#/smalltext#> 
<#infotext#> 
&nbsp;<img src="/admin/media/icon_help.gif" title="<#text#>" />
<#/infotext#> 

<#select#> 

				<div class="row" <#display_condition_check#>>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="form_field_<#id#>"><#title#><#info#></label>
							<textarea id="form_field_<#id#>" class="form-control" name="<#name#>" <#option#>><#value#></textarea>
							<select class="form-control" name="<#name#>" <#option#> <#display_condition_action#> <#if::check::start#>required<#if::check::end#>>
								<#optionlist#><option value="<#option_value#>" <#select#>><#option_value#></option><#/optionlist#>
							</select>
                            <div class="help-block with-errors"></div>
                        </div>
                    </div>
                </div>

<#/select#> 

<#textarea#> 

				<div class="row" <#display_condition_check#>>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="form_field_<#id#>"><#title#><#info#></label>
							<textarea id="form_field_<#id#>" class="form-control" name="<#name#>" <#option#> <#if::check::start#>required<#if::check::end#>><#value#></textarea>
                            <div class="help-block with-errors"></div>
                        </div>
                    </div>
                </div>

<#/textarea#>

<#checkbox#> 
<tr <#display_condition_check#>>
	<td class="formItemBorder">&nbsp;</td>
	<td class="formItem" width="180"><#title#><#info#></td>
	<td class="formItem"><input type="checkbox" name="<#name#>" value="<#value#>" <#checked#> <#display_condition_action#>></td>
</tr>
<#/checkbox#>
<#radio#> 
<tr <#display_condition_check#>>
	<td class="formItemBorder">&nbsp;</td>
	<td class="formItem" colspan="2"><#title#><#info#></td>
</tr> 
	<#optionlist#>
	<tr>
		<td class="formItemBorder">&nbsp;</td>
		<td class="formItem" width="180"><#option_value#></td>
		<td class="formItem"><input type="radio" name="<#name#>" <#option#> value="<#option_value#>" <#option_checked#> <#display_condition_action#>></td>
	</tr>
	<#/optionlist#>
<#/radio#>

<#error#>
<div class="alert danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>{'Bitte füllen Sie das folgende Feld aus!'|L10N}</div>
<#/error#>

<#error_numbers#> 
<tr>
	<td class="formErrorBorder">&nbsp;</td>
	<td colspan="2" class="formError">Bitte füllen Sie das folgende Feld nur mit Zahlen!</td>
</tr>
<#/error_numbers#>

<#error_email#> 
<tr>
	<td class="formErrorBorder">&nbsp;</td>
	<td colspan="2" class="formError">Dies ist keine gültige E-Mail Adresse!</td>
</tr>
<#/error_email#>

<#error_plz#> 
<tr>
	<td class="formErrorBorder">&nbsp;</td>
	<td colspan="2" class="formError">Dies ist keine gültige Postleitzahl!</td>
</tr>
<#/error_plz#>

<#error_date#> 
<tr>
	<td class="formErrorBorder">&nbsp;</td>
	<td colspan="2" class="formError">Bitte geben Sie ein Datum im Format DD.MM.YYYY ein!</td>
</tr>
<#/error_date#>

<#error_currency#> 
<tr>
	<td class="formErrorBorder">&nbsp;</td>
	<td colspan="2" class="formError">Bitte geben Sie einen Währungsbetrag an!</td>
</tr>
<#/error_currency#>
