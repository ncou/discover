<?php

declare(strict_types=1);

namespace Chiron\Discover\Command;

use Chiron\Core\Command\AbstractCommand;
use Chiron\Core\Memory;
use Chiron\Discover\PackageManifest;

//https://github.com/laravel/framework/blob/7.x/src/Illuminate/Foundation/Console/PackageDiscoverCommand.php

// TODO : passer les méthodes "perform" en protected pour chaque classe de type "Command"
class PackageDiscoverCommand extends AbstractCommand
{
    protected static $defaultName = 'package:discover';

    protected function configure()
    {
        $this->setDescription('Rebuild the cached package manifest.');
    }

    public function perform(PackageManifest $manifest, Memory $memory): int
    {
        // Force to generate the manifest.
        $manifest->discover();
        // Read the 'fresh' cached package manifest.
        $packages = $memory->read(PackageManifest::MEMORY_SECTION);

        foreach ($packages as $package => $extra) {
            $this->line(sprintf(" - Discovering <info>%s</info>", $package));

            if ($this->isVerbose()) {
                // TODO : utiliser un ->write($array) suivi d'un ->newline() ????
                $this->listing($extra['providers'] ?? []);
                $this->listing($extra['bootloaders'] ?? []);

                //$this->listing2($extra['bootloaders'] ?? [], 'fg=yellow');
                //$this->listing2($extra['providers'] ?? [], 'fg=yellow');
                $this->newline();
            }
        }

        $this->success('Package manifest generated successfully.');

        return self::SUCCESS;
    }
}
