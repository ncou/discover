<?php

declare(strict_types=1);

namespace Chiron\Discover\Command;

use Chiron\Core\Command\AbstractCommand;
use Chiron\Discover\PackageManifest;
use Chiron\Discover\Exception\DiscoverException;

//https://github.com/laravel/framework/blob/7.x/src/Illuminate/Foundation/Console/PackageDiscoverCommand.php

// TODO : passer les méthodes "perform" en protected pour chaque classe de type "Command"
class PackageDiscoverCommand extends AbstractCommand
{
    protected static $defaultName = 'package:discover';

    protected function configure()
    {
        $this->setDescription('Rebuild the cached package manifest.');
    }

    public function perform(PackageManifest $manifest): int
    {
        try {
            $manifest->discover();
        } catch(DiscoverException $e){
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        // TODO : si on est en mode verbose on pourrait afficher plus d'infos du manifest, au lieu de juste afficher le package on pourrait afficher le détail (cad un bootloader, un provider, etc...)
        // TODO : utiliser un iterator dans la classe PackageManifest ????
        /*
        foreach ($manifest->getPackages() as $package) {
            $this->line("Discovered Package: <info>{$package}</info>");
        }*/

        foreach ($manifest->getManifest() as $package => $extra) {
            $this->line(sprintf(" - Discovering <info>%s</info> (<comment>%s</comment>)", $package, $extra['version']));

            if ($this->isVerbose()) {
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
