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

use block_workplanner\event\plan_updated;

/**
 * Reads and changes the plans a user makes for their activities.
 *
 * A plan puts an activity on a day of the week plan or in the later area. Each user has
 * at most one plan per activity. Every change raises the event plan_updated.
 *
 * @package    block_workplanner
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class plan_store {
    /** @var string Planned for a day. */
    public const SLOT_DAY = 'day';

    /** @var string In the later area, without a day. */
    public const SLOT_LATER = 'later';

    /** @var string No plan; removes an existing one. */
    public const SLOT_NONE = 'none';

    /** @var string Database table of the plans. */
    private const TABLE = 'block_workplanner_plan';

    /**
     * Returns the plans of a user.
     *
     * @param int $userid
     * @return array Plans keyed by course module id.
     */
    public static function get_for_user(int $userid): array {
        global $DB;

        $plans = [];
        foreach ($DB->get_records(self::TABLE, ['userid' => $userid]) as $plan) {
            $plans[(int) $plan->cmid] = $plan;
        }
        return $plans;
    }

    /**
     * Plans an activity for a user, or removes the plan.
     *
     * The caller must have checked that the user may see the activity.
     *
     * @param int $userid
     * @param \cm_info $cm
     * @param string $slot One of the SLOT_* constants.
     * @param int $day Any time on the planned day; only used for SLOT_DAY.
     * @return void
     * @throws \invalid_parameter_exception If the slot is unknown or a day plan has no day.
     */
    public static function set(int $userid, \cm_info $cm, string $slot, int $day): void {
        global $DB;

        if (!in_array($slot, [self::SLOT_DAY, self::SLOT_LATER, self::SLOT_NONE], true)) {
            throw new \invalid_parameter_exception('Unknown slot: ' . $slot);
        }
        if ($slot === self::SLOT_DAY && $day <= 0) {
            throw new \invalid_parameter_exception('A day plan needs a day.');
        }

        $existing = $DB->get_record(self::TABLE, ['userid' => $userid, 'cmid' => $cm->id]);
        $now = time();

        if ($slot === self::SLOT_NONE) {
            if (!$existing) {
                return;
            }
            $DB->delete_records(self::TABLE, ['id' => $existing->id]);
            self::trigger_event($userid, (int) $existing->id, $cm, $slot);
            return;
        }

        $plandate = $slot === self::SLOT_DAY ? usergetmidnight($day) : 0;
        if ($existing) {
            $existing->slot = $slot;
            $existing->plandate = $plandate;
            $existing->timemodified = $now;
            $DB->update_record(self::TABLE, $existing);
            $id = (int) $existing->id;
        } else {
            $id = (int) $DB->insert_record(self::TABLE, (object) [
                'userid' => $userid,
                'courseid' => $cm->course,
                'cmid' => $cm->id,
                'slot' => $slot,
                'plandate' => $plandate,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }
        self::trigger_event($userid, $id, $cm, $slot);
    }

    /**
     * Raises the event for a changed plan.
     *
     * @param int $userid
     * @param int $planid
     * @param \cm_info $cm
     * @param string $slot
     * @return void
     */
    private static function trigger_event(int $userid, int $planid, \cm_info $cm, string $slot): void {
        plan_updated::create([
            'objectid' => $planid,
            'context' => \context_user::instance($userid),
            'relateduserid' => $userid,
            'other' => [
                'cmid' => (int) $cm->id,
                'courseid' => (int) $cm->course,
                'slot' => $slot,
            ],
        ])->trigger();
    }
}
