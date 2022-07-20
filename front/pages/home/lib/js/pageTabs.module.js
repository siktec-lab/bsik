class PageTabs {
    alltabs = null;
    tabs    = {};
    active  = null;
    constructor(className, resizeson = []) {
        $(className).each((i,el) => {
            let name = $(el).attr("data-tab-name");
            this.tabs[name] = {
                el : $(el),
                resize : resizeson.hasOwnProperty(name) ? resizeson[name] : false
            };
        });
        this.allTabs = $(className);
        this.active = this.current();
        this.show(this.active);
    }
    current() {
        for (const [name, tab] of Object.entries(this.tabs)) { 
            if (tab.el.hasClass("current")) {
                return name;
            }
        }
    }
    hide(name) {
        if (this.tabs.hasOwnProperty(name)) {
            this.tabs[name].el.removeClass("current");
        }
    }
    show(name, url = false, title = false) {
        if (this.tabs.hasOwnProperty(name) && !this.tabs[name].el.hasClass("current")) {
            this.hide(this.active);
            this.tabs[name].el.addClass("current");
            this.active = name;
            if (url) {
                this.changeUrl(url, title);
            }
            if (this.tabs[name].resize) {
                $(this.tabs[name].resize)[0].slick.refresh();
            }
        }
    }
    changeUrl(url, title) {
        title = title === false ? document.title : title;
        window.history.pushState('data', title, url);
    }

};

export { PageTabs }