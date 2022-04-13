<?php

declare(strict_types=1);

namespace Chiron\Discover;

use Chiron\Core\Directories;
use Chiron\Core\Memory;
use Chiron\Filesystem\Filesystem;
use RuntimeException;

//https://github.com/laravel/framework/blob/8.x/src/Illuminate/Foundation/PackageManifest.php
//https://github.com/top-think/framework/blob/6.0/src/think/console/command/ServiceDiscover.php

// TODO : vérifier si les packages sont ordonnés => https://github.com/thecodingmachine/discovery/blob/c5d15800bdd7ddf8390d00eeb9e570142eb69f10/src/PackagesOrderer.php

// TODO : on devrait surement forcer un refresh de ce fichier packages.php lorsque l'utilisateur fait un "composer -dump-update" c'est à dire qu'il faudrait lancer la commande de clean du cache à ce moment là !!!
// TODO : classe à renommer en PackageDiscover::class ????
// TODO : il faudrait pas ajouter un Iterator pour lister tous les packages ????
final class PackageManifest
{
    public const MEMORY_SECTION = 'packages';

    private ?array $cache = null;
    private Directories $directories;
    private Memory $memory;

    public function __construct(Directories $directories, Memory $memory)
    {
        $this->directories = $directories;
        $this->memory = $memory;
    }

    public function config(string $key): array
    {
        $manifest = $this->getManifest();

        $data = [];
        foreach ($manifest as $package => $extra) {
            if (isset($extra[$key])) {
                $data = array_merge($data, (array) $extra[$key]); // TODO : voir si on met en place un helper Arr dans le package support pour simplifier ce type de manipulation.
            }
        }

        return $data;
    }

    private function getManifest(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        if (! $this->memory->exists(self::MEMORY_SECTION)) {
            $this->discover();
        }

        return $this->cache = $this->memory->read(self::MEMORY_SECTION);
    }

    public function discover(): void
    {
        $manifest = [];
        foreach ($this->getPackageExtras() as $package => $extra) {
            $manifest[$package]['providers'] = $extra['providers'] ?? [];
            $manifest[$package]['bootloaders'] = $extra['bootloaders'] ?? [];
        }

        $this->memory->write(self::MEMORY_SECTION, $manifest);
    }

    /**
     * @return iterable<string, array>
     */
    private function getPackageExtras(): iterable
    {
        $packages = [];
        $installedFile = $this->directories->get('@vendor/composer/installed.json');

        if (is_file($installedFile)) {
            $installed = json_decode(file_get_contents($installedFile), true);
            // Compatibility with Composer 2.0
            $packages = $installed['packages'] ?? $installed;
        }

        foreach ($packages as $package) {
            if (! empty($package['extra']['chiron'])) {
                yield $package['name'] => $package['extra']['chiron'];
            }
        }
    }
}
