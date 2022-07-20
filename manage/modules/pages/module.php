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
require_once PLAT_PATH_AUTOLOAD;

use Bsik\Builder\Components;
use Bsik\Users\User;
use Bsik\Builder\BsikIcon;
use Bsik\Module\Modules;
use Bsik\Module\Module;
use Bsik\Module\ModuleView;
use Bsik\Privileges as Priv;
use Bsik\Objects\SettingsObject;

/****************************************************************************/
/*******************  local Includes    *************************************/
/****************************************************************************/
//require_once "includes".DS."components.php";


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
    name          : "pages",
    privileges    : $module_policy,
    views         : ["manage"],
    default_view  : "manage"
)); 

/****************************************************************************/
/*******************  View - manage  ****************************************/
/****************************************************************************/

Modules::module("pages")->register_view(
    view : new ModuleView(
        name        : "manage",
        privileges  : null,
        settings    : new SettingsObject([
            "title"         => "Create / Attach / Manage pages",
            "description"   => "Manage registered pages",
        ])
    ),
    render      : function() {

        /** @var Module $this */

        //Include confiramtion modal in page:
        $this->page->additional_html(Components::confirm());


        //Include some data for js use:
        $this->page->meta->data_object([
            "can_edit" => $this->page::$issuer_privileges->can("content.edit")
        ]);

        ////////////////////////////////////////
        // Page Actions:
        ////////////////////////////////////////

        //only expose by privileges:
        $actions_bar   = Components::action_bar(
            actions : [
                $this->page::$issuer_privileges->if("content.create")->then( 
                    do   : ["action" => "open-create-page-modal", "text" => "Create Page", "icon" => "page"], 
                    else : [], 
                    args : []
                ),
                $this->page::$issuer_privileges->if("content.create", "content.upload")->then( 
                    do   : ["action" => "open-upload-page-modal", "text" => "Upload Page Template", "icon" => "fa-file-upload"], 
                    else : [], 
                    args : []
                ),
                $this->page::$issuer_privileges->if("content.create", "content.upload")->then( 
                    do   : ["action" => "scan-pages-templates", "text" => "Scan Pages Templates", "icon" => "binoculars"],
                    else : [], 
                    args : []
                ),
                $this->page::$issuer_privileges->if("content.edit")->then( 
                    do   : ["action" => "open-pages-settings-modal", "text" => "Global Pages Settings", "icon" => "cog"], 
                    else : [], 
                    args : []
                )
            ],
            colors : [],
            class : ""
        ); 

        //Add a modal to render pages settings objects:
        $this->page->additional_html(Components::modal(
            "pages-settings-modal", 
            BsikIcon::fas("fa-cogs")."&nbsp;&nbsp;Update Settings",
            [
                Components::alert(
                    text : "<span class='alert-message'>alert text</span>",
                    color : "warning",
                    icon : "fas fa-edit",
                    classes : ["edit-settings-alert-info"]
                ),
                "<div class='form-modal-container'></div>"
            ],
            "",
            [
                ["button.btn.btn-secondary", ["data-action" => "save-pages-settings"], "Save Settings", false],
                ["button.btn.btn-primary",   [],     "Cancel", true],
            ],
            [
                "close-white"    => true,
                "size"     => "lg",
                "backdrop" => "static",
                "keyboard" => "false",
            ]
        ));

        //Add a modal to create pages entries:
        $this->page->additional_html(Components::modal(
            "pages-create-modal", 
            BsikIcon::fas("fa-folder-plus")."&nbsp;&nbsp;Create Page",
            [
                $this->page->engine->render("create_page_form")
            ],
            "",
            [
                ["button.btn.btn-secondary", ["data-action" => "create-page"], "Create New Page", false],
                ["button.btn.btn-primary",   [],     "Cancel", true],
            ],
            [
                "close-white"   => true,
                "size"          => "lg",
                "backdrop"      => "static",
                "keyboard"      => "false",
            ]
        ));

        ////////////////////////////////////////
        // Pages Table:
        ////////////////////////////////////////
        $pages_table_title = Components::title(text : "Defined Pages", attrs : ["class" => "module-title"]);
        $table_actions = [];
        //Set actions based on privileges:
        $this->page::$issuer_privileges->if("content.edit")->then(
            do : function(&$actions) {
                $actions[] = ["name" => "page_settings", "title" => "Edit page settings", "icon" => "fas fa-cog"];
            },
            args: [&$table_actions]
        );
        $this->page::$issuer_privileges->if("content.download")->then(
            do : function(&$actions) {
                $actions[] = ["name" => "page_download", "title" => "Download static version of this page", "icon" => "fas fa-download"];
            },
            args: [&$table_actions]
        );
        $this->page::$issuer_privileges->if("content.delete")->then(
            do : function(&$actions) {
                $actions[] = ["name" => "page_delete", "title" => "Delete this page entry", "icon" => "fas fa-trash"];
            },
            args: [&$table_actions]
        );
        //Set table view:
        $pages_table = Components::dynamic_table(
            id                  : "pages-table",
            ele_selector        : "table#pages-table.table",
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
            api     : PLAT_URL_BASE."/manage/api/pages/",
            table   : "page_all", 
            operations : $table_actions,
            fields  : [
                [
                    "field"             => 'name',
                    "title"             => 'Name',
                    "visible"           => true,
                    "searchable"        => true,
                    "sortable"          => true,
                    "formatter"         => "Bsik.dataTables.formatters.page_name"
                ],
                [
                    "field"             => "status",
                    "title"             => "Page Status",
                    "visible"           => true,
                    "searchable"        => true,
                    "sortable"          => true,
                    "formatter"         => "Bsik.dataTables.formatters.page_status"
                ],
                [
                    "field"             => "type",
                    "title"             => "Page Type",
                    "visible"           => true,
                    "searchable"        => true,
                    "sortable"          => true,
                    "formatter"         => "Bsik.dataTables.formatters.page_type"
                ],
                [
                    "field"             => "template",
                    "title"             => "Page Template",
                    "visible"           => true,
                    "searchable"        => false,
                    "sortable"          => false,
                    "formatter"         => "Bsik.dataTables.formatters.page_templates"
                ],
                [
                    "field"             => "breadname",
                    "title"             => "Bread Name",
                    "visible"           => true,
                    "searchable"        => true,
                    "sortable"          => true
                ],
                [
                    "field"             => 'page_folder',
                    "title"             => 'Folder',
                    "visible"           => false,
                    "searchable"        => false,
                    "sortable"          => false
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
                {$pages_table_title}
                {$pages_table}
            </div>
        HTML;
    }
);
