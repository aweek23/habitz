<?php
// JPGraph line/area chart for step evolution.
// Emits a PNG; falls back to a placeholder if JPGraph isn't available.

$jpgraphBase = __DIR__ . '/jpgraph/src';
$jpgraphMain = $jpgraphBase . '/jpgraph.php';
$jpgraphLine = $jpgraphBase . '/jpgraph_line.php';

// Sample step data (text axis, linear scale).
$steps = [5600, 6200, 5900, 6800, 7200, 7000, 7800, 8100, 7600, 8400, 9000, 9400];

// Fallback placeholder when JPGraph is missing.
if (!file_exists($jpgraphMain) || !file_exists($jpgraphLine)) {
    $width = 640; $height = 280;
    $img = imagecreatetruecolor($width, $height);
    $bg  = imagecolorallocate($img, 15, 20, 28);
    $fg  = imagecolorallocate($img, 220, 230, 240);
    imagefill($img, 0, 0, $bg);

    $message = "JPGraph manquant\nAjoute la librairie dans php/jpgraph/ pour afficher le graphique.";
    $lines = explode("\n", $message);
    $y = 110;
    foreach ($lines as $line) {
        imagestring($img, 5, 60, $y, $line, $fg);
        $y += 24;
    }

    header('Content-Type: image/png');
    imagepng($img);
    imagedestroy($img);
    exit;
}

require_once $jpgraphMain;
require_once $jpgraphLine;

$graph = new Graph(640, 280);
$graph->SetScale('textlin', 0, max($steps) * 1.1);
$graph->SetFrame(false);
$graph->SetMargin(18, 12, 14, 26);
$graph->SetMarginColor('#0f141c');
$graph->SetColor('#0f141c');
$graph->img->SetAntiAliasing(true);
$graph->SetClipping(true);

$graph->xaxis->SetTickLabels(array_fill(0, count($steps), ''));
$graph->xaxis->HideTicks(true, true);
$graph->xaxis->SetColor('#3c4a5a', '#3c4a5a');
$graph->yaxis->HideTicks(true, true);
$graph->yaxis->SetColor('#3c4a5a', '#3c4a5a');
$graph->ygrid->SetColor('#15202b');
$graph->ygrid->Show(false);
$graph->SetBox(false);

$linePlot = new LinePlot($steps);
$linePlot->SetColor('#6ae0f8');
$linePlot->SetWeight(2);
$linePlot->SetFillGradient('#63c8b2', '#0f141c', GRAD_VER);
$linePlot->SetStepStyle(false);
$linePlot->SetBarCenter();

$graph->Add($linePlot);
$graph->Stroke();
