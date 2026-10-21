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

namespace block_workplanner\privacy;

use block_workplanner\local\plan_store;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the privacy provider.
 *
 * @package    block_workplanner
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(provider::class)]
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * Creates a user with one plan and a second user with one plan.
     *
     * @return array{0: \stdClass, 1: \stdClass} The two users.
     */
    private function create_two_planners(): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $page = $generator->create_module('page', ['course' => $course->id]);
        $cm = get_fast_modinfo($course)->get_cm($page->cmid);
        $first = $generator->create_and_enrol($course, 'student');
        $second = $generator->create_and_enrol($course, 'student');
        plan_store::set((int) $first->id, $cm, plan_store::SLOT_LATER, 0);
        plan_store::set((int) $second->id, $cm, plan_store::SLOT_DAY, time());
        return [$first, $second];
    }

    /**
     * The user context of a user with plans is reported, and only that user.
     *
     * @return void
     */
    public function test_contexts_and_users(): void {
        $this->resetAfterTest();
        [$first] = $this->create_two_planners();
        $context = \context_user::instance($first->id);

        $contextlist = provider::get_contexts_for_userid($first->id);
        $userlist = new userlist($context, 'block_workplanner');
        provider::get_users_in_context($userlist);

        $this->assertSame([(int) $context->id], array_map('intval', $contextlist->get_contextids()));
        $this->assertSame([(int) $first->id], array_map('intval', $userlist->get_userids()));
    }

    /**
     * The export holds the user's plans.
     *
     * @return void
     */
    public function test_export_user_data(): void {
        $this->resetAfterTest();
        [$first] = $this->create_two_planners();
        $context = \context_user::instance($first->id);

        provider::export_user_data(new approved_contextlist($first, 'block_workplanner', [$context->id]));

        $data = writer::with_context($context)->get_data([get_string('pluginname', 'block_workplanner')]);
        $this->assertCount(1, $data->plans);
        $this->assertSame(plan_store::SLOT_LATER, $data->plans[0]->slot);
    }

    /**
     * Deleting a user's data leaves the other user's plans.
     *
     * @return void
     */
    public function test_delete_data_for_user(): void {
        global $DB;
        $this->resetAfterTest();
        [$first, $second] = $this->create_two_planners();
        $context = \context_user::instance($first->id);

        provider::delete_data_for_user(new approved_contextlist($first, 'block_workplanner', [$context->id]));

        $this->assertFalse($DB->record_exists('block_workplanner_plan', ['userid' => $first->id]));
        $this->assertTrue($DB->record_exists('block_workplanner_plan', ['userid' => $second->id]));
    }

    /**
     * Deleting by user list and by context removes the plans of the context owner.
     *
     * @return void
     */
    public function test_delete_by_userlist_and_context(): void {
        global $DB;
        $this->resetAfterTest();
        [$first, $second] = $this->create_two_planners();

        $firstcontext = \context_user::instance($first->id);
        provider::delete_data_for_users(new approved_userlist($firstcontext, 'block_workplanner', [$first->id]));
        provider::delete_data_for_all_users_in_context(\context_user::instance($second->id));

        $this->assertSame(0, $DB->count_records('block_workplanner_plan'));
    }
}
