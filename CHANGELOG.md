# Changelog

This changelog describes **only the two most recent published GitHub releases** of `local_filexray`. Each entry is grounded in the Git comparison between its release tag and the immediately preceding **published** release tag. Later commits on `master`, including builds and source maintenance after `1.1.2`, are excluded.

## [1.1.2] - 2026-10-19

Published: 2026-10-19 14:50:50 UTC.

Comparison: [1.1.1...1.1.2](https://github.com/EduardoKrausME/moodle-local_filexray/compare/1.1.1...1.1.2)

### Inventory generation and resilience

- Added `course_context_inventory::populate_report()` to bulk-insert file metadata for course and course-module contexts **synchronously during report creation**, in the report creation transaction. Native backup-only file references are then supplemented asynchronously by the one-course-at-a-time cron worker.
- Updated native backup inventory insertion to skip file IDs already present in the same report/course inventory and avoid duplicate records.
- Updated the worker to preserve previously snapshotted files, append newer course/module context files during execution, and recompute counts from persisted records. On failure, retry or recovery, context-file metadata is restored so the report can retain its context coverage even when backup inspection fails.
- Extended report status data with formatted file counts, total **logical bytes** and formatted sizes; the JavaScript progress table now displays both counts and sizes.

### Comparison and reporting

- Added `comparison_summary::coverage()` to calculate three exclusive inventory groups by both file-record count and logical bytes, deduplicating references to the same Moodle file ID in the overall comparison.
- Added separate coverage visualizations for file counts and logical bytes, with expanded comparison labels and responsive layouts.
- Ranked the top five course inventories **on the current page** by logical byte size rather than file count.
- Added per-course component breakdowns, visual counts and logical-byte totals, and a component filter applied to the server-side paginated file list.
- Kept full saved-inventory component totals separate from individual file-list pagination; the same file can contribute to more than one course's logical totals.

### Interface and optional MCP notice

- Added a dismissible integration notice linking to `local_mcp` documentation. It is shown to site administrators when `local_mcp` is absent, unless dismissed via a per-user preference and a sesskey-protected POST.
- Added a Mustache banner template, CSS, and English/Brazilian Portuguese labels. **This release does not add MCP tools or make `local_mcp` a required dependency.**
- Made the report-generation button initially disabled in the server-rendered template and enabled it after the JavaScript click handler is installed.
- Updated the README to explain the initial context snapshot, background backup inspection and report interpretation.

### Tests and version metadata

- Updated regression tests for course-context inventory, backup inspection, report management and comparison summaries.
- Updated `version.php` from `2026101700` / release `1.1.1` to `2026101900` / release `1.1.2`.

## [1.1.1] - 2026-10-17

Published: 2026-10-17 13:49:41 UTC.

Comparison: [1.1.0...1.1.1](https://github.com/EduardoKrausME/moodle-local_filexray/compare/1.1.0...1.1.1)

### Inventory coverage

- Introduced `classes/course_context_inventory.php` to append metadata for files directly associated with the Moodle course context and its course-module contexts, beyond references selected by native course backup.
- Integrated that additional inventory pass into the per-course background worker. Existing file IDs are excluded for the same report/course to avoid duplication; stored file content is not copied or modified.
- Updated the README to clarify that files outside the combined inventory are **not necessarily orphaned**. User-context and other actively used files may remain unmatched.

### Comparison, charts and interface

- Improved the comparison page's matched-file calculation by joining saved file references to current `mdl_files` records and excluding directory placeholder records.
- Added an inventory-coverage chart distinguishing inventoried files, classified file areas outside inventories, and residual files. Added a top-five chart for courses with the most inventoried file records **on the displayed page**.
- Added a visible warning when user data is excluded from backups, plus a settings link for administrators with the appropriate capability.
- Adjusted the analyzed-course count and English/Brazilian Portuguese labels and chart styles.

### Configuration, tests and version metadata

- Changed the declared default of the `local_filexray/skipuserdata` setting from `0` to `1`, so excluding learner backup data is the default for installations without a previously stored setting. Previously saved configuration values are not changed by this default-setting diff.
- Added `tests/course_context_inventory_test.php` for the additional inventory functionality.
- Updated `version.php` from `2026101600` / release `1.1.0` to `2026101700` / release `1.1.1`.

### Interpretation and limitations

The backup fast-mode setting can exclude learner-related references, particularly files held in contexts other than course/module contexts. Missing inventory references are **not proof that a file is unused**, and no file deletion is implied by these comparisons. Counts and sizes in course charts represent saved inventories, not unique physical storage consumption.

---

These notes summarize verified differences between published tags. No runtime tests or deployments were executed while preparing this changelog, and changes after the latest release tag are not included.
