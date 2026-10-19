<?php

/*
 * Helpers for start-local.bat (run from the project folder).
 *
 *   php -n scripts/start-local.php php-ini   write php.ini next to this PHP, with the extensions Himma needs
 *   php scripts/start-local.php deps-current  exit 0 when vendor/ was installed from the current composer.lock
 *   php scripts/start-local.php drop-git      delete vendor/ when Composer put packages there with git
 */

const STAMP = 'vendor/.installed-lock';

switch ($argv[1] ?? '') {
    case 'php-ini':
        $dir = dirname(PHP_BINARY);
        $lines = ['extension_dir = "' . $dir . DIRECTORY_SEPARATOR . 'ext"'];
        foreach (['curl', 'fileinfo', 'mbstring', 'openssl', 'pdo_sqlite', 'sqlite3', 'zip'] as $extension) {
            $lines[] = 'extension = ' . $extension;
        }
        $lines[] = 'upload_max_filesize = 8M';
        $lines[] = 'post_max_size = 40M';
        exit(file_put_contents($dir . DIRECTORY_SEPARATOR . 'php.ini', implode(PHP_EOL, $lines) . PHP_EOL) ? 0 : 1);

    case 'deps-current':
        exit(is_file('vendor/autoload.php') && is_file(STAMP)
            && file_get_contents('composer.lock') === file_get_contents(STAMP) ? 0 : 1);

    case 'drop-git':
        // Composer keeps updating a package the way it was installed. Packages that came with git
        // are downloaded again as zip files instead: some can't be checked out on Windows at all
        // (phpdotenv 5.7 has a test file named "nul"), and the zip files leave tests out.
        $installed = json_decode((string) @file_get_contents('vendor/composer/installed.json'), true);
        $fromGit = array_filter($installed['packages'] ?? [], fn ($package) => ($package['installation-source'] ?? '') === 'source');
        if (!$fromGit) {
            exit(0);
        }

        echo ' Some packages were installed with git; downloading them again...', PHP_EOL;
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator('vendor', FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $path = $item->getPathname();
            @chmod($path, 0777); // git makes its object files read-only
            $item->isDir() ? (@rmdir($path) || @unlink($path)) : @unlink($path);
        }
        @rmdir('vendor');
        if (is_dir('vendor')) {
            echo ' Could not delete the "vendor" folder. Close other Himma windows and editors, then try again.', PHP_EOL;
            exit(1);
        }
        exit(0);
}

fwrite(STDERR, "Unknown command\n");
exit(2);
