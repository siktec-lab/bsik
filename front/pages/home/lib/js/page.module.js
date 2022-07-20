//TODO: this import should be improved -> add a method to set window globals this way we can expose some data such a app root:
//var AppRoot = "/bsik";
//const { SikDropdown } = await import(window.AppRoot + '/manage/lib/js/sikDropdown.module.js');
//window.SikDropdown = SikDropdown;

import { SmartForm }    from '/bsik/manage/lib/js/SmartForm.module.js';
import { SikDropdown } from '/bsik/manage/lib/js/sikDropdown.module.js';
import { Register } from './register.module.js';
import { Login } from './login.module.js';
import { PageTabs } from './pageTabs.module.js';


document.addEventListener("DOMContentLoaded", function(event) {

(function($, window, document, Bsik, undefined) {

    console.log("Loaded page", Bsik);

    //Set modals:
    Bsik.modals.register = new bootstrap.Modal(
        document.getElementById('user-register'),
        Bsik.core.helpers.objAttr.getDataAttributes("#user-register")
    );
    Bsik.modals.registerElement = $(Bsik.modals.register._element);
    Bsik.modals.login = new bootstrap.Modal(
        document.getElementById('user-login'),
        Bsik.core.helpers.objAttr.getDataAttributes("#user-login")
    );

    //Page view tabs:
    let content = new PageTabs(
        ".tb-content-tab",  
        { home : ".application-slider"} // Trigger resize on those
    );
    
    //Crypto selector:
    var dropdown = new SikDropdown("#crypto-select", {
        name        : "select-example",
        placeholder : "Select Currency",
        value       : null
    });
    console.log(dropdown);

    //Login form definition:
    let loginForm = new SmartForm(
        document.getElementById("user-login-form"), 
        {
            validate : [
                //{ field : "username",   rule : "email",      args : [] },
                //{ field : "password",   rule : "password",   args : [[], /* len */ 8, /* upper */ 1, /* lower */ 1, /* digits */ 1, /* special */ 1] },
            ],
            map                      : {
                "login-user-name" : "username",
                "login-password"  : "password",
                "login-set-rem"   : "remember"
            }
        }
    );
    
    //Login plugin:
    Bsik.login = new Login(
        loginForm,                              // login form SmartForm
        "button[data-action='user-login']",     // login button
        Bsik.modals.login,                      // login form modal
        { //Options:
        
        }
    );

    //Registration form definition:

    //Registration plugin:
    Bsik.register = new Register("#registration-procedure", Bsik.modals.register, {
        signup_form     : "#user-signup-form",
        signup_button   : '#signup-continue',
        signup_plans    : "#register-select-plan",
    });

    /************* Set user actions **********/
    $.extend(Bsik.userEvents, {
        "click navigate" : function(event) {
            event.preventDefault();
            let $menuEntry = $(this);
            let tab = $menuEntry.data("tab");
            let url = $menuEntry.attr("href");
            content.show(tab, url);
            $menuEntry.closest(".pages-tabs").find(".tab-selected").removeClass("tab-selected");
            $menuEntry.closest("li").addClass("tab-selected");
        },
        "click redirect" : function(event) {
            
            let to = $(this).data("link");
            console.log("redirect", to);
            Bsik.core.redirectPage(to, true);
        },
        // Open register modal with preselected plan:
        "click open-register-modal-with-plan" : function(event) {
            
            let plan = $(this).closest("li").data("plan");
            Bsik.register.openModal("proc-signup" ,plan);
        },
        // Open register modal from any link button:
        "click open-register-modal" : function() {
            let plan = $(this).data("plan") ?? "none";
            Bsik.register.openModal("proc-signup" ,plan);
        },
        // Open register modal from any link button:
        "click test-self-api" : function() {
            console.log("test1");
            Bsik.core.apiRequest(null, "front.hi", { name : "shlomi" }, 
                {
                    error: function(jqXhr, textStatus, errorMessage) {
                        // let error = jqXhr.responseJSON ? jqXhr.responseJSON.errors || [errorMessage] : [errorMessage];
                        // Bsik.notify.error(`say hello error - ${error.join()}`, true);
                        console.log(jqXhr.responseJSON);
                    },
                    success: function(res) {
                        console.log(res);
                    }
                }
            );
        },
        // Open register modal from any link button:
        "click test-admin-api" : function() {
            console.log("test2");
            Bsik.core.apiRequest(null, "manage.dashboard.sayhello", { name : "shlomi" }, 
                {
                    error: function(jqXhr, textStatus, errorMessage) {
                        // let error = jqXhr.responseJSON ? jqXhr.responseJSON.errors || [errorMessage] : [errorMessage];
                        // Bsik.notify.error(`say hello error - ${error.join()}`, true);
                        console.log(jqXhr.responseJSON);
                    },
                    success: function(res) {
                        console.log(res);
                    }
                }
            );
        }
    });

    //Attach user actions:
    Bsik.core.helpers.onActions("click change", "data-action", Bsik.userEvents);

    //Load slick sliders:
    $('.application-slider').slick({
        dots: true,
        infinite: true,
        speed: 300,
        slidesToShow: 1,
        arrows: false,
        //adaptiveHeight: true
    });

    //Pricing toggler:
    $(document).on("click", ".select-yearly-payment", function(){
        //All connected togglers:
        let toggler  = $(this);
        let togglers = $(".select-yearly-payment");
        let plans    = $("ul.plans-list");
        let state    = toggler.prop("checked");
        //Sync all togglers:
        togglers.each(function() {
            $(this).prop("checked", state);
        });
        //update plans cards:
        plans.each(function() {
            let list    = $(this);
            let cards   = list.find("li[data-update]");
            let monthly = state ? false : true;
            cards.each(function() {
                let price       = $(this).data("price");
                let yearFactor  = parseFloat($(this).data("yearly"));
                let desc        = $(this).find("p.plan-description");
                if (monthly) {
                    desc.text(`$ ${price} / Month`);
                } else {
                    desc.text(`$ ${parseInt(price*yearFactor)} / Year`);
                }
            });
        });
    });
    $("ul.plans-list .form-check-input").each(function(){
        $(this).closest("li").on("click", function(){
            let allPlans = $(this).closest("ul.plans-list").find("li");
            allPlans.removeClass("selected");
            $(this).find(".form-check-input").prop("checked", true);
            $(this).addClass("selected");
        });
    });

})(jQuery, window, document, window.Bsik);

});