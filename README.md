# Work planner (block_workplanner)

A dashboard block for learners. It collects the activities of all their courses and
helps them plan their work.

- **My tasks** in three views: *By deadline* (overdue, this week, next week, later),
  *Learning path* (steps in order, the next step highlighted) and *Free* (materials
  without a deadline and outside a learning path).
- **My week plan**: seven days with the teachers' deadlines. Learners drag activities onto
  a day or use the "Plan" menu, which also works with the keyboard and on touch screens.
- **Later**: a place for activities without a fixed day.
- **My learning progress** per course, from activity completion.
- **My competencies** and **information from your teachers**, provided by other plugins
  through a hook (see below).

**Way back:** when a learner opens an activity from the planner, the pages of that course
show a notice with a button back to the planner. Landing on the course page from an
activity (for example after "Save and return to course") returns to the planner directly.
The mark lives only in the browser tab (session storage) and expires after three hours.

Plans are private to the learner. Courses the user can edit are not shown, so teachers
do not see their own courses as tasks.

## Requirements

- Moodle 5.0 or later
- PHP 8.2, 8.3 or 8.4
- MariaDB, MySQL or PostgreSQL

## Installation

1. Unzip the plugin into `blocks/workplanner` (Moodle 5.1 and later:
   `public/blocks/workplanner`).
2. Visit *Site administration > Notifications* to complete the installation.
3. Add the block to the default dashboard (*Site administration > Appearance >
   Default Dashboard page*), in the main content region, and reset the dashboards
   for all users if it should appear for everyone.

## Where the data comes from

| Section | Source |
|---|---|
| Deadlines | Activity dates (for example due date, close time), else the expected completion date |
| Learning paths | Access restrictions "requires completion of" or "requires grade in" another activity |
| Free | Activities without a deadline and outside a learning path |
| Progress | Activity completion |
| Competencies, teacher information, notes | Other plugins through the hook |

## Hook for other plugins

Plugins add data through `\block_workplanner\hook\extend_planner`. Register a callback
in your plugin's `db/hooks.php`:

```php
$callbacks = [
    [
        'hook' => \block_workplanner\hook\extend_planner::class,
        'callback' => \local_example\hook_callbacks::class . '::extend_planner',
    ],
];
```

The callback receives the hook with `$hook->userid` and `$hook->courseids` and may call
`add_info()`, `add_note()` and `add_competency()`. All texts are plain text. See
`docs/adr/0001-planner-hook.md` for the contract.

## Capabilities

| Capability | Default | Purpose |
|---|---|---|
| `block/workplanner:myaddinstance` | Authenticated user | Add the block to the own dashboard |
| `block/workplanner:addinstance` | Manager | Add the block |
| `block/workplanner:view` | Authenticated user | See the own planner |
| `block/workplanner:plan` | Authenticated user | Plan own activities |

## Privacy

The plugin stores one record per planned activity: user, course, activity, day or
"later", and times. Data export and deletion are implemented through the privacy API.
Plans are removed when the activity, the course or the user is deleted.

## License

2026 Moodle in Niedersachsen e. V.

This program is free software: you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation, either
version 3 of the License, or (at your option) any later version.
