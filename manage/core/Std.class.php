<?php
/******************************************************************************/
// Created by: Shlomi Hassid.
// Release Version : 1.0.1
// Creation Date: date
// Copyright 2020, Shlomi Hassid.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.1:
    ->creation - initial
*******************************************************************************/
require_once "Excep.class.php";

class BsikCoreStd {

    private static $regex = [
        "filter-none" => '/[^%s]/'
    ];

/********************** STRING HELPERS *********************************************/
    /**
     * string_starts_with
     * Check if a string starts with a string
     * 
     * @param  string $haystack
     * @param  string $needle
     * @return bool
     */
    final public static function str_starts_with(string $haystack, string $needle) : bool {
        return substr_compare($haystack, $needle, 0, strlen($needle)) === 0;
    }
    /**
     * string_ends_with
     * Check if a string ends with a string
     * 
     * @param  string $haystack
     * @param  string $needle
     * @return bool
     */
    final public static function str_ends_with(string $haystack, string $needle) : bool {
        return substr_compare($haystack, $needle, -strlen($needle)) === 0;
    }    
    /**
     * filter_string
     *
     * @param  string $str
     * @param  mixed $allowed - string or array
     * @return string - filtered string
     */
    final public static function str_filter_string(string $str, $allowed = ["A-Z","a-z","0-9"]) : string {
        $regex = is_string($allowed) ? 
            sprintf(self::$regex["filter-none"], $allowed) :
            sprintf(self::$regex["filter-none"], implode($allowed));
        return preg_replace($regex, '', $str);
    }
    /**
     * is_json - validates a json string by safely parsing it
     * 
     * @param mixed ...$args => packed arguments to pass to json_decode
     * @return bool 
     */
    final public static function str_is_json(...$args) : bool {
        json_decode(...$args);
        return (json_last_error()===JSON_ERROR_NONE);
    }
    /**
     * str_strip_comments - remove comments from strings
	 * From https://stackoverflow.com/a/19136663/319266
	 * @param string $str
	 */
	public static function str_strip_comments(string $str = '' ) : string {
		return preg_replace('~(" (?:\\\\. | [^"])*+ ") | \# [^\v]*+ | // [^\v]*+ | /\* .*? \*/~xs', '$1', $str);
	}
    /********************** ARRAY HELPERS *********************************************/    
    /**
     * arr_get_from
     * return only required keys if defined else a default value
     * @param  array $data - the array with all the data
     * @param  array $keys - keys to return
     * @param  mixed $default - default value if not set
     * @return array - matching keys and there value
     */
    final public static function arr_get_from(array $data, array $keys, $default = null) : array {
        $filter = array_fill_keys($keys, $default);
        $merged = array_intersect_key($data, $filter) + $filter;
        ksort($merged);
        return $merged;
    }
    
    /**
     * arr_filter_out - copies an array without excluded keys
     *
     * @param  array $input - input array
     * @param  array $exclude - excluded keys
     * @return array
     */
    final public static function arr_filter_out(array $input, array $exclude = []) : array {
        return array_diff_key($input, array_flip($exclude));
    }

    /**
     * Merge two arrays - will preserve keys that start with $ e.x $key => finall value.
     * @param array $arr1
     * @param array $arr2
     *
     * @return array
     */
    final public static function arr_extend(array $arr1, array $arr2)
    {
        if (empty($arr1)) {
            return $arr2;
        } else if (empty($arr2)) {
            return $arr1;
        }
        foreach ($arr2 as $key => $value) {
            if (is_string($key) && $key[0] === '$')
                continue;
            if (is_int($key)) {
                $arr1[] = $value;
            } elseif (is_array($arr2[$key])) {
                if (!isset($arr1[$key])) {
                    $arr1[$key] = array();
                }
                if (is_int($key)) {
                    $arr1[] = self::arr_extend($arr1[$key], $value);
                } else {
                    $arr1[$key] = self::arr_extend($arr1[$key], $value);
                }
            } else {
                $arr1[$key] = $value;
            }
        }
        return $arr1;
    }
    /**
     * arr_validate - walks an arry and validate keys and value type.
     * @param array $required - example ["key1" => "string", "key2.key22" => "integer"]
     * @param array $against
     *
     * @return array
     */
    final public static function arr_validate(array $required, array $against) : bool {
        foreach ($required as $key => $type) {
            $keys = explode(".", $key);
            $cond = explode(":", $type);
            $cur = array_shift($keys);
            if (
                isset($against[$cur]) && 
                ((!empty($keys) && gettype($against[$cur]) === "array") ||
                 (
                     (empty($keys) && gettype($against[$cur]) === $cond[0])
                     &&
                     (!isset($cond[1]) || $cond[1] !== "empty" || !empty($against[$cur]))
                 ) 
                )
            ) {
                if (!empty($keys) && !self::arr_validate([implode(".", $keys) => $type], $against[$cur]))
                    return false;
            } else {
                // print $key." - ".$cur."<br />";
                return false;
            }
        }
        return true;
    }
    /********************** DATE HELPERS *********************************************/    
    /**
     * time_datetime
     * return a time stamp in a pre defined format
     * @param  string $w - the format to use
     * @return mixed -> string or false when error
     */
    final public static function time_datetime(string $w = "now-str")
    {
        switch ($w) {
            case "now-str" :
                return date('Y-m-d H:i:s');
            case "now-mysql" :
                return date('Y-m-d H:i:s');
            default:
                return date($w);
        }
    }

    /********************** File System HELPERS *********************************************/ 
    final public static function fs_path_to(string $in, array $path_to_file = []) {
        $path = "";
        $url  = PLAT_FULL_DOMAIN;
        switch ($in) {
            case "modules":
                $path = PLAT_PATH_MANAGE.DS."modules".DS;
                $url  .= "/manage/modules/"; 
                break;
            case "admin-lib-required":
                $path = PLAT_PATH_MANAGE.DS."lib".DS."required".DS;
                $url  .= "/manage/lib/required/"; 
                break;
            case "admin-lib":
                $path = PLAT_PATH_MANAGE.DS."lib".DS;
                $url  .= "/manage/lib/"; 
                break;
            case "themes":
                $path = PLAT_PATH_MANAGE.DS."lib".DS."themes".DS;
                $url  .= "/manage/lib/themes/"; 
                break;
            case "core":
                $path = PLAT_PATH_MANAGE.DS."core".DS;
                $url  .= "/manage/core/"; 
                break;
            case "schema":
                $path = PLAT_PATH_MANAGE.DS."core".DS."schema".DS;;
                $url  .= "/manage/core/schema/"; 
                break;
        }
        $path .= implode(DS, $path_to_file);
        $url  .= implode("/", $path_to_file);
        return ["path" => $path, "url" => $url];
    }
    final public static function fs_file_exists(string $in, array $path_to_file = []) 
    {
        $file = self::fs_path_to($in, $path_to_file);
        if (file_exists($file["path"])) {
            return $file;
        }
        return false;
    }
}

//Reflect the std -> will be usefull for Base inheritance:
class BsikStd {

    public static BsikCoreStd $std;

}

BsikStd::$std = new BsikCoreStd();