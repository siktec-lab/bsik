'use strict';

//The menu js class:
class AppState {

    key = "app_state";

    storage = null;

    state = {
        // sport   : null,
        // filters : {
        //     today       : false,
        //     favorites   : false
        // },
    };

    proc = [];

    constructor () {
        //Local storage is availabe:
        if (this._storageAvailable('localStorage')) {
            this.storage = window.localStorage;
        }
    }
    init() {
        //has saved state?
        if (this.storage && this.storage.getItem(this.key)) {
            this.loadFromStorage();
        } else {
            this.update();
        }
    }
    hook(path, update, load) {
        this.proc.push({
            state   : path,
            update  : update,
            load    : load
        });
    }
    loadFromStorage() {
        this.state = JSON.parse(this.storage.getItem(this.key));   
        for (const p of this.proc) {
            p.load.call(this);
        }
    }
    update() {
        for (const p of this.proc) {
            p.update.call(this);
        }
        this._saveToStorage();
    }
    hasState(path) {
        return this._getProperty(this.state, path, 'none') !== 'none';
    }
    getState(path, def = null) {
        return this._getProperty(this.state, path, def);
    }

    setState(path, value) {
        this._setProperty(this.state, path, value);
    }

    _saveToStorage() {
        if (this.storage) {
            this.storage.setItem(this.key, JSON.stringify(this.state));
        }
    }

    _storageAvailable(type) {
        var storage;
        try {
            storage = window[type];
            var x = '__storage_test__';
            storage.setItem(x, x);
            storage.removeItem(x);
            return true;
        }
        catch(e) {
            return e instanceof DOMException && (
                // everything except Firefox
                e.code === 22 ||
                // Firefox
                e.code === 1014 ||
                // test name field too, because code might not be present
                // everything except Firefox
                e.name === 'QuotaExceededError' ||
                // Firefox
                e.name === 'NS_ERROR_DOM_QUOTA_REACHED') &&
                // acknowledge QuotaExceededError only if there's something already stored
                (storage && storage.length !== 0);
        }
    }

    _getProperty(obj, path, defaultValue = '-') {
        const value = path.split('.').reduce((o, p) => o && o[p], obj);
        return value || value === false ? value : defaultValue;
    }

    _setProperty(obj, path, value) {
        var schema = obj; 
        var pList = path.split('.');
        var len = pList.length;
        for(var i = 0; i < len-1; i++) {
            var elem = pList[i];
            if( !schema[elem] ) schema[elem] = {}
            schema = schema[elem];
        }
        schema[pList[len-1]] = value;
    }


}

export { AppState }