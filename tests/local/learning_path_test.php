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

namespace block_workplanner\local;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for finding learning paths from access restrictions.
 *
 * @package    block_workplanner
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(learning_path::class)]
final class learning_path_test extends \advanced_testcase {
    /**
     * Loads the completion constants used by the tests.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');
        parent::setUpBeforeClass();
    }

    /**
     * Restricts an activity to the completion of another one.
     *
     * @param int $cmid Restricted activity.
     * @param int $requiredcmid Activity that must be completed.
     * @param string $op Operator of the restriction set: & (all of) or !| (none of).
     * @return void
     */
    private function require_completion(int $cmid, int $requiredcmid, string $op = '&'): void {
        global $DB;

        $tree = [
            'op' => $op,
            'c' => [['type' => 'completion', 'cm' => $requiredcmid, 'e' => COMPLETION_COMPLETE]],
            'showc' => [true],
        ];
        $DB->set_field('course_modules', 'availability', json_encode($tree), ['id' => $cmid]);
    }

    /**
     * A chain of completion restrictions forms one path in dependency order.
     *
     * @return void
     */
    public function test_chain_of_completion_restrictions_is_one_path(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $first = $generator->create_module('page', ['course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $second = $generator->create_module('page', ['course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $third = $generator->create_module('page', ['course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $generator->create_module('page', ['course' => $course->id]);

        // The third step depends on the second, which depends on the first.
        $this->require_completion($third->cmid, $second->cmid);
        $this->require_completion($second->cmid, $first->cmid);
        rebuild_course_cache($course->id, true);

        $paths = learning_path::find(get_fast_modinfo($course));

        $this->assertSame([[(int) $first->cmid, (int) $second->cmid, (int) $third->cmid]], $paths);
    }

    /**
     * A restriction inside a negated set is no path step.
     *
     * @return void
     */
    public function test_negated_restriction_is_ignored(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $first = $generator->create_module('page', ['course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $second = $generator->create_module('page', ['course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL]);

        $this->require_completion($second->cmid, $first->cmid, '!|');
        rebuild_course_cache($course->id, true);

        $this->assertSame([], learning_path::find(get_fast_modinfo($course)));
    }

    /**
     * Activities without restrictions form no path.
     *
     * @return void
     */
    public function test_course_without_restrictions_has_no_path(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $generator->create_module('page', ['course' => $course->id]);
        $generator->create_module('page', ['course' => $course->id]);

        $this->assertSame([], learning_path::find(get_fast_modinfo($course)));
    }
}
