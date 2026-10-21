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

namespace block_workplanner;

use core\hook\output\before_http_headers;

/**
 * Callbacks for core hooks.
 *
 * @package    block_workplanner
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Loads the way back to the planner on the pages of a course and its activities.
     *
     * The JavaScript module decides in the browser whether the user came from the planner
     * (a mark in the session storage of the tab); without that mark it does nothing. It is
     * loaded here because only this hook reaches every activity page before the page head
     * is written.
     *
     * @param before_http_headers $hook
     * @return void
     */
    public static function before_http_headers(before_http_headers $hook): void {
        global $PAGE;

        if (during_initial_install() || !isloggedin() || isguestuser() || !$PAGE->has_set_url()) {
            return;
        }
        if (empty($PAGE->course->id) || (int) $PAGE->course->id === SITEID) {
            return;
        }
        if (!in_array($PAGE->context->contextlevel, [CONTEXT_COURSE, CONTEXT_MODULE], true)) {
            return;
        }
        $PAGE->requires->js_call_amd('block_workplanner/return', 'init', [
            (new \moodle_url('/my/'))->out(false),
            (int) $PAGE->course->id,
        ]);
    }
}
