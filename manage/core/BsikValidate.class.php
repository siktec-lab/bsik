<?php
/******************************************************************************/
// Created by: SIKTEC.
// Release Version : 1.0.1
// Creation Date: 2021-03-18
// Copyright 2021, SIKTEC.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.1:
    ->initial


Add custom validators Example:

class MyValidators {
    public static function start_with(string $input, string $letter = "") {
        if ($input[0] !== $letter[0] ?? "") {
            return "@input@ has to start with the letter {$letter[0]}";
        }
        return true;
    }
}
BsikValidate::add_validator("startWith", "MyValidators::start_with");

*******************************************************************************/

class BsikValidate {

    //Use encoding:
    public static $encoding = 'UTF-8';

    //Defined filters:
    private static $filters = [
        "none"           => __CLASS__."::filter_none",
        "trim"           => __CLASS__."::filter_trim",
        "type"           => __CLASS__."::filter_conv_type",      // "string", "array", "number", "boolean", "float"
        "strchars"       => __CLASS__."::filter_string"
    ];

    //Defined rules and there name:
    private static $validators = [
        "required"      => __CLASS__."::rule_required",
        "type"          => __CLASS__."::rule_type",                 // "boolean","integer","double","string","array","object","NULL"
        "minlength"     => __CLASS__."::rule_min_length",
        "maxlength"     => __CLASS__."::rule_max_length",
        "length"        => __CLASS__."::rule_length",
        "max"           => __CLASS__."::rule_max",
        "min"           => __CLASS__."::rule_min",
        "regex"         => "",
        "custom"        => "",
        "none"          => ""
    ];
    
    //A buffer that holds conditions until create rule is called:
    private static $filter_buffer = [];
    private static $rule_buffer = [];

    //Rule string symbols:
    private static $sym_chain       = "->";
    private static $sym_args        = "::";
    private static $sym_args_glue   = ",,";
    
    /**
     * register_condition - adds a validate function to be use WARNING can overwrite the current defined.
     * 
     * @param  mixed $name
     * @param  mixed $call_path
     * @return void
     */
    final public static function add_filter(string $name, string $call_path) : void {
        self::$filters[$name] = $call_path;
    }
    final public static function normalize(mixed $input, string $procedures) {
        $parsed_procedures = self::parse_rule($procedures);
        foreach ($parsed_procedures as $procedure) {
            if (!isset(self::$filters[$procedure["func"]]) || !is_callable(self::$filters[$procedure["func"]])) 
                throw new Exception("Trying to filter with unknown procedure [".$procedure["func"]."]", E_NOTICE);
            $input = call_user_func_array(self::$filters[$procedure["func"]], [$input, ...$procedure["args"]]);
        }
        return $input;
    }

    /**
     * register_condition - adds a validate function to be use WARNING can overwrite the current defined.
     * 
     * @param  mixed $name
     * @param  mixed $call_path
     * @return void
     */
    final public static function add_validator(string $name, string $call_path) : void {
        self::$validators[$name] = $call_path;
    }
    final public static function validate(mixed $input, string $rule, array &$messages) : bool {
        $parsed_rule = self::parse_rule($rule);
        $valid = true;
        foreach ($parsed_rule as $condition) {
            if (!isset(self::$validators[$condition["func"]]) || !is_callable(self::$validators[$condition["func"]])) 
                throw new Exception("Trying to validate with unknown func [".$condition["func"]."]", E_NOTICE);
            $test = call_user_func_array(self::$validators[$condition["func"]], [$input, ...$condition["args"]]);
            if ($test !== true) {
                $valid = false;
                $test = !is_array($test) ? [$test] : $test;
                $messages[$condition["func"]] = $test;
            }
        }
        return $valid;
    }
    
    /**
     * add_cond - add a condition to the rule that is being built
     *
     * @param  string $cond - the specific condition name
     * @param  array $args - packed arguments that the rule should use.
     * @return self
     */
    final public static function add_procedure(string $procedure, ...$args) {
        self::$filter_buffer[] = trim($procedure).(!empty($args) ? 
            self::$sym_args.implode(self::$sym_args_glue, $args) : 
            "");
        return __CLASS__;
    }

    /**
     * add_cond - add a condition to the rule that is being built
     *
     * @param  string $cond - the specific condition name
     * @param  array $args - packed arguments that the rule should use.
     * @return self
     */
    final public static function add_cond(string $cond, ...$args) {
        self::$rule_buffer[] = trim($cond).(!empty($args) ? 
            self::$sym_args.implode(self::$sym_args_glue, $args) : 
            "");
        return __CLASS__;
    }
    
    /**
     * create_rule - packs all the conditions and return the rule definition string
     *
     * @return string
     */
    final public static function create_filter() : string {
        $ret = implode(self::$sym_chain, self::$filter_buffer);
        self::$filter_buffer = [];
        return $ret;
    }   

    /**
     * create_rule - packs all the conditions and return the rule definition string
     *
     * @return string
     */
    final public static function create_rule() : string {
        $ret = implode(self::$sym_chain, self::$rule_buffer);
        self::$rule_buffer = [];
        return $ret;
    }    
    /**
     * parse_rule - parse a rule definition string to its parts
     *
     * @param string $rule - the rule definition string (generated from create_rule)
     * @return array - ["func" => "{condition name}", "args" => [ ... ]]
     */
    private static function parse_rule(string $rule) : array {
        $parts = explode(self::$sym_chain, $rule);
        $conditions = [];
        foreach ($parts as $part) {
            $definition = explode(self::$sym_args, $part);
            $args       = isset($definition[1]) ? explode(self::$sym_args_glue, $definition[1]) : [];
            $conditions[] = [
                "func" => $definition[0],
                "args" => $args
            ];
        }
        return $conditions;
    }
/******************************  Filter procedures  *****************************/
    private static function filter_none(mixed $input) {
        return $input;
    }
    private static function filter_trim(mixed $input) {
        //procedure
        if (is_array($input)) {
            return array_map(fn($el) => (is_string($el) ? trim($el) : $el), $input);
        }
        return is_string($input) ? trim($input) : $input;
    }
    private static function filter_conv_type(mixed $input, string $type = "string") {
        //"string", "array", "number", "boolean"
        switch ($type) {
            case "string" : {
                if (is_array($input)) {
                    $input = implode($input);
                } else {
                    $input = "".$input;
                }
            } break;
            case "number" : {
                if (is_numeric($input)) {
                    $input = intval($input);
                } else {
                    $input = 0;
                }
            } break;
            case "float" : {
                if (is_numeric($input)) {
                    $input = floatval($input);
                } else {
                    $input = 0.0;
                }
            } break;
            case "boolean" : {
                $input = $input ? true : false;
            } break;
            case "array" : {
                if (is_string($input)) {
                    $input = explode(',', $input);
                }
                if (!is_array($input)) {
                    $input = [];
                }
            } break;
        }
        return $input;
    }
    private static function filter_string(mixed $input, ...$allowed) {
        //procedure
        if (!is_string($input) && !is_array($input)) 
            return $input;
        $regex = is_string($allowed) ? sprintf('/[^%s]/', $allowed) : sprintf('/[^%s]/', implode($allowed));
        return preg_replace($regex, '', $input);
    }
/******************************  Validator conditions  *****************************/
    private static function rule_required(mixed $input) {
        //Conditions:
        if ($input === null || $input === "") {
            return "@input@ is required";
        }
        return true;
    }
    private static function rule_type(mixed $input, string $type = 'string') {
        $input_type = gettype($input);
        if (gettype($input) !== strtolower($type)) {
            return "@input@ is not of type '{$type}' seen '{$input_type}'";
        }
        return true;
    }
    private static function rule_min_length(string $input, string $min = '1') {
        //Handle inputs:
        $length = mb_strlen($input, self::$encoding);
        $parsed_min = (int)$min;
        //Conditions:
        if ($length < $parsed_min) {
            return "@input@ should be at least - {$min} characters long";
        }
        return true;
    }
    private static function rule_max_length(string $input, string $max = '1') {
        //Handle inputs:
        $length = mb_strlen($input, self::$encoding);
        $parsed_max = (int)$max;
        //Conditions:
        if ($length > $parsed_max) {
            return "@input@ should be at least - {$max} characters long";
        }
        return true;
    }
    private static function rule_length(string $input, string $min = '1', string $max = '1') {
        //Handle inputs:
        $length = mb_strlen($input, self::$encoding);
        $parsed_min = (int)$min;
        $parsed_max = (int)$max;
        //Conditions:
        if ($length < $parsed_min || $length > $parsed_max) {
            return "@input@ should be at least {$min} and maximum {$max} characters long";
        }
        return true;
    }
    private static function rule_min(string $input, string $min = '0') {
        //Handle inputs:
        $input = +$input;
        $min    = +$min;
        //Conditions:
        if ($input < $min) {
            return "@input@ should be greater or equal to - {$min}";
        }
        return true;
    }
    private static function rule_max(string $input, string $max = '0') {
        //Handle inputs:
        $input = +$input;
        $max    = +$max;
        //Conditions:
        if ($input > $max) {
            return "@input@ should be smaller or equal to - {$max}";
        }
        return true;
    }
}
/*
function _process_post($data)
    {
        $name = $data['name'];
        $post = $data['post'];
        $label = $data['label'];
        $rule = $data['rule'];

        # allow HTML
        if ($rule == 'allow_html') {
            $allow_html = true;
        } else {
            $allow_html = false;
        }

        if ($post != null) {
            # match one field's contents to another
            if (mb_substr($rule, 0, 7) == 'matches') {

                preg_match_all("/\[(.*?)\]/", $rule, $matches);
                $match_field = $matches[1][0];

                if ($post != $_POST[$match_field])
                {
                    if($this->_suppress_validation_errors($data)) {
                        $this->errors[$name] = $data['string'];
                    } else {
                        $this->errors[$name] = 'The ' . $label . ' field does not match the ' . $match_field . ' field';
                    }

                    return false;
                }
            }

            # min length
            if (mb_substr($rule, 0, 10) == 'min_length' || mb_substr($rule, 0, 3) == 'min') {

                preg_match_all("/\[(.*?)\]/", $rule, $matches);
                $match = $matches[1][0];

                if (strlen($post) < $match)
                {
                    if($this->_suppress_validation_errors($data)) {
                        $this->errors[$name] = $data['string'];
                    } else {
                        $this->errors[$name] = $label . ' must be at least ' . $match . ' characters';
                    }

                    return false;
                }
            }

            # max length
            if (mb_substr($rule, 0, 10) == 'max_length' || mb_substr($rule, 0, 3) == 'max') {

                preg_match_all("/\[(.*?)\]/", $rule, $matches);
                $match = $matches[1][0];

                if (strlen($post) > $match)
                {
                    if($this->_suppress_validation_errors($data)) {
                        $this->errors[$name] = $data['string'];
                    } else {
                        $this->errors[$name] = $label . ' must be less than ' . $match . ' characters';
                    }

                    return false;
                }
            }

            # exact length
            if (mb_substr($rule, 0, 12) == 'exact_length' || mb_substr($rule, 0, 5) == 'exact') {

                preg_match_all("/\[(.*?)\]/", $rule, $matches);
                $match = $matches[1][0];

                if (strlen($post) != $match)
                {
                    if($this->_suppress_validation_errors($data)) {
                        $this->errors[$name] = $data['string'];
                    } else {
                        $this->errors[$name] = $label . ' must be exactly ' . $match . ' characters';
                    }

                    return false;
                }
            }

            # less than (integer)
            if (mb_substr($rule, 0, 9) == 'less_than') {

                preg_match_all("/\[(.*?)\]/", $rule, $matches);
                $match = $matches[1][0];

                if (!is_numeric($post))
                {
                    if($this->_suppress_validation_errors($data)) {
                        $this->errors[$name] = $data['string'];
                    } else {
                        $this->errors[$name] = $label . ' must be a number';
                    }

                    return false;
                }

                if ((int)$post >= (int)$match)
                {
                    if($this->_suppress_validation_errors($data)) {
                        $this->errors[$name] = $data['string'];
                    } else {
                        $this->errors[$name] = $label . ' must be less than ' . $match;
                    }

                    return false;
                }
            }

            # greater than
            if (mb_substr($rule, 0, 12) == 'greater_than') {

                preg_match_all("/\[(.*?)\]/", $rule, $matches);
                $match = $matches[1][0];

                if (!is_numeric($post))
                {
                    if($this->_suppress_validation_errors($data)) {
                        $this->errors[$name] = $data['string'];
                    } else {
                        $this->errors[$name] = $label . ' must be a number';
                    }

                    return false;
                }

                if ((int)$post <= (int)$match)
                {
                    if($this->_suppress_validation_errors($data)) {
                        $this->errors[$name] = $data['string'];
                    } else {
                        $this->errors[$name] = $label . ' must be greater than ' . $match;
                    }

                    return false;
                }
            }

            # alpha
            if ($rule == 'alpha' && !ctype_alpha(str_replace(' ', '', $post)))
            {
                if($this->_suppress_validation_errors($data)) {
                    $this->errors[$name] = $data['string'];
                } else {
                    $this->errors[$name] = $label . ' may only contain letters';
                }

                return false;
            }

            # alphanumeric
            if ($rule == 'alpha_numeric' && !ctype_alnum(str_replace(' ', '', $post)))
            {
                if($this->_suppress_validation_errors($data)) {
                    $this->errors[$name] = $data['string'];
                } else {
                    $this->errors[$name] = $label . ' may only contain letters and numbers';
                }
                
                return false;
            }

            # alpha_dash
            if ($rule == 'alpha_dash' && preg_match('/[^A-Za-z0-9_-]/', $post))
            {
                if($this->_suppress_validation_errors($data)) {
                    $this->errors[$name] = $data['string'];
                } else {
                    $this->errors[$name] = $label . ' may only contain letters, numbers, hyphens and underscores';
                }
            }

            # numeric
            if ($rule == 'numeric' && !is_numeric($post))
            {
                if($this->_suppress_validation_errors($data)) {
                    $this->errors[$name] = $data['string'];
                } else {
                    $this->errors[$name] = $label . ' must be numeric';
                }

                return false;
            }

            # integer
            if ($rule == 'int' && !filter_var($post, FILTER_VALIDATE_INT))
            {
                if($this->_suppress_validation_errors($data)) {
                    $this->errors[$name] = $data['string'];
                } else {
                    $this->errors[$name] = $label . ' must be a number';
                }

                return false;
            }

            # valid email
            if ($rule == 'valid_email' && !filter_var($post, FILTER_VALIDATE_EMAIL))
            {
                if($this->_suppress_validation_errors($data)) {
                    $this->errors[$name] = $data['string'];
                } else {
                    $this->errors[$name] = 'Please enter a valid email address';
                }

                return false;
            }

            # valid IP
            if ($rule == 'valid_ip' && !filter_var($post, FILTER_VALIDATE_IP))
            {
                if($this->_suppress_validation_errors($data)) {
                    $this->errors[$name] = $data['string'];
                } else {
                    $this->errors[$name] = $label . ' must be a valid IP address';
                }

                return false;
            }

            # valid URL
            if ($rule == 'valid_url' && !filter_var($post, FILTER_VALIDATE_URL))
            {
                if($this->_suppress_validation_errors($data)) {
                    $this->errors[$name] = $data['string'];
                } else {
                    $this->errors[$name] = $label . ' must be a valid IP address';
                }

                return false;
            }

            # sanitize string
            if ($rule == 'sanitize_string') {
                $post = filter_var($post, FILTER_SANITIZE_STRING);
            }

            # sanitize URL
            if ($rule == 'sanitize_url') {
                $post = filter_var($post, FILTER_SANITIZE_URL);
            }

            # sanitize email
            if ($rule == 'sanitize_email') {
                $post = filter_var($post, FILTER_SANITIZE_EMAIL);
            }

            # sanitize integer
            if ($rule == 'sanitize_int') {
                $post = filter_var($post, FILTER_SANITIZE_NUMBER_INT);
            }

            # md5
            if ($rule == 'md5') {
                $post = md5($post . $this->salt);
            }

            # sha1
            if ($rule == 'sha1') {
                $post = sha1($post . $this->salt);
            }

            # php's password_hash() function
            if ($rule == 'hash') {
                $post = password_hash($post, PASSWORD_DEFAULT);
            }

            # strip everything but numbers
            if ($rule == 'strip_numeric') {
                $post = preg_replace("/[^0-9]/", '', $post);
            }

            # create twitter-style username
            if ($rule == 'slug') {
                $post = $this->slug($post);
            }
        }

        # run it through the cleaning method as a final step
        return $this->_clean_value($post, $allow_html);
    }
*/