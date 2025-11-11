<?
class Ext_Thebing_Accommodation_Html {
	
	
	public static function printPreparePdfDialog(){
		
		$iInqAccId = $_VRAS['row_id'];
		$oInquiryAcc = new Ext_TS_Inquiry_Journey_Accommodation($iInqAccId);
		$oInquiry = new Ext_TS_Inquiry($oInquiryAcc->inquiry_id);
		$oSchool = $oInquiry->getSchool();
		$oCustomer = $oInquiry->getCustomer();
		$oInbox = $oInquiry->getInbox();
		
		
		$aTemplates = Ext_Thebing_Pdf_Template_Search::s('document_accommodation_communication', $oCustomer->getLanguage(), $oSchool->id, $oInbox->id);
		
?>

		<div style="padding:10px;">
		
			<form action="" method="post">
				<select name="template" id="template">
				<?
					foreach($aTemplates as $oTemplate){
						?>
						
						<option value="<?=$oTemplate->id?>"><?=$oTemplate->name?></option>
						
						<?
					}
				?>	
				</select>	
				<input class="btn" type="submit" onclick="createPDF();return false;" value="<?=L10N::t('PDF generieren')?>"/>
			</form>
		
		</div>

<?
		
		
	}
	
	
}
