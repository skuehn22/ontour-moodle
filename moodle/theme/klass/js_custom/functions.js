// The plugin function for adding a new filtering routine
$.fn.dataTableExt.afnFiltering.push(
    function(oSettings, aData, iDataIndex){

        if($("#dateStart").val() === ""){

            var dateStart = parseDateValue("01/01/1970");
            var dateEnd = parseDateValue("31/12/2030");
            var evalDate= parseDateValue("01/01/1970");

        }else{

            var dateStart = parseDateValue($("#dateStart").val());
            var dateEnd = parseDateValue($("#dateEnd").val());
            // aData represents the table structure as an array of columns, so the script access the date value
            // in the first column of the table via aData[0]
            var evalDate= parseDateValue(aData[5]);

        }

        if (evalDate >= dateStart && evalDate <= dateEnd) {
            return true;
        }
        else {
            return false;
        }

    });

jQuery.extend( jQuery.fn.dataTableExt.oSort, {
    "date-uk-pre": function ( a ) {
        var ukDatea = a.split('/');
        return (ukDatea[2] + ukDatea[1] + ukDatea[0]) * 1;
    },

    "date-uk-asc": function ( a, b ) {
        return ((a < b) ? -1 : ((a > b) ? 1 : 0));
    },

    "date-uk-desc": function ( a, b ) {
        return ((a < b) ? 1 : ((a > b) ? -1 : 0));
    }
} );


// Function for converting a mm/dd/yyyy date value into a numeric string for comparison (example 08/12/2010 becomes 20100812
function parseDateValue(rawDate) {
    var dateArray= rawDate.split("/");
    var parsedDate= dateArray[2] + dateArray[1] + dateArray[0];
    return parsedDate;
}


$(function() {

    //alert("test");

    $('#commentTable thead tr')
        .clone(true)
        .addClass('filters')
        .appendTo('#commentTable thead');

    // Implements the dataTables plugin on the HTML table
    var table = $('#commentTable').DataTable({
        orderCellsTop: true,
        fixedHeader: true,
        aaSorting: [[0,'desc']],
        autoWidth: false,  // add this line
        columnDefs: [
            {
                targets: 0,
                width: '50px'
            }
        ],
        initComplete: function () {
            var api = this.api();

            api.columns().eq(0).each(function(colIdx) {
                // Set the header cell to contain the input element
                var cell = $('.filters th').eq($(api.column(colIdx).header()).index());
                var title = $(cell).text();
                $(cell).html( '<input type="text" class="filter-input" placeholder="'+title+'" />' );

                // On every keypress in this input
                $('input', $('.filters th').eq($(api.column(colIdx).header()).index()) )
                    .off('keyup change')
                    .on('keyup change', function (e) {
                        e.stopPropagation();
                        // Get the search value
                        $(this).attr('title', $(this).val());
                        var regexr = '({search})'; //$(this).parents('th').find('select').val();
                        var cursorPosition = this.selectionStart;
                        // Search the column for that value
                        api
                            .column(colIdx)
                            .search((this.value != "") ? regexr.replace('{search}', '((('+this.value+')))') : "", this.value != "", this.value == "")
                            .draw();
                        $(this).focus()[0].setSelectionRange(cursorPosition, cursorPosition);
                    });
            });
        },
    });

    // The dataTables plugin creates the filtering and pagination controls for the table dynamically, so these
    // lines will clone the date range controls currently hidden in the baseDateControl div and append them to
    // the feedbackTable_filter block created by dataTables
    // $dateControls= $("#baseDateControl").children("div").clone();
    // $("#feedbackTable_filter").prepend($dateControls);

    // Implements the jQuery UI Datepicker widget on the date controls
    $('.datepicker').datepicker(
        {
            showOn: 'button',
            buttonImage: '../../theme/klass/assets/images/calendar.gif',
            buttonImageOnly: true,
            dateFormat: 'dd/mm/yy'

        }
    );

    // Create event listeners that will filter the table whenever the user types in either date range box or
    // changes the value of either box using the Datepicker pop-up calendar
    $("#dateStart").keyup(function() {
        table.draw();
    });
    $("#dateStart").change(function() {
        table.draw();
    });
    $("#dateEnd").keyup(function() {
        table.draw();
    });
    $("#dateEnd").change(function() {
        table.draw();
    });

    $('a.toggle-vis').on('click', function (e) {
        e.preventDefault();

        // Get the column API object
        var column = table.column($(this).attr('data-column'));

        // Toggle the visibility
        column.visible(!column.visible());
    });

});

$(document).ready(function() {

    init();

});

function init(){



    $( ".btn-danger" ).removeClass( "ui-button ui-corner-all ui-widget" );
    $( ".btn-warning" ).removeClass( "ui-button ui-corner-all ui-widget" );

    $(".del-class").button().click(function(){



        $(".class_span").html('<strong> '+$(this).data('bid')+' </strong>');
        $("#user_delete_id").val($(this).data('userid'));
        $("#bid_delete_id").val($(this).data('bid'));

    });
}





