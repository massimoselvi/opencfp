<?php

declare(strict_types=1);

/**
 * Copyright (c) 2013-2017 OpenCFP
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/opencfp/opencfp
 */

namespace OpenCFP\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ClearCacheCommand extends Command
{
    /**
     * @var string[]
     */
    private $paths;

    public function __construct(array $paths)
    {
        parent::__construct('cache:clear');

        $this->paths = $paths;
    }

    protected function configure()
    {
        $this->setDescription('Clears the caches');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle(
            $input,
            $output
        );

        $io->title('OpenCFP');

        $io->section('Clearing caches');

        \array_walk($this->paths, function ($path) use ($io) {
            $count = $this->deleteFilesAndDirectoriesIn($path);

            $io->writeln(\sprintf(
                '  * %s (%d files and directories)',
                $path,
                $count
            ));
        });

        $io->success('Cleared caches.');
    }

    private function deleteFilesAndDirectoriesIn(string $path): int
    {
        $count = 0;

        try {
            $filesAndDirectories = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $path,
                    \RecursiveDirectoryIterator::SKIP_DOTS
                ),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
        } catch (\UnexpectedValueException $e) {
            return 0;
        }

        foreach ($filesAndDirectories as $fileInfo) {
            if ($this->delete($fileInfo)) {
                ++$count;
            }
        }

        return $count;
    }

    private function delete(\SplFileInfo $fileInfo): bool
    {
        $filePath = (string) $fileInfo;

        /** @var \SplFileInfo $fileInfo */
        if ($fileInfo->isDir()) {
            return \rmdir($filePath);
        }

        if ($fileInfo->isFile()) {
            return \unlink($filePath);
        }

        return false;
    }
}
