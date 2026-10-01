<?php

namespace Deployer;

require 'recipe/laravel.php';

/*
 * Tracker on tracker.sliceer.com: `vendor/bin/dep deploy` from main.
 *
 * Tracker does not use the server's PHP. It runs on its own FrankenPHP binary
 * (PHP 8.5) as the `tracker` user, under tracker.service, and shares the host
 * with Sliceer and Tennis, whose PHP and configuration a Tracker deploy must
 * never touch. So:
 *
 *  - Composer runs as `sasa` through Tracker's binary, without scripts, because
 *    `sasa` cannot read shared/.env.
 *  - Every artisan command runs as `tracker` through a NOPASSWD sudo rule, and
 *    so does the service restart. Both rules, the tracker-deploy group and the
 *    service's write paths are a one-time root setup; see DEPLOYMENT.md on the
 *    server.
 *  - Assets are built on the server with Node 22 from nvm; the system Node is
 *    too old for Vite.
 */

set('application', 'tracker');
set('repository', 'git@github.com:sasafister/tracker.git');
set('keep_releases', 5);
set('use_relative_symlinks', false);

set('tracker_php', '{{deploy_path}}/bin/frankenphp php-cli');
set('tracker_env', 'env PHPRC={{deploy_path}}/php.ini');

// Artisan, as `tracker`, which owns shared/ and the database credentials.
set('bin/php', 'sudo -n -u tracker {{tracker_env}} {{tracker_php}}');

// Composer, as `sasa`, which owns the release directory.
set('bin/composer', '{{tracker_env}} {{tracker_php}} /usr/bin/composer');
set('composer_options', implode(' ', [
    '--verbose',
    '--prefer-dist',
    '--no-progress',
    '--no-interaction',
    '--no-dev',
    '--optimize-autoloader',
    '--no-scripts',
]));

set('nvm', 'export NVM_DIR="$HOME/.nvm" && . "$NVM_DIR/nvm.sh" && nvm use 22 >/dev/null');

// The Laravel recipe already shares .env and storage/.

// `tracker` writes the framework caches and the public/storage link; it reaches
// the release through the tracker-deploy group (only `sasa` and `tracker`), so
// those two are made group-writable. Never www-data: Sliceer's releases are
// writable by that group, and Tracker must not be able to touch them.
// storage/ itself is shared and already belongs to `tracker`.
set('writable_dirs', ['bootstrap/cache', 'public']);
set('writable_mode', 'chmod');
set('writable_chmod_mode', 'g+w');
set('writable_recursive', true);

// The server Sliceer and Tennis share. Spelled out rather than taken from an
// SSH config alias, so the deploy works from any machine with the key.
host('production')
    ->setHostname('116.203.99.40')
    ->setRemoteUser('sasa')
    ->setPort(17000)
    ->set('deploy_path', '/var/www/html/tracker')
    ->set('branch', 'main');

desc('Discovers packages, which Composer skipped');
task('artisan:package:discover', artisan('package:discover'));

desc('Builds the frontend with Node 22');
task('assets:build', function () {
    within('{{release_path}}', function () {
        run('{{nvm}} && yarn install --frozen-lockfile --non-interactive');
        run('{{nvm}} && yarn build');
        run('rm -rf node_modules');
    });
});

desc('Restarts Tracker, and only Tracker');
task('tracker:restart', function () {
    run('sudo -n systemctl restart tracker.service');
    run('systemctl is-active tracker.service');
});

after('deploy:vendors', 'artisan:package:discover');

// Before .env becomes the link to shared/.env, which `sasa` cannot read and
// Vite opens on every build. The frontend reads no VITE_ variables, so the
// release's copy of .env.example is all the build needs.
before('deploy:shared', 'assets:build');
after('deploy:symlink', 'tracker:restart');
after('deploy:failed', 'deploy:unlock');
