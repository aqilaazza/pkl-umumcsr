<?php

namespace App\Http\Controllers\Manager;

use FPDF;

class SertifikatPDF extends FPDF
{
    public $bgImage = '';

    public function Header()
    {
        if ($this->bgImage && file_exists($this->bgImage)) {
            $this->Image($this->bgImage, 0, 0, 297, 210);
        }
    }

    private function formatRangeTanggal($tglMulai, $tglSelesai)
    {
        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $ts1 = strtotime($tglMulai);
        $ts2 = strtotime($tglSelesai);
        $d1 = (int) date('d', $ts1);
        $m1 = (int) date('n', $ts1);
        $y1 = (int) date('Y', $ts1);
        $d2 = (int) date('d', $ts2);
        $m2 = (int) date('n', $ts2);
        $y2 = (int) date('Y', $ts2);
        if ($y1 === $y2) {
            if ($m1 === $m2) {
                if ($d1 === $d2) {
                    return "$d1 " . $namaBulan[$m1] . " $y1";
                }
                return "$d1 - $d2 " . $namaBulan[$m1] . " $y1";
            } else {
                return "$d1 " . $namaBulan[$m1] . " - $d2 " . $namaBulan[$m2] . " $y1";
            }
        } else {
            return "$d1 " . $namaBulan[$m1] . " $y1 - $d2 " . $namaBulan[$m2] . " $y2";
        }
    }

    private function getTanggalIndonesia($tanggal)
    {
        $namaBulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $ts = strtotime($tanggal);
        return date('d', $ts) . ' ' . $namaBulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    }

    public function BuatSertifikat($nomorSurat, $nama, $sekolah, $bidang, $unit, $tglMulai, $tglSelesai, $namaTTD, $jabatanTTD, $qrImage)
    {
        $this->Cell(0, 20, '', 0, 1);
        $this->SetFont('Arial', '', 13);
        $this->Cell(0, 15, "No : $nomorSurat", 0, 1, 'C');
        $this->SetFont('Arial', '', 15);
        $this->Cell(0, 15, "Diberikan kepada:", 0, 1, 'C');
        $this->SetFont('Arial', 'B', 30);
        $this->Cell(0, 20, $nama, 0, 1, 'C');
        $this->SetFont('Arial', 'B', 20);
        $this->Cell(0, 10, $sekolah, 0, 1, 'C');
        $this->SetFont('Arial', '', 13);
        $bagian_str = "";
        if ($bidang || $unit) {
            $parts = [];
            if ($bidang) {
                $parts[] = $bidang;
            }
            if ($unit) {
                $parts[] = $unit;
            }
            $bagian_str = " di Bagian " . implode(' ', $parts);
        } else {
            $bagian_str = " di";
        }
        $ket = "Telah Melaksanakan Praktek Kerja Lapangan" . $bagian_str . " PT PLN Nusantara Power UP Paiton";
        $this->Cell(0, 35, $ket, 0, 1, 'C');
        $this->SetY($this->GetY() - 10);
        $this->SetFont('Arial', '', 13);
        $periode = $this->formatRangeTanggal($tglMulai, $tglSelesai);
        $this->Cell(0, 0, "Tanggal $periode", 0, 1, 'C');
        $tglSkrg = $this->getTanggalIndonesia(date('Y-m-d'));
        $this->Cell(0, 20, "Paiton, " . $tglSkrg, 0, 1, 'C');
        $yAfterDate = $this->GetY();
        if ($qrImage && file_exists($qrImage)) {
            $qrSize = 30;
            $qrX = ($this->GetPageWidth() - $qrSize) / 2;
            $qrY = $yAfterDate + 3;
            $this->Image($qrImage, $qrX, $qrY, $qrSize, $qrSize);
            $this->SetXY($qrX, $qrY + $qrSize + 1);
            $this->SetFont('Arial', '', 6);
            $this->Cell($qrSize, 2, 'Scan QR verifikasi', 0, 0, 'C');
        }
        $ttdY = $yAfterDate + 3 + 30 + 1 + 2 + 3;
        $this->SetY($ttdY);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 6, $namaTTD, 0, 1, 'C');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 5, $jabatanTTD, 0, 1, 'C');
    }
}
