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
    if (Bsik.loaded.module.sub === "bookmakers") {

        (function($, window, document, Bsik, undefined) {

            console.log(Bsik, window);

            //Set modals:
            Bsik.modals.create = new bootstrap.Modal(
                document.getElementById('new-bookmaker-modal'),
                Bsik.core.helpers.objAttr.getDataAttributes("#new-bookmaker-modal")
            );
            Bsik.modals.createElement = $(Bsik.modals.create._element);

            Bsik.module[Bsik.loaded.module.name].bookmakers = {

                /************ module specific js: ******************/
                openCreateBookmakerModal : function($btn, which, name = "") {
                    Bsik.modals.create.show();
                },
                resetCreateBookmakerModal : function(removeData = false) {
                    $("#create-bookmaker-form").find(".is-invalid").removeClass("is-invalid");
                    if (removeData) {
                        $("#create-bookmaker-form")[0].reset();
                    }
                },
                createNewBookmaker : function($btn) {
                    let data = Bsik.core.serializeToObject(
                        "#create-bookmaker-form", [], {
                            "bookmaker-logo"                : "book_logo", 
                            "create-bookmaker-display-name" : "book_text",
                            "create-bookmaker-sys-name"     : "book_name"
                        },
                        true
                    );
                    //Default logo:
                    if (data.book_logo.length < 1) {
                        data.book_logo = '#';
                    }

                    //remove invalids:
                    Bsik.module.scanner.bookmakers.resetCreateBookmakerModal(false);
                    console.log(data);

                    //Save bookmaker:
                    $btn.prop("disabled", true);
                    Bsik.core.apiRequest(null, "scanner.register_new_bookmaker", data, {
                        error: function(jqXhr, textStatus, errorMessage) {
                            let errors = jqXhr.responseJSON ? jqXhr.responseJSON.errors || [errorMessage] : [errorMessage];
                            Bsik.notify.error(`Add bookmaker error - ${errors.join()}`, true);
                            // console.log(jqXhr.responseJSON);
                            if (typeof jqXhr.responseJSON === 'object' && jqXhr.responseJSON.data) {
                                for (const [name, err] of Object.entries(jqXhr.responseJSON.data)) {
                                    if (typeof err === 'object' && Object.keys(err).length > 0) {
                                        console.log(name, err);
                                        switch (name) {
                                            case "book_active": {
                                                
                                            } break;
                                            case "book_name": {
                                                $("#create-bookmaker-sys-name").addClass("is-invalid");
                                            } break;
                                            case "book_text": {
                                                $("#create-bookmaker-display-name").addClass("is-invalid");
                                            } break;
                                        }
                                    }
                                }
                            }
                        },
                        success: function(res) {
                            $("#bookmakers-table").bootstrapTable('refresh', { silent : true });
                            Bsik.modals.create.hide();
                        },
                        complete: function() {
                            $btn.prop("disabled", false);
                        }
                    });
                    
                },
                deleteBookmaker : function(bookmaker, id) {
                    Bsik.core.helpers.getConfirmation(
                        `Please confirm the deletion of "<strong>${bookmaker}</strong>" Bookmaker - All the related data will be removed from your system and lost.`,
                        function yes(event) {
                            $(event.target).prop("disabled", true);
                            Bsik.core.apiRequest(null, "scanner.delete_bookmaker", { 
                                bookmaker : id
                            }, {
                                error: function(jqXhr, textStatus, errorMessage) {
                                    let error = jqXhr.responseJSON ? jqXhr.responseJSON.errors || [errorMessage] : [errorMessage];
                                    Bsik.notify.error(`Delete Bookmaker error - ${error.join()}`, true);
                                    console.log(jqXhr.responseJSON);
                                },
                                success: function(res) {
                                    Bsik.notify.bubble("info", `Deleted Bookmaker '${bookmaker}' successfully.`);
                                    $("#bookmakers-table").bootstrapTable('refresh', { silent : true });
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
                changeBookmakerActivation : function($btn, id) {
                    $btn.prop("disabled", true);
                    Bsik.core.apiRequest(null, "scanner.change_bookmaker_activation", { 
                        bookmaker     : id
                    }, {
                        error: function(jqXhr, textStatus, errorMessage) {
                            let error = jqXhr.responseJSON ? jqXhr.responseJSON.errors || [errorMessage] : [errorMessage];
                            Bsik.notify.error(`Change Bookmaker [${id}] activation error - ${error.join()}`, true);
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
                openEditBookmakerModal : function(btn, row) {
                    console.log("Edit bookmaker", btn, row);
                }
            };

            /***************************************************************/
            // Dynamic table formaters:
            /***************************************************************/
            Bsik.dataTables.formatters.bookmaker_display = function(value, data_row, index, header) {
                //console.log(value, data_row);
                return `
                    <span class="badge bg-elegant">
                        <img class="bookmaker-icon" src="${data_row.logo}" />&nbsp;&nbsp;
                        ${value.toUpperCase()}
                    </span>
                `;
            };
            Bsik.dataTables.formatters.bookmaker_logo = function(value, data_row, index, header) {
                return `<img class="bookmaker-icon" src="${value}" />`;
            };
            Bsik.dataTables.formatters.bookmaker_active = function(value, data_row, index, header) {
                return `<div class="form-check form-switch form-check-inline" ${Bsik.loaded.module.data.can_edit ? "" : "title='you cant change the page status - you need edit privileges to do that'" }>
                            <input 
                                name="bookmaker-active" 
                                data-bookmaker="${data_row.id}" 
                                class="form-check-input" 
                                type="checkbox" ${value ? "checked" : ""}
                                data-action="change-bookmaker-activation"
                                ${Bsik.loaded.module.data.can_edit ? "" : "disabled" }
                            />
                            <label class="form-check-label">
                                Disabled / Active
                            </label>
                        </div>`;
            };
            /***************************************************************/
            // Dynamic table actions:
            /***************************************************************/
            Bsik.tableOperateEvents = {
                'click .bookmaker_edit': function(e, value, row, index) {
                    Bsik.module.scanner.bookmakers.openEditBookmakerModal($(e.target), row);
                },
                'click .bookmaker_delete': function(e, value, row, index) {
                    console.log("bookmaker_delete", row);
                    Bsik.module.scanner.bookmakers.deleteBookmaker(row.text || "", row.id);
                }
            };

            /***************************************************************/
            // Select Bookmaker icon:
            /***************************************************************/
            var IconDropdown = new SikDropdown("#create-bookmaker-select-logo-dropdown", {
                name        : "bookmaker-logo",
                placeholder : "Select Icon",
                value       : null
            });

            /***************************************************************/
            // Bookmaker icon upload:
            /***************************************************************/
            Bsik.core.asyncUploadFileField(
                document.querySelector('#bookmakers-upload-thumbs'), 
                null,
                "scanner.upload_bookmaker_logo", 
                { "filepond" : "test" },
                {
                    onload: (response) => {
                        //Repopulate select box:
                        Bsik.core.apiRequest(null, "scanner.bookmaker_logo_files", {}, {
                            error: function(jqXhr, textStatus, errorMessage) {
                                let error = jqXhr.responseJSON ? jqXhr.responseJSON.errors || [errorMessage] : [errorMessage];
                                Bsik.notify.error(`say hello error - ${error.join()}`, true);
                                console.log(jqXhr.responseJSON);
                            },
                            success: function(res) {
                                if (typeof res.data === 'object') {
                                    IconDropdown.clear();
                                    for (const [key, value] of Object.entries(res.data)) {
                                        let label = value.split('/').pop();
                                        IconDropdown.addItem(value, `<img class="bookmaker-icon" src="${value}" />&nbsp;${label}`);
                                    }
                                }
                            }
                        });
                        return response;
                    },
                    onerror: (response) => { 
                        //This part is important because it give us the ability to set the response body.
                        return response;
                    }
                },
                {
                    allowMultiple : true,
                    labelFileProcessingError: function(response) {
                        // replaces the error on the FilePond error label
                        let resp = Bsik.core.jsonParse(response.body);
                        return resp.errors[0];
                    },
                    fileRenameFunction: (file) => new Promise((resolve) => {
                        resolve(window.prompt('Enter new filename', file.name));
                    })
                },
                [
                    FilePondPluginFileValidateType,
                    FilePondPluginFileRename
                ]
            );

            /************* Set user actions **********/
            $.extend(Bsik.userEvents, {
                "click open-create-bookmaker-modal" : function(e) {
                    console.log("open create");
                    Bsik.module.scanner.bookmakers.resetCreateBookmakerModal(true);
                    Bsik.module.scanner.bookmakers.openCreateBookmakerModal();
                },
                "click create-new-bookmaker" : function(e) {
                    Bsik.module.scanner.bookmakers.createNewBookmaker($(this));
                },
                'change change-bookmaker-activation': function(e) {
                    let $switch = $(this);
                    let id    = $switch.data("bookmaker");
                    console.log($switch, id);
                    Bsik.module.scanner.bookmakers.changeBookmakerActivation($switch, id);
                }
            });

            //Attach user actions:
            Bsik.core.helpers.onActions("click change", "data-action", Bsik.userEvents);


        })(jQuery, window, document, window.Bsik);
    }
});
