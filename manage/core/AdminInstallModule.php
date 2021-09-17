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

    public function __construct(string $_path, string $_in = PLAT_PATH_MANAGE.DS."modules")
    {
        $this->path         = $_path;
        $this->install_in   = $_in;
        $this->temp_stamp   = "".time();
        $this->zip          = new ZipArchive(); 
    }

    public function open_zip() : bool {
        $result = $this->zip->open($this->path);
        if ($result !== true) {
            $this->close_zip();
            throw new Exception("Module zip file cant be opened [{$this->ZipStatusString($result)}]", E_PLAT_ERROR);
        }
        return true;
    }
    public function close_zip() {
        try {
            $this->zip->close();
        } catch (Exception $e) { ; }
    }
    public function extract($to = null) : string {
        $to = $to ?? $this->install_in;
        $to = ltrim($to, "/\\").DS.self::TEMP_FOLDER.$this->temp_stamp;
        if (!$this->zip->extractTo($to)) {
            $this->close_zip();
            throw new Exception("An Error occurred while extracting module zip [unknown]", E_PLAT_ERROR);
        }
        $this->temp_extracted_path = $to;
        return $to;
    }

    public function install($folder = null) {
        $folder = $folder ?? self::TEMP_FOLDER.$this->temp_stamp;
        $file_path = self::$std::fs_file_exists("modules", [$folder, "module.jsonc"]);
        //Parse definition:
        $definition = json_decode(
            self::$std::str_strip_comments(
                file_get_contents($file_path["path"]) ?? "")
        , true);
        if (empty($definition["schema"] ?? "")) {
            throw new Exception("Cant install module schema is not defined", E_PLAT_ERROR);
        }
        //Parse schema install:
        $schema_path = self::$std::fs_file_exists("schema", [sprintf($this->schema_install_template, $definition["schema"])]);
        if ($schema_path === false) {
            throw new Exception("Cant install module schema is not supported [{$definition["schema"]}]", E_PLAT_ERROR);
        }
        $schema = json_decode(
            self::$std::str_strip_comments(
                file_get_contents($schema_path["path"]) ?? "")
        , true);
        //Extend:
        $definition = self::$std::arr_extend($schema, $definition);
        //Required:
        if (!self::$std::arr_validate($schema['$schema_required'], $definition)) {
            throw new Exception("Cant install module install is corrupted", E_PLAT_ERROR);
        }
        print json_encode($definition);
        //Parse modules:
        $installed = [];
        foreach ($definition[$definition['$schema_naming']['modules_container']] as $module) {
            $installed[] = $this->install_module($definition, $module);
        }
        return $installed;
    }

    private function install_module(array $definition, array $module) : array{
        $module_name = $module["name"] ?? "unknown";
        //Parse schema module:
        if (empty($module["schema"] ?? "")) {
            return ["installed" => false, "module" => $module_name, "message" => "schema not defined"];
        }
        $schema_path = self::$std::fs_file_exists("schema", [sprintf($this->schema_module_template, $module["schema"])]);
        if ($schema_path === false) {
            return ["installed" => false, "module" => $module_name, "message" => "schema is not supported"];
        }
        $schema = json_decode(
            self::$std::str_strip_comments(
                file_get_contents($schema_path["path"]) ?? "")
        , true);
        //Extend:
        $module = self::$std::arr_extend($schema, $module);
        //Required:
        if (!self::$std::arr_validate($schema['$schema_required'], $module)) {
            return ["installed" => false, "module" => $module_name, "message" => "module definition is invalid"];
        }
        print "<br /><br />";
        print json_encode($module);
        return ["installed" => true, "module" => $module_name, "message" => "module installed"];
    }

    public function validate_install($folder = null) : array {
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

    private function validate_required(array $required, array $against) : bool {
        foreach ($required as $key => $type) {
            $keys = explode(".", $key);
            $cur = array_shift($keys);
            if (
                isset($against[$cur]) && 
                ((!empty($keys) && gettype($against[$cur]) === "array") ||
                 (empty($keys) && gettype($against[$cur]) === $type))
            ) {
                if (!empty($keys) && !$this->validate_required([implode(".", $keys) => $type], $against[$cur]))
                    return false;
            } else {
                print $key." - ".$cur."<br />";
                return false;
            }
        }
        return true;
    }
    public function validate_file(string $path, string $validate_type) : bool {
        
        switch ($validate_type) {
            case "jsonc": {
                $json = self::$std::str_strip_comments(file_get_contents($path) ?? "");
                if (!self::$std::str_is_json($json)) {
                    return false;
                }
            } break; 
            case "json": {
                $json = file_get_contents($path) ?? "";
                if (!self::$std::str_is_json($json)) {
                    return false;
                }
            } break; 
        }
        return true;
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

}