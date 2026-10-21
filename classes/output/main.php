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

namespace block_workplanner\output;

use core\output\help_icon;
use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;

/**
 * The work planner as shown in the block.
 *
 * Adds what only the full page needs (root id, planning permission and help icons) to the planner data.
 * The changing parts are rendered again in the browser from the external function
 * get_planner, which returns the same planner data.
 *
 * @package    block_workplanner
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class main implements renderable, templatable {
    /** @var string[] Sections that carry a help icon; each needs the strings key and key_help. */
    private const HELP = ['infos', 'tasks', 'week', 'later', 'progress', 'competencies'];

    /** @var string Id of the root element, used by the JavaScript module. */
    private string $rootid;

    /** @var array Planner data from \block_workplanner\local\planner::export(). */
    private array $planner;

    /** @var bool Whether the user may plan activities. */
    private bool $canplan;

    /**
     * Creates the renderable.
     *
     * @param string $rootid Id of the root element.
     * @param array $planner Planner data.
     * @param bool $canplan Whether the user may plan activities.
     */
    public function __construct(string $rootid, array $planner, bool $canplan) {
        $this->rootid = $rootid;
        $this->planner = $planner;
        $this->canplan = $canplan;
    }

    /**
     * Returns the template context.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $help = [];
        foreach (self::HELP as $key) {
            $help[$key] = (new help_icon($key, 'block_workplanner'))->export_for_template($output);
        }
        return array_merge($this->planner, [
            'rootid' => $this->rootid,
            'canplan' => $this->canplan,
            'help' => $help,
        ]);
    }
}
