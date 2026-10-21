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
 * One calendar week in the time zone of the current user.
 *
 * The week starts on the weekday set in the user's calendar preferences. Days are
 * counted with calendar arithmetic, so weeks with a daylight saving change stay
 * seven days long.
 *
 * @package    block_workplanner
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class week {
    /** @var \DateTimeImmutable Midnight of the first day of the week. */
    private \DateTimeImmutable $start;

    /**
     * Creates a week from the midnight of its first day.
     *
     * @param \DateTimeImmutable $start
     */
    private function __construct(\DateTimeImmutable $start) {
        $this->start = $start;
    }

    /**
     * Returns the week that contains a point in time.
     *
     * @param int $timestamp Any time within the wanted week.
     * @return self
     */
    public static function containing(int $timestamp): self {
        global $CFG;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $timezone = \core_date::get_user_timezone_object();
        $day = (new \DateTimeImmutable('@' . $timestamp))->setTimezone($timezone)->setTime(0, 0);
        $weekday = (int) $day->format('w');
        $offset = ($weekday - calendar_get_starting_weekday() + 7) % 7;
        return new self($day->modify('-' . $offset . ' days'));
    }

    /**
     * Returns the start of the week as a timestamp.
     *
     * @return int
     */
    public function get_start(): int {
        return $this->start->getTimestamp();
    }

    /**
     * Returns the start of the following week as a timestamp.
     *
     * @return int
     */
    public function get_end(): int {
        return $this->start->modify('+7 days')->getTimestamp();
    }

    /**
     * Returns the midnight timestamps of the seven days of the week.
     *
     * @return int[]
     */
    public function get_days(): array {
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = $this->start->modify('+' . $i . ' days')->getTimestamp();
        }
        return $days;
    }

    /**
     * Returns the week before this one.
     *
     * @return self
     */
    public function previous(): self {
        return new self($this->start->modify('-7 days'));
    }

    /**
     * Returns the week after this one.
     *
     * @return self
     */
    public function next(): self {
        return new self($this->start->modify('+7 days'));
    }

    /**
     * Tells whether a point in time lies within this week.
     *
     * @param int $timestamp
     * @return bool
     */
    public function contains(int $timestamp): bool {
        return $timestamp >= $this->get_start() && $timestamp < $this->get_end();
    }
}
