@extends('layouts.app')

@section('content')
<div class="card-body py-3">
   <div class="table-responsive">									
	<table id="CategoryTable"  class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
    <thead>
        <tr>
            <th></th>
            <th class="min-w-150px">Arabic Name</th>
            <th class="min-w-150px">English Name</th>
            <th class="min-w-150px">Order</th>
            <th class="min-w-150px">ِActions</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>
</div>
</div>
@endsection

<script src="assets/js/jquery-3.6.4.min.js"></script>
<script>
/*$(document).ready(function() {
    var table = $('#CategoryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('categoryList') }}",  // Fetch parent table data
        columns: [
            {
                className: 'details-control',  // Add a column for expanding the row
                orderable: false,
                data: null,
                defaultContent: '<button class="btn">+</button>'
            },
        
            { data: 'name_ar', name: 'name_ar' },
            { data: 'name_en', name: 'name_en' },
            { data: 'order', name: 'order' },
            { data: 'id', name: 'id' },
        ],
        select: {
        style:    'os',
        selector: 'td:not(:first-child)'
    },
        order: [[1, 'asc']]
    });

    // Add event listener for opening and closing details
    $('#CategoryTable tbody').on('click', 'td.details-control', function() {
        var tr = $(this).closest('tr');
        var row = table.row(tr);
        
        if (row.child.isShown()) {
            // Close the nested row
            destroyChild(row);
        tr.removeClass('shown');
        } else {
            createChild(row);
        tr.addClass('shown');
        }
    });
});
function createChild ( row ) 
    {
    // This is the table we'll convert into a DataTable
    var table = $('<table class="display" width="100%"/>');
 
    // Display it the child row
    row.child( table ).show();

    var categoryId = row.data().id;
    var newUrl = "{{ route('subcategoryList', ['id' => ':id']) }}";
    newUrl = newUrl.replace(':id', categoryId);

    // Initialise as a DataTable
    var usersTable = table.DataTable( {
        processing: true,
        serverSide: true,
        ajax: newUrl,  // Fetch parent table data
        columns: [ {
            name: "name_ar",
            data: "name_ar"
        },
        {
            name: "name_en",
            data: "name_en"
        },
        {
            name: "order",
            data: "order"
        },
       {
            name: "id",
            data: "id"
        }
    ],
        order: [[1, 'asc']]
    });
    }

function destroyChild(row) {
    var table = $("table", row.child());
    table.detach();
    table.DataTable().destroy();
 
    // And then hide the row
    row.child.hide();
}
*/
/////



function createChild(row) {
	var rowData = row.data();

	// This is the table we'll convert into a DataTable
	var table = $('<table class="display" width="100%"/>');

	// Display it the child row
	row.child(table).show();

    var categoryId = row.data().id;
    var newUrl = "{{ route('subcategoryList', ['id' => ':id']) }}";
    newUrl = newUrl.replace(':id', categoryId);

	// Child row DataTable configuration, always passes the parent row's id to server
	var usersTable = table.DataTable({
		pageLength: 5,
		ajax: {
			url: newUrl,
			data: function(d) {
				d.site = rowData.id;
			}
		},
		columns: [
			{  data: "name_ar" },
			{  data: "name_en" },
			{  data: "order" }
		],
		select: true,
	});

}

function updateChild(row) {
	$("table", row.child())
		.DataTable()
		.ajax.reload();
}

function destroyChild(row) {
	// Remove and destroy the DataTable in the child row
	var table = $("table", row.child());
	table.detach();
	table.DataTable().destroy();

	// And then hide the row
	row.child.hide();
}

$(document).ready(function() {

	var siteTable = $("#CategoryTable").DataTable({
		ajax: "{{ route('categoryList') }}",
		order: [1, "asc"],
		columns: [
			{
				className: "details-control",
				orderable: false,
				data: null,
				defaultContent: "",
				width: "10%"
			},
			{ data: "name_ar" },
            { data: "name_en" },
            { data: "order" },
            { data: "id" },
	
		],
		select: {
			style: "os",
			selector: "td:not(:first-child)"
		},
	});

	// Add event listener for opening and closing details
	$("#CategoryTable tbody").on("click", "td.details-control", function() {
		var tr = $(this).closest("tr");
		var row = siteTable.row(tr);

		if (row.child.isShown()) {
			// This row is already open - close it
			destroyChild(row);
			tr.removeClass("shown");
		} else {
			// Open this row
			createChild(row);
			tr.addClass("shown");
		}
	});
});

</script>
