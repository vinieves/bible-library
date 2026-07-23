<?php

namespace App\Filament\Concerns;

use App\Support\TableExport;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Adiciona um botão "Exportar" (CSV / Excel) a uma página ListRecords do Filament,
 * exportando exatamente os itens filtrados/pesquisados da tabela.
 *
 * Requer que a classe use `InteractsWithTable` (padrão das páginas ListRecords).
 */
trait ExportsTableData
{
    /**
     * @param  list<string>  $headings  cabeçalhos das colunas
     * @param  Closure(mixed): list<scalar|null>  $mapper  mapeia cada registro para uma linha
     */
    protected function tableExportActions(string $filenameBase, array $headings, Closure $mapper): ActionGroup
    {
        return ActionGroup::make([
            Action::make('exportCsv')
                ->label('Baixar CSV')
                ->icon('heroicon-o-document-text')
                ->action(fn (): BinaryFileResponse => $this->downloadTableExport('csv', $filenameBase, $headings, $mapper)),
            Action::make('exportXlsx')
                ->label('Baixar Excel (.xlsx)')
                ->icon('heroicon-o-table-cells')
                ->action(fn (): BinaryFileResponse => $this->downloadTableExport('xlsx', $filenameBase, $headings, $mapper)),
        ])
            ->label('Exportar')
            ->icon('heroicon-o-arrow-down-tray')
            ->button();
    }

    /**
     * @param  'csv'|'xlsx'  $format
     * @param  list<string>  $headings
     * @param  Closure(mixed): list<scalar|null>  $mapper
     */
    private function downloadTableExport(string $format, string $filenameBase, array $headings, Closure $mapper): BinaryFileResponse
    {
        $query = $this->getFilteredSortedTableQuery();

        $rows = (function () use ($query, $mapper): iterable {
            foreach ($query->cursor() as $record) {
                yield $mapper($record);
            }
        })();

        return TableExport::download($format, $filenameBase, $headings, $rows);
    }
}
