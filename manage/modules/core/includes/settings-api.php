<?php

//Extending the Api of manage core for settings handling:
require_once BSIK_AUTOLOAD;

use \Bsik\Api\ApiEndPoint;
use \Bsik\Api\AdminApi;
use \Bsik\Api\Validate;
use \Bsik\Builder\Components;
use \Bsik\Render\Template;
use \Bsik\Std;
use \Bsik\Trace;
use \Bsik\Objects\SettingsObject;
use \Bsik\Privileges as Priv;
use Bsik\Settings\CoreSettings;

/********************************************************************************/
/*****************  get system settings  ******************************************/
/********************************************************************************/
$get_core_settings_policy = new Priv\RequiredPrivileges();
$get_core_settings_policy->define(
    new Priv\PrivAccess(manage : true)
);

AdminApi::register_endpoint(new ApiEndPoint(
    module      : "core",
    name        : "get_system_settings",
    working_dir     : dirname(__FILE__).DS."..",
    allow_global    : true,
    allow_external  : false,
    allow_override  : false,
    policy          : $get_core_settings_policy,
    describe    : "Return core system settings",
    params      : [],
    filter      : [],
    validation  : [],
    method : function(AdminApi $Api, array $args, ApiEndPoint $Endpoint) {

        //Answer data:
        $Api->request->answer_data([
            "settings"  => strval(CoreSettings::$settings),
            "object"    => serialize(CoreSettings::$settings)
        ]);

        return true;
    }
));

/********************************************************************************/
/*****************  get system settings  ******************************************/
/********************************************************************************/
AdminApi::register_endpoint(new ApiEndPoint(
    module          : "core",
    name            : "get_system_settings_groups",
    working_dir     : dirname(__FILE__).DS."..",
    allow_global    : true,
    allow_external  : true,
    allow_override  : false,
    policy          : $get_core_settings_policy,
    describe        : "Return core system settings in a groups form",
    params      : [
        "groups"     => [],
        "form"       => false,
        "object"     => false,
        "array"      => false,
        "flatten"    => false
    ],
    filter      : [
        "groups"   => Validate::filter("type", "array")::filter("strchars","A-Z","a-z","0-9","_","-")::create_filter(),
        "form"     => Validate::filter("type", "boolean")::create_filter(),
        "object"   => Validate::filter("type", "boolean")::create_filter(),
        "array"   => Validate::filter("type", "boolean")::create_filter(),
        "flatten"   => Validate::filter("type", "boolean")::create_filter()
    ],
    validation  : [
        "groups"    => Validate::condition("required")::condition("type", "array")::condition("count", "0", "200")::create_rule(),
        "form"      => Validate::condition("type", "boolean")::create_rule(),
        "object"    => Validate::condition("type", "boolean")::create_rule(),
        "array"    => Validate::condition("type", "boolean")::create_rule(),
        "flatten"    => Validate::condition("type", "boolean")::create_rule()
    ],

    method : function(AdminApi $Api, array $args, ApiEndPoint $Endpoint) {

        //Put into groups:
        $settings = CoreSettings::$settings->get_all();
        $filtered = [];
        $flatten = [];

        foreach($settings as $key => $value) {
            $group = explode("-", $key)[0];
            if (!empty($args["groups"]) && !in_array($group, $args["groups"])) {
                continue;
            }
            if (!array_key_exists($group, $filtered)) {
                $filtered[$group] = new SettingsObject();
            }
            if ($args["flatten"] && !array_key_exists($group, $filtered)) {
                $flatten[$group] = [];
            }
            /** @var SettingsObject[] $filtered  */
            $opt = CoreSettings::$settings->get_key($key);
            if (!is_null($opt["option"])) 
                $filtered[$group]->set_option($key,  $opt["option"]);
            if (!is_null($opt["default"]))
                $filtered[$group]->set_default($key, $opt["default"]);
            if (!is_null($opt["description"])) 
                $filtered[$group]->set_description($key,  $opt["description"]);
            if (!is_null($opt["value"]))
                $filtered[$group]->set($key,  $opt["value"]);
            if ($args["flatten"]) {
                $flatten[$group][$key] = $opt;
                $flatten[$group][$key]["calculated"] = $filtered[$group]->get($key);
            }
        }

        if ($args["object"]) {
            $Api->request->append_answer_data([
                "object" => $filtered
            ]);
        }

        if ($args["array"]) {
            $to_arr = [];
            foreach ($filtered as $group => $set) {
                /** @var SettingsObject $set  */
                $to_arr[$group] = $set->dump_parts();
            }
            $Api->request->append_answer_data([
                "array" => $to_arr
            ]);
        }

        if ($args["flatten"]) {
            $Api->request->append_answer_data([
                "flatten" => $flatten
            ]);
        }
        return true;
    }

));
