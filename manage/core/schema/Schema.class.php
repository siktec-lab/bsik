<?php

namespace Bsik\Module\Schema;

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
if (!defined('ROOT_PATH')) define("ROOT_PATH", dirname(__FILE__).DS."..".DS.".." );

/******************************  Requires       *****************************/
require_once PLAT_PATH_AUTOLOAD;

use \Exception;
use \Bsik\Std;

class SchemaObj {
    public bool     $status  = false;
    public string   $message = "schema not intialized";
    public array    $struct  = [];
}

class ModuleDefinition {
    public bool     $valid  = false;
    public array    $struct = [];
}

class ModuleSchema {

    private const   DEFAULT_VERSION = "1.0.0";
    public const    SCHEMA_FOLDER   = "versions";

    private const   SCHEMAS = [
        "install" => [
            "template" => "module.install.%s.jsonc"
        ],
        "module"  => [
            "template" => "module.define.%s.jsonc"
        ],
    ];

    private string $type;
    private string $version;
    private string $template_name;
    public ?SchemaObj $schema_template = null;
    
    /**
     * __construct
     *
     * @param  string $_type - which schema type we are proccessing 
     * @param  ?string $_version - target version or null for default
     * @throws Exception => E_PLAT_ERROR if schema type is not supported.
     * @return void
     */
    public function __construct(string $_type, ?string $_version = null) {

        $this->type            = trim($_type);
        $this->version         = trim($_version ?? self::DEFAULT_VERSION);

        if (array_key_exists($this->type, self::SCHEMAS) && Std::$str::is_version($this->version)) {
            $this->template_name     = sprintf(self::SCHEMAS[$this->type]["template"], $this->version);
            $this->schema_template = $this->get_template($this->template_name);
        } else {
            $this->schema_template = new SchemaObj();
        }
    }

    public function get_template(string $template_name) : ?SchemaObj {

        $sch = new SchemaObj();
        
        //Find the schema template:
        $template_path = Std::$fs::file_exists("schema", [self::SCHEMA_FOLDER, $template_name]);
        
        //If not found return:
        if ($template_path === false) {
            $sch->status    = false;
            $sch->message   = "Schema version is not supported";
            return $sch;
        }
        //Load the schema:
        $sch->struct = Std::$fs::get_json_file($template_path["path"]) ?? [];
        if (empty($sch->struct)) {
            $sch->status    = false;
            $sch->message   = "Schema is corrupted";
        } else {
            $sch->status    = true;
            $sch->message   = "loaded";
        }
        return $sch;
    }
    
    public function is_loaded() {
        return $this->schema_template->status;
    }

    public function get_message() {
        return $this->schema_template->message;
    }


    /**
     * naming - get the human readable name of the container.
     *
     * @param  string $naming
     * @return string
     */
    public function naming(string $naming) : string {
        return $this->is_loaded() ? $this->schema_template->struct['$schema_naming'][$naming] ?? "" : "";
    }
    
    /**
     * validate a json file against the loaded schema
     *
     * @param  array $struct
     * @return bool
     */
    public function validate(array $struct) : bool {
        //var_dump($this->schema_template->struct['$schema_required']);
        //var_dump($struct['this']);
        return $this->is_loaded() 
                ? Std::$arr::validate($this->schema_template->struct['$schema_required'], $struct)
                : false;
    }

    public function create_definition(array $struct) : ModuleDefinition {
        $def = new ModuleDefinition();
        $def->struct = Std::$arr::extend($this->schema_template->struct, $struct);
        $def->valid = $this->validate($def->struct);
        //var_dump($def->valid);
        return $def;
    }

}
