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
    console.log(Bsik);
    // Bsik.module[Bsik.loaded.module.name].publish_statuses = [
    //     { "value" : 1, "text" : "draft"  },
    //     { "value" : 2, "text" : "active" }
    // ];
    // Bsik.module[Bsik.loaded.module.name].shipping_templates = JSON.parse($(document).find("meta[name='shippping_templates']").attr("content") || '[]');

    /** Module Settings: *************************************************/

    if (Bsik.loaded.module.sub === "modules") {

        (function($, window, document, Bsik, undefined) {

            console.log(window);
            console.log(Bsik);

            Bsik.module[Bsik.loaded.module.name].modules = {

                /************ Initiate the base js object: ******************/
                uploadFile($btn) {        
                    let file = $("#input-manual-module")[0].files[0];
                    if (!file) {
                        Bsik.notify.warn("Select a ZIP module file first.", true);
                        return;
                    }
                    Bsik.core.apiRequest(null, "install_module_file", { module_file : file }, 
                        {
                            error: function(jqXhr, textStatus, errorMessage) {
                                Bsik.notify.error(`Operation module install failed - ${errorMessage}`, true);
                            },
                            success: function(res) {
                                console.log(arguments);
                                if (res.errors.length) {
                                    console.log(typeof res.errors, typeof []);
                                    Bsik.notify.error(`
                                        Cant install module : ${
                                            typeof res.errors === 'string' ? res.errors : res.errors[0]
                                        }
                                    `, true);
                                } else {

                                }
                                // switch (res.data.result) {
                                //     case "added":
                                //         Bsik.notify.bubble("info", `Added ${_upc} to publish que`);
                                //         break;
                                //     case "que":
                                //         Bsik.notify.bubble("warn", `${_upc} allready in que`);
                                //         break;
                                //     case "published":
                                //         Bsik.notify.bubble("warn", `${_upc} allready published`);
                                //         break;
                                //     case "ignored":
                                //         Bsik.notify.bubble("warn", `${_upc} is not valid and set as ignored`);
                                //         break;
                                //     default:
                                //         Bsik.notify.error(`Adding ${_upc} to que failed with unknown error`);
                                // }
                                // if (table_refresh)
                                //     $(table_refresh).bootstrapTable('refresh', { silent : true });
                            }
                        },
                        true
                    );
                }
            };

            /************* Add dynamic table operation handler **********/
            Bsik.tableOperateEvents = {
                'click .like': function(e, value, row, index) {
                    console.log('You click like action, row: ' + JSON.stringify(row));
                },
                'click .delete': function(e, value, row, index) {
                    console.log("delete row called!", this);
                    this.$el.bootstrapTable('remove', {
                        field: 'id',
                        values: [row.id]
                    })
                }
            };
            /************* Add dynamic table formmaters handler **********/
            Bsik.dataTables.formatters.inventory = function(value, data_row, index, header) {
                return (value >= 10) ? "+10" : value;
            };
            /************* Set user actions **********/
            $.extend(Bsik.userEvents, {
                "click upload-module-btn" : function(e) {
                    Bsik.module.settings.modules.uploadFile($(this));
                },
            });
            //Attach user actions:
            Bsik.core.helpers.onActions("click change", "data-action", Bsik.userEvents);

        })(jQuery, this, document, window.Bsik);
    }
});













// document.addEventListener("DOMContentLoaded", function(event) {

//     /** Global module code: *************************************************/
//     // console.log(Bsik);
//     // Bsik.module[Bsik.loaded.module.name].publish_statuses = [
//     //     { "value" : 1, "text" : "draft"  },
//     //     { "value" : 2, "text" : "active" }
//     // ];
//     // Bsik.module[Bsik.loaded.module.name].shipping_templates = JSON.parse($(document).find("meta[name='shippping_templates']").attr("content") || '[]');

//     /** Module Settings: *************************************************/

//     if (Bsik.loaded.module.sub === "settings") {

//         (function($, window, document, Bsik, undefined) {

//             console.log(window);
//             console.log(Bsik);

//             Bsik.module[Bsik.loaded.module.name].publish = {

//                 /************ Initiate the base js object: ******************/
                

                

                
//             };

//             /************* Add dynamic table operation handler **********/
//             Bsik.tableOperateEvents = {
//                 'click .like': function(e, value, row, index) {
//                     console.log('You click like action, row: ' + JSON.stringify(row));
//                 },
//                 'click .delete': function(e, value, row, index) {
//                     console.log("delete row called!", this);
//                     this.$el.bootstrapTable('remove', {
//                         field: 'id',
//                         values: [row.id]
//                     })
//                 }
//             };
//             /************* Add dynamic table formmaters handler **********/
//             Bsik.dataTables.formatters.inventory = function(value, data_row, index, header) {
//                 return (value >= 10) ? "+10" : value;
//             };
//             /************* Set user actions **********/
//             $.extend(Bsik.userEvents, {
//                 "change listing-set-profit" : function(e) {
//                     let table = $("#publish-que-table").bootstrapTable('getData',{ useCurrentPage : true, includeHiddenRows : true })
//                     let profit = $(this).val();
//                     let row = $(this).closest("tr[data-index]").attr("data-index");
//                     Bsik.module.amazon.publish.save_profit(table[row].id, profit, "#publish-que-table");
//                 },
//             });
//             //Attach user actions:
//             Bsik.core.helpers.onActions("click change", "data-action", Bsik.userEvents);

//         })(jQuery, this, document, window.Bsik);
//     }
// });