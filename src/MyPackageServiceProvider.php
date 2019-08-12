<?php

namespace LazyElePHPant\MyPackage;

use LazyElePHPant\MyPackage\Models\MyPackage;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Output\ConsoleOutput;

class MyPackageServiceProvider extends ServiceProvider
{
    protected $seeds_path = '/Seeds';
    protected $seeds_path_from_parent = 'LazyElePHPant\\MyPackage\\Seeds\\';

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'mypackage');

        $this->app->make('Illuminate\Database\Eloquent\Factory')->load(__DIR__ . 'database/factories');

        // if ($this->app->runningInConsole()) {
        //     if ($this->isConsoleCommandContains([ 'db:seed', '--seed' ], [ '--class', 'help', '-h' ])) {
        //         $this->addSeedsAfterConsoleCommandFinished();
        //     }
        // }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerEloquentFactoriesFrom(__DIR__.'/database/factories');
    }

    /**
     * Get a value that indicates whether the current command in console
     * contains a string in the specified $fields.
     *
     * @param string|array $contain_options
     * @param string|array $exclude_options
     *
     * @return bool
     */
    protected function isConsoleCommandContains($contain_options, $exclude_options = null) : bool
    {
        $args = Request::server('argv', null);
        if (is_array($args)) {
            $command = implode(' ', $args);
            if (str_contains($command, $contain_options) && ($exclude_options == null || !str_contains($command, $exclude_options))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Add seeds from the $seed_path after the current command in console finished.
     */
    protected function addSeedsAfterConsoleCommandFinished()
    {
        Event::listen(CommandFinished::class, function (CommandFinished $event) {
            if ($event->output instanceof ConsoleOutput) {
                $this->addSeedsFrom(__DIR__ . $this->seeds_path);
            }
        });
    }

    /**
     * Register seeds.
     *
     * @param string  $seeds_path
     * @return void
     */
    protected function addSeedsFrom($seeds_path)
    {
        $file_names = glob( $seeds_path . '/*.php');

        foreach ($file_names as $filename) {
            $classes = $this->getClassesFromFile($filename);

            foreach ($classes as $class) {
                Artisan::call('db:seed', [ '--class' => $class, '--force' => '' ]);
            }
        }
    }

    /**
     * Register factories.
     *
     * @param  string  $path
     * @return void
     */
    protected function registerEloquentFactoriesFrom($path)
    {
        $this->app->make(EloquentFactory::class)->load($path);
    }

    /**
     * Get full class names declared in the specified file.
     *
     * @param string $filename
     * @return array an array of class names.
     */
    private function getClassesFromFile(string $filename) : array
    {
        $namespace = "";
        $lines = file($filename);
        $namespaceLines = preg_grep('/^namespace /', $lines);

        if (is_array($namespaceLines)) {
            $namespaceLine = array_shift($namespaceLines);
            $match = array();
            preg_match('/^namespace (.*);$/', $namespaceLine, $match);
            $namespace = array_pop($match);
        }

        $classes = array();
        $php_code = file_get_contents($filename);
        $tokens = token_get_all($php_code);
        $count = count($tokens);

        for ($i = 2; $i < $count; $i++) {
            if ($tokens[$i - 2][0] == T_CLASS && $tokens[$i - 1][0] == T_WHITESPACE && $tokens[$i][0] == T_STRING) {
                $class_name = $tokens[$i][1];

                if ($namespace !== "") {
                    $classes[] = $this->seeds_path_from_parent . $class_name;
                } else {
                    $classes[] = $class_name;
                }
            }
        }

        return $classes;
    }
}
