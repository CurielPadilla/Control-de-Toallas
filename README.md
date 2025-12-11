
# Sistema de Control de Toallas con Reconocimiento Facial (PHP + MySQL + face-api.js)

Este paquete incluye una aplicación web para gestionar préstamos y devoluciones de toallas con **reconocimiento facial por webcam** (en el navegador con `face-api.js`).

**Características:**
- Registro de asociados (nombre, membresía) con captura de **muestras faciales**.
- **Reconocimiento facial** en navegador para identificar al asociado.
- Registro de **préstamos** y **devoluciones** por tipo de toalla (BC, BG, AA, TF) con **hora**.
- **Historial** por asociado y general.
- **Exportación** a **CSV** (servidor) y a **Excel/PDF** (cliente) usando librerías por CDN (SheetJS/jsPDF).
- Imágenes guardadas como **archivos** en `/faces/{member_id}`.

---

## Requisitos
- **PHP 8+** con extensión PDO MySQL habilitada.
- **MySQL 8+** (o compatible).
- Servidor web (Apache/Nginx). Para pruebas locales, también sirve PHP built-in server.
- Navegador moderno con acceso a webcam y **HTTPS** (o `http://localhost`).

## Instalación rápida
1. Cree la base de datos y tablas:
   ```bash
   mysql -u root -p < database.sql
   ```
2. Configure credenciales en `api/db.php` (`$dsn`, `$user`, `$pass`).
3. Asegure permisos de escritura para la carpeta `faces/` por el usuario del servidor web:
   ```bash
   chmod -R 750 faces
   ```
4. Inicie un servidor local en la carpeta raíz del proyecto:
   ```bash
   php -S localhost:8080 -t public
   ```
   > Si usa Apache/Nginx, sirva `public/` como raíz web y `api/` como endpoints PHP.
5. Abra `http://localhost:8080`.

## Modelos de face-api.js
Este proyecto usa `face-api.js` desde CDN y **modelos desde CDN** para simplificar. Si desea usar modelos locales, descargue los archivos a `models/` y cambie `MODEL_URL` en `public/js/face.js`.

*URLs de modelos recomendadas (TinyFaceDetector, Landmark, Recognition):*
```
https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights/
```
Archivos usuales:
- `tiny_face_detector_model-weights_manifest.json`
- `face_landmark_68_model-weights_manifest.json`
- `face_recognition_model-weights_manifest.json`

## Exportación PDF/Excel
- **Excel**: se usa **SheetJS (xlsx)** por CDN en `admin.html` para generar `.xlsx` en el navegador.
- **PDF**: se usa **jsPDF** + `autoTable` por CDN en `admin.html`.
- **CSV (servidor)**: endpoint `/api/reports/history?format=csv`.

## Estructura
```
/ (raíz)
  database.sql
  /api
    db.php
    members.php
    faces.php
    events.php
    towel_types.php
    reports.php
  /faces             # almacena imágenes de rostros por miembro
    .htaccess
  /models            # (opcional) colocar modelos locales
    README.txt
  /public            # raíz web
    index.html
    register.html
    operate.html
    admin.html
    /css
      styles.css
    /js
      face.js
      register.js
      operate.js
      admin.js
```

## Notas de seguridad
- Deshabilite listado de directorios en `/faces` (incluido `.htaccess`).
- Valide orígenes permitidos si expone `/api` públicamente.
- Informe a los asociados sobre el uso de **datos biométricos**.

## Créditos
- Reconocimiento facial en navegador con **face-api.js** (MIT).
- Exportación cliente: **SheetJS** y **jsPDF**.

