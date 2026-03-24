<?php

namespace Caio\LaravelModuleGenerator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'make:module {name : Nome do módulo}
                            {--type=api : Tipo do módulo (api ou web)}
                            {--force : Sobrescrever arquivos do módulo existente}';

    /**
     * @var string
     */
    protected $description = 'Cria um novo módulo com estrutura completa (API ou Web)';

    protected string $moduleName;
    protected string $modulePath;
    protected string $stubsPath;
    protected string $moduleType;

    public function handle(): int
    {
        $this->moduleName = Str::studly($this->argument('name'));
        $this->modulePath = app_path("Modules/{$this->moduleName}");
        $this->moduleType = strtolower($this->option('type'));
        $this->stubsPath  = $this->resolveStubsPath();

        if (!in_array($this->moduleType, ['api', 'web'])) {
            $this->error("Tipo inválido: '{$this->moduleType}'. Utilize --type=api ou --type=web.");
            return self::FAILURE;
        }

        if (File::exists($this->modulePath) && !$this->option('force')) {
            $this->error("O módulo {$this->moduleName} já existe!");
            $this->info("Use --force para sobrescrever os arquivos existentes.");
            return self::FAILURE;
        }

        if (!File::exists($this->stubsPath)) {
            $this->error("Diretório de stubs não encontrado em: {$this->stubsPath}");
            $this->info("Publique os stubs com: php artisan vendor:publish --tag=module-stubs");
            return self::FAILURE;
        }

        $this->info("Criando módulo: {$this->moduleName} [tipo: {$this->moduleType}]");
        $this->newLine();

        $this->createModuleStructure();
        $this->generateFiles();

        $this->newLine();
        $this->info(">>>> Módulo {$this->moduleName} criado com sucesso!");
        $this->newLine();
        $this->info("Próximos passos:");
        $this->line("  1. Atualize a migration em:         app/Modules/{$this->moduleName}/Database/Migrations/");
        $this->line("  2. Defina os \$fillable em:           app/Modules/{$this->moduleName}/Models/{$this->moduleName}.php");
        $this->line("  3. Adicione regras de validação em:  app/Modules/{$this->moduleName}/Http/Requests/");

        if ($this->moduleType === 'api') {
            $this->line("  4. Customize o resource em:          app/Modules/{$this->moduleName}/Http/Resources/{$this->moduleName}Resource.php");
        } else {
            $this->line("  4. Crie as views em:                 resources/views/{$this->moduleKebab()}/");
        }

        $this->line("  5. Execute as migrations:            php artisan migrate");

        return self::SUCCESS;
    }

    /**
     * Resolve o caminho dos stubs: utiliza os publicados primeiro, fallback para os do pacote.
     */
    protected function resolveStubsPath(): string
    {
        $published = base_path('stubs/modules');

        if (File::exists($published)) {
            return $published;
        }

        return __DIR__ . '/../../../stubs/modules';
    }

    protected function moduleKebab(): string
    {
        return Str::kebab($this->moduleName);
    }

    /**
     * Cria a estrutura de diretórios do módulo.
     */
    protected function createModuleStructure(): void
    {
        $directories = [
            'Http/Controllers',
            'Http/Requests',
            'Models',
            'Repositories/Contracts',
            'Services',
            'Policies',
            'Middleware',
            'Database/Migrations',
            'Database/Seeders',
            'Database/Factories',
            'Routes',
            'Config',
            'Providers',
        ];

        if ($this->moduleType === 'api') {
            $directories[] = 'Http/Resources';
        }

        foreach ($directories as $directory) {
            $path = "{$this->modulePath}/{$directory}";

            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
                $this->line("   >>>> Criado: {$directory}");
            }
        }
    }

    /**
     * Gera os arquivos a partir dos stubs.
     */
    protected function generateFiles(): void
    {
        $this->newLine();
        $this->info("Gerando arquivos...");

        // Controller (API ou Web)
        $controllerStub = $this->moduleType === 'web' ? 'controller-web.stub' : 'controller.stub';
        $this->generateFile($controllerStub, "Http/Controllers/{$this->moduleName}Controller.php");

        // Requests
        $this->generateFile('store-request.stub',  "Http/Requests/Store{$this->moduleName}Request.php");
        $this->generateFile('update-request.stub', "Http/Requests/Update{$this->moduleName}Request.php");

        // Resource (apenas API)
        if ($this->moduleType === 'api') {
            $this->generateFile('resource.stub', "Http/Resources/{$this->moduleName}Resource.php");
        }

        // Model
        $this->generateFile('model.stub', "Models/{$this->moduleName}.php");

        // Repository Interface
        $this->generateFile(
            'repository-interface.stub',
            "Repositories/Contracts/{$this->moduleName}RepositoryInterface.php"
        );

        // Repository
        $this->generateFile('repository.stub', "Repositories/{$this->moduleName}Repository.php");

        // Service
        $this->generateFile('service.stub', "Services/{$this->moduleName}Service.php");

        // Policy
        $this->generateFile('policy.stub', "Policies/{$this->moduleName}Policy.php");

        // Seeder
        $this->generateFile('seeder.stub', "Database/Seeders/{$this->moduleName}Seeder.php");

        // Factory
        $this->generateFile('factory.stub', "Database/Factories/{$this->moduleName}Factory.php");

        // Rotas
        $routesStub = $this->moduleType === 'web' ? 'routes-web.stub' : 'routes-api.stub';
        $routesFile = $this->moduleType === 'web' ? 'Routes/web.php' : 'Routes/api.php';
        $this->generateFile($routesStub, $routesFile);

        // Config
        $this->generateFile('config.stub', 'Config/' . strtolower($this->moduleName) . '.php');

        // Service Provider
        $this->generateFile('service-provider.stub', "Providers/{$this->moduleName}ServiceProvider.php");

        // Migration
        $this->generateMigration();
    }

    protected function generateFile(string $stub, string $destination): void
    {
        $stubPath        = "{$this->stubsPath}/{$stub}";
        $destinationPath = "{$this->modulePath}/{$destination}";

        if (!File::exists($stubPath)) {
            $this->warn(">>>> Stub não encontrado: {$stub}");
            return;
        }

        $content = $this->replaceStubVariables(File::get($stubPath));

        File::put($destinationPath, $content);
        $this->line("   >>>> Gerado: {$destination}");
    }

    protected function generateMigration(): void
    {
        $tableName  = Str::snake(Str::pluralStudly($this->moduleName));
        $timestamp  = date('Y_m_d_His');
        $fileName   = "{$timestamp}_create_{$tableName}_table.php";
        $stubPath   = "{$this->stubsPath}/migration.stub";
        $destPath   = "{$this->modulePath}/Database/Migrations/{$fileName}";

        if (!File::exists($stubPath)) {
            $this->warn("   >>>> Stub de migration não encontrado");
            return;
        }

        $content = $this->replaceStubVariables(File::get($stubPath));
        $content = str_replace('{{TABLE_NAME}}', $tableName, $content);

        File::put($destPath, $content);
        $this->line("   >>>> Gerado: Database/Migrations/{$fileName}");
    }

    protected function replaceStubVariables(string $content): string
    {
        $replacements = [
            '{{MODULE}}'              => $this->moduleName,
            '{{MODULE_LOWER}}'        => Str::camel($this->moduleName),
            '{{MODULE_LOWER_PLURAL}}' => Str::plural(Str::camel($this->moduleName)),
            '{{MODULE_PLURAL}}'       => Str::pluralStudly($this->moduleName),
            '{{MODULE_KEBAB}}'        => Str::kebab($this->moduleName),
            '{{MODULE_KEBAB_PLURAL}}' => Str::plural(Str::kebab($this->moduleName)),
            '{{MODULE_UPPER}}'        => Str::upper(Str::snake($this->moduleName)),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $content
        );
    }
}
