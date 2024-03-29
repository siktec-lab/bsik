<?php
//Extending the Api of manage:

//For intellisense support:
require_once BSIK_AUTOLOAD;

use \Bsik\Std;
use \Bsik\Api\Validate;
use \Bsik\Render\Template;
use \Bsik\Api\AdminApi;
use \Bsik\Api\ApiEndPoint;
use \Bsik\Privileges as Priv;

/****************************************************************************/
/**********************  privileges policies  **********************************/
/****************************************************************************/
$my_policy = new Priv\RequiredPrivileges();
$my_policy->define(
    new Priv\PrivUsers(
        interact : false,
    )
);
/****************************************************************************/
/**********************  Greeting Message  **********************************/
/****************************************************************************/

AdminApi::register_endpoint(new ApiEndPoint(
    module          : "dashboard",
    name            : "sayhello", 
    params          : [ 
        "name" => null
    ],
    filter          : [],
    validation      : [],
    //The method to execute -> has Access to BsikApi
    method          : function(AdminApi $Api, array $args, ApiEndPoint $Endpoint) {


        
        $engine = new Template(
            cache : Std::$fs::path($Endpoint->working_dir, "templates", "cache")
        );
        $engine->addFolders([Std::$fs::path($Endpoint->working_dir, "templates")]);

        $ret_hello = $engine->render("sayhello", $args);
        $ret_bye = $Api->call($args, "core.saybye");

        


        $Api->request->update_answer_status(200);
        $Api->request->answer_data([
            "message1" => $ret_hello,
            "message2" => $ret_bye->answer_data()["message"] ?? "cant get message",
        ]);

        return true;
    },
    working_dir     : dirname(__FILE__),
    allow_global   : true,
    allow_external : true,
    allow_override : false,
    allow_front    : true,
    policy         : $my_policy, 

));

