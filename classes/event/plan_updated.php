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

namespace block_workplanner\event;

/**
 * Raised when a user plans an activity, moves its plan or removes it.
 *
 * Expected data in "other":
 * - int cmid: the planned course module.
 * - int courseid: the course of the course module.
 * - string slot: day, later or none (none = plan removed).
 *
 * @package    block_workplanner
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plan_updated extends \core\event\base {
    /**
     * Sets the basic properties of the event.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'block_workplanner_plan';
    }

    /**
     * Returns the localised name of the event.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventplanupdated', 'block_workplanner');
    }

    /**
     * Returns a non-localised description for the logs.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' changed the work planner entry for the course module " .
            "with id '{$this->other['cmid']}' to the slot '{$this->other['slot']}'.";
    }

    /**
     * Returns the dashboard, where the planner is shown.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/my/');
    }

    /**
     * Checks that the event carries the data it needs.
     *
     * @return void
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();
        foreach (['cmid', 'courseid', 'slot'] as $key) {
            if (!isset($this->other[$key])) {
                throw new \coding_exception("The '{$key}' value must be set in other.");
            }
        }
    }

    /**
     * Maps the object id for backup and restore of logs.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'block_workplanner_plan', 'restore' => \core\event\base::NOT_MAPPED];
    }

    /**
     * Maps the ids in "other" for backup and restore of logs.
     *
     * @return array
     */
    public static function get_other_mapping() {
        return [
            'cmid' => ['db' => 'course_modules', 'restore' => 'course_module'],
            'courseid' => ['db' => 'course', 'restore' => 'course'],
        ];
    }
}
