<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\Route;

class QrController extends Controller
{
    public function index()
    {
        $menuUrl = url('/');
        
        return view('admin.qr.index', [
            'menuUrl' => $menuUrl,
        ]);
    }

    public function generate()
    {
        $menuUrl = url('/');
        
        // Create QR code with SVG writer
        $qrCode = QrCode::create($menuUrl)
            ->setSize(300);
        
        $writer = new SvgWriter();
        $result = $writer->write($qrCode);
        
        return response($result->getString())
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', 'inline; filename="menu-qr.svg"');
    }

    public function download()
    {
        $menuUrl = url('/');
        
        // Create QR code with PNG writer for download
        $qrCode = QrCode::create($menuUrl)
            ->setSize(400);
        
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        return response($result->getString())
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'attachment; filename="menu-qr.png"');
    }
}