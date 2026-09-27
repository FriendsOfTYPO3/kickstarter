:navigation-title: FAQ

..  _faq:

===
FAQ
===

..  accordion::
    :name: faq

    ..  accordion-item:: Can I extend other/foreign extensions?
        :name: extend-extensions
        :header-level: 2
        :show:

        Yes, no, maybe. There are some commands available which completely
        rewrite the existing PHP files. I have no clue, if this will work for
        any PHP class. Try it. Fail. Revert it ;-) But don't tell me that it is
        not working. Move the foreign extension into the configured export
        directory. I'm pretty sure for commands which just adds complete new
        files you will not get any error, but please be careful while adding
        further controller action methods or getter/setters for foreign domain
        model.

    ..  accordion-item:: Where is my new TYPO3 extension?
        :name: extension-export-location
        :header-level: 2

        See chapter :ref:`configuration`. By default a new extension will be
        exported to ``[TYPO3 Temp. Dir]/kickstarter/*``.

    ..  accordion-item:: How to prevent overwriting existing methods?
        :name: prevent-overwrite-code
        :header-level: 2

        Sure, extension kickstarter will completely rewrite full PHP file,
        but in most cases it keeps existing code as it. It only adds new
        functionality and will never delete a method or modify existing methods.
        But yes, it may happen that some spaces or empty lines will be removed
        after rewriting a PHP file.
        Extension Kickstarter does not have any kind of configuration file
        where you can define what have to be overwritten or not like done
        in :t3ext:`extension_builder`. I prefer to backup your extension before
        you start modifying it with extension kickstarter.
