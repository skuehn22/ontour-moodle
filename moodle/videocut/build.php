<?php

global $CFG;
global $DB;
require(__DIR__.'/../config.php');
require_once('../config.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/pdflib.php');
require '../vendor/autoload.php';
use transloadit\Transloadit;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;


if(isset($_GET['class-filter']) && $_GET['class-filter'] != ''){


    $u_id = $_GET['class-filter'];
    $sql = "SELECT * FROM {booking_data3} WHERE user_id = '$u_id'";
    $booking = $DB->get_record_sql($sql);
    $school = $booking->school;
    $classname = $booking->classname_teacher;
    $year = substr($booking->arrival, 0, 4);
    $present = $_GET['quality'];
    $op = getOp($booking);
    $op_url = "https://ontour.org/videos/op/".$op->name.".png";


    $task2 = prepareTask2($u_id);

    switch ($_GET['video_type']) {
        case "intro":

            $video = "https://ontour.org/videos/intro/intro-1-gruppen.mp4";
            $task6 = prepareTask6($u_id);
            $response = cutIntro($video, $present, $year, $school, $classname, $op, $task2, $u_id, $task6);
            break;

        case "intro_intern":

            $video = "https://ontour.org/videos/intro/intro-1-gruppen.mp4";
            $task6 = prepareTask6($u_id);
            $response = cutIntroIntern($video, $present, $year, $school, $classname, $op, $task2, $u_id, $task6);
            break;

        case "task2":

            //if rotation of images is requested
            if(isset($_GET['bild'])){
                $rotations = getRotations($_GET['bild']);
            }

            $response = cutTask2($present, $task2, $year, $school, $classname, $op, $booking, $rotations);
            break;

        case "intro_all":
            $response = cutTask2OLD($present, $task2, $year, $school, $classname, $op_url);
            break;

        case "outro":

            if(isset($_GET['bild'])){
                $rotations = getRotations($_GET['bild']);
            }
            $task6 = prepareTask6($u_id);
            $response = cutOutro($present, $school, $classname, $op, $task2, $u_id, $task6, $rotations);
            break;

        case "outro2":

            if(isset($_GET['bild'])){
                $rotations = getRotations($_GET['bild']);
            }
            $task6 = prepareTask6($u_id);
            $response = cutOutro2($present, $school, $classname, $op, $task2, $u_id, $task6, $rotations);
            break;

        case "testCut":

            if(isset($_GET['bild'])){
                $rotations = getRotations($_GET['bild']);
            }
            $task6 = prepareTask6($u_id);
            $response = cutTest($present, $school, $classname, $op, $task2, $u_id, $task6, $booking, $rotations);
            break;

    }

    if($response){
        echo '<div class="container" style="padding:50px;">';
        echo '<pre>';
        echo $response->data['ok']."<br>";
        echo $response->data['message']."<br>";
        echo "Assembly ID ".$response->data['assembly_id']."<br>";
        echo "<a href='".$response->data['assembly_url']."' target='_blank'>Response</a><br>";
        echo "<a href='".$response->data['assembly_url']."' target='_blank'>Response</a><br>";
        echo '</pre>';
        echo '</div>';
    }


}else{
    echo 'nicht erreicht';
}

function cutIntro($video_test, $present, $year, $school, $classname, $op, $task2, $user, $task6){

    global $DB;

    $transloadit = new Transloadit([
        'key' => '10244a0d6acf4396ab95d7f1a0d6de04',
        'secret' => 'f91c4b36dd6de958a71af6d920ae43fa74ea6f5e',
    ]);

   $intro_part1 = "https://ontour.org/videos/intro/".$op->short.".mp4";
  // $intro_part1 = "https://ontour.org/videos/schwarz.mp4";
   $font_file = "/home/runcloud/webapps/app-moodle-ontour/moodle/videocut/TrashHand.ttf";

    $response = $transloadit->createAssembly([

        'params' => [
            'steps' => [


                /* IMPORT FILES */
                'seq1' => [
                    "robot" => "/http/import",
                    "url" => "$intro_part1",

                ],

                /*
                'font' => [
                    "robot" => "/http/import",
                    "url" => $font_file,
                ],
                */



                'introtext' => [

                    "use" => "seq1",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "result"=> true,
                    "ffmpeg" => [
                        "vf" => "drawtext=text='Eure':fontfile='/home/runcloud/webapps/app-moodle-ontour/moodle/videocut/TrashHand.ttf':fontsize=90:fontcolor=white:x=(w-text_w)/2:y=(h-text_h)/2:enable='between(t,0,1)',
                                 drawtext=text='Klassenfahrt':font='ubuntu':fontsize=90:fontcolor=white:x=(w-text_w)/2:y=600:enable='between(t,0,1)',
                                 drawtext=text='$year':font='Sans':fontsize=90:fontcolor=white:x=(w-text_w)/2:y=800:enable='between(t,0,1)',
                                 drawtext=text='$school':fontsize=70:fontcolor=ffffff:alpha='if(lt(t,2),0,if(lt(t,3),(t-2)/1,if(lt(t,4),1,if(lt(t,5),(1-(t-4))/1,0))))':x=(w-text_w)/2:y=(h-text_h)/2,
                                 drawtext=text='$classname':fontsize=70:fontcolor=ffffff:alpha='if(lt(t,5),0,if(lt(t,6),(t-5)/1,if(lt(t,8),1,if(lt(t,9),(1-(t-8))/1,0))))':x=(w-text_w)/2:y=(h-text_h)/2
                        "
                    ]
                ],

            ],
        ],
    ]);

    return $response;


}

function cutIntroIntern($video_test, $present, $year, $school, $classname, $op, $task2, $user, $task6){

    global $DB;

    $fontfile = "TrashHand.ttf";
    $video = $_FILES["video"]["tmp_name"];

    $text_1 = "Eure";
    $text_2 = "Klassenfahrt";
    $text_3 = "2023";
    $output = time();

    $command = 'ffmpeg -i '.$video.' -vf "drawtext=text='.$text_1.':fontfile=\'TrashHand.ttf\':fontcolor=white:fontsize=260:x=(w-text_w)/2:y=(h-text_h)/2:enable=\'between(t,0,1)\'
    ,drawtext=text='.$text_2.':fontfile=\'TrashHand.ttf\':fontcolor=white:fontsize=260:x=(w-text_w)/2:y=1000:enable=\'between(t,0,1)\'
    ,drawtext=text='.$text_3.':fontfile=\'TrashHand.ttf\':fontcolor=#49AEDB:fontsize=260:x=1000:y=1500:enable=\'between(t,0,1)\'" output20000j0_'.$output.'.mp4';

    
    return system($command);


}



function cutOutro2 ($present, $school, $classname, $op, $task2, $user, $task6, $rotations){


    $test = false;

    global $DB;

    $transloadit = new Transloadit([
        'key' => '10244a0d6acf4396ab95d7f1a0d6de04',
        'secret' => 'f91c4b36dd6de958a71af6d920ae43fa74ea6f5e',
    ]);

    $duration = 0;

    /*
        echo "<pre>";
        print_r($task6);
        echo "</pre>";
    */

    foreach ($task6 as $t){
        $duration = $duration + $t['duration'];

        /*
        echo "Dauer: ".$t['duration']."<br>";
        echo "SRC: ".$t['src']."<br>";
        echo "Duration: ".$t['duration']."<br>";
        echo "EstimatedDuration: ".$t['estimatedDuration']."<br>";
        echo "Filesize: ".$t['filesize']."<br><br><br>";
        */

    }

    echo "Dauer gesamt: ".$duration;


    $outro_text = getOutroText($present, $school, $classname, $user, $task2, $op);



    $black = [
        "robot" => "/http/import",
        "url" => "https://ontour.org/videos/klassen/schwarzes_video.mp4",
    ];

    $black_1sek = [
        "robot" => "/http/import",
        "url" => "https://ontour.org/videos/klassen/schwarzes_video_1sek.mp4",
    ];

    $audio = [
        "robot" => "/http/import",
        "url" => "https://ontour.org/videos/Outro-Musik.wav",
    ];


    $missing = [
        "robot" => "/http/import",
        "url" => "https://ontour.org/videos/klassen/before_credits.mp4",
    ];

  //  $assembly["params"]["steps"]["black_6sek"] = $black ;
 //   $assembly["params"]["steps"]["black_1sek"] = $black_1sek ;
   // $assembly["params"]["steps"]["audio"] = $audio ;
    $assembly["params"]["steps"]["missing"] = $missing ;


    //CALCULATE SPEED
    $outro_text_length = substr_count($outro_text, "\r") * 2; // Add 2 for each "\r" character
    echo "<br>";
    echo "substr_count: ".substr_count($outro_text, "\r");

    echo "<br>";
    echo "Textlänge ".$outro_text_length;

    //distance = number of lines * font size.
    $distance = ($outro_text_length + 5) * 42;

    //speed = total vertical distance / video duration
    $speed = $distance / $duration;

    echo "<br>";
    echo "Scroll - Speed ".$speed;

    echo "<br>".$outro_text;


    //WRITE CREDITS
    $data = [
        "use"=> "missing",
        "robot"=> "/video/encode",
        "result"=> true,
        "background"=> "#000000",
        "ffmpeg_stack"=> "v4.3.1",
        "preset"=> "hls-1080p",
        "turbo"=> false,
        "ffmpeg"=> [
            "vf"=> "pad=width=3840:height=2160:x=20:y=200:color=black,drawtext=text='$outro_text':x=2100:y=h-'$speed'*t:fontsize=42:fontcolor=white:line_spacing=30"
        ]
    ];

    $assembly["params"]["steps"]["credits"] = $data;



    //AUDIO EXTRACT THE SCHÜLER SOUND

    /*
    $data = [
        "robot"=> "/audio/encode",
        "result"=> true,
        "ffmpeg_stack"=> "v4.3.1",
        "use"=> "credits",
        "ffmpeg"=> [
            "ss"=> "00:00:1.0", "t"=> "$duration"
        ],
        "preset"=> "wav"

    ];

    */

  //  $assembly["params"]["steps"]["sound_extra"] = $data;


    //AUDIO MERGE SONG + SCHÜLER SOUND
    /*
    $data = [
        "robot"=> "/audio/merge",
        "result"=> true,
        "ffmpeg_stack"=> "v5.0.0",
        "use"=> [
            "steps" => [
                [
                    "name"=> "sound_extra",
                    "as"=> "audio_1",

                ],
                [
                    "name"=> "audio",
                    "as"=> "audio_2"
                ]
            ],
            "bundle_steps"=> true
        ],
        "preset"=> "mp3"

    ];

    $assembly["params"]["steps"]["concatenated_audio"] = $data;
*/

    //AUDIO FADE

    /*
    $fade_start = $duration - 4;

    $data = [
        "robot"=> "/audio/encode",
        "result"=> true,
        "ffmpeg_stack"=> "v5.0.0",
        "use"=> "concatenated_audio",
        "preset"=> "mp3",
        "ffmpeg"=> [
            "af"=> "afade=t=out:st=$fade_start:d=4",
            "to"=> "$duration"
        ]

    ];

    $assembly["params"]["steps"]["sound_together_with_fade"] = $data;

*/
    //VIDEO MIT CREDITS MERGE MIT AUDIO
    /*
    if(!$test){
        $data = [
            "robot"=> "/video/merge",
            "result"=> true,
            "preset"=> "hls-1080p",
            "ffmpeg_stack"=> "v4.3.1",
            "use"=> [
                "steps" => [
                    [
                        "name"=> "credits",
                        "as"=> "video",

                    ],
                    [
                        "name"=> "sound_together_with_fade",
                        "as"=> "audio"
                    ]
                ],
                "bundle_steps"=> true
            ],
            "duration" => $duration,

        ];

        $assembly["params"]["steps"]["sound"] = $data;
    */

 //   }



    echo "<pre>";
    //print_r($assembly);
    echo "</pre>";


    $response = $transloadit->createAssembly($assembly);
    //$response = "";

    return $response;


}

function cutOutro ($present, $school, $classname, $op, $task2, $user, $task6, $rotations){


    $test = false;

    global $DB;

    $transloadit = new Transloadit([
        'key' => '10244a0d6acf4396ab95d7f1a0d6de04',
        'secret' => 'f91c4b36dd6de958a71af6d920ae43fa74ea6f5e',
    ]);

    $duration = 0;

    /*
        echo "<pre>";
        print_r($task6);
        echo "</pre>";
    */

    foreach ($task6 as $t){
        $duration = $duration + $t['duration'];

        /*
        echo "Dauer: ".$t['duration']."<br>";
        echo "SRC: ".$t['src']."<br>";
        echo "Duration: ".$t['duration']."<br>";
        echo "EstimatedDuration: ".$t['estimatedDuration']."<br>";
        echo "Filesize: ".$t['filesize']."<br><br><br>";
        */

    }

    echo "Dauer gesamt: ".$duration;


    $outro_text = getOutroText($present, $school, $classname, $user, $task2, $op);

    //IMPORT ALL MEDIA
    if(!$test){

        $black = [
            "robot" => "/http/import",
            "url" => "https://ontour.org/videos/klassen/schwarzes_video.mp4", /* 6 sekunden */
        ];

        $black_1sek = [
            "robot" => "/http/import",
            "url" => "https://ontour.org/videos/klassen/schwarzes_video_1sek.mp4",
        ];

        $audio = [
            "robot" => "/http/import",
            "url" => "https://ontour.org/videos/Outro-Musik.wav",
        ];

        $logo_ver = [
            "robot" => "/http/import",
            "url" => "https://ontour.org/videos/op/WelcomeBerlinTours.png",
        ];


        $logo_ontour = [
            "robot" => "/http/import",
            "url" => "https://ontour.org/videos/op/Direktbuchung.png",
        ];

        $assembly["params"]["steps"]["black_6sek"] = $black ;
        $assembly["params"]["steps"]["black_1sek"] = $black_1sek ;
        $assembly["params"]["steps"]["audio"] = $audio ;
        $assembly["params"]["steps"]["logo_ver"] = $logo_ver ;
        $assembly["params"]["steps"]["logo_ontour"] = $logo_ontour ;

    }else{

        $black = [
            "robot" => "/http/import",
            "url" => "https://ontour.org/videos/schwarz.mp4",
        ];


        $assembly["params"]["steps"]["black"] = $black ;

    }

    if(!$test) {

        //import all media from class
        $newdata = [];
        $i = 0;
        foreach ($task6 as $t) {
            $media = $t['src'];

            if ($t['type'] != 'img') {

                $newdata[$i] = [
                    "robot" => "/http/import",
                    "url" => "$media",
                ];

                $assembly["params"]["steps"]["seq" . $i] = $newdata[$i];
            }

            $i++;
        }


        //MERGE MEDIA

        $k = 0;
        $count_img = 0;
        $count_vid = 0;




        foreach ($task6 as $t) {

            if (isset($rotations) && array_key_exists($k, $rotations)) {

                $rotate = $rotations[$k];

                switch ($rotate) {
                    case "90":
                        $rotate = 90;
                        break;
                    case "180":
                        $rotate = 180;
                        break;
                    case "270":
                        $rotate = 270;
                        break;
                    case "360":
                        $rotate = 360;
                        break;
                    case "0":
                        $rotate = 0;
                        break;
                }

            } else {
                $rotate = 0;
            }


            if ($t['type'] == 'img') {

                $count_img++;
                $media = $t['src'];

                if($rotate != 0){

                    $data = [

                        "use" => "black_6sek",
                        "robot" => "/video/encode",
                        "result" => true,
                        "preset" => "hls-1080p",
                        "ffmpeg_stack" => "v4.3.1",
                        "turbo" => false,
                        "watermark_start_time" => 0.1,
                        "watermark_duration" => 5,
                        "watermark_url" => "$media",
                        "watermark_size" => "99%",
                        "watermark_position" => "left",
                        "height" => 720,
                        "width" => 1280,
                        "resize_strategy" => "pad",

                    ];

                    $assembly["params"]["steps"]["water_".$k] = $data;

                    $name = "water_".$k;

                    $data = [
                        "use" => "$name",
                        "robot" => "/video/encode",
                        "result" => true,
                        "background" => "#000000",
                        "ffmpeg_stack" => "v4.3.1",
                        "height" => 720,
                        "preset" => "hls-1080p",
                        "resize_strategy" => "pad",
                        "turbo" => false,
                        "width" => 1280,
                        "rotate" => $rotate,
                    ];


                    $assembly["params"]["steps"]["prepared_before_" . $k] = $data;

                    $name = "prepared_before_".$k;

                    $data = [
                        "use" => "$name",
                        "robot" => "/video/encode",
                        "result" => true,
                        "background" => "#000000",
                        "ffmpeg_stack" => "v4.3.1",
                        "height" => 720,
                        "preset" => "hls-1080p",
                        "resize_strategy" => "pad",
                        "turbo" => false,
                        "width" => 1280
                    ];


                    $assembly["params"]["steps"]["prepared_" . $k] = $data;


                }else{

                    $data = [

                        "use" => "black_6sek",
                        "robot" => "/video/encode",
                        "result" => true,
                        "preset" => "hls-1080p",
                        "ffmpeg_stack" => "v4.3.1",
                        "turbo" => false,
                        "watermark_start_time" => 0.1,
                        "watermark_duration" => 5,
                        "watermark_url" => "$media",
                        "watermark_size" => "99%",
                        "watermark_position" => "left",
                        "height" => 720,
                        "width" => 1280,
                        "resize_strategy" => "pad",

                    ];


                    $assembly["params"]["steps"]["prepared_" . $k] = $data;

                }


            } else {

                $count_vid++;

                $seq = "seq" . $k;

                if($rotate != 0){
                    $data = [
                        "use" => "$seq",
                        "robot" => "/video/encode",
                        "result" => true,
                        "background" => "#000000",
                        "ffmpeg_stack" => "v4.3.1",
                        "height" => 720,
                        "preset" => "hls-1080p",
                        "resize_strategy" => "pad",
                        "turbo" => false,
                        "width" => 1280,
                        "rotate" => $rotate,
                    ];

                    $assembly["params"]["steps"]["prepared_before_" . $k] = $data;

                    $seq = "prepared_before_" . $k;
                }

                $data = [
                    "use" => "$seq",
                    "robot" => "/video/encode",
                    "result" => true,
                    "background" => "#000000",
                    "ffmpeg_stack" => "v4.3.1",
                    "height" => 720,
                    "preset" => "hls-1080p",
                    "resize_strategy" => "pad",
                    "turbo" => false,
                    "width" => 1280
                ];

                $assembly["params"]["steps"]["prepared_" . $k] = $data;

            }

            $k++;
        }


        //  prepare concat
        $data = [
            "robot" => "/video/concat",
            "result" => true,
            "ffmpeg_stack" => "v4.3.1",
            "preset" => "hls-1080p",
        ];

        $assembly["params"]["steps"]["together"] = $data;

        //get all concat steps
        $z = 0;
        $concat_steps = [];

        foreach ($task6 as $t) {

            $name = "prepared_" . $z;

            $concat_steps[] = [

                "name" => "$name",
                "as" => "video_" . $z,
            ];

            $z++;
        }

    }


    if(!$test) {

        //add all concut steps to assembly
        for ($j = 0; $j < $i; $j++) {

            if ($j == 0) {
                $assembly["params"]["steps"]["together"]["use"]["steps"] = $concat_steps;
            } else {

            }
        }

        //resize concut
        $data = [
            "use" => "together",
            "robot" => "/video/encode",
            "result" => true,
            "background" => "#000000",
            "ffmpeg_stack" => "v4.3.1",
            "height" => 720,
            "preset" => "hls-1080p",
            "resize_strategy" => "pad",
            "turbo" => true,
            "width" => 1280
        ];

        $assembly["params"]["steps"]["concut_resize"] = $data;
    }


    //CALCULATE SPEED
    $outro_text_length = substr_count($outro_text, "\r") * 2; // Add 2 for each "\r" character
    echo "<br>";
    echo "substr_count: ".substr_count($outro_text, "\r");

    echo "<br>";
    echo "Textlänge ".$outro_text_length;

    //distance = number of lines * font size.
    $distance = ($outro_text_length) * 57;

    //speed = total vertical distance / video duration
    $speed = $distance / $duration;




    echo "<br>";
    echo "Scroll - Speed ".$speed;


    //ADD CREDITS
    $data = [
        "use"=> "concut_resize",
        "robot"=> "/video/encode",
        "result"=> true,
        "background"=> "#000000",
        "ffmpeg_stack"=> "v4.3.1",
        "preset"=> "hls-1080p",
        "turbo"=> false,
        "ffmpeg"=> [

            "vf"=> "pad=width=3840:height=2160:x=20:y=200:color=black,drawtext=text='$outro_text':x=2100:y=h-'$speed'*t:fontsize=42:fontcolor=white:line_spacing=30"
        ]
    ];

    $assembly["params"]["steps"]["credits"] = $data;



    //EXTRACT SOUND OF SCHÜLER
    $data = [
        "robot"=> "/audio/encode",
        "result"=> true,
        "ffmpeg_stack"=> "v4.3.1",
        "use"=> "credits",
        "ffmpeg"=> [
            "ss"=> "00:00:1.0", "t"=> "$duration"
        ],
        "preset"=> "wav"

    ];

    $assembly["params"]["steps"]["schuler_sound"] = $data;


    //MERGE SONG + SCHULER SOUND
    $data = [
        "robot"=> "/audio/merge",
        "result"=> true,
        "ffmpeg_stack"=> "v5.0.0",
        "use"=> [
            "steps" => [
                [
                    "name"=> "schuler_sound",
                    "as"=> "audio_1",

                ],
                [
                    "name"=> "audio",
                    "as"=> "audio_2"
                ]
            ],
            "bundle_steps"=> true
        ],
        "preset"=> "mp3"

    ];

    $assembly["params"]["steps"]["concatenated_audio"] = $data;


    //AUDIO FADE
    $fade_start = $duration - 4;

    $data = [
        "robot"=> "/audio/encode",
        "result"=> true,
        "ffmpeg_stack"=> "v5.0.0",
        "use"=> "concatenated_audio",
        "preset"=> "mp3",
        "ffmpeg"=> [
            "af"=> "afade=t=out:st=$fade_start:d=4",
            "to"=> "$duration"
        ]

    ];

    $assembly["params"]["steps"]["sounds_together_with_fade"] = $data;



    //MERGE FADEDED SOUND WITH VIDEO
    $data = [
        "robot"=> "/video/merge",
        "result"=> true,
        "preset"=> "hls-1080p",
        "ffmpeg_stack"=> "v4.3.1",
        "use"=> [
            "steps" => [
                [
                    "name"=> "credits",
                    "as"=> "video",

                ],
                [
                    "name"=> "sounds_together_with_fade",
                    "as"=> "audio"
                ]
            ],
            "bundle_steps"=> true
        ],
        "duration" => $duration,

    ];

    $assembly["params"]["steps"]["sound"] = $data;


    //  prepare concat
    $data = [
        "robot" => "/video/concat",
        "result" => true,
        "ffmpeg_stack" => "v4.3.1",
        "preset" => "hls-1080p",
        "use"=> [
            "steps" => [
                [
                    "name"=> "sound",
                    "as"=> "video_1",

                ],
                [
                    "name"=> "black_6sek",
                    "as"=> "video_2"
                ]
            ],
        ],
    ];

    $assembly["params"]["steps"]["outro_add_black_6_sek"] = $data;


    $media = "https://ontour.org/videos/op/WelcomeBerlinTours.png";
    $logo1 = $duration - 5;


    $data = [

        "use" => "outro_add_black_6_sek",
        "robot" => "/video/encode",
        "result" => true,
        "preset" => "hls-1080p",
        "ffmpeg_stack" => "v4.3.1",
        "turbo" => false,
        "watermark_start_time" => $logo1,
        "watermark_duration" => 2,
        "watermark_url" => "$media",
        "watermark_size" => "99%",
        "watermark_position" => "left",
        "height" => 720,
        "width" => 1280,
        "resize_strategy" => "pad",

    ];

    //$assembly["params"]["steps"]["finale_ver"] = $data;


    $media = "https://ontour.org/videos/op/Direktbuchung.png";
    $logo1 = $duration - 2;



    $data = [

        "use" => "finale_ver",
        "robot" => "/video/encode",
        "result" => true,
        "preset" => "hls-1080p",
        "ffmpeg_stack" => "v4.3.1",
        "turbo" => false,
        "watermark_start_time" => $logo1,
        "watermark_duration" => 2,
        "watermark_url" => "$media",
        "watermark_size" => "99%",
        "watermark_position" => "left",
        "height" => 720,
        "width" => 1280,
        "resize_strategy" => "pad",

    ];

   // $assembly["params"]["steps"]["completed"] = $data;


    echo "<pre>";
    //print_r($assembly);
    echo "</pre>";


    $response = $transloadit->createAssembly($assembly);
    //$response = "";

    echo "<br> GESAMT: ".$i."<br>";
    echo "Bilder: ".$count_img;
    echo "<br>Videos: ".$count_vid;

    return $response;


}

function prepareTask2($u_id){

    global $DB;

    $task = [];
    $gruppen_task2 = 0;


    //TASK 2
    for ($i = 1; $i <= 6; $i++) {

        $next = $u_id+$i;
        $sql = "SELECT * FROM {user} WHERE id = '$next'";
        $user = $DB->get_record_sql($sql);


        $sql = "SELECT * FROM {assign_submission} WHERE userid = '$user->id' && assignment = 54 && status = 'submitted' && latest = 1";
        $assign_submission_task2 = $DB->get_record_sql($sql);

        $sql = "SELECT * FROM {assign_submission} WHERE userid = '$user->id' && assignment = 49 && status = 'submitted' && latest = 1";
        $assign_submission_task1 = $DB->get_record_sql($sql);

        if($i ==6 ){
            $sql = "SELECT * FROM {assign_submission} WHERE userid = '$user->id' && assignment = 61 && status = 'submitted' && latest = 1";
            $assign_submission_task2_teacher = $DB->get_record_sql($sql);
        }


        if($assign_submission_task2){

            if($assign_submission_task1){

                $sql = "SELECT * FROM {assignsubmission_onlinetext} WHERE submission = '$assign_submission_task1->id' && assignment = 49";
                $assign_submission_onlinetext = $DB->get_record_sql($sql);

                $task[$i]['name'] = $assign_submission_onlinetext->onlinetext;

            }

            $sql = "SELECT * FROM {files} WHERE userid = '$user->id' && component = 'assignsubmission_file' && filearea = 'submission_files' && filesize > 0 && filename != '.' && contextid = 1575 && itemid = $assign_submission_task2->id";
            $file = $DB->get_record_sql($sql);
            $task[$i]['img'] = "https://reisen.ontour.org/pluginfile.php/2966/assignsubmission_file/submission_files/2003/cdv_photo_1671021754.mov?forcedownload=1&contextid=1575&itemid=$assign_submission_task2->id&file=$file->filename";

            if($assign_submission_task2 && $assign_submission_task1){
                $gruppen_task2++;
            }

        }

        if($assign_submission_task2_teacher){

            $sql = "SELECT * FROM {files} WHERE userid = '$user->id' && mimetype = 'image/jpeg' && component = 'assignsubmission_file' && filearea = 'submission_files' && filesize > 0 && filename != '.' && contextid = 2522 && itemid = $assign_submission_task2_teacher->id";
            $file = $DB->get_record_sql($sql);
            $task[$i]['img'] = "https://reisen.ontour.org/pluginfile.php/2966/assignsubmission_file/submission_files/2003/cdv_photo_1671021754.mov?forcedownload=1&contextid=2522&itemid=$assign_submission_task2_teacher->id&file=$file->filename";
            $gruppen_task2++;


        }


    }
    $task[7]['name'] = $gruppen_task2;

    return $task;
}

function cutTask2($present, $task, $year, $school, $classname, $op, $booking, $rotations){


    //the original index is wrong
    $task_fix = [];


    foreach ($task as $t){
        if($t['img'] != ""){
            $task_fix[] = $t;
        }
    }


    //how many groups are there
    switch ($task[7]['name']) {
        case "1":
            $response = cut1Groups($present, $task, $year, $school, $classname, $op, $booking, $rotations, $task_fix);
            break;
        case "2":
            $response = cut2Groups($present, $task, $year, $school, $classname, $op, $booking, $rotations, $task_fix);
            break;
        case "3":
            $response = cut3Groups($present, $task, $year, $school, $classname, $op, $booking, $rotations, $task_fix);
            break;
        case "4":
            $response = cut4Groups($present, $task, $year, $school, $classname, $op, $booking, $rotations, $task_fix);
            break;
        case "5":
            $response = cut5Groups($present, $task, $year, $school, $classname, $op, $booking, $rotations, $task_fix);
            break;
        case "6":
            $response = cut6Groups($present, $task, $year, $school, $classname, $op, $booking, $rotations, $task_fix);
            break;
    }

    return $response;

}

function cutTask2OLD($present, $task, $year, $school, $classname, $op){


    $video_test = "";
    $transloadit = new Transloadit([
        'key' => '10244a0d6acf4396ab95d7f1a0d6de04',
        'secret' => 'f91c4b36dd6de958a71af6d920ae43fa74ea6f5e',
    ]);

    //the original index is wrong
    $task_fix = [];


    $m=0;

    foreach ($task as $t){

        if($t['img'] != ""){
            $task_fix[] = $t;
        }

    }

    //print_r($task_fix);
    //echo $task_fix[0]['img'];
echo $task[7]['name'];
    if($task[7]['name']  == 3){


        $name1 = $task_fix[0]['name'];
        $name2 = $task_fix[1]['name'];
        $name3 = $task_fix[2]['name'];
        $name4 = $task_fix[3]['name'];
        //$name5 = $task_fix[0]['name'];

        $response = $transloadit->createAssembly([



            'params' => [
                'steps' => [


                    'seq1' => [
                        "robot" => "/http/import",
                        "url" => "$video_test",
                    ],


                    'resized_seq2' => [
                        "use" => "seq1",
                        "robot" => "/video/encode",
                        "height" => 2160,
                        "width" => 3840,
                        "result" => "true",
                        "ffmpeg_stack" => "v5.0.0",
                        "background" => "#000000",
                        "preset" => "$present",
                    ],




                    /* INSERT INTRO TEXT */
                    'seq1_01' => [

                        "use" => "resized_seq2",
                        "robot" => "/video/encode",
                        "ffmpeg_stack" => "v5.0.0",
                        "preset" => "$present",
                        "turbo"=> true,
                        "ffmpeg" => [

                            "vf" => "drawtext=font='Arial':text='Eure':fontsize=90:fontcolor=white:x=800:y=450:enable='between(t,0,1)'"

                        ]
                    ],

                    'seq1_02' => [

                        "use" => "seq1_01",
                        "robot" => "/video/encode",
                        "ffmpeg_stack" => "v5.0.0",
                        "preset" => "$present",
                        "turbo"=> true,
                        "ffmpeg" => [

                            "vf"=> "drawtext=text='Klassenfahrt':font='Arial':fontsize=110:fontcolor=white:x=650:y=550:enable='between(t,0,1)'"

                        ]
                    ],

                    'seq1_03' => [

                        "use" => "seq1_02",
                        "robot" => "/video/encode",
                        "ffmpeg_stack" => "v5.0.0",
                        "preset" => "$present",
                        "turbo"=> true,
                        "ffmpeg" => [

                            "vf"=> "drawtext=text='$year':font='Arial':fontsize=90:fontcolor='#49AEDB':x=1100:y=675:enable='between(t,0,1)'"

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

                            "vf"=> "drawtext=text='$school':font='Arial':fontsize=110:fontcolor=white@0.2:x=650:y=850:enable='between(t,2.0,4)'"

                        ]
                    ],

                    'seq1_wa' => [

                        "use" => "seq1_03",
                        "robot" => "/video/encode",
                        "ffmpeg_stack" => "v5.0.0",
                        "preset" => "$present",
                        "turbo"=> true,
                        "ffmpeg" => [

                            "vf"=> "drawtext=text='$school':font='Arial':fontsize=110:fontcolor=white@0.4:x=650:y=850:enable='between(t,2.2,4)'"

                        ]
                    ],

                    'seq1_wb' => [

                        "use" => "seq1_wa",
                        "robot" => "/video/encode",
                        "ffmpeg_stack" => "v5.0.0",
                        "preset" => "$present",
                        "turbo"=> true,
                        "ffmpeg" => [

                            "vf"=> "drawtext=text='$school':font='Arial':fontsize=110:fontcolor=white@0.6:x=650:y=850:enable='between(t,2.5,4)'"

                        ]
                    ],

                    'seq1_wc' => [

                        "use" => "seq1_wb",
                        "robot" => "/video/encode",
                        "ffmpeg_stack" => "v5.0.0",
                        "preset" => "$present",
                        "turbo"=> true,
                        "ffmpeg" => [

                            "vf"=> "drawtext=text='$school':font='Arial':fontsize=110:fontcolor=white@0.8:x=650:y=850:enable='between(t,2.9,4)'"

                        ]
                    ],

                    'seq1_wd' => [

                        "use" => "seq1_wc",
                        "robot" => "/video/encode",
                        "ffmpeg_stack" => "v5.0.0",
                        "preset" => "$present",
                        "turbo"=> true,
                        "ffmpeg" => [

                            "vf"=> "drawtext=text='$school':font='Arial':fontsize=110:fontcolor=white@1:x=650:y=850:enable='between(t,3.3,4)'"

                        ]
                    ],

                    'seq1_we' => [

                        "use" => "seq1_wd",
                        "robot" => "/video/encode",
                        "ffmpeg_stack" => "v5.0.0",
                        "preset" => "$present",
                        "turbo"=> true,
                        "ffmpeg" => [

                            "vf"=> "drawtext=text='$school':font='Arial':fontsize=110:fontcolor=white@0.7:x=650:y=850:enable='between(t,3.5,4)'"

                        ]
                    ],

                    'seq1_wf' => [

                        "use" => "seq1_we",
                        "robot" => "/video/encode",
                        "ffmpeg_stack" => "v5.0.0",
                        "preset" => "$present",
                        "turbo"=> true,
                        "ffmpeg" => [

                            "vf"=> "drawtext=text='$school':font='Arial':fontsize=110:fontcolor=white@0.5:x=650:y=850:enable='between(t,3.7,4)'"

                        ]
                    ],

                    'seq1_wg' => [

                        "use" => "seq1_wf",
                        "robot" => "/video/encode",
                        "ffmpeg_stack" => "v5.0.0",
                        "preset" => "$present",
                        "turbo"=> true,
                        "ffmpeg" => [

                            "vf"=> "drawtext=text='$school':font='Arial':fontsize=110:fontcolor=white@0.3:x=650:y=850:enable='between(t,3.8,4)'"

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

                            "vf" => "drawtext=text='$classname':font='Arial':fontsize=90:fontcolor=white@0.8:x=50:y=200:enable='between(t,6,9)'"

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

                            "vf"=> "drawtext=text='Eure':font='Arial':fontsize=90:fontcolor=white:x=800:y=450:enable='between(t,11,16)'"


                        ]
                    ],

                    'seq1_dummy2' => [

                        "use" => "seq1_dummy1",
                        "robot" => "/video/encode",
                        "ffmpeg_stack" => "v5.0.0",
                        "preset" => "$present",
                        "turbo"=> true,
                        "ffmpeg" => [

                            "vf"=> "drawtext=text='Klassenfahrt':font='Arial':fontsize=110:fontcolor=white:x=650:y=550:enable='between(t,12,16)'"


                        ]
                    ],

                    'seq1_dummy3' => [

                        "use" => "seq1_dummy2",
                        "robot" => "/video/encode",
                        "ffmpeg_stack" => "v5.0.0",
                        "preset" => "$present",
                        "turbo"=> true,
                        "ffmpeg" => [

                            "vf"=> "drawtext=text='$year':font='Arial':fontsize=110:fontcolor=white:x=1100:y=675:enable='between(t,13,16)'"


                        ]
                    ],

                    'logo_mit' => [

                        "use" => "seq1_dummy3",
                        "robot" => "/video/encode",
                        "ffmpeg_stack" => "v5.0.0",
                        "preset" => "$present",
                        "turbo"=> true,
                        "ffmpeg" => [

                            "vf"=> "drawtext=text='MIT':font='Arial':fontsize=110:fontcolor=white:x=650:y=350:enable='between(t,17,19)'"


                        ]
                    ],




                    'logo' => [

                        "use" => "logo_mit",
                        "robot" => "/video/encode",
                        "result" => true,
                        "preset"=> "$present",
                        "ffmpeg_stack" => "v4.3.1",
                        "turbo" => false,
                        "watermark_start_time" => 20,
                        "watermark_duration" => 5,
                        "watermark_url"=>  $op,
                        "watermark_size" => "60%",
                        "watermark_position" => "center",

                    ],


                    'logo_mit' => [

                        "use" => "logo",
                        "robot" => "/video/encode",
                        "ffmpeg_stack" => "v5.0.0",
                        "preset" => "$present",
                        "turbo"=> true,
                        "ffmpeg" => [

                            "vf"=> "drawtext=text='BERLIN':font='Arial':fontsize=40:fontcolor=white:x=650:y=350:enable='between(t,26,29)'"

                        ]
                    ],



                    'watermarked1' => [

                        "use" => "logo_mit",
                        "robot" => "/video/encode",
                        "result" => true,
                        "preset"=> "$present",
                        "ffmpeg_stack" => "v4.3.1",
                        "turbo" => false,
                        "watermark_start_time" => 31,
                        "watermark_duration" => 5,
                        "watermark_url"=>  $task_fix[0]['img'],
                        "watermark_size" => "100%",
                        "watermark_position" => "center",

                    ],


                    'present1' => [

                        "use" => "watermarked1",
                        "robot" => "/video/encode",
                        "ffmpeg_stack" => "v5.0.0",
                        "preset" => "$present",
                        "turbo"=> true,
                        "ffmpeg" => [

                            "vf"=> "drawtext=text='PRÄSENTIERT VON':font='Arial':fontsize=40:fontcolor=white:x=650:y=450:enable='between(t,31,36)'"

                        ]
                    ],


                    'name1' => [

                        "use" => "present1",
                        "robot" => "/video/encode",
                        "ffmpeg_stack" => "v5.0.0",
                        "preset" => "$present",
                        "turbo"=> true,
                        "ffmpeg" => [



                            "vf"=> "drawtext=text='$name1':font='Arial':fontsize=40:fontcolor=white:x=650:y=850:enable='between(t,31,36)'"

                        ]
                    ],


                    'watermarked2' => [

                        "use" => "name1",
                        "robot" => "/video/encode",
                        "result" => true,
                        "preset"=> "$present",
                        "ffmpeg_stack" => "v4.3.1",
                        "turbo" => false,
                        "watermark_start_time" => 35,
                        "watermark_duration" => 5,
                        "watermark_url"=>  $task_fix[1]['img'],
                        "watermark_size" => "100%",
                        "watermark_position" => "center",

                    ],


                    'watermarked3' => [

                        "use" => "watermarked2",
                        "robot" => "/video/encode",
                        "result" => true,
                        "preset"=> "$present",
                        "ffmpeg_stack" => "v4.3.1",
                        "turbo" => false,
                        "watermark_start_time" => 39,
                        "watermark_duration" => 5,
                        "watermark_position" => "center",
                        "watermark_size" => "100%",
                        "watermark_url"=>  $task_fix[2]['img'],

                    ],
                    'watermarked4' => [

                        "use" => "watermarked3",
                        "robot" => "/video/encode",
                        "result" => true,
                        "preset"=> "$present",
                        "ffmpeg_stack" => "v4.3.1",
                        "turbo" => false,
                        "watermark_start_time" => 43,
                        "watermark_duration" => 5,
                        "watermark_position" => "center",
                        "watermark_size" => "100%",
                        "watermark_url"=>  $task_fix[3]['img'],

                    ],

                ],
            ],
        ]);

        return $response;

    }

    // echo $task_fix[0]['name'];
}

function cutTest($present, $school, $classname, $op, $task2, $user, $task6, $booking, $rotations){

    global $DB;

    $transloadit = new Transloadit([
        'key' => '10244a0d6acf4396ab95d7f1a0d6de04',
        'secret' => 'f91c4b36dd6de958a71af6d920ae43fa74ea6f5e',
    ]);

    $duration = 0;

    /*
            echo "<pre>";
            print_r($task2);
            echo "</pre>";
    */


    foreach ($task6 as $t){

        echo "Video Dauer". $t['duration']."<br>";

        $duration = $duration + $t['duration'];
    }

    echo "Dauer gesamt2: ".$duration;


    $outro_text = getOutroText($present, $school, $classname, $user, $task2, $op);


    //IMPORT ALL MEDIA
    $audio = [
        "robot" => "/http/import",
        "url" => "https://ontour.org/videos/Outro-Musik.wav",
    ];

    $assembly["params"]["steps"]["audio"] = $audio ;
    $black = [
        "robot" => "/http/import",
        "url" => "https://ontour.org/videos/schwarz.mp4",
    ];

    $assembly["params"]["steps"]["black_6sek"] = $black ;


    /*
    $black = [
        "robot" => "/http/import",
        "url" => "https://ontour.org/videos/klassen/schwarzes_video.mp4",
    ];

    $assembly["params"]["steps"]["black_lang"] = $black ;
    */

    $i = 0;
    foreach ($task6 as $t) {

        if($i < 2){
            $media = $t['src'];

            if ($t['type'] != 'img') {

                $newdata[$i] = [
                    "robot" => "/http/import",
                    "url" => "$media",
                ];

                $assembly["params"]["steps"]["seq" . $i] = $newdata[$i];
            }
        }

        $i++;
    }

    $k=0;
    foreach ($task6 as $t) {

        if($k < 2) {
            if (array_key_exists($k, $rotations)) {

                $rotate = $rotations[$k];

                switch ($rotate) {
                    case "90":
                        $rotate = 90;
                        break;
                    case "180":
                        $rotate = 180;
                        break;
                    case "270":
                        $rotate = 270;
                        break;
                    case "360":
                        $rotate = 360;
                        break;
                    case "0":
                        $rotate = 0;
                        break;
                }

            } else {
                $rotate = 0;
            }


        }

        if($k < 2) {

            $seq = "seq" . $k;

            if ($rotate != 0) {
                $data = [
                    "use" => "$seq",
                    "robot" => "/video/encode",
                    "result" => true,
                    "background" => "#000000",
                    "ffmpeg_stack" => "v4.3.1",
                    "height" => 720,
                    "preset" => "hls-1080p",
                    "resize_strategy" => "pad",
                    "turbo" => false,
                    "width" => 1280,
                    "rotate" => $rotate,
                ];

                $assembly["params"]["steps"]["prepared_before_" . $k] = $data;

                $seq = "prepared_before_" . $k;
            }


            $data = [
                "use" => "$seq",
                "robot" => "/video/encode",
                "result" => true,
                "background" => "#000000",
                "ffmpeg_stack" => "v4.3.1",
                "height" => 720,
                "preset" => "hls-1080p",
                "resize_strategy" => "pad",
                "turbo" => false,
                "width" => 1280
            ];

            $assembly["params"]["steps"]["prepared_" . $k] = $data;
        }

        $k++;

    }


    //  prepare concat
    $data = [
        "robot" => "/video/concat",
        "result" => true,
        "ffmpeg_stack" => "v4.3.1",
        "preset" => "hls-1080p",
    ];

    $assembly["params"]["steps"]["together"] = $data;

    //get all concat steps
    $z = 0;
    $concat_steps = [];

    foreach ($task6 as $t) {

        if($z < 2){
            $name = "prepared_" . $z;

            $concat_steps[] = [

                "name" => "$name",
                "as" => "video_" . $z,
            ];
        }

        $z++;
    }

    for ($j = 0; $j < 2; $j++) {

        if ($j == 0) {
            $assembly["params"]["steps"]["together"]["use"]["steps"] = $concat_steps;
        } else {

        }

    }


    //insert music

        $data = [
            "robot"=> "/video/merge",
            "result"=> true,
            "preset"=> "hls-1080p",
            "use"=> [
                "steps" => [
                    [
                        "name"=> "together",
                        "as"=> "video"
                    ],
                    [
                        "name"=> "audio",
                        "as"=> "audio"
                    ]
                ],
                "bundle_steps"=> true
            ],
            "duration" => $duration
        ];

        $assembly["params"]["steps"]["sound"] = $data;


        /*

        $data = [
            "use" => "together",
            "robot" => "/video/encode",
            "result" => true,
            "background" => "#000000",
            "ffmpeg_stack" => "v4.3.1",
            "height" => 720,
            "preset" => "hls-1080p",
            "resize_strategy" => "pad",
            "turbo" => true,
            "width" => 1280
        ];

        $assembly["params"]["steps"]["padding"] = $data;


        $data = [

            "use"=> "black",
            "robot"=> "/video/encode",
            "result"=> true,
            "background"=> "#000000",
            "ffmpeg_stack"=> "v4.3.1",
            "height"=> 720,
            "preset"=> "hls-1080p",
            "resize_strategy"=> "pad",
            "turbo"=> true,
            "width"=> 1280

        ];


        $assembly["params"]["steps"]["prepared"] = $data ;



        $data = [
            "use"=> "prepared",
            "robot"=> "/video/encode",
            "result"=> true,
            "background"=> "#000000",
            "ffmpeg_stack"=> "v4.3.1",
            "preset"=> "hls-1080p",
            "turbo"=> false,
            "ffmpeg"=> [
                "vf"=> "pad=width=3650:height=1500:x=20:y=200:color=black,drawtext=text='$outro_text':x=2100:y=h-400*t:fontsize=42:fontcolor=white"
            ]
        ];

        $assembly["params"]["steps"]["padding2"] = $data;
        */



        echo "<pre>";
        print_r($assembly);
        echo "</pre>";



    $response = $transloadit->createAssembly($assembly);
    //$response = "";


    return $response;


}

function getOp($booking){

    global $DB;
    $sql = "SELECT * FROM {ext_operators1} WHERE id = '$booking->operators_id'";
    $operator = $DB->get_record_sql($sql);

    return $operator;

}

function getNames($u_id){

    global $DB;

    $sql = "SELECT *
				FROM {finishing_students}
				WHERE fk_user = ".$u_id;

    $studentNames = $DB->get_records_sql($sql);

    return $studentNames;

}

function cut1Groups($present, $task, $year, $school, $classname, $op, $booking, $rotations, $task_fix){

    $transloadit = new Transloadit([
        'key' => '10244a0d6acf4396ab95d7f1a0d6de04',
        'secret' => 'f91c4b36dd6de958a71af6d920ae43fa74ea6f5e',
    ]);

    $name1 = strip_tags($task_fix[0]['name']);
    $name1 = str_replace("&nbsp;", " ", $name1);

    $name2 = strip_tags($task_fix[1]['name']);
    $name2 = str_replace("&nbsp;", " ", $name2);

    $name3 = strip_tags($task_fix[2]['name']);
    $name3 = str_replace("&nbsp;", " ", $name3);

    $name4 = strip_tags($task_fix[3]['name']);
    $name4 = str_replace("&nbsp;", " ", $name4);


    $img1 = $task_fix[0]['img'];
    $img2 = $task_fix[1]['img'];
    $img3 = $task_fix[2]['img'];
    $img4 = $task_fix[3]['img'];



    $response = $transloadit->createAssembly([



        'params' => [
            'steps' => [

                'img1' => [
                    "robot" => "/http/import",
                    "url" => "$img1",
                ],


                'audio1' => [
                    "robot" => "/http/import",
                    "url" => "https://ontour.org/videos/Feeling_Good_Alex_Makemusic.wav",
                ],




                'img1_re' => [
                    "use" => "img1",
                    "robot" => "/image/resize",
                    "width"=> 1680,
                    "height"=> 1080,
                    "result" => "true",
                    "imagemagick_stack" => "v2.0.7",
                    "rotation" => "$rotations[1]",
                ],



                "slideshow"=> [
                    "robot"=> "/video/merge",
                    "result"=> true,
                    "preset"=> "$present",
                    "ffmpeg_stack"=> "v4.3.1",
                    "resize_strategy"=> "pad",
                    "use"=> [
                        "steps" => [
                            [
                                "name"=> "img1_re",
                                "as"=> "image_1",

                            ]
                        ],
                        "bundle_steps"=> true
                    ],
                    "framerate"=> "1/4",
                    "duration"=> 4

                ],



                "encode_audio" => [
                    "use"=> "audio1",
                    "robot"=> "/audio/encode",
                    "result"=> true,
                    "ffmpeg_stack"=> "v4.3.1",
                    "preset"=> "mp3",
                    "ffmpeg"=> [
                        "af"=> "afade=enable='between(t,0,4)':t=in:ss=0:d=3, afade=enable='between(t,2,30)':t=out:st=1:d=4",
                        "to"=> "4"
                    ]
                ],


                "audio"=> [
                    "robot"=> "/video/merge",
                    "result"=> true,
                    "preset"=> "hls-1080p",
                    "use"=> [
                        "steps" => [
                            [
                                "name"=> "slideshow",
                                "as"=> "video"
                            ],
                            [
                                "name"=> "encode_audio",
                                "as"=> "audio"
                            ]
                        ],
                        "bundle_steps"=> true
                    ],
                    "duration" => 4.0
                ],




                'name1' => [

                    "use" => "audio",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "result"=> true,
                    "ffmpeg" => [
                        "vf"=> "drawtext=text='$name1':fontfile=Trashhand.ttf:fontsize=70:fontcolor=ffffff:alpha='if(lt(t,0),0,if(lt(t,1),(t-0)/1,if(lt(t,3),1,if(lt(t,4),(1-(t-3))/1,0))))':x=(w-text_w)/2:y=(h-text_h)/2:x=(w-text_w)/2:y=950"
                    ]
                ],

            ],
        ],
    ]);

    return $response;

}

function cut2Groups($present, $task, $year, $school, $classname, $op, $booking, $rotations, $task_fix){

    $transloadit = new Transloadit([
        'key' => '10244a0d6acf4396ab95d7f1a0d6de04',
        'secret' => 'f91c4b36dd6de958a71af6d920ae43fa74ea6f5e',
    ]);

    $name1 = strip_tags($task_fix[0]['name']);
    $name1 = str_replace("&nbsp;", " ", $name1);

    $name2 = strip_tags($task_fix[1]['name']);
    $name2 = str_replace("&nbsp;", " ", $name2);

    $name3 = strip_tags($task_fix[2]['name']);
    $name3 = str_replace("&nbsp;", " ", $name3);

    $name4 = strip_tags($task_fix[3]['name']);
    $name4 = str_replace("&nbsp;", " ", $name4);


    $img1 = $task_fix[0]['img'];
    $img2 = $task_fix[1]['img'];
    $img3 = $task_fix[2]['img'];
    $img4 = $task_fix[3]['img'];




    $response = $transloadit->createAssembly([



        'params' => [
            'steps' => [


                'img1' => [
                    "robot" => "/http/import",
                    "url" => "$img1",
                ],

                'img2' => [
                    "robot" => "/http/import",
                    "url" => "$img2",
                ],


                'audio1' => [
                    "robot" => "/http/import",
                    "url" => "https://ontour.org/videos/Feeling_Good_Alex_Makemusic.wav",
                ],




                'img1_re' => [
                    "use" => "img1",
                    "robot" => "/image/resize",
                    "width"=> 1680,
                    "height"=> 1080,
                    "result" => "true",
                    "imagemagick_stack" => "v2.0.7",
                    "rotation" => "$rotations[1]",
                ],


                'img2_re' => [
                    "use" => "img2",
                    "robot" => "/image/resize",
                    "width"=> 1680,
                    "height"=> 1080,
                    "result" => "true",
                    "imagemagick_stack" => "v2.0.7",
                    "rotation" => "$rotations[2]",
                ],




                "slideshow"=> [
                    "robot"=> "/video/merge",
                    "result"=> true,
                    "preset"=> "$present",
                    "ffmpeg_stack"=> "v4.3.1",
                    "resize_strategy"=> "pad",
                    "use"=> [
                        "steps" => [
                            [
                                "name"=> "img1_re",
                                "as"=> "image_1",

                            ],
                            [
                                "name"=> "img2_re",
                                "as"=> "image_2"
                            ]
                        ],
                        "bundle_steps"=> true
                    ],
                    "framerate"=> "1/4",
                    "duration"=> 8

                ],



                "encode_audio" => [
                    "use"=> "audio1",
                    "robot"=> "/audio/encode",
                    "result"=> true,
                    "ffmpeg_stack"=> "v4.3.1",
                    "preset"=> "mp3",
                    "ffmpeg"=> [
                        "af"=> "afade=enable='between(t,0,8)':t=in:ss=0:d=3, afade=enable='between(t,14,30)':t=out:st=4:d=4",
                        "to"=> "8"
                    ]
                ],


                "audio"=> [
                    "robot"=> "/video/merge",
                    "result"=> true,
                    "preset"=> "hls-1080p",
                    "use"=> [
                        "steps" => [
                            [
                                "name"=> "slideshow",
                                "as"=> "video"
                            ],
                            [
                                "name"=> "encode_audio",
                                "as"=> "audio"
                            ]
                        ],
                        "bundle_steps"=> true
                    ],
                    "duration" => 8.0
                ],




                'name1' => [

                    "use" => "audio",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "result"=> true,
                    "ffmpeg" => [


                        "vf"=> "drawtext=text='$name1':fontfile=Trashhand.ttf:fontsize=70:fontcolor=ffffff:alpha='if(lt(t,0),0,if(lt(t,1),(t-0)/1,if(lt(t,3),1,if(lt(t,4),(1-(t-3))/1,0))))':x=(w-text_w)/2:y=(h-text_h)/2:x=(w-text_w)/2:y=950,
                        drawtext=text='Die Lehrkräfte':fontfile=Trashhand.ttf:fontsize=70:fontcolor=ffffff:alpha='if(lt(t,4),0,if(lt(t,5),(t-4)/1,if(lt(t,7),1,if(lt(t,8),(1-(t-7))/1,0))))':x=(w-text_w)/2:y=950"
                    ]
                ],

            ],
        ],
    ]);

    return $response;

}

function cut3Groups($present, $task, $year, $school, $classname, $op, $booking, $rotations, $task_fix){

    $transloadit = new Transloadit([
        'key' => '10244a0d6acf4396ab95d7f1a0d6de04',
        'secret' => 'f91c4b36dd6de958a71af6d920ae43fa74ea6f5e',
    ]);

    $name1 = strip_tags($task_fix[0]['name']);
    $name1 = str_replace("&nbsp;", " ", $name1);

    $name2 = strip_tags($task_fix[1]['name']);
    $name2 = str_replace("&nbsp;", " ", $name2);

    $name3 = strip_tags($task_fix[2]['name']);
    $name3 = str_replace("&nbsp;", " ", $name3);

    $name4 = strip_tags($task_fix[3]['name']);
    $name4 = str_replace("&nbsp;", " ", $name4);


    $img1 = $task_fix[0]['img'];
    $img2 = $task_fix[1]['img'];
    $img3 = $task_fix[2]['img'];
    $img4 = $task_fix[3]['img'];



    $response = $transloadit->createAssembly([



        'params' => [
            'steps' => [


                'img1' => [
                    "robot" => "/http/import",
                    "url" => "$img1",
                ],

                'img2' => [
                    "robot" => "/http/import",
                    "url" => "$img2",
                ],

                'img3' => [
                    "robot" => "/http/import",
                    "url" => "$img3",
                ],







                'audio1' => [
                    "robot" => "/http/import",
                    "url" => "https://ontour.org/videos/Feeling_Good_Alex_Makemusic.wav",
                ],




                'img1_re' => [
                    "use" => "img1",
                    "robot" => "/image/resize",
                    "width"=> 1680,
                    "height"=> 1080,
                    "result" => "true",
                    "imagemagick_stack" => "v2.0.7",
                    "rotation" => "$rotations[1]",
                ],


                'img2_re' => [
                    "use" => "img2",
                    "robot" => "/image/resize",
                    "width"=> 1680,
                    "height"=> 1080,
                    "result" => "true",
                    "imagemagick_stack" => "v2.0.7",
                    "rotation" => "$rotations[2]",
                ],

                'img3_re' => [
                    "use" => "img3",
                    "robot" => "/image/resize",
                    "width"=> 1680,
                    "height"=> 1080,
                    "result" => "true",
                    "imagemagick_stack" => "v2.0.7",
                    "rotation" => "$rotations[3]",
                ],



                "slideshow"=> [
                    "robot"=> "/video/merge",
                    "result"=> true,
                    "preset"=> "$present",
                    "ffmpeg_stack"=> "v4.3.1",
                    "resize_strategy"=> "pad",
                    "use"=> [
                        "steps" => [
                            [
                                "name"=> "img1_re",
                                "as"=> "image_1",

                            ],
                            [
                                "name"=> "img2_re",
                                "as"=> "image_2"
                            ],
                            [
                                "name"=> "img3_re",
                                "as"=> "image_3"
                            ]
                        ],
                        "bundle_steps"=> true
                    ],
                    "framerate"=> "1/4",
                    "duration"=> 12

                ],



                "encode_audio" => [
                    "use"=> "audio1",
                    "robot"=> "/audio/encode",
                    "result"=> true,
                    "ffmpeg_stack"=> "v4.3.1",
                    "preset"=> "mp3",
                    "ffmpeg"=> [
                        "af"=> "afade=enable='between(t,0,12)':t=in:ss=0:d=3, afade=enable='between(t,18,30)':t=out:st=8:d=4",
                        "to"=> "12"
                    ]
                ],


                "audio"=> [
                    "robot"=> "/video/merge",
                    "result"=> true,
                    "preset"=> "hls-1080p",
                    "use"=> [
                        "steps" => [
                            [
                                "name"=> "slideshow",
                                "as"=> "video"
                            ],
                            [
                                "name"=> "encode_audio",
                                "as"=> "audio"
                            ]
                        ],
                        "bundle_steps"=> true
                    ],
                    "duration" => 12.0
                ],




                'name1' => [

                    "use" => "audio",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "result"=> true,
                    "ffmpeg" => [


                        "vf"=> "drawtext=text='$name1':fontfile=Trashhand.ttf:fontsize=70:fontcolor=ffffff:alpha='if(lt(t,0),0,if(lt(t,1),(t-0)/1,if(lt(t,3),1,if(lt(t,4),(1-(t-3))/1,0))))':x=(w-text_w)/2:y=(h-text_h)/2:x=(w-text_w)/2:y=950,
                        drawtext=text='$name2':fontfile=Trashhand.ttf:fontsize=70:fontcolor=ffffff:alpha='if(lt(t,4),0,if(lt(t,5),(t-4)/1,if(lt(t,7),1,if(lt(t,8),(1-(t-7))/1,0))))':x=(w-text_w)/2:y=950,
                        drawtext=text='Die Lehrkräfte':fontfile=Trashhand.ttf:fontsize=70:fontcolor=ffffff:alpha='if(lt(t,8),0,if(lt(t,9),(t-8)/1,if(lt(t,11),1,if(lt(t,12),(1-(t-11))/1,0))))':x=(w-text_w)/2:y=950"
                    ]
                ],

            ],
        ],
    ]);

    return $response;

}

function cut4Groups($present, $task, $year, $school, $classname, $op, $booking, $rotations, $task_fix){

    $transloadit = new Transloadit([
        'key' => '10244a0d6acf4396ab95d7f1a0d6de04',
        'secret' => 'f91c4b36dd6de958a71af6d920ae43fa74ea6f5e',
    ]);

    $name1 = strip_tags($task_fix[0]['name']);
    $name1 = str_replace("&nbsp;", " ", $name1);

    $name2 = strip_tags($task_fix[1]['name']);
    $name2 = str_replace("&nbsp;", " ", $name2);

    $name3 = strip_tags($task_fix[2]['name']);
    $name3 = str_replace("&nbsp;", " ", $name3);

    $name4 = strip_tags($task_fix[3]['name']);
    $name4 = str_replace("&nbsp;", " ", $name4);


    $img1 = $task_fix[0]['img'];
    $img2 = $task_fix[1]['img'];
    $img3 = $task_fix[2]['img'];
    $img4 = $task_fix[3]['img'];



    $response = $transloadit->createAssembly([



        'params' => [
            'steps' => [


                'img1' => [
                    "robot" => "/http/import",
                    "url" => "$img1",
                ],

                'img2' => [
                    "robot" => "/http/import",
                    "url" => "$img2",
                ],

                'img3' => [
                    "robot" => "/http/import",
                    "url" => "$img3",
                ],

                'img4' => [
                    "robot" => "/http/import",
                    "url" => "$img4",
                ],


                'audio1' => [
                    "robot" => "/http/import",
                    "url" => "https://ontour.org/videos/Feeling_Good_Alex_Makemusic.wav",
                ],

                'img1_re' => [
                    "use" => "img1",
                    "robot" => "/image/resize",
                    "width"=> 1680,
                    "height"=> 1080,
                    "result" => "true",
                    "imagemagick_stack" => "v2.0.7",
                    "rotation" => "$rotations[1]",
                ],


                'img2_re' => [
                    "use" => "img2",
                    "robot" => "/image/resize",
                    "width"=> 1680,
                    "height"=> 1080,
                    "result" => "true",
                    "imagemagick_stack" => "v2.0.7",
                    "rotation" => "$rotations[2]",
                ],

                'img3_re' => [
                    "use" => "img3",
                    "robot" => "/image/resize",
                    "width"=> 1680,
                    "height"=> 1080,
                    "result" => "true",
                    "imagemagick_stack" => "v2.0.7",
                    "rotation" => "$rotations[3]",
                ],

                'img4_re' => [
                    "use" => "img4",
                    "robot" => "/image/resize",
                    "width"=> 1680,
                    "height"=> 1080,
                    "result" => "true",
                    "imagemagick_stack" => "v2.0.7",
                    "rotation" => "$rotations[4]",
                ],


                "slideshow"=> [
                    "robot"=> "/video/merge",
                    "result"=> true,
                    "preset"=> "$present",
                    "ffmpeg_stack"=> "v4.3.1",
                    "resize_strategy"=> "pad",
                    "use"=> [
                        "steps" => [
                            [
                                "name"=> "img1_re",
                                "as"=> "image_1",

                            ],
                            [
                                "name"=> "img2_re",
                                "as"=> "image_2"
                            ],
                            [
                                "name"=> "img3_re",
                                "as"=> "image_3"
                            ],
                            [
                                "name"=> "img4_re",
                                "as"=> "image_4"
                            ]
                        ],
                        "bundle_steps"=> true
                    ],
                    "framerate"=> "1/4",
                    "duration"=> 16

                ],



                "encode_audio" => [
                    "use"=> "audio1",
                    "robot"=> "/audio/encode",
                    "result"=> true,
                    "ffmpeg_stack"=> "v4.3.1",
                    "preset"=> "mp3",
                    "ffmpeg"=> [
                        "af"=> "afade=enable='between(t,0,16)':t=in:ss=0:d=3, afade=enable='between(t,12,30)':t=out:st=12:d=4",
                        "to"=> "16"
                    ]
                ],


                "audio"=> [
                    "robot"=> "/video/merge",
                    "result"=> true,
                    "preset"=> "hls-1080p",
                    "use"=> [
                        "steps" => [
                            [
                                "name"=> "slideshow",
                                "as"=> "video"
                            ],
                            [
                                "name"=> "encode_audio",
                                "as"=> "audio"
                            ]
                        ],
                        "bundle_steps"=> true
                    ],
                    "duration" => 16.0
                ],




                'name1' => [

                    "use" => "audio",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "result"=> true,
                    "ffmpeg" => [


                        "vf"=> "drawtext=text='$name1':fontfile=Trashhand.ttf:fontsize=70:fontcolor=ffffff:alpha='if(lt(t,0),0,if(lt(t,1),(t-0)/1,if(lt(t,3),1,if(lt(t,4),(1-(t-3))/1,0))))':x=(w-text_w)/2:y=(h-text_h)/2:x=(w-text_w)/2:y=950,
                        drawtext=text='$name2':fontfile=Trashhand.ttf:fontsize=70:fontcolor=ffffff:alpha='if(lt(t,4),0,if(lt(t,5),(t-4)/1,if(lt(t,7),1,if(lt(t,8),(1-(t-7))/1,0))))':x=(w-text_w)/2:y=950,
                        drawtext=text='$name3':fontfile=Trashhand.ttf:fontsize=70:fontcolor=ffffff:alpha='if(lt(t,8),0,if(lt(t,9),(t-8)/1,if(lt(t,11),1,if(lt(t,12),(1-(t-11))/1,0))))':x=(w-text_w)/2:y=950,
                        drawtext=text='Die Lehrkräfte':fontfile=Trashhand.ttf:fontsize=70:fontcolor=ffffff:alpha='if(lt(t,12),0,if(lt(t,13),(t-12)/1,if(lt(t,15),1,if(lt(t,16),(1-(t-15))/1,0))))':x=(w-text_w)/2:y=950"
                    ]
                ],

            ],
        ],
    ]);

    return $response;

}

function cut5Groups($present, $task, $year, $school, $classname, $op, $booking, $rotations, $task_fix){

    $transloadit = new Transloadit([
        'key' => '10244a0d6acf4396ab95d7f1a0d6de04',
        'secret' => 'f91c4b36dd6de958a71af6d920ae43fa74ea6f5e',
    ]);

    $name1 = strip_tags($task_fix[0]['name']);
    $name1 = str_replace("&nbsp;", " ", $name1);

    $name2 = strip_tags($task_fix[1]['name']);
    $name2 = str_replace("&nbsp;", " ", $name2);

    $name3 = strip_tags($task_fix[2]['name']);
    $name3 = str_replace("&nbsp;", " ", $name3);

    $name4 = strip_tags($task_fix[3]['name']);
    $name4 = str_replace("&nbsp;", " ", $name4);

    $name5 = strip_tags($task_fix[4]['name']);
    $name5 = str_replace("&nbsp;", " ", $name5);


    $img1 = $task_fix[0]['img'];
    $img2 = $task_fix[1]['img'];
    $img3 = $task_fix[2]['img'];
    $img4 = $task_fix[3]['img'];
    $img5 = $task_fix[4]['img'];
    $img6 = $task_fix[5]['img'];


    $response = $transloadit->createAssembly([



        'params' => [
            'steps' => [


                'img1' => [
                    "robot" => "/http/import",
                    "url" => "$img1",
                ],

                'img2' => [
                    "robot" => "/http/import",
                    "url" => "$img2",
                ],

                'img3' => [
                    "robot" => "/http/import",
                    "url" => "$img3",
                ],

                'img4' => [
                    "robot" => "/http/import",
                    "url" => "$img4",
                ],

                'img5' => [
                    "robot" => "/http/import",
                    "url" => "$img5",
                ],


                'audio1' => [
                    "robot" => "/http/import",
                    "url" => "https://ontour.org/videos/Feeling_Good_Alex_Makemusic.wav",
                ],


                'img1_re' => [
                    "use" => "img1",
                    "robot" => "/image/resize",
                    "width"=> 1680,
                    "height"=> 1080,
                    "result" => "true",
                    "imagemagick_stack" => "v2.0.7",
                    "rotation" => "$rotations[1]",
                ],


                'img2_re' => [
                    "use" => "img2",
                    "robot" => "/image/resize",
                    "width"=> 1680,
                    "height"=> 1080,
                    "result" => "true",
                    "imagemagick_stack" => "v2.0.7",
                    "rotation" => "$rotations[2]",
                ],

                'img3_re' => [
                    "use" => "img3",
                    "robot" => "/image/resize",
                    "width"=> 1680,
                    "height"=> 1080,
                    "result" => "true",
                    "imagemagick_stack" => "v2.0.7",
                    "rotation" => "$rotations[3]",
                ],

                'img4_re' => [
                    "use" => "img4",
                    "robot" => "/image/resize",
                    "width"=> 1680,
                    "height"=> 1080,
                    "result" => "true",
                    "imagemagick_stack" => "v2.0.7",
                    "rotation" => "$rotations[4]",
                ],

                'img5_re' => [
                    "use" => "img5",
                    "robot" => "/image/resize",
                    "width"=> 1680,
                    "height"=> 1080,
                    "result" => "true",
                    "imagemagick_stack" => "v2.0.7",
                    "rotation" => "$rotations[5]",
                ],


                "slideshow"=> [
                    "robot"=> "/video/merge",
                    "result"=> true,
                    "preset"=> "$present",
                    "ffmpeg_stack"=> "v4.3.1",
                    "resize_strategy"=> "pad",
                    "use"=> [
                        "steps" => [
                            [
                                "name"=> "img1_re",
                                "as"=> "image_1",

                            ],
                            [
                                "name"=> "img2_re",
                                "as"=> "image_2"
                            ],
                            [
                                "name"=> "img3_re",
                                "as"=> "image_3"
                            ],
                            [
                                "name"=> "img4_re",
                                "as"=> "image_4"
                            ],
                            [
                                "name"=> "img5_re",
                                "as"=> "image_5"
                            ]
                        ],
                        "bundle_steps"=> true
                    ],
                    "framerate"=> "1/4",
                    "duration"=> 20

                ],



                "encode_audio" => [
                    "use"=> "audio1",
                    "robot"=> "/audio/encode",
                    "result"=> true,
                    "ffmpeg_stack"=> "v4.3.1",
                    "preset"=> "mp3",
                    "ffmpeg"=> [
                        "af"=> "afade=enable='between(t,0,20)':t=in:ss=0:d=3, afade=enable='between(t,15,30)':t=out:st=16:d=4",
                        "to"=> "20"
                    ]
                ],


                "audio"=> [
                    "robot"=> "/video/merge",
                    "result"=> true,
                    "preset"=> "hls-1080p",
                    "use"=> [
                        "steps" => [
                            [
                                "name"=> "slideshow",
                                "as"=> "video"
                            ],
                            [
                                "name"=> "encode_audio",
                                "as"=> "audio"
                            ]
                        ],
                        "bundle_steps"=> true
                    ],
                    "duration" => 20.0
                ],


                'name1' => [

                    "use" => "audio",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "result"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='$name1':fontfile=Trashhand.ttf:fontsize=70:fontcolor=ffffff:alpha='if(lt(t,0),0,if(lt(t,1),(t-0)/1,if(lt(t,3),1,if(lt(t,4),(1-(t-3))/1,0))))':x=(w-text_w)/2:y=(h-text_h)/2:x=(w-text_w)/2:y=950,
                        drawtext=text='$name2':fontfile=Trashhand.ttf:fontsize=70:fontcolor=ffffff:alpha='if(lt(t,4),0,if(lt(t,5),(t-4)/1,if(lt(t,7),1,if(lt(t,8),(1-(t-7))/1,0))))':x=(w-text_w)/2:y=950,
                        drawtext=text='$name3':fontfile=Trashhand.ttf:fontsize=70:fontcolor=ffffff:alpha='if(lt(t,8),0,if(lt(t,9),(t-8)/1,if(lt(t,11),1,if(lt(t,12),(1-(t-11))/1,0))))':x=(w-text_w)/2:y=950,
                        drawtext=text='$name4':fontfile=Trashhand.ttf:fontsize=70:fontcolor=ffffff:alpha='if(lt(t,12),0,if(lt(t,13),(t-12)/1,if(lt(t,15),1,if(lt(t,16),(1-(t-15))/1,0))))':x=(w-text_w)/2:y=950,
                        drawtext=text='Die Lehrkräfte':fontfile=Trashhand.ttf:fontsize=70:fontcolor=ffffff:alpha='if(lt(t,16),0,if(lt(t,17),(t-16)/1,if(lt(t,19),1,if(lt(t,20),(1-(t-19))/1,0))))':x=(w-text_w)/2:y=950"
                    ]
                ],



            ],
        ],
    ]);

    return $response;

}

function cut6Groups($present, $task, $year, $school, $classname, $op, $booking, $rotations, $task_fix){

    $transloadit = new Transloadit([
        'key' => '10244a0d6acf4396ab95d7f1a0d6de04',
        'secret' => 'f91c4b36dd6de958a71af6d920ae43fa74ea6f5e',
    ]);

    $name1 = strip_tags($task_fix[0]['name']);
    $name1 = str_replace("&nbsp;", " ", $name1);

    $name2 = strip_tags($task_fix[1]['name']);
    $name2 = str_replace("&nbsp;", " ", $name2);

    $name3 = strip_tags($task_fix[2]['name']);
    $name3 = str_replace("&nbsp;", " ", $name3);

    $name4 = strip_tags($task_fix[3]['name']);
    $name4 = str_replace("&nbsp;", " ", $name4);

    $name5 = strip_tags($task_fix[4]['name']);
    $name5 = str_replace("&nbsp;", " ", $name5);


    $img1 = $task_fix[0]['img'];
    $img2 = $task_fix[1]['img'];
    $img3 = $task_fix[2]['img'];
    $img4 = $task_fix[3]['img'];
    $img5 = $task_fix[4]['img'];
    $img6 = $task_fix[5]['img'];


    $response = $transloadit->createAssembly([



        'params' => [
            'steps' => [

                'img1' => [
                    "robot" => "/http/import",
                    "url" => "$img1",
                ],

                'img2' => [
                    "robot" => "/http/import",
                    "url" => "$img2",
                ],

                'img3' => [
                    "robot" => "/http/import",
                    "url" => "$img3",
                ],

                'img4' => [
                    "robot" => "/http/import",
                    "url" => "$img4",
                ],

                'img5' => [
                    "robot" => "/http/import",
                    "url" => "$img5",
                ],

                'img6' => [
                    "robot" => "/http/import",
                    "url" => "$img6",
                ],




                'audio1' => [
                    "robot" => "/http/import",
                    "url" => "https://ontour.org/videos/Feeling_Good_Alex_Makemusic.wav",
                ],




                'img1_re' => [
                    "use" => "img1",
                    "robot" => "/image/resize",
                    "width"=> 1680,
                    "height"=> 1080,
                    "result" => "true",
                    "imagemagick_stack" => "v2.0.7",
                    "rotation" => "$rotations[1]",
                ],


                'img2_re' => [
                    "use" => "img2",
                    "robot" => "/image/resize",
                    "width"=> 1680,
                    "height"=> 1080,
                    "result" => "true",
                    "imagemagick_stack" => "v2.0.7",
                    "rotation" => "$rotations[2]",
                ],

                'img3_re' => [
                    "use" => "img3",
                    "robot" => "/image/resize",
                    "width"=> 1680,
                    "height"=> 1080,
                    "result" => "true",
                    "imagemagick_stack" => "v2.0.7",
                    "rotation" => "$rotations[3]",
                ],

                'img4_re' => [
                    "use" => "img4",
                    "robot" => "/image/resize",
                    "width"=> 1680,
                    "height"=> 1080,
                    "result" => "true",
                    "imagemagick_stack" => "v2.0.7",
                    "rotation" => "$rotations[4]",
                ],

                'img5_re' => [
                    "use" => "img5",
                    "robot" => "/image/resize",
                    "width"=> 1680,
                    "height"=> 1080,
                    "result" => "true",
                    "imagemagick_stack" => "v2.0.7",
                    "rotation" => "$rotations[5]",
                ],

                'img6_re' => [
                    "use" => "img6",
                    "robot" => "/image/resize",
                    "width"=> 1680,
                    "height"=> 1080,
                    "result" => "true",
                    "imagemagick_stack" => "v2.0.7",
                    "rotation" => "$rotations[6]",
                ],

                "slideshow"=> [
                    "robot"=> "/video/merge",
                    "result"=> true,
                    "preset"=> "$present",
                    "ffmpeg_stack"=> "v4.3.1",
                    "resize_strategy"=> "pad",
                    "use"=> [
                        "steps" => [
                            [
                                "name"=> "img1_re",
                                "as"=> "image_1",

                            ],
                            [
                                "name"=> "img2_re",
                                "as"=> "image_2"
                            ],
                            [
                                "name"=> "img3_re",
                                "as"=> "image_3"
                            ],
                            [
                                "name"=> "img4_re",
                                "as"=> "image_4"
                            ],
                            [
                                "name"=> "img5_re",
                                "as"=> "image_5"
                            ],
                            [
                                "name"=> "img6_re",
                                "as"=> "image_6"
                            ]
                        ],
                        "bundle_steps"=> true
                    ],
                    "framerate"=> "1/4",
                    "duration"=> 24

                ],



                "encode_audio" => [
                    "use"=> "audio1",
                    "robot"=> "/audio/encode",
                    "result"=> true,
                    "ffmpeg_stack"=> "v4.3.1",
                    "preset"=> "mp3",
                    "ffmpeg"=> [
                        "af"=> "afade=enable='between(t,0,24)':t=in:ss=0:d=3, afade=enable='between(t,15,30)':t=out:st=20:d=4",
                        "to"=> "24"
                    ]
                ],


                "audio"=> [
                    "robot"=> "/video/merge",
                    "result"=> true,
                    "preset"=> "hls-1080p",
                    "use"=> [
                        "steps" => [
                            [
                                "name"=> "slideshow",
                                "as"=> "video"
                            ],
                            [
                                "name"=> "encode_audio",
                                "as"=> "audio"
                            ]
                        ],
                        "bundle_steps"=> true
                    ],
                    "duration" => 24.0
                ],




                'name1' => [

                    "use" => "audio",
                    "robot" => "/video/encode",
                    "ffmpeg_stack" => "v5.0.0",
                    "preset" => "$present",
                    "result"=> true,
                    "ffmpeg" => [

                        "vf"=> "drawtext=text='$name1':fontfile=Trashhand.ttf:fontsize=70:fontcolor=ffffff:alpha='if(lt(t,0),0,if(lt(t,1),(t-0)/1,if(lt(t,3),1,if(lt(t,4),(1-(t-3))/1,0))))':x=(w-text_w)/2:y=(h-text_h)/2:x=(w-text_w)/2:y=950,
                        drawtext=text='$name2':fontfile=Trashhand.ttf:fontsize=70:fontcolor=ffffff:alpha='if(lt(t,4),0,if(lt(t,5),(t-4)/1,if(lt(t,7),1,if(lt(t,8),(1-(t-7))/1,0))))':x=(w-text_w)/2:y=950,
                        drawtext=text='$name3':fontfile=Trashhand.ttf:fontsize=70:fontcolor=ffffff:alpha='if(lt(t,8),0,if(lt(t,9),(t-8)/1,if(lt(t,11),1,if(lt(t,12),(1-(t-11))/1,0))))':x=(w-text_w)/2:y=950,
                        drawtext=text='$name4':fontfile=Trashhand.ttf:fontsize=70:fontcolor=ffffff:alpha='if(lt(t,12),0,if(lt(t,13),(t-12)/1,if(lt(t,15),1,if(lt(t,16),(1-(t-15))/1,0))))':x=(w-text_w)/2:y=950,
                        drawtext=text='$name5':fontfile=Trashhand.ttf:fontsize=70:fontcolor=ffffff:alpha='if(lt(t,16),0,if(lt(t,17),(t-16)/1,if(lt(t,19),1,if(lt(t,20),(1-(t-19))/1,0))))':x=(w-text_w)/2:y=950,
                        drawtext=text='Die Lehrkräfte':fontfile=Trashhand.ttf:fontsize=70:fontcolor=ffffff:alpha='if(lt(t,20),0,if(lt(t,21),(t-20)/1,if(lt(t,23),1,if(lt(t,24),(1-(t-23))/1,0))))':x=(w-text_w)/2:y=950"
                    ]
                ],
            ],
        ],
    ]);


    return $response;

}

function prepareTask6($u_id){

    global $DB;

    $task = [];
    $k = 0;

    //TASK 6
    for ($i = 1; $i <= 6; $i++) {

        $next = $u_id+$i;
        $sql = "SELECT * FROM {user} WHERE id = '$next'";
        $user = $DB->get_record_sql($sql);

        //echo "USER: ".$next."<br>";

        $sql = "SELECT * FROM {assign_submission} WHERE userid = '$user->id' && assignment = 64 && status = 'submitted' && latest = 1";
        $assign_submission_task6s = $DB->get_records_sql($sql);


        if($assign_submission_task6s){

            foreach ($assign_submission_task6s as $assign_submission_task6){

                $contextid = 2968;

                $sql = "SELECT * FROM {files} WHERE userid = '$user->id' && component = 'assignsubmission_file' && filearea = 'submission_files' && filesize > 0 && filename != '.' && contextid = $contextid && itemid = $assign_submission_task6->id";
                $files = $DB->get_records_sql($sql);


                foreach ($files as $file){

                    $task[$k]['src'] = "https://reisen.ontour.org/pluginfile.php/2966/assignsubmission_file/submission_files/2003/cdv_photo_1671021754.mov?forcedownload=1&contextid=$contextid&itemid=$assign_submission_task6->id&file=$file->filename";

                    if($file->mimetype == "video/quicktime" || $file->mimetype == "video/mp4"){



                    //    echo "<br>";
                        $task[$k]['src'] = "https://reisen.ontour.org/pluginfile.php/2966/assignsubmission_file/submission_files/2003/cdv_photo_1671021754.mov?forcedownload=1&contextid=$contextid&itemid=$assign_submission_task6->id&file=$file->filename";
                        $localFilePath = "video.mov";
                        file_put_contents($localFilePath, file_get_contents($task[$k]['src']));

                        $ffprobeCommand = "/usr/bin/ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 {$localFilePath}";
                        $duration = exec($ffprobeCommand);
                     //   echo $duration = (float) $duration;
                    //    echo "<br>";

                        $fileSizeBytes = $file->filesize * 1024;
                        $estimatedBitrate = $fileSizeBytes * 8 / $duration;
                        $estimatedDuration = $fileSizeBytes * 8 / $estimatedBitrate;

                       // $estimatedDuration = "";

                        $video = $task[$k]['src'];
                        $bitrate = "350k";

                        $task[$k]['type'] = "video";
                        $task[$k]['duration'] =$estimatedDuration;
                        $task[$k]['filesize'] = $file->filesize;
                        $task[$k]['estimatedDuration'] = $estimatedDuration;
                    }else{
                        $task[$k]['type'] = "img";
                        $task[$k]['duration'] = 6;
                        $task[$k]['filesize'] = 1;
                    }

                    $k++;
                }

            }

        }

    }


    return $task;
}

function getOutroText($present, $school, $classname, $user, $task2, $op){

    GLOBAL $DB;

    $sql = "SELECT * FROM {finishing_students} WHERE fk_user = ".$user;
    $studentNames = $DB->get_records_sql($sql);

    $sql = "SELECT * FROM {finishing_lehrer} WHERE fk_user = ".$user;
    $teacherNames = $DB->get_records_sql($sql);

    $outro_text  = "PRODUZENTEN\t\t\t\t\t\t\t\t\t".$school."\r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t";
    $outro_text  .= $op->name."\r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t";
    $outro_text  .= "onTour Media GmbH\r\r\r\n";

    $outro_text .= "TEAMS GRUPPEN\t\t\t\t\t\t\t\t\t";

    $i = 0;
    foreach ($task2 as $t){
        if($t['name'] != "" && strlen($t['name']) > 2){


            // Remove emoji symbols and emoticons
            $regex = '/[\x{1F600}-\x{1F64F}]/u'; // Range for emoticons
            $clean_string = preg_replace($regex, '', $t['name']);

            // Remove other miscellaneous emoji symbols
            $regex = '/[\x{1F300}-\x{1F5FF}]/u'; // Range for other symbols
            $clean_string = preg_replace($regex, '', $clean_string);

            // Remove transport and map symbols
            $regex = '/[\x{1F680}-\x{1F6FF}]/u'; // Range for transport and map symbols
            $clean_string = preg_replace($regex, '', $clean_string);

            // Remove flags (iOS and Android)
            $regex = '/[\x{1F1E0}-\x{1F1FF}]/u'; // Range for flags
            $clean_string = preg_replace($regex, '', $clean_string);


            if($i == 0){
                $name = strip_tags($clean_string);
                $name = str_replace("&nbsp;", "", $name);
                $outro_text .=  $name."\r\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t";
            }else{
                $name = strip_tags($clean_string);
                $name = str_replace("&nbsp;", "", $name);
                $outro_text .=  $name."\r\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t";
            }
        }
        $i++;
    }


    $outro_text .= "\r\r\n";

    if($studentNames){

        $outro_text .= "MITWIRKENDE\t\t\t\t\t\t\t\t\t\t";

        foreach ($studentNames as $studentName){

            $outro_text .= "$studentName->name\r\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t";
        }

        $outro_text .= "\r\r\r";
    }


    if($teacherNames){

        $outro_text .= "LEHRKRÄFTE / BEGLEITPERSONEN\t\t";

        foreach ($teacherNames as $teacherName){
            $outro_text .= "$teacherName->name\r\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t";
        }

        $outro_text .= "\r\r\r";
    }



    $outro_text .= "KAMERA & TON\t\t\t\t\t\t\t\t\tSchülerinnen ".$classname."\r\n\n\n"; /* added 2 */
    $outro_text .= "REGIE & PRODUKTIONSLEITUNG\t\tonTour Media GmbH\r\n\n\n";
    $outro_text .= "BüHNE & SOUNDEFFEKTE\t\t\t\t\tBerlin\r\n\n\n";
    $outro_text .= "LICHT\t\t\t\t\t\t\t\t\t\t\t\t\tDie Sonne\r\n\n\n"; /* added 3 */
    $outro_text .= "EIN FILM VON\t\t\t\t\t\t\t\t\t\t".$school."\r\r\n\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t"; /* added 3 */

    if($op->name != "Direktbuchung"){
        $outro_text .= "& ".$op->name."\r\r\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t";
    }

    $outro_text .= "& onTour Media GmbH \r\r\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t© All Rights reserved\r\r\r";


    return $outro_text;

}

function getRotations($data){


    $rotations = [];

    $i = 0;
    foreach ($data as $image){

        $pieces = explode("_", $image);

        $rotations[$pieces[0]] = $pieces[1];

        /*
        switch ($pieces[0]) {
            case 1:
                $rotations[1] = $pieces[1];
                break;
            case 2:
                $rotations[2] = $pieces[1];
                break;
            case 3:
                $rotations[3] = $pieces[1];
                break;
            case 4:
                $rotations[4] = $pieces[1];
                break;
            case 5:
                $rotations[5] = $pieces[1];
                break;
            case 6:
                $rotations[6] = $pieces[1];
                break;
        }

        $i++;
        */

    }
    echo "<pre>";
    print_r($rotations);
    echo "</pre>";

    return $rotations;

}