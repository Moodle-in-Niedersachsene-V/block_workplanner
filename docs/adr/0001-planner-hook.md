# ADR 0001: Other plugins extend the planner through a hook

Status: accepted, 20.10.2026

## Context

The work planner shows data that other plugins own: teacher information, notes on
activities and competencies come from the course assistant (`local_kursassistent`).
The course assistant will get an English component name before its Marketplace
submission, and schools may run the planner without it.

## Decision

The planner defines the hook `\block_workplanner\hook\extend_planner` and dispatches it
once per build. Plugins that want to add data register a callback in their own
`db/hooks.php`. The planner never names another plugin in its code.

Everything else the planner shows comes from core: enrolments, activity dates,
completion, and access restrictions for learning paths.

## Contract

- The hook carries the user id and the ids of the courses in the planner.
- `add_info(courseid, text, authorname, timecreated)`: teacher information for the user.
  A course outside the planner raises a `coding_exception`.
- `add_note(cmid, text)`: a teacher note on an activity for the user.
- `add_competency(cmid, competencyid, title)`: a competency assigned to an activity.
- All texts are plain text. The planner escapes them on output.
- The callback checks itself that the user may see what it adds.

## Consequences

- Renaming the course assistant changes only the course assistant.
- The planner works without any callback; the sections for information and
  competencies stay hidden then.
- Changing the method signatures breaks callbacks in other plugins, so they change only
  together with this ADR.
