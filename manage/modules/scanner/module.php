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

use \Bsik\Settings\CoreSettings;
use \Bsik\Builder\Components;
use \Bsik\Builder\BsikIcon;
use \Bsik\Module\Modules;
use \Bsik\Module\Module;
use \Bsik\Module\ModuleView;
use \Bsik\Privileges as Priv;
use \Bsik\Trace;
use \Bsik\Objects\SettingsObject;

/****************************************************************************/
/*******************  local Includes    *************************************/
/****************************************************************************/
//require_once "includes".DS."components.php";
require_once "includes".DS."bookmakers.logos.class.php";


/****************************************************************************/
/*******************  required privileges for module / views    *************/
/****************************************************************************/
$module_policy = new Priv\RequiredPrivileges();
$module_policy->define(
    new Priv\PrivAccess(manage : true),
    new Priv\PrivContent(view : true)
);

/****************************************************************************/
/*******************  Register Module  **************************************/
/****************************************************************************/

Modules::register_module_once(new Module(
    name          : "scanner",
    privileges    : $module_policy,
    views         : ["overview", "bookmakers"],
    default_view  : "overview",
    settings      : new SettingsObject(
        defaults : [
            "title"         => "Odds Scanner",
            "desc"          => "Manage Scanner content and settings",
            "logos-folder"  => "front\\global\\lib\\img\\bookmakers",
            "logo-suffix"   => ".logo.png",
            "module-explain" => "O'm a message from settings"
        ],
        options : [
            "title"         => "string", //TODO:: add a final mark to say its not dynamically 
            "desc"          => "string",
            "logos-folder"  => "string:notempty",
            "logo-suffix"   => "string:notempty",
            ""
        ],
        descriptions : [
            "title"         => "Tab title when module is loaded",
            "desc"          => "Tab description when module is loaded",
            "logos-folder"  => "Path to the bookmakers logos - root base path is added",
            "logo-suffix"   => "The suffix ()"
        ]
    )
)); 

Modules::module("scanner")->register_view(
    view : new ModuleView(
        name        : "bookmakers",
        privileges  : null,
        settings    : new SettingsObject([
            "title" => "Supported Bookmakers",
            "description"  => "Manage and create registered bookmakers",
        ])
    ),
    render      : function() {

        /** @var Module $this */

        //Additional assets:
        $this->page->include_asset("head", "js", "required", ["filepond", "filepond.min.js"]);
        $this->page->include_asset("head", "js", "required", ["filepond", "plugins", "filepond-plugin-file-validate-type.min.js"]);
        $this->page->include_asset("head", "js", "required", ["filepond", "plugins", "filepond-plugin-file-rename.min.js"]);
        $this->page->include_asset("head", "css", "required", ["filepond", "filepond.min.css"]);
        $this->page->include_asset("head", "js", "global", ["js", "sikDropdown.module.js"]);

        //Include confiramtion modal in page:
        $this->page->additional_html(Components::confirm());

        //Include some data for js use:
        $this->page->meta->data_object([
            "can_edit" => $this->page::$issuer_privileges->can("content.edit")
        ]);

        ////////////////////////////////////////
        // bookmakers Actions:
        ////////////////////////////////////////

        //only expose by privileges:
        $actions_bar   = Components::action_bar(
            actions : [
                $this->page::$issuer_privileges->if("content.create")->then( 
                    do   : ["action" => "open-create-bookmaker-modal", "text" => "Add Bookmaker", "icon" => "fa-address-book"], 
                    else : [], 
                    args : []
                ),
            ],
            colors : [],
            class : ""
        ); 

        ////////////////////////////////////////
        // create bookmakers form:
        ////////////////////////////////////////
        $logo = new BookmakerLogos(
            $this->db, 
            $this->settings->get("logos-folder", "."),
            $this->settings->get("logo-suffix", "logo.png")
        );

        Trace::reg_vars(["Bookmakers logos" => $logo->list_logo_files()]);

        //Add a modal to render pages settings objects:
        $this->page->additional_html(Components::modal(
            id: "new-bookmaker-modal", 
            title: BsikIcon::far("fa-address-book")."&nbsp;&nbsp;Add Bookmaker",
            body: [
                Components::alert(
                    text : "<span class='alert-message'>alert text</span>",
                    color : "info",
                    icon : "fas fa-info",
                    classes : ["edit-settings-alert-info"]
                ),
                $this->page->engine->render("bookmaker-create-form", ["logos" => $logo->list_logo_files()])
            ],
            footer: "",
            buttons: [
                ["button.btn.btn-secondary", ["data-action" => "create-new-bookmaker"], "Add Bookmaker", false],
                ["button.btn.btn-primary",   [],     "Cancel", true],
            ],
            set: [
                "close-white"    => true,
                "size"     => "lg",
                "backdrop" => "static",
                "keyboard" => "false",
            ]
        ));

        ////////////////////////////////////////
        // Bookmakers Table:
        ////////////////////////////////////////
        $bookmakers_table_title = Components::title(text : "Registered Bookmakers", attrs : ["class" => "module-title"]);
        $table_actions = [];

        //Set actions based on privileges:
        $this->page::$issuer_privileges->if("content.edit")->then(
            do : function(&$actions) {
                $actions[] = ["name" => "bookmaker_edit", "title" => "Edit page settings", "icon" => "far fa-edit"];
            },
            args: [&$table_actions]
        );
        $this->page::$issuer_privileges->if("content.delete")->then(
            do : function(&$actions) {
                $actions[] = ["name" => "bookmaker_delete", "title" => "Delete this page entry", "icon" => "fas fa-trash"];
            },
            args: [&$table_actions]
        );

        //Set table view:
        $bookmakers_table = Components::dynamic_table(
            id                  : "bookmakers-table",
            ele_selector        : "table#bookmakers-table.table",
            option_attributes   : [
                //"data-toolbar"=>"#toolbar",
                "data-search"           =>"true",
                "data-show-refresh"     =>"true",
                "data-show-toggle"      =>"true",
                "data-show-fullscreen"  =>"false",
                "data-show-columns"     =>"true",
                "data-show-columns-toggle-all"  =>"true",
                //"data-detail-view"    =>"true",
                "data-show-export"      =>"true",
                "data-click-to-select"  =>"true",
                //"data-detail-formatter"       =>"detailFormatter",
                "data-minimum-count-columns"    =>"2",
                "data-show-pagination-switch"   =>"false",
                "data-pagination"       =>"true",
                "data-id-field"         =>"id",
                "data-page-list"        =>"[10, 25, 50, 100, all]",
                "data-show-footer"      =>"false",
                "data-side-pagination"  =>"server",
                "data-search-align"     =>"left"
            ],
            api     : CoreSettings::$url["manage"]."/api/scanner/",
            table   : "scanner_bookmakers", 
            operations : $table_actions,
            fields  : [
                [
                    "field"             => 'id',
                    "title"             => 'ID',
                    "visible"           => true,
                    "searchable"        => true,
                    "sortable"          => true
                ],
                [
                    "field"             => "active",
                    "title"             => "Active",
                    "visible"           => true,
                    "searchable"        => true,
                    "sortable"          => true,
                    "formatter"         => "Bsik.dataTables.formatters.bookmaker_active"
                ],
                [
                    "field"             => 'name',
                    "title"             => 'Name',
                    "visible"           => true,
                    "searchable"        => true,
                    "sortable"          => true,
                    "align"             => "left"
                ],
                [
                    "field"             => "text",
                    "title"             => "Display Name",
                    "visible"           => true,
                    "searchable"        => true,
                    "sortable"          => true,
                    "align"             => "left",
                    "formatter"         => "Bsik.dataTables.formatters.bookmaker_display"
                ],
                [
                    "field"             => "logo",
                    "title"             => "Logo",
                    "visible"           => false,
                    "searchable"        => false,
                    "sortable"          => false,
                    "formatter"         => "Bsik.dataTables.formatters.bookmaker_logo"
                ],
                [
                    "field"             => 'operate',
                    "title"             => 'Actions',
                    "clickToSelect"     => false,
                    "events"            => "@js:Bsik.tableOperateEvents", // the function in module js
                    "formatter"         => null // Will use dynamic generated formatter only if operations are defined next
                ]
            ]
        );


        //Template:
        return <<<HTML
            <div class='container'>
                {$actions_bar}
            </div>
            <div class='container sik-form-init'>
                {$bookmakers_table_title}
                {$bookmakers_table}
            </div>
        HTML;

    }
);
/****************************************************************************/
/*******************  View - manage  ****************************************/
/****************************************************************************/

Modules::module("scanner")->register_view(
    view : new ModuleView(
        name        : "overview",
        privileges  : null,
        settings    : new SettingsObject([
            "title" => "Scanner Overview",
            "desc"  => "All high-level data about the scanner",
        ])
    ),
    render      : function() {

        /** @var Module $this */

        //Include confiramtion modal in page:
        $this->page->additional_html(Components::confirm());

        //Template:
        return <<<HTML
            <div class='container'>
            </div>
            <div class='container sik-form-init'>
            </div>
        HTML;
    }
);
