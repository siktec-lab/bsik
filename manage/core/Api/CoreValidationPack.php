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
Validate::add_validator("start_with", "MyValidators::start_with");

or extend by using the entire class:
Validate::add_class_validator(new MyValidators);

*****************************************************************************/

use \Bsik\Api\Validate;

class ValidationCorePack {

    final public static function in_array($input, string $allowed) {
        return in_array($input, explode("|", $allowed)) ? true : "@input@ is not a valid input.";
    }

    final public static function one_of($input, string $allowed) {
        return self::in_array($input, $allowed);
    }

    final public static function start_with(string $input, string $start = "") {
        if (!str_starts_with($input, $start)) {
            return "@input@ has to start with {$start}";
        }
        return true;
    }

    final public static function ends_with(string $input, string $end = "") {
        if (!str_ends_with($input, $end)) {
            return "@input@ has to end with {$end}";
        }
        return true;
    }

    final public static function required(mixed $input) {
        //Conditions:
        if ($input === null || $input === "") {
            return "@input@ is required";
        }
        return true;
    }

    final public static function optional(mixed $input) {
        //return true if the input is set to continue chain:
        return empty($input) ? "skip" : true;
    }

    final public static function type(mixed $input, string $type = 'string') {
        $input_type = gettype($input);
        if (gettype($input) !== strtolower($type)) {
            return "@input@ is not of type '{$type}' seen '{$input_type}'";
        }
        return true;
    }
    final public static function min_length(string $input, string $min = '1') {
        //Handle inputs:
        $length = mb_strlen($input, Validate::$encoding);
        $parsed_min = (int)$min;
        //Conditions:
        if ($length < $parsed_min) {
            return "@input@ should be at least - {$min} characters long";
        }
        return true;
    }
    final public static function max_length(string $input, string $max = '1') {
        //Handle inputs:
        $length = mb_strlen($input, Validate::$encoding);
        $parsed_max = (int)$max;
        //Conditions:
        if ($length > $parsed_max) {
            return "@input@ should be at least - {$max} characters long";
        }
        return true;
    }
    final public static function count(array $input, string $min = '0', string $max = '1') {
        $parsed_min = (int)$min;
        $parsed_max = (int)$max;
        $c = count($input);
        if ($c > $parsed_max || $c < $parsed_min) {
            return "@input@ array should be at least {$parsed_min} and maximum {$parsed_max} elements long";
        }
        return true;
    }
    final public static function length(string $input, string $min = '1', string $max = '1') {
        //Handle inputs:
        $length = mb_strlen($input, Validate::$encoding);
        $parsed_min = (int)$min;
        $parsed_max = (int)$max;
        //Conditions:
        if ($length < $parsed_min || $length > $parsed_max) {
            return "@input@ should be at least {$min} and maximum {$max} characters long";
        }
        return true;
    }
    final public static function min(string|int $input, string $min = '0') {
        //Handle inputs:
        $input = +$input;
        $min   = +$min;
        //Conditions:
        if ($input < $min) {
            return "@input@ should be greater or equal to - {$min}";
        }
        return true;
    }
    final public static function max(string|int $input, string $max = '0') {
        //Handle inputs:
        $input = +$input;
        $max    = +$max;
        //Conditions:
        if ($input > $max) {
            return "@input@ should be smaller or equal to - {$max}";
        }
        return true;
    }
    final public static function range(string|int|float $input, string|int|float $min = '0', string|int|float $max = '1') {
        //Handle inputs:
        $input  = floatval($input);
        $min    = floatval($min);
        $max    = floatval($max);
        //Conditions:
        if ($input < $min || $input > $max) {
            return "@input@ should be in range - {$min}, {$max}";
        }
        return true;
    }
    final public static function email(string $input) {
        //Conditions:
        if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
            return "@input@ is not a valid email address";
        }
        return true;
    }
}

//Register validator extenssion pack:
Validate::add_class_validator(new ValidationCorePack);



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
                $post = filter_var($post, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
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