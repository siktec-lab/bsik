<?php
//Extending the Api of manage:

//For intellisense support:
require_once PLAT_PATH_CORE.DS.'BsikApi.class.php';
if (!isset($AApi)) { $AApi = new BsikApi(Base::get_session("csrftoken")); }

/****************************************************************************/
/**********************  GET SCRIPT STATUS       ****************************/
/****************************************************************************/
// $AApi->register_endpoint(new BsikApiEndPoint(
//     $name = "install_module_file", 
//     $params = [],
//     $filters = [],
//     $validation = [],
//     //The method to execute -> has Access to BsikApi
//     function(BsikApi $Api, array $args) {

//         //Perform file upload:
//         [$status, $ret] = $Api->file(
//             "module_file", 
//             PLAT_PATH_MANAGE.DS."uploaded", 
//             $Api::$std::fs_format_size_to(10, "MB", "B"),
//             ["txt"]
//         );

//         //Set response:
//         if (!$status) {
//             $Api->update_answer_status(403, $ret);
//         } else {
//             $Api->update_answer_status(200);
//             $Api->request->answer->data = [
//                 "uploaded" => $ret
//             ];
//         }
//         return true;
//     }
// ));

/****************************************************************************/
/**********************  EXECUTE SYNC OPERATION  ****************************/
/****************************************************************************/

// $AApi->register_endpoint(new BsikApiEndPoint(
//     $name = "run_listing_scrape", 
//     $params = [ // Defines the expected params with there defaults.
//         "op"    => "updatenew", //null indicates no default.
//         "limit" => 5
//     ],
//     $filters = [ // Defines filters to apply -> this will modify the params.
//         "op"    => BsikValidate::add_procedure("trim")::add_procedure("strchars","A-Z","a-z","0-9","_")::create_filter(),
//         "limit" => BsikValidate::add_procedure("type", "number")::create_filter()
//     ],
//     $validation = [ // Defines Validation rules of this endpoint.
//         "op"    => BsikValidate::add_cond("required")::add_cond("type","string")::create_rule(),
//         "limit" => BsikValidate::add_cond("type","integer")::create_rule()
//     ],
//     //The method to execute -> has Access to BsikApi
//     function(BsikApi $Api, array $args) {
//         //Script definition:
//         //$php = "C:\\xampp\\php\\php.exe";
//         $php = "php";
//         $script = "D:\\server\\evollcdash\\manage\\core\\operations\\op.synccentric.php";
//         $args_str = "op={$args['op']} limit={$args['limit']}";
//         //Execute based on platform:
//         if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
//             //Execute background windows:
//             pclose(popen("start /B \"bla\" {$php} {$script} {$args_str}", "r"));
//         } else {
//             shell_exec("{$php} {$script} {$args_str} >/dev/null &");
//         }
//         //Set response:
//         $Api->request->answer->data = [
//             "op"    => $args['op'],
//             "limit" => $args['limit']
//         ];
//         $Api->update_answer_status(200);
//         return true;
//     }
// ));

// /****************************************************************************/
// /**********************  EXECUTE IMPORT OPERATION  **************************/
// /****************************************************************************/

// $AApi->register_endpoint(new BsikApiEndPoint(
//     $name = "run_listing_import", 
//     $params = [ // Defines the expected params with there defaults.
//         "limit" => 100
//     ],
//     $filters = [ // Defines filters to apply -> this will modify the params.
//         "limit" => BsikValidate::add_procedure("type", "number")::create_filter()
//     ],
//     $validation = [ // Defines Validation rules of this endpoint.
//         "limit" => BsikValidate::add_cond("type","integer")::create_rule()
//     ],
//     //The method to execute -> has Access to BsikApi
//     function(BsikApi $Api, array $args) {
//         //Script definition:
//         $php = "php";
//         $script = "D:\\server\\evollcdash\\manage\\core\\operations\\op.import.listings.php";
//         $args_str = "limit={$args['limit']}";
//         //Execute based on platform:
//         if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
//             //Execute background windows:
//             pclose(popen("start /B \"bla\" {$php} {$script} {$args_str}", "r"));
//         } else {
//             shell_exec("{$php} {$script} {$args_str} >/dev/null &");
//         }
//         //Set response:
//         $Api->request->answer->data = [
//             "limit" => $args['limit']
//         ];
//         $Api->update_answer_status(200);
//         return true;
//     }
// ));

// /****************************************************************************/
// /**********************  EXECUTE IMPORT OPERATION  **************************/
// /****************************************************************************/

// $AApi->register_endpoint(new BsikApiEndPoint(
//     $name = "add_upc_publish_que", 
//     $params = [ // Defines the expected params with there defaults.
//         "upc"       => "",
//         "state"     => "publish"
//     ],
//     $filters = [ // Defines filters to apply -> this will modify the params.
//         "upc"   => BsikValidate::add_procedure("type", "string")::add_procedure("trim")::add_procedure("strchars","0-9")::create_filter(),
//         "state" => BsikValidate::add_procedure("type", "string")::add_procedure("trim")::create_filter()
//     ],
//     $validation = [ // Defines Validation rules of this endpoint.
//         "upc"   => BsikValidate::add_cond("type","string")::add_cond("length", 12, 12)::create_rule(),
//         "state" => BsikValidate::add_cond("type","string")::create_rule()
//     ],
//     //The method to execute -> has Access to BsikApi
//     function(BsikApi $Api, array $args) {
//         //Script definition:
//         $state = $args["state"] === "draft" ? 1 : 2;
//         $upc = $args["upc"];
//         $result = "added";
//         //Check state:
//         $current = $Api::$db->where("upc", $upc)->getOne("listings");
//         if ($current["published"] === 2) {
//             $result = "published";
//         } elseif ($current["status"] === 1) {
//             $result = "new";
//         } elseif ($current["status"] === 3) {
//             $result = "ignored";
//         } elseif ($Api::$db->where("publish_upc", $upc)->has("listings_publish_que")) {
//             $result = "que";
//         } else {
//             $Api::$db->insert("listings_publish_que", [
//                 "publish_upc"       => $upc,
//                 "added"             => $Api::$db->now(),
//                 "status"            => $state,
//                 "shipping_template" => null, /* SH: added - 2021-09-13 => Add automatic template selection based on the freights */
//             ]);
//             $Api::$db->where("upc", $upc)->update("listings", ["published" => 3], 1);
//         }
//         //Set response:
//         $Api->request->answer->data = [
//             "result" => $result,
//             "status" => $current["status"]
//         ];
//         $Api->update_answer_status(200);
//         return true;
//     }
// ));

