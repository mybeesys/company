pdfMake.fonts = {
    Arial: {
        normal: 'ARIAL.TTF',
        bold: 'ARIALBD.TTF',
        italics: 'ARIALI.TTF',
        bolditalics: 'ARIALBI.TTF'
    }
};

function exportButtons() {
    new $.fn.dataTable.Buttons(table, {
        buttons: [{
                extend: 'excelHtml5',
                exportOptions: {
                    columns: [0, 1, 2, 3]
                },
            },
            {
                extend: 'pdf',
                exportOptions: {
                    columns: [0, 1, 2, 3]
                },
                customize: function(doc) {
                    doc.defaultStyle.font = 'Arial';
                },
            },
            {
                extend: 'print',
                exportOptions: {
                    columns: [0, 1, 2, 3]
                },
            },
        ]
    }).container().appendTo($('#kt_role_table_buttons'));

    const exportButtons = $('#kt_role_table_export_menu [data-kt-export]');
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