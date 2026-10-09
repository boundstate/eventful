---
icon: lucide/rocket
---

# Get started

## Installation

You can install this plugin via the in-app Plugin Store or with composer.

=== "Plugin Store"

    Visit the **Plugin Store** screen of your installation’s control panel, then search for **Eventful**.

=== "Composer"

    These instructions assume you are using DDEV, but you can run similar commands in other environments. Open up a terminal and run:

    ```bash
    # Navigate to the project directory:
    cd /path/to/my-project

    # Require the plugin with Composer:
    ddev composer require boundstate/eventful

    # Install the plugin with Craft:
    ddev craft plugin/install eventful
    ```

## Create your first eventful entry

Add an **Event Date** field to an entry type.
Any entries of that type will now be visible in the calendar view, displayed on the **Events** page of the control panel!
