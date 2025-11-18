/**
 * DataTables - Configuración local
 * Reemplaza los CDN por assets compilados localmente
 */

// Core DataTables
import DataTable from 'datatables.net-bs4';

// Buttons extension
import 'datatables.net-buttons-bs4';
import 'datatables.net-buttons/js/buttons.html5.mjs';
import 'datatables.net-buttons/js/buttons.print.mjs';

// Dependencies for export
import JSZip from 'jszip';
import pdfMake from 'pdfmake/build/pdfmake';
import pdfFonts from 'pdfmake/build/vfs_fonts';

// Setup pdfMake fonts
pdfMake.vfs = pdfFonts.pdfMake ? pdfFonts.pdfMake.vfs : pdfFonts.vfs;

// Make JSZip available globally for DataTables buttons
window.JSZip = JSZip;

// Export DataTable for use
export default DataTable;
