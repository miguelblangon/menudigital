@extends('layouts.admin')

@section('title', 'Código QR')

@section('content')
<h1 class="text-2xl font-bold text-gray-800 mb-6">Código QR</h1>

<div data-print-block class="bg-white rounded-lg shadow p-4 sm:p-6">
    <div class="text-center mb-6">
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">Código QR de tu Carta</h2>
        <p class="text-sm sm:text-base text-gray-600">Imprime este código y colócalo en tus mesas para que los clientes puedan ver tu carta digital.</p>
    </div>

    <div class="flex flex-col items-center gap-6">
        {{--
            data-qr: el SvgWriter de endroid/qr-code escribe el SVG con un ancho
            absoluto (width="464px"). Sin la regla `[data-qr] svg` de app.css ese
            ancho fijo desborda cualquier pantalla de 375px y la página entera
            obliga a desplazarse en horizontal. El contenedor limita el tamaño
            en pantalla y el SVG, al ser vectorial, se escala sin perder nitidez
            al imprimir.
        --}}
        <div data-qr class="w-full max-w-[17rem] sm:max-w-[22rem] bg-white p-4 sm:p-8 rounded-xl shadow-lg border-2 border-gray-100">
            {!! $qrSvg !!}
        </div>

        <!-- URL Info -->
        <div class="bg-blue-50 p-4 rounded-lg w-full max-w-md">
            <p class="text-sm text-blue-800 font-medium mb-1">URL del código QR:</p>
            <p class="text-blue-600 break-all">{{ $menuUrl }}</p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 w-full max-w-md">
            <a href="{{ route('admin.qr.download') }}" class="flex-1 flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V6a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V18a2 2 0 01-2 2z" />
                </svg>
                Descargar PNG
            </a>

            <button type="button" data-print class="flex-1 flex items-center justify-center gap-2 px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Imprimir
            </button>
        </div>

        <!-- Instructions -->
        <div class="bg-gray-50 p-4 sm:p-6 rounded-lg w-full max-w-2xl">
            <h4 class="font-semibold text-gray-900 mb-3">¿Cómo usar el código QR?</h4>
            <ol class="list-decimal list-inside space-y-2 text-gray-700 text-sm sm:text-base">
                <li>Descarga o imprime el código QR</li>
                <li>Colócalo en las mesas de tu restaurante</li>
                <li>Los clientes escanean con su teléfono</li>
                <li>¡Listo! Verán tu carta digital al instante</li>
            </ol>
        </div>
    </div>
</div>
@endsection
