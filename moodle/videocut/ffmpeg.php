<?php

$video = $_FILES["video"]["tmp_name"];
$bitrate = $_POST["bitrate"];
$text_1 = "Eure";
$text_2 = "Klassenfahrt";
$text_3 = "2023";
$schoolname = "Ratsgymnasium Goslar";
$classname = "10b";
$output = time();

$video = 'wbt.mp4';
$fontfile = "TrashHand.ttf";

//$command = "ffmpeg -i $video -b:v $bitrate -bufsize $bitrate output.mp4";


$command = 'ffmpeg -i '.$video.' -vf "drawtext=text='.$text_1.':fontfile=\'TrashHand.ttf\':fontcolor=white:fontsize=310:x=(w-text_w)/2:y=1100:enable=\'between(t,0,1)\'
,drawtext=text='.$text_2.':fontfile=\'TrashHand.ttf\':fontcolor=white:fontsize=360:x=(w-text_w)/2:y=1350:enable=\'between(t,0,1)\'
,drawtext=text='.$text_3.':fontfile=\'TrashHand.ttf\':fontcolor=#49AEDB:fontsize=360:x=2300:y=1550:enable=\'between(t,0,1)\'
,drawtext=text='.$schoolname.':fontfile=\'TrashHand.ttf\':fontsize=260:fontcolor=ffffff:alpha=\'if(lt(t,2),0,if(lt(t,3),(t-2)/1,if(lt(t,4),1,if(lt(t,5),(1-(t-4))/1,0))))\':x=(w-text_w)/2:y=1400
,drawtext=text='.$classname.':fontfile=\'TrashHand.ttf\':fontsize=260:fontcolor=ffffff:alpha=\'if(lt(t,6),0,if(lt(t,7),(t-6)/1,if(lt(t,9),1,if(lt(t,10),(1-(t-9))/1,0))))\':x=(w-text_w)/2:y=1400" output20000j0_'.$output.'.mp4';


$text = 'seq2.text';
$command = 'ffmpeg -safe 0 -f concat -i '.$text.' -c:v libx264 \
-vf "scale=1080:1920:force_original_aspect_ratio=increase,crop=1080:1920" -pix_fmt yuv420p slideshowddddddsdd.mp4';


system($command,$outputs);

echo "<pre>";
print_r($output);
echo "</pre>";

echo "File has been converted";

