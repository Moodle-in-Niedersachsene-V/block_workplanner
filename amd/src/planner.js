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
 * Work planner on the dashboard: drag and drop, the plan menu and the week navigation.
 *
 * After every change the planner data is loaded again and the regions marked with
 * data-template are rendered from the same templates the server uses.
 *
 * @module     block_workplanner/planner
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getPlanner, setPlan} from 'block_workplanner/repository';
import Templates from 'core/templates';
import Notification from 'core/notification';
import Pending from 'core/pending';
import {getString} from 'core/str';
import {setUserPreference} from 'core_user/repository';
import {remember, forget} from 'block_workplanner/return';

const SELECTORS = {
    REGIONS: '[data-template]',
    STATUS: '[data-region="status"]',
    INFOS: '[data-region="infos-section"]',
    COURSE: '[data-action="course"]',
    DRAGGABLE: '[data-draggable="true"]',
    DROPZONE: '[data-dropzone="true"]',
    PLAN: '[data-action="plan"]',
    ACTIVITY: 'a[data-courseid]',
    WEEK: '[data-action="week"]',
};

const CLASSES = {
    DRAGGING: 'block_workplanner-dragging',
    OVER: 'block_workplanner-over',
};

/**
 * Renders the changing regions of the planner again.
 *
 * @param {HTMLElement} root Root element of the planner.
 * @param {Object} data Result of block_workplanner_get_planner.
 * @param {Boolean} canPlan Whether the user may plan activities.
 * @returns {Promise}
 */
const render = (root, data, canPlan) => {
    const context = {...data, canplan: canPlan};
    root.dataset.weekstart = data.weekstart;
    root.dataset.courseid = data.courseid;
    root.querySelector(SELECTORS.INFOS).hidden = !data.hasinfos;
    const regions = Array.from(root.querySelectorAll(SELECTORS.REGIONS));
    return Promise.all(regions.map((region) => Templates.renderForPromise(region.dataset.template, context)
        .then(({html, js}) => {
            Templates.replaceNodeContents(region, html, js);
            return region;
        })
    ));
};

/**
 * Waits for a request, loads the planner for a week, renders it and announces the result.
 *
 * @param {HTMLElement} root Root element of the planner.
 * @param {Boolean} canPlan Whether the user may plan activities.
 * @param {Promise} request Request to wait for first.
 * @param {Number} weekstart Any time within the week to show.
 * @param {String} message Key of the string announced to screen readers.
 * @returns {Promise}
 */
const refresh = (root, canPlan, request, weekstart, message) => {
    const pending = new Pending('block_workplanner/refresh');
    return request
        .then(() => getPlanner(weekstart, Number(root.dataset.courseid)))
        .then((data) => render(root, data, canPlan))
        .then(() => getString(message, 'block_workplanner'))
        .then((text) => {
            root.querySelector(SELECTORS.STATUS).textContent = text;
            return text;
        })
        .catch(Notification.exception)
        .finally(() => pending.resolve());
};

/**
 * Plans an activity and shows the changed planner.
 *
 * @param {HTMLElement} root Root element of the planner.
 * @param {Boolean} canPlan Whether the user may plan activities.
 * @param {Number} cmid Course module id of the activity.
 * @param {String} slot day, later or none.
 * @param {Number} day Any time on the planned day, 0 unless the slot is day.
 * @returns {Promise}
 */
const plan = (root, canPlan, cmid, slot, day) => refresh(
    root,
    canPlan,
    setPlan(cmid, slot, day),
    Number(root.dataset.weekstart),
    'planupdated'
);

/**
 * Shows only the chosen course, or all courses, and remembers the choice for the next visit.
 *
 * @param {HTMLElement} root Root element of the planner.
 * @param {Boolean} canPlan Whether the user may plan activities.
 * @param {Number} courseid Course to show, 0 for all courses.
 * @returns {Promise}
 */
const selectCourse = (root, canPlan, courseid) => {
    root.dataset.courseid = courseid;
    return refresh(
        root,
        canPlan,
        setUserPreference('block_workplanner_course', courseid),
        Number(root.dataset.weekstart),
        'courseloaded'
    );
};

/**
 * Removes the highlight from all drop targets.
 *
 * @param {HTMLElement} root Root element of the planner.
 */
const clearHighlight = (root) => {
    root.querySelectorAll('.' + CLASSES.OVER).forEach((zone) => zone.classList.remove(CLASSES.OVER));
};

/**
 * Handles clicks on activities, the plan menu, the remove buttons and the week navigation.
 *
 * A click on an activity marks the way back to the planner.
 *
 * @param {HTMLElement} root Root element of the planner.
 * @param {Boolean} canPlan Whether the user may plan activities.
 */
const registerClicks = (root, canPlan) => {
    root.addEventListener('click', (e) => {
        const activity = e.target.closest(SELECTORS.ACTIVITY);
        if (activity) {
            remember(Number(activity.dataset.courseid));
            return;
        }
        const planButton = e.target.closest(SELECTORS.PLAN);
        if (planButton && canPlan) {
            e.preventDefault();
            plan(root, canPlan, Number(planButton.dataset.cmid), planButton.dataset.slot, Number(planButton.dataset.day));
            return;
        }
        const weekButton = e.target.closest(SELECTORS.WEEK);
        if (weekButton) {
            e.preventDefault();
            refresh(root, canPlan, Promise.resolve(), Number(weekButton.dataset.weekstart), 'weekloaded');
        }
    });
};

/**
 * Handles the course selection.
 *
 * @param {HTMLElement} root Root element of the planner.
 * @param {Boolean} canPlan Whether the user may plan activities.
 */
const registerCourseSelection = (root, canPlan) => {
    const select = root.querySelector(SELECTORS.COURSE);
    if (!select) {
        return;
    }
    select.addEventListener('change', () => selectCourse(root, canPlan, Number(select.value)));
};

/**
 * Lets the user drag activities onto a day, into the later area or back into the task list.
 *
 * @param {HTMLElement} root Root element of the planner.
 * @param {Boolean} canPlan Whether the user may plan activities.
 */
const registerDragAndDrop = (root, canPlan) => {
    root.addEventListener('dragstart', (e) => {
        const item = e.target.closest(SELECTORS.DRAGGABLE);
        if (!item) {
            return;
        }
        e.dataTransfer.setData('text/plain', item.dataset.cmid);
        e.dataTransfer.effectAllowed = 'move';
        item.classList.add(CLASSES.DRAGGING);
    });

    root.addEventListener('dragend', (e) => {
        const item = e.target.closest(SELECTORS.DRAGGABLE);
        if (item) {
            item.classList.remove(CLASSES.DRAGGING);
        }
        clearHighlight(root);
    });

    root.addEventListener('dragover', (e) => {
        const zone = e.target.closest(SELECTORS.DROPZONE);
        if (!zone) {
            return;
        }
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        zone.classList.add(CLASSES.OVER);
    });

    root.addEventListener('dragleave', (e) => {
        const zone = e.target.closest(SELECTORS.DROPZONE);
        if (zone && !zone.contains(e.relatedTarget)) {
            zone.classList.remove(CLASSES.OVER);
        }
    });

    root.addEventListener('drop', (e) => {
        const zone = e.target.closest(SELECTORS.DROPZONE);
        if (!zone) {
            return;
        }
        e.preventDefault();
        clearHighlight(root);
        const cmid = parseInt(e.dataTransfer.getData('text/plain'), 10);
        if (!cmid) {
            return;
        }
        plan(root, canPlan, cmid, zone.dataset.slot, Number(zone.dataset.day || 0));
    });
};

/**
 * Starts the planner.
 *
 * @param {String} rootId Id of the root element.
 * @param {Boolean} canPlan Whether the user may plan activities.
 */
export const init = (rootId, canPlan) => {
    const root = document.getElementById(rootId);
    if (!root) {
        return;
    }
    forget();
    registerClicks(root, canPlan);
    registerCourseSelection(root, canPlan);
    if (canPlan) {
        registerDragAndDrop(root, canPlan);
    }
};
