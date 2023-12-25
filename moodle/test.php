
<div class="container" style="padding:50px;">

    <form id="form_b_uid">
        <h3>Videoschnitt</h3>
        <div class="preference">
            <label for="b_uid">Buchungsnummer:</label>
            <input type="text" name="b_uid" id="b_uid">
            <input type="button" name="submit" id="filter-buid" value="Klassen suchen"><br><br>
        </div>
    </form>

    <form id="form_classes" style="display:none;" method="get">
        <div class="row">
            <div class="col-md-5 pt-3 classes"></div><br>
        </div>
        <div class="preference quality-div">
            <label for="quality">Video-Qualität:</label>
            <select name="quality" id="quality">
                <option value="hls/270p">hls/270p</option>
                <option value="web/mp4/720p">web/mp4/720p</option>
                <option value="web/webm/1080p">web/webm/1080p</option>
                <option value="iPhone">iPhone</option>
            </select><br><br>
        </div>
        <div class="preference" >
            <input type="submit" name="submit" id="build" value="Video produzieren"><br><br>
        </div>
    </form>

</div>
</div>


<script src="https://reisen.ontour.org/theme/jquery.php/core/jquery-3.5.1.js"></script>
<script>

    $('#filter-buid').click(function() {
        filter1();
    });

    function filter1() {

        var url = "/api/get_classes.php";

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

</script>

<?php

global $CFG;
global $DB;
require(__DIR__.'/config.php');
require_once('config.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/pdflib.php');
require 'vendor/autoload.php';

use transloadit\Transloadit;



if(isset($_GET['class-filter']) && $_GET['class-filter'] != ''){

    $transloadit = new Transloadit([
        'key' => '10244a0d6acf4396ab95d7f1a0d6de04',
        'secret' => 'f91c4b36dd6de958a71af6d920ae43fa74ea6f5e',
    ]);


    $u_id = $_GET['class-filter'];
    $sql = "SELECT * FROM {booking_data3} WHERE user_id = '$u_id'";
    $booking = $DB->get_record_sql($sql);
    $school = $booking->school;
    $classname = $booking->classname;
    $year = substr($booking->arrival, 0, 4);
    $present = $_GET['quality'];





    $task2 = [];

    //TASK 2
    for ($i = 1; $i <= 6; $i++) {

        $next = $u_id+1;
        $sql = "SELECT * FROM {user} WHERE id = '$next'";
        $user = $DB->get_record_sql($sql);

        $sql = "SELECT * FROM {files} WHERE userid = '$user->id' && component = 'assignsubmission_file' && filearea = 'submission_files' && filesize > 0 && filename != '.' && contextid = 1575";
        $file = $DB->get_record_sql($sql);

        $task[$i] = "https://reisen.ontour.org/pluginfile.php/2966/assignsubmission_file/submission_files/2003/cdv_photo_1671021754.mov?forcedownload=1&contextid=1575&itemid=2254&file=IMG-20230227-WA0000.jpg";

    }

    //$video_test = "https://ontour.org/videos/01_Sequenz.mp4";
    //$video_test = "https://ontour.org/videos/01-sequenz_pG0rOnPV.mp4";
    $video_test = "https://reisen.ontour.org/pluginfile.php/2966/assignsubmission_file/submission_files/2003/cdv_photo_1671021754.mov?forcedownload=1";

    $response = $transloadit->createAssembly([

        'params' => [
            'steps' => [

                /* IMPORT FILES */
                'seq1' => [
                    "robot" => "/http/import",
                    "url" => "$video_test",
                ],

                'imported_audio_1' => [
                    "robot" => "/http/import",
                    "url" => "https://ontour.org/videos/Feeling_Free_Hvrdvr.wav",
                ],


                'seq12' => [

                    'robot' => "/video/merge",
                    "result" => true,
                    "ffmpeg_stack" => "v5.0.0",
                    "preset"=> "$present",

                    'use' => [

                        'steps' => [

                            [
                                "name" => "seq1",
                                "as"=> "video_1",
                            ],

                            [
                                "name" => "imported_audio_1",
                                "as"=> "audio",
                            ]
                        ]
                    ],

                ],



                /*

                'resized_seq1' => [
                    "use" => "seq1",
                    "robot" => "/video/encode",
                    "result" => "true",
                    "ffmpeg_stack" => "v5.0.0",
                    "background" => "#000000",
                    "preset" => "$present",
                    "rotate" => 90
                ],

                'resized_seq2' => [
                    "use" => "resized_seq1",
                    "robot" => "/video/encode",
                    "height" => 2160,
                    "width" => 3840,
                    "result" => "true",
                    "ffmpeg_stack" => "v5.0.0",
                    "background" => "#000000",
                    "preset" => "$present",
                ],

                */


                /* INTRO CONCAT */

                /*

                'seq12' => [

                    'robot' => "/video/concat",
                    "result" => true,
                    "ffmpeg_stack" => "v5.0.0",
                    "preset"=> "$present",

                    'use' => [

                        'steps' => [

                            [
                                "name" => "seq1",
                                "as"=> "video_1",
                            ],

                            [
                                "name" => "seq1_1",
                                "as"=> "video_1_1",
                            ],

                            [
                                "name" => "seq2",
                                "as"=> "video_2",
                            ],

                            [
                                "name" => "seq3",
                                "as"=> "video_3",
                            ],

                        ]
                    ],

                ],

                */


                /* INSERT INTO TEXT */
                'seq1_01' => [

                    "use" => "seq12",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf" => "drawtext=font='Kaushan Script':text='Eure':fontsize=90:fontcolor=white:x=800:y=450:enable='between(t,0,1)'"

                    ]
                ],

                'seq1_02' => [

                    "use" => "seq1_01",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='Klassenfahrt':font='Kaushan Script':fontsize=110:fontcolor=white:x=650:y=550:enable='between(t,0,1)'"

                    ]
                ],

                'seq1_03' => [

                    "use" => "seq1_02",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='$year':font='Kaushan Script':fontsize=90:fontcolor='#49AEDB':x=1100:y=675:enable='between(t,0,1)'"

                    ]
                ],

                /* INSERT SCHOOL TEXT */




                'seq1_04' => [

                    "use" => "seq1_03",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='$school':font='Kaushan Script':fontsize=110:fontcolor=white@0.2:x=650:y=850:enable='between(t,2.0,4)'"

                    ]
                ],

                'seq1_wa' => [

                    "use" => "seq1_03",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='$school':font='Kaushan Script':fontsize=110:fontcolor=white@0.4:x=650:y=850:enable='between(t,2.2,4)'"

                    ]
                ],

                'seq1_wb' => [

                    "use" => "seq1_wa",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='$school':font='Kaushan Script':fontsize=110:fontcolor=white@0.6:x=650:y=850:enable='between(t,2.5,4)'"

                    ]
                ],

                'seq1_wc' => [

                    "use" => "seq1_wb",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='$school':font='Kaushan Script':fontsize=110:fontcolor=white@0.8:x=650:y=850:enable='between(t,2.9,4)'"

                    ]
                ],

                'seq1_wd' => [

                    "use" => "seq1_wc",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='$school':font='Kaushan Script':fontsize=110:fontcolor=white@1:x=650:y=850:enable='between(t,3.3,4)'"

                    ]
                ],

                'seq1_we' => [

                    "use" => "seq1_wd",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='$school':font='Kaushan Script':fontsize=110:fontcolor=white@0.7:x=650:y=850:enable='between(t,3.5,4)'"

                    ]
                ],

                'seq1_wf' => [

                    "use" => "seq1_we",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='$school':font='Kaushan Script':fontsize=110:fontcolor=white@0.5:x=650:y=850:enable='between(t,3.7,4)'"

                    ]
                ],

                'seq1_wg' => [

                    "use" => "seq1_wf",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='$school':font='Kaushan Script':fontsize=110:fontcolor=white@0.3:x=650:y=850:enable='between(t,3.8,4)'"

                    ]
                ],



                /* INSERT CLASS TEXT */



                'seq1_class' => [

                    "use" => "seq1_wg",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf" => "drawtext=text='$classname':font='Kaushan Script':fontsize=90:fontcolor=white@0.8:x=50:y=200:enable='between(t,6,9)'"

                    ]
                ],



                /* INSERT DUMMY CLASS TRIP TEXT */


                'seq1_dummy1' => [

                    "use" => "seq1_class",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='Eure':font='Kaushan Script':fontsize=40:fontcolor=white:x=190:y=100:enable='between(t,11,16)'"

                    ]
                ],

                'seq1_dummy2' => [

                    "use" => "seq1_dummy1",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='Klassenfahrt':font='Kaushan Script':fontsize=52:fontcolor=white:x=110:y=150:enable='between(t,12,16)'"

                    ]
                ],

                'seq1_dummy3' => [

                    "use" => "seq1_dummy2",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='$year':font='Kaushan Script':fontsize=42:fontcolor='#49AEDB':x=320:y=1085:enable='between(t,13,16)'"

                    ]
                ],
            ],
        ],
    ]);


    $response = $transloadit->createAssembly([

        'params' => [
            'steps' => [

                /* IMPORT FILES */
                'seq1' => [
                    "robot" => "/http/import",
                    "url" => "$video_test",
                ],

                'imported_audio_1' => [
                    "robot" => "/http/import",
                    "url" => "https://ontour.org/videos/Feeling_Free_Hvrdvr.wav",
                ],


                'seq12' => [

                    'robot' => "/video/merge",
                    "result" => true,
                    "ffmpeg_stack" => "v5.0.0",
                    "preset"=> "$present",

                    'use' => [

                        'steps' => [

                            [
                                "name" => "seq1",
                                "as"=> "video_1",
                            ],

                            [
                                "name" => "imported_audio_1",
                                "as"=> "audio",
                            ]
                        ]
                    ],

                ],



                /*

                'resized_seq1' => [
                    "use" => "seq1",
                    "robot" => "/video/encode",
                    "result" => "true",
                    "ffmpeg_stack" => "v5.0.0",
                    "background" => "#000000",
                    "preset" => "$present",
                    "rotate" => 90
                ],

                'resized_seq2' => [
                    "use" => "resized_seq1",
                    "robot" => "/video/encode",
                    "height" => 2160,
                    "width" => 3840,
                    "result" => "true",
                    "ffmpeg_stack" => "v5.0.0",
                    "background" => "#000000",
                    "preset" => "$present",
                ],

                */


                /* INTRO CONCAT */

                /*

                'seq12' => [

                    'robot' => "/video/concat",
                    "result" => true,
                    "ffmpeg_stack" => "v5.0.0",
                    "preset"=> "$present",

                    'use' => [

                        'steps' => [

                            [
                                "name" => "seq1",
                                "as"=> "video_1",
                            ],

                            [
                                "name" => "seq1_1",
                                "as"=> "video_1_1",
                            ],

                            [
                                "name" => "seq2",
                                "as"=> "video_2",
                            ],

                            [
                                "name" => "seq3",
                                "as"=> "video_3",
                            ],

                        ]
                    ],

                ],

                */


                /* INSERT INTO TEXT */
                'seq1_01' => [

                    "use" => "seq12",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf" => "drawtext=font='Kaushan Script':text='Eure':fontsize=90:fontcolor=white:x=800:y=450:enable='between(t,0,1)'"

                    ]
                ],

                'seq1_02' => [

                    "use" => "seq1_01",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='Klassenfahrt':font='Kaushan Script':fontsize=110:fontcolor=white:x=650:y=550:enable='between(t,0,1)'"

                    ]
                ],

                'seq1_03' => [

                    "use" => "seq1_02",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='$year':font='Kaushan Script':fontsize=90:fontcolor='#49AEDB':x=1100:y=675:enable='between(t,0,1)'"

                    ]
                ],

                /* INSERT SCHOOL TEXT */




                'seq1_04' => [

                    "use" => "seq1_03",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='$school':font='Kaushan Script':fontsize=110:fontcolor=white@0.2:x=650:y=850:enable='between(t,2.0,4)'"

                    ]
                ],

                'seq1_wa' => [

                    "use" => "seq1_03",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='$school':font='Kaushan Script':fontsize=110:fontcolor=white@0.4:x=650:y=850:enable='between(t,2.2,4)'"

                    ]
                ],

                'seq1_wb' => [

                    "use" => "seq1_wa",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='$school':font='Kaushan Script':fontsize=110:fontcolor=white@0.6:x=650:y=850:enable='between(t,2.5,4)'"

                    ]
                ],

                'seq1_wc' => [

                    "use" => "seq1_wb",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='$school':font='Kaushan Script':fontsize=110:fontcolor=white@0.8:x=650:y=850:enable='between(t,2.9,4)'"

                    ]
                ],

                'seq1_wd' => [

                    "use" => "seq1_wc",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='$school':font='Kaushan Script':fontsize=110:fontcolor=white@1:x=650:y=850:enable='between(t,3.3,4)'"

                    ]
                ],

                'seq1_we' => [

                    "use" => "seq1_wd",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='$school':font='Kaushan Script':fontsize=110:fontcolor=white@0.7:x=650:y=850:enable='between(t,3.5,4)'"

                    ]
                ],

                'seq1_wf' => [

                    "use" => "seq1_we",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='$school':font='Kaushan Script':fontsize=110:fontcolor=white@0.5:x=650:y=850:enable='between(t,3.7,4)'"

                    ]
                ],

                'seq1_wg' => [

                    "use" => "seq1_wf",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='$school':font='Kaushan Script':fontsize=110:fontcolor=white@0.3:x=650:y=850:enable='between(t,3.8,4)'"

                    ]
                ],



                /* INSERT CLASS TEXT */



                'seq1_class' => [

                    "use" => "seq1_wg",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf" => "drawtext=text='$classname':font='Kaushan Script':fontsize=90:fontcolor=white@0.8:x=50:y=200:enable='between(t,6,9)'"

                    ]
                ],



                /* INSERT DUMMY CLASS TRIP TEXT */


                'seq1_dummy1' => [

                    "use" => "seq1_class",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='Eure':font='Kaushan Script':fontsize=40:fontcolor=white:x=190:y=100:enable='between(t,11,16)'"

                    ]
                ],

                'seq1_dummy2' => [

                    "use" => "seq1_dummy1",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='Klassenfahrt':font='Kaushan Script':fontsize=52:fontcolor=white:x=110:y=150:enable='between(t,12,16)'"

                    ]
                ],

                'seq1_dummy3' => [

                    "use" => "seq1_dummy2",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "turbo"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='$year':font='Kaushan Script':fontsize=42:fontcolor='#49AEDB':x=320:y=1085:enable='between(t,13,16)'"

                    ]
                ],
            ],
        ],
    ]);



    // Show the results of the assembly we spawned
    echo '<div class="container" style="padding:50px;">';
    echo '<pre>';
    print_r($response);
    echo '</pre>';
    echo '</div>';
}

