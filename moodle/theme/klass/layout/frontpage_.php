<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * frontpage.php
 *
 * @package   theme_klass
 * @copyright 2015 Lmsace Dev Team,lmsace.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
// Get the HTML for the settings bits.
$html = theme_klass_get_html_for_settings($OUTPUT, $PAGE);

if (right_to_left()) {
    $regionbsid = 'region-bs-main-and-post';
} else {
    $regionbsid = 'region-bs-main-and-pre';
}

$courserenderer = $PAGE->get_renderer('core', 'course');

echo $OUTPUT->doctype() ?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
    <?php echo $OUTPUT->standard_head_html() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/d62481694c.js" crossorigin="anonymous"></script>

</head>

<body <?php echo $OUTPUT->body_attributes(); ?>>

<?php echo $OUTPUT->standard_top_of_body_html() ?>

<?php

if (isloggedin()) {
    require_once(dirname(__FILE__) . '/includes/header_backend.php');
    echo $headerlayout;
} else {
    require_once(dirname(__FILE__) . '/includes/header.php');
    echo $headerlayout;
}

    ?>
<!--Custom theme header-->
<div class="">
    <?php
        $toggleslideshow = theme_klass_get_setting('toggleslideshow');
    if ($toggleslideshow == 1) {
            require_once(dirname(__FILE__) . '/includes/slideshow.php');
    }
    ?>
</div>
    <?php
    $whotitle = theme_klass_get_setting('whoweare_title');
    $whodesc = theme_klass_get_setting('whoweare_description', 'format_html');
    if (!empty($whotitle) || !empty($whodesc)) {
?>

<!--Inhalt nur für Startseite-->

        <?php

        if (isloggedin()) {

            /*echo'
            <div class="container">
                <div class="home pt-5">
                    <div class="row pt-5 pb-5">
                        <div class="offset-md-2 col-md-8 text-center pt-5">
                            <h1>Willkommen</h1>
                        </div>
                    </div>
                </div>
            </div>';*/
           # echo '<p class="pb-5"></p>';
        } else {
        echo '<div class="fp-site-customdesc">
            <div class="container">
                <div class="home">
                    <div class="row pt-5 pb-5">
                        <div class="offset-md-2 col-md-8 text-center">
                            <h1>Der revolutionäre Projekttag</h1>
                            <p class="subheading">
                                „Eine unglaubliche Reise die nicht nur inhaltlich, sondern auch <br>emotional überzeugt!“ <br><span class="footnote">Kristian Schäfer, Gesamtschullehrer</span>
                            </p>
                        </div>
                    </div>
                    <div class="row m-0 pb-5 pt-5" style="background-color: #fff;">
                        <div class="offset-md-3 col-md-6 pb-5">
                            <div style="padding:56.25% 0 0 0;position:relative;"><iframe src="https://player.vimeo.com/video/571264013?badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=58479" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen style="position:absolute;top:0;left:0;width:100%;height:100%;border-radius: 20px; border: 5px solid #343A45;" title="Wieland-Zeitzeugenbegegnung-Rohschnitt.mp4"></iframe></div><script src="https://player.vimeo.com/api/player.js"></script>
                        </div>
                    </div>
                    <div class="row pt-5 pb-5">
                        <div class="offset-md-1 col-md-10">
                            <h1 class="pb-5 text-center">Was ist ProjektReise.online?</h1>
                            <p class="subheading pb-5">
                                Eine Kombination aus Präsenzunterricht und E-Learning<br>
                                Ein innovative und mitreißende Reise aus dem Klassenzimmer.
                            </p>
                            <p class="text-left">
                                Wir simulieren eine echte Reise (Klassenfahrt / Projekttag), mit den dazugehörigen unvergesslichen Erlebnissen, gemischten Emotionen und nachhaltigen Lerninhalten. Die Klasse fährt gemeinsam am Beamer / Smartboard nach Berlin. Dort entdekcen die Schüler spannende Orte, lernen authentische Menschen kennen und alles was zu einer Klassenfahrt dazu gehört.
                            </p>
                        </div>
                    </div>
                    <div class="row pt-5 pb-5">
                        <div class="offset-md-2 col-md-8 text-center">
                            <h1 class="pb-5">Die neue Form der Erlebnispädagogik</h1>
                            <p class="subheading pb-5">
                                Eine Kombination aus Präsenzunterricht und E-Learning
                                Ein innovative und mitreißende Reise aus dem Klassenzimmer.
                            </p>
                        </div>
                    </div>

                    <div class="row pt-5 pb-5">
                        <div class="col-md-12 text-center">
                            <p>
                            <div class="row">
                                <div class="col-12 col-sm-4 pb-3">
                                    <div class="card card-1 h-100" >
                                        <div class="card-body">
                                            <h4 class="card-title">Interaktiv & individuell</h4>
                                            <p class="card-text">Direkter Einfluss auf den Verlauf der Reise und Gruppendynamiken in der Kasse.</p>
                                        </div>
                                        <img class="card-img-bottom" src="/pix/theme/Interaktiv-individuell.jpg" alt="Interaktiv & individuell" style="width:100%">
                                    </div>
                                </div>
                                <div class="col-12 col-sm-4 pb-3">
                                    <div class="card card-2 h-100" >
                                        <div class="card-body">
                                            <h4 class="card-title">Emotional & persönlich</h4>
                                            <p class="card-text">Authentische Szenen, echt Zeitzeugen und unglaubliche Begegnungen.</p>
                                        </div>
                                        <img class="card-img-bottom" src="/pix/theme/emotional-persoenlich.jpg" alt="Emotional & persönlich" style="width:100%">
                                    </div>
                                </div>
                                <div class="col-12 col-sm-4 pb-3">
                                    <div class="card card-3 h-100" >
                                        <div class="card-body">
                                            <h4 class="card-title">Unique Projektergebnis</h4>
                                            <p class="card-text">Unser Sahnehäubchen:<br>
                                                Ein Abschlussfilm in dem die Schüler eine Hauptrolle spielen.</p>
                                        </div>
                                        <img class="card-img-bottom" src="/pix/theme/Unique-Projektergebnis.jpg" alt="Unique Projektergebnis" style="width:100%">
                                    </div>
                                </div>
                            </div>
                            </p>
                        </div>
                    </div>
                    <div class="row pt-5 pb-5">
                        <div class="col-12 offset-md-1 col-md-10  text-center">
                            <h1 class="pb-5">Wir gestalten Unterricht</h1>
                            <p class="subheading pb-5">
                                Als Projekttag oder in Kombination mit einer reellen Klassenfahrt.
                            </p>
                            <p>
                                Ideal für alle LehrerInnen der Fächer Geschichte, Politik, Sozialkunde und alle die Lust haben auch unterhaltsamer, pädagogischer und inhaltlicher Ebene mit Ihrer Klasse auf eine ganz besondere Reise zu gehen.
                            </p>
                        </div>
                    </div>
                    <div class="row pt-5 pb-5">
                        <div class="col-12 offset-md-1 col-md-10">
                            <p>
                            <div class="row">
                                <div class="col-12 col-sm-6  pb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <ul class="fa-ul home-list">
                                                <li class="pl-2 pb-1"><span class="fa-li"><i class="fas fa-check"></i></span>Ideal geeignet für die Klassenstufen 9 - 12</li>
                                                <li class="pl-2 pb-1"><span class="fa-li"><i class="fas fa-check"></i></span>Die Klassengröße spielt keine Rolle</li>
                                                <li class="pl-2 pb-1"><span class="fa-li"><i class="fas fa-check"></i></span>Hohe Aufmerksamkeit bei den SchülerInnen</li>
                                                <li class="pl-2 pb-1"><span class="fa-li"><i class="fas fa-check"></i></span>Nachhaltiges Lernen</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <p class="text-left">Wie soll das funktionieren? Was muss ich beachten? Wie ist der Ablauf?</p>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="offset-md-2 col-md-8 pt-4">
                                                    <p><button type="submit" class="btn btn-secondary btn-block trip-nav-button text-transform-none" id="bookbtn" style="max-width: 300px;">Mehr erfahren</button></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </p>
                        </div>
                    </div>
                    <div class="row pt-5 pb-5">
                        <div class="col-12 offset-md-1 col-md-10">
                            <p>
                            <div class="row">
                                <div class="col-12 col-sm-6">
                                    <div class="card h-100 cluster">
                                        <div class="card-body">
                                            <h2 class="pb-5 text-left">
                                                Geringer Planungsaufwand
                                            </h2>
                                            <p class="subheading text-left">
                                                Unser intuitives System ermöglicht es Ihnen die Reise vollkommen zu
                                                genießen und sich nicht um die Organisation zu kümmern.
                                            </p>
                                            <p class="subheading text-left">
                                                Ein Rundum-Sorglos-Paket mit der
                                                kopfschmerzfrei Garantie.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="card card-5 h-100 cluster">
                                        <img class="card-img-bottom" src="/pix/theme/Geringer-Planungsaufwand.jpg" alt="Unique Projektergebnis" style="width:100%">
                                    </div>
                                </div>
                            </div>
                            </p>
                        </div>
                    </div>
                    <div class="row pt-5 pb-5">
                        <div class="offset-md-1 col-md-10">
                            <p>
                            <div class="row">
                                <div class="col-12 col-sm-6">
                                    <div class="card card-5 h-100 cluster">
                                        <img class="card-img-bottom" src="/pix/theme/oekologisch-nachhaltig.jpg" alt="Unique Projektergebnis" style="width:100%">
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="card h-100 cluster">
                                        <div class="card-body">
                                            <h2 class="pb-5 text-left">
                                                Ökologisch und
                                                nachhaltig
                                            </h2>
                                            <p class="subheading text-left">
                                                Keine umweltschädigenden langen
                                                An- und Afahrten (oder Flüge). Keine überfüllten touristische Plätze.
                                            </p>
                                            <p class="subheading text-left">
                                                Wir schützen die natürlichen endlichen Resscourcen und liefern trotzdem unvergessliche Erfahrungen.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </p>
                        </div>
                    </div>
                    <div class="row pt-5 pb-5">
                        <div class="offset-md-1 col-md-10">
                            <p>
                            <div class="row">
                                <div class="col-12 col-sm-6">
                                    <div class="card h-100 cluster">
                                        <div class="card-body">
                                            <h2 class="pb-5 text-left">
                                                Für jeden
                                                erschwinglich
                                            </h2>
                                            <p class="subheading text-left">
                                                Unsere geringen Preise ermöglichen es jedem Schüler die Welt zu entdecken.
                                            </p>
                                            <p class="subheading text-left">
                                                Mit kleinen Dingen Großes erreichen!
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="card card-5 h-100 cluster">
                                        <img class="card-img-bottom" src="/pix/theme/fuer-jeden-erschwinglich.jpg" alt="Unique Projektergebnis" style="width:100%">
                                    </div>
                                </div>
                            </div>
                            </p>
                        </div>
                    </div>
                    <div class="row pt-5 pb-5">
                        <div class="offset-md-1 col-md-10">
                            <p>
                            <div class="row">
                                <div class="col-12 col-sm-6">
                                    <div class="card card-5 h-100 cluster">
                                        <img class="card-img-bottom" src="/pix/theme/eine-unvergessliche-erinnerung.jpg" alt="Unique Projektergebnis" style="width:100%">
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="card h-100 cluster">
                                        <div class="card-body">
                                            <h2 class="pb-5 text-left">
                                                Eine unvergessliche
                                                Erinnerung!
                                            </h2>
                                            <p class="subheading text-left">
                                                Nicht nur die Reise, sondern auch das Projektergebnis bietet den SchülerInnen und den LehrerInnen eine bleibende Erinnerung.
                                            </p>
                                            <p class="subheading text-left">
                                                Sogar die Eltern können sich, mit Hilfe des Projektergebnisses mit auf die Reise genommen werden.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </p>
                        </div>
                    </div>

                    <div class="row pt-5 pb-5">
                        <div class="offset-md-1 col-md-10">
                            <h2 class="pb-5 text-center" style="font-size: 38px; font-weight: 600;">
                                Empfohlen von Lehrern, Eltern und Schülern
                            </h2>
                        </div>
                    </div>
                    <div class="row pt-5 pb-5">
                        <div class="col-md-12 text-center">
                            <p>
                            <div class="row">
                                <div class="col-12 col-sm-4 pb-3">
                                    <div class="card h-100" >
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6 pb-2">
                                                    <img class="card-img-bottom" src="/pix/theme/Sterne-bewertung-Feedbackpng.png" alt="Unique Projektergebnis" style="width:100%">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h4 class="card-title  text-left">Sehr zu empfehlen!</h4>
                                                    <p class="card-text-review text-left pb-3">Überaus unterhaltsam und lehrreich!<br>
                                                        Toll Ergebnisse. Man spürt das Herzblut und bekommt zudem einen verblüffend reale Eindrücke des Reiseziels.</p>
                                                    <p class="card-text-review text-left">
                                                        Corinna Klaud<br>
                                                        (Deutschlehrer, Bremen)
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-4 pb-3">
                                    <div class="card h-100" >
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6 pb-2">
                                                    <img class="card-img-bottom" src="/pix/theme/Sterne-bewertung-Feedbackpng.png" alt="Unique Projektergebnis" style="width:100%">
                                                </div>
                                                <div class="col-md-6 text-right">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h4 class="card-title text-left">Einfach super. Danke!  </h4>
                                                    <p class="card-text-review text-left pb-3">Uns war anfangs nicht klar, wie einzigartig und toll so eine virtuelle Reise umgesetzt werden kann. Wir sind sehr dankbar und freuen uns schon auf die Nächste!</p>
                                                    <p class="card-text-review card-text text-left">
                                                        Andreas Wetzler<br>
                                                        (Geschichtslehrer, Bochum)
                                                    </p>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-4">
                                    <div class="card h-100" >
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6 pb-2">
                                                    <img class="card-img-bottom" src="/pix/theme/Sterne-bewertung-Feedbackpng.png" alt="Unique Projektergebnis" style="width:100%">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h4 class="card-title text-left">Alles einfacher</h4>
                                                    <p class="card-text-review text-left pb-3">Uns war anfangs nicht klar, wie einzigartig und toll so eine virtuelle Reise umgesetzt werden kann. Wir sind sehr dankbar und freuen uns schon auf die Nächste!</p>
                                                    <p class="card-text-review text-left">
                                                        Christian Pola<br>
                                                        (Prof., Uni Lüneburg)
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
        }
        ?>



        <!--Inhalt nur für Startseite END-->

        <!--Custom theme slider



        <div class="fp-site-customdesc">
            <div class="container">
            <h2><?php echo $whotitle; ?></h2>
            <?php
            if ($whodesc) { ?>
                <p><?php echo $whodesc; ?></p>
                <?php
            } ?>
        </div>

        </div>-->
    <?php
    } ?>
<!--Custom theme Who We Are block-->

<?php

    if (isloggedin()) {
        echo '<div id="page" class="container">
              <header id="page-header" class="clearfix">';

        echo $html->heading;
        echo '<div id="course-header">';
        echo $OUTPUT->course_header();
        echo ' </div> </header>';
        echo ' <div id="page-content" class="row">';
        if (!empty($OUTPUT->blocks_for_region('side-pre'))) {
            $class = "col-md-9";
        } else {
            $class = "col-md-12";
        }
        echo '<div id="'.$regionbsid.'"  class="'.$class.'">';
        echo $OUTPUT->course_content_header();
        echo $OUTPUT->main_content();
        echo $OUTPUT->course_content_footer();
        echo '</div>';
        echo $OUTPUT->blocks('side-pre', 'col-md-3');
        echo '</div>';
        echo (isset($flatnavbar)) ? $flatnavbar : "";
        echo '</div>';
    } else {
        echo'<div style="display:none;">';
        echo $OUTPUT->course_content_header();
        echo $OUTPUT->main_content();
        echo $OUTPUT->course_content_footer();
        echo '</div>';
    }

?>

<?php
    require_once(dirname(__FILE__) . '/includes/footer.php');
    echo $footerlayout;

?>
<!--Custom theme footer-->

</body>
</html>