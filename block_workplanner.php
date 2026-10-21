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

use block_workplanner\local\planner;
use block_workplanner\local\week;
use block_workplanner\output\main;

/**
 * Work planner block for the dashboard.
 *
 * Shows a learner's activities by deadline, learning path or as free material, lets them
 * plan activities on a week plan or in a later area, and shows learning progress,
 * competencies and teacher information.
 *
 * @package    block_workplanner
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_workplanner extends block_base {
    /**
     * Sets the block title.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_workplanner');
    }

    /**
     * Allows the block only on the dashboard.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['all' => false, 'my' => true];
    }

    /**
     * Allows only one planner per dashboard.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * The block has no settings.
     *
     * @return bool
     */
    public function has_config() {
        return false;
    }

    /**
     * Builds the content of the block for the current user.
     *
     * @return stdClass
     */
    public function get_content() {
        global $USER;

        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = (object) ['text' => '', 'footer' => ''];

        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }
        $context = context_user::instance($USER->id);
        if (!has_capability('block/workplanner:view', $context)) {
            return $this->content;
        }

        $now = time();
        $courseid = (int) get_user_preferences('block_workplanner_course', 0);
        $data = (new planner((int) $USER->id, $now))->export(week::containing($now), $courseid);
        $canplan = has_capability('block/workplanner:plan', $context);
        $rootid = html_writer::random_id('block_workplanner');
        $renderer = $this->page->get_renderer('block_workplanner');
        $this->content->text = $renderer->render(new main($rootid, $data, $canplan));
        $this->page->requires->js_call_amd('block_workplanner/planner', 'init', [$rootid, $canplan]);
        return $this->content;
    }
}
