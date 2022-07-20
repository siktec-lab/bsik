<?php
//Extending the Api of manage:
require_once PLAT_PATH_AUTOLOAD;

use \Bsik\Api\ApiEndPoint;
use \Bsik\Api\ApiAnswerObj;
use \Bsik\Api\AdminApi;
use \Bsik\Api\Validate;
use Bsik\Builder\Components;
use Bsik\Render\Template;
use Bsik\Std;
use Bsik\Objects\SettingsObject;
use Bsik\Privileges as Priv;


/********************************************************************************/
/*****************  get page names  *********************************************/
/********************************************************************************/
$get_names_policy = new Priv\RequiredPrivileges();
$get_names_policy->define(
    new Priv\PrivContent(view: true)
);
AdminApi::register_endpoint(new ApiEndPoint(
    module      : "pages",
    name        : "get_pages_names", 
    params      : [],
    filter      : [],
    validation  : [],
    method : function(AdminApi $Api, array $args, ApiEndPoint $Endpoint) {
        $Api->request->answer_data([
            "pages" => $Api::$db->getValue("page_all", "name", null)
        ]);
    },
    working_dir     : dirname(__FILE__).DS."..",
    allow_global    : true,
    allow_external  : true,
    allow_override  : false,
    policy          : $get_names_policy
));

/********************************************************************************/
/*****************  check page name  ********************************************/
/********************************************************************************/
AdminApi::register_endpoint(new ApiEndPoint(
    module      : "pages",
    name        : "page_name_valid", 
    params      : [
        "name"      => null
    ],
    filter      : [
        "name"      => Validate::filter("trim")::filter("strchars","A-Z","a-z","0-9","_","-")::create_filter()
    ],
    validation  : [
        "name"      => Validate::condition("required")::condition("min_length", "2")::create_rule()
    ],
    method : function(AdminApi $Api, array $args, ApiEndPoint $Endpoint) {
        $name = strtolower($args["name"]);
        $exist = $Api::$db->where("name", $name)->has("page_all");
        //Set answer:
        $Api->request->answer_data([
            "valid" => !$exist,
            "name"  => $name
        ]);
    },
    working_dir     : dirname(__FILE__).DS."..",
    allow_global    : true,
    allow_external  : true,
    allow_override  : false,
    policy          : $get_names_policy
));


/*********************************************************************************/
/*****************  delete page  *************************************************/
/*********************************************************************************/
$delete_page_policy = new Priv\RequiredPrivileges();
$delete_page_policy->define(
    new Priv\PrivContent(delete: true)
);
AdminApi::register_endpoint(new ApiEndPoint(
    module      : "pages",
    name        : "delete_page", 
    params      : [
        "name"      => null
    ],
    filter      : [
        "name"      => Validate::filter("trim")::filter("strchars","A-Z","a-z","0-9","_","-")::create_filter()
    ],
    validation  : [
        "name"      => Validate::condition("optional")::condition("min_length", "2")::create_rule()
    ],
    method : function(AdminApi $Api, array $args, ApiEndPoint $Endpoint) {

        //Get current settings:
        $page = $Api::$db->where("name", $args["name"])->getOne("page_all");
        
        if (!empty($page)) {
 
            //Save to db:
            $Api::$db->where("name", $args["name"])->delete("page_all", 1);
            //Set answer:
            $Api->request->answer_data([
                "page" => $args["name"]
            ]);

        } else {
            $Api->request->update_answer_status(500, "page [{$args["name"]}] is not defined in db.");
        }
        return true;
    },
    working_dir     : dirname(__FILE__).DS."..",
    allow_global    : false,
    allow_external  : true,
    allow_override  : false,
    policy          : $delete_page_policy
));

/*********************************************************************************/
/*****************  change page status *******************************************/
/*********************************************************************************/
$change_status_policy = new Priv\RequiredPrivileges();
$change_status_policy->define(
    new Priv\PrivContent(edit : true)
);
AdminApi::register_endpoint(new ApiEndPoint(
    module      : "pages",
    name        : "change_page_status", 
    params      : [
        "name"      => null
    ],
    filter      : [
        "name"      => Validate::filter("trim")::filter("strchars","A-Z","a-z","0-9","_","-")::create_filter()
    ],
    validation  : [
        "name"      => Validate::condition("optional")::condition("min_length", "2")::create_rule()
    ],
    method : function(AdminApi $Api, array $args, ApiEndPoint $Endpoint) {

        //Get current settings:
        $page = $Api::$db->where("name", $args["name"])->getOne("page_all");
        if (!empty($page)) {
            //Toggle status:
            $Api::$db->where("name", $args["name"])->update("page_all", [
                "status" => $page["status"] === 1 ? 0 : 1
            ], 1);
            //Set answer:
            $Api->request->answer_data([
                "page" => $args["name"]
            ]);
        } else {
            $Api->request->update_answer_status(500, "page [{$args["name"]}] is not defined in db.");
        }
        return true;
    },
    working_dir     : dirname(__FILE__).DS."..",
    allow_global    : false,
    allow_external  : true,
    allow_override  : false,
    policy          : $change_status_policy
));

/*********************************************************************************/
/*****************  change page status *******************************************/
/*********************************************************************************/
$create_page_policy = new Priv\RequiredPrivileges();
$create_page_policy->define(
    new Priv\PrivContent(create : true)
);
AdminApi::register_endpoint(new ApiEndPoint(
    module      : "pages",
    name        : "create_page_entry", 
    params      : [
        "page-name"     => null,
        "page-type"     => null, 
        "page-display"  => null, 
        "page-template" => null
    ],
    filter      : [
        "page-name"     => Validate::filter("type", "string")
                                    ::filter("trim")
                                    ::filter("strchars","A-Z","a-z","0-9","_","-")
                                    ::filter("lowercase")::create_filter(),
        "page-type"     => Validate::filter("type", "string")::filter("trim")::create_filter(),
        "page-display"  => Validate::filter("type", "string")
                                    ::filter("trim")
                                    ::filter("strchars","A-Z","a-z","0-9","_","-")
                                    ::filter("lowercase")::create_filter(),
        "page-template" => Validate::filter("type", "string")::filter("trim")::create_filter()
    ],
    validation  : [
        "page-name"     => Validate::condition("required")::create_rule(), // Will be validate by a nother internal api call.
        "page-type"     => Validate::condition("required")::condition("one_of", "file|2|dynamic|1")::create_rule(), 
        "page-display"  => Validate::condition("optional")::create_rule(), 
        "page-template" => Validate::condition("required")::create_rule()
    ],
    method : function(AdminApi $Api, array $args, ApiEndPoint $Endpoint) {

        /** @var ApiAnswerObj $validateName */
        $validateName = $Api->call(["name" => $args["page-name"]], "pages.page_name_valid")->answer;

        if ($validateName->code === 200 && $validateName->data["valid"]) {
            //Prepare values:
            $name = $validateName->data["name"];
            $type = in_array($args["page-type"], ["1","dynamic"]) ? 1 : 2;
            $bread = ucfirst(empty($args["page-display"]) ? $name : $args["page-display"]);
            //Logic:
            try {
                $Api::$db->insert("page_all", [
                    "name"          => $name,
                    "status"        => 0,
                    "type"          => $type,
                    "template"      => $type === 1 ? $args["page-template"] : null,
                    "page_folder"   => $type === 1 ? DS : DS.trim($args["page-template"], "\\/").DS,
                    "file_name"     => $type === 1 ? "" : sprintf("page.%s.php", trim($args["page-template"], "\\/")),
                    "breadname"     => $bread
                ]);
                $Api->request->answer_data([
                    "name" => $name,
                    "type" => $type,
                    "bread" => $bread
                ]);
            } catch (Exception $e) {
                $Api->request->update_answer_status(500, "Error while saving page - {$e->getMessage()}");
            }
        } else {
            $Api->request->update_answer_status(400, "page name [{$args["page-name"]}] is not valid");
        }
    },
    working_dir     : dirname(__FILE__).DS."..",
    allow_global    : true,
    allow_external  : true,
    allow_override  : false,
    policy          : $create_page_policy
));