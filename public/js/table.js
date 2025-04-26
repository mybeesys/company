pdfMake.fonts = {
    Arial: {
        normal: "ARIAL.TTF",
        bold: "ARIALBD.TTF",
        italics: "ARIALI.TTF",
        bolditalics: "ARIALBI.TTF",
    },
};

$("#kt_app_sidebar_toggle").on("click", function () {
    if (typeof dataTable !== "undefined" && dataTable) {
        dataTable.columns.adjust().draw();
    }
});
//columnsToReverseInArabic arabic words To Reverse In Arabic lang
//columnsToReverse arabic columns To Reverse In English lang
function exportButtons(
    columns,
    id,
    lang,
    columnsToReverse = [],
    columnsToReverseInArabic = [],
    pageSize = "A4",
    PageTable = null,
    pageDataTable = null,
    activePdf = true,
    activeExcel = true,
    activePrint = true
) {
    if (pageDataTable) {
        dataTable = pageDataTable;
    }
    if (PageTable) {
        table = PageTable;
    }

    new $.fn.dataTable.Buttons(table, {
        buttons: [
            {
                extend: "excelHtml5",
                exportOptions: {
                    columns: columns,
                },
            },
            {
                extend: "pdfHtml5",
                messageTop: " ",
                orientation: "landscape",
                pageSize: pageSize,
                text: "PDF",
                exportOptions: {
                    orthogonal: "PDF",
                    columns: columns.reverse(),
                },

                customize: function (doc) {
                    // Set default font to Arial
                    doc.defaultStyle.font = "Arial";
                    doc.defaultStyle.alignment = "center";
                    // Function to reverse the text in specific columns
                    function reverseColumnText(doc) {
                        for (
                            var i = 1;
                            i < doc.content[2].table.body.length;
                            i++
                        ) {
                            for (
                                var j = 0;
                                j < doc.content[2].table.body[i].length;
                                j++
                            ) {
                                if (columnsToReverse.includes(j)) {
                                    doc.content[2].table.body[i][j]["text"] =
                                        doc.content[2].table.body[i][j]["text"]
                                            .split(" ")
                                            .reverse()
                                            .join(" ");
                                }
                            }
                        }
                    }

                    // Function to reverse the text in specific columns when switching lang
                    function reverseColumnTextInArabic(doc) {
                        if (
                            doc.content[2] &&
                            doc.content[2].table &&
                            doc.content[2].table.body
                        ) {
                            for (
                                var i = 1;
                                i < doc.content[2].table.body.length;
                                i++
                            ) {
                                for (
                                    var j = 0;
                                    j < doc.content[2].table.body[i].length;
                                    j++
                                ) {
                                    // Check if the column is in columnsToReverseInArabic and if the text is available
                                    if (
                                        columnsToReverseInArabic.includes(j) &&
                                        doc.content[2].table.body[i][j] &&
                                        doc.content[2].table.body[i][j]["text"]
                                    ) {
                                        // Only attempt to reverse text if it exists
                                        doc.content[2].table.body[i][j][
                                            "text"
                                        ] = doc.content[2].table.body[i][j][
                                            "text"
                                        ]
                                            .split(" ")
                                            .reverse()
                                            .join(" ");
                                    }
                                }
                            }
                        }
                    }

                    // Function to reverse the headers and rows if language is Arabic
                    function reverseForArabic(doc) {
                        if (
                            doc.content[2] &&
                            doc.content[2].table &&
                            doc.content[2].table.body &&
                            doc.content[2].table.body.length > 0
                        ) {
                            // Reverse all header texts
                            for (
                                var i = 0;
                                i < doc.content[2].table.body[0].length;
                                i++
                            ) {
                                if (doc.content[2].table.body[0][i]["text"]) {
                                    doc.content[2].table.body[0][i]["text"] =
                                        doc.content[2].table.body[0][i]["text"]
                                            .split(" ")
                                            .reverse()
                                            .join(" ");
                                }
                            }

                            // Reverse header cells
                            var headerCells = doc.content[2].table.body[0];
                            headerCells.reverse();

                            // Reverse each row's cells in the body
                            for (
                                var i = 1;
                                i < doc.content[2].table.body.length;
                                i++
                            ) {
                                var rowCells = doc.content[2].table.body[i];
                                rowCells.reverse();
                            }
                        }
                    }

                    function centerTable(doc) {
                        doc.content[2].table.widths = "*"
                            .repeat(doc.content[2].table.body[0].length)
                            .split("");

                        doc.content[2].margin = [-25, 0, -25, 0];
                        doc.content[2].alignment = "center";
                    }

                    centerTable(doc);

                    reverseColumnText(doc);

                    if (lang === "ar") {
                        reverseForArabic(doc);
                        reverseColumnTextInArabic(doc);
                    }
                },
            },
            {
                extend: "print",
                exportOptions: {
                    columns: columns.reverse(),
                },
                customize: function (win) {
                    var headers = $(win.document.body).find("thead tr");
                    if (lang == "ar") {
                        headers.each(function () {
                            var headerCells = $(this).children("th").toArray();
                            $(this).empty().append($(headerCells.reverse())); // Reverse the order of header cells and append them back
                        });
                        var bodyRows = $(win.document.body).find("tbody tr");
                        bodyRows.each(function () {
                            var bodyCells = $(this).children("td").toArray();
                            $(this).empty().append($(bodyCells.reverse())); // Reverse the order of body cells and append them back
                        });
                    }
                },
            },
        ],
    })
        .container()
        .appendTo($(`${id}_buttons`));

    const exportButtons = $(`${id}_export_menu [data-kt-export]`);
    exportButtons.on("click", function (e) {
        e.preventDefault();
        const exportValue = $(this).attr("data-kt-export");
        if (exportValue === "pdf" && activePdf) {
            $(".dt-buttons .buttons-" + exportValue).click();
        } else if (exportValue === "excel" && activeExcel) {
            $(".dt-buttons .buttons-" + exportValue).click();
        } else if (exportValue === "print" && activePrint) {
            $(".dt-buttons .buttons-" + exportValue).click();
        }
    });
}

function handleSearchDatatable(pageDataTable) {
    if (pageDataTable) {
        dataTable = pageDataTable;
    }

    const filterSearch = $('[data-kt-filter="search"]');
    filterSearch.on("keyup", function (e) {
        dataTable.search(e.target.value).draw();
    });
}
