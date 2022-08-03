<?php
/******************************************************************************/
// Created by: shlomo hassid.
// Release Version : 1.0.1
// Creation Date: 10/05/2020
// Copyright 2020, shlomo hassid.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.1:
    ->Creation - Initial
            
*******************************************************************************/
define('USE_BSIK_ERROR_HANDLERS', true);

/******************************************************************************/
/******************************  REQUIRES  ************************************/
/******************************************************************************/
require_once 'bsik.php';
require_once BSIK_AUTOLOAD;

use \Bsik\Std;
use \Bsik\Base;
use \Bsik\Settings\CoreSettings;
use \Bsik\Api\FrontApi;
use \Bsik\Trace;
use \Bsik\Users\User;
use \Bsik\Render\FPage;

Trace::add_step(__FILE__, "Controller - front index");


/******************************************************************************/
/*********************  LOAD CONF AND DB CONNECTION  **************************/
/******************************************************************************/
Base::configure($conf);
Trace::add_trace("Loaded Base Configuration Object",__FILE__, $conf);
Base::connect_db();
Trace::add_trace("Establish db connection",__FILE__);

/******************************************************************************/
/*********************  LOAD CORE SETTINGS  ***********************************/
/******************************************************************************/
if (!CoreSettings::extend_from_database(Base::$db)) {
    throw new Exception("Cant Load Settings", E_PLAT_ERROR);
}

//Core settings:
CoreSettings::load_constants();

//Set object defaults:
Trace::$enable = CoreSettings::get("trace-debug-expose", false);
Base::$db->setTrace(Trace::$enable);
\Bsik\Render\Template::$default_debug      = CoreSettings::get("template-rendering-debug-mode", false);
\Bsik\Render\Template::$default_autoreload = CoreSettings::get("template-rendering-auto-reload", true);

//Session start:
if(!session_id()){ session_start(); }

/******************************************************************************/
/**************************   LOAD USER   *************************************/
/******************************************************************************/
/******************************  Load Admin      *****************************/
$User = new User();
Trace::add_trace("Loaded User Object", __FILE__);

/******************************  User login / logout   *****************************/
//Check user signed or not:
$User->user_login();
$User->initial_user_login_status();
Trace::reg_vars(["User signed" => $User->is_signed]);
Trace::add_trace("User login status",__FILE__, $User->user_data);
Trace::reg_vars(["User granted privileges" => $User->priv->all_granted(true)]);

// Trace::expose_trace();
// exit;

/******************************************************************************/
/************************  FRONT PAGE CONSTANTS  ******************************/
/******************************************************************************/
FPage::set_user_string("----------");
FPage::$index_page_url = CoreSettings::$url["full"];
FPage::tokenize();
FPage::load_request($_REQUEST ?? []);
FPage::load_logger(
    path : CoreSettings::$path["logs"],
    channel: FPage::$request->type == "api" ? "fapi-front" : "fpage-general",
);
FPage::load_defined_pages();
FPage::load_paths(global_dir  : ["front", "global"]);

//Initialize Api:
$FApi = new FrontApi(
    csrf                : FPage::csrf(),  // CSRF TOKEN
    debug               : CoreSettings::get("api-responses-with-debug-info", false), 
    issuer_privileges   : $User->priv
);

//------
Trace::add_trace("Loaded FrontPage object", __FILE__, ["token" => FPage::$token]);
Trace::reg_vars(["Request"          => FPage::$request->get()]);
Trace::reg_vars(["Requested page"   => FPage::$request->page]);
Trace::reg_vars(["Requested which"  => FPage::$request->which]);
Trace::reg_vars(["Available pages"  => FPage::$pages]);

/******************************  Global Includes      *****************************/
//Load global endpoints:
//TODO: we need to consider this as the possibility of full dynamic front is supported
if (Std::$fs::file_exists(FPage::$paths["global-api"])) {
    include_once FPage::$paths["global-api"];
}

/******************************************************************************/
/************************    CONTROLLER LOGIC    ******************************/
/******************************************************************************/

Trace::add_step(__FILE__,"Loading and building page:");
switch (FPage::$request->type) {
    
    case "page": {

        Trace::add_trace("Type detected", __FILE__, FPage::$page_type);
        //Load page -> request based:
        if (FPage::load_page_record()) {

            //Two types of pages:
            switch (FPage::$page_type) {

                case "file": {
                
                    //paths for file based page:
                    FPage::load_paths(page_dir : ["front", "pages"]);
                    Trace::add_trace("loaded page required paths", __FILE__, FPage::$paths);
                    
                    //Include page implementation:
                    include FPage::$paths["page"].DS.FPage::$page["file_name"];

                    //Trace some defined properties:
                    Trace::add_trace("register pages", __FILE__, FPage::$implemented_pages);
                    $Page_instance = FPage::load_page(FPage::$page["name"], $User);

                    if (FPage::is_allowed()) {
                        //Render the page:    
                        $Page_instance->render();
                        Trace::add_trace("page settings", __FILE__, FPage::$settings->get_all());
                    } else {
                        //Jump to home say no privileges:
                        FPage::jump_to_page();
                    }
                } break;

                case "dynamic": {
                    var_dump("dynamic");
                    var_dump(FPage::$page);
                } break;
            }
            // --> is it static  ???????
                    // --> load global components
                    // --> load global api.
                    // --> load page.

            // --> is it dynamic ???????
                    // --> load global api.
                    // --> load dynamic page builder.
        } else {
            FPage::error_page(404);
        }
        Trace::expose_trace();
    } break;
    case "api": {

        Trace::add_trace("Api type request detected", __FILE__);
        $FApi->set_headers(content : "application/json");
        
        //Preload endpoints of the current page:
        if (FPage::load_page_record()) {
            //Two types of pages:
            switch (FPage::$page_type) {
                case "file": {
                    //paths for file based page:
                    FPage::load_paths(page_dir : ["front", "pages"]);
                    //Preloads the module end point:
                    if (Std::$fs::file_exists("raw", FPage::$paths["page-api"])) {
                        include_once FPage::$paths["page-api"];
                    }
                } break;
                case "dynamic": {
                } break;
            }
        }
        
        $FApi->parse_request($_REQUEST);

        if ($FApi->is_manage_call()) {
            $FApi->answer_from_manage(print : true);
        } else {
            $FApi->answer(print : true, execute : true, external : true);
        }
    } break;
    case "error": {
        FPage::error_page(FPage::$request->page);
    } break;
    case "logout": {
        if ($User->is_signed) {
            $User->user_logout();
        }
        FPage::jump_to_page();
    }
    break;
    default: {
        FPage::error_page(404);
    }
}


