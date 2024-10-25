pdfMake.fonts = {
    Arial: {
        normal: 'ARIAL.TTF',
        bold: 'ARIALBD.TTF',
        italics: 'ARIALI.TTF',
        bolditalics: 'ARIALBI.TTF'
    }
};

function exportButtons(columns, id, lang, columnsToReverse, columnsToReverseInArabic = []) {
    
    new $.fn.dataTable.Buttons(table, {
        buttons: [{
            extend: 'excelHtml5',
            exportOptions: {
                columns: columns
            },
        },
        {
            extend: 'pdfHtml5',
            messageTop: 'message',
            text: "PDF",
            exportOptions: {
                orthogonal: "PDF",
                columns: columns.reverse(),
            },

            customize: function (doc) {
                // Set default font to Arial
                doc.defaultStyle.font = 'Arial';
                doc.defaultStyle.alignment = 'center'
                // Function to reverse the text in specific columns
                function reverseColumnText(doc) {
                    for (var i = 1; i < doc.content[2].table.body.length; i++) {
                        for (var j = 0; j < doc.content[2].table.body[i].length; j++) {
                            if (columnsToReverse.includes(j)) {
                                doc.content[2].table.body[i][j]['text'] = doc.content[2].table.body[i][j]['text'].split(' ').reverse().join(' ');
                            }
                        }
                    }
                }

                // Function to reverse the text in specific columns when switching lang
                function reverseColumnTextInArabic(doc) {
                    for (var i = 1; i < doc.content[2].table.body.length; i++) {
                        for (var j = 0; j < doc.content[2].table.body[i].length; j++) {
                            if (columnsToReverseInArabic.includes(j)) {
                                doc.content[2].table.body[i][j]['text'] = doc.content[2].table.body[i][j]['text'].split(' ').reverse().join(' ');
                            }
                        }
                    }
                }

                // Function to reverse the headers and rows if language is Arabic
                function reverseForArabic(doc) {
                    // Reverse all header texts
                    for (var i = 0; i < doc.content[2].table.body[0].length; i++) {
                        doc.content[2].table.body[0][i]['text'] = doc.content[2].table.body[0][i]['text'].split(' ').reverse().join(' ');
                    }
                    // Reverse header cells
                    var headerCells = doc.content[2].table.body[0];
                    headerCells.reverse();

                    // Reverse each row's cells in the body
                    for (var i = 1; i < doc.content[2].table.body.length; i++) {
                        var rowCells = doc.content[2].table.body[i];
                        rowCells.reverse();
                    }
                }
                function centerTable(doc) {
                    doc.content[2].table.widths = '*'.repeat(doc.content[2].table.body[0].length).split('');

                    doc.content[2].margin = [-25, 0, -25, 0];
                    doc.content[2].alignment = 'center';
                }

                centerTable(doc);

                reverseColumnText(doc);

                if (lang === 'ar') {
                    reverseForArabic(doc);
                    reverseColumnTextInArabic(doc)
                }
            }
        },
        {
            extend: 'print',
            exportOptions: {
                columns: columns.reverse()
            },
            customize: function (win) {
                var headers = $(win.document.body).find('thead tr');
                if (lang == 'ar') {

                    headers.each(function () {
                        var headerCells = $(this).children('th').toArray();
                        $(this).empty().append($(headerCells.reverse())); // Reverse the order of header cells and append them back
                    });
                    var bodyRows = $(win.document.body).find('tbody tr');
                    bodyRows.each(function () {
                        var bodyCells = $(this).children('td').toArray();
                        $(this).empty().append($(bodyCells.reverse())); // Reverse the order of body cells and append them back
                    });
                }
            }
        },
        ]
    }).container().appendTo($(`${id}_buttons`));

    const exportButtons = $(`${id}_export_menu [data-kt-export]`);
    exportButtons.on('click', function (e) {
        e.preventDefault();
        const exportValue = $(this).attr('data-kt-export');
        $('.dt-buttons .buttons-' + exportValue).click();
    });
};

function handleSearchDatatable() {
    const filterSearch = $('[data-kt-filter="search"]');
    filterSearch.on('keyup', function (e) {
        dataTable.search(e.target.value).draw();
    });
};