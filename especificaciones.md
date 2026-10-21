* Stack tecnico
- Framework : Laravel, version 13
- Front End : Plantillas Blade, Tailwind css, JS.

[Reglas]
- Preguntar siempre antes de hacer algun cambio.
- Leer la documentacion oficial de Laravel, https://laravel.com/framework/docs
- Generar test unitarios.
- Generar documentacion.  

* Proyecto: Crear un menu digital, totalmente responsive y especialmente adaptado para resoluciones moviles.

[Landing page]
- Landing page contendra los productos, las categorias y los alergenos.
- Los productos se mostraran en la landing page de forma responsive y adptada a resoluciones de moviles para que se ve de forma agradable y tenga la informacion bien organizada.
- Los productos pertenzeran a una categoría
- Los productos podran tener varios alergenos a la vez. 
- En la landing page se podra filtar por los alergenos quitando los productos que lleven estos alergenos, dicho filtro se hara de forma asincrono sin tener que recargar la pagina.
- En la landing page se podra filtrar por categorias al pulsar en dicha categoria x, solo se mostraran los productos de esa categoria, se hara de forma asincrona sin tener que recargar la pagina.  

[Gestion]
- La gestion sera la parte donde se puedran Crear, actualizar y eliminar tanto productos, categorias y Alergenos. (CRUD)
- Enlazar categoria y productos para que el filtro pueda funcionar adecuadamente.
- Asignar Alergenos con productos para que el filtro pueda funcionar adecuadamente.
- La parte de la gestion tambien se hara de forma responsiva tambien pensado en moviles. 
- Apatado de codigo Qr, se pide generar un codigo Qr de la url de la landing page, para que se pueda ver el menu de forma rapida y agil 

[Seguridad]
- Analiza posibles fallos de seguirdad y crea una auditoria en base a los ataques mas comunes.
- Solo habra un usuario administrador, el cual administra el sitio, añadiendo, modificando y eliminando contenido, el sitio no esta pensado para registro de usuarios publico.
- La web no esta pensada para multiples usuarios solo para el Administrador.
- Implementar medidas de seguridad para que los atacantes no puedan hacer fuerza bruta e implementar las medidas de seguridad necesarias para la autenticacion con doble factor.


[Completado]
[Landing page]
- Landing page, con productos, alergenos y categorias. Status = OK
- Landing page, filtros de categorias y alergenos. Status = OK
- Landinng page responsive. Status = OK
[Gestion]
- CRUD de Productos, Categorias, Alergenos. Status = OK
- CRUD de Qr. Status = Pendiente.
- Enlazar Productos, Categorias y Alergenos. Status = OK 
[Seguridad]
- Solo Administrador pede acceder . Status = OK
- No hay forma de que un usuario se registre publicamente. Status = OK.
- Analisis de Amenazas y posibles fallos de seguridad. Status = Pendiente.
- Implementacion de medidas de seguiridad tales como un bloqueador por intentos fallidos u otros metodos para segurizar el entorno y que no sea vulnerable. Status = Pendiente.
