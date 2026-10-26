# Documentación de MenúDigital

Índice de la documentación del proyecto. Lea `ARQUITECTURA.md` para entender el
sistema, `MOVIL.md` para el criterio de adaptabilidad a teléfono (es el requisito
transversal del proyecto) y `PRUEBAS.md` para saber qué está verificado y cómo.

| Documento | Qué contiene | Cuándo leerlo |
|---|---|---|
| [ARQUITECTURA.md](ARQUITECTURA.md) | Stack, modelo de datos, rutas y controladores, carta pública con y sin JavaScript, generación del QR | Para tocar código: modelos, controladores o rutas |
| [MOVIL.md](MOVIL.md) | Criterio *mobile-first*: viewport, *safe areas*, barra pegajosa, *drawer* del panel, tarjetas frente a tabla, impresión del QR | Para cualquier cambio de interfaz |
| [PRUEBAS.md](PRUEBAS.md) | Suite de feature tests (103), qué fija cada test y las puertas de calidad (Pint, PHPStan) | Antes de subir un cambio |
| [DESARROLLO.md](DESARROLLO.md) | Instalación, base de datos, *seeders*, credenciales de desarrollo, comandos y despliegue | Para arrancar el proyecto en una máquina nueva |
| [ESTADO.md](ESTADO.md) | Trazabilidad requisito → implementación, con lo pendiente y la deuda conocida | Para saber qué está hecho y qué no |
| [PROGRESO-AUDITORIA.md](PROGRESO-AUDITORIA.md) | Bitácora de la auditoría de seguridad y de las correcciones, bloque a bloque | Para el detalle de seguridad y decisiones tomadas |

## Verificación rápida de todo

```bash
composer install && npm install && npm run build
php artisan migrate:fresh --seed
php artisan test                                             # 103 passing · 434 aserciones
vendor/bin/pint --test                                       # 0 incidencias de estilo
vendor/bin/phpstan analyse --no-progress --memory-limit=1G   # 0 errores (nivel 5)
```

## Reglas del proyecto (de `especificaciones.md`)

1. Preguntar antes de cambiar el comportamiento de producto.
2. Consultar la documentación oficial de Laravel (<https://laravel.com/framework/docs>).
3. Generar tests.
4. Generar documentación: **cada decisión de calado se refleja en esta carpeta**.
