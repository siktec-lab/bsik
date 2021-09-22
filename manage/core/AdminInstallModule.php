<?php

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
if (!defined('ROOT_PATH')) define("ROOT_PATH", dirname(__FILE__).DS."..".DS.".." );

/******************************  Requires       *****************************/
require_once ROOT_PATH.DS.'conf.php';
require_once PLAT_PATH_VENDOR.DS.'autoload.php';
require_once PLAT_PATH_CORE.DS.'Base.class.php';

/*********************  Load Conf and DataBase  *****************************/
if (!isset(Base::$db)) {
    Base::configure($conf);
    Base::connect_db();
}


class SchemaLoader extends Base {

    private array $schemas = [
        "install" => [
            "template" => "module.install.%s.jsonc"
        ],
        "module"  => [
            "template" => "module.define.%s.jsonc"
        ],
    ];
    private string $type      = "";
    private string $version   = "1.0.0";
    private string $file_name = "";
    public $obj;
    public function __construct(string $_type, string $_version) {
        if (isset($this->schemas[$_type])) {
            $this->type         = $_type;
            $this->version      = $_version;
            $this->file_name    = sprintf($this->schemas[$_type]["template"], $this->version);
            $this->obj       = $this->get();
        } else {
            throw new Exception("Schema type is invalid [{$_type}]", E_PLAT_ERROR);
        }
    }

    public function get() {

        $ret = (object)[ 
            "status"    => true,
            "message"   => "Schema loaded",
            "data"    => null,
        ];

        $schema_path = self::$std::fs_file_exists("schema", [$this->file_name]);
        
        if ($schema_path === false) {
            $ret->code = false;
            $ret->message = "Schema version is not supported";
            return $ret;
        }
        
        $ret->data = json_decode(
            self::$std::str_strip_comments(
                file_get_contents($schema_path["path"]) ?? "")
        , true);

        if (empty($ret->data)) {
            $ret->code = false;
            $ret->message = "Schema is corrupted";
            return $ret;
        }

        return $ret;
    }

    public function naming(string $naming) {
        return $this->obj->data['$schema_naming'][$naming] ?? "";
    }
    public function check(array $json) : bool {
        return self::$std::arr_validate($this->obj->data['$schema_required'], $json);
    }
}

/********************** Installer *******************************************/
class ModuleInstaller extends Base {


    //Path and extraction related:
    private const TEMP_FOLDER           = "bsik_m_temp_";
    private string $temp_stamp          = "";  
    private string $install_in          = "";
    public string $temp_extracted_path  = "";
    private string $path                = "";
    public ZipArchive $zip;

    //Validation related
    public const REQUIRED_FILES = [
        "module.jsonc" => "jsonc"
    ];
    //Installation related:
    private string $schema_install_template = "module.install.%s.jsonc";
    private string $schema_module_template  = "module.define.%s.jsonc";

    /** 
     * Construct ModuleInstaller
     * 
     * @param string $_path => path to archive on server.
     * @param string $_in   => path to default extract destination folder.
     * @return ModuleInstaller
     */
    public function __construct(string $_path, string $_in = PLAT_PATH_MANAGE.DS."modules")
    {
        $this->path         = $_path;
        $this->install_in   = $_in;
        $this->temp_stamp   = "".time();
        $this->zip          = new ZipArchive(); 
    }
    /** 
     * open_zip - loads the archive into memory
     * 
     * @throws Exception => E_PLAT_ERROR on zip cant be opened.
     * @return bool => true on loaded
     */
    public function open_zip() : bool {
        $result = $this->zip->open($this->path);
        if ($result !== true) {
            $this->close_zip();
            throw new Exception("Module zip file cant be opened [{$this->ZipStatusString($result)}]", E_PLAT_ERROR);
        }
        return true;
    }
    /** 
     * list_files - list all files with name and index - keys are unique full path.
     * 
     * @return array => empty on zip not loaded
     */
    public function list_files() : array {
        $list = [];
        if (isset($this->zip)) {
            //List the files in zip:
            for ($i = 0; $i < $this->zip->numFiles; $i++) {
                $stat = $this->zip->statIndex($i);
                $list[$stat['name']] = [
                    "name" => basename($stat['name']),
                    "index" => $i
                ];
            }
        }
        return $list;
    }
    /** 
     * validate_required_files_in_zip - validate an zip in module archive.
     * 
     * @return array => array of errors message if any
     */
    public function validate_required_files_in_zip() : array {
        $errors = [];
        if (!isset($this->zip)) {
            $errors[] = "zip archive not loaded";
        } else {
            //List the files in zip:
            $list = $this->list_files();
            //Validate required:
            foreach (self::REQUIRED_FILES as $file => $validate) {
                if (isset($list[$file])) {
                    $content = $this->zip->getFromIndex($list[$file]["index"]);
                    if (!$this->validate_file("", $validate, $content)) {
                        $errors[] = "File is invalid [{$file}] expected [{$validate}]";
                    }
                } else {
                    $errors[] = "Required file missing [{$file}]";
                }
            }
        }
        return $errors;
    }
    /** 
     * validate_required_files_in_extracted - validate an extracted zip in folder.
     * @param  mixed $folder => null for loaded, string for given folder path 
     * @return array         => array of errors message if any
     */
    public function validate_required_files_in_extracted($folder = null) : array {
        $folder = $folder ?? self::TEMP_FOLDER.$this->temp_stamp;
        $errors = [];
        foreach (self::REQUIRED_FILES as $file => $validate) {
            $file_path = self::$std::fs_file_exists("modules", [
                $folder,
                ...explode("/", $file)
            ]);
            if (!$file_path) {
                $errors[] = "Required file missing [{$file}]";
                continue;
            }
            if (!$this->validate_file($file_path["path"], $validate)) {
                $errors[] = "File is invalid [{$file}] expected [{$validate}]";
                continue;
            }
        }
        return $errors;
    }
    /** 
     * validate_file - validate an a file that is required by its type.
     * @param  string $path             => the file path will be used only if $content is null. 
     * @param  string $validate_type    => the file content validation required (json, jsonc etc.)  
     * @param  mixed $content           => null for load from path os the file content
     * @return bool                     => true on valid
     */
    public function validate_file(string $path, string $validate_type, $content = null) : bool {
        if (is_null($content)) {
            $content = file_get_contents($path) ?? "";
        }
        switch ($validate_type) {
            case "jsonc": {
                $json = self::$std::str_strip_comments($content);
                if (!self::$std::str_is_json($json)) {
                    return false;
                }
            } break; 
            case "json": {
                $json = $content;
                if (!self::$std::str_is_json($json)) {
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
        } catch (Exception $e) { ; }
    }
    /** 
     * extract - extract the loaded zip archive to a folder
     * 
     * @param mixed $to => null to use loaded target or string of the target path.
     * @return string   => the extracted path
     */
    public function extract($to = null) : string {
        $to = $to ?? $this->install_in;
        $to = rtrim($to, "/\\").DS.self::TEMP_FOLDER.$this->temp_stamp;
        if (!$this->zip->extractTo($to)) {
            $this->close_zip();
            throw new Exception("An Error occurred while extracting module zip [unknown]", E_PLAT_ERROR);
        }
        $this->temp_extracted_path = $to;
        return $to;
    }
    /** 
     * get_json_file - parse a json file - uses teh fs_file_exists method
     * 
     * @param string $in    => the core place root folder ex. 'modules'.
     * @param array $path   => packed arguments defining the path from root.
     * @return array        => returned json or empty on error
     */
    private function get_json_file(string $in, ...$path ) : array {
        $file_path = self::$std::fs_file_exists($in, $path);
        if ($file_path === false) {
            return [];
        }
        //Parse json:
        $definition = json_decode(
            self::$std::str_strip_comments(
                file_get_contents($file_path["path"]) ?? "")
        , true);
        if (empty($definition)) {
            return [];
        }
        return $definition;
    }
    public function clean() : void {
        $_path   = rtrim($this->install_in, "/\\").DS.self::TEMP_FOLDER.$this->temp_stamp;
        $this->clear_folder($_path, true);
    }
    public function install($by = null, $folder = null) : array {

        $folder = $folder ?? self::TEMP_FOLDER.$this->temp_stamp;
        $installed = [];
        //Load module.jsonc:
        $module_def = $this->get_json_file("modules", $folder, "module.jsonc");
        if (empty($module_def)) {
            return [false, "module.jsonc is missing or corrupted", []];
        }
        if (empty($module_def["schema"] ?? "")) {
            return [false, "module.jsonc is missing schema version definition", []];
        }
        //Load schema:
        $schema = new SchemaLoader("install", $module_def["schema"]);
        if (!$schema->obj->status) {
            return [false, $schema->obj->message, []];
        }
        //Extend:
        $module_def = self::$std::arr_extend($schema->obj->data, $module_def);
        //Required:
        if (!$schema->check($module_def)) {
            return [false, "module.jsonc is missing some required properties", []];
        }
        //Install module depends on the type:
        switch ($module_def["this"]["type"]) {
            case "single" : {
                $_path   = rtrim($this->install_in, "/\\").DS.$folder;
                $_module = $module_def[$schema->naming('modules_container')][0];
                [$status, $module_name, $message] = $this->install_module($module_def, $_module, $_path, $by);
                if (!$status) {
                    return [false, $message, $installed];
                } else {
                    $installed[] = $module_name;
                }
            } break;
            case "bundle" : {
                return [false, "bundle are not supported yet", []];
            } break;
        }
        //Return
        return [true, "installed", $installed];
    }

    private function install_module(array $definition, array $module, string $folder, $by = null) : array {
        $module_name = self::$std::str_filter_string($module["name"] ?? "unknown", ["A-Z","a-z","0-9", "_"]);
        $module_path = trim($folder, " /\\").DS.$module_name;
        //Validate name:
        if (self::$db->where("name", $module_name)->has("admin_modules")) {
            return [false, $module_name, "allready installed"];
        }
        //Parse schema module:
        if (empty($module["schema"] ?? "")) {
            return [false,$module_name,"schema not defined"];
        }
        //Load schema:
        $schema = new SchemaLoader("module", $module["schema"]);
        if (!$schema->obj->status) {
            return [false,$module_name,$schema->obj->message];
        }
        //Extend:
        $module = self::$std::arr_extend($schema->obj->data, $module);
        //Required:
        if (!$schema->check($module)) {
            return [false,$module_name,"module definition is invalid"];
        }

        //Move module folder:
        if (!$this->xcopy($module_path, rtrim($this->install_in, "/\\").DS.$module_name)) {
            return [false,$module_name,"failed to copy module to destination"];
        }
        //Register to db:
        if (!self::$db->insert("admin_modules", [
            "name"          => $module_name,
            "status"        => 0,
            "priv_users"    => 0,
            "priv_content"  => 0,
            "priv_admin"    => 0,
            "priv_install"  => 0,
            "path"          => $module_name.'/',
            "settings"      => json_encode($module[$schema->naming("settings_container")]),
            "defaults"      => json_encode($module[$schema->naming("defaults_container")]),
            "menu"          => json_encode($module[$schema->naming("menu_container")]),
            "version"       => $module["ver"],
            "created"       => self::$db->now(),
            "updated"       => self::$db->now(),
            "info"          => json_encode($module),
            "installed_by"  => $by,
        ])) {
            return [false, $module_name, "failed to register module to database"];
        };
        return [true,$module_name,"module installed"];
    }

    public function ZipStatusString( $status ) : string {
        switch( (int) $status )
        {
            case ZipArchive::ER_OK           : return 'N No error';
            case ZipArchive::ER_MULTIDISK    : return 'N Multi-disk zip archives not supported';
            case ZipArchive::ER_RENAME       : return 'S Renaming temporary file failed';
            case ZipArchive::ER_CLOSE        : return 'S Closing zip archive failed';
            case ZipArchive::ER_SEEK         : return 'S Seek error';
            case ZipArchive::ER_READ         : return 'S Read error';
            case ZipArchive::ER_WRITE        : return 'S Write error';
            case ZipArchive::ER_CRC          : return 'N CRC error';
            case ZipArchive::ER_ZIPCLOSED    : return 'N Containing zip archive was closed';
            case ZipArchive::ER_NOENT        : return 'N No such file';
            case ZipArchive::ER_EXISTS       : return 'N File already exists';
            case ZipArchive::ER_OPEN         : return 'S Can\'t open file';
            case ZipArchive::ER_TMPOPEN      : return 'S Failure to create temporary file';
            case ZipArchive::ER_ZLIB         : return 'Z Zlib error';
            case ZipArchive::ER_MEMORY       : return 'N Malloc failure';
            case ZipArchive::ER_CHANGED      : return 'N Entry has been changed';
            case ZipArchive::ER_COMPNOTSUPP  : return 'N Compression method not supported';
            case ZipArchive::ER_EOF          : return 'N Premature EOF';
            case ZipArchive::ER_INVAL        : return 'N Invalid argument';
            case ZipArchive::ER_NOZIP        : return 'N Not a zip archive';
            case ZipArchive::ER_INTERNAL     : return 'N Internal error';
            case ZipArchive::ER_INCONS       : return 'N Zip archive inconsistent';
            case ZipArchive::ER_REMOVE       : return 'S Can\'t remove file';
            case ZipArchive::ER_DELETED      : return 'N Entry has been deleted';
            default: return sprintf('Unknown status %s', $status );
        }
    }   
    public function clear_folder(string $dir, bool $self = false) {
        if (!file_exists($dir)) return;
        $di = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ( $ri as $file ) {
            $file->isDir() ?  rmdir($file) : $this->delete_files($file);
        }
        if ($self) {
            rmdir($dir);
        }
    }
    public function delete_files(...$files) {
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
    public function xcopy($source, $dest, $permissions = 0755) 
    {
        $sourceHash = $this->hash_directory($source);
        // Check for symlinks
        if (is_link($source)) {
            return symlink(readlink($source), $dest);
        }
        // Simple copy for a file
        if (is_file($source)) {
            $file = explode(DS, $source);
            //print 
            //return copy($source, $dest);
            return copy($source, is_dir($dest) ? rtrim($dest, DS).DS.end($file) : $dest);
        }
        // Make destination directory
        if (!is_dir($dest)) {
            mkdir($dest, $permissions, true);
        }
        // Loop through the folder
        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }
            // Deep copy directories
            if($sourceHash != $this->hash_directory($source.DS.$entry)) {
                if (!$this->xcopy($source.DS.$entry, $dest.DS.$entry, $permissions)) {
                    return false;
                }
            }
        }
        // Clean up
        $dir->close();
        return true;
    }

    private function hash_directory($directory) {
        if (!is_dir($directory)){ return false; }
        $files = array();
        $dir = dir($directory);
        while (false !== ($file = $dir->read())){
            if ($file != '.' and $file != '..') {
                if (is_dir($directory . DS . $file)) { $files[] = $this->hash_directory($directory . DS . $file); }
                else { $files[] = md5_file($directory . DS . $file); }
            }
        }
        $dir->close();
        return md5(implode('', $files));
    }
}