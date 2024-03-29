<?php


require_once BSIK_AUTOLOAD;

use \Bsik\Api\FrontApi;
use \Bsik\Api\ApiEndPoint;
use Bsik\Api\BsikApi;
use \Bsik\Api\Validate;

/****************************************************************************/
/**********************  CORE ADMIN API METHODS  ****************************/
/****************************************************************************/

/******************************  Get from tabels  ***************************/
FrontApi::register_endpoint(new ApiEndPoint(
    module      : "home",
    name        : "hi", 
    params      : [],
    filter      : [],
    validation  : [],
    //The method to execute -> has Access to BsikApi
    method : function(FrontApi $Api, array $args, ApiEndPoint $Endpoint) {
        $Api->request->answer_data([
            "front" => "hello from home"
        ]);
        return true;
    },
    working_dir     : dirname(__FILE__),
    allow_global    : true,
    allow_external  : true,
    allow_override  : false
));