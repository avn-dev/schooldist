
var NewsletterGui = Class.create(ATG2,
{
	prepareAction : function($super, aData)
	{
		if(aData.action == 'goBack')
		{
			document.location.href = '/admin/extensions/newsletter_v2.html';
		}
		else
		{
			$super(aData);
		}
	}
});