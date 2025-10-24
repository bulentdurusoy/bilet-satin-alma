<?php
require __DIR__ . '/vendor/fpdf/fpdf.php';

// Türkçe karakter dönüştürücü (UTF-8 → ISO-8859-9)
function tr($s) {
    return iconv('UTF-8', 'ISO-8859-9//TRANSLIT', $s);
}

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,12, tr('Merhaba FPDF!'), 0, 1, 'C');
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10, tr('Bu bir test sayfasıdır.'), 0, 1);

ob_end_clean(); 
$pdf->Output('I', 'test.pdf');
