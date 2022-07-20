<?php

use Bsik\DB\MysqliDb;
use Bsik\Std;

class BookmakerLogos {

    private MysqliDb $db;
    public string $path = "";
    public string $url  = "";
    public string $suffix;
    public function __construct(
        MysqliDb $db, 
        string $path = ".",
        string $suffix = "logo.png"
    ) {

        //Validate folder path:        
        if (Std::$fs::file_exists("root", $path)) {
            $path = Std::$fs::path_to("root", $path);
            $this->path = $path["path"];
            $this->url = $path["url"];
        } else {
            throw new \Exception("Bookmakers logo folder path is not reachable - check module settings", E_PLAT_ERROR);
        }

        //Save the required file suffix:
        $this->suffix = $suffix;
        
        //Save ref to db:
        $this->db = $db;

    }

    public function list_logo_files() {
        $url = $this->url;
        return array_map(
            function($img) use($url) {
                return trim($url, " /\\").'/'.$img;
            },
            Std::$fs::list_files_in($this->path, $this->suffix)
        );
    }

}