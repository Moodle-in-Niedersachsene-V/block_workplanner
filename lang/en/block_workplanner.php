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

/**
 * English language strings for block_workplanner.
 *
 * @package    block_workplanner
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activitynotavailable'] = 'This activity is not available to you.';
$string['allcourses'] = 'All courses';
$string['competencies'] = 'My competencies';
$string['competencies_help'] = 'Your teachers have assigned competencies to activities. A competency is achieved when you have finished all of its activities, and in progress when you have finished or started at least one of them.';
$string['competencyachieved'] = 'Achieved: {$a->done} of {$a->total} activities';
$string['competencycount'] = '{$a} competencies';
$string['competencycountone'] = '1 competency';
$string['competencyinprogress'] = 'In progress: {$a->done} of {$a->total} activities';
$string['competencyopen'] = 'Open: {$a->done} of {$a->total} activities';
$string['competencysummary'] = '{$a->achieved} achieved · {$a->inprogress} in progress · {$a->open} open';
$string['coursefilter'] = 'Course';
$string['courseloaded'] = 'The planner now shows the chosen course.';
$string['deadlineat'] = 'Due {$a}';
$string['dueon'] = 'Due: {$a}';
$string['eventplanupdated'] = 'Work planner entry changed';
$string['freeintro'] = 'Materials without a deadline and outside a learning path. Use them when you need them or plan them.';
$string['grouplater'] = 'Later';
$string['groupnextweek'] = 'Next week';
$string['groupoverdue'] = 'Overdue';
$string['groupthisweek'] = 'This week';
$string['infos'] = 'Information from your teachers';
$string['infos_help'] = 'Your teachers write this information in their courses. You only see information from your own courses.';
$string['later'] = 'Later';
$string['later_help'] = 'Put activities here that you want to do without fixing a day yet. Deadlines set by your teachers still apply.';
$string['legenddeadline'] = 'Deadline set by your teacher';
$string['legendplan'] = 'Planned by you, colour = course';
$string['nextstep'] = 'Your next step';
$string['nextweek'] = 'Next week';
$string['nocompetencies'] = 'No competencies are assigned to your activities.';
$string['nodated'] = 'No open tasks with a deadline.';
$string['nofree'] = 'No free materials.';
$string['nolater'] = 'Drag activities here or choose "Later" in their plan menu.';
$string['nopaths'] = 'No learning paths in your courses.';
$string['noprogress'] = 'No learning progress to show yet.';
$string['overduecount'] = '{$a} overdue';
$string['pathcount'] = '{$a->done} of {$a->total} steps finished';
$string['pathname'] = '{$a->course}: {$a->section}';
$string['plan'] = 'Plan';
$string['planactivity'] = 'Plan {$a}';
$string['plannedfor'] = 'Planned: {$a}';
$string['plannedlater'] = 'Planned: later';
$string['planupdated'] = 'Your plan has been updated.';
$string['pluginname'] = 'Work planner';
$string['previousweek'] = 'Previous week';
$string['privacy:metadata:plan'] = 'The plans a user makes for their activities in the work planner.';
$string['privacy:metadata:plan:cmid'] = 'The planned activity.';
$string['privacy:metadata:plan:courseid'] = 'The course of the planned activity.';
$string['privacy:metadata:plan:plandate'] = 'The day the activity is planned for.';
$string['privacy:metadata:plan:slot'] = 'Whether the activity is planned for a day or for later.';
$string['privacy:metadata:plan:timecreated'] = 'The time the plan was made.';
$string['privacy:metadata:plan:timemodified'] = 'The time the plan was last changed.';
$string['privacy:metadata:plan:userid'] = 'The user who made the plan.';
$string['privacy:metadata:preference:course'] = 'The course chosen in the course selection of the work planner, 0 for all courses.';
$string['progress'] = 'My learning progress';
$string['progress_help'] = 'Finished activities of all activities with completion tracking in each course. Under "Show grades" you see your own grades, as far as your teachers have released them in the gradebook.';
$string['progresscount'] = '{$a->done} of {$a->total}';
$string['removeplan'] = 'Remove from plan';
$string['removeplanfor'] = 'Remove {$a} from plan';
$string['returnnotice'] = 'You opened this activity from your work planner.';
$string['returntoplanner'] = 'Back to the work planner';
$string['showgrades'] = 'Show grades';
$string['statedone'] = 'Done';
$string['stateinprogress'] = 'In progress';
$string['statelocked'] = 'Locked';
$string['tasks'] = 'My tasks';
$string['tasks_help'] = 'By deadline: activities with a deadline. Learning path: steps in a fixed order. Free: materials without a deadline and outside a learning path. Drag a task onto a day of your week plan or use its "Plan" menu.';
$string['teachernote'] = 'Note from your teacher:';
$string['today'] = 'Today';
$string['viewdated'] = 'By deadline';
$string['viewfree'] = 'Free';
$string['viewpath'] = 'Learning path';
$string['week'] = 'My week plan';
$string['week_help'] = 'Red entries are deadlines set by your teachers. Coloured entries are activities you planned yourself. Only you can see your plan.';
$string['weeklabel'] = '{$a->from} – {$a->to}';
$string['weekloaded'] = 'The week plan has been updated.';
$string['workplanner:addinstance'] = 'Add a new work planner block';
$string['workplanner:myaddinstance'] = 'Add a new work planner block to the Dashboard';
$string['workplanner:plan'] = 'Plan own activities in the work planner';
$string['workplanner:view'] = 'View own work planner';
