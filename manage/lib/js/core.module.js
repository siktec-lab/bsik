/******************************************************************************/
// Created by: SIKTEC.
// Release Version : 1.0.0
// Creation Date: 2021-03-17
// Copyright 2021, SIKTEC.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.0:
    -> initial
*******************************************************************************/
import * as helpers from './utils.module.js';
export { helpers };
/******************************  PORT INNER UTILS  *****************************/

/******************************  NOTIFY HANDLERS  *****************************/

/**
 * @param {String} HTML representing a single element
 * @return {Element}
 */
export function apiRequest(url, req, _data, handlers, formData = false) {
    //Set url:
    url = url ? url : document.querySelector("meta[name='api']").getAttribute("content");
    //Tokenize the request:
    let data = {
        'request_token': document.querySelector("meta[name='csrf-token']").getAttribute('content'),
        'request_type': req,
    };
    //Prep the data - normal object or formData:
    let prepData;
    if (formData) {
        prepData = new FormData();
        prepData.append('request_token', data.request_token);
        prepData.append('request_type',  data.request_type);
        for (const key in _data) {
            prepData.append(key,  _data[key]);
        }
    } else {
        prepData = $.extend(data, _data);
    }
    //Build request:
    let ajaxSet = {
        type: 'POST',
        dataType: 'json',
        data: prepData,
        success: function(data) {},
        error: function(jqXhr, textStatus, errorMessage) {
            console.log("ERROR on AJAX", errorMessage);
            console.log("ERROR on AJAX", jqXhr);
            console.log("ERROR on AJAX", textStatus);
        },
        complete: function(data) {
            //console.log(data);
        },
    };
    //If formdata add some settings:
    if (formData) {
        ajaxSet.contentType = false;
        ajaxSet.enctype     = 'multipart/form-data';
        ajaxSet.processData = false;
    }
    //Extend settings & handlers:
    $.extend(ajaxSet, handlers);
    //Execute:
    /* SH: added - 2021-05-17 => Remove later this is for dev */
    console.log(url, ajaxSet);
    return $.ajax(url, ajaxSet);
}

export function serializeToObject(form, exclude) {
    exclude || (exclude = []);
    let obj = {},
        $form = $(form);
    if ($form.length) {
        $form
            .find("input, select, textarea") // Loop all input fields 
            .not(':input[type=button], :input[type=submit], :input[type=reset]') // We don't want those:
            .each(function(i, e) {
                let _name = (e.name) ? e.name : e.id; //Make sure we have names otherwise use the ID:
                if (_name.length && exclude.indexOf(_name) === -1) { //If not excluded:
                    obj[_name] = $(e).val() || "";
                }
            });
    }
    return obj;
}

export function redirectPost(to, args, method = "POST", absolute = false) {
    let form = '';
    let path = absolute ? (baseUrl + to) : to;
    $.each(args, function(key, value) {
        value = (typeof value === 'string') ? value.split('"').join('\"') : value;
        form += '<input type="hidden" name="' + key + '" value="' + value + '">';
    });
    $('<form action="' + path + '" method="' + method + '">' + form + '</form>').appendTo($(document.body)).submit();
}

export function redirectPage(to, click = false, delay = 0, absolute = false) {
    setTimeout(function() {
        let path = absolute ? (baseUrl + to) : to;
        if (click) window.location.href = path;
        else window.location.replace(path);
    }, delay);
}

export function reloadPage(delay = 0) {
    this.redirectPage(window.location.href, false, delay, false);
}

export function openInNewTab(href) {
    Object.assign(document.createElement('a'), {
        target: '_blank',
        href: href,
    }).click();
}

export function updateUrl(data, title, url) {
    if (typeof window.history.replaceState === 'function') {
        window.history.replaceState(data, title, url);
    }
}

export function format(fmt, ...args) {
    return fmt
        .split("%%")
        .reduce((aggregate, chunk, i) =>
            aggregate + chunk + (typeof args[i] !== 'undefined' ? args[i] : ""), "");
}

export function getCharacterLength(str) {
    return [...str].length;
}

export function getKeyByValue(object, value) {
    return Object.keys(object).find(key => object[key] === value);
}

export function scrollToAnimated(selector, speed = 800) {
    $([document.documentElement, document.body]).animate({
        scrollTop: $(selector).eq(0).offset().top
    }, speed);
}