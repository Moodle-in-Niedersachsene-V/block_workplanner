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

use block_workplanner\local\planner;
use block_workplanner\local\week;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Returns the work planner of the current user for one week.
 *
 * The result is the template context of the block, so the page can render the same
 * templates after a change without loading the whole dashboard again.
 *
 * @package    block_workplanner
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_planner extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'weekstart' => new external_value(
                PARAM_INT,
                'Any time within the week to show, 0 for the current week',
                VALUE_DEFAULT,
                0
            ),
            'courseid' => new external_value(PARAM_INT, 'Course to show, 0 for all courses', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Returns the planner of the current user.
     *
     * @param int $weekstart Any time within the week to show, 0 for the current week.
     * @param int $courseid Course to show, 0 for all courses.
     * @return array
     */
    public static function execute(int $weekstart = 0, int $courseid = 0): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'weekstart' => $weekstart,
            'courseid' => $courseid,
        ]);
        $context = \context_user::instance($USER->id);
        self::validate_context($context);
        require_capability('block/workplanner:view', $context);

        $now = time();
        $week = week::containing($params['weekstart'] > 0 ? $params['weekstart'] : $now);
        return (new planner((int) $USER->id, $now))->export($week, $params['courseid']);
    }

    /**
     * Describes the result.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'courseid' => new external_value(PARAM_INT, 'Shown course, 0 for all courses'),
            'courses' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Course id'),
                'name' => new external_value(PARAM_TEXT, 'Course name'),
                'selected' => new external_value(PARAM_BOOL, 'Whether the course is shown'),
            ])),
            'weekstart' => new external_value(PARAM_INT, 'Start of the shown week'),
            'weeklabel' => new external_value(PARAM_TEXT, 'Label of the shown week'),
            'prevweek' => new external_value(PARAM_INT, 'Start of the previous week'),
            'nextweek' => new external_value(PARAM_INT, 'Start of the next week'),
            'thisweek' => new external_value(PARAM_INT, 'Start of the current week'),
            'isthisweek' => new external_value(PARAM_BOOL, 'Whether the shown week is the current week'),
            'days' => new external_multiple_structure(new external_single_structure([
                'day' => new external_value(PARAM_INT, 'Midnight of the day'),
                'weekday' => new external_value(PARAM_TEXT, 'Short weekday name'),
                'datetext' => new external_value(PARAM_TEXT, 'Date of the day'),
                'istoday' => new external_value(PARAM_BOOL, 'Whether the day is today'),
                'isweekend' => new external_value(PARAM_BOOL, 'Whether the day is a Saturday or Sunday'),
                'hasentries' => new external_value(PARAM_BOOL, 'Whether the day has entries'),
                'entries' => new external_multiple_structure(self::entry_structure()),
            ])),
            'hasinfos' => new external_value(PARAM_BOOL, 'Whether there is teacher information'),
            'infos' => new external_multiple_structure(new external_single_structure([
                'coursename' => new external_value(PARAM_TEXT, 'Course name'),
                'colorindex' => new external_value(PARAM_INT, 'Colour index of the course'),
                'authorname' => new external_value(PARAM_TEXT, 'Name of the author'),
                'text' => new external_value(PARAM_TEXT, 'Plain text of the information'),
                'timetext' => new external_value(PARAM_TEXT, 'Time the information was written'),
            ])),
            'hasdated' => new external_value(PARAM_BOOL, 'Whether there are open tasks with a deadline'),
            'dated' => new external_multiple_structure(new external_single_structure([
                'label' => new external_value(PARAM_TEXT, 'Label of the group'),
                'isoverdue' => new external_value(PARAM_BOOL, 'Whether the group holds overdue tasks'),
                'cards' => new external_multiple_structure(self::card_structure()),
            ])),
            'haspaths' => new external_value(PARAM_BOOL, 'Whether there are learning paths'),
            'paths' => new external_multiple_structure(new external_single_structure([
                'name' => new external_value(PARAM_TEXT, 'Name of the learning path'),
                'colorindex' => new external_value(PARAM_INT, 'Colour index of the course'),
                'counttext' => new external_value(PARAM_TEXT, 'Finished steps as text'),
                'steps' => new external_multiple_structure(new external_single_structure([
                    'number' => new external_value(PARAM_INT, 'Position in the path, starting at 1'),
                    'iscurrent' => new external_value(PARAM_BOOL, 'Whether this is the next step to work on'),
                    'card' => self::card_structure(),
                ])),
            ])),
            'hasfree' => new external_value(PARAM_BOOL, 'Whether there are free materials'),
            'free' => new external_multiple_structure(self::card_structure()),
            'haslater' => new external_value(PARAM_BOOL, 'Whether the later area holds entries'),
            'later' => new external_multiple_structure(self::entry_structure()),
            'hasprogress' => new external_value(PARAM_BOOL, 'Whether there is learning progress to show'),
            'progress' => new external_multiple_structure(new external_single_structure([
                'hascompletion' => new external_value(PARAM_BOOL, 'Whether activities have completion tracking'),
                'coursename' => new external_value(PARAM_TEXT, 'Course name'),
                'colorindex' => new external_value(PARAM_INT, 'Colour index of the course'),
                'completed' => new external_value(PARAM_INT, 'Finished activities'),
                'total' => new external_value(PARAM_INT, 'Activities with completion tracking'),
                'percent' => new external_value(PARAM_INT, 'Finished activities in percent'),
                'counttext' => new external_value(PARAM_TEXT, 'Finished activities as text'),
                'hasoverdue' => new external_value(PARAM_BOOL, 'Whether tasks are overdue'),
                'overduetext' => new external_value(PARAM_TEXT, 'Overdue tasks as text'),
                'hasgrades' => new external_value(PARAM_BOOL, 'Whether the user has visible grades in the course'),
                'grades' => new external_multiple_structure(new external_single_structure([
                    'name' => new external_value(PARAM_TEXT, 'Name of the activity'),
                    'grade' => new external_value(PARAM_TEXT, 'Grade as text'),
                ])),
            ])),
            'hascompetencies' => new external_value(PARAM_BOOL, 'Whether there are competencies'),
            'competencysummary' => new external_value(PARAM_TEXT, 'Number of competencies per state as text'),
            'competencies' => new external_multiple_structure(new external_single_structure([
                'title' => new external_value(PARAM_TEXT, 'Title of the competency'),
                'coursenames' => new external_value(PARAM_TEXT, 'Courses the competency appears in'),
                'isachieved' => new external_value(PARAM_BOOL, 'Whether all activities are finished'),
                'isinprogress' => new external_value(PARAM_BOOL, 'Whether some activities are finished or started'),
                'isopen' => new external_value(PARAM_BOOL, 'Whether no activity is started'),
                'statetext' => new external_value(PARAM_TEXT, 'State as text'),
            ])),
        ]);
    }

    /**
     * Describes an activity shown as a task card.
     *
     * @return external_single_structure
     */
    private static function card_structure(): external_single_structure {
        return new external_single_structure([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'name' => new external_value(PARAM_TEXT, 'Name of the activity'),
            'url' => new external_value(PARAM_URL, 'Link to the activity, empty when locked'),
            'hasurl' => new external_value(PARAM_BOOL, 'Whether the activity can be opened'),
            'iconurl' => new external_value(PARAM_URL, 'Icon of the activity type'),
            'modtype' => new external_value(PARAM_TEXT, 'Name of the activity type'),
            'coursename' => new external_value(PARAM_TEXT, 'Course name'),
            'colorindex' => new external_value(PARAM_INT, 'Colour index of the course'),
            'hasdue' => new external_value(PARAM_BOOL, 'Whether the activity has a deadline'),
            'duetext' => new external_value(PARAM_TEXT, 'Deadline as text'),
            'isoverdue' => new external_value(PARAM_BOOL, 'Whether the deadline has passed'),
            'isdone' => new external_value(PARAM_BOOL, 'Whether the activity is finished'),
            'isinprogress' => new external_value(PARAM_BOOL, 'Whether the activity is started'),
            'islocked' => new external_value(PARAM_BOOL, 'Whether access restrictions still lock the activity'),
            'isplanned' => new external_value(PARAM_BOOL, 'Whether the user planned the activity'),
            'plantext' => new external_value(PARAM_TEXT, 'Plan as text'),
            'candrag' => new external_value(PARAM_BOOL, 'Whether the user can plan the activity'),
            'hasnote' => new external_value(PARAM_BOOL, 'Whether there is a teacher note'),
            'note' => new external_value(PARAM_TEXT, 'Plain text of the teacher note'),
            'hascompetencies' => new external_value(PARAM_BOOL, 'Whether competencies are assigned'),
            'competencytext' => new external_value(PARAM_TEXT, 'Number of assigned competencies as text'),
            'competencies' => new external_multiple_structure(new external_single_structure([
                'title' => new external_value(PARAM_TEXT, 'Title of the competency'),
            ])),
        ]);
    }

    /**
     * Describes an entry of the week plan or the later area.
     *
     * @return external_single_structure
     */
    private static function entry_structure(): external_single_structure {
        return new external_single_structure([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'name' => new external_value(PARAM_TEXT, 'Name of the activity'),
            'url' => new external_value(PARAM_URL, 'Link to the activity, empty when locked'),
            'hasurl' => new external_value(PARAM_BOOL, 'Whether the activity can be opened'),
            'coursename' => new external_value(PARAM_TEXT, 'Course name'),
            'colorindex' => new external_value(PARAM_INT, 'Colour index of the course'),
            'isdeadline' => new external_value(PARAM_BOOL, 'True for a teacher deadline, false for a plan of the user'),
            'deadlinetext' => new external_value(PARAM_TEXT, 'Time of the deadline as text'),
            'isdone' => new external_value(PARAM_BOOL, 'Whether the activity is finished'),
            'candrag' => new external_value(PARAM_BOOL, 'Whether the user can move the entry'),
        ]);
    }
}
