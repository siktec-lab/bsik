<?php


namespace Bsik\Builder;

require_once PLAT_PATH_AUTOLOAD;

use \Exception;

if (!defined("PLAT_PATH_MODULES")) {
    define("PLAT_PATH_MODULES", "");
}

/**
 * @method static string helloworld( string $name ) return a hello world message
 * @method static array html_ele( string $selector, array $add_attrs, string $content ) builds a html element give the elemnet definition
 * @method static string title( string $text, int $size, array $attrs ) build a simple title element
 * @method static string alert( string $text, string $color, string $icon, bool $dismiss, array $classes ) build an alert element
 * @method static string loader( string $color, string $size, string $align, bool $show, string $type, string $text ) a loader spinner generator
 * @method static string modal( string $id, string $title, string|array $body, string|array $footer, array $buttons, array $set ) a modal generator
 * @method static string confirm() generate confirmation modal
 * @method static string dynamic_table( string $id, string $ele_selector, array $option_attributes, string $api, string $table, array $fields, array $operations ) generate html and js of dynamic table
 * @method static string dropdown( array $buttons, string $text = "dropdown", string $id = "", array $class_main = [], array $class_list = [] ) generates a dropdown element
 * @method static string action_bar( array $actions = [], array $colors, string $class = "" ) generates a action bar menu like elements
 */
class Components {

    private static $components = [];

    public static function register(string $name, $set, $protected = true) : void {
        if (isset(self::$components[$name]) && self::$components[$name]["protected"]) {
            throw new Exception("Tried to override a protected component", E_PLAT_ERROR);
        }
        self::$components[$name] = [
            "cb"        => $set,
            "protected" => $protected
        ];
    }
    
    public static function register_once(string $name, $set, $protected = true) : void {
        if (self::is_registered($name)) {
            return;
        }
        self::$components[$name] = [
            "cb"        => $set,
            "protected" => $protected
        ];
    }

    public static function is_registered(string $name) : bool {
        return isset(self::$components[$name]);
    }

    public static function __callstatic($name, $arguments) {
        
        if (!isset(self::$components[$name])) {
            throw new Exception("Tried to use an undefined component", E_PLAT_ERROR);
        }
        if (is_callable(self::$components[$name]["cb"])) {
            return call_user_func_array(self::$components[$name]["cb"], $arguments);
        }
        return self::$components[$name]["cb"];
    }

}

class BsikButtons {

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
    ) : string {
        
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

class BsikForms {

    const FORM_CONTROL_CLASS    = "form-control";
    const FORM_LABEL_CLASS      = "form-label";
    const FORM_CONTROL_SIZE     = "form-control-%s";

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

class BsikIcon {
    const ICON_TPL = "<i class='%s %s %s' %s></i>";
    const ICON_FAS = "fas";
    const ICON_FAB = "fab";
    const ICON_FAR = "far";
    const ICONS = [
        "cog"           => "fa-cog",
        "cogs"          => "fa-cogs",
        "user-cog"      => "fa-user-cog",
        "tasks"         => "fa-tasks",
        "file"          => "fa-file",
        "page"          => "fa-file",
        "upload"        => "fa-file-upload",
        "binoculars"    => "fa-binoculars",
        "scan"          => "fa-binoculars",
        "shield"        => "fa-shield-alt",
        "star"          => "fa-star",
        "users"         => "fa-user-friends",
    ];
    private static function icon(string $icon) : string {
        return self::ICONS[$icon] ?? $icon;
    }
    public static function fas(string $icon, string $color = "", string $class = "") : string {
        return sprintf(self::ICON_TPL, 
            self::ICON_FAS, 
            self::icon($icon),
            $class,
            !empty($color) ? "style='color:{$color}'" : ""
        );
    }
    public static function far(string $icon, string $color = "", string $class = "") : string {
        return sprintf(self::ICON_TPL, 
            self::ICON_FAR, 
            self::icon($icon),
            $class,
            !empty($color) ? "style='color:{$color}'" : ""
        );
    }
    public static function fab(string $icon, string $color = "", string $class = "") : string {
        return sprintf(self::ICON_TPL, 
            self::ICON_FAB, 
            self::icon($icon), 
            $class,
            !empty($color) ? "style='color:{$color}'" : ""
        );
    }
}