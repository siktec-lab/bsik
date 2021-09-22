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

class APageButtons extends BsikStd
{

    const BTN_CLASS = "btn";
    const BTN_COLOR_CLASS = "btn-%s";
    const BTN_SIZE_CLASS = "btn-%s";

    public static function button(
        string $id,
        string $text,
        string $color = "primary", 
        string $size = "",
        string $type = "button",
        array $attrs = [],
        array $classes = [],
        string $spinner = ""
    ) {
        
        $class = self::BTN_CLASS." ".self::_btn_size($size)." ".self::_btn_color($color)." ".implode(" ", $classes);
        $attrs = self::_attrs($attrs);
        $spinner = !empty($spinner) ? self::_spinner($spinner) : "";
        return <<<HTML
            <button type="{$type}" id="{$id}" class="{$class}" {$attrs}>
                {$spinner}
                {$text}
            </button>
        HTML;
    }
    
    private static function _spinner(string $type_size = "border-sm") {
        $type = explode('-', $type_size);
        return "<span class='spinner-{$type[0]} spinner-{$type_size}' role='status' aria-hidden='true' style='display:none'></span>";
    }

    private static function _attrs(array $attrs = []) {
        $html = "";
        foreach ($attrs as $attr => $value) {
            $html .= trim($attr)."='".htmlspecialchars($value)."'";
        }
        return $html;
    }

    private static function _btn_size(string $size) {
        return !empty($size) ? sprintf(self::BTN_SIZE_CLASS, $size) : "";
    }

    private static function _btn_color(string $color) {
        return !empty($color) ? sprintf(self::BTN_COLOR_CLASS, $color) : "";
    }

}
class APageForms extends BsikStd
{

    const FORM_CONTROL_CLASS = "form-control";
    const FORM_LABEL_CLASS = "form-label";
    const FORM_CONTROL_SIZE = "form-control-%s";

    public static function label($for, $text) {
        return "<label for='".htmlspecialchars($for)."' class='".self::FORM_LABEL_CLASS."'>{$text}</label>";
    }

    public static function file(
        string $name, 
        string $id, 
        string $label = "", 
        string $size = "", 
        array $classes = [], 
        array $wrapper = [], 
        array $states = [], 
        array $attrs = []) {
        
        $label = !empty($label) ? self::label($id, $label) : "";
        $input_classes = self::FORM_CONTROL_CLASS." ".self::_form_control_size($size)." ".implode(" ", $classes);
        $wrapper = implode(" ", $wrapper);
        $states = implode(" ", $states);
        $attrs = self::_attrs($attrs);

        return <<<HTML
            <div class="{$wrapper}">
                {$label}
                <input class='{$input_classes}' type="file" id='{$id}' name='{$name}' {$attrs} {$states} />
            </div>
        HTML;
    }



    private static function _attrs(array $attrs = []) {
        $html = "";
        foreach ($attrs as $attr => $value) {
            $html .= trim($attr)."='".htmlspecialchars($value)."'";
        }
        return $html;
    }

    private static function _form_control_size(string $size) {
        return !empty($size) ? sprintf(self::FORM_CONTROL_SIZE, $size) : "";
    }
}