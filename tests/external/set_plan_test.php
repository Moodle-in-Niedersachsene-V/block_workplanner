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
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the external function set_plan.
 *
 * @package    block_workplanner
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(set_plan::class)]
#[CoversClass(plan_store::class)]
final class set_plan_test extends \advanced_testcase {
    /**
     * Creates a course with one page and a student, and logs the student in.
     *
     * @return array{0: \stdClass, 1: \stdClass, 2: \stdClass} Course, page and student.
     */
    private function create_student_with_page(): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $page = $generator->create_module('page', ['course' => $course->id]);
        $student = $generator->create_and_enrol($course, 'student');
        $this->setUser($student);
        return [$course, $page, $student];
    }

    /**
     * Planning for a day stores the midnight of that day.
     *
     * @return void
     */
    public function test_plan_for_day_stores_midnight(): void {
        global $DB;
        $this->resetAfterTest();
        [, $page, $student] = $this->create_student_with_page();
        $day = make_timestamp(2026, 10, 21, 15, 30);

        $result = set_plan::execute($page->cmid, plan_store::SLOT_DAY, $day);
        external_api::clean_returnvalue(set_plan::execute_returns(), $result);

        $plan = $DB->get_record('block_workplanner_plan', ['userid' => $student->id, 'cmid' => $page->cmid], '*', MUST_EXIST);
        $this->assertSame(plan_store::SLOT_DAY, $plan->slot);
        $this->assertEquals(usergetmidnight($day), $plan->plandate);
    }

    /**
     * Moving a plan to the later area keeps one record without a day.
     *
     * @return void
     */
    public function test_move_to_later_replaces_day(): void {
        global $DB;
        $this->resetAfterTest();
        [, $page, $student] = $this->create_student_with_page();

        set_plan::execute($page->cmid, plan_store::SLOT_DAY, time());
        set_plan::execute($page->cmid, plan_store::SLOT_LATER, 0);

        $plans = $DB->get_records('block_workplanner_plan', ['userid' => $student->id]);
        $this->assertCount(1, $plans);
        $plan = reset($plans);
        $this->assertSame(plan_store::SLOT_LATER, $plan->slot);
        $this->assertEquals(0, $plan->plandate);
    }

    /**
     * Removing a plan deletes the record and raises the event.
     *
     * @return void
     */
    public function test_remove_plan_deletes_record_and_raises_event(): void {
        global $DB;
        $this->resetAfterTest();
        [, $page, $student] = $this->create_student_with_page();
        set_plan::execute($page->cmid, plan_store::SLOT_LATER, 0);

        $sink = $this->redirectEvents();
        set_plan::execute($page->cmid, plan_store::SLOT_NONE, 0);
        $events = $sink->get_events();
        $sink->close();

        $this->assertFalse($DB->record_exists('block_workplanner_plan', ['userid' => $student->id]));
        $this->assertCount(1, $events);
        $this->assertInstanceOf(\block_workplanner\event\plan_updated::class, $events[0]);
        $this->assertSame(plan_store::SLOT_NONE, $events[0]->other['slot']);
    }

    /**
     * A user without the capability cannot plan.
     *
     * @return void
     */
    public function test_missing_capability_is_refused(): void {
        global $DB;
        $this->resetAfterTest();
        [, $page, $student] = $this->create_student_with_page();
        $userrole = $DB->get_field('role', 'id', ['shortname' => 'user']);
        assign_capability('block/workplanner:plan', CAP_PROHIBIT, $userrole, \context_system::instance()->id, true);

        $this->expectException(\required_capability_exception::class);
        try {
            set_plan::execute($page->cmid, plan_store::SLOT_LATER, 0);
        } finally {
            $this->assertFalse($DB->record_exists('block_workplanner_plan', ['userid' => $student->id]));
        }
    }

    /**
     * An activity in a course the user is not enrolled in cannot be planned.
     *
     * @return void
     */
    public function test_activity_of_other_course_is_refused(): void {
        global $DB;
        $this->resetAfterTest();
        [, , $student] = $this->create_student_with_page();
        $othercourse = $this->getDataGenerator()->create_course();
        $otherpage = $this->getDataGenerator()->create_module('page', ['course' => $othercourse->id]);

        $this->expectException(\moodle_exception::class);
        try {
            set_plan::execute($otherpage->cmid, plan_store::SLOT_LATER, 0);
        } finally {
            $this->assertFalse($DB->record_exists('block_workplanner_plan', ['userid' => $student->id]));
        }
    }
}
