# File X-Ray documentation

This folder contains the static bilingual documentation site for the Moodle `local_filexray` plugin.

The visual system, responsive styling, theme toggle and language switcher were adapted from the `docs` folder in [EduardoKrausME/moodle-local_mcp](https://github.com/EduardoKrausME/moodle-local_mcp/tree/master/docs). Content is specific to the File X-Ray implementation.

The root `index.html` redirects visitors to `pt_br/` or `en/` based on browser language. Direct links to either locale also work. The `privacy.html` redirect follows the same convention. No build process or third-party frontend dependencies are required.

To publish this documentation with GitHub Pages, configure the repository Pages source to **Deploy from a branch**, select **master** and **/docs**. This repository setting is separate from the documentation files themselves.

The site includes no Moodle data or screenshots from an actual Moodle installation. The dashboard preview is illustrative.
