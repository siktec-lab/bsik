<?php
//Extending the Api of manage:
require_once PLAT_PATH_AUTOLOAD;

use \Bsik\Std;
use \Bsik\Api\ApiEndPoint;
use \Bsik\Api\AdminApi;
use \Bsik\Api\Validate;
use Bsik\Privileges as Priv;

/********************************************************************************/
/*****************  get page names  *********************************************/
/********************************************************************************/
$get_templates_policy = new Priv\RequiredPrivileges();
$get_templates_policy->define(
    new Priv\PrivContent(view: true)
);
AdminApi::register_endpoint(new ApiEndPoint(
    module      : "pages",
    name        : "get_page_templates", 
    params      : [
        "template_type"  => "file"
    ],
    filter      : [
        "template_type"  => Validate::filter("type", "string")::filter("trim")::create_filter()
    ],
    validation  : [
        "template_type"  => Validate::condition("required")::condition("one_of", "file|2|dynamic|1")::create_rule()
    ],
    method : function(AdminApi $Api, array $args, ApiEndPoint $Endpoint) {
        
        switch ($args["template_type"]) {
            case "2":
            case "file": {
                $folders = [];
                foreach (Std::$fs::list_folders_in(PLAT_FRONT_PAGES) as $folder) {
                    $folders[] = [ "id" => $folder, "name" => $folder];
                }
                $Api->request->answer_data([
                    "templates" => $folders,
                    "type"      => $args["template_type"]
                ]);
            } break;
            case "1":
            case "dynamic": {
                $Api->request->answer_data([
                    "templates" => $Api::$db->get("page_templates", null, "id, name"),
                    "type"      => $args["template_type"]
                ]);
            } break;
        }
    },
    working_dir     : dirname(__FILE__).DS."..",
    allow_global    : true,
    allow_external  : true,
    allow_override  : false,
    policy          : $get_templates_policy
));
