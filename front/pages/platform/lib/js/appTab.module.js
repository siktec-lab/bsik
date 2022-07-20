'use strict';

import { SikSlider } from '/bsik/front/global/lib/js/SikSlider.module.js';

export { AppTab }
export default class AppTab {
    
    slider;
    eles   = {
        tabs    : null,
        panel   : null,
        header  : null
    };

    defaults = {
    };
    options = {};
    constructor (element,  opt = {}) {
        this.eles.tabs = typeof element === 'string' 
                        ? document.querySelector(element) 
                        : element;  

        this._setOptions(opt);
        this._loadElements();

        //Set the tabs:
        this.slider = new SikSlider(this.eles.tabs, {
            animations : {
                // "anim1" : function(slide, show) {
                //     console.log("anim1", slide, show);
                // }
            },
            defaultAnimation : {
                show : "fade",
                hide : "fade",
                duration : 200 
            },
            slides : { // must use a slide name:
                "mylines"  : {
                    // after : function(slide ,show) { console.log("after anim slide 1", show); },
                    // before : function(slide ,show) { console.log("before anim slide 1", show); }
                },
                "ticker"  : {
                    // after : function(slide ,show) { console.log("after anim slide 2", show); },
                    // before : function(slide ,show) { console.log("before anim slide 2", show); }
                },
                "alerts"  : {
                    // after : function(slide ,show) { console.log("after anim slide 2", show); },
                    // before : function(slide ,show) { console.log("before anim slide 2", show); }
                },
            }
        });

        //Toggle tab content:
        this._on(this.eles.header, "click", "li[data-loadtab]", this._loadTabContentHandler);

        //Expand tab:
        this._on(this.eles.header, "click", "*", this._expandPanelHandler);


    }
    panelIsOpen() {
        return this.eles.panel.classList.contains("expanded");
    }
    expandPanel(open = true) {
        if (open)
            this.eles.panel.classList.add("expanded");
        else 
            this.eles.panel.classList.remove("expanded");
    }
    _expandPanelHandler(el, ev) {
        if (!this.panelIsOpen()) {
            this.expandPanel(true);
        } else {
            if (!ev.target.closest("li[data-loadtab]")) {
                this.expandPanel(false);
            }
        }
    }
    loadTabContent(tab) {
        console.log("load tab content", tab);
        //Header:
        let elesHeader = this.eles.header.querySelectorAll(`li[data-loadtab]`);
        let elHeader = this.eles.header.querySelector(`li[data-loadtab='${tab}']`);
        if (elHeader && !elHeader.classList.contains("selected")) {
            //Remove all:
            for (const el of elesHeader) el.classList.remove("selected");
            elHeader.classList.add("selected");
            //Slide
            console.log("slide", tab);
            this.slider.show(tab);
        }
    }
    _loadTabContentHandler(el, ev) {
        this.loadTabContent(el.getAttribute("data-loadtab"));
    }
    _loadElements() {
        this.eles.panel = this.eles.tabs.closest("section.panel");
        this.eles.header = this.eles.panel.querySelector("ul.tab-header");
    }
    _setOptions(opt) {
    	//Extend defaults:
        this._extend(
        	this.options, 
            this.defaults, 
            typeof opt === 'object' ? opt : {}
       );
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

}