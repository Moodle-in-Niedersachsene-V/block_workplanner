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

use block_workplanner\hook\extend_planner;

/**
 * Builds the work planner of one user: tasks, learning paths, week plan, progress and competencies.
 *
 * The planner reads only core data (enrolments, access restrictions, activity dates,
 * completion) and the user's own plans. Other plugins add teacher information, notes and
 * competencies through the hook extend_planner. The result is the template context of
 * the block and the return value of the external function get_planner; both share
 * this one source.
 *
 * Courses the user can edit are left out: the planner shows a learner's tasks, not a
 * teacher's own courses.
 *
 * @package    block_workplanner
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class planner {
    /** @var int Number of course colours in styles.css. */
    private const COLOURS = 6;

    /** @var string[] Activity date identifiers that count as a deadline, in order of preference. */
    private const DEADLINES = ['duedate', 'timeclose'];

    /** @var int Maximum number of teacher information entries shown. */
    private const MAX_INFOS = 10;

    /** @var int User whose planner is built. */
    private int $userid;

    /** @var int Current time; the reference for overdue tasks and the current week. */
    private int $now;

    /**
     * Creates the planner of a user.
     *
     * @param int $userid
     * @param int $now Current time.
     */
    public function __construct(int $userid, int $now) {
        $this->userid = $userid;
        $this->now = $now;
    }

    /**
     * Returns the planner with the week plan of the given week.
     *
     * A course id of 0 shows all courses. A course that is not part of the user's planner
     * (for example a stored choice after the user left the course) also shows all courses.
     *
     * @param week $week Week shown in the week plan.
     * @param int $courseid Course to show, 0 for all courses.
     * @return array Template context, structure as in external\get_planner::execute_returns().
     */
    public function export(week $week, int $courseid = 0): array {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->libdir . '/gradelib.php');

        $allcourses = $this->get_courses();
        $courseid = isset($allcourses[$courseid]) ? $courseid : 0;
        $courses = $courseid ? [$courseid => $allcourses[$courseid]] : $allcourses;
        $hook = new extend_planner($this->userid, array_keys($courses));
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        $plans = plan_store::get_for_user($this->userid);

        $items = [];
        $paths = [];
        $progress = [];
        $infos = [];
        $courseoptions = [];
        $colours = [];
        $colour = 0;
        foreach ($allcourses as $course) {
            $colours[(int) $course->id] = $colour;
            $courseoptions[] = [
                'id' => (int) $course->id,
                'name' => $this->course_name($course),
                'selected' => (int) $course->id === $courseid,
            ];
            $colour = ($colour + 1) % self::COLOURS;
        }

        foreach ($courses as $course) {
            $coursename = $this->course_name($course);
            $colour = $colours[(int) $course->id];
            $result = $this->collect_course($course, $coursename, $colour, $hook, $plans);
            $items += $result['items'];
            foreach ($result['paths'] as $path) {
                $paths[] = $this->export_path($course, $coursename, $path, $result['items']);
            }
            if ($result['progress']) {
                $progress[] = $result['progress'];
            }
            foreach ($hook->get_infos((int) $course->id) as $info) {
                $infos[] = [
                    'coursename' => $coursename,
                    'colorindex' => $colour,
                    'authorname' => $info['authorname'],
                    'text' => $info['text'],
                    'timecreated' => $info['timecreated'],
                    'timetext' => $this->date_time($info['timecreated']),
                ];
            }
        }

        usort($infos, fn(array $a, array $b): int => $b['timecreated'] <=> $a['timecreated']);
        $infos = array_map(function (array $info): array {
            unset($info['timecreated']);
            return $info;
        }, array_slice($infos, 0, self::MAX_INFOS));

        $dated = $this->export_dated($items);
        $free = $this->export_free($items);
        $later = $this->export_later($items);
        $competencies = $this->export_competencies($items);

        return [
            'courseid' => $courseid,
            'courses' => $courseoptions,
            'weekstart' => $week->get_start(),
            'weeklabel' => $this->week_label($week),
            'prevweek' => $week->previous()->get_start(),
            'nextweek' => $week->next()->get_start(),
            'thisweek' => week::containing($this->now)->get_start(),
            'isthisweek' => $week->contains($this->now),
            'days' => $this->export_days($week, $items),
            'hasinfos' => !empty($infos),
            'infos' => $infos,
            'hasdated' => !empty($dated),
            'dated' => $dated,
            'haspaths' => !empty($paths),
            'paths' => $paths,
            'hasfree' => !empty($free),
            'free' => $free,
            'haslater' => !empty($later),
            'later' => $later,
            'hasprogress' => !empty($progress),
            'progress' => $progress,
            'hascompetencies' => !empty($competencies),
            'competencysummary' => $this->competency_summary($competencies),
            'competencies' => $competencies,
        ];
    }

    /**
     * Returns the visible courses the user takes part in as a learner, keyed by id.
     *
     * @return array<int, \stdClass>
     */
    private function get_courses(): array {
        $courses = [];
        foreach (enrol_get_all_users_courses($this->userid, true, 'enablecompletion, showgrades') as $course) {
            $context = \context_course::instance($course->id);
            if (!$course->visible && !has_capability('moodle/course:viewhiddencourses', $context, $this->userid)) {
                continue;
            }
            if (has_capability('moodle/course:manageactivities', $context, $this->userid)) {
                continue;
            }
            $courses[(int) $course->id] = $course;
        }
        return $courses;
    }

    /**
     * Collects the activities, learning paths and progress of one course.
     *
     * @param \stdClass $course
     * @param string $coursename Formatted course name.
     * @param int $colour Colour index of the course.
     * @param extend_planner $hook Data added by other plugins.
     * @param array<int, \stdClass> $plans Plans of the user by course module id.
     * @return array{items: array<int, array>, paths: int[][], progress: ?array}
     */
    private function collect_course(
        \stdClass $course,
        string $coursename,
        int $colour,
        extend_planner $hook,
        array $plans
    ): array {
        $modinfo = get_fast_modinfo($course, $this->userid);
        $completion = new \completion_info($course);
        $paths = learning_path::find($modinfo);

        $items = [];
        $tracked = 0;
        $done = 0;
        $overdue = 0;
        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->deletioninprogress || !$cm->url || !$cm->is_visible_on_course_page()) {
                continue;
            }
            $item = $this->build_item($cm, $coursename, $colour, $completion, $hook, $plans[(int) $cm->id] ?? null);
            $items[(int) $cm->id] = $item;

            if ($item['istracked']) {
                $tracked++;
                $done += $item['isdone'] ? 1 : 0;
                $overdue += $item['isoverdue'] ? 1 : 0;
            }
        }

        $visiblepaths = [];
        foreach ($paths as $path) {
            $steps = array_values(array_filter($path, fn(int $cmid): bool => isset($items[$cmid])));
            if (count($steps) > 1) {
                $visiblepaths[] = $steps;
                foreach ($steps as $cmid) {
                    $items[$cmid]['inpath'] = true;
                }
            }
        }

        $grades = $this->get_grades($course, $modinfo);
        $progress = null;
        if (($completion->is_enabled() && $tracked > 0) || $grades) {
            $progress = [
                'hascompletion' => $tracked > 0,
                'coursename' => $coursename,
                'colorindex' => $colour,
                'completed' => $done,
                'total' => $tracked,
                'percent' => $tracked > 0 ? (int) round($done * 100 / $tracked) : 0,
                'counttext' => get_string('progresscount', 'block_workplanner', (object) [
                    'done' => $done,
                    'total' => $tracked,
                ]),
                'hasoverdue' => $overdue > 0,
                'overduetext' => $overdue > 0 ? get_string('overduecount', 'block_workplanner', $overdue) : '',
                'hasgrades' => !empty($grades),
                'grades' => $grades,
            ];
        }

        return ['items' => $items, 'paths' => $visiblepaths, 'progress' => $progress];
    }

    /**
     * Builds the internal record of one activity.
     *
     * @param \cm_info $cm
     * @param string $coursename
     * @param int $colour
     * @param \completion_info $completion
     * @param extend_planner $hook
     * @param \stdClass|null $plan The user's plan for the activity.
     * @return array
     */
    private function build_item(
        \cm_info $cm,
        string $coursename,
        int $colour,
        \completion_info $completion,
        extend_planner $hook,
        ?\stdClass $plan
    ): array {
        $istracked = $completion->is_enabled($cm) != COMPLETION_TRACKING_NONE;
        $isdone = false;
        $isinprogress = false;
        if ($istracked) {
            $data = $completion->get_data($cm, false, $this->userid);
            $state = (int) $data->completionstate;
            $isdone = in_array($state, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS], true);
            $isinprogress = !$isdone && ($state === COMPLETION_COMPLETE_FAIL || !empty($data->timemodified));
        }

        $due = $this->find_deadline($cm);
        $islocked = !$cm->uservisible || !$cm->available;

        return [
            'cmid' => (int) $cm->id,
            'courseid' => (int) $cm->course,
            'coursename' => $coursename,
            'colorindex' => $colour,
            'name' => $cm->get_formatted_name(['escape' => false]),
            'url' => $islocked ? '' : $cm->url->out(false),
            'iconurl' => $cm->get_icon_url()->out(false),
            'modtype' => (string) $cm->get_module_type_name(),
            'sectionnum' => (int) $cm->sectionnum,
            'due' => $due,
            'istracked' => $istracked,
            'isdone' => $isdone,
            'isinprogress' => $isinprogress,
            'isoverdue' => !$isdone && $due > 0 && $due < $this->now,
            'islocked' => $islocked,
            'inpath' => false,
            'plan' => $plan,
            'note' => $hook->get_note((int) $cm->id),
            'competencies' => $hook->get_competencies((int) $cm->id),
        ];
    }

    /**
     * Returns the deadline the teacher set for an activity, or 0.
     *
     * Activity dates such as the due date of an assignment come first; the expected
     * completion date counts when the activity has none.
     *
     * @param \cm_info $cm
     * @return int
     */
    private function find_deadline(\cm_info $cm): int {
        $dates = [];
        foreach (\core\activity_dates::get_dates_for_module($cm, $this->userid) as $date) {
            if (!empty($date['dataid']) && !empty($date['timestamp'])) {
                $dates[$date['dataid']] = (int) $date['timestamp'];
            }
        }
        foreach (self::DEADLINES as $dataid) {
            if (!empty($dates[$dataid])) {
                return $dates[$dataid];
            }
        }
        return (int) $cm->completionexpected;
    }

    /**
     * Exports open activities with a deadline, grouped into overdue, this week, next week and later.
     *
     * @param array<int, array> $items
     * @return array
     */
    private function export_dated(array $items): array {
        $current = week::containing($this->now);
        $limits = [
            'overdue' => $this->now,
            'thisweek' => $current->get_end(),
            'nextweek' => $current->next()->get_end(),
            'later' => PHP_INT_MAX,
        ];
        $open = array_filter($items, fn(array $item): bool => $item['due'] > 0 && !$item['isdone']);
        uasort($open, fn(array $a, array $b): int => $a['due'] <=> $b['due']);

        $groups = [];
        foreach ($open as $item) {
            foreach ($limits as $key => $limit) {
                if ($item['due'] < $limit) {
                    $groups[$key][] = $this->export_card($item);
                    break;
                }
            }
        }

        $result = [];
        foreach (array_keys($limits) as $key) {
            if (!empty($groups[$key])) {
                $result[] = [
                    'label' => get_string('group' . $key, 'block_workplanner'),
                    'isoverdue' => $key === 'overdue',
                    'cards' => $groups[$key],
                ];
            }
        }
        return $result;
    }

    /**
     * Exports one learning path with its steps.
     *
     * @param \stdClass $course
     * @param string $coursename
     * @param int[] $cmids Steps in path order.
     * @param array<int, array> $items Activities of the course.
     * @return array
     */
    private function export_path(\stdClass $course, string $coursename, array $cmids, array $items): array {
        $first = $items[$cmids[0]];
        $sectionname = get_section_name($course, $first['sectionnum']);
        $steps = [];
        $currentfound = false;
        $done = 0;
        foreach ($cmids as $index => $cmid) {
            $item = $items[$cmid];
            $iscurrent = !$currentfound && !$item['isdone'] && !$item['islocked'];
            $currentfound = $currentfound || $iscurrent;
            $done += $item['isdone'] ? 1 : 0;
            $steps[] = [
                'number' => $index + 1,
                'iscurrent' => $iscurrent,
                'card' => $this->export_card($item),
            ];
        }
        return [
            'name' => get_string('pathname', 'block_workplanner', (object) [
                'course' => $coursename,
                'section' => $sectionname,
            ]),
            'colorindex' => $first['colorindex'],
            'counttext' => get_string('pathcount', 'block_workplanner', (object) [
                'done' => $done,
                'total' => count($cmids),
            ]),
            'steps' => $steps,
        ];
    }

    /**
     * Exports activities without a deadline that are not part of a learning path.
     *
     * Open activities come first, finished ones after them, each in course order.
     *
     * @param array<int, array> $items
     * @return array
     */
    private function export_free(array $items): array {
        $free = array_filter($items, fn(array $item): bool => $item['due'] === 0 && !$item['inpath']);
        $open = array_filter($free, fn(array $item): bool => !$item['isdone']);
        $finished = array_filter($free, fn(array $item): bool => $item['isdone']);
        $sorted = array_merge(array_values($open), array_values($finished));
        return array_map(fn(array $item): array => $this->export_card($item), $sorted);
    }

    /**
     * Exports the activities in the later area.
     *
     * @param array<int, array> $items
     * @return array
     */
    private function export_later(array $items): array {
        $later = array_filter(
            $items,
            fn(array $item): bool => $item['plan'] && $item['plan']->slot === plan_store::SLOT_LATER
        );
        return array_values(array_map(fn(array $item): array => $this->export_entry($item, false), $later));
    }

    /**
     * Exports the seven days of the week plan with deadlines and the user's plans.
     *
     * @param week $week
     * @param array<int, array> $items
     * @return array
     */
    private function export_days(week $week, array $items): array {
        $days = $week->get_days();
        $today = usergetmidnight($this->now);
        $timezone = \core_date::get_user_timezone_object();
        $result = [];
        foreach ($days as $index => $day) {
            $end = $days[$index + 1] ?? $week->get_end();
            $entries = [];
            foreach ($items as $item) {
                if ($item['due'] >= $day && $item['due'] < $end) {
                    $entries[] = $this->export_entry($item, true);
                }
            }
            foreach ($items as $item) {
                $plan = $item['plan'];
                if ($plan && $plan->slot === plan_store::SLOT_DAY && $plan->plandate >= $day && $plan->plandate < $end) {
                    $entries[] = $this->export_entry($item, false);
                }
            }
            $weekday = (int) (new \DateTimeImmutable('@' . $day))->setTimezone($timezone)->format('w');
            $result[] = [
                'day' => $day,
                'weekday' => userdate($day, '%a'),
                'datetext' => userdate($day, get_string('strftimedateshort', 'langconfig')),
                'istoday' => $day === $today,
                'isweekend' => $weekday === 0 || $weekday === 6,
                'hasentries' => !empty($entries),
                'entries' => $entries,
            ];
        }
        return $result;
    }

    /**
     * Exports the competencies of the user's activities with their state.
     *
     * A competency is achieved when all its visible activities are finished and in
     * progress when at least one of them is finished or started. Competencies in progress
     * come first, then open ones, then achieved ones, each by title.
     *
     * @param array<int, array> $items
     * @return array
     */
    private function export_competencies(array $items): array {
        $competencies = [];
        foreach ($items as $item) {
            foreach ($item['competencies'] as $id => $title) {
                $competencies[$id] ??= [
                    'title' => $title,
                    'courses' => [],
                    'done' => 0,
                    'started' => false,
                    'total' => 0,
                ];
                $competencies[$id]['courses'][$item['coursename']] = true;
                $competencies[$id]['total']++;
                $competencies[$id]['done'] += $item['isdone'] ? 1 : 0;
                $competencies[$id]['started'] = $competencies[$id]['started'] || $item['isinprogress'];
            }
        }

        $result = [];
        foreach ($competencies as $competency) {
            $isachieved = $competency['done'] === $competency['total'];
            $isinprogress = !$isachieved && ($competency['done'] > 0 || $competency['started']);
            $state = $isachieved ? 'achieved' : ($isinprogress ? 'inprogress' : 'open');
            $result[] = [
                'order' => array_search($state, ['inprogress', 'open', 'achieved'], true),
                'title' => $competency['title'],
                'coursenames' => implode(', ', array_keys($competency['courses'])),
                'isachieved' => $isachieved,
                'isinprogress' => $isinprogress,
                'isopen' => !$isachieved && !$isinprogress,
                'statetext' => get_string('competency' . $state, 'block_workplanner', (object) [
                    'done' => $competency['done'],
                    'total' => $competency['total'],
                ]),
            ];
        }
        usort($result, fn(array $a, array $b): int => [$a['order'], \core_text::strtolower($a['title'])]
            <=> [$b['order'], \core_text::strtolower($b['title'])]);
        return array_map(function (array $competency): array {
            unset($competency['order']);
            return $competency;
        }, $result);
    }

    /**
     * Summarises the competencies by state, for example "2 achieved · 1 in progress · 3 open".
     *
     * @param array $competencies Exported competencies.
     * @return string
     */
    private function competency_summary(array $competencies): string {
        return get_string('competencysummary', 'block_workplanner', (object) [
            'achieved' => count(array_filter($competencies, fn(array $c): bool => $c['isachieved'])),
            'inprogress' => count(array_filter($competencies, fn(array $c): bool => $c['isinprogress'])),
            'open' => count(array_filter($competencies, fn(array $c): bool => $c['isopen'])),
        ]);
    }

    /**
     * Exports an activity as a task card.
     *
     * @param array $item
     * @return array
     */
    private function export_card(array $item): array {
        $plan = $item['plan'];
        $plantext = '';
        if ($plan && $plan->slot === plan_store::SLOT_DAY) {
            $plantext = get_string('plannedfor', 'block_workplanner', $this->short_date((int) $plan->plandate));
        } else if ($plan && $plan->slot === plan_store::SLOT_LATER) {
            $plantext = get_string('plannedlater', 'block_workplanner');
        }

        $competencies = [];
        foreach ($item['competencies'] as $title) {
            $competencies[] = ['title' => $title];
        }

        $count = count($competencies);
        return [
            'cmid' => $item['cmid'],
            'courseid' => $item['courseid'],
            'name' => $item['name'],
            'url' => $item['url'],
            'hasurl' => $item['url'] !== '',
            'iconurl' => $item['iconurl'],
            'modtype' => $item['modtype'],
            'coursename' => $item['coursename'],
            'colorindex' => $item['colorindex'],
            'hasdue' => $item['due'] > 0,
            'duetext' => $item['due'] > 0 ? get_string('dueon', 'block_workplanner', $this->date_time($item['due'])) : '',
            'isoverdue' => $item['isoverdue'],
            'isdone' => $item['isdone'],
            'isinprogress' => $item['isinprogress'],
            'islocked' => $item['islocked'],
            'isplanned' => $plan !== null,
            'plantext' => $plantext,
            'candrag' => !$item['isdone'],
            'hasnote' => $item['note'] !== '',
            'note' => $item['note'],
            'hascompetencies' => $count > 0,
            'competencytext' => $count === 1
                ? get_string('competencycountone', 'block_workplanner')
                : get_string('competencycount', 'block_workplanner', $count),
            'competencies' => $competencies,
        ];
    }

    /**
     * Exports an activity as an entry of the week plan or the later area.
     *
     * @param array $item
     * @param bool $isdeadline True for the teacher's deadline, false for the user's plan.
     * @return array
     */
    private function export_entry(array $item, bool $isdeadline): array {
        return [
            'cmid' => $item['cmid'],
            'courseid' => $item['courseid'],
            'name' => $item['name'],
            'url' => $item['url'],
            'hasurl' => $item['url'] !== '',
            'coursename' => $item['coursename'],
            'colorindex' => $item['colorindex'],
            'isdeadline' => $isdeadline,
            'deadlinetext' => $isdeadline
                ? get_string('deadlineat', 'block_workplanner', userdate($item['due'], get_string('strftimetime', 'langconfig')))
                : '',
            'isdone' => $item['isdone'],
            'candrag' => !$isdeadline && !$item['isdone'],
        ];
    }

    /**
     * Returns the label of a week, for example "19 October – 25 October 2026".
     *
     * @param week $week
     * @return string
     */
    private function week_label(week $week): string {
        $days = $week->get_days();
        return get_string('weeklabel', 'block_workplanner', (object) [
            'from' => userdate($days[0], get_string('strftimedateshort', 'langconfig')),
            'to' => userdate($days[6], get_string('strftimedate', 'langconfig')),
        ]);
    }

    /**
     * Returns the formatted name of a course as plain text.
     *
     * @param \stdClass $course
     * @return string
     */
    private function course_name(\stdClass $course): string {
        return format_string($course->fullname, true, [
            'context' => \context_course::instance($course->id),
            'escape' => false,
        ]);
    }

    /**
     * Returns the user's own grades for the activities of a course.
     *
     * Only grades the user may see are returned: the course shows grades to participants,
     * the user may view grades, and neither the grade item nor the grade is hidden.
     * Visibility is decided by the gradebook; the planner only reads it.
     *
     * @param \stdClass $course
     * @param \course_modinfo $modinfo
     * @return array List of activity name and grade text, in course order.
     */
    private function get_grades(\stdClass $course, \course_modinfo $modinfo): array {
        global $DB;

        $context = \context_course::instance($course->id);
        if (empty($course->showgrades) || !has_capability('moodle/grade:view', $context, $this->userid)) {
            return [];
        }
        $items = $DB->get_records(
            'grade_items',
            ['courseid' => $course->id, 'itemtype' => 'mod', 'itemnumber' => 0],
            '',
            'id, itemmodule, iteminstance'
        );
        $graded = [];
        foreach ($items as $item) {
            $instances = $modinfo->get_instances_of($item->itemmodule);
            $cm = $instances[$item->iteminstance] ?? null;
            if ($cm && $cm->uservisible && !$cm->deletioninprogress) {
                $graded[(int) $cm->id] = $cm;
            }
        }

        $grades = [];
        foreach ($modinfo->get_cms() as $cm) {
            if (!isset($graded[$cm->id])) {
                continue;
            }
            $info = grade_get_grades($course->id, 'mod', $cm->modname, $cm->instance, $this->userid);
            $item = $info->items[0] ?? null;
            $grade = $item->grades[$this->userid] ?? null;
            if (!$item || !empty($item->hidden) || !$grade || $grade->grade === null || !empty($grade->hidden)) {
                continue;
            }
            $grades[] = [
                'name' => $cm->get_formatted_name(['escape' => false]),
                'grade' => trim(strip_tags((string) ($grade->str_long_grade ?? $grade->str_grade))),
            ];
        }
        return $grades;
    }

    /**
     * Formats a point in time as short date and time.
     *
     * @param int $time
     * @return string
     */
    private function date_time(int $time): string {
        return userdate($time, get_string('strftimedatetimeshort', 'langconfig'));
    }

    /**
     * Formats a day as weekday and date, for example "Wed, 21 October".
     *
     * @param int $day
     * @return string
     */
    private function short_date(int $day): string {
        return userdate($day, '%a') . ', ' . userdate($day, get_string('strftimedateshort', 'langconfig'));
    }
}
