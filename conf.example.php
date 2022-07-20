<?php
/******************************************************************************/
// Created by: shlomo hassid.
// Release Version : 1.0.1
// Creation Date: 10/05/2020
// Copyright 2020, shlomo hassid.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.1:
    ->Creation, initial.
*******************************************************************************/
/******************************  constants  *****************************/

define("BUILD_ON",      "PHP 8");
define("APP_VERSION",   "1.0.1");

/************************** System Configuration & Trace ******************************/
define('PLAT_CHARSET',                      'UTF-8');
define('PLAT_TIMEZONE',                     'Asia/Jerusalem');
define('PLAT_HASH_SALT',                    'ssaltSh');
define('EXPOSE_OP_TRACE',                   false);
define('PLAT_ADMIN_PANEL_API_DEBUG_MODE',   true);
define('PLAT_SESSION_LIFETIME',             86400);
if (!defined('USE_BSIK_ERROR_HANDLERS'))
    define('USE_BSIK_ERROR_HANDLERS',       true);
define('PLAT_EXPOSE_PHP_ERRORS',            true);
define('ERROR_METHOD',                      'inline'); // inline | redirect | hide
define('PLAT_LOG_DIRECTORY',                ROOT_PATH.DS."logs".DS);

/******************************  headers and ini  *****************************/
header('Content-Type: text/html; charset='.PLAT_CHARSET);
date_default_timezone_set(PLAT_TIMEZONE);
ini_set("session.gc_maxlifetime", PLAT_SESSION_LIFETIME);
ini_set("session.cookie_lifetime", PLAT_SESSION_LIFETIME);
ini_set('log_errors', true);
ini_set('error_log', PLAT_LOG_DIRECTORY.'php_errors.log');
error_reporting(-1); // -1 all, 0 don't
ini_set('display_errors', PLAT_EXPOSE_PHP_ERRORS ? 'on' : 'off');


/******************************  Configuration - DataBase  *****************************/
$conf = [];
//Insert your db credentials:
$conf["db"] = [
    'host'   => 'localhost',
    'port'   => '3306',
    'name'   => 'bsik',
    'user'   => 'bsik_db_user',
    'pass'   => '*************'
];

//Control what to log & send:
define("SEND_DB_ERRORS", false);
define("SEND_ERRORS_TO", "example@gmail.com");
define("LOG_DB_ERRORS", true);
define("LOG_DB_TO_TABLE", "bsik_db_error_log");
define("LOG_PLAT_ERRORS", true);
define("LOG_PLAT_TO_TABLE", "plat_log");

/******************************  Configuration - path  *****************************/
$conf["path"] = [ "domain" => "http://localhost", "base" => "/bsik"];
$conf["path"]["site_base_path"]     = ROOT_PATH; // Defined in index
$conf["path"]["site_admin_path"]    = $conf["path"]["site_base_path"].DS.'manage';
$conf["path"]["site_admin_url"]     = $conf["path"]["domain"].$conf["path"]["base"].'/manage';
$conf["path"]["site_base_url"]      = $conf["path"]["domain"].$conf["path"]["base"];

/******************************  Configuration - platform / base  *****************************/
/* SH: added - 2021-03-03 => extend from db */
$conf["default-page"]   = "home";   // Default for web
$conf["default-module"] = "dashboard";  // For admin panel

/******************************  PATH CONSTANTS  *****************************/
define('PLAT_URL_DOMAIN',           $conf["path"]["domain"]);
define('PLAT_URL_BASE',             $conf["path"]["base"]);
define('PLAT_PATH_BASE',            $conf["path"]["site_base_path"]);
define('PLAT_FULL_DOMAIN',          $conf["path"]["site_base_url"]);
define('PLAT_PATH_IMPORT',          $conf["path"]["site_admin_path"].DS."lib".DS."import");
define('PLAT_PATH_VENDOR',          ROOT_PATH.DS."vendor");
define('PLAT_PATH_MANAGE',          $conf["path"]["site_admin_path"]);
define('PLAT_URL_MANAGE',           $conf["path"]["site_base_url"]."/manage");
define('PLAT_PATH_CORE',            $conf["path"]["site_admin_path"].DS."core");

/******************************  FW signup  *****************************/
$conf["g-sign"] = array(
    "app-name"      => "SIK FrameWork",
    "client-id"     => "000000000000-aabababababababababab.apps.googleusercontent.com",
    "client-secret" => "234_XXXXXXXXXXXXXXXXXXXXXXXXXXXX",
    "redirect"      => $conf["path"]["site_base_url"].DS."?page=gredirect"
);

/******************************  ERROR CODES  *****************************/