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
require_once "Trace.class.php";
require_once "Base.class.php";

class Page extends Base
{
    private $types = array("page","api");
    public $request =  array(
        "type"      => "",      // page, api
        "page"      => "",
        "when"      => ""
    );
    public $token =  array(
        "csrf" => "",
        "meta" => ""
    );

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
        "icon"                  => ""
    );
    public $additional_meta = array();
    private $custom_body_tag = "";
    private $storage = array();

    //Page loaded values:
    public $definition = [];
    public $settings = []; // Holds merged definition settings
    /* Page constructor.
     *  @param $conf => SIK configuration array Used in Base Parent
     *  @Default-params: none
     *  @return none
     *  @Examples:
    */
    public function __construct()
    {
        $this->tokenize(); //Tokenize the page.
        $this->request["type"] = $this->request_type(); //Get the request 
        $this->request["page"] = $this->request_page($this::$conf["default-page"] ?? "");
        $this::$index_page_url = $this->parse_slash_url_with($this::$conf["path"]["site_base_url"]);
        $this->request["when"] = self::$std::time_datetime(); //Time stamp for debugging
        //$this->head_meta = self::array_extend($this->head_meta, $this::$conf["page"]["meta"]); //Sets the ,eta global defaults
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
    private function request_page(string $default)
    {
        // TODO: Log the requests given to the server.
        $page = (isset($_REQUEST["page"]) && ctype_alnum($_REQUEST["page"])) ? $_REQUEST["page"] : $default;
        return self::$std::str_filter_string($page, "A-Za-z0-9_-");
    }
        
    /**
     * load_page
     * load the page related definition and structure
     * 
     * @return bool
     */
    public function load_page() : bool {
        if ($this->request["page"]) {
            $cols = [
                "pages.*",
                "t.name as template_name",
                "t.libs as default_libs",
                "t.global as default_settings",
                "ty.id as type_id",
                "ty.name as type_name",
                "l.name as layout_name",
                "l.variables as default_layout_vars",
                "l.file as layout_file"
            ];
            $this->definition = self::$db->where("pages.name", $this->request["page"])
                                         ->join("templates as t", "t.id = pages.template", "LEFT")
                                         ->join("page_layout as l", "l.id = pages.layout", "LEFT")
                                         ->join("page_types as ty", "ty.id = pages.type", "LEFT")
                                         ->getOne("pages", $cols);
            //Parse settings if set:
            if (!empty($this->definition)) {
                if (isset($this->definition["default_settings"]))
                    $this->definition["default_settings"] = json_decode($this->definition["default_settings"], true);
                else
                    $this->definition["default_settings"] = [];
                if (isset($this->definition["settings"]))
                    $this->definition["settings"] = json_decode($this->definition["settings"], true); 
                else
                    $this->definition["settings"] = [];
                //Merge defined -> extends default settings:
                $this->settings = array_merge($this->definition["default_settings"], $this->definition["settings"]);
            }
        }
        return !empty($this->definition);
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
        $version = ltrim($lib[1], " +=");
        return [
            "name" => $lib[0],
            "cond" => substr($lib[1] ?? "=", 0, 1),
            "version" => $version,
            "sig" => intval(explode(".", $version)[0] ?? 0),
            "pos" => $pos
        ];
    }
    /**
     * load_json_libs - loads cms based define libs that are stored as special json object
     * 
     * @param  string $libs_json -> the json representation
     * @return void
     */
    private function load_json_libs(string $libs_json) {
        //Parse lib json object to array:
        $def_lib = json_decode($libs_json, true);
        //Parse each lib:
        foreach ($def_lib as $key => $inpos_lib) {
            $pos = $key;
            foreach ($inpos_lib as $type_libs) {
                $type = $type_libs["type"];
                foreach ($type_libs["libs"] as $lib) {
                    if (self::$std::str_starts_with($lib,"//") || self::$std::str_starts_with($lib,"http")) {
                        $this->static_links_counter++;
                        $this->lib_toload[$type]["link".$this->static_links_counter] = ["name" => $lib, "pos" => $pos];
                    } else {
                        $lib_obj = self::parse_lib_query($lib, $pos);
                        if ($this->lib_toload[$type][$lib_obj["name"]] ?? false) {
                            if ($lib_obj["cond"] === "=" && $lib_obj["version"] !== $this->lib_toload[$type][$lib_obj["name"]]["version"]) {
                                trigger_error(
                                    sprintf("Lib `%s` require version `%s` but version `%s` loaded first", $lib_obj["name"], $lib_obj["version"], $this->lib_toload[$type][$lib_obj["name"]]["version"]), 
                                    E_USER_WARNING
                                );
                            } elseif ($lib_obj["cond"] === "+" && $lib_obj["sig"] <  $this->lib_toload[$type][$lib_obj["name"]]["sig"]) {
                                trigger_error(
                                    sprintf("Lib `%s` require at least version `%s` but version `%s` loaded first", $lib_obj["name"], $lib_obj["sig"], $this->lib_toload[$type][$lib_obj["name"]]["version"]), 
                                    E_USER_WARNING
                                );
                            }
                        } else {
                            $this->lib_toload[$type][$lib_obj["name"]] = $lib_obj;
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
    public function load_libs(bool $template, bool $page) : int {
        
        //Build definitions:
        if ($template && isset($this->definition["default_libs"])) 
            $this->load_json_libs($this->definition["default_libs"]);
        if ($page && isset($this->definition["libs"])) 
            $this->load_json_libs($this->definition["libs"]);
    
        //Add via include method:
        foreach($this->lib_toload as $type => $libs)
            foreach($libs as $name => $set)
                $this->include($set["pos"], $type, $name, $set);
    
        return count($this->lib_toload["css"]) + count($this->lib_toload["js"]);
    }
    
    /**
     * import_defined_libs - import installed libs from db 
     *
     * @return array - return the libs not found
     */
    public function import_defined_libs() : array {
        $import = [];
        $not_found = [];
        //Build required:
        foreach($this->includes as $pos => $types)
            foreach($types as $type => $libs)
                foreach($libs as $lib)
                    if ($lib["name"] !== "link" && $lib["name"] !== "path")
                        $import[strtolower($lib["name"].$lib["path"])] = "";
        //Create query conditions:
        self::$db->where("concat_ws('',name,version)", array_keys($import) , "IN");
        $data = self::$db->map('libname')->get("libs", null, ["concat_ws('',name,version) as libname","js","css"]);
        //Parse :
        foreach($data as &$lib) {
            $lib["js"] = json_decode($lib["js"], true);
            usort($lib["js"], fn($a, $b) => $a["order"] <=> $b["order"] );
            $lib["css"] = json_decode($lib["css"], true);
            usort($lib["css"], fn($a, $b) => $a["order"] <=> $b["order"] );
        }
        //Fill the data or removed undefined:
        foreach($this->includes as $pos => &$types) 
            foreach($types as $type => &$libs)
                foreach($libs as $key => &$lib)
                    if ($lib["name"] !== "link" && $lib["name"] !== "path")
                        if (isset($data[strtolower($lib["name"].$lib["path"])])) {
                            $base = PLAT_URL_BASE."/lib/import/".strtolower($lib["name"].".".$lib["path"])."/".$type."/";
                            $lib["path"] = array_map(
                                fn($a) => $base.$a['file'], 
                                $data[strtolower($lib["name"].$lib["path"])][$type]
                            );
                        } else {
                            $not_found[] = strtolower($lib["name"].$lib["path"]);
                            unset($libs[$key]);
                        }
        return $not_found;
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
        
    /**
     * render_libs
     * render libs that are included in all the different methods
     * @param  mixed $type
     * @param  mixed $pos
     * @return void
     */
    public function render_libs(string $type, string $pos) {
        $tpl = [
            "css" => '<link rel="stylesheet" href="%s" />'.PHP_EOL,
            "js"  => '<script type="text/javascript" src="%s"></script>'.PHP_EOL
        ];
        $use = $tpl[$type];
        foreach ($this->includes[$pos][$type] ?? [] as $lib) {
            if (is_array($lib["path"])) {
                array_walk($lib["path"], fn($p) => printf($use, $p));
            } else {
                printf($use, $lib["path"]);
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
    /* SH: added - 2021-03-12 => Attach to template or page variables */
    public function render_favicon(string $path, string $name = "favicon") {
        $tpl = '<link rel="apple-touch-icon" sizes="180x180" href="%1$s/apple-touch-icon.png">'.
        '<link rel="icon" type="image/png" sizes="32x32" href="%1$s/%2$s-32x32.png">'.
        '<link rel="icon" type="image/png" sizes="16x16" href="%1$s/%2$s-16x16.png">'.
        '<link rel="manifest" href="%1$s/site.webmanifest">';
        printf($tpl, $path, $name);
    }
}