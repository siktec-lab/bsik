<?php

namespace Bsik\Module;

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
if (!defined('ROOT_PATH')) define("ROOT_PATH", dirname(__FILE__).DS."..".DS.".." );

/******************************  Requires       *****************************/
require_once PLAT_PATH_AUTOLOAD;

use \Bsik\Std;
use \Exception;
use \ZipArchive;
use \Bsik\Base;
use \Bsik\FsTools\BsikZip;
use \Bsik\FsTools\BsikFileSystem;
use \Bsik\Module\Schema;
use \SplFileInfo;
use \Throwable;

/*********************  Load Conf and DataBase  *****************************/
if (!isset(Base::$db)) {
    Base::configure($conf);
    Base::connect_db();
}

/********************** Installer *******************************************/
class ModuleInstaller {

    //Path and extraction related:
    private const           TEMP_FOLDER_PREFIX  = "bsik_m_temp_";
    private string          $rand_id            = "";
    private string          $temp_folder_path   = "";  
    private ?SplFileInfo    $install_in        = null;
    public  ?SplFileInfo    $temp_extracted    = null;
    private ?SplFileInfo    $source            = null;
    public  ZipArchive      $zip;

    //Validation related
    public const REQUIRED_FILES = [
        "module.jsonc" => "jsonc",
        "module.php"   => "exists"
    ];

    //Installation related:
    private string $schema_install_template = "module.install.%s.jsonc";
    private string $schema_module_template  = "module.define.%s.jsonc";

    /** 
     * Construct ModuleInstaller
     * 
     * @param string $source => path to archive on server.
     * @param string $in   => path to default extract destination folder.
     * @throws Exception => E_PLAT_ERROR on zip cant be opened.
     * @return ModuleInstaller
     */
    public function __construct(
        string|SplFileInfo $source, 
        string|SplFileInfo $in = PLAT_PATH_MANAGE.DIRECTORY_SEPARATOR."modules"
    ) {
        $this->source           = is_string($source) ? new SplFileInfo($source) : $source;
        $this->install_in       = is_string($in) ? new SplFileInfo($in) : $in;
        $this->rand_id          = std::$date::time_datetime("YmdHis");
        $this->temp_folder_path = Std::$fs::path($this->install_in->getRealPath(), self::TEMP_FOLDER_PREFIX.$this->rand_id);
        $this->zip              = BsikZip::open_zip($this->source->getRealPath() ?: "");
    }

    /** 
     * validate_required_files_in_zip - validate an zip in module archive.
     * 
     * @return array => array of errors message if any
     */
    public function validate_required_files_in_zip() : array {
        $errors = [];
        if ($this->zip->filename && $this->zip->status === ZipArchive::ER_OK) {
            //List the files in zip
            $list = BsikZip::list_files($this->zip);
            //Validate required - simple validation just of presence and format:
            foreach (self::REQUIRED_FILES as $file => $validate) {
                if (array_key_exists($file, $list)) {
                    $content = $this->zip->getFromIndex($list[$file]["index"]);
                    if (!$this->validate_file("", $validate, $content)) {
                        $errors[] = "File is invalid [{$file}] expected [{$validate}]";
                    }
                } else {
                    $errors[] = "Required file missing [{$file}]";
                }
            }
        } else {
            $errors[] = "zip archive not loaded";
        }
        return $errors;
    }

    /** 
     * validate_required_files_in_extracted - validate an extracted zip in folder.
     * @param  mixed $folder => null for loaded, string for given folder path 
     * @return array         => array of errors message if any
     */
    public function validate_required_files_in_extracted($folder = null) : array {
        $folder = $folder ?? $this->temp_extracted->getRealPath();
        $errors = [];
        foreach (self::REQUIRED_FILES as $file => $validate) {
            if (!Std::$fs::path_exists($folder, $file)) {
                $errors[] = "Required file missing [{$file}]";
                continue;
            }
            if (!$this->validate_file(Std::$fs::path($folder, $file), $validate)) {
                $errors[] = "File is invalid [{$file}] expected [{$validate}]";
            }
        }
        return $errors;
    }

    /** 
     * validate_file - validate an a file that is required by its type.
     * @param  string $path             => the file path will be used only if $content is null. 
     * @param  string $validate_type    => the file content validation required (json, jsonc etc.)  
     * @param  mixed $content           => null for load from path or the file content
     * @return bool                     => true on valid
     */
    public function validate_file(string $path, string $validate_type, ?string $content = null) : bool {
        if (is_null($content)) {
            $content = file_get_contents($path) ?? "";
        }
        switch ($validate_type) {
            case "jsonc": {
                $json = Std::$str::strip_comments($content);
                if (!Std::$str::is_json($json)) {
                    return false;
                }
            } break; 
            case "json": {
                $json = $content;
                if (!Std::$str::is_json($json)) {
                    return false;
                }
            } break;
        }
        return true;
    }

    /** 
     * close_zip - close and release a loaded zip file
     * safe to use even when not opened
     * @return void
     */
    public function close_zip() {
        try {
            $this->zip->close();
        } catch (Throwable) { ; }
    }

    /** 
     * temp_deploy - extract the loaded zip archive to a folder
     * 
     * @param   bool $close_after - whether to close the zip file or not.
     * @return  bool true when success
     */
    public function temp_deploy(bool $close_after = false) : bool {
        if ($result = BsikZip::extract_zip($this->zip, $this->temp_folder_path)) {
            $this->temp_extracted = new SplFileInfo($this->temp_folder_path);
        } 
        if ($close_after) {
            $this->close_zip();
        }
        return $result;
    }
    
    /**
     * clean - will clear an remove the temp folder if its there
     * 
     * @return bool - true if temp was cleared false if no temp folder.
     */
    public function clean() : bool {
        if ($this->temp_extracted && $path = $this->temp_extracted->getRealPath()) {
            return BsikFileSystem::clear_folder($path, true);
        }
        return false;
    }
    
    public function install($by = null, ?string $from = null) : array {

        $from = $from ?? $this->temp_extracted->getRealPath();
        $installed = [];
        
        //Load module.jsonc:
        $module_json = Std::$fs::get_json_file(
            Std::$fs::path($from, "module.jsonc")
        );

        //Check we got a valid schema.jsonc
        if (empty($module_json)) {
            return [false, "module.jsonc is missing or corrupted", []];
        }
        if (empty($module_json["schema"] ?? "")) {
            return [false, "module.jsonc is missing schema version definition", []];
        }
        
        //Load schema:
        $schema = new Schema\ModuleSchema("install", $module_json["schema"]);
        if (!$schema->is_loaded()) {
            return [false, $schema->get_message(), []];
        }
        
        //Create the given definition and validate:
        $module_def = $schema->create_definition($module_json);
        if (!$module_def->valid) {
            return [false, "module.jsonc is invalid", $module_def->struct]; //TODO: remove struct
        }
        return [true, "installed", $module_def->struct];
        
        // //Extend:
        // $module_def = Std::$arr::extend($schema->obj->data, $module_def);

        // //Required:
        // if (!$schema->check($module_def)) {
        //     return [false, "module.jsonc is missing some required properties", []];
        // }
        
        // //Install module depends on the type:
        // switch ($module_def["this"]["type"]) {
        //     case "single" : {
        //         $_path   = rtrim($this->install_in, "/\\").DS.$folder;
        //         $_module = $module_def[$schema->naming('modules_container')][0];
        //         [$status, $module_name, $message] = $this->install_module($module_def, $_module, $_path, $by);
        //         if (!$status) {
        //             return [false, $message, $installed];
        //         } else {
        //             $installed[] = $module_name;
        //         }
        //     } break;
        //     case "bundle" : {
        //         return [false, "bundle are not supported yet", []];
        //     } break;
        // }
        
        //Return
        return [true, "installed", $installed];
    }

    private function install_module(array $definition, array $module, string $folder, $by = null) : array {
        $module_name = Std::$str::filter_string($module["name"] ?? "unknown", ["A-Z","a-z","0-9", "_"]);
        $module_path = trim($folder, " /\\").DS.$module_name;

        // //Validate name:
        // if (Base::$db->where("name", $module_name)->has("bsik_modules")) {
        //     return [false, $module_name, "allready installed"];
        // }

        // //Parse schema module:
        // if (empty($module["schema"] ?? "")) {
        //     return [false,$module_name,"schema not defined"];
        // }

        // //Load schema:
        // $schema = new Schema\ModuleSchema("module", $module["schema"]);
        // if (!$schema->obj->status) {
        //     return [false,$module_name,$schema->obj->message];
        // }

        // //Extend:
        // $module = Std::$arr::extend($schema->obj->data, $module);

        // //Check Required files:
        // if (!$schema->check($module)) {
        //     return [false,$module_name,"module definition is invalid"];
        // }

        // //Move module folder:
        // if (!BsikFileSystem::xcopy($module_path, rtrim($this->install_in, "/\\").DS.$module_name)) {
        //     return [false,$module_name,"failed to copy module to destination"];
        // }

        // //Register to db:
        // if (!Base::$db->insert("bsik_modules", [
        //     "name"          => $module_name,
        //     "status"        => 0,
        //     "updates"       => 0,
        //     "priv_users"    => 0,
        //     "priv_content"  => 0,
        //     "priv_admin"    => 0,
        //     "priv_install"  => 0,
        //     "path"          => $module_name.'/',
        //     "settings"      => json_encode($module[$schema->naming("settings_container")]),
        //     "defaults"      => json_encode($module[$schema->naming("defaults_container")]),
        //     "menu"          => json_encode($module[$schema->naming("menu_container")]),
        //     "version"       => $module["ver"],
        //     "created"       => Base::$db->now(),
        //     "updated"       => Base::$db->now(),
        //     "info"          => json_encode($module),
        //     "installed_by"  => $by,
        // ])) {
        //     return [false, $module_name, "failed to register module to database"];
        // };
        return [true,$module_name,"module installed"];
    }
}