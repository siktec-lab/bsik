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
require_once "Std.class.php";
require_once 'Db.class.php';

class Base extends BsikStd
{

    /* Base Static properties:*/
    public static $conf;
    static $index_page_url = "";
    public static MysqliDb $db;

    /* Get datetime str
     *  @param $w => String
     *  @Default-param: "now-str"
     *  @return String
     *  @Exmaples:
     *      > "now-str" => now in 'Y-m-d H:i:s' format
     *      > "now-mysql" => now in 'Y-m-d H:i:s' format
     *      > Any => runs date() and pass argument
     */
    public static function configure( array $_conf) : void {
        self::$conf = $_conf;
    }
    public static function connect_db() : void {
        self::$db = new MysqliDb(
            self::$conf["db"]['host'], 
            self::$conf["db"]['user'], 
            self::$conf["db"]['pass'], 
            self::$conf["db"]['name'], 
            self::$conf["db"]['port']
        );
    }
    public static function disconnect_db() : void {
        self::$db->disconnect();
    }
    
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
    
    
    /* A Ui based to handle errors that occurred:
    */
    public static function error_page($code = 0) {
        Base::jump_to_page("error",["ername" => $code],true);
    }
    /* Jump to page by redirect if headers were sent will use a javascript method.
     *  @param $page => String - Page name as used by system
     *  @param $Qparams => Array - Keys as params names and values as value to attach to the URL query string
     *  @param $exit => Boolean - whether to kill the page or not
     *  @Default-params: 
     *      - String "main", 
     *      - [{no query String extra params}],
     *      - Boolean True
     *  @Examples:
     *      > jump_to_page("about", ["v" => 10]) => redirects to the about page with v = 10
    */
    public static function jump_to_page($page = "/", $Qparams = [], $exit = true) {
        $url = self::$index_page_url."/".
                ($page !== "/" ? urlencode($page)."/" : "").
                (!empty($Qparams) ? "?" : "");
        foreach ($Qparams as $p => $v)
            $url .= "&".urlencode($p)."=".urlencode($v);
        if (headers_sent()) 
            echo '<script type="text/javascript">window.location = "'.$url.'"</script>';
        else
            header("Location: ".$url);
        if ($exit) exit();
    } 

        
    /**
     * create_session - sets a session value
     *
     * @param  array $sessions
     * @return void
     */
    public static function create_session(array $sessions) {
        foreach ($sessions as $key => $sess) {
            $_SESSION[$key] = $sess;
        }
    }
    /**
     * create_session - deletes a session value
     *
     * @param  array $sessions
     * @return void
     */
    public static function delete_session(array $sessions) {
        foreach ($sessions as $sess) {
            if (isset($_SESSION[$sess]))
                unset($_SESSION[$sess]);
        }
    }
    /**
     * get_session - get from session
     *
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    public static function get_session(string $key, $default = null) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }
    /* Map all files in a folder:
     *  @param $path => String : the path to the dynamic pages folder.
     *  @param $ext => String : the extension.
     *  @return array
    */
    protected function list_files_in(string $path, string $ext = ".php") : array {
        return array_filter(
            scandir($path), function($k) use($ext) { 
                return is_string($k) && self::$std::str_ends_with($k, $ext); 
            }
        );
    }
}