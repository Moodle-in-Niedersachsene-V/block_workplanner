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
use block_workplanner\local\planner;
use core_external\external_api;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the external function get_planner.
 *
 * @package    block_workplanner
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(get_planner::class)]
#[CoversClass(planner::class)]
final class get_planner_test extends \advanced_testcase {
    /**
     * Returns the course module ids of a list of cards.
     *
     * @param array $cards
     * @return int[]
     */
    private function cmids(array $cards): array {
        return array_map(fn(array $card): int => $card['cmid'], $cards);
    }

    /**
     * An assignment with a due date is a dated task, a page without one is free material.
     *
     * @return void
     */
    public function test_tasks_are_sorted_into_dated_and_free(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $assign = $generator->create_module('assign', ['course' => $course->id, 'duedate' => time() + DAYSECS]);
        $page = $generator->create_module('page', ['course' => $course->id]);
        $student = $generator->create_and_enrol($course, 'student');
        $this->setUser($student);

        $result = external_api::clean_returnvalue(get_planner::execute_returns(), get_planner::execute(0));

        $dated = array_merge(...array_map(fn(array $group): array => $this->cmids($group['cards']), $result['dated']));
        $this->assertSame([(int) $assign->cmid], $dated);
        $this->assertSame([(int) $page->cmid], $this->cmids($result['free']));
        $this->assertCount(7, $result['days']);
    }

    /**
     * A planned activity appears on its day and in the later area when moved there.
     *
     * @return void
     */
    public function test_plans_appear_on_day_and_in_later_area(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $page = $generator->create_module('page', ['course' => $course->id]);
        $student = $generator->create_and_enrol($course, 'student');
        $this->setUser($student);
        $now = time();

        set_plan::execute($page->cmid, plan_store::SLOT_DAY, $now);
        $result = external_api::clean_returnvalue(get_planner::execute_returns(), get_planner::execute($now));
        $planned = [];
        foreach ($result['days'] as $day) {
            foreach ($day['entries'] as $entry) {
                $planned[$day['day']] = $entry['cmid'];
            }
        }
        $this->assertSame([usergetmidnight($now) => (int) $page->cmid], $planned);

        set_plan::execute($page->cmid, plan_store::SLOT_LATER, 0);
        $result = external_api::clean_returnvalue(get_planner::execute_returns(), get_planner::execute($now));
        $this->assertSame([(int) $page->cmid], $this->cmids($result['later']));
    }

    /**
     * Choosing a course shows only its activities but still lists all courses for the selection.
     *
     * @return void
     */
    public function test_course_selection_limits_tasks(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $maths = $generator->create_course();
        $german = $generator->create_course();
        $mathspage = $generator->create_module('page', ['course' => $maths->id]);
        $generator->create_module('page', ['course' => $german->id]);
        $student = $generator->create_and_enrol($maths, 'student');
        $generator->enrol_user($student->id, $german->id, 'student');
        $this->setUser($student);

        $result = external_api::clean_returnvalue(get_planner::execute_returns(), get_planner::execute(0, $maths->id));

        $this->assertSame((int) $maths->id, $result['courseid']);
        $this->assertSame([(int) $mathspage->cmid], $this->cmids($result['free']));
        $this->assertCount(2, $result['courses']);
    }

    /**
     * A course the user is not enrolled in shows all of the user's courses.
     *
     * @return void
     */
    public function test_foreign_course_shows_all_courses(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $page = $generator->create_module('page', ['course' => $course->id]);
        $foreign = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $this->setUser($student);

        $result = external_api::clean_returnvalue(get_planner::execute_returns(), get_planner::execute(0, $foreign->id));

        $this->assertSame(0, $result['courseid']);
        $this->assertSame([(int) $page->cmid], $this->cmids($result['free']));
    }

    /**
     * Courses the user teaches are not part of their planner.
     *
     * @return void
     */
    public function test_teacher_courses_are_left_out(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $generator->create_module('page', ['course' => $course->id]);
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        $result = external_api::clean_returnvalue(get_planner::execute_returns(), get_planner::execute(0));

        $this->assertSame([], $result['free']);
        $this->assertSame([], $result['dated']);
    }

    /**
     * A user without the capability cannot see a planner.
     *
     * @return void
     */
    public function test_missing_capability_is_refused(): void {
        global $DB;
        $this->resetAfterTest();
        $student = $this->getDataGenerator()->create_user();
        $this->setUser($student);
        $userrole = $DB->get_field('role', 'id', ['shortname' => 'user']);
        assign_capability('block/workplanner:view', CAP_PROHIBIT, $userrole, \context_system::instance()->id, true);

        $this->expectException(\required_capability_exception::class);
        get_planner::execute(0);
    }
}
