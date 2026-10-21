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

/**
 * Finds the learning paths of a course from the access restrictions of its activities.
 *
 * A learning path is a set of activities joined by restrictions of the kind "requires
 * completion of activity A" or "requires a grade in activity A". This is how the course
 * assistant and the core restriction form build learning paths, so the planner needs no
 * data of its own to show them. Restrictions inside a negated set ("must not") never
 * count as a step.
 *
 * @package    block_workplanner
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class learning_path {
    /** @var int[] Completion states a restriction must require to count as a path step. */
    private const COMPLETED_STATES = [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS];

    /**
     * Returns the learning paths of a course, each as course module ids in path order.
     *
     * Paths are sorted by the course position of their first step. Within a path, a step
     * comes after all steps it depends on; steps on the same level keep their course order.
     *
     * @param \course_modinfo $modinfo
     * @return int[][]
     */
    public static function find(\course_modinfo $modinfo): array {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $order = self::course_order($modinfo);
        $edges = self::find_edges($modinfo, $order);
        if (!$edges) {
            return [];
        }

        $groups = self::group($edges);
        $paths = [];
        foreach ($groups as $members) {
            $paths[] = self::sort_steps($members, $edges, $order);
        }
        usort($paths, fn(array $a, array $b): int => $order[$a[0]] <=> $order[$b[0]]);
        return $paths;
    }

    /**
     * Returns the position of every course module in course order.
     *
     * @param \course_modinfo $modinfo
     * @return array Course module id => position.
     */
    private static function course_order(\course_modinfo $modinfo): array {
        $order = [];
        foreach ($modinfo->get_sections() as $cmids) {
            foreach ($cmids as $cmid) {
                $order[(int) $cmid] = count($order);
            }
        }
        return $order;
    }

    /**
     * Collects the dependencies "required activity => dependent activity".
     *
     * @param \course_modinfo $modinfo
     * @param array $order Course module id => position in course order.
     * @return array Dependent course module id => required course module ids.
     */
    private static function find_edges(\course_modinfo $modinfo, array $order): array {
        $gradeitems = null;
        $edges = [];
        foreach ($modinfo->get_cms() as $cm) {
            if (empty($cm->availability) || $cm->deletioninprogress || !isset($order[$cm->id])) {
                continue;
            }
            $tree = json_decode($cm->availability);
            if (!is_object($tree)) {
                continue;
            }
            foreach (self::positive_conditions($tree) as $condition) {
                $required = 0;
                if (($condition->type ?? '') === 'completion') {
                    $required = self::required_by_completion($condition, $cm, $modinfo, $order);
                } else if (($condition->type ?? '') === 'grade' && !empty($condition->id)) {
                    $gradeitems ??= self::grade_item_modules($modinfo);
                    $required = $gradeitems[(int) $condition->id] ?? 0;
                }
                if ($required && $required !== (int) $cm->id && isset($order[$required])) {
                    $edges[(int) $cm->id][] = $required;
                }
            }
        }
        return $edges;
    }

    /**
     * Returns all conditions of a restriction tree that are not inside a negated set.
     *
     * @param \stdClass $tree
     * @return \stdClass[]
     */
    private static function positive_conditions(\stdClass $tree): array {
        if (isset($tree->op) && str_starts_with((string) $tree->op, '!')) {
            return [];
        }
        $conditions = [];
        foreach ($tree->c ?? [] as $child) {
            if (!is_object($child)) {
                continue;
            }
            if (isset($child->c)) {
                $conditions = array_merge($conditions, self::positive_conditions($child));
            } else {
                $conditions[] = $child;
            }
        }
        return $conditions;
    }

    /**
     * Resolves the activity a completion condition requires.
     *
     * A course module id of -1 means "the previous activity with completion tracking".
     *
     * @param \stdClass $condition
     * @param \cm_info $cm Activity that carries the condition.
     * @param \course_modinfo $modinfo
     * @param array $order Course module id => position in course order.
     * @return int Course module id, 0 if the condition does not require completion.
     */
    private static function required_by_completion(
        \stdClass $condition,
        \cm_info $cm,
        \course_modinfo $modinfo,
        array $order
    ): int {
        if (!in_array((int) ($condition->e ?? 0), self::COMPLETED_STATES, true)) {
            return 0;
        }
        $required = (int) ($condition->cm ?? 0);
        if ($required !== -1) {
            return $required;
        }
        $previous = 0;
        foreach ($order as $cmid => $position) {
            if ($position >= $order[$cm->id]) {
                break;
            }
            $other = $modinfo->get_cm($cmid);
            if ($other->completion != COMPLETION_TRACKING_NONE && !$other->deletioninprogress) {
                $previous = $cmid;
            }
        }
        return $previous;
    }

    /**
     * Maps the grade items of the course's activities to their course modules.
     *
     * @param \course_modinfo $modinfo
     * @return array Grade item id => course module id.
     */
    private static function grade_item_modules(\course_modinfo $modinfo): array {
        global $DB;

        $items = $DB->get_records(
            'grade_items',
            ['courseid' => $modinfo->get_course_id(), 'itemtype' => 'mod'],
            '',
            'id, itemmodule, iteminstance'
        );
        $map = [];
        foreach ($items as $item) {
            $instances = $modinfo->get_instances_of($item->itemmodule);
            if (isset($instances[$item->iteminstance])) {
                $map[(int) $item->id] = (int) $instances[$item->iteminstance]->id;
            }
        }
        return $map;
    }

    /**
     * Groups activities that are joined by dependencies, directly or indirectly.
     *
     * @param array $edges Dependent course module id => required course module ids.
     * @return int[][] Each group as a list of course module ids.
     */
    private static function group(array $edges): array {
        $parent = [];
        $find = function (int $id) use (&$parent, &$find): int {
            $parent[$id] ??= $id;
            if ($parent[$id] !== $id) {
                $parent[$id] = $find($parent[$id]);
            }
            return $parent[$id];
        };
        foreach ($edges as $dependent => $requireds) {
            foreach ($requireds as $required) {
                $parent[$find($dependent)] = $find($required);
            }
        }
        $groups = [];
        foreach (array_keys($parent) as $id) {
            $groups[$find($id)][] = $id;
        }
        return array_values($groups);
    }

    /**
     * Orders the steps of one path so that every step follows the steps it requires.
     *
     * @param int[] $members
     * @param array $edges Dependent course module id => required course module ids.
     * @param array $order Course module id => position in course order.
     * @return int[]
     */
    private static function sort_steps(array $members, array $edges, array $order): array {
        $pending = [];
        foreach ($members as $id) {
            $pending[$id] = count(array_unique($edges[$id] ?? []));
        }
        $sorted = [];
        while ($pending) {
            $ready = array_keys(array_filter($pending, fn(int $count): bool => $count === 0));
            if (!$ready) {
                // A circular restriction can never be met; the remaining steps follow in course order.
                $ready = array_keys($pending);
            }
            usort($ready, fn(int $a, int $b): int => $order[$a] <=> $order[$b]);
            $next = $ready[0];
            $sorted[] = $next;
            unset($pending[$next]);
            foreach ($pending as $id => $count) {
                if (in_array($next, $edges[$id] ?? [], true)) {
                    $pending[$id] = max(0, $count - 1);
                }
            }
        }
        return $sorted;
    }
}
