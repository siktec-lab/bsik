'use strict';

class OddsTable {

    el = {
        wrapper : null,
        loader  : null,
        table   : null,
        header  : null,
        body    : null
    };

    cols = {};

    defaults = {
        headerFilters  : []
    };

    options = {};

    appState = {};

    bookmakers = [ //TODO: load and change based on state filters:
        { name : 'ps3838',      id : 76234,      text : "PS3838",       icon : "ps3838" },
        { name : 'betonline',   id : 1548,       text : "BETONLINE",    icon : "betonline" },
        { name : 'bookmaker',   id : 456,        text : "BOOKMAKER",    icon : "bookmaker" },
        { name : 'bet365',      id : 78987,      text : "BET365",       icon : "bet365" }
    ];

    templates = {
        filterList : "<ul class='filter-select'></ul>",
        filterEle  : `
            <li>
                <div class="form-check">
                    <label class="form-check-label">
                        <input class="form-check-input" type="checkbox" data-state="%%" value="%%" %%/>%%
                    </label>
                </div>
            </li>
        ` 
    };

    constructor(wrapper, appState, opt = {}) {
        //The menu element:
        this.el.wrapper = typeof wrapper === 'string'
                      ? document.querySelector(wrapper)
                      : wrapper;

        //The app state to handle filters and more:
        this.appState = appState;

        //Set options:
        this.setOptions(opt);
        
        //Set elements:
        this.parseEles();

        this.loadFilters();
        //this.loading(true);

        //Attach handlers:
        this.attachHandlers();
    }
    
    setOptions(opt) {
        this._extend(this.options, this.defaults, opt);
    }

    parseEles() {
        this.el.loader  = this.el.wrapper.querySelector("div.odds-loading");
        this.el.table   = this.el.wrapper.querySelector("table.odds-table");
        this.el.header  = this.el.table.querySelector("thead");
        this.el.body    = this.el.table.querySelector("tbody");

        //Set cols:
        let _cols = this.el.header.querySelectorAll("th");
        let colindex = 0;
        for (const col of _cols) {
            this.cols[col.getAttribute("data-colname")] = {
                el      : col,
                name    : col.getAttribute("data-colname"),
                index   : colindex++,
                filter  : col.classList.contains("filters"),
                sort    : col.classList.contains("sortable")
            }
        }
    }

    loading(state = false) {
        if (state)
            this.el.loader.classList.add("active");
        else 
            this.el.loader.classList.remove("active");
    }

    loadSport() {
        let sport = this.appState.getState("sport");
        //Get needed types:
        console.log("change sport", sport);
    }

    updateOddsRequest(rows) {
        
        //TODO: this ticks - fire every 3 seconds:

        
        //Get needed types:
        //Get needed periods:
        //Get needed time:
        //Get needed leagues:
        //Only today?
        //get mylines list: -> this is saved in db:
        //Get alerts check: -> this is saved in db:

        //Build games token list:
        //TODO: games are rotations with the latest update token.
                //this way only new data will be returned and remove add orders: 
        
        //Load received results:
        for (const row of rows) {
            this.updateGame(row);
        }
    }

    hasGameRow(id) {
        let row = this.el.body.querySelector(`tr.row-start[data-id='${id}']`);
        return !!row;
    }

    getGameRow(id) {
        let row = this.el.body.querySelector(`tr.row-start[data-id='${id}']`);
        if (row) {
            return row.gameRow;
        }
        return null;
    }

    updateGame(row) {
        //Is it drawn:
        let data = typeof row === 'string' ? this._jsonParse(obj) : row;
        let game = this.getGameRow(data.id);
        if (game !== null) {
            //Update the data - will also remove:
            game.update(data);
            game.draw();
        } else {
            //Create new:
            this.createGame(data);
        }
    }

    createGame(data) {
        let row = new GameRow(data, this);
        row.draw();
    }

    loadFilters() {
        
        //clean:
        for (const [name, col] of Object.entries(this.cols)) {
            if (col.filter) {
                let select = col.el.querySelector("ul.filter-select");
                if (select) {
                    select.innerText = null;
                } else {
                    col.el.append(this._createEle(this.templates.filterList));
                }
            }
        }

        //Load filters:
        for (const filter of this.options.headerFilters) {
            let col = this.cols[filter.col];
            if (col && col.filter) {
                let list = col.el.querySelector("ul.filter-select");
                let state = this.appState.getState(filter.name) ?? filter.enabled;
                let entry = this._formatStr(
                    this.templates.filterEle,
                    filter.name,
                    filter.value,
                    state ? "checked" : "",
                    filter.text
                );
                let listEle = this._createEle(entry);
                list.append(listEle);
            }
        }


    }

    attachHandlers() {
        //Save user selected filter states:
        this._on(this.el.header, 'change', "input.form-check-input[data-state]", function(el, ev){
            this.appState.setState(
                el.getAttribute("data-state"),
                el.checked
            );
            this.appState._saveToStorage();
        });
        //Toggle select filters list:
        this._on(this.el.header, 'click', "th.filters", function(el, ev){
            if (!ev.target.closest("ul.filter-select")) {
                if (el.classList.contains("open")) {
                    el.classList.remove("open");
                } else {
                    el.classList.add("open");
                }
            }
        });
    }

    _handler(el, ev) {
        // if (el.classList.contains('open')) {
        //     this._close(el);
        // } else {
        //     this._open(el);
        // }
    }

    _createEle(str) {
        var template = document.createElement('template');
        template.innerHTML = str.trim();
        return template.content.firstChild;
    }

    _on(ele, type, selector, handler) {
        let _this = this;
        ele.addEventListener(type, function(ev) {
            let el = ev.target.closest(selector);
            if (el) handler.call(_this, el, ev); //The element is bind to this
        });
    }

    _extend() {
        for(var i=1; i<arguments.length; i++)
            for(var key in arguments[i])
                if(arguments[i].hasOwnProperty(key))
                    arguments[0][key] = arguments[i][key];
        return arguments[0];
    }

    _getKeyByValue(object, value) {
        return Object.keys(object).find(key => object[key] === value) || value;
    }

    _getSelector(el) {
        if (el.tagName.toLowerCase() === "html")
            return "html";
        var str = el.tagName.toLowerCase();
        str += (el.id != "") ? "#" + el.id : "";
        if (el.className) {
            var classes = el.className.trim().split(/\s+/);
            for (var i = 0; i < classes.length; i++) {
                str += "." + classes[i]
            }
        }
        if(document.querySelectorAll(str).length==1) return str;
        return getSelector(el.parentNode) + " > " + str;
    }

    _formatStr(fmt, ...args) {
        return fmt
            .split("%%")
            .reduce((aggregate, chunk, i) =>
                aggregate + chunk + (typeof args[i] !== 'undefined' ? args[i] : ""), "");
    }

    _jsonParse(json) {
        try {
            return JSON.parse(json);
        } catch (e) {}
        return null;
    }

}

class GameRow {

    data    = {
        id      : 0,
        rot     : 0,
        period  : "1st Quarter",
        date    : "",
        time    : "",
        start   : 0,
        end     : 0,
        tmz     : "GMT+2",
        league  : { text : "LEAGUE", abbr: "league", id : 0},
        sport   : { text : "SPORT", abbr: "sport", id : 0 },
        home    : "Home",
        away    : "Away",
        fav     : false,
        today   : false,
        odds    : {}
    };

    table   = null;
    row     = null;
    parts   = {};

    constructor(obj, table) {
        //the game line obj:
        if (this.update(obj)) {
            this.table = table;
            console.log(this);
        } else {
            console.warn("got a game row with invalid data", obj);
        }
    }

    update(data) {
        console.log("updating game row!");
        //update the game row data:
        this._extend(this.data, (typeof data === 'string' ? this._jsonParse(data) : data));
        return typeof this.data.id === 'number' && this.data.id > 0;
    }

    draw() {
        console.log("drawing game row!");
        if (this.row === null) {
            //new row create:
            console.log("drawing NEW game row!");
            this.create();
        } else {
            //Partial update:
            console.log("drawing UPDATE game row!");
        }
    }

    create() {
        let sep = `<tr class="row-sep"></tr>`;
        let padBookmakers = Array(this.table.bookmakers.length).fill('<td class="data-bookmaker">bookmaker</td>').join('');
        let ml  = `
            <tr class="game-row row-start row-ml" data-id="${this.getId()}">
                <td class="lbl-league">
                    <i class="icon-tombet icon-${this.data.sport.abbr}-2"></i>
                    <span class="league-abbr">${this.data.league.text}</span>
                </td>
                <td class="lbl-time">
                    <span class="time-date">${this.data.date}</span>
                    <span class="time-hour">${this.data.time}</span>
                </td>
                <td class="lbl-period">${this.data.period}</td>
                <td class="lbl-moneyline">
                    <i class="icon-tombet icon-ml"></i>
                    <span class="type-abbr">ML</span>
                </td>
                ${padBookmakers}
            </tr>
        `;
        let ou = `
            <tr class="game-row row-middle row-ou ${ this.data.fav ? "is-fav" : "" }">
                <td class="lbl-game-general" colspan="3" rowspan="2">
                    <span class="away-team">${ this.data.away }</span>
                    <span class="home-team">
                        <i class="icon-tombet icon-home"></i>
                        ${ this.data.home }
                    </span>
                    <span class="fav-game-tag"><i class="icon-tombet icon-star"></i></span>
                    <span class="focus-game-tag"><i class="icon-tombet icon-focus"></i></span>
                </td>
                <td class="lbl-overunder">
                    <i class="icon-tombet icon-ou"></i>
                    <span class="type-abbr">OU</span>
                </td>
                ${padBookmakers}
            </tr>
        `;
        
        let hdp = `
            <tr class="game-row row-end row-hdp">
                <td class="lbl-handicap">
                    <i class="icon-tombet icon-hdp"></i>
                    <span class="type-abbr">HDP</span>
                </td>
                ${padBookmakers}
            </tr>
        `;
        
        this.parts = {
            sep     : this.table._createEle(sep),
            start   : this.table._createEle(ml),
            middle  : this.table._createEle(ou),
            end     : this.table._createEle(hdp)
        };

        //Add to table:
        this.table.el.body.append(this.parts.sep);
        this.table.el.body.append(this.parts.start);
        this.table.el.body.append(this.parts.middle);
        this.table.el.body.append(this.parts.end);

        //Run bookmakers create:

        //Reveal: which is basically run filters:
    }

    remove() {
        console.log("removing game row!");
    }

    getRot() {
        return this.data.rot;
    }
    getId() {
        return this.data.id;
    }
    _jsonParse(json) {
        try {
            return JSON.parse(json);
        } catch (e) {}
        return null;
    }
    _extend() {
        for(var i=1; i<arguments.length; i++)
            for(var key in arguments[i])
                if(arguments[i].hasOwnProperty(key))
                    arguments[0][key] = arguments[i][key];
        return arguments[0];
    }
}


// {
//     id      : 1233,
//     rot     : 2344,
//     period  : "1st Quarter",
//     date    : "03/11/2022",
//     time    : "03:10 AM",
//     start   : 643577,
//     end     : 7826354,
//     tmz     : "GMT+2",
//     league  : { text : "NBA", abbr: "nba", id : 652},
//     sport   : { text : "Basketball", abbr: "basketball", id : 63 },
//     home    : "Home team",
//     away    : "Away team",
//     fav     : true,
//     today   : true,
//     odds    : {
//         ps3838 : {
//             id : 64564, //bookmaker id:
//             ml : { home : -139, away : 122, his : []},
//             ou : [
//                 { over : 115, under : 104, point : 221, main: false, his : []},
//                 { over : 145, under : -181, point : 218, main: true, his : []},
//                 { over : 160, under : -174, point : 212, main: false, his : []},
//             ],
//             hdp : [
//                 { over : 210, under : 190, point : 2, main: false, his : []},
//                 { over : 180, under : -181, point : 2.5, main: true, his : []},
//                 { over : 190, under : -780, point : 3, main: false, his : []},
//             ]
//         }
//     }

// }

export { OddsTable }