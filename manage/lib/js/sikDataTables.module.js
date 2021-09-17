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
import * as $$ from './utils.module.js';
import * as SikCore from './core.module.js';

export var formatters = {};

export function get(url, req, params) {
    console.log(params);
    return SikCore.apiRequest(
        url,
        req,
        params.data, {
            error: params.error,
            success: function(res) {
                console.log("shlomi", res);
                params.success(res.data);
            }
        }
    );
}