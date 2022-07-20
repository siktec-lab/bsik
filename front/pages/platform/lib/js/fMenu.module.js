
//The menu js class:
class FloatingMenu {
    menuEl = null;

    defaults = {
        outsideClickClose : false,
    };

    options = {};

    constructor(_menu, opt = {}) {
        //The menu element:
        this.menuEl = typeof _menu === 'string'
                      ? document.querySelector(_menu)
                      : _menu;
        //Set options:
        this.setOptions(opt);
        //Attach handlers:
        this.attachHandlers();
    }
    setOptions(opt) {
        this._extend(this.options, this.defaults, opt);
    }
    attachHandlers() {
        if (this.menuEl) {
            this._on(this.menuEl, 'click', '.trigger-menu', this._handler.bind(this));
        }
        if (this.options.outsideClickClose) {
            document.addEventListener('click', this._closeAllHandler.bind(this));
            //this._on(document, 'click', '*', this._closeAllHandler.bind(this));
        }
    }
    _open(item) {
        let opened = item.closest('.fmenu').querySelectorAll('.trigger-menu.open');
        for (const ele of opened) {
            this._close(ele);
        }
        item.classList.add('open');
        //expand:
        let list = item.closest('li').querySelector(".floating-menu");
        list.style.setProperty("max-height", this._measureExpandableList(list));
        list.style.setProperty("opacity", "1");
        item.style.setProperty("max-width", this._measureExpandableTrigger(item));
    }
    _close(item) {
        let list = item.closest('li').querySelector(".floating-menu");
        item.classList.remove('open');
        //shrink:
        list.style.removeProperty("max-height");
        list.style.removeProperty("opacity");
        item.style.removeProperty("max-width");
    }
    closeAll() {
        let opened = this.menuEl.querySelectorAll('.trigger-menu.open');
        for (const ele of opened) {
            this._close(ele);
        }
    }
    _measureExpandableList(list) {
        const items = list.querySelectorAll('li');
        return (items.length * this._getHeight(items[0], "outer") + 10) + 'px';
    }
    _measureExpandableTrigger(item) {
        const textEle = item.querySelector('.text');
        const sizeBase = this._getWidth(item, "outer");
        const sizeExpandLabel = this._getWidth(textEle, "outer");
        return (sizeBase + sizeExpandLabel + 6) + 'px';
    }
    _closeAllHandler(ev) {
        if (!ev.target.closest(this._getSelector(this.menuEl))) {
            this.closeAll();
        }
    }
    _handler(el, ev) {
        if (el.classList.contains('open')) {
            this._close(el);
        } else {
            this._open(el);
        }
    }
    _on(ele, type, selector, handler) {
        ele.addEventListener(type, function(ev) {
            let el = ev.target.closest(selector);
            if (el) handler.call(this, el, ev); //The element is bind to this
        });
    }
    _getWidth(el, type) {
        if (type === 'inner') 
            return el.clientWidth;
        else if (type === 'outer') 
            return el.offsetWidth;
        return 0;
    }
    _getHeight(el, type) {
        if (type === 'inner')
            return el.clientHeight;
        else if (type === 'outer')
            return el.offsetHeight;
        return 0;
    }
    _extend() {
        for(var i=1; i<arguments.length; i++)
            for(var key in arguments[i])
                if(arguments[i].hasOwnProperty(key))
                    arguments[0][key] = arguments[i][key];
        return arguments[0];
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
}

export { FloatingMenu }