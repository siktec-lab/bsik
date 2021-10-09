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
require_once PLAT_PATH_CORE.DS."Std.class.php";

class BsikModule extends BsikStd
{

    public $module_name = "";
    public $module_which = "";
    private $view_default = "";
    private $views = [];
    public $content = "";
    
    /* Page constructor.
     *  @param $conf => SIK configuration array Used in Base Parent
     *  @Default-params: none
     *  @return none
     *  @Examples:
    */
    public function __construct(
        string $module_name,
        string $which = ""
    ) {
        $this->module_name = $module_name;
        $this->module_which = $which;
    }

    public function set_views(array $views, string $default = "") {
        $this->views = array_fill_keys($views, 0);
        $this->view_default = $default;
    }

    public function view(string $name, callable $render = null) {
        if (!isset($this->views[$name])) {
            throw new Exception("Trying to register undefined view in module", E_PLAT_ERROR);
        }
        $this->views[$name] = $render;
    }

    public function render(string $which, ...$args) : string {
        if ($which === "default") {
            $this->module_which = $this->view_default;
            $which = $this->view_default;
        }
        if (!isset($this->views[$which]) || !is_callable($this->views[$which])) {
            throw new Exception("Trying to render undefined view in module", E_PLAT_ERROR);
        }
        return (string)$this->views[$which](...$args);
    }

}