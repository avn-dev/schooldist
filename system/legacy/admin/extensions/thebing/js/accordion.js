/* Slide Function - Vertical
 * @parms 
 * - obj => nicht nötigt
 * - id => id des zu slidenden Objektes 
 */
aSlideArray = new Array();
function vertical_slide(obj,id){
    if( aSlideArray[id] == 1) {
        Effect.BlindDown(id, { duration: 0.5,scaleX :false,queue: 'end' });
        aSlideArray[id] = 0;
        document.getElementById(id+'_top').className = "accordion_top";
    } else {
        Effect.BlindUp(id, { duration: 0.5 ,scaleX :false,queue: 'end' });
        aSlideArray[id] = 1;
        document.getElementById(id+'_top').className = "accordion_top_up";
    }

}
