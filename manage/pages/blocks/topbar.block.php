<?php
/******************************************************************************/
// Created by: Shlomo Hassid.
// Release Version : 1.0.1
// Creation Date: 17/05/20202
// Copyright 2020, Shlomo Hassid.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.1:
    ->initial, Creation
*******************************************************************************/
require_once BSIK_AUTOLOAD;

use \Bsik\Render\Blocks\Block;
use \Bsik\Render\Template;

class TopBarBlock extends Block {

    /** 
     * The header default values / settings
     * @override
     */
    public array $defaults   = [
        "plat_logo" => ""
    ]; 

    public $Page  = null;

    public function __construct($Page, Template|null $engine, array $settings)
    {
        parent::__construct(settings : $settings, engine : $engine);
        $this->Page = $Page;
        $this->template();
    }
    /** 
     * Manipulate and add templates
     * @override
     */
    public function template() {

    }
    
    /**
     * render
     * the render logic - costum behavior before rendering
     * this will be called by the page controller render method
     * @param  mixed $Page
     * @param  array $values
     * @return string
     */
    public function render() : string {
        $this->settings["plat_logo"] = $this->Page->get("plat-logo");
        return $this->engine->render("topbar", $this->settings);
    }

}