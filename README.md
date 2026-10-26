# File X-Ray for Moodle

File X-Ray audits which records in Moodle's `files` table are selected by each course's native backup process. **No MBZ is generated and no physical file content is copied or deleted.**

## Documentation

The bilingual documentation site uses the same visual layout as [Moodle MCP documentation](https://eduardokrausme.github.io/moodle-local_mcp/), adapted for File X-Ray. Browse [Portuguese](docs/pt_br/index.html) or [English](docs/en/index.html) pages in this repository, or publish the `docs/` directory using GitHub Pages.

The source includes a public overview, installation, native backup inspection, report processing, per-course comparison, file-area explanations, security limits and privacy information. It does not imply that files outside course inventories are safe to delete.

## Workflow

1. Open **Site administration → Reports → File X-Ray** and select **Generate report**.
2. File X-Ray creates `local_filexray_report`, snapshots each ordinary Moodle course into `local_filexray_course` and immediately inserts file metadata from course and activity contexts into `local_filexray_file` in the same creation transaction. This synchronous SQL snapshot is committed before returning the new report ID.
3. An ad-hoc cron task adds native backup references **one course at a time**. A report-level lock prevents parallel work on the same report and the worker queues the next course after completing the previous one.
4. The browser polls progress over Moodle's authenticated `core/ajax`, displaying progress, errors and the 50 courses on the selected page. Closing the tab does not stop cron.
5. Each course's final backup `filefinal` references are captured before Moodle's `fileslist` step. This skips the physical copy, MBZ zip and destination-storage steps.
6. The creation request bulk-loads `mdl_files` records belonging to the course context and all of its `course_modules` contexts before cron starts. The worker then supplements these records with backup-only references and adds any context files created after report creation, deduplicated by file ID within each report/course.
7. **Compare with mdl_files** lists file records not encountered by this combined inventory. These are **not automatically orphaned**: system/user files, other Moodle features and plugins without complete backup support can reference them.

## Performance: excluding learner data

In **Site administration → Plugins → Local plugins → File X-Ray**, enable **Exclude user data from inventory (faster)** to turn off the native Moodle backup `users` setting for subsequent course inspections. This can improve processing time on courses with many participants, submissions and posts. The option is off by default so existing installations preserve their previous behaviour.

**Trade-off:** files in the course and activity contexts are still inventoried, even if the backup excludes user data. Learner-generated files kept in other contexts (such as user contexts) and not referenced by the backup may still show up as unmatched despite being in use. This does not delete any files or students and unmatched entries are not evidence that files are orphaned. To inspect those files, leave the option disabled and ensure the native Moodle backup settings permit user data. Do not change this option while a report is running because pending courses would then be inspected differently from completed courses. If the Moodle backup user setting is locked on, the fast-mode inspection fails rather than bypassing the lock.

## Architecture

* `local_filexray_report` — snapshot metadata and status.
* `local_filexray_course` — one course job per report, with timestamps/errors/counts.
* `local_filexray_file` — file record ID, content hash and related metadata for a given course and report.
* `classes/backup_inventory.php` — runs Moodle's general backup plan through the final annotation migration, but deliberately stops **before** `backup_final_files_structure_step` (`fileslist`), which would otherwise copy files.
* `classes/course_context_inventory.php` — bulk-inserts all course and activity context file metadata on report creation; later appends new context files without duplicating existing report/course file IDs.
* `classes/task/process_course.php` — one task per course, chaining the next job on cron.
* `classes/report_manager.php` — creates the initial course/file snapshot in a database transaction and provides paginated live progress.

**Requirements** — Moodle 4.5 or later, PHP compatible with the Moodle instance, working Moodle cron, site management permissions.

## Security and limitations

Only users with `local/filexray:view` can see inventories; `local/filexray:run` is needed to create and resume reports. Both capabilities are system-context capabilities. The comparison is read-only and never deletes a file.

This implementation combines file references resolved by Moodle backup with files directly attached to the course or activity contexts. It does not claim every record outside that set is unreferenced or safe to delete; neither does it scan `moodledata/filedir` for physical orphan hashes. A file can be shared by several records with the same `contenthash` and therefore file record counts are not equivalent to occupied disk bytes.

A failed course makes the entire comparison provisional. Reports keep the original list of courses, even if courses are removed after the report was created. A report whose cron worker died can be resumed by selecting **Resume pending courses**; a still-running row is reclaimed only after two hours and after obtaining the exclusive report lock.

The initial context lookup is synchronous: creating a report may take longer on sites with large numbers of files, although it uses two database-side `INSERT ... SELECT` operations rather than inserting every file from PHP. For a very large site, calculate disk occupancy separately by **distinct contenthash**, not by summing logical file-record sizes. Do not delete anything on the basis of this report alone.

## CLI

To enqueue a report without accessing the browser, run from the Moodle installation root

```bash
php local/filexray/cli/create_report.php
```

The ad-hoc task queue executes the inventory. Keep `admin/cli/cron.php` running in the site's normal schedule.

## Development checks

The CI workflow runs PHP lint and the full repository-specific validator from

https://github.com/EduardoKrausME/moodle-plugin-validate/tree/master/.chatgpt

It does not commit formatter changes automatically.

## Detailed comparison

The comparison page now shows paginated per-course counts and logical bytes taken from the saved report, followed by a file-area breakdown grouped by `component` and `filearea`. User drafts (`user/draft`), user icons (`user/icon`), private files (`user/private`) and profile files (`user/profile`) are shown first. The draft total is equivalent to `SUM(filesize)` for its file area; physical duplicates are not deduplicated.

For each area, totals across the current `mdl_files` table are displayed alongside counts and logical bytes **not found in this report's course backup inventory**. These areas are not automatically orphaned, and the report does not estimate exclusively reclaimable space.

## Additional file area explanations

The file-area comparison includes labelled, explained rows for `user/draft` (unfinished editor uploads), `user/backup` (user backup storage), `user/icon` (user profile pictures), `badges/badgeimage` (badge graphics), and component-wide totals `backup/*` and `core_h5p/*` (all file areas owned by their respective components). Existing private/profile user rows remain, followed by additional areas discovered by the database query.

The two component-wide totals are deliberately **not additive** with their individual file-area rows. For each fixed row, total logical bytes correspond to an unfiltered `SUM(filesize)` for the same component and optional filearea filter; directory metadata normally has zero bytes. File counts exclude directory placeholders. Distinct file IDs prevent join fan-out from inventories shared by multiple courses. Rows outside the inventory do not imply safely removable files.

## Categorias consolidadas e registros residuais

A comparação reúne em uma única linha o componente `core_h5p`, mostrando todas as áreas de arquivo realmente existentes, e reúne os componentes `theme_*` em uma linha **Temas**, exibindo até cinco áreas e `etc...` quando houver outras. As quantidades e os bytes representam a soma de todas as áreas agrupadas, mesmo as que não aparecem na lista abreviada.

A tabela **Não encontrado nos inventários dos cursos** exclui, tanto na contagem quanto na paginação, os componentes já classificados na tabela **Outras áreas de arquivos**, como arquivos dos usuários, emblemas, backups, H5P e temas. Assim, a lista residual permanece voltada aos demais componentes que o inventário dos cursos não identificou. A ausência no inventário não comprova que um arquivo seja órfão ou possa ser apagado.

## Visualização de arquivos do inventário

Na página de cada curso, o File X-Ray ignora os registros de diretório com `filename = '.'` e mantém a paginação baseada somente nos arquivos reais. Os links de pré-visualização verificam a associação com o relatório, o curso, o ID do arquivo e seu `contenthash` original.

Imagens comuns, PDFs e arquivos de áudio e vídeo compatíveis são servidos pelo próprio Moodle, com autenticação e verificação de `local/filexray:view`. Tipos potencialmente ativos (HTML, SVG, JavaScript) e outros formatos desconhecidos são entregues como download, não renderizados inline.

Documentos Office (`.doc`, `.docx`, `.xls`, `.xlsx`, `.ppt`, `.pptx`, formatos OpenDocument) podem ser visualizados no **Google Docs Viewer**, somente após uma confirmação específica do administrador. O plugin emite então um endereço HTTPS assinado com HMAC, limitado ao arquivo do relatório e válido por até dez minutos, acessível ao Google sem a sessão Moodle. Durante esse período, qualquer pessoa que obtenha o endereço completo poderá acessar o arquivo; a prévia envolve transferência do documento ao serviço Google e depende de um Moodle acessível pela internet. Não utilize a opção para documentos confidenciais sem autorização institucional. O download autenticado permanece disponível como alternativa. O inventário não envia dados ao Google automaticamente.

## Optional integration with Moodle MCP

When [local_mcp](https://eduardokrausme.github.io/moodle-local_mcp/) is installed and enabled, it automatically discovers `classes/mcp/provider.php` in File X-Ray. Neither plugin needs a hard dependency, a changed `lib.php`, a database migration, or a modification to the MCP plugin.

READ tools: `filexray_list_reports`, `filexray_report_status`, `filexray_list_course_files`, `filexray_get_comparison` and `filexray_list_unmatched_files`. They require `local/filexray:view` in the system context, return only file metadata (no raw data or public download URLs), and paginate course and file lists with a maximum of 100 entries.

WRITE tools: `filexray_create_report`, `filexray_resume_report` and `filexray_delete_report`. They require `local/filexray:run`, and Moodle MCP must obtain explicit preview/confirmation before execution. Creating a report uses the existing asynchronous cron queue and can remove the oldest File X-Ray report according to retention settings; resuming retries failed inventories; deleting removes File X-Ray snapshot records only, **never** the Moodle `files` table or physical files.

Results of unfinished reports are provisional. Records outside course-backup inventories are **not necessarily orphaned** and must not be deleted based on an MCP response. Ensure Moodle cron is working, and refresh Moodle caches after deployment if newly added MCP tools are not discovered immediately.
