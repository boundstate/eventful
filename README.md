<img src="./src/icon-static.svg" width="100" height="100" />

# Eventful - Make any Craft element an event

## Requirements

This plugin requires Craft CMS 5.10.0 or later, and PHP 8.2 or later.

## Installation

You can install this plugin via the in-app [Plugin Store](#plugin-store) or with [the command line](#composer).

### Plugin Store

Visit the **Plugin Store** screen of your installation’s control panel, then search for **Eventful**.

Click the **Install** button, then check out the [configuration](#configuration) instructions!

### Composer

These instructions assume you are using DDEV, but you can run similar commands in other environments. Open up a terminal and run:

```bash
# Navigate to the project directory:
cd /path/to/my-project

# Require the plugin with Composer:
ddev composer require boundstate/eventful

# Install the plugin with Craft:
ddev craft plugin/install eventful
```

## Configuration

This plugin builds its configuration from [Project config](https://craftcms.com/docs/5.x/system/project-config.html), managed via the **Eventul** &rarr; **Settings** screen in Craft’s control panel.

## Development

Run `ddev start` to serve the demo at http://eventful.ddev.site.

Run `ddev composer phpdoc` to generate the docs.

Run `ddev zensical serve` to serve the docs at http://eventful.ddev.site:8000
