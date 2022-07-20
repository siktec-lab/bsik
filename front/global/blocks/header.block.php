<?php
/******************************************************************************/
// Created by: Shlomo Hassid.
// Release Version : 1.0.1
// Creation Date: 17/05/20202
// Copyright 2020, Shlomo Hassid.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.1:
    ->intial, Creation
*******************************************************************************/
require_once PLAT_PATH_AUTOLOAD;

use Bsik\Render\Blocks\Block;
use Bsik\Render\Template;

class HeaderBlock extends Block {

    /** 
     * The header default values / settings
     * @override
     */
    public array $defaults   = [
        "doc_type"          => "html",
        "meta_token"        => "",
        "meta" => [
            "viewport"      => "",
            "author"        => "",
            "description"   => "",
            "api"           => "",
            "page"          => "",
            "page_sub"      => "",
            "title"         => "title"
        ],
        "favicon" => [
            "path"          => "",
            "name"          => "",
        ],
        "css_libs"          => "",
        "js_libs"           => "",
        "render-meta-tags"  => true
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
        // $this->templates["headerblock.dyn"] = <<<HTML
        //         <body {{ body_tag }}>
        // HTML;
        // $this->engine->addTemplates($this->templates);
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
        $this->settings["css_libs"] = $this->Page->render_libs("css", "head");
        $this->settings["js_libs"]  = $this->Page->render_libs("js", "head");
        $this->settings["meta"]     = $this->Page->meta->defined_metas;
        $this->settings["body_tag"] = $this->Page->custom_body_tag;
        $this->settings["doctype"]  = $this->Page->get("doctype");
        $this->settings["ex_meta"]  = $this->Page->meta->additional_meta;
        $this->settings["favicon"]  = [
            "name" => "favicon", 
            "path" => $this->Page::$paths["global-lib-url"]."/img/fav"
        ];
        $this->settings["meta_token"] = $this->Page::$token["meta"];
        return $this->engine->render("header", $this->settings);
    }

}