<?php

namespace App\Support;

use League\Csv\Writer as CsvWriter;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TableExport
{
    /**
     * Gera um arquivo (CSV ou XLSX) e devolve um download que se apaga após o envio.
     *
     * @param  'csv'|'xlsx'  $format
     * @param  list<string>  $headings
     * @param  iterable<int, list<scalar|null>>  $rows  gerador de linhas (uma lista de valores por linha)
     */
    public static function download(string $format, string $filenameBase, array $headings, iterable $rows): BinaryFileResponse
    {
        $filename = $filenameBase.'-'.now()->format('Y-m-d_His');

        return $format === 'xlsx'
            ? self::xlsx($filename, $headings, $rows)
            : self::csv($filename, $headings, $rows);
    }

    /**
     * @param  list<string>  $headings
     * @param  iterable<int, list<scalar|null>>  $rows
     */
    private static function csv(string $filename, array $headings, iterable $rows): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'exp');

        $handle = fopen($path, 'w');
        // BOM UTF-8 para o Excel abrir acentos corretamente.
        fwrite($handle, "\xEF\xBB\xBF");

        $csv = CsvWriter::createFromStream($handle);
        $csv->insertOne($headings);

        foreach ($rows as $row) {
            $csv->insertOne(array_map(static fn ($v): string => (string) ($v ?? ''), $row));
        }

        fclose($handle);

        return self::respond($path, $filename.'.csv');
    }

    /**
     * @param  list<string>  $headings
     * @param  iterable<int, list<scalar|null>>  $rows
     */
    private static function xlsx(string $filename, array $headings, iterable $rows): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'exp');

        $writer = new XlsxWriter();
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues($headings));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues(array_map(static fn ($v) => $v ?? '', $row)));
        }

        $writer->close();

        return self::respond($path, $filename.'.xlsx');
    }

    private static function respond(string $path, string $downloadName): BinaryFileResponse
    {
        return response()
            ->download($path, $downloadName)
            ->deleteFileAfterSend(true);
    }
}
