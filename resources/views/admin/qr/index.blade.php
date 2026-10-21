<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Código QR') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-center mb-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Código QR de tu Carta</h3>
                        <p class="text-gray-600">Imprime este código y colócalo en tus mesas para que los clientes puedan ver tu carta digital.</p>
                    </div>

                    <div class="flex flex-col items-center gap-8">
                        <!-- QR Code Display -->
                        <div class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-100">
                            <img src="{{ route('admin.qr.generate') }}" alt="Código QR de la carta" class="w-64 h-64">
                        </div>

                        <!-- URL Info -->
                        <div class="bg-blue-50 p-4 rounded-lg w-full max-w-md">
                            <p class="text-sm text-blue-800 font-medium mb-1">URL del código QR:</p>
                            <p class="text-blue-600 break-all">{{ $menuUrl }}</p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4 w-full max-w-md">
                            <a href="{{ route('admin.qr.download') }}" class="flex-1 flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V6a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V18a2 2 0 01-2 2z" />
                                </svg>
                                Descargar PNG
                            </a>
                            
                            <button onclick="window.print()" class="flex-1 flex items-center justify-center gap-2 px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Imprimir
                            </button>
                        </div>

                        <!-- Instructions -->
                        <div class="bg-gray-50 p-6 rounded-lg w-full max-w-2xl">
                            <h4 class="font-semibold text-gray-900 mb-3">¿Cómo usar el código QR?</h4>
                            <ol class="list-decimal list-inside space-y-2 text-gray-700">
                                <li>Descarga o imprime el código QR</li>
                                <li>Colócalo en las mesas de tu restaurante</li>
                                <li>Los clientes escanean con su teléfono</li>
                                <li>¡Listo! Verán tu carta digital al instante</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>