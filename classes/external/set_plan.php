<?php
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

namespace block_workplanner\external;

use block_workplanner\local\plan_store;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;

/**
 * Plans an activity of the current user for a day, moves it to the later area or removes the plan.
 *
 * @package    block_workplanner
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_plan extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id of the activity'),
            'slot' => new external_value(PARAM_ALPHA, 'day, later or none (removes the plan)'),
            'day' => new external_value(PARAM_INT, 'Any time on the planned day, only for slot day', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Changes the plan of the current user for an activity.
     *
     * @param int $cmid
     * @param string $slot
     * @param int $day
     * @return null
     * @throws \moodle_exception If the user cannot see the activity.
     */
    public static function execute(int $cmid, string $slot, int $day = 0) {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'slot' => $slot,
            'day' => $day,
        ]);
        $context = \context_user::instance($USER->id);
        self::validate_context($context);
        require_capability('block/workplanner:plan', $context);

        [$course, $cm] = get_course_and_cm_from_cmid($params['cmid'], '', 0, $USER->id);
        if (!$cm->is_visible_on_course_page() || !can_access_course($course, $USER->id, '', true)) {
            throw new \moodle_exception('activitynotavailable', 'block_workplanner');
        }

        plan_store::set((int) $USER->id, $cm, $params['slot'], $params['day']);
        return null;
    }

    /**
     * Describes the result: none.
     *
     * @return null
     */
    public static function execute_returns() {
        return null;
    }
}
