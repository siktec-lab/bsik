<?php
/******************************************************************************/
// Created by: SIKTEC.
// Release Version : 1.0.0
// Creation Date: 2021-03-16
// Copyright 2021, SIKTEC.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.0:
    ->initial
*******************************************************************************/
require_once PLAT_PATH_CORE.DS."Base.class.php";
require_once PLAT_PATH_CORE.DS."BsikValidate.class.php";

class BsikApiEndPoint extends Base {

    public string $name = "";
    public array $params;
    public array $filters;
    public array $conditions;
    public $execute;    
    /**
     * __construct
     *
     * @param  string $_name    - the unique endpoint name.
     * @param  mixed $_required - an Array with expected $args defined.
     * @param  mixed $_method   - The closure to execute Arguments must be (AdminApi $Api, array $args)
     * @return void
     */
    public function __construct(
        string $_name,              //The Api endpoint name / the method name
        array $_params,             //Expected params with there defaults
        array $_filter,             //Filter procedures to apply
        array $_validation,         //Validation conditions to apply       
        $_method) {
        $this->name = self::$std::str_filter_string($_name, ["A-Z", "a-z", "0-9", " ", "_"]);
        $this->params = $_params;
        $this->filters = $_filter;
        $this->conditions = $_validation;
        $this->execute = $_method;      // The operation closure
    }

}

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class BsikApi extends Base {

    //Logger:
    public Logger $logger;
    public static $user_string;
    //Data:
    private $csrf  = "";    //System token supplied
    private $debug = false; //debug mode adds data to the result
    private $debug_data = [];

    //Containers:
    public $request; // Implement an object defining the result returned
    public $endpoints; // A class that holds all implemented end points

    public $codes = [
        200 => 'OK',
        201 => 'Created',                       // POST/PUT resulted in a new resource, MUST include Location header
        202 => 'Accepted',                      // request accepted for processing but not yet completed, might be disallowed later
        204 => 'No Content',                    // DELETE/PUT fulfilled, MUST NOT include message-body
        304 => 'Not Modified',                  // If-Modified-Since, MUST include Date header
        400 => 'Bad Request',                   // malformed syntax
        403 => 'Forbidden',                     // unauthorized
        404 => 'Not Found',                     // request URI does not exist
        405 => 'Method Not Allowed',            // HTTP method unavailable for URI, MUST include Allow header
        415 => 'Unsupported Media Type',        // unacceptable request payload format for resource and/or method
        426 => 'Upgrade Required',
        451 => 'Unavailable For Legal Reasons', // REDACTED
        500 => 'Internal Server Error',         // all other errors
        501 => 'Not Implemented'                // (currently) unsupported request method
    ];

    public function __construct(
        string $csrf,
        bool $debug_mode = false, 
        string $logger_channel = "bsikapi-general",
        string $logger_stream = PLAT_LOG_DIRECTORY,
        string $user_str = ""
    ) {

        //define headers:
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: *");
        header("Content-Type: application/json");

        //Set Logger:
        $this->logger = new Logger($logger_channel);
        $this->logger->pushHandler(new StreamHandler($logger_stream.$logger_channel.".log"));
        $this->logger->pushProcessor(function ($record) {
            $record['extra']['admin'] = BsikApi::$user_string;
            return $record;
        });
        $this->set_user_string($user_str);

        //Define:
        $this->request = (object)[
            "token"  => "",
            "type"   => "",
            "args"   => [],
            "answer" => (object)[
                "code"      => 0,  // Defined http codes number
                "message"   => "", // Defined http code message
                "errors"    => [], // Holds errors added by the system
                "debug"     => [], // Debug information
                "data"      => []  // Result data
            ]
        ];
        $this->endpoints =  new class {};
        $this->csrf = $csrf;
        $this->debug = $debug_mode;

    }
    public function register_debug(string $name, $entry) {
        if ($this->debug) {
            $this->debug_data[$name] = $entry;
        }
    }
    public function set_user_string(string $str) {
        self::$user_string = empty(trim($str)) ? "unknown" : trim($str);
    }
    public function get_user(string $part = "str") {
        switch ($part) {
            case "id": return explode(":", self::$user_string)[0] ?? null;
            case "email": return explode(":", self::$user_string)[1] ?? null;
        }
        return self::$user_string;
    }
    public function register_endpoint(BsikApiEndPoint $end_point) : bool{
        if (property_exists($this->endpoints, $end_point->name)) return false;
        $this->endpoints->{$end_point->name} = $end_point;
        return true;
    }
    
    /**
     * update_answer_status - changes the code + adds a row to errors
     *
     * @param  int $code - the http code - if 0 ignored.
     * @param  string $error - pushes and error string - if empty ignored.
     * @return void
     */
    public function update_answer_status(int $code = 0, string $error = "") {
        if (isset($this->codes[$code])) {
            $this->request->answer->code = $code;
            $this->request->answer->message = $this->codes[$code];
        }
        if (!empty($error)) {
            $this->request->answer->errors[] = $error;
        }
    }
    /* SH: added - 2021-04-03 => make this documented that those request entries are reserved */
    public function parse_request(array $input, array $ignore = ["type","module", "which", "request_type","request_token"]) {
        $this->request->token = $input["request_token"] ?? "";
        $this->request->type  = $input["request_type"] ?? "";
        $this->request->args  = self::$std::arr_filter_out($input, $ignore);
        //Validate origin token:
        if (empty($this->csrf) || empty($this->request->token) || $this->csrf !== $this->request->token) {
            $this->update_answer_status(403, "Token is not set or invalid");
            return false;
        }
        //Validate registered endpoint:
        if (empty($this->request->type) || !property_exists($this->endpoints, $this->request->type)) {
            $this->update_answer_status(501, "Requested api method is not supported");
            $this->logger->notice("Received undefined Api request.", [$this->request->type]);
            return false;
        }
        return true;
    }

    // public string $name = "";
    // public array $params;
    // public array $filters;
    // public array $conditions;
    // public $execute;  
    private function prepare_endpoint_args(array $raw_args, string $endpoint) : array {

        $params = $this->endpoints->{$endpoint}->params;
        $filters = $this->endpoints->{$endpoint}->filters;
        //Get defined or null:
        $defined_args = self::$std::arr_get_from($raw_args, array_keys($params), null);
        //Set defaults on null or empty string:
        array_walk($defined_args, 
            fn(&$el, $k) => $el = (is_null($el) || $el == "" ? $params[$k] : $el)
        );
        //Register debugging:
        $this->register_debug("request-filters", $filters);
        $this->register_debug("request-args", $defined_args);
        //Apply normalization procedures:
        foreach ($defined_args as $arg_name => $arg) {
            try {
                $defined_args[$arg_name] = BsikValidate::normalize($arg, $filters[$arg_name] ?? "none");
            } catch (Throwable $t) {
                $this->register_debug("error-arg-normalize-".$arg_name,$t->getMessage());
                $this->logger->notice($t->getMessage(), [$filters[$arg_name] ?? "none"]);
            }
        }
        $this->register_debug("final-args", $defined_args);
        return $defined_args;
    }
    public function execute(array $args = [], string $endpoint = "") {
        //Request defined:
        $endpoint = empty($endpoint) ?  $this->request->type : $endpoint;
        $raw_args = empty($args) ? $this->request->args : $args;
        $this->register_debug("raw-args", $raw_args);
        //If Registered than execute:
        if (property_exists($this->endpoints, $endpoint)) {
            //Check required are defined and valid:
            $filtered_args = $this->prepare_endpoint_args($raw_args, $endpoint);
            $messages = [];
            $valid = true;
            //Validate inputs:
            $this->register_debug("request-validation-rules", $this->endpoints->{$endpoint}->conditions);
            foreach($this->endpoints->{$endpoint}->conditions as $param => $rule) {
                $messages[$param] = [];
                try {
                    if (!BsikValidate::validate($filtered_args[$param], $rule, $messages[$param])) {
                        $valid = false;
                    }
                } catch (Throwable $t) {
                    $this->register_debug("error-arg-validation-".$param,$t->getMessage());
                    $this->logger->notice($t->getMessage(), $rule);
                }
            }
            if (!$valid) {
                $this->request->answer->data = $messages;
                $this->update_answer_status(400, "Request params are not valid");
                return false;
            }
            //Execute:
            return ($this->endpoints->{$endpoint}->execute)($this, $filtered_args);
        }
        $this->update_answer_status(501, "Requested api method is not supported");
        return false;
    }

    public function answer(bool $print = true) {
        if ($this->debug)
            $this->request->answer->debug = $this->debug_data;

        if ($this->request->answer->code === 0)
            $this->update_answer_status(200);
        http_response_code($this->request->answer->code);
        $response = json_encode($this->request->answer);
        if ($print) 
            print $response;
        return $response;
    }
    public function file(string $name, string $to, int $max_bytes = -1, array $mime = []) : array {
        // Undefined | Multiple Files | $_FILES Corruption Attack
        // If this request falls under any of them, treat it invalid.
        if (
            !isset($_FILES[$name]) ||
            !isset($_FILES[$name]['error']) ||
            is_array($_FILES[$name]['error'])
        ) {
            return [false, 'invalid parameters'];
        }
        // Check $_FILES['file']['error'] value.
        switch ($_FILES[$name]['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return [false, 'no file sent'];
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return [false, 'form exceeded filesize limit'];
            default:
                return [false, 'unknown errors'];
        }
        // You should also check filesize here.
        if ($max_bytes > -1 && $_FILES[$name]['size'] > $max_bytes) {
            return [false, 'exceeded filesize limit'];
        }
        // DO NOT TRUST $_FILES['file']['mime'] VALUE !!
        // Check MIME Type by yourself.
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $got_mime = $finfo->file($_FILES[$name]['tmp_name']);
        $allowed_mime = self::$std::fs_get_mimetypes(...$mime);
        $ext = array_search($got_mime, $allowed_mime, true);
        if (!empty($mime) && !is_string($ext) ) {
            return [false, 'invalid file format'];
        }

        // You should name it uniquely.
        // DO NOT USE $_FILES['file']['name'] WITHOUT ANY VALIDATION !!
        // On this example, obtain safe unique name from its binary data.
        $to = sprintf('%s/%s.%s', 
            trim($to, "/\\"),
            self::$std::str_filter_string(
                pathinfo($_FILES[$name]['name'], PATHINFO_FILENAME), 
                ["A-Z","a-z","0-9","_","."]
            ),
            $ext
        );
        try {
            if (!move_uploaded_file(
                $_FILES[$name]['tmp_name'],
                $to
            )) {
                return [false, 'failed to move file'];
            }
        } catch (Exception $e) {
            return [false, 'failed to move file'];
        }
        return [true, $to];
    }
    
}