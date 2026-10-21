<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\QrCodeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class QrController extends Controller
{
    public function __construct(private readonly QrCodeService $qrCodes) {}

    public function index(): View
    {
        $menuUrl = url("/");

        return view("admin.qr.index", [
            "menuUrl" => $menuUrl,
            // SVG en línea: render inmediato sin segunda petición y nítido al imprimir.
            "qrSvg" => $this->qrCodes->svg($menuUrl, 320),
        ]);
    }

    public function generate(): Response
    {
        $svg = $this->qrCodes->svg(url("/"), 320);

        return response($svg, 200, [
            "Content-Type" => "image/svg+xml",
            "Content-Disposition" => 'inline; filename="menu-qr.svg"',
            "Cache-Control" => "private, max-age=3600",
        ]);
    }

    public function download(): Response
    {
        $png = $this->qrCodes->png(url("/"), 600);

        return response($png, 200, [
            "Content-Type" => "image/png",
            "Content-Disposition" => 'attachment; filename="menu-qr.png"',
            "Cache-Control" => "private, max-age=3600",
        ]);
    }
}
