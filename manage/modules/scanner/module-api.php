<?php
//Extending the Api of manage:
require_once BSIK_AUTOLOAD;

use \Bsik\Std;
use \Bsik\Api\ApiEndPoint;
use \Bsik\Api\AdminApi;
use \Bsik\Api\ApiAnswerObj;
use \Bsik\Api\Validate;
use Bsik\Privileges as Priv;

/****************************************************************************/
/* COMPONENTS & INCLUDES:   *************************************************/
/****************************************************************************/
// require_once "includes".DS."pages-components.php";
require_once "includes".DS."bookmakers.logos.class.php";

/********************************************************************************/
/*****************  get all bookmaker availbale icons / images  *****************/
/********************************************************************************/
AdminApi::register_endpoint(new ApiEndPoint(
    module      : "scanner",
    name        : "bookmaker_logo_files", 
    params      : [],
    filter      : [],
    validation  : [],
    method : function(AdminApi $Api, array $args, ApiEndPoint $Endpoint) {

        //TODO make this as loaded from settings:
        $logo = new BookmakerLogos(
            AdminApi::$db, 
            Std::$fs::path_to("", ["front", "global", "lib", "img", "bookmakers"])["path"], //$APage->settings["logos-folder"],
            ".logo.png"
        );

        $Api->request->answer_data($logo->list_logo_files());
    },
    working_dir     : dirname(__FILE__).DS."..",
    allow_global    : true,
    allow_external  : true,
    allow_override  : false,
    policy          : null
));
/********************************************************************************/
/*****************  upload a bookmaker logo / icon image  ***********************/
/********************************************************************************/
$bookmaker_logo_policy = new Priv\RequiredPrivileges();
$bookmaker_logo_policy->define(
    new Priv\PrivContent(upload: true)
);
AdminApi::register_endpoint(new ApiEndPoint(
    module      : "scanner",
    name        : "upload_bookmaker_logo", 
    params      : [
        "filepond" => "Files::filepond", //TODO: implement this to support getting files: or not maybe a simple files object is enough
    ],
    filter      : [],
    validation  : [],
    method : function(AdminApi $Api, array $args, ApiEndPoint $Endpoint) {

        $validations = [
            'extension' => [ // maximum of 1mb
                "is" => [".png"]
            ],
            'size' => [ 
                'max' => 0.25
            ],
            'custom' => function($file) { 
                if (!std::$str::ends_with($file->name, ".logo.png")) {
                    return "File name must end with '.logo.png'";
                }
                return true;
            }
        ];

        $upload = new \Bsik\Api\FileUpload\Upload($_FILES["filepond"], $validations);
        $errors = [];
        $paths  = [];
        foreach ($upload->files as $file) {
            if ($file->validate()) {

                
                //TODO: this path should be loaded from module settings:
                $save_in = Std::$fs::path_to("root", ["front", "global", "lib", "img", "bookmakers"]);

                if (Std::$fs::path_exists($save_in["path"], $file->name)) {
                    $errors[] = "Error moving file allready exists in directory";
                } else {
                    if (!$file->put($save_in["path"], $file->name)) {
                        $errors[] = "Error moving file unknown put error";
                    } else {
                        $save_in["file"] = $file->name;
                        $paths[] = $save_in;
                    }
                }
            } else {
                $errors[] = $file->get_error();
            }
        }
        //The answer - exposes the uploaded paths
        if (!empty($errors)) {
            $Api->request->add_errors($errors);
            $Api->request->update_answer_status(415);
        } else {
            $Api->request->answer_data([
                "test"      => $_FILES,
                "errors"    => $errors,
                "paths"     => $paths,
            ]);
        }
    },
    working_dir     : dirname(__FILE__).DS."..",
    allow_global    : false,
    allow_external  : true,
    allow_override  : false,
    policy          : $bookmaker_logo_policy
));


/********************************************************************************/
/*****************  Create and Add to to db a new bookmaker     *****************/
/********************************************************************************/
$bookmaker_create_policy = new Priv\RequiredPrivileges();
$bookmaker_create_policy->define(
    new Priv\PrivContent(create: true)
);
AdminApi::register_endpoint(new ApiEndPoint(
    module      : "scanner",
    name        : "register_new_bookmaker", 
    params      : [
        "book_name"   => null, 
        "book_text"   => null, 
        "book_logo"   => '#',
        "book_active" => 0,
    ],
    filter      : [
        "book_name"   => Validate::filter("type", "string")::filter("trim")::filter("strchars","A-Z","a-z","0-9","_","-")::create_filter(),
        "book_text"   => Validate::filter("type", "string")::filter("trim")::filter("strchars","A-Z","a-z","0-9","_","-")::create_filter(),
        "book_logo"   => Validate::filter("type", "string")::filter("trim")::filter("sanitize", FILTER_SANITIZE_URL)::create_filter(),
        "book_active" => Validate::filter("type", "number")::create_filter(),
    ],
    validation  : [
        "book_name"     => Validate::condition("required")::condition("min_length", "2")::create_rule(),
        "book_text"     => Validate::condition("required")::condition("min_length", "2")::create_rule(),
        "book_logo"     => Validate::condition("required")::condition("ends_with", '.logo.png')::create_rule(),
        "book_active"   => Validate::condition("optional")::condition("range", 0, 1)::create_rule(),
    ],
    method : function(AdminApi $Api, array $args, ApiEndPoint $Endpoint) {

        try {
            //Save to db:
            $Api::$db->insert("scanner_bookmakers",[
                "name"   => strtolower($args["book_name"]),
                "text"   => $args["book_text"],
                "logo"   => $args["book_logo"],
                "active" => $args["book_active"]
            ]);
            //Get last bookmaker id:
            $Api->request->answer_data([
                "bookmaker" => $Api::$db->getInsertId()
            ]);
        } catch (\Exception $e) {
            $Endpoint->log_error(
                message : "Error while saving new bookmaker to database.", 
                context : ["mysql_error" => $e->getMessage()]
            );
            $Api->request->update_answer_status(500, "Error while saving to database");
        }
    },
    working_dir     : dirname(__FILE__).DS."..",
    allow_global    : false,
    allow_external  : true,
    allow_override  : false,
    allow_front     : false,
    policy          : $bookmaker_create_policy
));

/********************************************************************************/
/*****************  Delete bookmaker     ****************************************/
/********************************************************************************/
$bookmaker_delete_policy = new Priv\RequiredPrivileges();
$bookmaker_delete_policy->define(
    new Priv\PrivContent(delete: true)
);
AdminApi::register_endpoint(new ApiEndPoint(
    module      : "scanner",
    name        : "delete_bookmaker", 
    params      : [
        "bookmaker"   => null,
    ],
    filter      : [
        "bookmaker"   => Validate::filter("type", "number")::create_filter()
    ],
    validation  : [
        "bookmaker"     => Validate::condition("required")::condition("min", 1)::create_rule(),
    ],
    method : function(AdminApi $Api, array $args, ApiEndPoint $Endpoint) {

        try {
            //Save to db:
            $Api::$db->where("id", $args["bookmaker"])->delete("scanner_bookmakers", 1);
            //Get last bookmaker id:
            $Api->request->answer_data([
                "bookmaker" => $args["bookmaker"]
            ]);
        } catch (\Exception $e) {
            $Endpoint->log_error(
                message : "Error while deleting bookmaker.", 
                context : ["mysql_error" => $e->getMessage()]
            );
            $Api->request->update_answer_status(500, "Error while saving to database");
        }
    },
    working_dir     : dirname(__FILE__).DS."..",
    allow_global    : false,
    allow_external  : true,
    allow_override  : false,
    allow_front     : false,
    policy          : $bookmaker_delete_policy
));



/********************************************************************************/
/*****************  Delete bookmaker     ****************************************/
/********************************************************************************/
$bookmaker_activation_policy = new Priv\RequiredPrivileges();
$bookmaker_activation_policy->define(
    new Priv\PrivContent(edit: true)
);
AdminApi::register_endpoint(new ApiEndPoint(
    module      : "scanner",
    name        : "change_bookmaker_activation", 
    params      : [
        "bookmaker"   => null,
    ],
    filter      : [
        "bookmaker"   => Validate::filter("type", "number")::create_filter()
    ],
    validation  : [
        "bookmaker"     => Validate::condition("required")::condition("min", 1)::create_rule(),
    ],
    method : function(AdminApi $Api, array $args, ApiEndPoint $Endpoint) {

        //Get current bookmaker activation:
        $book = $Api::$db->where("id", $args["bookmaker"])->getOne("scanner_bookmakers");
        if (!empty($book)) {
            try {
                //Toggle status:
                $Api::$db->where("id", $args["bookmaker"])->update("scanner_bookmakers", [
                        "active" => $book["active"] === 1 ? 0 : 1
                ], 1);
                //Set answer:
                $Api->request->answer_data([ "bookmaker" => $args["bookmaker"] ]);
            } catch (\Exception $e) {
                $Endpoint->log_error(
                    message : "Error while activation change bookmaker.", 
                    context : ["mysql_error" => $e->getMessage()]
                );
                $Api->request->update_answer_status(500, "Error while activation change bookmaker in database");
            }
        } else {
            $Api->request->update_answer_status(500, "Bookmaker [{$args["bookmaker"]}] is not registered in db.");
        }
        return true;
    },
    working_dir     : dirname(__FILE__).DS."..",
    allow_global    : true,
    allow_external  : true,
    allow_override  : false,
    allow_front     : false,
    policy          : $bookmaker_activation_policy
));
