$(document).ready(function () {

    //loadDataTable();
    
    $("#booking").submit(function(e){

        if( $(".klassenname0").val() == $(".klassenname1").val()){
            e.preventDefault();
            alert("Doppelter Klassenname ist nicht gestattet - 1");
        }

        if( $(".klassenname0").val() == $(".klassenname2").val()){
            e.preventDefault();
            alert("Doppelter Klassenname ist nicht gestattet - 2");
        }

    });


    var counter = 1;
    var index = 0;

    $("#addrow").on("click", function () {

        counter++;
        index++;

        var newRow = $("<tr id='name_row"+counter+"'>");
        var cols = "";
        cols += '<td class="p-0 pb-3" style="border: none!important;"> <input type="text" style="width: 220px!important;" name="school_classes['+index+']"  id="school_classes['+index+']" class="form-control student-list klassenname'+index+'" value="Klasse '+counter+'"/></td>';
        cols += '<td class="p-0 pb-3 pl-2" style="border: none!important;"><input type="button" class="ibtnDel btn btn-md btn-cross" data-delete="'+counter+'"  value="x"></td>';
        newRow.append(cols);
        $("table.v-code-block").append(newRow);

        var newRow = $("<tr id='arr_row"+counter+"'>");
        var cols = "";
        cols += '<td colspan="2" class="p-0 pb-3" style="border: none!important;"> <input type="text" class="form-control student-list"  min="date" name="arrival['+index+']" placeholder="Anreise" onfocus="(this.type= \x27date\x27)" onfocusout="(this.type=\x27text\x27)" ></td>';
        newRow.append(cols);
        $("table.v-code-block").append(newRow);

        var newRow = $("<tr id='dep_row"+counter+"'>");
        var cols = "";
        cols += '<td colspan="2" class="p-0 pb-3" style="border: none!important;"> <input type="text" class="form-control student-list" min="date" name="departure['+index+']" placeholder="Abreise" onfocus="(this.type= \x27date\x27)" onfocusout="(this.type=\x27text\x27)" ></td>';
        newRow.append(cols);
        $("table.v-code-block").append(newRow);

        var newRow = $("<tr id='student_row"+counter+"'>");
        var cols = "";
        cols += '<td colspan="2" class="p-0 pb-3" style="border: none!important;"> <select id="students[]" name="students['+index+']" class="form-control student-list"><option value="">Anzahl Schüler_innen</option> <option value="9 - 14 Schüler_innen">9 - 14 Schüler_innen</option><option value="15 - 19 Schüler_innen">15 - 19 Schüler_innen</option><option value="20 - 24 Schüler_innen">20 - 24 Schüler_innen</option><option value="25 - 29 Schüler_innen">25 - 29 Schüler_innen</option><option value="30 - 35 Schüler_innen">30 - 35 Schüler_innen</option></select> </td>';
        newRow.append(cols);
        $("table.v-code-block").append(newRow);

        var newRow = $("<tr id='age_row"+counter+"'>");
        var cols = "";
        cols += '<td colspan="2" class="p-0 pb-3" style="border: none!important;"><select id="age[]" name="age['+index+']" class="form-control student-list"><option value="">Alter Schüler_innen</option><option value="14 - 15 Jahre">14 - 15 Jahre</option><option value="15 - 16 Jahre">15 - 16 Jahre</option><option value="16 - 17 Jahre">16 - 17 Jahre</option><option value="17 - 18 Jahre">17 - 18 Jahre</option><option value="18 und älter">18 und älter</option></select>  </td>';
        newRow.append(cols);
        $("table.v-code-block").append(newRow);

        var newRow = $("<tr id='age_row"+counter+"'>");
        var cols = "";
        cols += '<td colspan="2" class="p-0 pb-3" style="border: none!important;"> <textarea placeholder="Anmerkung" class="form-control  student-list" id="note_class[]" name="note_class[]" rows="3"></textarea></td>';
        newRow.append(cols);
        $("table.v-code-block").append(newRow);

        var newRow = $("<tr id='sp_vcode_row"+counter+"'>");
        var cols = "";
        cols += '<td colspan="2" class="p-0 pb-3" style="border: none!important;"><input type="text" class="form-control student-list" id="sp_vcode[]" name="sp_vcode['+index+']" minlength="5" placeholder="Eigener Z-Code">  </td>';
        newRow.append(cols);
        $("table.v-code-block").append(newRow);

    });


    $("#addrow_company").on("click", function () {

        counter++;
        index++;

        var newRow = $("<tr id='name_row"+counter+"'>");
        var cols = "";
        cols += '<td class="p-0 pb-3" style="border: none!important;"> <input type="text" style="width: 220px!important;" name="school_classes['+index+']"  id="school_classes['+index+']" class="form-control student-list klassenname'+index+'" value="Zugang '+counter+'"/></td>';
        cols += '<td class="p-0 pb-3 pl-2" style="border: none!important;"><input type="button" class="ibtnDel btn btn-md btn-cross" data-delete="'+counter+'"  value="x"></td>';
        newRow.append(cols);
        $("table.v-code-block").append(newRow);

        var newRow = $("<tr id='arr_row"+counter+"'>");
        var cols = "";
        cols += '<td colspan="2" class="p-0 pb-3" style="border: none!important;"> <input type="text" class="form-control student-list"  min="date" name="arrival['+index+']" placeholder="Anreise" onfocus="(this.type= \x27date\x27)" onfocusout="(this.type=\x27text\x27)" ></td>';
        newRow.append(cols);
        $("table.v-code-block").append(newRow);

        var newRow = $("<tr id='dep_row"+counter+"'>");
        var cols = "";
        cols += '<td colspan="2" class="p-0 pb-3" style="border: none!important;"> <input type="text" class="form-control student-list" min="date" name="departure['+index+']" placeholder="Abreise" onfocus="(this.type= \x27date\x27)" onfocusout="(this.type=\x27text\x27)" ></td>';
        newRow.append(cols);
        $("table.v-code-block").append(newRow);

        var newRow = $("<tr id='student_row"+counter+"'>");
        var cols = "";
        cols += '<td colspan="2" class="p-0 pb-3" style="border: none!important;"> <select id="students[]" name="students['+index+']" class="form-control student-list"><option value="">Anzahl Personen</option> <option value="9 - 14 Schüler_innen">9 - 14 Personen</option><option value="15 - 19 Schüler_innen">15 - 19 Personen</option><option value="20 - 24 Schüler_innen">20 - 24 Personen</option><option value="25 - 29 Schüler_innen">25 - 29 Personen</option><option value="30 - 35 Schüler_innen">30 - 35 Personen</option></select> </td>';
        newRow.append(cols);
        $("table.v-code-block").append(newRow);

        var newRow = $("<tr id='age_row"+counter+"'>");
        var cols = "";
        cols += '<td colspan="2" class="p-0 pb-3" style="border: none!important;"><select id="age[]" name="age['+index+']" class="form-control student-list"><option value="">Alter Personen</option><option value="14 - 15 Jahre">14 - 15 Jahre</option><option value="15 - 16 Jahre">15 - 16 Jahre</option><option value="16 - 17 Jahre">16 - 17 Jahre</option><option value="17 - 18 Jahre">17 - 18 Jahre</option><option value="18 und älter">18 und älter</option></select>  </td>';
        newRow.append(cols);
        $("table.v-code-block").append(newRow);

        var newRow = $("<tr id='age_row"+counter+"'>");
        var cols = "";
        cols += '<td colspan="2" class="p-0 pb-3" style="border: none!important;"> <textarea placeholder="Anmerkung" class="form-control  student-list" id="note_class[]" name="note_class[]" rows="3"></textarea></td>';
        newRow.append(cols);
        $("table.v-code-block").append(newRow);

        var newRow = $("<tr id='sp_vcode_row"+counter+"'>");
        var cols = "";
        cols += '<td colspan="2" class="p-0 pb-3" style="border: none!important;"><input type="text" class="form-control student-list" id="sp_vcode[]" name="sp_vcode['+index+']" minlength="5" placeholder="Eigener Z-Code">  </td>';
        newRow.append(cols);
        $("table.v-code-block").append(newRow);

    });

    $("table.v-code-block").on("click", ".ibtnDel", function (event) {

        var c = $(this).attr("data-delete");

        $('#name_row'+c).remove();
        $('#student_row'+c).remove();
        $('#arr_row'+c).remove();
        $('#dep_row'+c).remove();
        $('#age_row'+c).remove();
        $('#sp_vcode_row'+c).remove();

        counter--;

    });

    getBooking();

    $('#operators').on('change', function() {


        if(this.value === "1" || this.value === "9"){

            $('#operator_bid').prop('required',false);
            $( "#operator_bid" ).prop( "disabled", true );

        }else{
            $('#operator_bid').prop('required',true);
            $( "#operator_bid" ).prop( "disabled", false );
        }

        var op = this.value;
        var ma = $('#mailing_'+op).val();

        $('#email').val(ma);



        if(this.value == 1){
            $(".price_class").val('240');
        }else{
            if(this.value == 2){
                $(".price_class").val('180');
            }else{
                $(".price_class").val('216');
            }

        }

        //TODO
        //getPrice(this.value)

    });



    <!-- DATEPICKER SETUP -->
    var today = new Date();
    var dd = today.getDate();
    var mm = today.getMonth() + 1; //January is 0!
    var yyyy = today.getFullYear();

    if (dd < 10) {
        dd = '0' + dd;
    }

    if (mm < 10) {
        mm = '0' + mm;
    }

    today = yyyy + '-' + mm + '-' + dd;
    arrival.min = today;
    departure.min = today;

    $('#arrival').change(function(){
        var arr = this.value;
        departure.min = arr;
    });


});


function getBooking() {

    $(".links").html('');
    $(".overview").html('');

    var url = "/mod/bookings/includes/get-bookings.php";

    $.ajax({
        type: "POST",
        url: url,
        data: "",
        success: function(response)
        {
            if(response){
                $(".bookings").html(response);
            }
        }
    });
}

function getPrice(id) {


    var url = "/mod/bookings/includes/get-price.php";
    data: { id: id};

    $.ajax({
        type: "POST",
        url: url,
        data: data,
        success: function(response)
        {
            if(response){
              
                $(".price_class").val(response);
            }
        }
    });
}


