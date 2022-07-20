<?php

namespace Bsik\FsTools;

/******************************  Requires       *****************************/
require_once PLAT_PATH_AUTOLOAD;

use \Bsik\Std;
use \Exception;
use \SplFileInfo;
use \ZipArchive;

class BsikFileSystem {

    final public static function list_folder(string|\SplFileInfo $path) : ?\RecursiveIteratorIterator {
        $folder = is_string($path) ? new \SplFileInfo($path) : $path;
        if (!$folder->isDir())
            return null;
        /** @var \RecursiveIteratorIterator SplFileInfo[] $files */
        return new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder->getRealPath()), \RecursiveIteratorIterator::LEAVES_ONLY);
    }

    final public static function delete_files(...$files) {
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    final public static function clear_folder(string $path_dir, bool $self = false) : bool {
        if (!file_exists($path_dir)) return false;
        $di = new \RecursiveDirectoryIterator($path_dir, \FilesystemIterator::SKIP_DOTS);
        $ri = new \RecursiveIteratorIterator($di, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ( $ri as $file ) {
            $file->isDir() ?  rmdir($file) : self::delete_files($file);
        }
        if ($self) {
            rmdir($path_dir);
        }
        return true;
    }

    final public static function hash_directory(string $directory) {
        if (!is_dir($directory)) { 
            return false; 
        }
        $files = [];
        $dir = dir($directory);
        while (false !== ($file = $dir->read())) {
            if ($file != '.' and $file != '..') {
                if (is_dir($directory . DIRECTORY_SEPARATOR . $file)) { 
                    $files[] = self::hash_directory($directory . DIRECTORY_SEPARATOR . $file); 
                } else { 
                    $files[] = md5_file($directory . DIRECTORY_SEPARATOR . $file); 
                }
            }
        }
        $dir->close();
        return md5(implode('', $files));
    }

    final public static function xcopy(string $source, string $dest, int $permissions = 0755) : bool {
        $sourceHash = self::hash_directory($source);
        // Check for symlinks
        if (is_link($source))
            return symlink(readlink($source), $dest);
        // Simple copy for a file
        if (is_file($source)) {
            $file = explode(DIRECTORY_SEPARATOR, $source);
            return copy(
                    $source, is_dir($dest) 
                    ? rtrim($dest, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.end($file) 
                    : $dest
            );
        }
        // Make destination directory
        if (!is_dir($dest)) 
            mkdir($dest, $permissions, true);
        // Loop through the folder
        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..')
                continue;
            // Deep copy directories
            if ($sourceHash != self::hash_directory($source.DIRECTORY_SEPARATOR.$entry)) {
                if (!self::xcopy($source.DIRECTORY_SEPARATOR.$entry, $dest.DIRECTORY_SEPARATOR.$entry, $permissions)) {
                    return false;
                }
            }
        }
        // Clean up
        $dir->close();
        return true;
    }

    
}

class BsikZip {


    final public static function zip_status_message($status) : string {
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

    /** 
     * list_files - list all files with name and index - keys are unique full path.
     * @param ZipArchive $zip
     * @return array => empty on zip not loaded
     */
    final public static function list_files(ZipArchive $zip) : array {
        $list = [];
        if ($zip->filename && $zip->status === ZipArchive::ER_OK) { //small workaround hack to check its intialized 
            //List the files in zip:
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $list[$stat['name']] = [
                    "name" => basename($stat['name']),
                    "index" => $i
                ];
            }
        }
        return $list;
    }

    /** 
     * zip_folder - loads the archive into memory
     * 
     * @param string $path  => the path to the folder to zip
     * @param string $out   => the zip full name (path + name)
     * @throws Exception    => E_PLAT_ERROR on zip cant be opened from 'open_zip'.
     * @return bool   => true on saved
     */
    final public static function zip_folder(string $path, string $out) : bool {
        $zip = self::open_zip($out, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        /** @var \RecursiveIteratorIterator $files */
        $origin_path = new SplFileInfo($path);
        $files = BsikFileSystem::list_folder($origin_path) ?? [];
        foreach ($files as $name => $file) {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($origin_path->getRealPath()) + 1);
            if (!$file->isDir()) {
                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            } elseif ($relativePath !== false) {
                //Create empty folder:
                $zip->addEmptyDir($relativePath);
            }
        }
        // Zip archive will be created only after closing object
        return $zip->close();
    }

    /** 
     * open_zip - loads the archive into memory
     * 
     * @param string $path => the zip full name (path + name)
     * @param ?int $flags => ZipArchive FLAGS
     * @throws Exception => E_PLAT_ERROR on zip cant be opened.
     * @return ZipArchive => the zip object
     */
    final public static function open_zip(string $path, ?int $flags = null) : ZipArchive {
        $zip = new ZipArchive();
        $result = $zip->open($path, $flags);
        if ($result !== true) {
            $error = self::zip_status_message($result);
            throw new Exception("Module zip file cant be opened [{$error}]", E_PLAT_ERROR);
        }
        return $zip;
    }

    /** 
     * extract - extract the loaded zip archive to a folder
     * 
     * @param ZipArchive|string $zip
     * @param string $to
     * @throws Exception => E_PLAT_ERROR on zip cant be opened.
     * @return bool      => the extracted path
     */
    final public static function extract_zip(ZipArchive|string $zip, string $to) : bool {
        $close = false;
        if (is_string($zip)) {
            $zip = self::open_zip($zip);
            $close = true;
        }
        $success = $zip->extractTo($to);
        if ($close) $zip->close();
        return $success;
    }
}
/********************** Installer *******************************************/
// class ModuleInstaller {

//     //Path and extraction related:
//     private const TEMP_FOLDER           = "bsik_m_temp_";
//     private string $temp_stamp          = "";  
//     private string $install_in          = "";
//     public string $temp_extracted_path  = "";
//     private string $path                = "";
//     public ZipArchive $zip;

//     //Validation related
//     public const REQUIRED_FILES = [
//         "module.jsonc" => "jsonc"
//     ];

//     //Installation related:
//     private string $schema_install_template = "module.install.%s.jsonc";
//     private string $schema_module_template  = "module.define.%s.jsonc";

//     /** 
//      * Construct ModuleInstaller
//      * 
//      * @param string $_path => path to archive on server.
//      * @param string $_in   => path to default extract destination folder.
//      * @return ModuleInstaller
//      */
//     public function __construct(string $_path, string $_in = PLAT_PATH_MANAGE.DS."modules")
//     {
//         $this->path         = $_path;
//         $this->install_in   = $_in;
//         $this->temp_stamp   = "".time();
//         $this->zip          = new ZipArchive(); 
//     }
//     /** 
//      * open_zip - loads the archive into memory
//      * 
//      * @throws Exception => E_PLAT_ERROR on zip cant be opened.
//      * @return bool => true on loaded
//      */
//     public function open_zip() : bool {
//         $result = $this->zip->open($this->path);
//         if ($result !== true) {
//             $this->close_zip();
//             throw new Exception("Module zip file cant be opened [{$this->ZipStatusString($result)}]", E_PLAT_ERROR);
//         }
//         return true;
//     }
//     /** 
//      * list_files - list all files with name and index - keys are unique full path.
//      * 
//      * @return array => empty on zip not loaded
//      */
//     public function list_files() : array {
//         $list = [];
//         if (isset($this->zip)) {
//             //List the files in zip:
//             for ($i = 0; $i < $this->zip->numFiles; $i++) {
//                 $stat = $this->zip->statIndex($i);
//                 $list[$stat['name']] = [
//                     "name" => basename($stat['name']),
//                     "index" => $i
//                 ];
//             }
//         }
//         return $list;
//     }
//     /** 
//      * validate_required_files_in_zip - validate an zip in module archive.
//      * 
//      * @return array => array of errors message if any
//      */
//     public function validate_required_files_in_zip() : array {
//         $errors = [];
//         if (!isset($this->zip)) {
//             $errors[] = "zip archive not loaded";
//         } else {
//             //List the files in zip:
//             $list = $this->list_files();
//             //Validate required:
//             foreach (self::REQUIRED_FILES as $file => $validate) {
//                 if (isset($list[$file])) {
//                     $content = $this->zip->getFromIndex($list[$file]["index"]);
//                     if (!$this->validate_file("", $validate, $content)) {
//                         $errors[] = "File is invalid [{$file}] expected [{$validate}]";
//                     }
//                 } else {
//                     $errors[] = "Required file missing [{$file}]";
//                 }
//             }
//         }
//         return $errors;
//     }

//     /** 
//      * validate_required_files_in_extracted - validate an extracted zip in folder.
//      * @param  mixed $folder => null for loaded, string for given folder path 
//      * @return array         => array of errors message if any
//      */
//     public function validate_required_files_in_extracted($folder = null) : array {
//         $folder = $folder ?? self::TEMP_FOLDER.$this->temp_stamp;
//         $errors = [];
//         foreach (self::REQUIRED_FILES as $file => $validate) {
//             $file_path = Std::$fs::file_exists("modules", [
//                 $folder,
//                 ...explode("/", $file)
//             ]);
//             if (!$file_path) {
//                 $errors[] = "Required file missing [{$file}]";
//                 continue;
//             }
//             if (!$this->validate_file($file_path["path"], $validate)) {
//                 $errors[] = "File is invalid [{$file}] expected [{$validate}]";
//                 continue;
//             }
//         }
//         return $errors;
//     }

//     /** 
//      * validate_file - validate an a file that is required by its type.
//      * @param  string $path             => the file path will be used only if $content is null. 
//      * @param  string $validate_type    => the file content validation required (json, jsonc etc.)  
//      * @param  mixed $content           => null for load from path os the file content
//      * @return bool                     => true on valid
//      */
//     public function validate_file(string $path, string $validate_type, $content = null) : bool {
//         if (is_null($content)) {
//             $content = file_get_contents($path) ?? "";
//         }
//         switch ($validate_type) {
//             case "jsonc": {
//                 $json = Std::$str::strip_comments($content);
//                 if (!Std::$str::is_json($json)) {
//                     return false;
//                 }
//             } break; 
//             case "json": {
//                 $json = $content;
//                 if (!Std::$str::is_json($json)) {
//                     return false;
//                 }
//             } break; 
//         }
//         return true;
//     }

//     /** 
//      * close_zip - close and release a loaded zip file
//      * safe to use even when not opened
//      * @return void
//      */
//     public function close_zip() {
//         try {
//             $this->zip->close();
//         } catch (Exception $e) { ; }
//     }

//     /** 
//      * extract - extract the loaded zip archive to a folder
//      * 
//      * @param mixed $to => null to use loaded target or string of the target path.
//      * @return string   => the extracted path
//      */
//     public function extract($to = null) : string {
//         $to = $to ?? $this->install_in;
//         $to = rtrim($to, "/\\").DS.self::TEMP_FOLDER.$this->temp_stamp;
//         if (!$this->zip->extractTo($to)) {
//             $this->close_zip();
//             throw new Exception("An Error occurred while extracting module zip [unknown]", E_PLAT_ERROR);
//         }
//         $this->temp_extracted_path = $to;
//         return $to;
//     }

//     /** 
//      * get_json_file - parse a json file - uses teh fs_file_exists method
//      * 
//      * @param string $in    => the core place root folder ex. 'modules'.
//      * @param array $path   => packed arguments defining the path from root.
//      * @return array        => returned json or empty on error
//      */
//     private function get_json_file(string $in, ...$path ) : array {
//         $file_path = Std::$fs::file_exists($in, $path);
//         if ($file_path === false) {
//             return [];
//         }
//         //Parse json:
//         $definition = json_decode(
//             Std::$str::strip_comments(
//                 file_get_contents($file_path["path"]) ?? "")
//         , true);
//         if (empty($definition)) {
//             return [];
//         }
//         return $definition;
//     }

//     public function clean() : void {
//         $_path   = rtrim($this->install_in, "/\\").DS.self::TEMP_FOLDER.$this->temp_stamp;
//         $this->clear_folder($_path, true);
//     }
    
//     public function install($by = null, $folder = null) : array {

//         $folder = $folder ?? self::TEMP_FOLDER.$this->temp_stamp;
//         $installed = [];
        
//         //Load module.jsonc:
//         $module_def = $this->get_json_file("modules", $folder, "module.jsonc");
//         if (empty($module_def)) {
//             return [false, "module.jsonc is missing or corrupted", []];
//         }
//         if (empty($module_def["schema"] ?? "")) {
//             return [false, "module.jsonc is missing schema version definition", []];
//         }
        
//         //Load schema:
//         $schema = new SchemaLoader("install", $module_def["schema"]);
//         if (!$schema->obj->status) {
//             return [false, $schema->obj->message, []];
//         }
        
//         //Extend:
//         $module_def = Std::$arr::extend($schema->obj->data, $module_def);

//         //Required:
//         if (!$schema->check($module_def)) {
//             return [false, "module.jsonc is missing some required properties", []];
//         }
        
//         //Install module depends on the type:
//         switch ($module_def["this"]["type"]) {
//             case "single" : {
//                 $_path   = rtrim($this->install_in, "/\\").DS.$folder;
//                 $_module = $module_def[$schema->naming('modules_container')][0];
//                 [$status, $module_name, $message] = $this->install_module($module_def, $_module, $_path, $by);
//                 if (!$status) {
//                     return [false, $message, $installed];
//                 } else {
//                     $installed[] = $module_name;
//                 }
//             } break;
//             case "bundle" : {
//                 return [false, "bundle are not supported yet", []];
//             } break;
//         }
        
//         //Return
//         return [true, "installed", $installed];
//     }

//     private function install_module(array $definition, array $module, string $folder, $by = null) : array {
//         $module_name = Std::$str::filter_string($module["name"] ?? "unknown", ["A-Z","a-z","0-9", "_"]);
//         $module_path = trim($folder, " /\\").DS.$module_name;

//         //Validate name:
//         if (Base::$db->where("name", $module_name)->has("bsik_modules")) {
//             return [false, $module_name, "allready installed"];
//         }

//         //Parse schema module:
//         if (empty($module["schema"] ?? "")) {
//             return [false,$module_name,"schema not defined"];
//         }

//         //Load schema:
//         $schema = new SchemaLoader("module", $module["schema"]);
//         if (!$schema->obj->status) {
//             return [false,$module_name,$schema->obj->message];
//         }

//         //Extend:
//         $module = Std::$arr::extend($schema->obj->data, $module);

//         //Check Required files:
//         if (!$schema->check($module)) {
//             return [false,$module_name,"module definition is invalid"];
//         }

//         //Move module folder:
//         if (!$this->xcopy($module_path, rtrim($this->install_in, "/\\").DS.$module_name)) {
//             return [false,$module_name,"failed to copy module to destination"];
//         }

//         //Register to db:
//         if (!Base::$db->insert("bsik_modules", [
//             "name"          => $module_name,
//             "status"        => 0,
//             "updates"       => 0,
//             "priv_users"    => 0,
//             "priv_content"  => 0,
//             "priv_admin"    => 0,
//             "priv_install"  => 0,
//             "path"          => $module_name.'/',
//             "settings"      => json_encode($module[$schema->naming("settings_container")]),
//             "defaults"      => json_encode($module[$schema->naming("defaults_container")]),
//             "menu"          => json_encode($module[$schema->naming("menu_container")]),
//             "version"       => $module["ver"],
//             "created"       => Base::$db->now(),
//             "updated"       => Base::$db->now(),
//             "info"          => json_encode($module),
//             "installed_by"  => $by,
//         ])) {
//             return [false, $module_name, "failed to register module to database"];
//         };
//         return [true,$module_name,"module installed"];
//     }

//     public function ZipStatusString( $status ) : string {
//         switch( (int) $status )
//         {
//             case ZipArchive::ER_OK           : return 'N No error';
//             case ZipArchive::ER_MULTIDISK    : return 'N Multi-disk zip archives not supported';
//             case ZipArchive::ER_RENAME       : return 'S Renaming temporary file failed';
//             case ZipArchive::ER_CLOSE        : return 'S Closing zip archive failed';
//             case ZipArchive::ER_SEEK         : return 'S Seek error';
//             case ZipArchive::ER_READ         : return 'S Read error';
//             case ZipArchive::ER_WRITE        : return 'S Write error';
//             case ZipArchive::ER_CRC          : return 'N CRC error';
//             case ZipArchive::ER_ZIPCLOSED    : return 'N Containing zip archive was closed';
//             case ZipArchive::ER_NOENT        : return 'N No such file';
//             case ZipArchive::ER_EXISTS       : return 'N File already exists';
//             case ZipArchive::ER_OPEN         : return 'S Can\'t open file';
//             case ZipArchive::ER_TMPOPEN      : return 'S Failure to create temporary file';
//             case ZipArchive::ER_ZLIB         : return 'Z Zlib error';
//             case ZipArchive::ER_MEMORY       : return 'N Malloc failure';
//             case ZipArchive::ER_CHANGED      : return 'N Entry has been changed';
//             case ZipArchive::ER_COMPNOTSUPP  : return 'N Compression method not supported';
//             case ZipArchive::ER_EOF          : return 'N Premature EOF';
//             case ZipArchive::ER_INVAL        : return 'N Invalid argument';
//             case ZipArchive::ER_NOZIP        : return 'N Not a zip archive';
//             case ZipArchive::ER_INTERNAL     : return 'N Internal error';
//             case ZipArchive::ER_INCONS       : return 'N Zip archive inconsistent';
//             case ZipArchive::ER_REMOVE       : return 'S Can\'t remove file';
//             case ZipArchive::ER_DELETED      : return 'N Entry has been deleted';
//             default: return sprintf('Unknown status %s', $status );
//         }
//     }   

//     public function clear_folder(string $dir, bool $self = false) {
//         if (!file_exists($dir)) return;
//         $di = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
//         $ri = new \RecursiveIteratorIterator($di, \RecursiveIteratorIterator::CHILD_FIRST);
//         foreach ( $ri as $file ) {
//             $file->isDir() ?  rmdir($file) : $this->delete_files($file);
//         }
//         if ($self) {
//             rmdir($dir);
//         }
//     }

//     public function delete_files(...$files) {
//         foreach ($files as $file) {
//             if (file_exists($file)) {
//                 unlink($file);
//             }
//         }
//     }

//     public function xcopy($source, $dest, $permissions = 0755) 
//     {
//         $sourceHash = $this->hash_directory($source);
//         // Check for symlinks
//         if (is_link($source)) {
//             return symlink(readlink($source), $dest);
//         }
//         // Simple copy for a file
//         if (is_file($source)) {
//             $file = explode(DS, $source);
//             //print 
//             //return copy($source, $dest);
//             return copy($source, is_dir($dest) ? rtrim($dest, DS).DS.end($file) : $dest);
//         }
//         // Make destination directory
//         if (!is_dir($dest)) {
//             mkdir($dest, $permissions, true);
//         }
//         // Loop through the folder
//         $dir = dir($source);
//         while (false !== $entry = $dir->read()) {
//             // Skip pointers
//             if ($entry == '.' || $entry == '..') {
//                 continue;
//             }
//             // Deep copy directories
//             if($sourceHash != $this->hash_directory($source.DS.$entry)) {
//                 if (!$this->xcopy($source.DS.$entry, $dest.DS.$entry, $permissions)) {
//                     return false;
//                 }
//             }
//         }

//         // Clean up
//         $dir->close();
//         return true;
//     }

//     private function hash_directory($directory) {
//         if (!is_dir($directory)){ return false; }
//         $files = array();
//         $dir = dir($directory);
//         while (false !== ($file = $dir->read())){
//             if ($file != '.' and $file != '..') {
//                 if (is_dir($directory . DS . $file)) { $files[] = $this->hash_directory($directory . DS . $file); }
//                 else { $files[] = md5_file($directory . DS . $file); }
//             }
//         }
//         $dir->close();
//         return md5(implode('', $files));
//     }
// }
