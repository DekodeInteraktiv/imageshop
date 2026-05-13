# Imageshop Media Library

Imageshop is a cloud-based [Digital Asset Management system](http://www.imageshop.org) (image bank /DAM system) that makes it easier than ever to organize, search, share and use your digital files, internally and with the outside world and partners.

Drag & drop uploading and ultra-efficient image tagging enable your files are always available in the DAM system when and where they are needed, in the right format and the best quality. Read more about Imageshop here: http://www.imageshop.org

## WordPress.org assets

The `.wordpress-org/` directory contains assets published to the WordPress.org plugin repository's top-level `assets/` directory (the one at the same level as `trunk`). These are **not** included in the plugin ZIP — they are used solely for the plugin's presentation on WordPress.org.

| File | Description |
|------|-------------|
| `banner-772x250.png` / `.jpg` | Plugin banner (low resolution) |
| `banner-1544x500.png` / `.jpg` | Plugin banner (high resolution / retina) |
| `icon-128x128.png` | Plugin icon (low resolution) |
| `icon-256x256.png` / `.jpg` | Plugin icon (high resolution / retina) |
| `screenshot-1.png` | First screenshot (add more as `screenshot-2.png`, etc.) |

Changes committed to the default branch that only affect files in this directory (and `readme.txt`) will be automatically synced to WordPress.org via the [Deploy to WordPress.org](.github/workflows/wordpress-org-deploy.yml) workflow.
