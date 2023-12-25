
$(document).ready(function () {

    function init(){
        $( "#class-filter" ).change(function() {

            var group = $(this).val();

            links = "<h2 class='pt-5'>Aufgabenübersicht</h2><a target='_blank' href='https://reisen.ontour.org/mod/choice/report.php?id=144&group="+group+"'>Aufgabe 1 - Regeln</a><br>";
            links = " <a target='_blank' href='https://reisen.ontour.org/mod/assign/view.php?id=142&action=grading&tsort=lastname&tdir=4&group="+group+"'>Aufgabe 2 - Namen (neu Aufgabe 1)</a>";
            links += "<br><a target='_blank' href='https://reisen.ontour.org/mod/assign/view.php?id=145&action=grading&tsort=lastname&tdir=4&group="+group+"'>Aufgabe 3 - Filmproduktion</a>";
            links += "<br><a target='_blank' href='https://reisen.ontour.org/mod/assign/view.php?id=146&action=grading&tsort=lastname&tdir=4&group="+group+"'>Aufgabe 4 - Audio (neu Aufgabe 4)</a>";
            links += "<br><a target='_blank' href='https://reisen.ontour.org/mod/assign/view.php?id=148&action=grading&tsort=lastname&tdir=4&group="+group+"'>Aufgabe 5 - Teamfoto (neu Aufgabe 2)</a><br>";
            links += "<a target='_blank' href='https://reisen.ontour.org/mod/feedback/analysis.php?id=151&group=0'>Aufgabe 6 - Feedback</a><br>";
            links += "<a target='_blank' href='https://reisen.ontour.org/mod/assign/view.php?id=164&action=grading&group="+group+"'>Lehreraufgabe</a><br>";


            links += "<br><a target='_blank' href='https://reisen.ontour.org/mod/assign/view.php?id=167&action=grading&tsort=lastname&tdir=4&group="+group+"'>neu - Videodreh Berlin (Aufgabe 3)</a>";
            links += "<br><a target='_blank' href='https://reisen.ontour.org/mod/assign/view.php?id=168&action=grading&tsort=lastname&tdir=4&group="+group+"'>neu - Die Videosafari (Aufgabe 5)</a>";
            links += "<br><a target='_blank' href='https://reisen.ontour.org/mod/assign/view.php?id=169&action=grading&tsort=lastname&tdir=4&group="+group+"'>neu - Outtakes (Aufgabe 6)</a><br>";


            $(".links").html(links);

            getOverview(group);

            $(".content-table").show();

        });

        $( "#class-filter-webapp" ).change(function() {

            var group = $(this).val();

            links = "<h2 class='pt-5'>Aufgabenübersicht</h2><a target='_blank' href='https://reisen.ontour.org/mod/choice/report.php?id=144&group="+group+"'>Aufgabe 1 - Regeln</a><br>";
            links = " <a target='_blank' href='https://reisen.ontour.org/mod/assign/view.php?id=142&action=grading&tsort=lastname&tdir=4&group="+group+"'>Aufgabe 2 - Namen (neu Aufgabe 1)</a>";
            links += "<br><a target='_blank' href='https://reisen.ontour.org/mod/assign/view.php?id=145&action=grading&tsort=lastname&tdir=4&group="+group+"'>Aufgabe 3 - Filmproduktion</a>";
            links += "<br><a target='_blank' href='https://reisen.ontour.org/mod/assign/view.php?id=146&action=grading&tsort=lastname&tdir=4&group="+group+"'>Aufgabe 4 - Audio (neu Aufgabe 4)</a>";
            links += "<br><a target='_blank' href='https://reisen.ontour.org/mod/assign/view.php?id=148&action=grading&tsort=lastname&tdir=4&group="+group+"'>Aufgabe 5 - Teamfoto (neu Aufgabe 2)</a><br>";
            links += "<a target='_blank' href='https://reisen.ontour.org/mod/feedback/analysis.php?id=151&group=0'>Aufgabe 6 - Feedback</a><br>";
            links += "<a target='_blank' href='https://reisen.ontour.org/mod/assign/view.php?id=164&action=grading&group="+group+"'>Lehreraufgabe</a><br>";


            links += "<br><a target='_blank' href='https://reisen.ontour.org/mod/assign/view.php?id=167&action=grading&tsort=lastname&tdir=4&group="+group+"'>neu - Videodreh Berlin (Aufgabe 3)</a>";
            links += "<br><a target='_blank' href='https://reisen.ontour.org/mod/assign/view.php?id=168&action=grading&tsort=lastname&tdir=4&group="+group+"'>neu - Die Videosafari (Aufgabe 5)</a>";
            links += "<br><a target='_blank' href='https://reisen.ontour.org/mod/assign/view.php?id=169&action=grading&tsort=lastname&tdir=4&group="+group+"'>neu - Outtakes (Aufgabe 6)</a><br>";


            $(".links").html(links);

            getOverview(group);

            $(".content-table").show();

        });

    }

    $('#filter-buid').click(function() {
        filter1();
    });

    $('#filter-buid-webapp').click(function() {
        filterWebapp();
    });


    $('.end-trip').click(function() {
        var group = $(this).attr('data-group');
        $("#school_class").val(group),
            $("#sure").show();
    });

    $('.final-action').click(function() {
        finalActions();
    });

    $('.close').click(function() {
        $("#sure").hide();
    });

    $('.abort').click(function() {
        $("#sure").hide();
    });


    function finalActions(){

        url = "/mod/finishing/final-actions.php";

        $.ajax({
            type: "POST",
            url: url,
            data: $("#data").serialize(),
            success: function(data)
            {
                $("#sure").hide();
            }
        });

    }


    function filter1() {

        $(".links").html('');
        $(".overview").html('');

        var url = "/mod/finishing/includes/filter-booking.php";

        $.ajax({
            type: "POST",
            url: url,
            data: $("#form_b_uid").serialize(),
            success: function(response)
            {
                if(response){

                    $(".classes").html(response);
                    $("#form_classes").show();
                    getBookingInfo();
                    doTests();
                    init();
                    //getBookingInfo();

                }else{
                    $("#form_classes").hide();
                }

            }
        });

    }

    function filterWebapp() {

        alert("test1");

        $(".links").html('');
        $(".overview").html('');

        var url = "/mod/finishing/includes/filter-booking-webapp.php";

        $.ajax({
            type: "POST",
            url: url,
            data: $("#form_b_uid_webapp").serialize(),
            success: function(response)
            {
                if(response){

                    $(".classes-webapp").html(response);
                    $("#form_classes-webapp").show();
                    getBookingInfo();
                    doTests();
                    init();
                    //getBookingInfo();

                }else{
                    $("#form_classes").hide();
                }

            }
        });

    }

    function getBookingInfo(){

        //var id = document.getElementById("b_uid").value;
        var url = "/mod/finishing/includes/get-booking.php";

        $.ajax({
            type: "POST",
            url: url,
            data: $("#form_b_uid").serialize(),
            success: function(response)
            {
                if(response){
                    $(".booking-info").html(response);
                }else{

                }
            }
        });
    }


    function getOverview(group){

        var data = [];

        var url = "/mod/finishing/includes/get-overview.php";

        data['group'] = group;

        $.ajax({
            type: "POST",
            url: url,
            data: ({group: group}),
            success: function(response)
            {
                if(response){
                    $(".overview").html(response);
                }else{

                }
            }
        });
    }

    function doTests(){

        var data = [];

        var url = "/mod/finishing/includes/tests.php";

        data['group'] = 'group';

        $.ajax({
            type: "POST",
            url: url,
            data: ({group: "group"}),
            success: function(response)
            {
                if(response){
                    $(".test-container").html(response);
                }else{

                }
            }
        });
        
    }


    $("#form_b_uid").submit(function(e){
        e.preventDefault();
        filter1();
    });



});
