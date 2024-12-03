import React from "react";
import pdfMake from 'pdfmake/build/pdfmake';
import pdfFonts from 'pdfmake/build/vfs_fonts';

// Set the VFS (virtual file system) from the pdfFonts package




export  const generatePDF = () => {
    pdfMake.vfs = pdfFonts.pdfMake.vfs;
    const documentDefinition = {
      content: [
        // Header
        { text: 'Purchase Order', style: 'header' },
        {
          columns: [
            {
              width: 'auto',
              text: [
                { text: 'TO:', bold: true },
                '\n',
                'دازلا كرا',  // Arabic text
                '\n',
                'USA',
                '\n',
                'Email: Zulfiqar@aol.com',
                '\n',
                'FOB: FOBDestination'
              ],
            },
            {
              width: 'auto',
              text: [
                { text: 'PO #: 16816', bold: true },
                '\n',
                'Date Created: 25/11/2024',
                '\n',
                'Soucolat',
                '\n',
                'Kuwait'
              ],
              alignment: 'right',
            }
          ]
        },

        // Table of items
        {
          table: {
            widths: [30, 150, '*', 40, 60, 60],
            body: [
              // Table headers
              [
                { text: '#', bold: true, alignment: 'center' },
                { text: 'Item Description', bold: true, alignment: 'center' },
                { text: 'Quantity', bold: true, alignment: 'center' },
                { text: 'Unit', bold: true, alignment: 'center' },
                { text: 'Price', bold: true, alignment: 'center' },
                { text: 'Total Price', bold: true, alignment: 'center' }
              ],
              // Table data rows
              [
                '1',
                'water',
                '100.0000',
                'u1',
                'SAR 500.0000',
                'SAR 50000.0000'
              ]
            ]
          }
        },

        // Totals
        {
          columns: [
            {
              width: 'auto',
              text: [
                { text: 'Sub total: ', bold: true },
                'SAR 50000.00',
                '\n',
                { text: 'Tax: ', bold: true },
                'SAR 0.00',
                '\n',
                { text: 'Shipping & Handling: ', bold: true },
                'SAR 0.00',
                '\n',
                { text: 'Misc. Charges: ', bold: true },
                'SAR 0.00',
                '\n',
                { text: 'Grand Total: ', bold: true },
                'SAR 50000.00'
              ]
            },
            {
              width: 'auto',
              text: [
                '\n\n',
                { text: 'Buyer Signature: ', bold: true },
                '____________________',
                '\n',
                { text: 'Date: ', bold: true },
                '____________________'
              ]
            }
          ]
        }
      ],
      styles: {
        header: {
          fontSize: 18,
          bold: true,
          alignment: 'center',
          margin: [0, 0, 0, 10]
        },
        tableHeader: {
          bold: true,
          alignment: 'center',
          fillColor: '#f2f2f2',
          margin: [0, 5, 0, 5]
        },
        tableBody: {
          alignment: 'center',
          margin: [0, 5, 0, 5]
        }
      },
      defaultStyle: {
        font: 'Roboto', // For standard font, use 'Roboto' or any supported font
      }
    };

    // Create and download the PDF
    pdfMake.createPdf(documentDefinition).download('purchase_order_16816.pdf');
  };