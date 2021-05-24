<?php

declare(strict_types=1);

namespace Chiron\Discover;

use Chiron\Core\Directories;
use Chiron\Filesystem\Filesystem;
use RuntimeException;

//https://github.com/top-think/framework/blob/6.0/src/think/console/command/ServiceDiscover.php

// TODO : vérifier si les packages sont ordonnés => https://github.com/thecodingmachine/discovery/blob/c5d15800bdd7ddf8390d00eeb9e570142eb69f10/src/PackagesOrderer.php

// TODO : on devrait surement forcer un refresh de ce fichier packages.php lorsque l'utilisateur fait un "composer -dump-update" c'est à dire qu'il faudrait lancer la commande de clean du cache à ce moment là !!!
// TODO : on devrait aussi gérer les "inflectors" (c'est les mutations) à ajouter au container.
// TODO : classe à renommer en PackageDiscover::class ????
// TODO : il faudrait pas ajouter un Iterator pour lister tous les packages ????
final class PackageManifest
{
    /** @var array */
    private $manifest;

    /** @var string */
    private $cacheDir;

    /** @var string */
    private $vendorDir;

    /** @var string */
    private $manifestPath;

    /** @var Filesystem */
    private $filesystem;

    public function __construct(Filesystem $filesystem, Directories $directories)
    {
        $this->filesystem = $filesystem;

        $this->vendorDir = $directories->get('@vendor');
        $this->cacheDir = $directories->get('@cache');
        $this->manifestPath = $this->cacheDir . 'packages.json';
    }

    public function discover()
    {
        $packages = [];

        if (file_exists($path = $this->vendorDir . 'composer/installed.json')) {
            $packages = json_decode(file_get_contents($path), true);
            // Compatibility with Composer 2.0
            if (isset($packages['packages'])) {
                $packages = $packages['packages'];
            }
        }

        $manifest = [];

        foreach ($packages as $package) {
            if (! empty($package['extra']['chiron'])) {
                $packageInfo = $package['extra']['chiron'];

                $manifest[$package['name']]['version'] = $package['version'];

                // TODO : améliorer le code en le factorisant, il y a 4 fois le même bout de code pour un nom de balise différent !!!!
                if (! empty($packageInfo['providers'])) {
                    $manifest[$package['name']]['providers'] = $packageInfo['providers'];
                }

                if (! empty($packageInfo['bootloaders'])) {
                    $manifest[$package['name']]['bootloaders'] = $packageInfo['bootloaders'];
                }
            }
        }

        $this->write($manifest);
    }

    // TODO : améliorer le code en utilisant le fichier filesystem pour écrire le contenu du fichier + effectuer le test du répertoire "writable".
    private function write(array $manifest): void
    {

        // TODO : il faudrait surement faire un early exit si le tableau à écrire est vide !!!!


        // TODO : à virer
        // Ensure the directory exists and is writable.
        if (! is_writable($this->cacheDir)) {
            // TODO : utiliser un sprintf()
            // TODO : lever une ApplicationException::class ou une ImproperlyConfiguredException ????
            throw new RuntimeException('The ' . $this->cacheDir . ' directory must be present and writable.');
        }

        // TODO : on devrait pas enregistrer le contenu du fichier dans un .json, ca serait plus simple à ecrire/lire ????
        // TODO : utilise $this->files->write pour écrire le fichier, non ?????
        //file_put_contents($this->manifestPath, '<?php return ' . var_export($manifest, true) . ';');

        $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); // TODO : vérifier l'utilité du unescaped_slashes car on a uniquement des antislash dans le nom des classes php qui sont stochées dans le fichier composer.jon !!!!
        file_put_contents($this->manifestPath, $json);
    }

    public function getProviders(): array
    {
        return $this->getMeta('providers');
    }

    public function getBootloaders(): array
    {
        return $this->getMeta('bootloaders');
    }

    private function getMeta(string $key): array
    {
        $manifest = $this->getManifest();
        $data = [];

        foreach ($manifest as $package => $meta) {
            if (isset($meta[$key])) {
                $data = array_merge($data, (array) $meta[$key]);
            }
        }

        return $data;
    }

    /**
     * Get the current package manifest.
     *
     * @return array
     */
    public function getManifest(): array
    {
        if (! is_null($this->manifest)) {
            return $this->manifest;
        }

        if (! file_exists($this->manifestPath)) {
            $this->discover();
        }

        // TODO : améliorer le code : faire directement un require, et utiliser la méthode $this->files->exists() plutot que la méthode file_exists(). Virer de la classe FileSystem la méthode getRequire qui ne sera plus utilisée.
        // TODO : on devrait pas enregistrer le contenu du fichier dans un .json, ca serait plus simple à ecrire/lire ????
        /*
        return $this->manifest = file_exists($this->manifestPath) ?
            $this->filesystem->getRequire($this->manifestPath) : [];*/

        return $this->manifest = file_exists($this->manifestPath) ?
            json_decode(file_get_contents($this->manifestPath), true) : [];
    }
}
