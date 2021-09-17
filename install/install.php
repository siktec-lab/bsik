<?php
ini_set('max_execution_time', 300);
set_time_limit(300);

define("DS", DIRECTORY_SEPARATOR);

require_once "SikInstall.class.php";

$run = new SikInstall();

/*******************************************************************************/
/******************************  Set core folders  *****************************/
/*******************************************************************************/
$run->set_core_folder("required", ".".DS."manage".DS."lib".DS."required".DS, true);
$run->set_core_folder("themes", ".".DS."manage".DS."lib".DS."themes".DS, false);

/********************************************************************************/
/******************************  Register Required  *****************************/
/********************************************************************************/
$run->add_to_folder( //Jquery is required installed with composer
    "required", "jquery", 
    ".".DS."vendor".DS."packages".DS."jquery".DS."dist".DS,
    "^jquery"
);
$run->add_to_folder( //morris is required installed with composer for charts in the manage platform
    "required", "morris-js", 
    ".".DS."vendor".DS."packages".DS."morris-js".DS, 
    "*morris"
);
$run->add_to_folder( //raphael is required installed with composer for charts in the manage platform
    "required", "raphael-js", 
    ".".DS."vendor".DS."packages".DS."raphael-js".DS, 
    "*raphael"
);
$run->add_to_folder( //bootstrap is required installed with composer
    "required", "bootstrap", 
    ".".DS."vendor".DS."twbs".DS."bootstrap".DS."dist".DS,
    "."
);
$run->add_to_folder( //bootstrap-table is required installed with composer for dynamic tables support of manage
    "required", "bootstrap-table", 
    ".".DS."vendor".DS."wenzhixin".DS."bootstrap-table".DS."dist".DS,
    "."
);
$run->add_to_folder( //font-awesome is required installed with composer supports icons across manage platform
    "required", "font-awesome", 
    ".".DS."vendor".DS."fortawesome".DS."font-awesome".DS,
    "=css|js|otfs|webfonts"
);

/***********************************************************************************/
/******************************  Register themes core  *****************************/
/***********************************************************************************/
/*
$run->add_to_folder( //font-awesome is required installed with composer supports icons across manage platform
    "themes", "bootstrap", 
    ".".DS."vendor".DS."twbs".DS."bootstrap".DS."scss".DS,
    "."
);
*/

/**************************************************************************************/
/******************************  Execute installer steps  *****************************/
/**************************************************************************************/
$run->message(" *** Running post composer initialization ***");
$run->message("Root Path is : [".$run->root."]", true);
$run->message(" ** Checking core folders structure ** ");
$run->make_core_folders();
$run->message(" ** Cleaning directories ** ");
$run->clean_destinations();
$run->message(" ** Creating registered directories ** ");
$run->create_destinations();
$run->message(" ** Move registered content ** ");
$run->move_destinations();


$run->message(" ** INSTALLATION DONE ** ");

?>