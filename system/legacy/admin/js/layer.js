
// verschiebbarer layer
//� Dynamic Drive (www.dynamicdrive.com)

var dragapproved=false;
var zcor,xcor,ycor;

function movescontentmain(){
	if (event.button == 1 && dragapproved){
		zcor.style.pixelLeft=tempvar1+event.clientX-xcor;
		zcor.style.pixelTop=tempvar2+event.clientY-ycor;
		leftpos=document.all.scontentmain.style.pixelLeft-document.body.scrollLeft;
		toppos=document.all.scontentmain.style.pixelTop-document.body.scrollTop;
		return false;
	}
}

function dragscontentmain(){
	if (!document.all)
		return;
	if (event.srcElement.id == "scontentbar" || event.srcElement.id == "scontentbarsub"){
		dragapproved=true;
		zcor=scontentmain;
		tempvar1=zcor.style.pixelLeft;
		tempvar2=zcor.style.pixelTop;
		xcor=event.clientX;
		ycor=event.clientY;
		document.onmousemove = movescontentmain;
	}
}

document.onmousedown=dragscontentmain;
document.onmouseup=new Function("dragapproved=false");
// ende verschiebbarer layer