..  _commands:

========
Commands
========

The **Extension Kickstarter** comes with some various commands to create
individual parts of your own TYPO3 extension. I prefer to execute the
commands in listed ordering. As an example, you can create an extbase domain
model, but as long as there is no TCA table and some columns defined you
can not chose which column you want to get into your model.

All commands come with the same additional argument "extension key". It does
not prevent the extension key question, but it will be provided as default
value. I can not remove that question, because I have to check for correct
extension key spelling before.

make:extension
==============

This is the first command you should execute. It will ask you various questions
about extension key, author, company, email, autoloader and some more. For most
questions I tried to build some good defaults which you can take over by
pressing ENTER.

..  code-block:: bash
    vendor/bin/typo3 make:extension

make:repository
===============

This command will create a new Extbase Repository. You will find the new file
in directory ``Classes/Domain/Repository/*``. If you just enter "blog" as
repository classname it will throw an error, but it asks you for classname
again with correct classname ``BlogRepository`` as default.

..  code-block:: bash
    vendor/bin/typo3 make:repository

make:controller
===============

With one of the first questions this command will ask you to build an Extbase
Controller or a native TYPO3 controller (useful for ``userFunc`` usage).

You will find the new file in directory ``Classes/Controller/*``. If you just
enter "blog" as controller classname it will throw an error, but it asks you
for classname again with correct classname ``BlogController`` as default.

Later on you can add one or more controller action methods. If you just
enter "show", it will fail, but asks you again with "showAction" as default.

Maybe in future I provide a possibility to "inject" repositories which are
available in your extension. That's why "make:repository" is listed this
command.

..  code-block:: bash
    vendor/bin/typo3 make:controller

make:plugin
===========

With one of the first questions this command will ask you to build an Extbase
Plugin or a native TYPO3 plugin (useful for TypoScript usage).

This command will update `ext_localconf.php` and also `tt_content.php`.

Currently, you have to add extbase controller and its actions manually. This
feature is already on my `list <https://github.com/FriendsOfTYPO3/kickstarter/issues/14>`.

..  code-block:: bash
    vendor/bin/typo3 make:plugin

make:table
==========

This command will create the TCA table and its columns.

You will find the new file in directory ``Configuration/TCA/*``. If you just
enter "blog" as table name it will ask you, if you want to create table
``tx_yourext_domain_model_blog`` instead. If you chose "no" here, it will create
table name ``blog``.

Last question is a loop where you can add one or more columns. I supply all
official TCA types as a choice. I use a modified version of the Schema API
to build the SQL definition for ``ext_tables.sql``.

..  code-block:: bash
    vendor/bin/typo3 make:table

make:model
==========

This command will create a new Extbase Domain Model.

You will find the new file in directory ``Classes/Domain/Model/*``. It will
ask you for mapped table name. That's why it was important to execute
"make:table" first. The expected table name is available as default value.

Last question is a loop where you can map one or more TCA columns to model
properties. Extension Kickstarter will automatically lowerCamelCase the
table column for you.

..  code-block:: bash
    vendor/bin/typo3 make:model

make:command
============

This command will create a new Console Command (CLI) class.

See the official documentation for more information on `Console commands (CLI) <https://docs.typo3.org/permalink/t3coreapi:symfony-console-commands>`_.

You will find the new file in directory ``Classes/Command/*``.

..  code-block:: bash

    vendor/bin/typo3 make:command

make:event
==========

This command will create a new Event PHP class.

See the official documentation for more information on `Events / PSR-14 <https://docs.typo3.org/permalink/t3coreapi:eventdispatcher>`_.

You will find the new file in directory ``Classes/Event/*``.

..  code-block:: bash

    vendor/bin/typo3 make:event

make:eventlistener
==================

This command will create a new EventListener PHP class.

See the official documentation for more information on `Event listeners <https://docs.typo3.org/permalink/t3coreapi:eventdispatcherlisteners>`_.

You will find the new file in directory ``Classes/EventListener/*``.

Please update the used Event classname on your own.

..  code-block:: bash

    vendor/bin/typo3 make:eventlistener

make:locallang
==============

This command creates a new or extends an existing XLIFF (`.xlf`) language file
for translations. You can select between standard files like
``locallang.xlf`` or ``locallang_db.xlf``, or provide a custom filename. You can
then interactively add one or more translation terms with their label texts and
trans-unit IDs in a loop.

See the official documentation for more information on `XLIFF / Internationalization <https://docs.typo3.org/permalink/t3coreapi:xliff>`_.

You will find the new or updated files in directory
``Resources/Private/Language/*``.

..  code-block:: bash

    vendor/bin/typo3 make:locallang

make:middleware
===============

This command creates a new PSR-15 Middleware PHP class and registers it in your
extension. It prompts you for the middleware class name, whether to register
it for the frontend or backend request stack, the middleware identifier, and
optional execution ordering constraints (before/after existing middlewares).

See the official documentation for more information on `Middlewares <https://docs.typo3.org/permalink/t3coreapi:request-handling>`_.

You will find the new class in directory ``Classes/Middleware/*`` and the
middleware registration in ``Configuration/RequestMiddlewares.php``.

..  code-block:: bash

    vendor/bin/typo3 make:middleware

make:module
===========

This command registers a new backend module in your extension. It prompts you
for the parent module (e.g., `web`, `site`, or `system`), module identifier,
navigation position (`top` or `bottom`), access permissions (`user`, `admin`,
or `systemMaintainer`), workspace support, route path, title, and description.
You can select the referenced Extbase controller and its actions to execute.

See the official documentation for more information on `Backend modules configuration <https://docs.typo3.org/permalink/t3coreapi:backend-modules-configuration>`_.

You will find the module registration in ``Configuration/Backend/Modules.php``.
Note that this command requires at least one existing Extbase controller
created with ``make:controller``.

..  code-block:: bash

    vendor/bin/typo3 make:module

make:services-yaml
==================

This command creates or updates the ``Configuration/Services.yaml`` file to
configure Symfony Dependency Injection for your extension. It allows you to
quickly create a recommended default configuration (enabling ``autowire`` and
``autoconfigure``, while excluding Extbase models) or configure autowiring,
autoconfiguration, and public service visibility individually.

See the official documentation for more information on `Services.yaml / Dependency Injection <https://docs.typo3.org/permalink/t3coreapi:extension-configuration-services-yaml>`_.

You will find the file in directory ``Configuration/Services.yaml``.

..  code-block:: bash

    vendor/bin/typo3 make:services-yaml

make:site-package
=================

This command scaffolds a basic site package extension structure based on a
provided title.

See the official documentation for more information on `Sitepackages <https://docs.typo3.org/permalink/t3sitepackage:start>`_.

..  note::
    In TYPO3 v14, site handling is transitioning to Site Sets. Creating classic
    site packages for TYPO3 v14 is currently not supported. Use
    ``make:site-set`` instead.

..  code-block:: bash

    vendor/bin/typo3 make:site-package

make:site-set
=============

This command creates a new Site Set definition for your TYPO3 extension. It
asks for the site set identifier, configuration directory path, and a
human-readable label. Once created, the site set can be referenced as a
dependency in site configurations and enriched with site settings definitions.

See the official documentation for more information on `Site Sets <https://docs.typo3.org/permalink/t3coreapi:site-sets>`_.

You will find the new configuration in directory
``Configuration/Sets/<SetName>/config.yaml``.

..  code-block:: bash

    vendor/bin/typo3 make:site-set

make:site-settings-definition
=============================

This command adds typed site settings definitions to an existing Site Set in
your extension. It interactively guides you through defining setting
identifiers, data types (such as `string`, `int`, `bool`, or `color`),
categories, labels, descriptions, and default values.

See the official documentation for more information on `Site settings definitions <https://docs.typo3.org/permalink/t3coreapi:site-settings-definition>`_.

You will find the new definitions in directory
``Configuration/Sets/<SetName>/settings.definitions.yaml``. Note that this
command requires an existing Site Set created with ``make:site-set``.

..  code-block:: bash

    vendor/bin/typo3 make:site-settings-definition

make:testenv
============

This command will add TYPO3 testing environment to your extension.

You will find the new files in directory ``Build/*``.

..  code-block:: bash

    vendor/bin/typo3 make:testenv

make:typeconverter
==================

This command will create a new Extbase TypeConverter PHP class.

You will find the new file in directory ``Classes/Property/TypeConverter/*``.

Currently you have to register this class in "Services.yaml" on your own. But
I have that on my `list <https://github.com/FriendsOfTYPO3/kickstarter/issues/10>`.

..  code-block:: bash

    vendor/bin/typo3 make:typeconverter

make:upgrade
============

This command will create a new Upgrade Wizard PHP class.

See the official documentation for more information on `Upgrade Wizards <https://docs.typo3.org/permalink/t3coreapi:upgrade-wizards>`_.

You will find the new file in directory ``Classes/Upgrade/*``.

..  code-block:: bash

    vendor/bin/typo3 make:upgrade

make:validator
==============

This command creates a new Extbase Validator PHP class for validating either a
single property or an entire domain model. It asks for the validator name, the
validator type (property or model), and lets you select from available domain
models in your extension if validating a model.

See the official documentation for more information on `Extbase domain validators <https://docs.typo3.org/permalink/t3coreapi:extbase-domain-validator>`_.

You will find the new file in directory ``Classes/Validation/Validator/*``.

..  code-block:: bash

    vendor/bin/typo3 make:validator

make:viewhelper
===============

This command creates a new Fluid ViewHelper PHP class. It asks for the
ViewHelper class name, whether it renders children content, and allows adding
arguments with specific data types (such as `string`, `int`, `bool`, `float`,
`array`, `DateTimeInterface`, `FileReference`, `UploadedFile`, `ObjectStorage`,
or custom classes), descriptions, and default values.

See the official documentation for more information on `Fluid custom ViewHelpers <https://docs.typo3.org/permalink/t3coreapi:fluid-custom-viewhelper>`_.

You will find the new file in directory ``Classes/ViewHelpers/*``.

..  code-block:: bash

    vendor/bin/typo3 make:viewhelper

make:applycgl
=============

This command enforces TYPO3 Coding Guidelines (CGL) on your extension code by applying PHP CS Fixer rules. Since it's not PhpParser's responsibility to generate source code in a specific format like PSR, this command provides an additional step to ensure your code follows TYPO3's coding standards.

Requirements
------------

*   TYPO3 must be running in Composer mode
*   PHP function ``exec`` must be available
*   ``php-cs-fixer`` must be installed

The command will process the following directories if they exist in your extension:

*   ``Classes/``
*   ``Configuration/``
*   ``Tests/``

Usage
-----

..  code-block:: bash

    vendor/bin/typo3 make:applycgl

The command will:

#.  Verify that all requirements are met
#.  Locate the php-cs-fixer binary and configuration
#.  Apply TYPO3 coding guidelines to all applicable files in your extension
#.  Display the results of the formatting process

Note: The command uses a predefined configuration file located at ``EXT:kickstarter/Build/cgl/.php-cs-fixer.dist.php``

