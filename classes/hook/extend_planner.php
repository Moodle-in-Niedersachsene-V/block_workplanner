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

namespace block_workplanner\hook;

/**
 * Lets other plugins add teacher information, activity notes and competencies to a work planner.
 *
 * The planner dispatches this hook once per build, for one user and the courses shown to them.
 * Callbacks may only add data for these courses; they must check themselves that the user may
 * see what they add. All texts are plain text; the planner escapes them on output.
 *
 * See docs/adr/0001-planner-hook.md for why the planner offers a hook instead of calling
 * other plugins directly.
 *
 * @package    block_workplanner
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\label('Allows plugins to add teacher information, activity notes and competencies to the work planner.')]
#[\core\attribute\tags('block_workplanner', 'user')]
final class extend_planner {
    /** @var int User who sees the planner. */
    public readonly int $userid;

    /** @var int[] Courses shown in the planner. */
    public readonly array $courseids;

    /** @var array Information per course id. */
    private array $infos = [];

    /** @var array Teacher note per course module id. */
    private array $notes = [];

    /** @var array Competency titles per course module id and competency id. */
    private array $competencies = [];

    /**
     * Creates the hook for one user and the courses of their planner.
     *
     * @param int $userid User who sees the planner.
     * @param int[] $courseids Courses shown in the planner.
     */
    public function __construct(int $userid, array $courseids) {
        $this->userid = $userid;
        $this->courseids = array_map('intval', array_values($courseids));
    }

    /**
     * Adds a piece of teacher information for the user in one of their courses.
     *
     * @param int $courseid Course the information belongs to.
     * @param string $text Plain text of the information.
     * @param string $authorname Display name of the author.
     * @param int $timecreated Time the information was written.
     * @return void
     * @throws \coding_exception If the course is not part of the planner.
     */
    public function add_info(int $courseid, string $text, string $authorname, int $timecreated): void {
        $this->require_course($courseid);
        $this->infos[$courseid][] = [
            'text' => $text,
            'authorname' => $authorname,
            'timecreated' => $timecreated,
        ];
    }

    /**
     * Adds a teacher note for the user on one activity.
     *
     * @param int $cmid Course module the note belongs to.
     * @param string $text Plain text of the note.
     * @return void
     */
    public function add_note(int $cmid, string $text): void {
        $this->notes[$cmid] = $text;
    }

    /**
     * Assigns a competency to an activity.
     *
     * @param int $cmid Course module the competency is assigned to.
     * @param int $competencyid Identifier of the competency in the providing plugin.
     * @param string $title Plain text title of the competency.
     * @return void
     */
    public function add_competency(int $cmid, int $competencyid, string $title): void {
        $this->competencies[$cmid][$competencyid] = $title;
    }

    /**
     * Returns the information added for a course.
     *
     * @param int $courseid
     * @return array List of entries with text, authorname and timecreated.
     */
    public function get_infos(int $courseid): array {
        return $this->infos[$courseid] ?? [];
    }

    /**
     * Returns the teacher note for an activity, or an empty string.
     *
     * @param int $cmid
     * @return string
     */
    public function get_note(int $cmid): string {
        return $this->notes[$cmid] ?? '';
    }

    /**
     * Returns the competencies of an activity as competency id => title.
     *
     * @param int $cmid
     * @return array Competency id => title.
     */
    public function get_competencies(int $cmid): array {
        return $this->competencies[$cmid] ?? [];
    }

    /**
     * Stops callbacks from adding data for courses outside the planner.
     *
     * @param int $courseid
     * @return void
     * @throws \coding_exception
     */
    private function require_course(int $courseid): void {
        if (!in_array($courseid, $this->courseids, true)) {
            throw new \coding_exception('Course ' . $courseid . ' is not part of the work planner.');
        }
    }
}
