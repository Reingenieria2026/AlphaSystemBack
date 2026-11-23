<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class ListModelsRelations extends Command
{
    protected $signature = 'models:relations';
    protected $description = 'Lista todos los modelos y sus relaciones';

    public function handle()
    {
        $modelPath = app_path('Models');
        $files = File::allFiles($modelPath);

        foreach ($files as $file) {
            $namespace = 'App\\Models\\';
            $class = $namespace . pathinfo($file->getFilename(), PATHINFO_FILENAME);

            if (class_exists($class)) {
                $this->info("📌 Modelo: {$class}");

                $reflection = new ReflectionClass($class);
                foreach ($reflection->getMethods() as $method) {
                    $body = file($file->getPathname());
                    $line = $method->getStartLine() - 1;
                    $code = $body[$line] ?? '';

                    if (str_contains($code, 'has') || str_contains($code, 'belongs')) {
                        $this->line("   ➝ {$method->getName()}()");
                    }
                }
                $this->newLine();
            }
        }
    }
}
