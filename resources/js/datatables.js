/**
 * DataTables - Configuración local
 * Reemplaza los CDN por assets compilados localmente
 */

// Dependencies for export (must be loaded first)
import JSZip from 'jszip';
import pdfMake from 'pdfmake/build/pdfmake';
import pdfFonts from 'pdfmake/build/vfs_fonts';

// Make dependencies available globally BEFORE loading DataTables
window.JSZip = JSZip;
pdfMake.vfs = pdfFonts.pdfMake ? pdfFonts.pdfMake.vfs : pdfFonts.vfs;
window.pdfMake = pdfMake;

// Import jQuery and make it available
import jQuery from 'jquery';
window.$ = window.jQuery = jQuery;

// Core DataTables - will attach to jQuery
import 'datatables.net';
import 'datatables.net-bs4';

// Buttons extension
import 'datatables.net-buttons';
import 'datatables.net-buttons-bs4';
import 'datatables.net-buttons/js/buttons.html5.mjs';
import 'datatables.net-buttons/js/buttons.print.mjs';
