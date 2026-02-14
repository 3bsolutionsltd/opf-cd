PM Dashboard modeling constraints:

- Projects, tasks, milestones, risks, issues, approvals, and reports
  are separate entities and must not be merged
- Time must be explicit (start, end, due, recurrence)
- Activities may be one-time or repeating — do not assume either
- A project may exist without tasks
- A task may exist without assignments
- Approval is a state change, not a boolean
- Reports are historical snapshots, not live data
- Dashboards do not own data — they only read from it
