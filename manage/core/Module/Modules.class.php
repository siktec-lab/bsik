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

namespace Bsik\Module;

require_once BSIK_AUTOLOAD;

use \Bsik\DB\MysqliDb;
use \Bsik\Std;
use \Exception;
use \Bsik\Privileges as Priv;
use \Bsik\Privileges\RequiredPrivileges;
use \Bsik\Objects\SettingsObject;
use \Bsik\Api\AdminApi;
use \Bsik\Render\APage;
use \Bsik\Users\User;
use \Bsik\Render\AModuleRequest;

class ModuleView {

    public string $name;
    public Priv\RequiredPrivileges $priv;
    public SettingsObject $settings;
    public ?\Closure $render = null;
    
    public function __construct(
        string $name,
        ?Priv\RequiredPrivileges $privileges = null,
        ?SettingsObject $settings = null,
    ) {
        
        //Set legal view name:
        $this->name = Std::$str::filter_string($name, AModuleRequest::$which_pattern);

        //Privileges:
        $this->priv = $privileges ?? new Priv\RequiredPrivileges();

        //Settings:
        $this->settings = $settings ?? new SettingsObject();

    }

}

class ModuleEvent {
    /**
     * Module events name : 
     *  me-install, 
     *  me-uninstall, 
     *  me-activate, 
     *  me-deactivate, 
     *  me-update, 
     *  core-update,
     *  other-install,
     *  other-uninstall,
     *  other-activate,
     *  other-deactivate,
     *  other-update,
     *  signal-module,
     *  signal-core
     */
    public array $on = []; 
    public ?\Closure $method = null; // All methods has first arg Event_name string....
    public function __construct(
        array $on_events = [],
        ?\Closure $event_method = null
    ) {
        //Set legal view name:
        $this->on = $on_events;
        $this->method = $event_method;
    }
}

class Module {

    public  string $module_name = "";
    public  ?ModuleView $current_view = null;
    private string $default_view = "";
    private array  $views = [];
    public  SettingsObject $settings;

    public ?Priv\RequiredPrivileges $priv = null;
    
    //Holds defined module events:
    public array $module_events = [];

    //Additional data from installation data - dynamically loaded:
    public string $version   = "";   // The installed module version:
    public string $path      = "";   // The path to the module folder
    public array  $paths     = [     // Some usefull paths of parts in the module: 
        "module"            => "",
        "module-api"        => "",
        "module-blocks"     => "",
        "module-templates"  => "",
        "module-lib"        => "",
        "module-includes"   => ""
    ];
    public string $url      = "";   // The url to the module folder
    public array  $urls     = [     // Some usefull paths of parts in the module: 
        "module"            => "",
        "module-api"        => "",
        "module-lib"        => ""
    ];
    public string $which    = "";   // Requested view to load
    public array  $menu     = [];   // the installed menu entry
    public ?AdminApi $api   = null; // a reference to the use api object
    public ?APage $page     = null; // a reference to the use api object
    public ?MysqliDb $db    = null; //Reference to a $db connection dynamically assigned
    public ?User $user      = null;

    /**
     * __construct
     * @param string $name                      - the module name
     * @param RequiredPrivileges $privileges    - the module level required privileges
     * @param array $views                      - expected views names that are allowed in this module
     * @param string $default                   - default view name to be loaded if none is requested
     */
    public function __construct(
        string $name, 
        ?Priv\RequiredPrivileges $privileges = null,
        array $views         = [],
        string $default_view = "",
        ?SettingsObject $settings = null
    ) {

        $this->module_name = $name;
        $this->priv = $privileges ?? new RequiredPrivileges();
        $this->define_views($views, $default_view);
        $this->settings = $settings ?? new SettingsObject();
    
    }

    public function load(
        array $data     = [], 
        ?MysqliDb $DB   = null, 
        ?AdminApi $Api  = null, 
        ?APage $Page    = null, 
        ?User $User     = null
    ) {

        $this->version = $data["version"] ?? "";
        $this->which   = $data["which"] ?? "";
        $this->menu    = json_decode(($data["menu"] ?? "{}"), true);
        $this->db      = $DB;
        $this->api     = $Api;
        $this->page    = $Page;
        $this->user    = $User;

        //Set paths:
        $raw_path = $data["path"] ?? "";
        $folder             = Std::$fs::path_to("modules", $raw_path);
        $module_api         = Std::$fs::path_to("modules", [$raw_path, "module-api.php"]);
        $module_blocks      = Std::$fs::path_to("modules", [$raw_path, "blocks"]);
        $module_templates   = Std::$fs::path_to("modules", [$raw_path, "templates"]);
        $module_lib         = Std::$fs::path_to("modules", [$raw_path, "lib"]);
        $module_includes    = Std::$fs::path_to("modules", [$raw_path, "includes"]);
        $this->path = $folder["path"];
        $this->paths["module"]              = $this->path;
        $this->paths["module-api"]          = $module_api["path"];
        $this->paths["module-blocks"]       = $module_blocks["path"];
        $this->paths["module-templates"]    = $module_templates["path"];
        $this->paths["module-lib"]          = $module_lib["path"];
        $this->paths["module-includes"]     = $module_includes["path"];
        $this->url = $folder["url"];
        $this->urls["module"]              = $this->url;
        $this->urls["module-api"]          = $module_api["url"];
        $this->urls["module-lib"]          = $module_lib["url"];

        //Set requested view:
        $this->set_current_view($this->which);

    }

    public function set_current_view(string $view_name = "") : ModuleView {
        $name = !empty($view_name) && $view_name !== "default" ? $view_name : $this->default_view;
        $this->current_view = $this->view($name);
        return $this->current_view;
    }

    public function define_views(array $views, string $default = "") : void {
        $this->views = array_fill_keys($views, null);
        $this->default_view = $default;
    }

    public function register_view(
        ?ModuleView $view = null,
        callable $render = null
    ) : void {
        //Early out?
        if (is_null($view)) return;
        //check we can register:
        if (!array_key_exists($view->name, $this->views) || !is_callable($render)) {
            throw new Exception("Trying to register an undefined / uncallable view [{$view->name}] in module [{$this->module_name}]", E_PLAT_ERROR);
        }
        //Extend parent module privileges if it has specific privileges:
        $view->priv->extends($this->priv);
        //Set closure:
        $view->render = \Closure::bind(\Closure::fromCallable($render), $this);
        //Register:
        $this->views[$view->name] = $view;
    }

    public function register_event(array $on_events = [], ?\Closure $event_method = null) : void {
        $this->register_event_object(new ModuleEvent(
            $on_events, 
            \Closure::bind(\Closure::fromCallable($event_method), $this)
        ));
    }

    public function register_event_object(?ModuleEvent $event = null) : void {
        //Early out?
        if (is_null($event)) return;
        //Save events:
        $this->module_events[] = $event;
    }

    public function get_event(string $event_name) : ?ModuleEvent {
        //Early out?
        if (empty($this->module_events)) return null;
        //Loop and find event:
        foreach ($this->module_events as $module_event) {
            /** @var ModuleEvent $module_event */
            if (in_array($event_name, $module_event->on) && is_callable($module_event->method)) {
                return $module_event;
            }
        }
        return null;
    }

    public function exec_event(string $event_name, ...$args) : bool {
        $event = $this->get_event($event_name);
        if (!is_null($event)) {
            //We are trying to suppress all errors to make sure we are not killing the process:
            $try_exec = null;
            $try_mes  = "unknown";
            try {
                $try_exec = @call_user_func_array($event->method, [ $event_name, ...$args]);
            } catch(\Error $e) {
                $try_exec = false;
                $try_mes  = $e->getMessage();
            }
            if ($try_exec === false) {
                //This means we have a problem executing the method event log it:
                $this->page::log(
                    "error", 
                    "module event ['{$event_name}'] execution failed",
                    ["module" => $this->module_name, "error" => $try_mes]
                );
            }
            return true;
        }
        return false;
    }

    public function view(string $name) : ModuleView {
        if (!array_key_exists($name, $this->views) || empty($this->views[$name])) {
            throw new Exception("Trying to render undefined view [{$name}] in module", E_PLAT_ERROR);
        }
        return $this->views[$name];
    }

    public function render(string $view_name = "", array $args = [], ?Priv\PrivDefinition $issuer = null) : array {

        //Get and Set current view only if needed get loaded one:
        if (!empty($view_name)) {
            $this->set_current_view($view_name);
        }

        //Check privileges: note that those view privileges are extended from module:
        $priv_messages = [];
        if (!$this->current_view->priv->has_privileges($issuer, $priv_messages)) {
            return [
                false, 
                sprintf("More privileges are required : %s", 
                        "<br />&emsp;->&nbsp;".implode("<br />&emsp;->&nbsp;", $priv_messages)
                )
            ];
        }
        //execute:
        return [true, call_user_func_array($this->current_view->render, $args)];
    }

}



class Modules {
    
    private static  $installed  = []; //This holds the installed modules from db as array definitions

    private static $registered = []; //This holds loaded modules from code. those are objects
    
    private static ?MysqliDb $db; //Reference to a $db connection

    /**
     * init - loads currently installed modules
     *
     * @return instance - the number of installed modules
     */
    public static function init(?MysqliDb $db = null) {

        //Set db connection:
        self::$db = $db;

        //load modules from db and store the info about them:
        if (!is_null($db)) {
            self::$installed = self::$db->where("status", 0, "<>")->map("name")->arrayBuilder()->get("bsik_modules");
        }

        return new self();
    }

    /**
     * register
     * register a module object
     * @param  mixed $module
     * @throws Exception if module is not a Module instance
     * @return void
     */    
    public static function register(mixed $module) : void {
        //Make sure its callable:
        if (!is_object($module) || !$module instanceof Module) {
            throw new Exception("Trying to register a non callable module", E_PLAT_ERROR);
        }

        //Make sure its installed or skip this one:
        // if (self::is_installed($module->module_name)) {
        //     return;
        // }

        //Extend settings:
        $extend_settings_messages = []; 
        $module->settings->extend(
            self::$installed[$module->module_name]["settings"] ?? [], 
            $extend_settings_messages
        );

        //Save reference:
        self::$registered[$module->module_name] = $module;
    }
        
    /**
     * register_module_once
     * register a module object only if its a new name.
     * @param  mixed $module
     * @throws Exception if module is not a Module instance
     * @return void
     */
    public static function register_module_once(mixed $module) : void {

        //Make sure its an object and a module:
        if (!is_object($module) || !$module instanceof Module) {
            throw new Exception("Trying to register a non callable module", E_PLAT_ERROR);
        }

        //Check if its allready registered:
        if (self::is_registered($module->module_name)) {
            return;
        }

        //Register:
        self::register($module);
    }

    /**
     * loads a module code which will register itself.
     * @param  string $module
     * @throws Exception if module has errors or if the path is not reachable
     * @return bool
     */
    public static function load_module(string $module_name) : bool {

        //If its allready registered:
        if (self::is_registered($module_name)) {
            return true;
        }
        //Make sure its an installed module:
        if (self::is_installed($module_name)) {
            $module_installed = self::module_installed($module_name);
            $module_file = Std::$fs::path_to("modules", [$module_installed["path"], "module.php"]);
            //Require module
            if (file_exists($module_file["path"])) {
                try {
                    //Load module & views:
                    require $module_file["path"];
                    return true;
                } catch (\Throwable $e) {
                    throw new Exception("Internal Error captured on module load [{$e->getMessage()}].", E_PLAT_ERROR, $e);
                }
            } else {
                throw new Exception("Could not find module file to load.", E_PLAT_ERROR);
            }
        }
        return false;
    }
    /**
     * initiata a registered module.
     * @param  string $module
     * @param  string $view => the active view to set - empty string for default.
     * @param  ?MysqliDb $db
     * @param  ?AdminApi $Api
     * @param  ?APage $Page
     * @param  ?User $User
     * @throws Exception from self::load_module
     * @return ?Module
     */
    public static function initiate_module(string $module_name, string $view, ?MysqliDb $db = null, ?AdminApi $Api = null, ?APage $Page = null, ?User $User = null) : ?Module {

        $load = self::load_module($module_name);
        if ($load) {
            $data = self::module_installed($module_name);
            $data["which"] = $view;
            $module = self::module($module_name);
            if ($module) {
                $module->load(
                    data:   $data, 
                    DB:     $db,
                    Api:    $Api, 
                    Page:   $Page, 
                    User:   $User
                );
            }
            return $module;
        }
        return null;
    }
    
    /**
     * is_installed @Alias of installed
     * check if module name is installed
     * @param  string $name
     * @return bool
     */
    public static function is_installed(string $module_name) : bool {
        return array_key_exists($module_name, self::$installed);
    }

    /**
     * is_registered @Alias of registered
     * check if module name is registered
     * @param  string $name
     * @return bool
     */
    public static function is_registered(string $module_name) : bool {
        return array_key_exists($module_name, self::$registered);
    }

    /**
     * installed
     * check if module name is installed
     * @param  string $name
     * @return bool
     */
    public static function installed(string $name) : bool {
        return self::is_installed($name);
    }
    
    /**
     * registered
     * check if module name is registered
     * @param  string $name
     * @return bool
     */
    public static function registered(string $name) : bool {
            return self::is_registered($name);
    }

    
    /**
     * module_installed
     * returns the array of the installation definition of the modules.
     * holds only active modules.
     * @param  string $name
     * @return array
     */
    public static function module_installed(string $name) : array {
        if (self::installed($name)) {
            return self::$installed[$name];
        }
        return [];
    }    
    
    /**
     * module
     * return the module Object that was registered.
     * @param string $name
     * @return Module
     */
    public static function module(string $name) : Module|null {
        if (self::registered($name)) {
            return self::$registered[$name];
        }
        return null;
    }
    
    /**
     * get_all_installed
     * returns an array of all installed module names.
     * @return array
     */
    public static function get_all_installed() : array {
        return array_keys(self::$installed);
    }

    /**
     * get_all_registered
     * returns an array of all registered module names.
     * @return array
     */
    public static function get_all_registered() : array {
        return array_keys(self::$registered);
    }
}