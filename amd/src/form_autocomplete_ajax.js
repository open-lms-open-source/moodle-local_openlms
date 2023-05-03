// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Provides support for general form autocomplete via ajax.
 *
 * @module      local_openlms/form_autocomplete_ajax
 * @copyright   2023 Open LMS (https://www.openlms.net/)
 * @author      Petr Skoda
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/**
 * Load the list of items via WS.
 *
 * @param {String} selector The selector of the auto complete element.
 * @param {String} query The query string.
 * @param {Function} callback A callback function receiving an array of results.
 * @param {Function} failure A function to call in case of failure, receiving the error message.
 */
export async function transport(selector, query, callback, failure) {

    const wsmethod = document.querySelector(selector).getAttribute('data-ws-method');
    var wsarguments = document.querySelector(selector).getAttribute('data-ws-args');
    wsarguments = JSON.parse(wsarguments);

    if (typeof query === "undefined") {
        wsarguments.query = '';
    } else {
        wsarguments.query = query;
    }

    const request = {
        methodname: wsmethod,
        args: wsarguments
    };

    try {
        const response = await Ajax.call([request])[0];

        if (response.notice !== null) {
            callback(response.notice);
        } else {
            callback(response.list);
        }
    } catch (e) {
        failure(e);
    }
}

/**
 * Process the results for auto complete elements.
 *
 * @param {String} selector The selector of the auto complete element.
 * @param {Array} results An array or results returned by {@see transport()}.
 * @return {Array} New array of the selector options.
 */
export function processResults(selector, results) {
    return results;
}
