
var AddresslabelGui = Class.create(ATG2,
{
	requestCallbackHook: function($super, aData)
	{
		$super(aData);

		if($('fieldsList'))
		{
			var oThis = this;

			Sortable.destroy('fieldsList');

			Sortable.create('fieldsList',
			{
				tag:		'div',
				only:		'fieldsItem',
				handle:		'.fieldsItemMover',
				constraint:	'vertical',
				onUpdate:	function()
				{
					oThis._writePositions();
				},
				onChange:	function()
				{
					oThis._writePositions();
				}
			});

			this._writePositions();
		}
	},

	_writePositions: function()
	{
		var aItems = $A($$('.fieldsItemPosition'));

		var iPosition = 1;

		aItems.each(function(oItem)
		{
			oItem.value = iPosition++;
		});
	}
});