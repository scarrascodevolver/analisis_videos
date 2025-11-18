/**
 * DataTables - Configuración local
 * Reemplaza los CDN por assets compilados localmente
 * Usa el jQuery global cargado via CDN
 */

// Dependencies for export (must be loaded first)
import JSZip from 'jszip';
import pdfMake from 'pdfmake/build/pdfmake';
import pdfFonts from 'pdfmake/build/vfs_fonts';

// Make dependencies available globally BEFORE loading DataTables
window.JSZip = JSZip;
pdfMake.vfs = pdfFonts.pdfMake ? pdfFonts.pdfMake.vfs : pdfFonts.vfs;
window.pdfMake = pdfMake;

// Use global jQuery (loaded from CDN in layout)
const $ = window.jQuery;

// Core DataTables - attach to global jQuery
import DataTable from 'datatables.net';
DataTable($);

// Bootstrap 4 styling
import DataTableBs4 from 'datatables.net-bs4';
DataTableBs4($);

// Buttons extension
import Buttons from 'datatables.net-buttons';
Buttons($);

import ButtonsBs4 from 'datatables.net-buttons-bs4';
ButtonsBs4($);

// Button types
import 'datatables.net-buttons/js/buttons.html5.mjs';
import 'datatables.net-buttons/js/buttons.print.mjs';
