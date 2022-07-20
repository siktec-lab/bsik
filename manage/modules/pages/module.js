/******************************************************************************/
// Created by: SIKTEC.
// Release Version : 1.0.0
// Creation Date: 2021-03-16
// Copyright 2021, SIKTEC.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.0:
    ->initial 
*******************************************************************************/
document.addEventListener("DOMContentLoaded", function(event) {

    /** Global module code: *************************************************/
    // Bsik.module[Bsik.loaded.module.name].publish_statuses = [
    //     { "value" : 1, "text" : "draft"  },
    //     { "value" : 2, "text" : "active" }
    // ];
    // Bsik.module[Bsik.loaded.module.name].shipping_templates = JSON.parse($(document).find("meta[name='shippping_templates']").attr("content") || '[]');

    //Attach MaxType for inputs:
    Bsik.core.bindAllMaxType("[max-type]", true);
    
    /******************************************************************/
    /** Pages Create: *************************************************/
    /******************************************************************/
    if (Bsik.loaded.module.sub === "manage") {

        (function($, window, document, Bsik, undefined) {

            console.log(Bsik);

            //Set modals:
            Bsik.modals.create = new bootstrap.Modal(
                document.getElementById('pages-create-modal'),
                Bsik.core.helpers.objAttr.getDataAttributes("#pages-create-modal")
            );
            Bsik.modals.createElement = $(Bsik.modals.create._element);

            Bsik.modals.settings = new bootstrap.Modal(
                document.getElementById('pages-settings-modal'),
                Bsik.core.helpers.objAttr.getDataAttributes("#pages-settings-modal")
            );
            Bsik.modals.settingsElement = $(Bsik.modals.settings._element);

            //Basic behavior of empty input:
            Bsik.modals.settingsElement.on("click", ".checkbox-empty-disable-input", function(){
                $(this).closest(".input-group").find(".form-control")
                        .prop("disabled", $(this).prop("checked"))
                        .val(null);
                $(this).closest(".input-group").find(".checkbox-remove-override-input").prop("checked", false);
            });
            
            //Basic behavior of remove input:
            Bsik.modals.settingsElement.on("click", ".checkbox-remove-override-input", function(){
                $(this).closest(".input-group").find(".form-control, .form-select")
                        .prop("disabled", $(this).prop("checked"));
                $(this).closest(".input-group").find(".checkbox-empty-disable-input").prop("checked", false);
            });

            //mark changes on select settings:
            Bsik.modals.settingsElement.on("change", ".form-select", function(){
                let selected = $(this).val();
                let current  = $(this).attr("data-original");
                console.log(selected, current);
                if (selected !== current) {
                    $(this).addClass("revised");
                } else {
                    $(this).removeClass("revised");
                }
            });


            //Testing:
            /*TODO: remove this its just for testing */
            $("#scan-pages").click(function(){
                console.log("test");
                Bsik.core.apiRequest(null, "dashboard.sayhello", { name : "shlomi"}, 
                    {
                        error: function(jqXhr, textStatus, errorMessage) {
                            let error = jqXhr.responseJSON ? jqXhr.responseJSON.errors || [errorMessage] : [errorMessage];
                            Bsik.notify.error(`say hello error - ${error.join()}`, true);
                            console.log(jqXhr.responseJSON);
                        },
                        success: function(res) {
                            console.log(res.data);
                        }
                    }
                );
            });

            //Basic behaviors:
            class SettingsParser {

                static settings = {};

                static descriptions = {};

                static options = {};

                static formHtml = "";

                static loadSettings(which, name = "", cb = function(){}) {
                    //Set request params
                    let data = { of : which };
                    if (which === "page")
                        data["name"] = name;
                    Bsik.core.apiRequest(null, "pages.get_settings", data, 
                        {
                            error: function(jqXhr, textStatus, errorMessage) {
                                let error = jqXhr.responseJSON ? jqXhr.responseJSON.errors || [errorMessage] : [errorMessage];
                                Bsik.notify.error(`Load current settings error - ${error.join()}`, true);
                                console.log(jqXhr.responseJSON);
                            },
                            success: function(res) {
                                console.log(res);
                                SettingsParser.settings     = res.data.settings.values;
                                SettingsParser.descriptions = res.data.settings.descriptions;
                                SettingsParser.options      = res.data.settings.options;
                                SettingsParser.formHtml     = res.data.form;
                                cb();
                            }
                        }
                    );
                }

                static getRevisedSettings($form) {
                    let entries = $form.find(".input-group");
                    let changes = {};
                    entries.each(function() {
                        let $value  = $(this).find(".form-control, .form-select").eq(0);
                        let setting = $value.attr("name");
                        let empty   = $value.hasClass("form-control") 
                                        ? $(this).find(".checkbox-empty-disable-input").eq(0).prop("checked") 
                                        : false;
                        let remove  = ($value.hasClass("form-select") || $value.hasClass("form-control") )
                                        ? $(this).find(".checkbox-remove-override-input").eq(0).prop("checked") 
                                        : false;
                        let revised = $value.val() + "";
                        let current = $value.attr("data-original");
                        if (remove) {
                            changes[setting] = "@remove@";
                        } else if (empty) {
                            changes[setting] = "";
                        } else if (revised.length && revised !== current) {
                            changes[setting] = revised;
                        }
                    });
                    return changes;
                }
            }

            Bsik.module[Bsik.loaded.module.name].manage = {

                /************ module specific js: ******************/
                openSettingsModal : function($btn, which, name = "") {
                    //First get the settings:
                    $btn.prop("disabled", true);
                    SettingsParser.loadSettings(which, name, function(){
                        $btn.prop("disabled", false);
                        //Attach to modal:
                        let $body    = Bsik.modals.settingsElement.find(`div.form-modal-container`).eq(0);
                        $body.html(SettingsParser.formHtml);
                        //Set header:
                        let header   = Bsik.modals.settingsElement.find(".edit-settings-alert-info > span.alert-message").eq(0);
                        console.log(header);
                        header.html(which === "global"
                            ? `You are editing pages global settings - All front pages inherit from this settings definition.`
                            : `You are editing <strong>${name}</strong> page - Inherited and Overridden settings are marked with a tag.` 
                        );
                        //Open modal:
                        Bsik.modals.settings.show();
                    });
                },
                savePagesSettings : function($btn) {
                    let $form = Bsik.modals.settingsElement.find("#dyn-form-settings");
                    let revised = SettingsParser.getRevisedSettings($form);
                    let which = $form.data("which");
                    let name = $form.data("name");
                    console.log(revised);
                    // return;
                    $btn.prop("disabled", true);
                    Bsik.core.apiRequest(null, "pages.save_settings", { 
                            settings : JSON.stringify(revised),
                            of       : which,
                            name     : name
                    }, {
                        error: function(jqXhr, textStatus, errorMessage) {
                            let error = jqXhr.responseJSON ? jqXhr.responseJSON.errors || [errorMessage] : [errorMessage];
                            Bsik.notify.error(`Save revised settings error - ${error.join()}`, true);
                            console.log(jqXhr.responseJSON);
                        },
                        success: function(res) {
                            // console.log(res);
                            Bsik.notify.bubble("info", `Saved revised settings successfully.`);
                            Bsik.modals.settings.hide();
                        },
                        complete : function() {
                            $btn.prop("disabled", false);
                        }
                    });
                },
                deletePage : function(name) {
                    Bsik.core.helpers.getConfirmation(
                        `Please confirm the deletion of "<strong>${name}</strong>" page - All the related data will be removed from your system and lost.`,
                        function yes(event) {
                            $(event.target).prop("disabled", true);
                            Bsik.core.apiRequest(null, "pages.delete_page", { 
                                name     : name
                            }, {
                                error: function(jqXhr, textStatus, errorMessage) {
                                    let error = jqXhr.responseJSON ? jqXhr.responseJSON.errors || [errorMessage] : [errorMessage];
                                    Bsik.notify.error(`Delete page error - ${error.join()}`, true);
                                    console.log(jqXhr.responseJSON);
                                },
                                success: function(res) {
                                    // console.log(res);
                                    Bsik.notify.bubble("info", `Deleted page '${name}' successfully.`);
                                    $("#pages-table").bootstrapTable('refresh', { silent : true });
                                    event.data.modal.hide();
                                },
                                complete : function() {
                                    $(event.target).prop("disabled", false);
                                }
                            });
                        }, 
                        function no(event){
                            event.data.modal.hide();
                        }
                    );
                },
                changePageStatus : function($btn, name) {
                    $btn.prop("disabled", true);
                    Bsik.core.apiRequest(null, "pages.change_page_status", { 
                        name     : name
                    }, {
                        error: function(jqXhr, textStatus, errorMessage) {
                            let error = jqXhr.responseJSON ? jqXhr.responseJSON.errors || [errorMessage] : [errorMessage];
                            Bsik.notify.error(`Change page [${name}] status error - ${error.join()}`, true);
                            console.log(jqXhr.responseJSON);
                        },
                        success: function(res) {
                            console.log(res);
                        },
                        complete : function() {
                            $btn.prop("disabled", false);
                        }
                    });
                },
                checkNewPageName : function(name, valid, invalid) {
                    if (name.length === 0) {
                        if (invalid instanceof Function) invalid();
                    } else {
                        Bsik.core.apiRequest(null, "pages.page_name_valid", { 
                            name     : name
                        }, {
                            error: function(jqXhr, textStatus, errorMessage) {
                                // let error = jqXhr.responseJSON ? jqXhr.responseJSON.errors || [errorMessage] : [errorMessage];
                                // Bsik.notify.error(`Change page [${name}] status error - ${error.join()}`, true);
                                // console.log(jqXhr.responseJSON);
                                if (invalid instanceof Function) 
                                    invalid(name);
                            },
                            success: function(res) {
                                if (res.data.valid) {
                                    if (valid instanceof Function) valid(res.data.name);
                                } else {
                                    if (invalid instanceof Function) invalid(res.data.name);
                                }
                            },
                            complete : function() {

                            }
                        });
                    }
                },
                getPageTemplates : function(type, cb) {
                    Bsik.core.apiRequest(null, "pages.get_page_templates", { 
                        template_type     : type
                    }, {
                        error: function(jqXhr, textStatus, errorMessage) {
                            let error = jqXhr.responseJSON ? jqXhr.responseJSON.errors || [errorMessage] : [errorMessage];
                            Bsik.notify.error(`Loading page templates of type [${type}] error - ${error.join()}`, true);
                            console.log(jqXhr.responseJSON);
                        },
                        success: function(res) {
                            if (res.data.templates) {
                                if (cb instanceof Function) 
                                    cb(res.data.templates);
                            }
                        },
                        complete : function() {

                        }
                    });
                },
                createPage : function(form, mapNames) {
                    let $form = $(form);
                    if ($form.length) {
                        let data = Bsik.core.serializeToObject($form, [], mapNames);
                        // console.log(data);

                        Bsik.core.apiRequest(null, "pages.create_page_entry", data, {
                            error: function(jqXhr, textStatus, errorMessage) {
                                let error = jqXhr.responseJSON ? jqXhr.responseJSON.errors || [errorMessage] : [errorMessage];
                                Bsik.notify.error(`Create page entry error - ${error.join()}`, true);
                                console.log(jqXhr.responseJSON);
                            },
                            success: function(res) {
                                console.log(res);
                                $("#pages-table").bootstrapTable('refresh', { silent : true });
                                Bsik.modals.create.hide();
                            },
                            complete : function() {
    
                            }
                        });

                    }
                }
            };

            /***************************************************************/
            // Dynamic table formaters:
            /***************************************************************/
            Bsik.dataTables.formatters.page_name = function(value, data_row, index, header) {
                return `
                    <span class="badge bg-elegant">${value.toUpperCase()}</span>
                `;
            };
            Bsik.dataTables.formatters.page_type = function(value, data_row, index, header) {
                let type_name = value === 2 ? "Local Template" : "Dynamic Template";
                return `
                    <span class="badge bg-${value === 2 ? "special" : "unique"}">${type_name.toUpperCase()}</span>
                `;
            };
            Bsik.dataTables.formatters.page_templates = function(value, data_row, index, header) {
                return `
                    <span class="chain-tag">
                        <strong>${value === 1 ? "DYNAMIC" : "FILE"}</strong>
                        <i class="fas fa-angle-double-right"></i>
                        ${value === 1 ? value : data_row.page_folder}
                    </span>
                `;
            };
            Bsik.dataTables.formatters.page_status = function(value, data_row, index, header) {
                return `<div class="form-check form-switch form-check-inline" ${Bsik.loaded.module.data.can_edit ? "" : "title='you cant change the page status - you need edit privileges to do that'" }>
                            <input 
                                name="page-status" 
                                data-page="${data_row.name}" 
                                class="form-check-input" 
                                type="checkbox" ${value ? "checked" : ""}
                                data-action="change-page-status"
                                ${Bsik.loaded.module.data.can_edit ? "" : "disabled" }
                            />
                            <label class="form-check-label">
                                Draft / Publish
                            </label>
                        </div>`;
            };
            /***************************************************************/
            // Dynamic table actions:
            /***************************************************************/
            Bsik.tableOperateEvents = {
                'click .page_settings': function(e, value, row, index) {
                    console.log("page_settings", row, e, $(value));
                    Bsik.module.pages.manage.openSettingsModal($(e.target), "page", row.name || "");
                },
                'click .page_download': function(e, value, row, index) {
                    console.log("page_download", row);
                    //Bsik.module.users.roles.openPrivilegesModal(row);
                },
                'click .page_delete': function(e, value, row, index) {
                    console.log("page_delete", row);
                    Bsik.module.pages.manage.deletePage(row.name || "");
                }
            };

            /************* Set user actions **********/
            $.extend(Bsik.userEvents, {
                "click open-create-page-modal" : function(e) {
                    console.log("open create");
                    let $btn = $(this);
                    let $select = $("#create-input-page-type");
                    Bsik.modals.createElement.find(".is-valid, .is-invalid").removeClass("is-valid is-invalid");
                    Bsik.modals.createElement.find("form").trigger("reset");
                    $select.trigger("change");
                    Bsik.modals.create.show();
                },
                "change input-check-name" : function(e) {
                    let $input = $(this);
                    let name = $input.val();
                    $input.removeClass("is-valid is-invalid");
                    if (name.length !== 0) {
                        Bsik.module.pages.manage.checkNewPageName(
                            name,
                            function(finalName) {
                                $(this).addClass("is-valid");
                                $(this).val(finalName);
                            }.bind($input),
                            function(finalName) {
                                $(this).addClass("is-invalid");
                            }.bind($input)
                        );
                    }
                },
                "change input-load-templates" : function(e) {
                    console.log("load templates");
                    let $input = $(this);
                    let $select = $("#create-input-page-template");
                    let name = $input.val();
                    $select.html("");
                    Bsik.module.pages.manage.getPageTemplates(
                        name,
                        function(templates) {
                            for (const template of templates) {
                                $select.append(`<option value='${template.id}'>${template.name}</option>`);
                            }
                            $select.children("option").first().prop("selected", true);
                        }.bind($select)
                    );
                },
                "click create-page" : function(e) {
                    console.log("create");
                    Bsik.module.pages.manage.createPage(
                        "form#create-page-form", // Form to use
                        { //map values:
                            "create-input-page-name"            : "page-name",
                            "create-input-page-type"            : "page-type",
                            "create-input-page-display-name"    : "page-display",
                            "create-input-page-template"        : "page-template"
                        }
                    );
                },
                "click open-pages-settings-modal" : function(e) {
                    let $btn = $(this);
                    //console.log("click", $btn);
                    Bsik.module.pages.manage.openSettingsModal($btn, "global");
                },
                "click save-pages-settings" : function(e) {
                    let $btn = $(this);
                    //console.log("click", $btn);
                    Bsik.module.pages.manage.savePagesSettings($btn);
                },
                'change change-page-status': function(e) {
                    let $switch = $(this);
                    let name    = $switch.data("page");
                    console.log($switch, name);
                    Bsik.module.pages.manage.changePageStatus($switch, name);
                }
            });

            //Attach user actions:
            Bsik.core.helpers.onActions("click change", "data-action", Bsik.userEvents);


        })(jQuery, this, document, window.Bsik);
    }
});
