function checkPass(sPass)
{
	var aKeys = new Array(
		'qwe','wer','ert','asd','sdf','dfg','yxc','xcv','cvb','trz',
		'tzu','yui','uio','iop','fgh','ghj','hjk','jkl','vbn','bnm',
		'!"�','"�$','�$%','$%&','%&/','&/(','/()','()=',')=?'
	);

	var aNumbers = new Array(
		'137','379','973','731','246','468','159','357','753','951','846',
		'461','147','258','369','741','852','963','014','025','520','410',
		'1245','2356','4578','5689','9865','8754','6532','5421'
	);

	var sAlphabetFor = 'abcdefghijklmnopqrstuvqxyz01234567890';
	var sAlphabetRev = '09876543210zyxqvutsrqponmlkjihgfedcba';

	var iTotal		= 100;
	var iLength		= sPass.length;
	var sSmall		= sPass.toLowerCase();
	var bNumbers	= sPass.match(/[\d]/i);
	var bBig		= sPass.match(/[A-Z]/);
	var bSmall		= sPass.match(/[a-z]/);
	var bExtra		= sPass.match(/[^a-z\d]/i);

	// Check the length
	if(iLength < 10)
	{
		iTotal -= 5 * (10 - iLength);
	}

	// Numbers
	if(bNumbers == null)
	{
		iTotal -= 20;
	}
	// Upper characters
	if(bBig == null)
	{
		iTotal -= 20;
	}
	// Lower characters
	if(bSmall == null)
	{
		iTotal -= 20;
	}
	// Special characters
	if(bExtra == null)
	{
		iTotal -= 20;
	}

	// Check of double same signs
	for(var i = 0; i <= iLength-3; i++)
	{
		var sSame3 = sSmall.substr(i, 3);

		if(sSame3.substr(0,1) == sSame3.substr(1,1) && sSame3.substr(1,1) == sSame3.substr(2,1))
		{
			iTotal -= 20;
			break;
		}
	}

	// Check of keyboard sequences
	for(var i = 0; i <= aKeys.length; i++)
	{
		if(sSmall.indexOf(aKeys[i]) != -1)
		{
			iTotal -= 20;
			break;
		}
	}

	// Check of number sequences
	for(var i = 0; i <= aNumbers.length; i++)
	{
		if(sSmall.indexOf(aKeys[i]) != -1)
		{
			iTotal -= 20;
			break;
		}
	}

	// Check of ABC or 123 sequences
	for(var i = 0; i <= iLength-3; i++)
	{
		var sSeq3 = sSmall.substr(i, 3);

		if(sAlphabetFor.indexOf(sSeq3) != -1 || sAlphabetRev.indexOf(sSeq3) != -1)
		{
			iTotal -= 20;
			break;
		}
	}

	return iTotal > 0 ? iTotal : 0;
}