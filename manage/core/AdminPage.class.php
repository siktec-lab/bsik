<?php
/******************************************************************************/

use function GuzzleHttp\json_encode;

// Created by: Shlomi Hassid.
// Release Version : 1.0.1
// Creation Date: date
// Copyright 2020, Shlomi Hassid.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.1:
    ->creation - initial
*******************************************************************************/
require_once PLAT_PATH_VENDOR.DS.'autoload.php';
require_once PLAT_PATH_CORE.DS."Trace.class.php";
require_once PLAT_PATH_CORE.DS."Base.class.php";
require_once PLAT_PATH_CORE.DS."APageComponents.class.php";
require_once PLAT_PATH_CORE.DS."BsikAPageComponents".DS."CoreComponents.php";

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class APage extends Base
{   
    //Logger:
    public Logger $logger;
    public static $user_string;

    //Components:
    public APageComponents $components;

    //Properties:
    private $storage = array();
    private $types = array("module","api","error","logout");
    public  $request =  array(
        "type"      => "",      // page, api
        "module"      => "",
        "when"      => ""
    );
    public $token =  array(
        "csrf" => "",
        "meta" => ""
    );
    public $modules = [];
    public $module;         //will hols an object that defines the loaded module 
    //For includes:
    private $static_links_counter = 0;
    public $lib_toload = ["css" => [], "js" => []];
    public $includes = array(
        "head"  => array("js" => array(), "css" => array()),
        "body"   => array("js" => array(), "css" => array())
    );
    private $head_meta = array(
        "lang"                  => "",
        "charset"               => "",
        "viewport"              => "",
        "author"                => "",
        "description"           => "",
        "title"                 => "",
        "icon"                  => "",
        "api"                   => "",
        "module"                => "",
        "module-sub"            => "",
    );
    public $additional_meta = array();
    private $custom_body_tag = "";
    
    //Menu:
    public $menu = [];
    //Page loaded values:
    public $platform_settings = [];
    public $platform_libs = [];
    public $settings = []; // Holds merged settings

    /* Page constructor.
     *  @param $conf => SIK configuration array Used in Base Parent
     *  @Default-params: none
     *  @return none
     *  @Examples:
    */
    public function __construct(
        string $user_str = "",
        string $logger_channel = "bsikapi-general",
        string $logger_stream = PLAT_LOG_DIRECTORY
    ) {
        $this->tokenize(); //Tokenize the page.
        
        $this->request["type"]      = $this->request_type(); //Get the request 
        $this->request["module"]    = $this->request_module($this::$conf["default-module"] ?? "");
        $this->request["which"]     = $this->request_module_which($this::$conf["default-module-sub-entry"] ?? "");
        $this::$index_page_url      = $this->parse_slash_url_with($this::$conf["path"]["site_admin_url"]);

        //Extend metas
        $this->meta("api", $this::$index_page_url."/api/".$this->request["module"]);
        $this->meta("module", $this->request["module"]);
        $this->meta("module-sub", $this->request["which"]);

        $this->request["when"]      = self::$std::time_datetime(); //Time stamp for debugging
        $this->fill_modules();
        $this->load_plat_settings("global", true);

        //Logger:
        $this->logger = new Logger($logger_channel);
        $this->logger->pushHandler(new StreamHandler($logger_stream.$logger_channel.".log"));
        $this->logger->pushProcessor(function ($record) {
            $record['extra']['admin'] = APage::$user_string;
            return $record;
        });
        $this->set_user_string($user_str);
    }

    public function set_user_string(string $str) {
        self::$user_string = empty(trim($str)) ? "unknown" : trim($str);
    }

    private function load_plat_settings(string $which = "global", bool $load_libs = true) {
        $set = self::$db->where("name", $which)->getOne("admin_panel_settings", ["object", "libs"]);
        if (!empty($set) && !empty($set["object"])) {
            $this->platform_settings = json_decode($set["object"], true);
        }
        if (!empty($set) && !empty($set["libs"]) && $load_libs) {
            $this->platform_libs = json_decode($set["libs"], true);
        }
    }
    private function fill_modules() {
        //Get basic info needed:
        $this->modules = self::$db->where("status", 0, "<>")->map("name")->arrayBuilder()->get("admin_modules", null, [
            "name", "priv_users", "priv_content", "priv_admin", "priv_install", "path", "menu"
        ]);
        //Parse required privileges:
        foreach ($this->modules as &$module) {
            $module["priv"] = (object)[
                "users"     => $module["priv_users"]    ? true : false,
                "content"   => $module["priv_content"]  ? true : false,
                "admin"     => $module["priv_admin"]    ? true : false,
                "install"   => $module["priv_install"]  ? true : false
            ];
        }
        return count($this->modules);
    }    
    /**
     * isset_module
     *
     * @param  string $name
     * @return bool
     */
    public function isset_module(string $name = "") : bool {
        $name = empty($name) ? $this->request["module"] : $name;
        return isset($this->modules[$name]);
    } 
    /**
     * get_module_priv
     * get an object with all required priv of the signed admin
     * @param  string $name
     * @return mixed - Object with privs attributes, NULL if not set
     */
    public function get_module_priv(string $name = null) {
        $name = empty($name) ? $this->request["module"] : $name;
        return isset($this->modules[$name]) ? $this->modules[$name]["priv"] : null;
    }
    public function is_allowed_to_use(&$Admin, string $name = "") : bool {
        $name = empty($name) ? $this->request["module"] : $name;
        if (isset($Admin->priv) && !empty($Admin->priv) && isset($this->modules[$name])) {
            $module = &$this->modules[$name];
            if ($Admin->priv->users     < $module["priv"]->users)     return false;
            if ($Admin->priv->content   < $module["priv"]->content)   return false;
            if ($Admin->priv->admin     < $module["priv"]->admin)     return false;
            if ($Admin->priv->install   < $module["priv"]->install)   return false;
            return true;
        }
        return false;
    }
    /* Get and set the type of the page request.
     *  @Default-params: none
     *  @return String
     * 
    */
    private function request_type()
    {
        // TODO: Log the requests given to the server.
        return (isset($_REQUEST["type"]) && in_array($_REQUEST["type"] ,$this->types)) ? $_REQUEST["type"] : $this->types[0];
    }
    /* Get and Set the page requested.
     *  @Default-params: none
     *  @return String
     *
    */  
    private function request_module(string $default)
    {
        // TODO: Log the requests given to the server.
        $page = (isset($_REQUEST["module"])) ? $_REQUEST["module"] : $default;
        return self::$std::str_filter_string($page, "A-Za-z0-9_-");
    }
    /* Get and Set the page requested - sub entry of module.
     *  @Default-params: none
     *  @return String
     *
    */
    private function request_module_which(string $default)
    {
        $which = (isset($_REQUEST["which"])) ? $_REQUEST["which"] : $default;
        return self::$std::str_filter_string($which, "A-Za-z0-9_-");
    }

        
    /**
     * load_page
     * load the page related definition and structure
     * 
     * @return bool
     */
    public function load_module() : bool {
        if ($this->request["module"]) {
            $cols = [
                "admin_modules.name",
                "admin_modules.path",
                "admin_modules.settings",
                "admin_modules.defaults",
                "admin_modules.version",
                "admin_modules.menu"
            ];
            $loaded_module = self::$db->where("admin_modules.name", $this->request["module"])
                                        ->getOne("admin_modules", $cols);
            
            //Save module:
            $this->module = (object)[
                "name"    => $loaded_module["name"] ?? null,
                "version" => $loaded_module["version"] ?? null,
                "path"    => $loaded_module["path"] ?? null,
                "which"   => $this->request["which"],
                "menu"    => json_decode($loaded_module["menu"], true),
                "header"  => []
            ];
            //Parse settings if set:
            if (!empty($loaded_module) && !empty($loaded_module["settings"])) {
                $loaded_module["settings"] = json_decode($loaded_module["settings"], true);
                //Merge defined -> extends default settings:
                $this->settings = array_merge($this->platform_settings, $loaded_module["settings"]);
            }
        }
        return !empty($this->settings);
    }

    public function load_menu() {
        //Parse definitions:
        foreach ($this->modules as &$module) {
            $m = json_decode($module["menu"], true);
            usort($m["sub"], fn($a, $b) => $a['order'] - $b['order']);
            $this->menu[] = $m;
        }
        usort($this->menu, fn($a, $b) => $a['order'] - $b['order']);
    }
    /* Get and Set the page token If not set create a new one.
     *  @Default-params: none
     *  @return none
    */
    private function tokenize()
    {
        if (empty(self::get_session("csrftoken")))
            self::create_session(["csrftoken" => bin2hex(random_bytes(32))]);
        $this->token["csrf"] = self::get_session("csrftoken");
        $this->token["meta"] = "<meta name='csrf-token' content=".$this->token["csrf"].">";
    }


    /**
     * include - used by system and also by user for loading libs after parsed:
     *
     * @param  string $pos - the position -> head, body
     * @param  string $type - the lib type -> css, js
     * @param  string $name - the lib name
     * @param  array  $set - lib definition -> ["name", "version"]
     * @param  string $add - optional append to link
     * @return void
     */
    public function include(string $pos, string $type, string $name, array $set, string $add = "") {
        /* SH: added - 2021-03-03 => convert this to db error logging  */
        if (!is_string($pos) || !isset($this->includes[$pos])) {
            trigger_error("'Page->include' first argument ($pos) is unknown pos value", E_PLAT_WARNING);
            return $this;
        }
        if (!is_string($type) || (strtolower($type) !== "js" && strtolower($type) !== "css")) {
            trigger_error("'Page->include' second argument ($type) must be a valid type argument - js | css.", E_PLAT_WARNING);
            return $this;
        }
        $path = $set["name"] ?? "";
        if (self::$std::str_starts_with($name,"link") || self::$std::str_starts_with($name,"path")) {
            $path = $path;
            $name = $name[0] == 'l' ? "link" : "path"; 
        } else {
            $name = $name;
            $path = $set["version"] ?? "";
        }
        $this->includes[$pos][$type][] = ["name" => $name ,"path" => $path, "add" => $add];
        //return $this;
    }
    
    /**
     * parse_lib_query - parse a lib name to components for version control
     *
     * @param  string $lib_query - the lib name ex. libname:+3.3.0
     * @param  mixed $pos        - where to include -> head, body
     * @return void
     */
    private static function parse_lib_query(string $lib_query, string $pos = "") : array {
        $lib = explode(':', $lib_query);
        $place = in_array($lib[0], ["required", "lib", "install", "ext"]) ? $lib[0] : false;
        if (!$place) return [];
        $path = $lib[1] ?? "";
        return [
            "path" => $path,
            "place" => $place,
            "pos" => $pos
        ];
    }
    /**
     * load_json_libs - loads cms based define libs that are stored as special json object
     * 
     * @param  string $libs_json -> the json representation
     * @return void
     */
    private function load_libs_object(array $libs) {
        //Parse each lib:
        foreach ($libs as $key => $inpos_lib) {
            $pos = $key;
            foreach ($inpos_lib as $type_libs) {
                $type = $type_libs["type"];
                foreach ($type_libs["libs"] as $lib) {
                    if (self::$std::str_starts_with($lib,"//") || self::$std::str_starts_with($lib,"http")) {
                        $this->static_links_counter++;
                        $this->lib_toload[$type]["link".$this->static_links_counter] = ["name" => $lib, "pos" => $pos];
                    } else {
                        $lib_obj = self::parse_lib_query($lib, $pos);
                        if (!empty($lib_obj)) {
                            $this->static_links_counter++;
                            switch ($lib_obj["place"]) {
                                case "required": {
                                    $this->lib_toload[$type]["path".$this->static_links_counter] = [
                                        "name" => PLAT_FULL_DOMAIN."/manage/lib/required/".$lib_obj["path"],
                                        "pos"  => $lib_obj["pos"]
                                    ];
                                } break;
                                case "lib": {
                                    $this->lib_toload[$type]["path".$this->static_links_counter] = [
                                        "name" => PLAT_FULL_DOMAIN."/manage/lib/".$lib_obj["path"],
                                        "pos"  => $lib_obj["pos"]
                                    ];
                                } break;
                                case "module": {
                                    $this->lib_toload[$type]["path".$this->static_links_counter] = [
                                        "name" => PLAT_FULL_DOMAIN."/manage/lib/".$lib_obj["path"],
                                        "pos"  => $lib_obj["pos"]
                                    ];
                                } break;
                            }
                        }
                    }
                }
            }
        }
    }
        
    /**
     * load_libs - lids predefined libs by the cms
     *
     * @param  bool $template - whether to load template default libs?
     * @param  bool $page - whether to load page specific libs?
     * @return int
     */
    public function load_libs(bool $global) : int {
        
        //Build libs:
        if ($global && !empty($this->platform_libs)) 
            $this->load_libs_object($this->platform_libs);
        // if ($page && isset($this->definition["libs"])) 
        //     $this->load_json_libs($this->definition["libs"]);
    
        //Add via include method:
        foreach($this->lib_toload as $type => $libs)
            foreach($libs as $name => $set)
                $this->include($set["pos"], $type, $name, $set);
    
        return count($this->lib_toload["css"]) + count($this->lib_toload["js"]);
    }
    
    /* Set and Gets page html meta tags values.
     *  @param $name => String - the meta tag to use
     *  @param $set => Mixed - False|String if false will GET if string will set.
     *  @Default-params: $set = False
     *  @return String | Object(this)
     *  @Examples:
     *      > $Page->meta("author", "SIK Framework");
    */
    public function meta($name, $set = false) {
        if (!isset($this->head_meta[$name]))
            trigger_error("'Page->meta()' you must use a valid meta type.", E_PLAT_WARNING);
        if ($set === false) return $this->head_meta[$name];
        $this->head_meta[$name] = $set;
        return $this;
    }    
    /**
     * op_meta - declare a custom optional meta tag:
     * e.x op_meta(["name" => "text", "content" => "text"])
     *
     * @param array $define - associative array that defines the attributes
     * @return self
     */
    public function op_meta(array $define) {
        $attrs = "";
        foreach ($define as $attr => $value) {
            $attrs .= $attr.'="'.htmlspecialchars($value).'" '; 
        }
        $this->additional_meta[] = sprintf("<meta %s />", $attrs);
        return $this;
    }
    /* Set and Gets a custom body tag <body *******>.
     *  @param $set => MIXED - String | False
     *  @Default-params: false
     *  @return MIXED - String | Object(this)
    */
    public function body_tag($set = false) {
        if (!$set) return $this->custom_body_tag;
        $this->custom_body_tag = $set;
        return $this;
    }    
    
    /* Stich a url together for normalizing urls.
     *  @param $path => String - only the traverse folders
     *  @param $file => String - filename
     *  @Default-params: none
     *  @return String
     *  @Examples:
     *      > $Page->build_url_with("/dir/img/", "dom.png");
    */
    public function build_url_with($path, $file = "") {
        return str_replace('\\', '/', PLAT_URL_DOMAIN.DS.PLAT_URL_BASE.DS.$path.$file);
    }
    public function parse_slash_url_with($url) {
        return str_replace('\\', '/', $url);
    }
    /* Storage used to save data and handle it safely.
     *  @param $name => String
     *  @param $data => Mixed
     *  @param $protect => Boolean
     *  @Default-params: protect - true
     *  @return Boolean
     *  @Examples:
     *      > $Page->store("test value", "dom.png");
    */
    public function store($name, $data, $protect = true) {
        if ($protect && isset($this->storage[$name])) {
            trigger_error("'Page->store' you are trying to override a protected storage member", E_PLAT_WARNING);
            return false;
        }
        $this->storage[$name] = $data;
        if ($data === false || $data === null) return false;
        return true;
    }
    /* get method is used to retrieve stored data.
     *  @param $name => Boolean|String // if True return the entire storage array, otherwise return by name.
     *  @Default-params: None
     *  @return Mixed
     *  @Examples:
     *      > $Page->get(true);
     *      > $Page->get("key-name");
    */
    public function get($name) {
        return $name === true ? $this->storage : $this->storage[$name];
    }


    /******************************  RENDER ELEMENTS  *****************************/
    
    private function render_inline_error(string $text, string $icon = "fa-exclamation-triangle") : string {
        $styles = [
            "color:lightcoral",
            "padding:15px",
            "clear:both"
        ];
        $icon_html = "<i class='fas $icon'></i>";
        return sprintf("<span style='%s'>%s&nbsp;%s</span>", implode(';', $styles), $icon_html, $text);
    }

    public function render_libs(string $type, string $pos) {
        $tpl = [
            "css"       => '<link rel="stylesheet" href="%s" />'.PHP_EOL,
            "js"        => '<script type="text/javascript" src="%s"></script>'.PHP_EOL,
            "module"    => '<script type="module" src="%s"></script>'.PHP_EOL
        ];
        $use = $tpl[$type];
        foreach ($this->includes[$pos][$type] ?? [] as $lib) {

            if (is_array($lib["path"])) {
                //array_walk($lib["path"], fn($p) => printf($use, $p));
                array_walk($lib["path"], function($p) use($use, $tpl) {
                    if (strpos($p,".module.")) {
                        printf($tpl["module"], $p);
                    } else {
                        printf($use, $p);
                    }
                });
            } else {
                if (strpos($lib["path"],".module.")) {
                    printf($tpl["module"], $lib["path"]);
                } else {
                    printf($use,$lib["path"]);
                }
            }
        }
    }
    
    /**
     * render_favicon
     * the system expects 4 files ad the path folder:
     *  - apple-touch-icon.png
     *  - favicon-32x32.png
     *  - favicon-16x16.png
     *  - site.webmanifest
     * @param  string $path - path to the folder with favicons
     * @param  string $name - naming scheme of favicons
     * @return void
     */
    public function render_favicon(string $path, string $name = "favicon") {
        $tpl = '<link rel="apple-touch-icon" sizes="180x180" href="%1$s/apple-touch-icon.png">'.PHP_EOL.
        '<link rel="icon" type="image/png" sizes="32x32" href="%1$s/%2$s-32x32.png">'.PHP_EOL.
        '<link rel="icon" type="image/png" sizes="16x16" href="%1$s/%2$s-16x16.png">'.PHP_EOL.
        '<link rel="manifest" href="%1$s/site.webmanifest">'.PHP_EOL;
        printf($tpl, $path, $name);
    }

    public function render_module(string $module = "", $values = null) {
        $module = empty($module) ? $this->module->name : $module;
        $path           = PLAT_PATH_MANAGE.DS."modules".DS.$module;
        $main_path      = $path.DS."module.php";
        $components_path = $path.DS."components.php";
        //Include components:
        if (file_exists($components_path)) include_once $components_path;
        //Require module
        if (file_exists($main_path)) {
            require_once PLAT_PATH_CORE.DS.'BsikModule.class.php';
            $Module = new BsikModule($module, $this->module->which);
            require_once $main_path;
            try {
                $render = $ModuleBlockRender($this, $values, $Module);
                return is_string($render) ? 
                    $render : 
                    $Module->render($this->module->which, $this, $values);
            } catch (Throwable $e) {
                $this->logger->error("Error captured on module render [{$e->getMessage()}].", ["module" => $module, "path" => $path]);
                return $this->render_inline_error(text:"Error in module - check logs.");
            }
        }
        $this->logger->error("Could not find module content to render.", context: ["module" => $module, "path" => $path]);
        return "";
    } 
    
}
