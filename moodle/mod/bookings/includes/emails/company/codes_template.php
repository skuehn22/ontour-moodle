<?php

echo'<html><head>
<title>Video Event Berlin // '.$b_id.'</title>
<link rel="important stylesheet" href="chrome://messagebody/skin/messageBody.css">
</head>
<body style="padding: 0;" offset="0" topmargin="0" marginwidth="0" marginheight="0" leftmargin="0">

<meta http-equiv="Content-Type" content="text/html; ">
		<title>onTour</title>
	
	<div class="moz-text-html" lang="x-unicode">
		<div id="wrapper" dir="ltr" style="background-color: #49aedb; margin: 0; padding: 70px 0; width: 100%; padding-top: 25px; -webkit-text-size-adjust: none;" bgcolor="#49aedb" width="100%">
			<table style="border: 0;" width="100%" height="100%" cellspacing="0" cellpadding="0" border="0">
				<tbody><tr>
					<td valign="top" align="center">
						<div id="template_header_image">

						</div>
						<table id="template_container" style="background-color: #49aedb; border: 0px solid #429dc5; box-shadow: 0 1px 4px rgba(0,0,0,.1); border-radius: 3px;" width="600" cellspacing="0" cellpadding="0" border="0" bgcolor="#49aedb">
							<!--
							<tr>
								<td align="center" valign="top">
									<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header">
										<tr>
											<td id="header_wrapper">
												<h1>Danke für den Einkauf bei uns</h1>
											</td>
										</tr>
									</table>
								</td>
							</tr>
-->
							<tbody><tr>
								<td valign="top" align="center">
									<!-- Body -->
									<table id="template_body" width="600" cellspacing="0" cellpadding="0" border="0">
										<tbody><tr>
											<td id="body_content" style="background-color: #fff; border-radius: 16px;" valign="top" bgcolor="#fff">
												<!-- Content -->
												<table width="100%" cellspacing="0" cellpadding="20" border="0">
													<tbody><tr>
                                                        <td style="padding: 48px 48px 32px; text-align: center; padding-bottom: 0px;" align="center">
                                                            <p style="margin: 0 0 16px; margin-top: 0; padding-bottom: 0px;"><img src="https://ontour.org/wp-content/uploads/2021/12/Logo-onTour-1.png" alt="onTour" style="border: none; display: inline-block; font-size: 14px; font-weight: bold; height: auto; outline: none; text-decoration: none; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; width: 180px;" shrinktofit="true" width="180" border="0"></p>                                                        </td>
                                                    </tr>
                                                    <tr>
														<td style="padding: 48px 48px 32px; padding-top: 20px; padding-bottom: 0px;" valign="top">
															<div id="body_content_inner" style="color: #696969; font-family: &quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif; font-size: 14px; line-height: 150%; text-align: left;" align="left">
															
															
	<p style="margin: 0 0 13px; color: #49AEDB; font-weight: 700; font-size: 12px;">'.$booking->school.'</p>		';

    if($op->name != "Direktbuchung"){
        echo'
	<p style="margin: 0 0 13px; color: #49AEDB; font-weight: 700; font-size: 12px;">'.$op->name.' // '.$booking->ext_booking_id.'</p>';
    }



	echo '
    <p style="margin: 0 0 15px; font-weight: 700; font-size: 16px;">'.$anrede.',</p>';

    if($booking->operators_id == 1){
        echo'
        <p style="margin: 0 0 16px; line-height: normal;">
            <span style="font-size: 12px; line-height: normal;"></span></p><p style="margin: 0 0 16px;">vielen Dank für Ihre Buchung. Wir freuen uns sehr, dass Sie am Videoevent Berlin teilnehmen. 
        </p>';
    }else{
        echo'
        <p style="margin: 0 0 16px; line-height: normal;">
            <span style="font-size: 12px; line-height: normal;"></span></p><p style="margin: 0 0 16px;">wir freuen uns sehr, dass Sie über '.$op->name.' das Videoevent Berlin gebucht haben.  
        </p>';
    }


    echo'
    <p style="margin: 0 0 16px;">Mit dieser Mail erhalten Sie Ihren Zugang für Ihren Kundenbereich zum Videoevent Berlin. Die Zugangs Codes sind ggf. an die durchführenden Teams weiterzugeben.</p>
    <p style="margin: 0 0 16px;">Im Kundenberiech finden Sie vorbereitendes Material, Karten und die Zugänge für die onTour App. </p>
    <p style="margin: 0 0 16px;"></p>'.
    $z_codes_string.'<p>Wir sind gern für Sie da! Sie erreichen uns unter 030-62931521 oder nutzen Sie unser <a href="https://ontour.org/kontakt/" target="_blank">Kontaktformular</a> oder schreiben Sie uns eine E-Mail.  </p>
    <p>Viel Spaß und Beste Grüße  </p><p>Ihr onTour Team Berlin. 
</p>';

    if($booking->operators_id == 1){
        echo 'Sollten Sie das Videoevent schon in den kommenden Tagen durchführen, überweisen Sie die Rechnung bitte sofort. Falls Sie per Paypal gezahlt haben, ist der Rechnungsbetrag schon beglichen. 
';
    }

if($booking->product == 10){

    echo'
<br> 

    <table style="width: 100%; border-spacing: inherit; padding-bottom: 25px;" width="100%" cellspacing="inherit">
        <tbody><tr>
            <td style="padding: 12px; text-align: left; padding-bottom: 0px; padding-left: 80px;" align="left">
                <a href="https://reisen.ontour.org/zugangscode?code=true" target="_blank" style="font-weight: normal; font-size: 15px; background-color: #49aedb; border-radius: 6px; color: #fff; padding: 12px; text-decoration: none;" bgcolor="#49aedb">Zu Ihrem Videoevent</a>

            </td>
            <td style="padding: 12px; text-align: right; padding-bottom: 0px; padding-right: 80px;" align="right">
                <img src="https://ontour.org/wp-content/uploads/Durchfu%CC%88hrung-plattform.png" style="border: none; display: inline-block; font-size: 14px; font-weight: bold; height: auto; outline: none; text-decoration: none; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; width: 90px;" shrinktofit="true" width="90" border="0">
            </td>
        </tr>
    </tbody></table>';



}else{

    echo'
<br> 

    <table style="width: 100%; border-spacing: inherit; padding-bottom: 25px;" width="100%" cellspacing="inherit">
        <tbody><tr>
            <td style="padding: 12px; text-align: left; padding-bottom: 0px; padding-left: 80px;" align="left">
                <a href="https://reisen.ontour.org/zugangscode?code=true" target="_blank" style="font-weight: normal; font-size: 15px; background-color: #49aedb; border-radius: 6px; color: #fff; padding: 12px; text-decoration: none;" bgcolor="#49aedb">Zu Ihrem Videoprojekt</a>

            </td>
            <td style="padding: 12px; text-align: right; padding-bottom: 0px; padding-right: 80px;" align="right">
                <img src="https://ontour.org/wp-content/uploads/Durchfu%CC%88hrung-plattform.png" style="border: none; display: inline-block; font-size: 14px; font-weight: bold; height: auto; outline: none; text-decoration: none; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; width: 90px;" shrinktofit="true" width="90" border="0">
            </td>
        </tr>
    </tbody></table>';



}







if($booking->operators_id == 1){

    echo'
    <h2 style="display: block; font-family: &quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif; font-size: 18px; font-weight: bold; line-height: 130%; margin: 0 0 18px; text-align: left; color: #696969;">
    BuchnungsNr.: '.$booking->order_id.'</h2>

    <div style="margin-bottom: 40px;">
        <table class="td" style="color: #696969; vertical-align: middle; width: 100%; font-family:  Helvetica, Roboto, Arial, sans-serif; border-radius: 10px; border: 2px solid #49AEDB; font-size: 11px;" width="100%" cellspacing="0" cellpadding="6">
            <thead>
                <tr>
                    <!--<th class="td" scope="col" style="border:0; text-align:left;">Kurs</th>-->
                    <th class="td" scope="col" style="color: #696969; vertical-align: middle; padding: 12px; border: 0; text-align: left;" align="left"></th>
                    <!--<th class="td" scope="col" style="border:0; text-align:left;">Anzahl</th>-->
                    <th class="td" scope="col" style="color: #696969; vertical-align: middle; padding: 12px; border: 0; text-align: left;" align="left"></th>
                    <th class="td" scope="col" style="color: #696969; vertical-align: middle; padding: 12px; border: 0; text-align: right;" align="right"></th>
                </tr>
            </thead>';


    $i = 0;



    foreach ($data as $d){


        echo '<tbody>
				<tr class="order_item">
		            <td colspan="3" class="td" style="color: #696969; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; border-right: 0px;" align="left">
                        
                        <ul class="wc-item-meta" style="font-size: small; margin: 1em 0 0; padding: 0; list-style: none;">
                            <li style="margin: .5em 0 0; padding: 0;">
                            <strong class="wc-item-meta-label" style="font-size: 11px; float: left; margin-right: .25em; clear: both;">Tag der Durchführung:</strong> <p style="font-size: 11px; margin: 0;">'.$d['arr'].'</p>
                            </li>
                         
                            <li style="margin: .5em 0 0; padding: 0;">
                            <strong class="wc-item-meta-label" style="font-size: 11px; float: left; margin-right: .25em; clear: both;">Anzahl Teilnehmer:</strong> <p style="font-size: 11px; margin: 0;">'.$d['amount'].'</p>
                            </li>
                          
                        </ul>		
                    </td>

                
	            </tr>';

        $i++;

        echo '</tbody>';

    };



    echo'	
		

		<tfoot>
			<tr>
                <th class="td" scope="row" colspan="2" style="color: #696969; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left; border-top-width: 4px; padding-bottom: 5px; padding-top: 5px;" align="left">Zwischensumme:</th>
                <td class="td" style="color: #696969; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: right; border-top-width: 4px; padding-bottom: 5px; padding-top: 5px;" align="right">
                    <span class="woocommerce-Price-amount amount">'.$data[0]['line_total'].'&nbsp;<span class="woocommerce-Price-currencySymbol">€</span></span> <small class="tax_label"></small>                        
                </td>
            </tr>
			<tr>
                <th class="td" scope="row" colspan="2" style="color: #696969; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left; padding-bottom: 5px; padding-top: 5px;" align="left">19 % MwSt.</th>
                <td class="td" style="color: #696969; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: right; padding-bottom: 5px; padding-top: 5px;" align="right">
                    <span class="woocommerce-Price-amount amount">'.$data[0]['tax'].'&nbsp;<span class="woocommerce-Price-currencySymbol">€</span></span>                        
                </td>
		    </tr>
			<tr>
                <th class="td" scope="row" colspan="2" style="color: #696969; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left; padding-bottom: 5px; padding-top: 5px;" align="left">Gesamt:</th>
                <td class="td" style="color: #696969; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: right; padding-bottom: 5px; padding-top: 5px;" align="right"> 
                    <span class="woocommerce-Price-amount amount">'.$data[0]['total'].'&nbsp;<span class="woocommerce-Price-currencySymbol">€</span></span>                        
                </td>
			</tr>
			</tfoot>';

            echo'
                                    
            </table>
        </div>
        
        <table id="tracking" style="width: 100%; vertical-align: top; margin-bottom: 40px; padding: 0;" width="100%" cellspacing="0" cellpadding="0" border="0">
            <tbody><tr>
                <td style="text-align: left; font-family: Helvetica, Roboto, Arial, sans-serif; border: 0; padding: 0;" valign="top" align="left">
                            </td>
            </tr>
        </tbody></table>
        <table id="addresses" style="width: 100%; vertical-align: top; margin-bottom: 40px; padding: 0;" width="100%" cellspacing="0" cellpadding="0" border="0">
            <tbody><tr>
                <td style="text-align: left; font-family: Helvetica, Roboto, Arial, sans-serif; border: 0; padding: 0;" width="50%" valign="top" align="left">
                    <!--<h2>Schuladresse</h2>-->
                    <h2 style="display: block; font-family: &quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif; font-size: 18px; font-weight: bold; line-height: 130%; margin: 0 0 18px; text-align: left; color: #696969;">Ihre Daten</h2>
        
                    <address class="address" style="padding: 12px; color: #696969; font-size: 11px; border-radius: 10px; border: 2px solid #49AEDB;">
                        <span data-no-translation="">Name der Schule: '.$booking->school.'<br>'.$data[0]['firstname'].' '.$data[0]['lastname'].'<br>'.$data[0]['addr'].'<br>'.$data[0]['zip'].' '.$data[0]['city'].'</span>													<br>'.$data[0]['email'].'							</address>
                </td>
                    </tr>
        </tbody></table>';

}


echo'


<table style="width: 100%; border-spacing: inherit;" width="100%" cellspacing="inherit">
    <tbody><tr>
        <td style="padding: 12px; text-align: left; padding-bottom: 0px;" align="left">
    Beste Grüße aus Berlin<br>
            <img src="https://ontour.org/wp-content/uploads/Kai-Unterschrift.png" style="border: none; display: inline-block; font-size: 14px; font-weight: bold; height: auto; outline: none; text-decoration: none; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; width: 100px;" shrinktofit="true" width="100" border="0">
            <br>Kai Lübeck
</td>
        <td style="padding: 12px; text-align: right; padding-bottom: 0px;" align="right"><img src="https://ontour.org/wp-content/uploads/Kai-Lu%CC%88beck-Profilbild.png" style="border: none; display: inline-block; font-size: 14px; font-weight: bold; height: auto; outline: none; text-decoration: none; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; width: 150px;" shrinktofit="true" width="150" border="0"></td>
    </tr>
</tbody></table>

<table style="width: 100%; border-spacing: inherit;" width="100%" cellspacing="inherit">
    <tbody>
    <tr>
    <td colspan="2">
    <br>
    <hr>
</td>
</tr>
    <tr>
    <td style="padding: 12px; text-align: left; padding-bottom: 0px; font-size: 10px; color: #2e2e2e; vertical-align: top" align="left">
    	<p>onTour Media GmbH<br>
                                                Schönhauser Allee 36 - 39 | 10435 Berlin | Kulturbrauerei<br><br>
                                                  <a href="https://www.ontour.org">www.ontour.org</a><br><br>
                                                
                                                Tel. +49 (030) 6293 1521<br>
                                                E-Mail: <a href="mailto:kontakt@ontour.org">kontakt@ontour.org</a>
                                                
                                              </p>
</td>

</tr>
<tr>
<td colspan="2"><br><br></td>
</tr>    
</tbody></table>



															</div>
														</td>
													</tr>
												</tbody></table>
												<!-- End Content -->
											</td>
										</tr>
									</tbody></table>
									<!-- End Body -->
								</td>
							</tr>
						</tbody></table>
					</td>
				</tr>
				<tr>
					<td valign="top" align="center">
						<!-- Footer -->
						<table id="template_footer" width="600" cellspacing="0" cellpadding="10" border="0">
							<tbody><tr>
								<td style="padding: 0; border-radius: 6px;" valign="top">
                                    <!--
									<table border="0" cellpadding="10" cellspacing="0" width="100%">
										<tr>
											<td colspan="2" valign="middle" id="credit">
												<p>@ onTour</p>
											</td>
										</tr>
									</table>
-->
								</td>
							</tr>
						</tbody></table>
						<!-- End Footer -->
					</td>
				</tr>
			</tbody></table>
		</div>
	


</div>

</body>
</html>';
?>