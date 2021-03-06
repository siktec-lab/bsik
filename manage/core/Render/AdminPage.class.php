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

namespace Bsik\Render;

require_once PLAT_PATH_AUTOLOAD;

use \Bsik\Std;
use \Bsik\Base;
use \Bsik\Api\BsikApi;
use Bsik\Module\Modules;
use Bsik\Module\Module;
use Bsik\Module\ModuleView;
use \Bsik\Render\Template;
use \Bsik\Privileges as Priv;
use \Bsik\Users\User;

use \Exception;
use Throwable;

class AModuleRequest {
    
    //values filter paterns:
    public static $name_pattern  = "A-Za-z0-9_-";
    public static $which_pattern = "A-Za-z0-9_-";

    //Allowed types:
    public static $types = [
        "module",
        "api",
        "error",
        "logout"
    ];

    //Raw request:
    private array $requested; 
    
    //Values:
    public string $type     = "";
    public string $module  = "";
    public string $which = "";
    public string $when  = "";

    /**
     * __construct
     * @param  array $request -> the request params
     * @return AModuleRequest
     */
    public function __construct(array $request = []) {
        $this->requested = $request;
    }    

    /**
     * set_type
     * - sets the request type. 
     * @param  string $default
     * @return bool
     */
    public function type(string $default) : bool {
        $this->type = isset($this->requested["type"]) && in_array($this->requested["type"], self::$types) ? $this->requested["type"] : $default;
        return !empty($this->type);
    }    
    
    /**
     * set_page
     * - sets the requested page name
     * @param  string $default
     * @return bool
     */
    public function module(string $default) : bool {
        $this->module = isset($this->requested["module"])
                        ? Std::$str::filter_string($this->requested["module"], self::$name_pattern)
                        : $default;
        return !empty($this->module);
    }    

    /**
     * set_which
     * - sets the which query string
     * @param  string $default
     * @return bool
     */
    public function which(string $default) : bool {
        $this->which = (isset($this->requested["which"]))
                            ? Std::$str::filter_string($this->requested["which"], self::$which_pattern)
                            : $default;
        return !empty($this->which);
    }
    
    /**
     * set_when
     * - sets the timestamp of the request
     * @param  string $time_str
     * @return void
     */
    public function when(string $time_str = "") : void {
        $this->when = empty($time_str) ? Std::$date::time_datetime() : $time_str;
    }
    
    /**
     * get - serialize the request to an array
     *
     * @return array
     */
    public function get() : array {
        return Std::$obj::to_array($this, filter : [
            "name_pattern",
            "which_pattern",
            "types",
        ]);
    }
}

class APageMeta {

    public array $defined_metas;
    public array $additional_meta;

    public function __construct()
    {
        $this->defined_metas    = [];
        $this->additional_meta  = [];
    }
        
    /**
     * define
     * - defines required page meta tags.
     * @param  array $_metas
     * @return void
     */
    public function define(array $_metas = []) : void {
        $this->defined_metas = Std::$arr::is_assoc($_metas) ? $_metas : array_fill_keys($_metas, "");
    }
    
    /**
     * meta
     * sets a defined meta value that will be rendered
     * 
     * @param  string $name     => meta name
     * @param  string|bool $set => if false will return value otherwise will set the meta
     * @return object|string
     */
    public function set(string $name, string|bool $set = false) : object|string {
        if (!isset($this->defined_metas[$name]))
            trigger_error("'Page->meta()' you must use a valid meta type. [unknown entry '$name']", E_PLAT_WARNING);
        if ($set === false) 
            return $this->defined_metas[$name];
        $this->defined_metas[$name] = $set;
        return $this;
    }  

    /**
     * op_meta - declare a custom optional meta tag:
     * op_meta(["name" => "text", "content" => "text"])
     *
     * @param array $define - associative array that defines the attributes
     * @return object
     */
    public function add(array $define) : object {
        $attrs = "";
        foreach ($define as $attr => $value) {
            $attrs .= $attr.'="'.htmlspecialchars($value).'" '; 
        }
        $this->additional_meta[] = sprintf("<meta %s />", $attrs);
        return $this;
    }

    public function data_object(array $data, string $name = "module-data") : void {
        $this->add([
            "name"      => $name, 
            "content"   => base64_encode(json_encode($data))
        ]);
    }
    /**
     * get - serialize the metas object to an array
     *
     * @return array
     */
    public function get() : array {
        return Std::$obj::to_array($this, filter : [
            "name_pattern",
            "which_pattern",
            "types",
        ]);
    }

}

class APageHttpHeaders {

    public static $str = [
        'OK'                        => 200,
        'Created'                   => 201,
        'Accepted'                  => 202,
        'No Content'                => 204,
        'Not Modified'              => 304,
        'Bad Request'               => 400,
        'Forbidden'                 => 403,
        'Not Found'                 => 404,
        'Method Not Allowed'        => 405,
        'Unsupported Media Type'    => 415,
        'Upgrade Required'          => 426,
        'Internal Server Error'     => 500,
        'Not Implemented'           => 501
    ];

    final public static function getCodeOf(string $mes) {
        return self::$str[$mes] ?? 0;
    }
    final public static function getMessageOf(int $code) {
        return array_search($code, self::$str);
    }
    final public static function send_response_code(int $code) : bool {
        return http_response_code($code);
    }
}

class APage extends Base
{   

    public static APage $ref;

    //The request object:
    public static AModuleRequest $request;

    //The template engine:
    public Template $engine;

    //Issuer privileges:
    public static Priv\PrivDefinition $issuer_privileges;

    //Paths: 
    public static array $paths = [
        "global-blocks"         => PLAT_PAGES_BLOCKS,
        "global-templates"      => PLAT_PAGES_TEMPLATES,
        "global-lib"            => PLAT_PATH_LIB_URL
    ];

    //Loaded:
    public static Modules $modules;
    public static Module  $module;         //will hols an object that defines the loaded module 

    //For includes:
    private $static_links_counter = 0;
    public  $lib_toload = ["css" => [], "js" => []];
    public  $includes = array(
        "head"  => array("js" => array(), "css" => array()),
        "body"  => array("js" => array(), "css" => array())
    );

    public APageMeta $meta;

    public string $custom_body_tag = "";

    //Menu:
    public $menu = [];
    
    //Page loaded values:
    public $platform_settings = [];
    public $platform_libs = [];

    /* Page constructor.
     *  @param $conf => SIK configuration array Used in Base Parent
     *  @Default-params: none
     *  @return none
     *  @Examples:
    */
    public function __construct(
        bool $enable_logger         = true,
        string $logger_channel      = "general",
        ?Priv\PrivDefinition $issuer_privileges = null
    ) {

        $this::$index_page_url = Std::$url::normalize_slashes($this::$conf["path"]["site_admin_url"]);

        //Set logger:
        self::load_logger(
            path : PLAT_LOG_DIRECTORY,
            channel: $logger_channel
        );

        //Set issuer privileges:
        self::$issuer_privileges = $issuer_privileges ?? new Priv\PrivDefinition();

        //Set admin platform core templates:
        $this->engine = new Template(
            cache : PLAT_TEMPLATES_CACHE
        );

        $this->engine->addFolders([
            PLAT_PAGES_TEMPLATES
        ]);

        //Initialize meta object:
        $this->meta = new APageMeta();
        $this->meta->define([
            "lang"                  => "",
            "charset"               => "",
            "viewport"              => "",
            "author"                => "",
            "description"           => "",
            "title"                 => "",
            "icon"                  => "",
            "api"                   => $this::$index_page_url."/api/".self::$request->module,
            "module"                => self::$request->module,
            "module_sub"            => self::$request->which,
        ]);

        //Logger flag:
        self::$logger_enabled = $enable_logger;

        //Initialize Modules:
        self::$modules = Modules::init(self::$db);

        //Save ref:
        self::$ref = $this;
    }

    /**********************************************************************************************************
    /** FINALS:
     **********************************************************************************************************/

    final public static function error_page($code = 0, bool $exit = true) : void {
        //Load platform error pages:
        $code = in_array(intval($code), [404, 401, 403, 500]) ? $code : 404;
        self::log("notice", "error page [".$code."] load", [
            "request"   => $_SERVER['REQUEST_URI'],
            "method"    => $_SERVER['REQUEST_METHOD'],
            "remote"    => $_SERVER['REMOTE_ADDR']
        ]);

        //Sets the response http code:
        APageHttpHeaders::send_response_code($code);
        include sprintf("pages/errors/%s.php", $code);
        if ($exit) 
            exit();
    }

    /**
     * load_request
     * - parses a request structure into the corresponding object which will be carried around the entire render process
     * @param  array $request_data => the request usually $_REQUEST, $_POST, $_GET
     * @return void
     */
    final public static function load_request(array $request_data = []) : void {
        self::$request = new AModuleRequest(empty($request_data) ? [] : $request_data);
        self::$request->type("module");
        self::$request->module(self::$conf["default-module"] ?? "");
        self::$request->which("default");
        self::$request->when();
    }


    public function load_settings(string $which = "global", bool $load_libs = true) {
        $set = self::$db->where("name", $which)->getOne("bsik_settings", ["object", "libs"]);
        if (!empty($set) && !empty($set["object"])) {
            $this->platform_settings = json_decode($set["object"], true);
        }
        if (!empty($set) && !empty($set["libs"]) && $load_libs) {
            $this->platform_libs = json_decode($set["libs"], true);
        }
    }
  
    /**
     * is_module_installed
     *
     * @param  string $name
     * @return bool
     */
    public function is_module_installed(string $name = "") : bool {
        return self::$modules::is_installed(
            empty($name) ? self::$request->module : $name
        );
    } 

    public function is_allowed_to_use_module(string $name = "", array &$messages = []) : bool {
        $module_name = empty($name) ? self::$request->module : $name;
        $module = self::$modules::module($module_name);
        if ($module) {
            return $module->priv->has_privileges(self::$issuer_privileges, $messages);
        }
        return false;
    }
    
    /**
     * load_module 
     * 
     * @return bool
     */
    public function load_module(string $module = "", string $which = "", ?BsikApi $Api = null, ?User $User = null) : bool {

        $module_name = empty($module) ? self::$request->module : $module;
        $which = empty($which) ? self::$request->which  : $which;

        try {
            //Load and initiate module:
            self::$module = $this::$modules::initiate_module(
                $module_name,
                $which,
                self::$db,
                $Api,
                $this,
                $User
            );
            if (!is_null(self::$module)) {
                //Set template origin:
                if (file_exists(self::$module->paths["module-templates"])) {
                    $this->engine->addFolders([
                        self::$module->paths["module-templates"]
                    ]);
                }
                return true;
            }
        } catch (\Throwable $e) {
            $origin = $e->getPrevious();
            self::log("error", $e->getMessage(), 
                context : [
                    "module" => $module_name,
                    "file"   => is_null($origin) ? $e->getFile() : $origin->getFile(),
                    "line"   => is_null($origin) ? $e->getLine() : $origin->getLine()
                ]
            );
        }
        return false;
    //     if ($this->is_module_installed()) {

    //         $module_name        = empty($module) ? self::$request->module : $module;
    //         $module_installed   = self::$modules::module_installed($module_name);
    //         $module_installed["which"] = empty($which) ? self::$request->which  : $which;
    //         $module_file        = Std::$fs::path_to("modules", [$module_installed["path"], "module.php"]);

    //         //Require module
    //         if (file_exists($module_file["path"])) {
    //             try {
    //                 //Load module & views:
    //                 require $module_file["path"];
    //                 //Save ref and load data + settings:
    //                 self::$module = self::$modules::module($module_name);
    //                 if (self::$module) {
    //                     self::$module->load(
    //                         data:   $module_installed, 
    //                         DB:     self::$db,
    //                         Api:    $Api, 
    //                         Page:   $this, 
    //                         User:   $User
    //                     );
    //                 }
    //                 //Set template origin:
    //                 if (file_exists(self::$module->paths["module-templates"])) {
    //                     $this->engine->addFolders([
    //                         self::$module->paths["module-templates"]
    //                     ]);
    //                 }
    //                 return true;
    //             } catch (\Throwable $e) {
    //                 self::log("error", "Internal Error captured on module load [{$e->getMessage()}].", 
    //                     context : [
    //                         "module" => $module_name,
    //                         "path"   => $module_file["path"],
    //                         "file"   => $e->getFile(),
    //                         "line"   => $e->getLine()
    //                     ]
    //                 );
    //                 return false;
    //             }
    //         } else {
    //             self::log("error", "Could not find module file to load.", 
    //                 context: [
    //                     "module" => $module_name, 
    //                     "path"   => $module_file["path"]
    //                 ]
    //             );
    //             return false;
    //         }
    //     }
    //     return false;
    }

    public function load_menu() {
        //Parse definitions:
        foreach (self::$modules::get_all_installed() as &$module_name) {
            $definition = self::$modules::module_installed($module_name);
            $m = json_decode($definition["menu"], true);
            usort($m["sub"], fn($a, $b) => $a['order'] - $b['order']);
            $this->menu[] = $m;
        }
        usort($this->menu, fn($a, $b) => $a['order'] - $b['order']);
    }
    
    /**
     * include - used by system and also by user for loading libs after parsed:
     *
     * @param  string $pos - the position -> head, body
     * @param  string $type - the lib type -> css, js
     * @param  string $name - the lib name
     * @param  array  $set - lib definition -> ["name", "version"]
     * @param  string $add - optional append to link
     * @return object
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
        if (Std::$str::starts_with($name,"link") || Std::$str::starts_with($name,"path")) {
            $path = $path;
            $name = $name[0] == 'l' ? "link" : "path"; 
        } else {
            $name = $name;
            $path = $set["version"] ?? "";
        }
        $this->includes[$pos][$type][] = ["name" => $name ,"path" => $path, "add" => $add];
        return $this;
    }

    public function include_asset($pos, $type, $in, $path) {
        switch ($in) {
            case "me": {
                $url = Std::$fs::path_url(self::$paths["module-lib"], ...$path);
                $this->include($pos, $type, "link", ["name" => $url]);
            } break;
            case "global": {
                $url = Std::$fs::path_url(self::$paths["global-lib"], ...$path);
                $this->include($pos, $type, "link", ["name" => $url]);
            } break;
            case "required": {
                $url = Std::$fs::path_url(self::$paths["global-lib"], "required", ...$path);
                $this->include($pos, $type, "link", ["name" => $url]);
            } break;
        }
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
                    if (Std::$str::starts_with($lib,"//") || Std::$str::starts_with($lib,"http")) {
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
     * load_libs - loads predefined libs by the cms
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
    
    /** 
     * Set and Gets a custom body tag <body *******>.
     * @param mixed $set => if false will return current.
     * @return mixed
    */
    public function body_tag($set = false) {
        if (!$set) return $this->custom_body_tag;
        $this->custom_body_tag = $set;
        return $this;
    }    
    
    /******************************  RENDER METHODS  *****************************/
    
    private function render_inline_error(string $text, string $icon = "fa-exclamation-triangle") : string {
        $styles = [
            "color:lightcoral",
            "padding:15px",
            "clear:both",
            "display: block;"
        ];
        $icon_html = "<i class='fas $icon'></i>";
        return sprintf("<span style='%s'>%s&nbsp;%s</span>", implode(';', $styles), $icon_html, $text);
    }

    public function render_libs(string $type, string $pos, $print = true) : string {
        $tpl = [
            "css"       => '<link rel="stylesheet" href="%s" />'.PHP_EOL,
            "js"        => '<script type="text/javascript" src="%s"></script>'.PHP_EOL,
            "module"    => '<script type="module" src="%s"></script>'.PHP_EOL
        ];
        $use = $tpl[$type];
        $buffer = "";
        if (!$print) ob_start();
        foreach ($this->includes[$pos][$type] ?? [] as $lib) {
            if (is_array($lib["path"])) {
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
        if (!$print) {
            $buffer = ob_get_contents(); 
            ob_end_clean();
        }
        return $buffer;
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

    public function render_module(string $view_name = "", array $args = []) {
        //all needed info:
        try {
            //Load module & views:
            if (self::$module) {

                //Render the module view:
                [$status, $content] = self::$module->render(
                    view_name   : $view_name, //Empty view name will render current one.
                    args        : $args,
                    issuer      : self::$issuer_privileges
                );
                //make sure that content is string:
                if (!is_string($content)) 
                    throw new Exception("error rendering view [".self::$module->module_name."->".self::$module->which."] - view returned non printable content.", E_PLAT_ERROR);
                //Return the view content:
                if ($status) {
                    return $content;
                } else {
                    return $this->render_inline_error(text : $content);
                }
            } else {
                throw new Exception("tried to render module from APage - without loading a module.", E_PLAT_ERROR);
            }
        } catch (\Throwable $e) {
            self::log("error", "Error captured on module render [{$e->getMessage()}].", [
                "module"    => self::$module->module_name, 
                "view"      => self::$module->which, 
                "file"      => $e->getFile(),
                "line"      => $e->getLine()
            ]);
            return $this->render_inline_error(text:"Error in module - check logs.");
        }
    } 
    
    public function render_block(string $placement, string $name, string $class, array $args = []) {
        $path = self::$paths[$placement."-blocks"].DS.$name.".block.php";
        if (file_exists($path)) {
            include $path;
            $ref = new \ReflectionClass($class);
            $Block = $ref->newInstanceArgs([$this, $this->engine, $args]);
            return $Block->render();
        } else {
            trigger_error("Tried to rended and undefined / reachable block [".$path."]", E_PLAT_WARNING);
        }
    }

    public function render_template($name, array $args = []) {
        return $this->engine->render($name, $args);
    }
}
