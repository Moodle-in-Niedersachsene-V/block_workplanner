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

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\plugin\provider as plugin_provider;
use core_privacy\local\request\transform;
use core_privacy\local\request\user_preference_provider;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider of block_workplanner.
 *
 * The plugin stores the plans a user makes for their activities. Plans belong to the
 * user context of their owner, since the planner spans all of the user's courses.
 *
 * @package    block_workplanner
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements core_userlist_provider, metadata_provider, plugin_provider, user_preference_provider {
    /**
     * Describes the stored personal data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('block_workplanner_plan', [
            'userid' => 'privacy:metadata:plan:userid',
            'courseid' => 'privacy:metadata:plan:courseid',
            'cmid' => 'privacy:metadata:plan:cmid',
            'slot' => 'privacy:metadata:plan:slot',
            'plandate' => 'privacy:metadata:plan:plandate',
            'timecreated' => 'privacy:metadata:plan:timecreated',
            'timemodified' => 'privacy:metadata:plan:timemodified',
        ], 'privacy:metadata:plan');
        $collection->add_user_preference('block_workplanner_course', 'privacy:metadata:preference:course');
        return $collection;
    }

    /**
     * Exports the course the user chose in the course selection.
     *
     * @param int $userid
     * @return void
     */
    public static function export_user_preferences(int $userid): void {
        $courseid = get_user_preferences('block_workplanner_course', null, $userid);
        if ($courseid !== null) {
            writer::export_user_preference(
                'block_workplanner',
                'block_workplanner_course',
                $courseid,
                get_string('privacy:metadata:preference:course', 'block_workplanner')
            );
        }
    }

    /**
     * Returns the user context of a user who has plans.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                 WHERE ctx.contextlevel = :contextlevel
                   AND ctx.instanceid = :userid
                   AND EXISTS (SELECT 1 FROM {block_workplanner_plan} p WHERE p.userid = ctx.instanceid)";
        $contextlist->add_from_sql($sql, ['contextlevel' => CONTEXT_USER, 'userid' => $userid]);
        return $contextlist;
    }

    /**
     * Adds the owner of a user context to the list when they have plans.
     *
     * @param userlist $userlist
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_user) {
            return;
        }
        if ($DB->record_exists('block_workplanner_plan', ['userid' => $context->instanceid])) {
            $userlist->add_user($context->instanceid);
        }
    }

    /**
     * Exports the plans of the user.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_user || (int) $context->instanceid !== $userid) {
                continue;
            }
            $plans = [];
            foreach ($DB->get_records('block_workplanner_plan', ['userid' => $userid], 'plandate, id') as $plan) {
                $plans[] = (object) [
                    'courseid' => $plan->courseid,
                    'cmid' => $plan->cmid,
                    'slot' => $plan->slot,
                    'plandate' => $plan->plandate ? transform::date($plan->plandate) : '',
                    'timecreated' => transform::datetime($plan->timecreated),
                    'timemodified' => transform::datetime($plan->timemodified),
                ];
            }
            if ($plans) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'block_workplanner')],
                    (object) ['plans' => $plans]
                );
            }
        }
    }

    /**
     * Deletes all plans in a context.
     *
     * @param \context $context
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context instanceof \context_user) {
            $DB->delete_records('block_workplanner_plan', ['userid' => $context->instanceid]);
        }
    }

    /**
     * Deletes the plans of a user.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_user && (int) $context->instanceid === $userid) {
                $DB->delete_records('block_workplanner_plan', ['userid' => $userid]);
            }
        }
    }

    /**
     * Deletes the plans of the listed users in a context.
     *
     * @param approved_userlist $userlist
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_user) {
            return;
        }
        if (in_array((int) $context->instanceid, array_map('intval', $userlist->get_userids()), true)) {
            $DB->delete_records('block_workplanner_plan', ['userid' => $context->instanceid]);
        }
    }
}
