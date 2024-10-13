pdfMake.fonts = {
    Arial: {
        normal: 'ARIAL.TTF',
        bold: 'ARIALBD.TTF',
        italics: 'ARIALI.TTF',
        bolditalics: 'ARIALBI.TTF'
    }
};

function exportButtons(columns, id) {
    new $.fn.dataTable.Buttons(table, {
        buttons: [{
                extend: 'excelHtml5',
                exportOptions: {
                    columns: columns
                },
            },
            {
                extend: 'pdf',
                exportOptions: {
                    columns: columns
                },
                customize: function(doc) {
                    doc.defaultStyle.font = 'Arial';
                },
            },
            {
                extend: 'print',
                exportOptions: {
                    columns: columns
                },
            },
        ]
    }).container().appendTo($(`${id}_buttons`));

    const exportButtons = $(`${id}_export_menu [data-kt-export]`);
    exportButtons.on('click', function(e) {
        e.preventDefault();
        const exportValue = $(this).attr('data-kt-export');
        $('.dt-buttons .buttons-' + exportValue).click();
    });
};

function handleSearchDatatable() {
    const filterSearch = $('[data-kt-filter="search"]');
    filterSearch.on('keyup', function(e) {
        dataTable.search(e.target.value).draw();
    });
};