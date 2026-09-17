<?php

namespace App\Console\Commands;

use App\Services\Sat\Exceptions\SatImportException;
use App\Services\Sat\Exceptions\SatSourceUnavailableException;
use App\Services\Sat\Sat69BImporter;
use Illuminate\Console\Command;

class SatImport69BCommand extends Command
{
    protected $signature = 'sat:import-69b {--file= : Ruta a un archivo local (CSV) en lugar de descargar de la URL configurada}';

    protected $description = 'Descarga (o lee localmente) e importa el listado público del Artículo 69-B del SAT.';

    public function handle(Sat69BImporter $importer): int
    {
        $localFile = $this->option('file');

        try {
            if ($localFile) {
                $this->info("Importando desde archivo local: {$localFile}");
                $dataset = $importer->importFromLocalFile($localFile);
            } else {
                $sourceUrl = config('sat.source_url');

                if (! $sourceUrl) {
                    $this->error('SAT_69B_SOURCE_URL no está configurada. Define la URL oficial del listado del SAT en el .env o usa --file=ruta.csv.');

                    return self::FAILURE;
                }

                $this->info("Descargando listado 69-B desde: {$sourceUrl}");
                $dataset = $importer->importFromUrl($sourceUrl, (int) config('sat.download_timeout'));
            }
        } catch (SatSourceUnavailableException $exception) {
            $this->error("Fuente del SAT no disponible: {$exception->getMessage()}");

            return self::FAILURE;
        } catch (SatImportException $exception) {
            $this->error("Error al importar el listado: {$exception->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Dataset #{$dataset->id} — estado: {$dataset->status->value} — registros: {$dataset->record_count}");

        if ($dataset->error_message) {
            $this->warn($dataset->error_message);
        }

        return $dataset->status->value === 'active' ? self::SUCCESS : self::FAILURE;
    }
}
