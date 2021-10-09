/******************************  SIDE MENU HANDLERS  *****************************/
import * as $$ from './utils.module.js';
import * as SikCore from './core.module.js';
import * as SikLoaded from './loaded.module.js';
import { SikNotify } from './sikNotify.module.js';
import * as SikDataTables from './sikDataTables.module.js';



/*****************************  MAIN APP CLASS *********************************************/
window["Bsik"] = {};
let Bsik = window.Bsik;
window.Bsik["notify"] = SikNotify.init();
window.Bsik["core"] = SikCore;
window.Bsik["loaded"] = SikLoaded;
window.Bsik["dataTables"] = SikDataTables;
window.Bsik["userEvents"] = {};
window.Bsik["modals"] = { confirm :  null };
window.Bsik["module"] = {};

/*****************************  BASIC CORE EVENTS *********************************************/
//Set Bsik defaults:
//The meta will always be visible because scripts are added after them:
window.Bsik.loaded.module.name = $("meta[name='module']").attr("content");
window.Bsik.loaded.module.sub = $("meta[name='module-sub']").attr("content");

//Register confirmation if its set:
if (document.getElementById('bsik-confirm-modal'))
    Bsik.modals.confirm = new bootstrap.Modal(
        document.getElementById('bsik-confirm-modal'),
        Bsik.core.helpers.objAttr.getDataAttributes("#bsik-confirm-modal")
    );

//Create module space:
window.Bsik["module"][window.Bsik.loaded.module.name] = {};

//Default handlers:
document.addEventListener("DOMContentLoaded", function(event) {

    //Expand menu:
    $(".admin-menu > .menu-entry.has-submenu").on("click", function() {
        $(this).toggleClass("open-menu");
    });

    //Load module by menu click:
    $(".admin-menu .menu-entry").not(".has-submenu").on("click", function(e) {
        e.stopPropagation();
        let load = $(this).data("menuact");
        console.log("load module: ", load);
    });

});