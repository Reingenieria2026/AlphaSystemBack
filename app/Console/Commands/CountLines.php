<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

class CountLines extends Command
{
    /**
     * El nombre y firma del comando Artisan.
     *
     * @var string
     */
    protected $signature = 'count:lines 
                            {--path= : Ruta base desde donde contar (por defecto usa base_path())}';

    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Cuenta líneas físicas y lógicas en el backend de Laravel, descartando vendor y carpetas ocultas';

    /**
     * Extensiones de archivo a contar.
     *
     * @var array
     */
    protected $extensions = ['php', 'blade.php', 'js', 'css', 'json'];

    /**
     * Ejecutar el comando.
     */
    public function handle()
    {
        // 1. Determinar la ruta raíz a escanear
        $baseDir = $this->option('path') 
                   ? realpath($this->option('path')) 
                   : base_path();

        if (! is_dir($baseDir)) {
            $this->error("La ruta especificada no existe: {$baseDir}");
            return 1;
        }

        $this->info("Iniciando conteo en: $baseDir\n");

        // Contadores generales
        $totalPhysical  = 0;
        $totalLogical   = 0;
        $filesCounted   = 0;

        // 2. Configurar Symfony Finder para recoger los archivos deseados
        $finder = new Finder();
        $finder
            ->files()
            ->in($baseDir)
            // Incluir únicamente extensiones definidas en $this->extensions
            ->name('/\.(' . implode('|', array_map('preg_quote', $this->extensions)) . ')$/i')
            // Excluir carpeta vendor
            ->exclude('vendor')
            // Excluir ruta node_modules (por si hay carpetas JS en el backend)
            ->exclude('node_modules')
            // Excluir carpetas ocultas (cuyo nombre empiece con punto)
            ->filter(function (\SplFileInfo $file) {
                $parts = explode(DIRECTORY_SEPARATOR, $file->getRelativePath());
                foreach ($parts as $segment) {
                    if (strlen($segment) > 1 && $segment[0] === '.') {
                        return false; // Descarta cualquier carpeta oculta (p.ej. .git, .vscode, etc.)
                    }
                }
                return true;
            });

        // 3. Recorrer cada archivo y sumar sus líneas físicas y lógicas
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($finder as $file) {
            $filesCounted++;

            $contents = @file_get_contents($file->getRealPath());
            if ($contents === false) {
                $this->warn("  [⚠] No se pudo leer: " . $file->getRealPath());
                continue;
            }

            // Contar líneas físicas (todas las líneas sin filtrar)
            $lines = preg_split("/\r\n|\n|\r/", $contents);
            $physicalCount = count($lines);
            $logicalCount  = $this->countLogicalLines($lines, $file->getRelativePathname());

            $totalPhysical  += $physicalCount;
            $totalLogical   += $logicalCount;
        }

        // 4. Mostrar resultados por consola
        $this->line("-------------------------------------");
        $this->info("Archivos analizados: $filesCounted");
        $this->info("Total de líneas físicas: $totalPhysical");
        $this->info("Total de líneas lógicas: $totalLogical");
        $this->line("-------------------------------------");

        return 0;
    }

    /**
     * Dada una lista de líneas de texto y la ruta relativa del archivo,
     * devuelve cuántas de esas líneas cuentan como "lógicas" (no vacías,
     * ni comentarios).
     *
     * @param  array  $lines            Arreglo de líneas (cada elemento sin salto de línea)
     * @param  string $relativePathName Ruta relativa, p.ej "app/Http/Controllers/SomeController.php"
     * @return int
     */
    protected function countLogicalLines(array $lines, string $relativePathName): int
    {
        $logical = 0;
        $insideBlockComment = false;

        // Determinar la extensión real, incluyendo el caso de blade.php
        $extension = pathinfo($relativePathName, PATHINFO_EXTENSION);
        // Si el archivo termina en ".blade.php", lo detectamos manualmente:
        if (substr($relativePathName, -9) === 'blade.php') {
            $extension = 'blade.php';
        }

        foreach ($lines as $rawLine) {
            $trimmed = trim($rawLine);

            // 1. Comentarios multilínea en PHP (/* ... */)
            if (!$insideBlockComment && preg_match('/^\/\*/', $trimmed)) {
                $insideBlockComment = true;
                if (preg_match('/\*\/$/', $trimmed) && strlen($trimmed) > 4) {
                    // Ejemplo: /* comentario */ en una sola línea
                    $insideBlockComment = false;
                }
                continue;
            }

            // 2. Si ya estamos dentro de un bloque /* ... */, buscar cierre */
            if ($insideBlockComment) {
                if (preg_match('/\*\/$/', $trimmed)) {
                    $insideBlockComment = false;
                }
                continue;
            }

            // 3. Comentarios de línea en PHP: // o #
            if ($extension === 'php') {
                if (strpos($trimmed, '//') === 0 || strpos($trimmed, '#') === 0) {
                    continue;
                }
            }

            // 4. Comentarios Blade/PHP en plantillas .blade.php: {{-- ... --}}
            if ($extension === 'blade.php') {
                // Comentario de Blade de una sola línea {{-- --}}
                if (strpos($trimmed, '{{--') === 0 && substr($trimmed, -3) === '--}') {
                    continue;
                }
                // Comentario de bloque {{-- ... --}} multilínea
                if (!$insideBlockComment && strpos($trimmed, '{{--') === 0) {
                    $insideBlockComment = true;
                    if (substr($trimmed, -3) === '--}') {
                        $insideBlockComment = false;
                    }
                    continue;
                }
                if ($insideBlockComment) {
                    if (substr($trimmed, -3) === '--}') {
                        $insideBlockComment = false;
                    }
                    continue;
                }
            }

            // 5. Comentarios en JS: // o /* ... */
            if ($extension === 'js') {
                if (strpos($trimmed, '//') === 0) {
                    continue;
                }
                if (!$insideBlockComment && preg_match('/^\/\*/', $trimmed)) {
                    $insideBlockComment = true;
                    if (preg_match('/\*\/$/', $trimmed) && strlen($trimmed) > 4) {
                        $insideBlockComment = false;
                    }
                    continue;
                }
                if ($insideBlockComment) {
                    if (preg_match('/\*\/$/', $trimmed)) {
                        $insideBlockComment = false;
                    }
                    continue;
                }
            }

            // 6. Comentarios en CSS/SCSS: /* ... */
            if ($extension === 'css' || $extension === 'scss') {
                if (strpos($trimmed, '/*') === 0) {
                    $insideBlockComment = true;
                    if (substr($trimmed, -2) === '*/' && strlen($trimmed) > 4) {
                        $insideBlockComment = false;
                    }
                    continue;
                }
                if ($insideBlockComment) {
                    if (substr($trimmed, -2) === '*/') {
                        $insideBlockComment = false;
                    }
                    continue;
                }
            }

            // 7. Comentarios en JSON: no hay comentarios "oficiales", pero si hay //
            if ($extension === 'json' && strpos($trimmed, '//') === 0) {
                continue;
            }

            // 8. Ignorar líneas vacías
            if ($trimmed === '') {
                continue;
            }

            // Todo lo que llegue aquí NO es comentario ni línea vacía
            $logical++;
        }

        return $logical;
    }
}
