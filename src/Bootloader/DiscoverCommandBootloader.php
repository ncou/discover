<?php

declare(strict_types=1);

namespace Chiron\Discover\Bootloader;

use Chiron\Core\Container\Bootloader\AbstractBootloader;
use Chiron\Console\Console;
use Chiron\Discover\Command\PackageDiscoverCommand;

final class DiscoverCommandBootloader extends AbstractBootloader
{
    public function boot(Console $console): void
    {
        $console->addCommand(PackageDiscoverCommand::getDefaultName(), PackageDiscoverCommand::class);
    }
}
