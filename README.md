# TYPO3 Extension Kickstarter

`kickstarter` is a TYPO3 extension that simplifies and accelerates the creation of new TYPO3 extensions by automating file generation, controller setup, and plugin registration through easy-to-use CLI commands. With a few CLI commands, it generates essential files like `LICENSE`, `composer.json`, and more. Whether starting from scratch or extending existing functionality, `kickstarter` helps streamline development.

---

## Features

- **Generate New TYPO3 Extensions**
  Automates the creation of TYPO3 extensions with pre-configured files.

- **CLI Commands for Common Tasks**
    - Create a new extension
    - Generate controllers (extbase and native)
    - Register plugins (extbase and native)
    - Extend existing controllers with additional actions.
    - Create basic TCA for tables
    - Create extbase domain model
    - Create extbase domain repository
    - Create extbase type converters
    - Create event
    - Create event listener
    - Create testing environment

---

## Installation

Install the extension using Composer:
```bash
composer req --dev friendsoftypo3/kickstarter
```

For DDEV:
```bash
ddev composer req --dev friendsoftypo3/kickstarter
```

## Configuration

By default, new extensions are created in `typo3temp/kickstarter/[your_ext_key]`.

To change this location:

- Navigate to **TYPO3 Backend → Admin Tools → Settings → Configure Extensions → `kickstarter`**.
- For Composer-based installations, the recommended directory is `packages/`.

## Usage

After installation, you can use the following CLI commands:

### Create a New Extension

```bash
vendor/bin/typo3 make:extension
```

DDEV:

```bash
ddev typo3 make:extension
```

**Warning:** This command will ask you to delete an existing extension in the target directory before creating a new one.

### Generate a Controller

```bash
vendor/bin/typo3 make:controller
```

DDEV:

```bash
ddev typo3 make:controller
```

You will be prompted to select controller actions. Existing actions will not be overwritten.

### Create and Register an Extbase Plugin

```bash
vendor/bin/typo3 make:plugin
```
DDEV:

```bash
ddev typo3 make:plugin
```

### Create a TCA table

```bash
vendor/bin/typo3 make:table
```
DDEV:

```bash
ddev typo3 make:table
```

### Add testing environment

```bash
vendor/bin/typo3 make:testenv
```
DDEV:

```bash
ddev typo3 make:testenv
```

### Create an Event Listener

```bash
vendor/bin/typo3 make:eventlistener
```
DDEV:

```bash
ddev typo3 make:eventlistener
```

### Create an Extbase Model

```bash
vendor/bin/typo3 make:model
```
DDEV:

```bash
ddev typo3 make:model
```

### Create an Extbase Repository

```bash
vendor/bin/typo3 make:repository
```
DDEV:

```bash
ddev typo3 make:repository
```

### Create an Extbase Type Converter

```bash
vendor/bin/typo3 make:typeconverter
```
DDEV:

```bash
ddev typo3 make:typeconverter
```

### Create an Upgrade Wizard

```bash
vendor/bin/typo3 make:upgrade
```
DDEV:

```bash
ddev typo3 make:upgrade
```

---

## Why Use kickstarter

Creating TYPO3 extensions often involves repetitive tasks like setting up configuration files and controllers. `kickstarter` automates these steps, saving time and ensuring a consistent structure.

---

## Special Thanks

Special thanks to [LiteGraph](https://github.com/jagenjo/litegraph.js) for their awesome JS UI library we are using in TYPO3 backend.

---

## Contributions & Feedback
Feedback and contributions are always welcome! Feel free to share your ideas or report issues in the [GitHub repository](https://github.com/friendsoftypo3/kickstarter).

---
