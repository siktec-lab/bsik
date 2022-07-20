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

namespace Bsik;

use Exception;

require_once PLAT_PATH_AUTOLOAD;

/**********************************************************************************************************
* Object Methods:
**********************************************************************************************************/
class Std_Object {

    /**
     * objectToArray
     * This method returns the array corresponding to an object, including non public members.
     * If the deep flag is true, is will operate recursively, otherwise (if false) just at the first level.
     *
     * @param object $obj
     * @param bool $deep = true
     * @return array
     * @throws \Exception
     */
    public static function to_array(object $obj, bool $deep = true, array $filter = []) : array {
        $reflectionClass = new \ReflectionClass(get_class($obj));
        $array = [];
        foreach ($reflectionClass->getProperties() as $property) {
            $property->setAccessible(true);
            $val = $property->getValue($obj);
            if (true === $deep && is_object($val)) {
                $val = self::to_array($val, $deep, $filter);
            }
            if (!in_array($property->getName(), $filter))
                $array[$property->getName()] = $val;
            $property->setAccessible(false);
        }
        return $array;
    }

}

/**********************************************************************************************************
* String Methods:
**********************************************************************************************************/
class Std_String {
    
    public static $regex = [
        "filter-none" => '~[^%s]~',
        "version"     => '/^(\d+\.)?(\d+\.)?(\*|\d+)$/'
    ];

    /**
     * starts_with
     * Check if a string starts with a string
     * 
     * @param  string $haystack
     * @param  string $needle
     * @return bool
     */
    final public static function starts_with(string $haystack, string $needle) : bool {
        return substr_compare($haystack, $needle, 0, strlen($needle)) === 0;
    }

    /**
     * ends_with
     * Check if a string ends with a string
     * 
     * @param  string $haystack
     * @param  string $needle
     * @return bool
     */
    final public static function ends_with(string $haystack, string $needle) : bool {
        return substr_compare($haystack, $needle, -strlen($needle)) === 0;
    } 

    /**
     * filter_string
     *
     * @param  string $str
     * @param  mixed $allowed - string or array
     * @return string - filtered string
     */
    final public static function filter_string(string $str, $allowed = ["A-Z","a-z","0-9"]) : string {
        $regex = is_string($allowed) ? 
            sprintf(self::$regex["filter-none"], $allowed) :
            sprintf(self::$regex["filter-none"], implode($allowed));
        return preg_replace($regex, '', $str);
    }
    
    /**
     * is_version - checks if a string is a valid version number D.D.D
     *
     * @param  mixed $version
     * @return bool
     */
    final public static function is_version(string $version) : bool {
        return preg_match(self::$regex["version"], $version);
    }
    
    /**
     * validate_version - compare versions
     * More: https://www.php.net/manual/en/function.version-compare.php
     * returns -1 if the first version is lower than the second, 0 if they are equal, and 1 if the second is lower.
     * When using the optional operator argument, the function will return true if the relationship is the one specified by the operator, false otherwise.
     * @param  mixed $version
     * @param  mixed $against
     * @param  mixed $condition - <, lt, <=, le, >, gt, >=, ge, ==, =, eq, !=, <>
     * @return bool|int
     */
    final public static function validate_version(string $version, string $against, ?string $condition = null) {
        return version_compare(
            trim($version), 
            trim($against), 
            trim($condition)
        );
    }

    /**
     * is_json - validates a json string by safely parsing it
     * 
     * @param array ...$args => packed arguments to pass to json_decode
     * @return bool 
     */
    final public static function is_json(...$args) : bool {
        json_decode(...$args);
        return (json_last_error() === JSON_ERROR_NONE);
    }
        
    /**
     * parse_json
     * safely try to parse json.
     * @param string $json
     * @param mixed $onerror - what to return on error
     * @param bool  $assoc - force associative array
     * @return mixed
     */
    final public static function parse_json(string $json, $onerror = false, bool $assoc = true) {
        return json_decode($json, $assoc) ?? $onerror;
    }

    /**
     * str_strip_comments - remove comments from strings
	 * From https://stackoverflow.com/a/19136663/319266
	 * @param string $str
	 */
	public static function strip_comments(string $str = '' ) : string {
		return preg_replace('~(" (?:\\\\. | [^"])*+ ") | \# [^\v]*+ | // [^\v]*+ | /\* .*? \*/~xs', '$1', $str);
	}

}

/**********************************************************************************************************
* Array Methods:
**********************************************************************************************************/
class Std_Array {

    /**
     * is_assoc
     * check if array is associative
     * @param  mixed $array
     * @return void
     */
    public static function is_assoc(array $array) : bool {
        $keys = array_keys($array);
        return $keys !== array_keys($keys);
    }

    /**
     * rename_key
     * renames an array key if it exists and the new one is not set
     * @param  mixed $key
     * @param  mixed $new
     * @param  mixed $arr
     * @return void
     */
    final public static function rename_key(string $old, string $new, array &$arr) : bool {
        if (array_key_exists($old, $arr) && !array_key_exists($new, $arr) ) {
            $arr[$new] = $arr[$old];
            unset($arr[$old]);
            return true;
        }
        return false;
    }

    /**
     * get_from
     * return only required keys if defined else a default value
     * @param  array $data - the array with all the data
     * @param  array $keys - keys to return
     * @param  mixed $default - default value if not set
     * @return array - matching keys and there value
     */
    final public static function get_from(array $data, array $keys, $default = null) : array {
        $filter = array_fill_keys($keys, $default);
        $merged = array_intersect_key($data, $filter) + $filter;
        ksort($merged);
        return $merged;
    }
    
    /**
     * filter_out - copies an array without excluded keys
     *
     * @param  array $input - input array
     * @param  array $exclude - excluded keys
     * @return array
     */
    final public static function filter_out(array $input, array $exclude = []) : array {
        return array_diff_key($input, array_flip($exclude));
    }

    /**
     * extend
     * Merge two arrays - will preserve keys that start with $ e.x $key => finall value.
     * @param array $arr1
     * @param array $arr2
     *
     * @return array
     */
    final public static function extend(array $def, array $ext) : array {
        if (empty($def)) {
            return $ext;
        } else if (empty($ext)) {
            return $def;
        }
        foreach ($ext as $key => $value) {
            if (is_string($key) && $key[0] === '$')
                continue;
            if (is_int($key)) {
                $def[] = $value;
            } elseif (is_array($ext[$key])) {
                if (!isset($def[$key])) {
                    $def[$key] = array();
                }
                if (is_int($key)) {
                    $def[] = self::extend($def[$key], $value);
                } else {
                    $def[$key] = self::extend($def[$key], $value);
                }
            } else {
                $def[$key] = $value;
            }
        }
        return $def;
    }

    /**
     * validate - walks an array and validate specific key values.
     * use '.' for keys traversal
     * use ':empty' after type to check its not empty.
     * use ':' to declare your custom validation function passed in $fn as an anon func.
     * types: any, boolean, integer, double, string, array, object, NULL, 'unknown type'
     * 
     * @param array $rules - example ["key1" => "string:empty", "key2.key22" => "integer|bool:customFn"]
     * @param array $check - the array to validate
     * @param array $fn - assoc array with functions to use. 
     * @return bool
     * 
     */
    final public static function validate(array $rules, array $check, array $fn = []) {
	
        foreach ($rules as $key => $type) {
            $keys  = explode(".", $key);
            $cond  = explode(":", $type);
            $types = explode("|", $cond[0] ?? ""); 
            $cur   = array_shift($keys);
            if (
                array_key_exists($cur, $check) && 
                (
                    (!empty($keys) && gettype($check[$cur]) === "array") 
                    || 
                    (empty($keys) && (in_array("any", $types, true) || in_array(gettype($check[$cur]), $types, true)))
                )
            ) {
                if (!empty($keys) && !self::validate([implode(".", $keys) => $type], $check[$cur], $fn)) {
                    return false;
                } elseif (empty($keys)) {
                    for ($i = 1; $i < count($cond); $i++) {
                        if (
                            (
                                array_key_exists($cond[$i], $fn)
                            && is_callable($fn[$cond[$i]])
                            && !call_user_func($fn[$cond[$i]], $check[$cur])
                            )
                            ||
                            ($cond[$i] === "empty" && empty($check[$cur]))
                            ||
                            ($cond[$i] !== "empty" && !array_key_exists($cond[$i], $fn))
                        ) {
                            return false;
                        }
                    }
                }
            } else {
                return false;
            }
        }
        return true;
    }
        
    /**
     * path_get
     * walks an array given a string of keys with '.' notation to get inner value or default return
     * Using a wildcard "*" will search intermediate arrays and return an array.
     * @param  string $path - example "key1.key2" | 'theme.*.color'
     * @param  array  $arr
     * @param  mixed $default - default value to return - null by default
     * @return mixed
     */
    final public static function path_get(string $path, array $arr, mixed $default = null) : mixed {
        //return early if empty:
        if (empty($arr)) 
            return $default;
        if (array_key_exists($path, $arr)) 
            return $arr[$path];
        if (empty($path))
            return $arr;
        // Remove starting delimiters and spaces
        $path = ltrim($path, ". ");
        // Remove ending delimiters, spaces, and wildcards
        $path = rtrim($path, ". *");
        // Split the keys by delimiter
        $keys = explode('.', $path);
        //Iterate:
        while (($key = array_shift($keys)) !== null) {
            // Make the key an integer if needed:
            if (ctype_digit($key)) {
                $key = (int) $key;
            }
            //The key is in this level ?
            if (array_key_exists($key, $arr)) {
                if (!empty($keys)) {
                    if (is_array($arr[$key]))
                        $arr = $arr[$key];
                    else
                        break; // Unable to dig deeper
                } else {
                    return $arr[$key]; // Found requested
                }
            }
            //Is a wild card?
            elseif ($key === '*') {
                $values = [];
                foreach ($arr as $ar) {
                    if ($value = self::path_get(implode('.', $keys), $ar)) {
                        $values[] = $value;
                    }
                }
                if (!empty($values)) {
                    return $values; // Found the values requested
                } else {
                    break; // Unable to dig deeper
                }
            } else {
                break; // Unable to dig deeper
            }
        }
        // return default at this point:
        return $default;
    }
    
    /**
     * values_are_not
     * check if an array don't have values - that means that if any of the values are strictly equals
     * to one of the given values the function will return false
     * @param array $arr
     * @param array $not = ["", null]
     * @return bool
     */
    final public static function values_are_not(array $arr, array $not = ["", null]) : bool {
        foreach ($arr as $v) {
            if (in_array($v, $not, true)) {
                return false;
            }
        }
        return true;
    }

}

/**********************************************************************************************************
* Url handling Methods:
**********************************************************************************************************/
class Std_Url {
    
    /**
     * normalize_slashes
     * replaces backslashes in url string
     * @param  string|array $url
     * @return string|array
     */
    public static function normalize_slashes(string|array $url) : string|array {
        return str_replace('\\', '/', $url);
    }

}

/**********************************************************************************************************
* Dates Helper Methods:
**********************************************************************************************************/
class Std_Date {

    /**
     * time_datetime
     * return a time stamp in a pre defined format
     * @param  string $w - the format to use
     * @return string|bool -> string or false when error
     */
    final public static function time_datetime(string $w = "now-str") : string|bool {
        switch ($w) {
            case "now-str" :
                return date('Y-m-d H:i:s');
            case "now-mysql" :
                return date('Y-m-d H:i:s');
            default:
                return date($w);
        }
    }

}

/**********************************************************************************************************
* File System Helper Methods:
**********************************************************************************************************/
class Std_FileSystem {
    
    /**
     * path
     * implodes an array to os based path
     * @param  mixed $path
     * @return string
     */
    final public static function path(...$path) : string {
        return implode(DIRECTORY_SEPARATOR, $path);
    }
        
    /**
     * path_url
     * implodes an array to a url path
     * @param  mixed $path
     * @return string
     */
    final public static function path_url(...$path) : string {
        return implode('/', $path);
    }
        
    /**
     * path_to
     * generates path and url from a given array
     * @param  string $in
     * @param  array|string $path_to_file
     * @return array
     */
    final public static function path_to(string $in, array|string $path_to_file = []) : array {
        //TODO: improve this $urls, to be dynamically extracted.
        $path = $in;
        $url  = PLAT_FULL_DOMAIN;
        switch ($in) {
            case "root":
                $path = PLAT_PATH_BASE.DIRECTORY_SEPARATOR;
                $url  .= "/"; 
                break;
            case "modules":
                $path = PLAT_PATH_MANAGE.DIRECTORY_SEPARATOR."modules".DIRECTORY_SEPARATOR;
                $url  .= "/manage/modules/"; 
                break;
            case "trash":
                    $path = PLAT_PATH_TRASH.DIRECTORY_SEPARATOR;
                    $url  .= "/manage/trash/"; 
                    break;
            case "admin-lib-required":
                $path = PLAT_PATH_MANAGE.DIRECTORY_SEPARATOR."lib".DIRECTORY_SEPARATOR."required".DIRECTORY_SEPARATOR;
                $url  .= "/manage/lib/required/"; 
                break;
            case "admin-lib":
                $path = PLAT_PATH_MANAGE.DIRECTORY_SEPARATOR."lib".DIRECTORY_SEPARATOR;
                $url  .= "/manage/lib/"; 
                break;
            case "themes":
                $path = PLAT_PATH_MANAGE.DIRECTORY_SEPARATOR."lib".DIRECTORY_SEPARATOR."themes".DIRECTORY_SEPARATOR;
                $url  .= "/manage/lib/themes/"; 
                break;
            case "core":
                $path = PLAT_PATH_MANAGE.DIRECTORY_SEPARATOR."core".DIRECTORY_SEPARATOR;
                $url  .= "/manage/core/"; 
                break;
            case "schema":
                $path = PLAT_PATH_MANAGE.DIRECTORY_SEPARATOR."core".DIRECTORY_SEPARATOR."schema".DIRECTORY_SEPARATOR;
                $url  .= "/manage/core/schema/"; 
                break;
            case "front-pages":
                $path = PLAT_FRONT_PAGES.DIRECTORY_SEPARATOR;
                $url  .= "/front/pages/"; 
                break;
            case "raw":
                $path = "";
                break;
        }
        //Normalize parts:
        if (!is_array($path_to_file)) {
            $path_to_file = [$path_to_file];
        }
        //Trim parts:
        array_walk($path_to_file, function(&$part){
            $part = trim($part, "\\/ ");
        });
        //Build:
        $path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $path_to_file);
        $url  = rtrim($url, "/")."/".implode("/", Std_Url::normalize_slashes($path_to_file));
        //Return:
        return ["path" => trim($path, DIRECTORY_SEPARATOR), "url" => $url];
    }
    
    /**
     * get_json_file
     * loads a json file and return its content
     * @param  mixed $path
     * @param  mixed $remove_bom
     * @param  mixed $associative
     * @return array
     */
    final public static function get_json_file($path, bool $remove_bom = true, bool $associative = true) : array|null {
        $json = trim(
            Std_String::strip_comments(@file_get_contents($path) ?: ""), 
            $remove_bom ? "\xEF\xBB\xBF \t\n\r\0\x0B" : " \t\n\r\0\x0B"
        );
        if (!empty($json)) {
            return Std_String::parse_json($json, null, $associative);
        }
        return null;
    }    

    /**
     * file_exists
     * checks wether a file exists
     * 
     * @param  mixed $in
     * @param  array|string $path_to_file
     * @return void
     */
    final public static function file_exists(string $in, array|string $path_to_file = []) 
    {
        $file = self::path_to($in, $path_to_file);
        if (file_exists($file["path"])) {
            return $file;
        }
        return false;
    }
    
    /**
     * path_exists
     * checks wether a file exists with a simple path
     * 
     * @param  array $path_to_file
     * @return void
     */
    final public static function path_exists(...$path_to_file) 
    {
        $path = implode(DIRECTORY_SEPARATOR, array_map(
            function($part){
                return trim($part, " \t\n\r\\/");
            },
            $path_to_file
    )   );
        if (file_exists($path)) {
            return $path;
        }
        return false;
    }

    /**
     * format_size_to_readable
     * coverts a size in bytes to readable format
     * @param  float $size
     * @param  int   $precision = 2
     * @return string
     */
    final public static function format_size_to_readable(float $size, int $precision = 2) : string {
        $unit = ['Byte','KiB','MiB','GiB','TiB','PiB','EiB','ZiB','YiB'];
        for($i = 0; $size >= 1024 && $i < count($unit)-1; $i++){
            $size /= 1024;
        }
        return round($size, $precision).' '.$unit[$i];
    }

        
    /**
     * format_size_to
     * converts between sizes
     * @param  int|float $size
     * @param  string $from
     * @param  string $to
     * @param  int $percision
     * @return int|float
     */
    final public static function format_size_to(int|float $size, string $from = "B", string $to = "KB", int $percision = 2) {
        $from = strtoupper($from);
        $to   = strtoupper($to);
        switch ($from) {
            case "KB": $size = $size * 1024; break;
            case "MB": $size = $size * 1048576; break;
            case "GB": $size = $size * 1073741824; break;
        }
        switch ($to) {
            case "B": return intval(number_format($size, 0, ".", ''));
            case "KB": return floatval(number_format($size / 1024, $percision, ".", ''));
            case "MB": return floatval(number_format($size / 1048576, $percision, ".", ''));
            case "GB": return floatval(number_format($size / 1073741824, $percision, ".", ''));
        }
        return $size;
    }

    private static array $mime_types = [
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',
        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',
        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',
        // audio/video
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',
        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',
        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet'
    ];

        
    /**
     * get_mimetypes
     * return the full mimetype name
     * @param  mixed $types
     * @return array
     */
    final public static function get_mimetypes(...$types) : array {
        if (in_array("*", $types)) return self::$mime_types;
        $ret = [];
        foreach ($types as $type) {
            if (self::$mime_types[$type] ?? false) {
                $ret[$type] = self::$mime_types[$type];
            }
        }
        return $ret;
    }

    /** 
     *  list_files_in
     *  Map all files in a folder:
     *  @param string $path => String : the path to the dynamic pages folder.
     *  @param string $ext  => String : the extension.
     *  @return array
    */
    final public static function list_files_in(string $path, string $ext = ".php") : array {
        return array_filter(
            scandir($path), function($k) use($ext) { 
                return is_string($k) && Std_String::ends_with($k, $ext); 
            }
        );
    }
    
    /**
     * list_folders_in
     * Map all folders in a folder:
     * @param  string $path
     * @return array
     */
    final public static function list_folders_in(string $path) : array {
        return array_values(array_filter(
            scandir($path), function($k) use($path) { 
                return is_string($k) && $k !== "." &&  $k !== ".." && is_dir($path.DIRECTORY_SEPARATOR.$k); 
            }
        ));
    }

}

/**********************************************************************************************************
* General helper Methods:
**********************************************************************************************************/
class Std_General {

    /**
     * print_pre
     * useful print variables in a pre container
     * @param  mixed $out = packed values
     * @return void
     */
    public static function print_pre(...$out) {
        print "<pre>";
        foreach ($out as $value) print_r($value);
        print "</pre>";
    }

}

/**********************************************************************************************************
* BSIK Std:
**********************************************************************************************************/
class Std {
    public static Std_String        $str;
    public static Std_Object        $obj;
    public static Std_Array         $arr;
    public static Std_Url           $url;
    public static Std_Date          $date;
    public static Std_FileSystem    $fs;
    public static Std_General       $gen;
}

Std::$str       = new Std_String;
Std::$obj       = new Std_Object;
Std::$arr       = new Std_Array;
Std::$url       = new Std_Url;
Std::$date      = new Std_Date;
Std::$fs        = new Std_FileSystem;
Std::$gen       = new Std_General;