# smpt

This repository tracks only the custom code layer of the site:

- `wp-content/mu-plugins/`
- `wp-content/themes/generatepress_child/`

It intentionally does not track:

- WordPress core
- regular plugins
- uploads and media
- cache, logs, and backups
- translations
- `wp-config.php`

This means a fresh clone is not a full runnable WordPress site by itself. It is the custom site code meant to be deployed into an existing WordPress install.
