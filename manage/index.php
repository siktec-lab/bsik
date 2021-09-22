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

define('DS', DIRECTORY_SEPARATOR);
define("ROOT_PATH", dirname(__FILE__).DS.".." );

/******************************  Requires       *****************************/

require_once ROOT_PATH.DS.'conf.php';
require_once PLAT_PATH_AUTOLOAD;
require_once PLAT_PATH_CORE.DS.'Base.class.php';
require_once PLAT_PATH_CORE.DS.'Admin.class.php';
require_once PLAT_PATH_MANAGE.DS.'core'.DS.'AdminPage.class.php';

Trace::add_step(__FILE__, "Controller - manage index");

if(!session_id()){ session_start(); }

/*********************  Load Conf and DataBase  *****************************/
Base::configure($conf);
Trace::add_trace("Loaded Base Configuration Object",__FILE__, $conf);
Base::connect_db();
Trace::add_trace("Establish db connection",__FILE__);

/******************************  Load Admin      *****************************/
$Admin = new Admin();
Trace::add_trace("Loaded Admin Object", __FILE__);
Trace::reg_vars(["admin levels" => $Admin->levels]);

/******************************  User login / logout   *****************************/
//Check user signed or not:
$Admin->admin_login();
$Admin->initial_admin_login_status();
Trace::reg_vars(["Admin signed" => $Admin->is_signed]);
Trace::add_trace("Admin login status",__FILE__, $Admin->admin_data);


/******************************  Load Modules And Pages *****************************/
$APage = new APage(
    $Admin->admin_identifier(), // For logging Admin identifier
    "bsikrender-manage",        // For the chanel to use  
);
Trace::add_trace("Loaded AdminPage object", __FILE__, ["request" => $APage->request, "token" => $APage->token]);
Trace::reg_vars(["Requested module" => $APage->request]);
Trace::reg_vars(["Available modules" => $APage->modules]);

/******************************  Build Page      *****************************/
Trace::add_step(__FILE__,"Loading and building page:");
switch ($APage->request["type"]) {
    case "module": {
        //Must be signed in:
        Trace::add_trace("Module type detected", __FILE__);
        if (!$Admin->is_signed) {
            Trace::add_trace("Module requires Admin to be signed in - redirecting", __FILE__);
            require_once PLAT_PATH_MANAGE.DS."pages".DS."login.php";
        }
        //Make sure Module Exists:
        elseif (!$APage->isset_module()) {
            Trace::add_trace("Requested module is not set", __FILE__);
            //$APage::error_page("module_not_set");
        }
        //Make sure exists and is allowed?
        elseif (!$APage->is_allowed_to_use($Admin)) {
            Trace::add_trace("Module requires privileges that admin does not have", __FILE__);
            $APage::error_page("admin_is_not_allowed");
        } else {
            Trace::add_trace("Admin check successfully - signed, is-set, is-allowed", __FILE__);
            //Load the platform that will also render the module:
            $APage->load_module();
            Trace::add_trace("Module loaded to Apage.", __FILE__);
            Trace::reg_vars(["Loaded module" => $APage->module]);
            Trace::reg_vars(["Loaded page settings (extended)" => $APage->settings]);
            require_once PLAT_PATH_MANAGE.DS."pages".DS."base.php";
        }
        Trace::expose_trace();
    } break;
    case "api": {
        Trace::add_trace("Api type request detected", __FILE__);
        //Load core Api end points and Api object:
        require_once PLAT_PATH_MANAGE.DS."core".DS."AdminApi.class.php";
        //Must be signed in:
        if (!$Admin->is_signed) {
            $AApi->update_answer_status(403, "You must be registered and signed as an Admin.");
        }
        //Make sure Module Exists:
        elseif ($APage->isset_module() && $APage->is_allowed_to_use($Admin)) {
            Trace::add_trace("Admin check successfully - signed, is-set, is-allowed", __FILE__);
            $APage->load_module();
            //Load module extenssion:
            try {
                require_once PLAT_PATH_MANAGE.DS."modules".DS.$APage->module->name.DS."module-api.php";
            } catch (Throwable $e) {
                $AApi->logger->notice("Api extension of module throwed error - ignored.", ["module" => $APage->module->name, "error" => $e->getMessage()]);
                $AApi->update_answer_status(404, "Api extension of module throwed error - ignored.");
            }
        }
        //Execute if everything is ok:
        if ($AApi->request->answer->code === 0) {
            $AApi->parse_request($_REQUEST);
            $AApi->execute();
        }
        $AApi->answer(true);
    } break;
    case "error": {
        echo "error";
        var_dump($_REQUEST);
    } break;
    case "logout": {
        if ($Admin->is_signed) {
            $Admin->admin_logout();
        }
        $APage::jump_to_page();
    }
    break;
    default: {
        // Not found page:
        $APage::error_page("module_type_not_set");
        Trace::expose_trace();
    }
}

