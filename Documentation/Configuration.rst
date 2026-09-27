:navigation-title: Configuration

..  _configuration:

=============
Configuration
=============

In extension settings (Install Tool) you will find just one option:

exportDirectory
===============

If empty, your created extensions will be exported to
`[TYPO3 temp. dir]/kickstarter/*` by default. I prefer to set this
directory to `packages/`. That's the default import directory for local
available packages.

This setting can not be set via CLI usage.

activateModule
==============

:t3ext:`kickstarter` comes with a TYPO3 backend module to kickstart your
extensions visually. Is this feature is not full featured and beta, this
module is hidden by default. If you are really sure you want to experiment
a bit, feel free to activate the module.
