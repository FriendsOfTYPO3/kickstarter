<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Traits;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The questions describing a site set. They are asked by make:site-set for a set added to an
 * existing extension, and by make:site-package for the set a new site package is built around.
 */
trait SiteSetQuestionsTrait
{
    private function askForSiteSetIdentifier(SymfonyStyle $io, string $composerName): string
    {
        // Build regex to validate input
        $pattern = '~^' . preg_quote($composerName, '~') . '(?:-[a-z0-9]+)?$~';

        do {
            $identifier = $io->ask(sprintf(
                'Please enter the site set identifier (must be "%s" or "%s-<suffix>")',
                $composerName,
                $composerName
            ), $composerName);

            if ($identifier === null) {
                $io->error('The identifier cannot be empty.');
                continue;
            }

            if (in_array(preg_match($pattern, $identifier), [0, false], true)) {
                $io->error(sprintf(
                    'Invalid identifier. It must be "%s" or start with "%s-" followed by lowercase letters and numbers.',
                    $composerName,
                    $composerName
                ));
                continue;
            }

            // Valid identifier
            return $identifier;

        } while (true);
    }

    private function askForSiteSetPath(SymfonyStyle $io, string $identifier, ?string $default = null): string
    {
        if ($default === null) {
            $default = '';
            if (str_contains($identifier, '/')) {
                [, $default] = explode('/', $identifier, 2);
            }
        }

        do {
            $siteSetPath = $io->ask('Please enter the site set directory name (no slashes)', $default);

            if ($siteSetPath === null) {
                $io->error('The site set path cannot be empty.');
                continue;
            }

            if (preg_match('~[\\\\/]~', $siteSetPath)) {
                $io->error('The site set path must not contain slashes.');
                continue;
            }

            // Valid input
            return $siteSetPath;

        } while (true);
    }

    private function askForSiteSetLabel(SymfonyStyle $io, string $siteSetPath, ?string $default = null): string
    {
        // Generate default label from site set path
        $default ??= $siteSetPath !== ''
            ? ucwords((string)preg_replace('/[^a-z0-9]+/i', ' ', $siteSetPath))
            : '';

        do {
            $label = $io->ask(
                'Please enter the site label (max 255 chars)',
                $default
            );

            if ($label === null) {
                $io->error('The site label cannot be empty.');
                continue;
            }

            if (strlen($label) > 255) {
                $io->error('The site label must not exceed 255 characters.');
                continue;
            }

            return $label;
        } while (true);
    }
}
