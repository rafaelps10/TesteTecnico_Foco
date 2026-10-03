<?php

namespace App\Console\Commands;

use App\Services\XmlImportService;
use Illuminate\Console\Command;

class ImportXmlCommand extends Command
{
    protected $signature = 'xml:import';

    protected $description = 'Importa hotéis, quartos e reservas a partir dos arquivos XML';

    public function handle(XmlImportService $xmlImportService): int
    {
        $this->info('Iniciando importação dos arquivos XML...');

        try {
            $xmlImportService->import();

            $this->info('Importação concluída com sucesso.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error(
                'Erro durante a importação: ' . $exception->getMessage()
            );

            return self::FAILURE;
        }
    }
}