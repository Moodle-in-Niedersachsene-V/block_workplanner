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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * The way back from an activity to the work planner.
 *
 * When a learner opens an activity from the planner, the planner marks the course in the
 * session storage of the tab. On the pages of that course a notice offers the way back.
 * When the learner lands on the course page coming from an activity, for example after
 * "Save and return to course", the browser returns to the planner straight away. The mark
 * is kept only for this tab and expires after three hours.
 *
 * @module     block_workplanner/return
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Templates from 'core/templates';

const KEY = 'block_workplanner_return';
const VALID_MS = 3 * 60 * 60 * 1000;

/**
 * Returns the session storage, or null when the browser does not allow it.
 *
 * @returns {Storage|null}
 */
const storage = () => {
    try {
        return window.sessionStorage;
    } catch (e) {
        return null;
    }
};

/**
 * Marks that the user opens an activity of a course from the planner.
 *
 * @param {Number} courseid Course of the activity.
 */
export const remember = (courseid) => {
    const store = storage();
    if (!store || !courseid) {
        return;
    }
    try {
        store.setItem(KEY, JSON.stringify({courseid, time: Date.now()}));
    } catch (e) {
        // Without storage the planner works as before, only the way back is missing.
        return;
    }
};

/**
 * Removes the mark.
 */
export const forget = () => {
    const store = storage();
    if (!store) {
        return;
    }
    try {
        store.removeItem(KEY);
    } catch (e) {
        return;
    }
};

/**
 * Reads the mark when it belongs to the given course and has not expired.
 *
 * @param {Number} courseid Course of the current page.
 * @returns {Boolean}
 */
const isMarked = (courseid) => {
    const store = storage();
    if (!store) {
        return false;
    }
    let mark = null;
    try {
        mark = JSON.parse(store.getItem(KEY));
    } catch (e) {
        return false;
    }
    if (!mark || mark.courseid !== courseid) {
        return false;
    }
    if (Date.now() - mark.time > VALID_MS) {
        forget();
        return false;
    }
    return true;
};

/**
 * Tells whether the user comes from an activity page to the course page.
 *
 * @returns {Boolean}
 */
const isReturnFromActivity = () => {
    if (window.location.pathname.indexOf('/course/view.php') === -1 || !document.referrer) {
        return false;
    }
    try {
        const referrer = new URL(document.referrer);
        return referrer.origin === window.location.origin && referrer.pathname.indexOf('/mod/') !== -1;
    } catch (e) {
        return false;
    }
};

/**
 * Shows the notice with the way back at the top of the main region.
 *
 * @param {String} url Address of the planner.
 * @returns {Promise}
 */
const showNotice = (url) => Templates.renderForPromise('block_workplanner/return_notice', {url})
    .then(({html, js}) => {
        const main = document.getElementById('region-main') || document.querySelector('[role="main"]');
        if (!main) {
            return null;
        }
        const nodes = Templates.prependNodeContents(main, html, js);
        main.querySelector('[data-action="returntoplanner"]').addEventListener('click', forget);
        return nodes;
    });

/**
 * Offers the way back on a page of a course the user opened from the planner.
 *
 * @param {String} url Address of the planner.
 * @param {Number} courseid Course of the current page.
 * @returns {Promise|null}
 */
export const init = (url, courseid) => {
    if (!isMarked(courseid)) {
        return null;
    }
    if (isReturnFromActivity()) {
        forget();
        window.location.assign(url);
        return null;
    }
    return showNotice(url);
};
