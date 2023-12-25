<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">

<style>
    body,h1,h2,h3,h4,h5,h6 {font-family: "Lato", sans-serif;}
    body, html {
        height: 100%;
        color: #fff;
        line-height: 1.8;
    }

    .bg {
        background-color: #343a40;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='28' height='49' viewBox='0 0 28 49'%3E%3Cg fill-rule='evenodd'%3E%3Cg id='hexagons' fill='%239C92AC' fill-opacity='0.25' fill-rule='nonzero'%3E%3Cpath d='M13.99 9.25l13 7.5v15l-13 7.5L1 31.75v-15l12.99-7.5zM3 17.9v12.7l10.99 6.34 11-6.35V17.9l-11-6.34L3 17.9zM0 15l12.98-7.5V0h-2v6.35L0 12.69v2.3zm0 18.5L12.98 41v8h-2v-6.85L0 35.81v-2.3zM15 0v7.5L27.99 15H28v-2.31h-.01L17 6.35V0h-2zm0 49v-8l12.99-7.5H28v2.31h-.01L17 42.15V49h-2z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"), linear-gradient(to right top, #343a40, #2b2c31, #211f22, #151314, #000000);
    }


    .selects{
        appearance: auto;
    }

    .north {
        transform:rotate(0deg);
        -ms-transform:rotate(0deg); /* IE 9 */
        -webkit-transform:rotate(0deg); /* Safari and Chrome */
    }
    .west {
        transform:rotate(90deg);
        -ms-transform:rotate(90deg); /* IE 9 */
        -webkit-transform:rotate(90deg); /* Safari and Chrome */
    }
    .south {
        transform:rotate(180deg);
        -ms-transform:rotate(180deg); /* IE 9 */
        -webkit-transform:rotate(180deg); /* Safari and Chrome */

    }
    .east {
        transform:rotate(270deg);
        -ms-transform:rotate(270deg); /* IE 9 */
        -webkit-transform:rotate(270deg); /* Safari and Chrome */
    }


</style>

<div class="bg" style="padding:50px;">

    <div class="row">
        <div class="col-4">
            <h1>Videoschnitt</h1>
        </div>
        <div class="col-7">

        </div>
    </div>
    <div class="row">
        <div class="col-4">
            <form id="form_b_uid">
                <label for="b_uid">Buchungsnummer:</label>
                <div class="row">
                    <div class="col-md-9 pt-3">
                        <div class="input-group mb-3">
                            <input type="text" name="b_uid" id="b_uid" value="7962" class="form-control">
                            <input type="button" class="btn btn-primary" name="submit" id="filter-buid" value="Klassen suchen">
                        </div>
                    </div>
                </div>

            </form>
            <form id="form_classes" style="display:none;" method="get">
                <div class="row">
                    <div class="col-md-9 classes"></div>
                </div>
                <div class="row">
                    <div class="col-md-9 pt-3">
                        <div class="preference quality-div">
                            <label for="quality">Aufgabe</label>
                            <select name="task" id="task" class="form-control selects">
                                <option value=""></option>

                                <option value="1">Aufgabe 1/2</option>
                                <!--
                                <option value="5">Aufgabe 5</option>
                                -->
                                <option value="6">Aufgabe 6</option>
                            </select>
                        </div>
                    </div>
                </div>  <div class="row">
                    <div class="col-md-9 pt-3">
                        <input type="button" class="btn btn-info" name="submit" id="get-data" value="Daten abholen" style="color: #fff;">
                    </div>
                </div>
                <div class="row pt-5 video-production" style="display: none;">
                    <div class="col-md-9">
                        <div class="preference quality-div">
                            <label for="quality">Video-Qualität:</label>
                            <select name="quality" id="quality" class="form-control selects">
                                <!--
                                <option value="hls/270p">hls/270p</option>
                                <option value="web/mp4/720p">web/mp4/720p</option>-->
                                <option value="web/webm/1080p">web/webm/1080p</option>
                                <!--<option value="iPhone">iPhone</option>-->
                            </select>
                        </div>
                        <div class="preference quality-div pt-3">
                            <label for="video_type">Video Teil:</label>
                            <select name="video_type" id="video_type" class="form-control selects">
                                <option value=""></option>
                                <!--
                                <option value="intro">Intro</option>
                                <option value="intro_intern">Intro intern</option>
                                <option value="task2">Aufgabe 1&2</option>
                                <option value="intro_all">Zusammen</option>-->
                                <option value="outro">Outro</option>
                                <!--
                                <option value="outro2">Outro_TEST</option>
                                <option value="testCut">Test-Cut</option>

                                -->
                            </select>
                        </div>
                        <div id="rotater_instructions" style="display: block;"></div>
                        <div class="preference pt-3" >
                            <input type="button" name="submit" id="build" value="Video produzieren" class="btn btn-warning" style="color: #fff;"><br><br>
                        </div>
                    </div>
                </div>

            </form>
        </div>
        <div class="col-7 shadow p-3 mb-5 bg-white rounded" id="output" style="color: #424242;min-height: 800px;">
            <br><h3>Output...</h3>
        </div>

    </div>

    <div class="row">
        <div class="col-md-4">


        </div>

    </div>

</div>



<script src="https://reisen.ontour.org/theme/jquery.php/core/jquery-3.5.1.js"></script>
<script>

    function init(){

        $('.rotater').click(function(){

            id = $(this).attr('id');
            id = id.substring(6);


            var img = $('#imgTask2_'+id);
            if(img.hasClass('north')){
                img.attr('class','west');

                if($('#bild'+id).length){
                    $('#bild'+id).val(id+"_90");
                }else{
                    $( "#rotater_instructions" ).append( "<input value='"+id+"_90' name='bild[]' id='bild"+id+"'>" );
                }



            }else if(img.hasClass('west')){
                img.attr('class','south');

                if($('#bild'+id).length){
                    $('#bild'+id).val(id+"_180");
                }else{
                    $( "#rotater_instructions" ).append( "<input value='"+id+"_180'  name='bild[]' id='bild"+id+"'>" );
                }

            }else if(img.hasClass('south')){
                img.attr('class','east');

                if($('#bild'+id).length){
                    $('#bild'+id).val(id+"_270");
                }else{
                    $( "#rotater_instructions" ).append( "<input value='"+id+"_270'  name='bild[]' id='bild"+id+"'>" );
                }

            }else if(img.hasClass('east')){
                img.attr('class','north');

                if($('#bild'+id).length){
                    $('#bild'+id).val(id+"_0");
                }else{
                    $( "#rotater_instructions" ).append( "<input value='"+id+"_0' name='bild[]' id='bild"+id+"'>" );
                }

            }else{
                img.attr('class','west');

                if($('#bild'+id).length){
                    $('#bild'+id).val(id+"_90");
                }else{
                    $( "#rotater_instructions" ).append( "<input value='"+id+"_90'  name='bild[]' id='bild"+id+"'>" );
                }
            }
        });








    }




    $('#filter-buid').click(function() {
        filter1();
    });

    $('#build').click(function() {
        build1();
    });

    $('#get-data').click(function() {
        data();
    });


    function filter1() {

        var url = "get_classes.php";

        $.ajax({
            type: "POST",
            url: url,
            data: $("#form_b_uid").serialize(),
            success: function(response)
            {
                if(response){

                    $(".classes").html(response);
                    $("#form_classes").show();


                    if(response == "Nicht vorhanden"){
                        $("#build").hide();
                        $(".quality-div").hide();
                    }else{
                        $("#build").show();
                        $(".quality-div").show();
                    }


                }else{

                    $("#form_classes").hide();
                }

            }
        });

    }


    function build1() {

        var url = "build.php";

        $.ajax({
            type: "GET",
            url: url,
            data: $("#form_classes").serialize(),
            success: function(response)
            {
                if(response){


                    $("#output").html(response);

                }else{

                }

            }
        });

    }


    function data() {

        var url = "get-data.php";

        $.ajax({
            type: "GET",
            url: url,
            data: $("#form_classes").serialize(),
            success: function(response)
            {
                if(response){
                    $(".video-production").show();
                    $("#output").html(response);
                    init();


                }else{

                }

            }
        });

    }


</script>

