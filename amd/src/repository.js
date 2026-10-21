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
 * Calls to the external functions of the work planner.
 *
 * @module     block_workplanner/repository
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {call as fetchMany} from 'core/ajax';

/**
 * Loads the planner of the current user for a week.
 *
 * @param {Number} weekstart Any time within the week, 0 for the current week.
 * @param {Number} courseid Course to show, 0 for all courses.
 * @returns {Promise<Object>} Planner data, the context of the block templates.
 */
export const getPlanner = (weekstart, courseid) => fetchMany([{
    methodname: 'block_workplanner_get_planner',
    args: {weekstart, courseid},
}])[0];

/**
 * Plans an activity for a day, moves it to the later area or removes the plan.
 *
 * @param {Number} cmid Course module id of the activity.
 * @param {String} slot day, later or none.
 * @param {Number} day Any time on the planned day, 0 unless the slot is day.
 * @returns {Promise}
 */
export const setPlan = (cmid, slot, day) => fetchMany([{
    methodname: 'block_workplanner_set_plan',
    args: {cmid, slot, day},
}])[0];
