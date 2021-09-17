<?php
require_once PLAT_PATH_CORE.DS."Std.class.php";

class APageComponents extends BsikStd {

    private static $components = [];

    public static function name() {
        return "testiiiing";
    }
    public static function register(string $name, $set, $protected = true){
        if (isset(self::$components[$name]) && self::$components[$name]["protected"]) {
            throw new Exception("Tried to override a protected component", E_PLAT_ERROR);
        }
        self::$components[$name] = [
            "cb" => $set,
            "protected" => $protected
        ];
    }

    public static function __callstatic($name, $arguments){
        if (!isset(self::$components[$name])) {
            throw new Exception("Tried to use an undefined component", E_PLAT_ERROR);
        }
        if (is_callable(self::$components[$name]["cb"])) {
            return call_user_func_array(self::$components[$name]["cb"], $arguments);
        }
        return self::$components[$name]["cb"];
    }

}

