<?php
if ($PageNumber>1){
	$pdf->newPage();
}


/* CUSTOM DESIGNED FORM */
/*LOGO comes with company address already*/
$pdf->addJpegFromFile($_SESSION['LogoFile'],15,715,0,110);
$FontSize=18;
$linestyle = array(
				width => '1',
				dash => ''
			);



$XPos = 40;
$YPos = 700;

//Company Address
$FontSize=14;
$pdf->addText($XPos, $YPos,$FontSize, $_POST['name']);
$FontSize=12;
$pdf->addText($XPos, $YPos-15,$FontSize, $_POST['addr1']);
$pdf->addText($XPos, $YPos-30,$FontSize, $_POST['addr2']);
$pdf->addText($XPos, $YPos-45,$FontSize, $_POST['addr3']);
$pdf->addText($XPos, $YPos-60,$FontSize, $_POST['addr4'] . ' ' . $myrow['addr5']);

//Date
$pdf->addText(410, $YPos, $FontSize, "Date :");
$pdf->addText(450, $YPos, $FontSize, ConvertSQLDate($_POST['date']));

$YPos -= 100;
$FontSize=14;
$pdf->addText($Page_Width/2 - 106, $YPos,$FontSize, _('CERTIFICATE OF CONFORMITY'));
$pdf->line($Page_Width/2-105, $YPos-16, $Page_Width/2+105, $YPos-16, $linestyle);
$FontSize=12;
$YPos -= 30;
//BOTTOM
$pdf->addText($XPos, 100, $FontSize, "Authorised By:");
$FontSize = 10;
$pdf->line ($Page_Width/2-120, 85, $Page_Width/2+120, 85, $linestyle);
$pdf->addText($Page_Width/2-50, 83, $FontSize, "(Quality Representative)");
$pdf->addJpegFromFile("css/ktsignature.jpg", 200, 87, 200, 60);
$FontSize =12;
?>
