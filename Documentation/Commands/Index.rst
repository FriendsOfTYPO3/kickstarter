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

This command will create a new  Console Command (CLI) class.

See the official documentation for more information on  `Console commands (CLI) <https://docs.typo3.org/permalink/t3coreapi:symfony-console-commands>`_.

You will find the new file in directory ``Classes/Command/*``.

..  code-block:: bash
    vendor/bin/typo3 make:command

make:event
==========

This command will create a new Event PHP class.

You will find the new file in directory ``Classes/Event/*``.

..  code-block:: bash
    vendor/bin/typo3 make:event

make:eventlistener
==================

This command will create a new EventListener PHP class.

You will find the new file in directory ``Classes/EventListener/*``.

Please update the used Event classname on your own.

..  code-block:: bash
    vendor/bin/typo3 make:eventlistener

make:typeconverter
==================

This command will create a new Extbase TypeConverter PHP class.

You will find the new file in directory ``Classes/Property/TypeConverter/*``.

Currently you have to register this class in "Services.yaml" on your own. But
I have that on my `list <https://github.com/FriendsOfTYPO3/kickstarter/issues/10>`.

..  code-block:: bash
    vendor/bin/typo3 make:typeconverter

make:testenv
============

This command will add TYPO3 testing environment to your extension.

You will find the new files in directory ``Build/*``.

..  code-block:: bash
    vendor/bin/typo3 make:testenv

make:upgrade
============

This command will create a new Upgrade Wizard PHP class.

You will find the new file in directory ``Classes/Upgrade/*``.

..  code-block:: bash
    vendor/bin/typo3 make:upgrade

make:applycgl
=============

This command enforces TYPO3 Coding Guidelines (CGL) on your extension code by applying PHP CS Fixer rules. Since it's not PhpParser's responsibility to generate source code in a specific format like PSR, this command provides an additional step to ensure your code follows TYPO3's coding standards.

Requirements
-----------

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
