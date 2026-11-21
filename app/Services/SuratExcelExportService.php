<?php

namespace App\Services;

use App\Models\Surat;
use DateTimeInterface;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SuratExcelExportService
{
    /**
     * @param  Collection<int, Surat>  $surats
     */
    public function laporanSelesai(
        Collection $surats,
        string $namaKategori,
        string $labelPeriode
    ): Spreadsheet {
        return $this->buildWorkbook(
            surats: $surats,
            judul: 'Rekapitulasi Surat Selesai',
            deskripsi: 'Dokumen penyelesaian surat berdasarkan periode dan kategori pelaporan.',
            namaSheet: 'Laporan Surat',
            metadata: [
                'Periode' => $labelPeriode,
                'Kategori' => $namaKategori,
            ],
            columns: [
                ['label' => 'No.', 'width' => 7, 'align' => Alignment::HORIZONTAL_CENTER, 'value' => fn (Surat $surat, int $index): int => $index + 1],
                ['label' => 'Nomor Surat', 'width' => 24, 'string' => true, 'value' => fn (Surat $surat): string => $surat->nomor_surat],
                ['label' => 'Nama Pemohon', 'width' => 34, 'wrap' => true, 'string' => true, 'value' => fn (Surat $surat): string => $surat->pemohon_pengirim],
                ['label' => 'Tanggal Masuk', 'width' => 18, 'align' => Alignment::HORIZONTAL_CENTER, 'format' => 'dd/mm/yyyy', 'value' => fn (Surat $surat): DateTimeInterface => $surat->tanggal_masuk],
                ['label' => 'Tanggal Selesai', 'width' => 20, 'align' => Alignment::HORIZONTAL_CENTER, 'format' => 'dd/mm/yyyy', 'value' => fn (Surat $surat): DateTimeInterface|string => $surat->selesai_pada ?? 'Tidak tercatat'],
                ['label' => 'Keterangan', 'width' => 42, 'wrap' => true, 'string' => true, 'value' => fn (Surat $surat): string => filled($surat->keterangan) ? $surat->keterangan : 'Tidak ada keterangan'],
            ],
        );
    }

    /**
     * @param  Collection<int, Surat>  $surats
     */
    public function dataSurat(
        Collection $surats,
        string $namaKategori,
        string $ringkasanFilter
    ): Spreadsheet {
        return $this->buildWorkbook(
            surats: $surats,
            judul: 'Rekapitulasi Data Surat',
            deskripsi: 'Dokumen pencatatan surat berdasarkan kategori dan filter pelaporan.',
            namaSheet: 'Data Surat',
            metadata: [
                'Kategori' => $namaKategori,
                'Filter' => $ringkasanFilter,
            ],
            columns: [
                ['label' => 'No.', 'width' => 7, 'align' => Alignment::HORIZONTAL_CENTER, 'value' => fn (Surat $surat, int $index): int => $index + 1],
                ['label' => 'Nomor Surat', 'width' => 24, 'string' => true, 'value' => fn (Surat $surat): string => $surat->nomor_surat],
                ['label' => 'Nama Pemohon', 'width' => 34, 'wrap' => true, 'string' => true, 'value' => fn (Surat $surat): string => $surat->pemohon_pengirim],
                ['label' => 'Tanggal Masuk', 'width' => 18, 'align' => Alignment::HORIZONTAL_CENTER, 'format' => 'dd/mm/yyyy', 'value' => fn (Surat $surat): DateTimeInterface => $surat->tanggal_masuk],
                ['label' => 'Tanggal Selesai', 'width' => 20, 'align' => Alignment::HORIZONTAL_CENTER, 'format' => 'dd/mm/yyyy', 'value' => fn (Surat $surat): DateTimeInterface|string => $surat->selesai_pada ?? 'Tidak tercatat'],
            ],
        );
    }

    public function saveTemporary(Spreadsheet $spreadsheet, string $prefix): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), $prefix);
        if ($tempPath === false) {
            throw new \RuntimeException('File sementara untuk export Excel tidak dapat dibuat.');
        }

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($tempPath);
        $spreadsheet->disconnectWorksheets();

        return $tempPath;
    }

    /**
     * @param  Collection<int, Surat>  $surats
     * @param  array<string, string>  $metadata
     * @param  array<int, array{label: string, width: int, value: callable, string?: bool, format?: string, wrap?: bool, align?: string}>  $columns
     */
    private function buildWorkbook(
        Collection $surats,
        string $judul,
        string $deskripsi,
        string $namaSheet,
        array $metadata,
        array $columns
    ): Spreadsheet {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator(config('branding.mail_name'))
            ->setCompany(config('branding.agency'))
            ->setTitle($judul)
            ->setSubject(implode(' - ', array_values($metadata)));

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($namaSheet);
        $sheet->setShowGridlines(false);

        $lastColumn = Coordinate::stringFromColumnIndex(count($columns));
        $headerRow = 9;
        $firstDataRow = 10;

        $this->addBranding($sheet, $lastColumn);

        $sheet->mergeCells("A4:{$lastColumn}4");
        $sheet->setCellValue('A4', $judul);
        $sheet->getStyle('A4')->getFont()->setName('Arial')->setBold(true)->setSize(17)->getColor()->setRGB('153B68');
        $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(28);

        $sheet->mergeCells("A5:{$lastColumn}5");
        $sheet->setCellValue('A5', $deskripsi);
        $sheet->getStyle('A5')->getFont()->setName('Arial')->setItalic(true)->setSize(12)->getColor()->setRGB('64748B');
        $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(5)->setRowHeight(20);

        $this->addMetadata($sheet, $lastColumn, $metadata);

        foreach ($columns as $columnIndex => $column) {
            $columnLetter = Coordinate::stringFromColumnIndex($columnIndex + 1);
            $sheet->setCellValue("{$columnLetter}{$headerRow}", $column['label']);
            $sheet->getColumnDimension($columnLetter)->setWidth($column['width']);
        }

        foreach ($surats as $index => $surat) {
            $row = $firstDataRow + $index;

            foreach ($columns as $columnIndex => $column) {
                $columnLetter = Coordinate::stringFromColumnIndex($columnIndex + 1);
                $cell = "{$columnLetter}{$row}";
                $value = $column['value']($surat, $index);

                if ($value instanceof DateTimeInterface) {
                    $sheet->setCellValue($cell, Date::dateTimeToExcel($value));
                } elseif (($column['string'] ?? false) === true) {
                    $sheet->setCellValueExplicit($cell, (string) $value, DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($cell, $value);
                }

                if (isset($column['format']) && $value instanceof DateTimeInterface) {
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode($column['format']);
                }
            }
        }

        $lastRow = max($headerRow, $firstDataRow + $surats->count() - 1);
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '174F95']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '103B74']],
                'vertical' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DCE8F5']],
            ],
        ]);
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$lastRow}")->getFont()->setName('Arial')->setSize(10);

        if ($surats->isNotEmpty()) {
            $bodyRange = "A{$firstDataRow}:{$lastColumn}{$lastRow}";
            $sheet->getStyle($bodyRange)->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 10, 'color' => ['rgb' => '263A53']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders' => [
                    'bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'DCE5EF']],
                ],
            ]);

            foreach (range($firstDataRow, $lastRow) as $row) {
                $sheet->getRowDimension($row)->setRowHeight(24);

                if (($row - $firstDataRow) % 2 === 1) {
                    $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('F6F9FD');
                }
            }
        }

        foreach ($columns as $columnIndex => $column) {
            $columnLetter = Coordinate::stringFromColumnIndex($columnIndex + 1);

            if ($surats->isNotEmpty()) {
                $columnAlignment = $sheet->getStyle("{$columnLetter}{$firstDataRow}:{$columnLetter}{$lastRow}")->getAlignment();
                $columnAlignment->setHorizontal($column['align'] ?? Alignment::HORIZONTAL_LEFT);

                if (($column['wrap'] ?? false) === true) {
                    $columnAlignment->setWrapText(true);
                }
            }
        }

        $sheet->getRowDimension(8)->setRowHeight(9);
        $sheet->getRowDimension($headerRow)->setRowHeight(32);
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->getAlignment()->setWrapText(true);
        $sheet->freezePane("A{$firstDataRow}");
        $sheet->setAutoFilter("A{$headerRow}:{$lastColumn}{$lastRow}");
        $sheet->setSelectedCell('A1');
        $sheet->getSheetView()->setZoomScale(100);
        $sheet->getPageSetup()
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setHorizontalCentered(true);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($headerRow, $headerRow);
        $sheet->getPageSetup()->setPrintArea("A1:{$lastColumn}{$lastRow}");
        $sheet->getPageMargins()->setTop(0.5)->setRight(0.35)->setBottom(0.5)->setLeft(0.35);
        $sheet->getHeaderFooter()->setOddFooter('&CArial,Regular&9Halaman &P dari &N');

        return $spreadsheet;
    }

    /** @param array<string, string> $metadata */
    private function addMetadata(Worksheet $sheet, string $lastColumn, array $metadata): void
    {
        $lastColumnIndex = Coordinate::columnIndexFromString($lastColumn);
        $leftEnd = Coordinate::stringFromColumnIndex(max(1, intdiv($lastColumnIndex, 2)));
        $rightStart = Coordinate::stringFromColumnIndex(max(2, intdiv($lastColumnIndex, 2) + 1));
        $blocks = ["A:{$leftEnd}", "{$rightStart}:{$lastColumn}"];

        foreach (array_values(array_slice($metadata, 0, 2, true)) as $index => $value) {
            $label = array_keys(array_slice($metadata, $index, 1, true))[0];
            [$startColumn, $endColumn] = explode(':', $blocks[$index]);
            $sheet->mergeCells("{$startColumn}6:{$endColumn}6");
            $sheet->mergeCells("{$startColumn}7:{$endColumn}7");
            $sheet->setCellValue("{$startColumn}6", mb_strtoupper($label));
            $sheet->setCellValueExplicit("{$startColumn}7", $value, DataType::TYPE_STRING);
            $sheet->getStyle("{$startColumn}6:{$endColumn}7")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $index === 0 ? 'EDF5FD' : 'F4F7FB']],
                'borders' => [
                    'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'C9D9EA']],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("{$startColumn}6")->getFont()->setName('Arial')->setBold(true)->setSize(8)->getColor()->setRGB('55708F');
            $sheet->getStyle("{$startColumn}7")->getFont()->setName('Arial')->setBold(true)->setSize(11)->getColor()->setRGB('163D6B');
        }

        $sheet->getRowDimension(6)->setRowHeight(18);
        $sheet->getRowDimension(7)->setRowHeight(26);
    }

    private function addBranding(Worksheet $sheet, string $lastColumn): void
    {
        $sheet->getRowDimension(1)->setRowHeight(46);
        $sheet->getRowDimension(2)->setRowHeight(34);
        $sheet->getRowDimension(3)->setRowHeight(9);
        $sheet->getStyle("A1:{$lastColumn}2")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F9FE']],
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'B9D2EC']],
            ],
        ]);

        $logoPemkab = new Drawing;
        $logoPemkab->setName('Logo Pemerintah Kabupaten Jember');
        $logoPemkab->setPath(public_path('images/logo-pemkab-jember.png'));
        $jumlahKolom = Coordinate::columnIndexFromString($lastColumn);
        $logoPemkab->setCoordinates($jumlahKolom >= 6 ? 'C1' : 'B1');
        $logoPemkab->setResizeProportional(false);
        $logoPemkab->setWidth(91);
        $logoPemkab->setHeight(89);
        $logoPemkab->setOffsetX($jumlahKolom >= 6 ? 21 : 45);
        $logoPemkab->setOffsetY(12);
        $logoPemkab->setWorksheet($sheet);

        $logoBapenda = new Drawing;
        $logoBapenda->setName('Logo Bapenda Jember');
        $logoBapenda->setPath(public_path('images/logo-bapenda-excel-crop.png'));
        $rightLogoColumn = Coordinate::stringFromColumnIndex(max(2, $jumlahKolom - 1));
        $logoBapenda->setCoordinates("{$rightLogoColumn}1");
        $logoBapenda->setResizeProportional(false);
        $logoBapenda->setWidth(69);
        $logoBapenda->setHeight(85);
        $logoBapenda->setOffsetX($jumlahKolom >= 6 ? 120 : 95);
        $logoBapenda->setOffsetY(15);
        $logoBapenda->setWorksheet($sheet);

        $sheet->mergeCells("A1:{$lastColumn}2");

        $identity = new RichText;
        $name = $identity->createTextRun(config('branding.short_name'));
        $name->getFont()->setName('Arial')->setBold(true)->setSize(14)->getColor()->setRGB('103B74');
        $identity->createText("\n");
        $fullName = $identity->createTextRun(mb_strtoupper(config('branding.full_name')));
        $fullName->getFont()->setName('Arial')->setBold(true)->setSize(10)->getColor()->setRGB('153B68');
        $identity->createText("\n");
        $agency = $identity->createTextRun(mb_strtoupper(config('branding.agency')));
        $agency->getFont()->setName('Arial')->setBold(true)->setSize(9)->getColor()->setRGB('55708F');

        $sheet->setCellValue('A1', $identity);
        $sheet->getStyle("A1:{$lastColumn}2")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
    }
}
