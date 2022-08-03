<?php

//Extending the Api of manage:
require_once BSIK_AUTOLOAD;

use \Bsik\Api\ApiEndPoint;
use \Bsik\Api\AdminApi;
use \Bsik\Api\Validate;
use \Bsik\Builder\Components;
use \Bsik\Render\Template;
use \Bsik\Std;
use \Bsik\Objects\SettingsObject;
use \Bsik\Privileges as Priv;


/********************************************************************************/
/*****************  get page settings  ******************************************/
/********************************************************************************/
$get_settings_policy = new Priv\RequiredPrivileges();
$get_settings_policy->define(
    new Priv\PrivContent(view: true)
);

AdminApi::register_endpoint(new ApiEndPoint(
    module      : "pages",
    name        : "get_settings",
    working_dir     : dirname(__FILE__).DS."..",
    allow_global    : true,
    allow_external  : true,
    allow_override  : false,
    policy          : $get_settings_policy,
    describe    : "Describe the endpoint for later use",
    params      : [
        "of"     => "global",
        "name"   => null,
        "form"   => true,
        "object" => false
    ],
    filter      : [
        "of"      => Validate::filter("trim")::filter("strchars","A-Z","a-z","0-9","_","-")::create_filter(),
        "name"    => Validate::filter("trim")::filter("strchars","A-Z","a-z","0-9","_","-")::create_filter(),
        "form"    => Validate::filter("type", "boolean")::create_filter(),
        "object"  => Validate::filter("type", "boolean")::create_filter()
    ],
    validation  : [
        "of"        => Validate::condition("required")::condition("in_array", "global|page")::create_rule(),
        "name"      => Validate::condition("optional")::condition("min_length", "2")::create_rule(),
        "form"      => Validate::condition("type", "boolean")::create_rule(),
        "object"    => Validate::condition("type", "boolean")::create_rule()
    ],
    method : function(AdminApi $Api, array $args, ApiEndPoint $Endpoint) {

        //get db row:
        if ($args["of"] == "global") {

            //Import settings:
            $saved = $Api::$db->where("name", "front-pages")->getOne("bsik_settings", "object");
            $global = new SettingsObject();
            $global->import($saved["object"] ?? "");
            
            //Build the form from template:
            $engine = new Template(
                cache : Std::$fs::path($Endpoint->working_dir, "templates", "cache")
            );

            $form = "";
            if ($args["form"]) {
                $engine->addFolders([Std::$fs::path($Endpoint->working_dir, "templates")]);
                $form = Components::settings_object_form(
                    settings   : $global,
                    attrs    : ["data-which" => $args["of"], "data-name" => "none"],
                    engine   : $engine,
                    template :"settings_form"
                );
            }

            //Set return data
            $Api->request->answer_data([
                "settings" => $global->dump_parts(false, "values-merged", "options", "descriptions"),
                "form"     => $form,
                "object"   => $args["object"] ? serialize($global) : ""
            ]);

        } elseif (!empty($args["name"])) {

            $page = $Api::$db->where("name", $args["name"])->getOne("page_all", "settings, page_folder, type");
            $Api->register_debug("from-page", $page);
            if (!empty($page)) {

                //Get global pages settings:
                $global = $Api::$db->where("name", "front-pages")->getOne("bsik_settings", "object");
                $global = Std::$str::parse_json($global["object"] ?? "", onerror: []);
                Std::$arr::rename_key("values", "defaults", $global);

                //Get local template settings if its static:
                $local = [];
                if ($page["type"] === 2) {
                    $local =  Std::$fs::get_json_file(
                        Std::$fs::path_to("front-pages", [$page["page_folder"], "settings.jsonc"])["path"]
                    ) ?? [];
                    Std::$arr::rename_key("values", "defaults", $local);
                }
                
                //Load the settings:
                $settings = new SettingsObject();
                $settings->import($global);
                $settings->import($local);
                $settings->extend($page["settings"]);
                
                $form = "";
                if ($args["form"]) {
                    //Build the form from template:
                    $engine = new Template(
                        cache : Std::$fs::path($Endpoint->working_dir, "templates", "cache")
                    );
                    $engine->addFolders([Std::$fs::path($Endpoint->working_dir, "templates")]);

                    //Create form:
                    $form = Components::settings_object_form(
                        settings   : $settings,
                        attrs    : ["data-which" => $args["of"], "data-name" => $args["name"]],
                        engine   : $engine,
                        template : "settings_form"
                    );
                }

                //Return:
                $Api->request->answer_data([
                    "settings"  => $settings->dump_parts(false, "values-merged", "options", "descriptions"),
                    "form"      => $form,
                    "object"    => $args["object"] ? serialize($settings) : ""
                ]);

            } else {
                $Endpoint->log_error(
                    message : "Error while loading page ['{$args["name"]}'], this should not happen"
                );
                $Api->request->update_answer_status(500, "page not found registered in db");
            }
        } else {
            $Api->request->update_answer_status(400, "request is does not have required params.");
        }
        return true;
    }
));

/*********************************************************************************/
/*****************  save page settings  ******************************************/
/*********************************************************************************/
$save_settings_policy = new Priv\RequiredPrivileges();
$save_settings_policy->define(
    new Priv\PrivContent(edit: true)
);
AdminApi::register_endpoint(new ApiEndPoint(
    module      : "pages",
    name        : "save_settings", 
    params      : [
        "of"        => null,
        "settings"  => null,
        "name"      => null
    ],
    filter      : [
        "of"        => Validate::filter("trim")::filter("strchars","A-Z","a-z")::create_filter(),
        "name"      => Validate::filter("trim")::filter("strchars","A-Z","a-z","0-9","_","-")::create_filter(),
        "settings"  => Validate::filter("trim")::create_filter(),
    ],
    validation  : [
        "of"        => Validate::condition("required")::condition("in_array", "global|page")::create_rule(),
        "name"      => Validate::condition("optional")::condition("min_length", "2")::create_rule(),
        "settings"  => Validate::condition("required")::condition("min_length", "1")::create_rule()
    ],
    method : function(AdminApi $Api, array $args, ApiEndPoint $Endpoint) {

        //Get current settings:
        $get = $Api->call(
            args     : array_merge($args, ["form" => false, "object" => true]), 
            endpoint : "pages.get_settings"
        );
        

        if ($get->answer_code() === 200) {

            /** @var SettingsObject $settings */
            $settings = unserialize($get->answer_data()["object"]);
            $errors = [];
            

            if ($args["of"] === "page") {
                
                if ($settings->extend($args["settings"], $errors)) {
                    //Save to db:
                    $Api::$db->where("name", $args["name"])->update("page_all", [
                        "settings" => $settings->values_json(true)
                    ], 1);
                    //Set answer:
                    $Api->request->answer_data([
                        "settings" => $settings->dump_parts(false, "values"),
                    ]);
                } else {
                    //Errors while extending - return those:
                    $Api->request->add_errors($errors);
                    $Api->request->update_answer_status(500);
                }

            } else {

                if ($settings->extend($args["settings"], $errors)) {

                    //Save to db:
                    $Api::$db->where("name", "front-pages")->update("bsik_settings", [
                        "object" => $settings->dump_parts(true, "values-merged", "options", "descriptions")
                    ], 1);
                    //Set answer:
                    $Api->request->answer_data([
                        "settings" => $settings->dump_parts(true, "values-merged", "options", "descriptions"),
                    ]);
                } else {
                    //Errors while extending - return those:
                    $Api->request->add_errors($errors);
                    $Api->request->update_answer_status(500);
                }
                
            }
        } else {
            $Api->request->answer = clone $get->answer;
        }
        return true;
    },
    working_dir     : dirname(__FILE__).DS."..",
    allow_global    : true,
    allow_external  : true,
    allow_override  : false,
    policy          : $save_settings_policy
));

