
import { SikDropdown } from '/bsik/manage/lib/js/sikDropdown.module.js';
import { FloatingMenu } from './fMenu.module.js';
import { OddsTable } from './oddsTable.module.js';
import { AppTab } from './appTab.module.js';
import { AppState } from './appState.module.js';



document.addEventListener("DOMContentLoaded", function(event) {

(function($, window, document, Bsik, undefined) {

    console.log("Loaded page", Bsik);

    //App state:
    var app_state = new AppState();

    //Hook sport:
    app_state.hook("sport", 
        function() {
            this.setState("sport", $(".sports-menu .item.selected").eq(0).data("sport"));
        }, 
        function() {
            $(`.sports-menu .item[data-sport]`).removeClass("selected");
            $(`.sports-menu .item[data-sport='${this.state.sport}']`).addClass("selected");
        }
    );

    //Hook control filters:
    app_state.hook("filters.today", 
        function() {
            this.setState("filters.today", $("#app-controls .control-item[data-filter='today']").hasClass("active"));
        }, 
        function() {
            let state = this.getState("filters.today");
            if (state !== null)
                $("#app-controls .control-item[data-filter='today']").toggleClass("active", state);
        }
    );
    //Hook control favorites:
    app_state.hook("filters.favorites", 
        function() {
            this.setState("filters.favorites", $("#app-controls .control-item[data-filter='favorites']").hasClass("active"));
        }, 
        function() {
            let state = this.getState("filters.favorites");
            if (state !== null)
                $("#app-controls .control-item[data-filter='favorites']").toggleClass("active", state);
        }
    );
    //Hook Table time sort:
    app_state.hook("sort.time", 
        function() {
            this.setState("sort.time", 
                $("section.odds th[data-state='time-sort']").hasClass("desc") ? "desc" : "asc"
            );
        }, 
        function() {
            let state = this.getState("sort.time");
            let ele = $("section.odds th[data-state='time-sort']");
            if (state === "desc") {
                ele.removeClass("asc").addClass("desc");
            } else if (state === "asc") {
                ele.removeClass("desc").addClass("asc");
            }
        }
    );
    app_state.init();

    // <th class="odds-th th-sized filters"    data-state="league-filter">League</th>
    //     <th class="odds-th th-sized sortable"   data-state="time-sort">Time</th>
    //     <th class="odds-th th-sized filters"    data-state="period-filter">Period</th>
    //     <th class="odds-th th-sized filters"    data-state="type-filter">Type</th>

    //Set modals:
    // Bsik.modals.register = new bootstrap.Modal(
    //     document.getElementById('user-register'),
    //     Bsik.core.helpers.objAttr.getDataAttributes("#user-register")
    // );
    // Bsik.modals.registerElement = $(Bsik.modals.register._element);
    
    //Crypto selector:
    // var dropdown = new SikDropdown("#crypto-select", {
    //     name        : "select-example",
    //     placeholder : "Select Currency",
    //     value       : null
    // });

    /************** Set filters and sort elements **********/
    $('.sports-menu .item[data-sport]').on("click", function() {
        if (!$(this).hasClass("selected")) {
            $(this).closest(".menu-items").find(".item.selected").removeClass("selected");
            $(this).addClass("selected");
            app_state.update();
            window.odds_table.loadSport();
        }
    });
    $("#app-controls .control-item").on("click", function() {
        $(this).toggleClass("active");
        app_state.update();
    });
    $("section.odds th[data-state='time-sort']").on("click", function() {
        if ($(this).hasClass("desc")) {
            $(this).removeClass("desc").addClass("asc");
        } else {
            $(this).removeClass("asc").addClass("desc");
        }
        app_state.update();
    });

    /************** Set scrollbars ******************/
    var odd_panel = document.querySelector('section.odds');
    window.Bsik.scrollbars["odd_panel"] = new PerfectScrollbar(odd_panel, {
        wheelSpeed: 1,
        wheelPropagation: true,
        minScrollbarLength: 20
    });

    /************* Set user menu *******************/
    var user_menu = new FloatingMenu("#user-menu", {
        outsideClickClose : true
    });
    window.user_menu = user_menu;

    /************* Set App user Tab **********/
    var app_tab = new AppTab("#app-tab", {

    });

    /************* Set Odds Table **********/
    var odds_table = new OddsTable('section.odds', app_state, {
        headerFilters  : [
            { col : "period",   name: "filters.periods.full",   text: "Full Game", value : "game",   enabled : true },
            { col : "period",   name: "filters.types.half",     text: "Half Time", value : "half",   enabled : true },
            { col : "type",     name: "filters.types.moneyline", text: "MoneyLine", value : "ml",   enabled : true },
            { col : "type",     name: "filters.types.underover", text: "UnderOver", value : "ou",   enabled : true },
            { col : "type",     name: "filters.types.handicap",  text: "Handicap",   value : "hdp", enabled : true }
        ]
    });
    window.odds_table = odds_table;
    console.log(odds_table);


    odds_table.updateOddsRequest([
        {
            id      : 1233,
            rot     : 2344,
            period  : "1st Quarter",
            date    : "05/11/2022",
            time    : "09:10 AM",
            start   : 643577,
            end     : 7826354,
            tmz     : "GMT+2",
            league  : { text : "NBA", abbr: "nba", id : 652},
            sport   : { text : "Basketball", abbr: "basketball", id : 63 },
            home    : "Home team",
            away    : "Away team",
            fav     : true,
            today   : true,
            odds    : {
                ps3838 : {
                    id : 64564, //bookmaker id:
                    ml : { home : -139, away : 122, his : []},
                    ou : [
                        { over : 115, under : 104, point : 221, main: false, his : []},
                        { over : 145, under : -181, point : 218, main: true, his : []},
                        { over : 160, under : -174, point : 212, main: false, his : []},
                    ],
                    hdp : [
                        { over : 210, under : 190, point : 2, main: false, his : []},
                        { over : 180, under : -181, point : 2.5, main: true, his : []},
                        { over : 190, under : -780, point : 3, main: false, his : []},
                    ]
                },
                betonline : {
                    id : 4568, //bookmaker id:
                    ml : { home : -119, away : 132, his : []},
                    ou : [
                        { over : 117, under : 104, point : 221, main: false, his : []},
                        { over : 140, under : -181, point : 218, main: true, his : []},
                        { over : 160, under : -185, point : 212, main: false, his : []},
                    ],
                    hdp : [
                        { over : 212, under : 190, point : 2, main: false, his : []},
                        { over : 185, under : -166, point : 2.5, main: true, his : []},
                        { over : 190, under : -480, point : 3, main: false, his : []},
                    ]
                }
            }
        }
    ]);

    /************* Set user actions **********/
    $.extend(Bsik.userEvents, {
        "click redirect" : function(event) {
            let to = $(this).data("link");
            console.log("redirect", to);
            Bsik.core.redirectPage(to, true);
        },
    });

    //Attach user actions:
    Bsik.core.helpers.onActions("click change", "data-action", Bsik.userEvents);

})(jQuery, window, document, window.Bsik);

});