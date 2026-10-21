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
 * External functions of block_workplanner.
 *
 * @package    block_workplanner
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_workplanner_get_planner' => [
        'classname' => \block_workplanner\external\get_planner::class,
        'description' => 'Returns the work planner of the current user for one week.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'block/workplanner:view',
    ],
    'block_workplanner_set_plan' => [
        'classname' => \block_workplanner\external\set_plan::class,
        'description' => 'Plans an activity for a day, moves it to the later area or removes the plan.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'block/workplanner:plan',
    ],
];
