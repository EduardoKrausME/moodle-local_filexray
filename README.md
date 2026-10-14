# File X-Ray for Moodle

File X-Ray audits which records in Moodle's `files` table are selected by each course's native backup process. **No MBZ is generated and no physical file content is copied or deleted.**

## Documentation

The bilingual documentation site uses the same visual layout as [Moodle MCP documentation](https://github.com/EduardoKrausME/moodle-local_mcp/tree/master/docs), adapted for File X-Ray. Browse [Portuguese](docs/pt_br/index.html) or [English](docs/en/index.html) pages in this repository, or publish the `docs/` directory using GitHub Pages.

The source includes a public overview, installation, native backup inspection, report processing, per-course comparison, file-area explanations, security limits and privacy information. It does not imply that files outside course inventories are safe to delete.

## Workflow

1. Open **Site administration → Reports → File X-Ray** and select **Generate report**.
2. File X-Ray snapshots each ordinary Moodle course into one row of `local_filexray_course` and creates a parent `local_filexray_report`.
3. An ad-hoc cron task inventories **one course at a time**. A report-level lock prevents parallel work on the same report and the worker queues the next course after completing the previous one.
4. The browser polls progress over Moodle's authenticated `core/ajax`, displaying progress, errors and the 50 courses on the selected page. Closing the tab does not stop cron.
5. Each course's final backup `filefinal` references are captured before Moodle's `fileslist` step. This skips the physical copy, MBZ zip and destination-storage steps.
6. **Compare with mdl_files** lists file records not encountered by this inventory. These are **not automatically orphaned**: system/user files, other Moodle features and plugins without complete backup support can reference them.

## Architecture

* `local_filexray_report` — snapshot metadata and status.
* `local_filexray_course` — one course job per report, with timestamps/errors/counts.
* `local_filexray_file` — file record ID, content hash and related metadata for a given course and report.
* `classes/backup_inventory.php` — runs Moodle's general backup plan through the final annotation migration, but deliberately stops **before** `backup_final_files_structure_step` (`fileslist`), which would otherwise copy files.
* `classes/task/process_course.php` — one task per course, chaining the next job on cron.
* `classes/report_manager.php` — immutable course snapshot and paginated live progress.

**Requirements** — Moodle 4.5 or later, PHP compatible with the Moodle instance, working Moodle cron, site management permissions.

## Security and limitations

Only users with `local/filexray:view` can see inventories; `local/filexray:run` is needed to create and resume reports. Both capabilities are system-context capabilities. The comparison is read-only and never deletes a file.

This implementation inventories the file records that the ordinary Moodle backup flow can resolve. It does not claim every record outside that set is unreferenced or safe to delete; neither does it scan `moodledata/filedir` for physical orphan hashes. A file can be shared by several records with the same `contenthash` and therefore file record counts are not equivalent to occupied disk bytes.

A failed course makes the entire comparison provisional. Reports keep the original list of courses, even if courses are removed after the report was created. A report whose cron worker died can be resumed by selecting **Resume pending courses**; a still-running row is reclaimed only after two hours and after obtaining the exclusive report lock.

For a very large site, calculate disk occupancy separately by **distinct contenthash**, not by summing logical file-record sizes. Do not delete anything on the basis of this report alone.

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
