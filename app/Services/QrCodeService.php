<?php

namespace App\Services;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Matrix\MatrixInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use RuntimeException;

/**
 * Generación del código QR de la carta.
 *
 * endroid/qr-code v6 cambió la API: `QrCode` es `final readonly` y se construye
 * con argumentos nombrados (ya no existen `QrCode::create()` ni `setSize()`), y su
 * PngWriter depende de imagick, que no está en este host (solo hay gd). Por eso el
 * PNG se rasteriza a mano desde la matriz de módulos con GD.
 */
class QrCodeService
{
    /** QR en SVG (vector: se imprime sin pérdida en las mesas). */
    public function svg(string $url, int $size = 400): string
    {
        $qrCode = $this->qrCode($url, $size, RoundBlockSizeMode::Margin);

        return (new SvgWriter)->write($qrCode)->getString();
    }

    /** QR en PNG rasterizado con GD a partir de la matriz de módulos. */
    public function png(string $url, int $size = 400): string
    {
        // Enlarge deja el bloque de tamaño entero; con Margin los módulos salen desdibujados.
        $qrCode = $this->qrCode($url, $size, RoundBlockSizeMode::Enlarge);
        $matrix = (new SvgWriter)->write($qrCode)->getMatrix();

        return $this->matrixToPng($matrix);
    }

    private function qrCode(string $url, int $size, RoundBlockSizeMode $roundBlockSizeMode): QrCode
    {
        return new QrCode(
            data: $url,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: max(8, (int) round($size * 0.08)),
            roundBlockSizeMode: $roundBlockSizeMode,
        );
    }

    private function matrixToPng(MatrixInterface $matrix): string
    {
        if (! function_exists("imagecreatetruecolor")) {
            throw new RuntimeException("La extensión GD es necesaria para descargar el QR en PNG.");
        }

        $blockCount = $matrix->getBlockCount();
        $blockSize = max(1, (int) $matrix->getBlockSize());
        $margin = $matrix->getMarginLeft();
        $canvas = $blockCount * $blockSize + 2 * $margin;

        $image = imagecreatetruecolor($canvas, $canvas);
        $background = imagecolorallocate($image, 255, 255, 255);
        $foreground = imagecolorallocate($image, 0, 0, 0);

        imagefill($image, 0, 0, $background);

        for ($row = 0; $row < $blockCount; $row++) {
            for ($column = 0; $column < $blockCount; $column++) {
                if ($matrix->getBlockValue($row, $column) !== 1) {
                    continue;
                }

                $x = $margin + $column * $blockSize;
                $y = $margin + $row * $blockSize;

                imagefilledrectangle($image, $x, $y, $x + $blockSize - 1, $y + $blockSize - 1, $foreground);
            }
        }

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();

        imagedestroy($image);

        return $png;
    }
}
