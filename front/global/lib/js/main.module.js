
import { ButtonState }  from '/bsik/manage/lib/js/ButtonState.module.js';

document.addEventListener("DOMContentLoaded", function(event) {

(function($, window, document, Bsik, undefined) {

    console.log("Loaded main", Bsik);


    /***********************************************************
     * ButtonState plugin init:
     **********************************************************/
    $(".button-state").each(function(){
        let btn = new ButtonState(this, {
            default_state : "active"
        });
    });
    /***********************************************************
     * PerfectScrollbar plugin init:
     **********************************************************/
    //update the scroll bars delayed just to make sure containers are visible:
    if (typeof PerfectScrollbar === 'function') {
        setTimeout(function(){ 
            for (let [key, scroll] of Object.entries(window.Bsik.scrollbars)) {
                scroll.update();
            }
        }, 1000);

        //Re-update if page size changes:
        window.addEventListener('resize', function(event) {
            for (let [key, scroll] of Object.entries(window.Bsik.scrollbars)) {
                scroll.update();
            }
        }, true);
    }

}) (jQuery, window, document, window.Bsik);

});