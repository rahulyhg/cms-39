<?php namespace Gzero\Core;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class PublishMigrations extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'core:migrations:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish migrations of specified package';

    /**
     * Execute the console command.
     *
     * @return mixed
     * @SuppressWarnings("complexity")
     */
    public function fire()
    {
        $option = $this->option('package');
        if (empty($option)) {
            $this->info('Usage: [--package=vendor/package]');
            return;
        }

        $platformTarget = app_path() . '/database/doctrine-migrations';
        if (!File::exists($platformTarget) && !File::isWritable($platformTarget)) {
            $this->error("\n\n  Path: 'app/database/doctrine-migrations' does not exist or is not writable!\n");
            return;
        }

        if (!File::exists(base_path() . '/vendor/' . $option)) {
            $this->error("\n\n  Package '$option' does not exist!\n");
            return;
        }

        $packageMigrations = base_path() . '/vendor/' . $option . '/src/migrations/doctrine-migrations';
        if (!File::exists($packageMigrations)) {
            $this->error("\n\n  Package '$option' has no path: '/src/migrations/doctrine-migrations'!\n");
            return;
        }

        if (!File::isDirectory($packageMigrations)) {
            $this->error("\n\n  Package '$option' path: '/src/migrations/doctrine-migrations' is not directory!\n");
            return;
        }

        foreach (File::allfiles($packageMigrations) as $file) {
            File::copy(
                $packageMigrations . '/' . $file->getRelativePathname(),
                $platformTarget . '/' . $file->getRelativePathname()
            );
        }
        $this->info("Package '$option' migrations copied to 'app/database/doctrine-migrations'");
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['package', null, InputOption::VALUE_OPTIONAL, 'Specified package.', null],
        ];
    }

}