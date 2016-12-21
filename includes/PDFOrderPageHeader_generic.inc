<?php
/* $Id: PDFOrderPageHeader_generic.inc 5816 2013-02-07 07:54:41Z daintree $*/
/* pdf-php by R&OS code to set up a new sales order page */
if ($PageNumber>1){
	$pdf->newPage();
}

$XPos = $Page_Width/2 - 60;

/* CUSTOM DESIGNED FORM */
/*LOGO comes with company address already*/
    $pdf->addJpegFromFile($_SESSION['LogoFile'],15,715,0,110);
$FontSize=18;

if ($Copy=='Customer'){
	$pdf->addText($XPos-60, 720,$FontSize, _('Delivery Order') . ' - ' . _('Customer Copy') );
} else {
	$pdf->addText($XPos-45, 720,$FontSize, _('Delivery Order') . ' - ' . _('Office Copy') );
}

/* if the deliver blind flag is set on the order, we do not want to output
the company contact info */
/*if ($DeliverBlind < 2) {
    $FontSize=14;
    $YPos = 480;
    $pdf->addText($XPos, $YPos,$FontSize, $_SESSION['CompanyRecord']['coyname']);
    $FontSize =8;
    $pdf->addText($XPos, $YPos-12,$FontSize, $_SESSION['CompanyRecord']['regoffice1']);
    $pdf->addText($XPos, $YPos-21,$FontSize, $_SESSION['CompanyRecord']['regoffice2']);
    $pdf->addText($XPos, $YPos-30,$FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);
    $pdf->addText($XPos, $YPos-39,$FontSize, _('Ph') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
    $pdf->addText($XPos, $YPos-48,$FontSize, $_SESSION['CompanyRecord']['email']);
}*/


$FontSize=18;
$XPos = 30;
$YPos = 690;

$pdf->addText($XPos, $YPos, $FontSize, _('D/O'));
$pdf->addText($XPos+40, $YPos, $FontSize, $myrow['orderno']);
$FontSize=14;
$YPos -= 30;

$pdf->addText($XPos, $YPos,$FontSize, _('Delivered To').':' );
$FontSize=12;
$pdf->addText($XPos, $YPos-15,$FontSize, $myrow['deliverto']);
$pdf->addText($XPos, $YPos-30,$FontSize, $myrow['deladd1']);
$pdf->addText($XPos, $YPos-45,$FontSize, $myrow['deladd2']);
$pdf->addText($XPos, $YPos-60,$FontSize, $myrow['deladd3'] . ' ' . $myrow['deladd4'] . ' ' . $myrow['deladd5'] . ' '/* . $myrow['deladd6']*/);

$YPos -= 80;
/*
$pdf->addText($XPos, $YPos,$FontSize, _('Customer').':');
$pdf->addText($XPos, $YPos-15,$FontSize, $myrow['name']);
$pdf->addText($XPos, $YPos-30,$FontSize, $myrow['address1']);
$pdf->addText($XPos, $YPos-45,$FontSize, $myrow['address2']);
$pdf->addText($XPos, $YPos-60,$FontSize, $myrow['address3'] . ' ' . $myrow['address4'] . ' ' . $myrow['address5']. ' ' . $myrow['address6']);
*/
/*
$pdf->addText($XPos, $YPos,$FontSize, _('Customer No.'). ' : ' . $myrow['debtorno']);
$pdf->addText($XPos, $YPos-20,$FontSize, _('Shipped by'). ' : ' . $myrow['shippername']);
*/

//$LeftOvers = $pdf->addTextWrap($XPos,$YPos-10,170,$FontSize,_('Comments').':'.stripcslashes($myrow['comments']));
/*
if (mb_strlen($LeftOvers)>1){
	$LeftOvers = $pdf->addTextWrap($XPos,$YPos-145,170,$FontSize,$LeftOvers);
	if (mb_strlen($LeftOvers)>1){
		$LeftOvers = $pdf->addTextWrap($XPos,$YPos-160,170,$FontSize,$LeftOvers);
		if (mb_strlen($LeftOvers)>1){
			$LeftOvers = $pdf->addTextWrap($XPos,$YPos-175,170,$FontSize,$LeftOvers);
			if (mb_strlen($LeftOvers)>1){
				$LeftOvers = $pdf->addTextWrap($XPos,$YPos-180,170,$FontSize,$LeftOvers);
			}
		}
	}
}*/
$YPos = 690;
$XPos = 360;
$Offset= 100;
$RightYPos = $YPos-10;
$pdf->addText($XPos, $RightYPos,$FontSize, _('GST REG. NO.'));
$pdf->addText($XPos+$Offset, $RightYPos,$FontSize, _(': ' . '2000046280D'));
$pdf->addText($XPos, $RightYPos-15,$FontSize, _('YOUR REF'));
$pdf->addText($XPos+$Offset, $RightYPos-15,$FontSize, ': ' . $_POST['salesref']);
$pdf->addText($XPos, $RightYPos-30,$FontSize,  _('DATE'));
$pdf->addText($XPos+$Offset, $RightYPos-30,$FontSize,  ': ' . ConvertSQLDate($myrow['ddate']));
$pdf->addText($XPos, $RightYPos-45,$FontSize,  _('TERMS'));
$pdf->addText($XPos+$Offset, $RightYPos-45,$FontSize, ': ' .  $myrow['paymentterms']);
$pdf->addText($XPos, $RightYPos-60,$FontSize,  _('ACCOUNT NO'));
$pdf->addText($XPos+$Offset, $RightYPos-60,$FontSize, ': ' .  $myrow['debtorno']);
$pdf->addText($XPos, $RightYPos-75,$FontSize,  _('Page'));
$pdf->addText($XPos+$Offset, $RightYPos-75,$FontSize, ': ' .  $PageNumber);

$YPos -= 150;
$XPos = 30;

$header_line_height = $line_height+25;

$SNXPos = $XPos;
$DescXPos = $SNXPos+35;
$UnitXPos = $DescXPos+370;
$QtyXPos = $UnitXPos + 55;
$SNW = 40;
$DescW = 385;
$UnitW = 85;
$QtyW = 85;
/*
$LeftOvers = $pdf->addTextWrap($XPos,$YPos,127,$FontSize, _('Item Code'),'left');
$LeftOvers = $pdf->addTextWrap(147,$YPos,255,$FontSize, _('Item Description'),'left');
$LeftOvers = $pdf->addTextWrap(400,$YPos,85,$FontSize, _('Quantity'),'right');
$LeftOvers = $pdf->addTextWrap(487,$YPos,70,$FontSize, _('Bin Locn'),'center');
$LeftOvers = $pdf->addTextWrap(573,$YPos,85,$FontSize,_('This Del'),'center');
$LeftOvers = $pdf->addTextWrap(672,$YPos,85,$FontSize, _('Prev Dels'),'center');
*/
$LeftOvers = $pdf->addTextWrap($XPos,$YPos,$SNW,$FontSize, _('S/N'),'left');
$LeftOvers = $pdf->addTextWrap($DescXPos,$YPos,$DescW,$FontSize, _('Item Description'),'left');
$LeftOvers = $pdf->addTextWrap($UnitXPos,$YPos,$UnitW,$FontSize, _('Unit'),'center');
$LeftOvers = $pdf->addTextWrap($QtyXPos,$YPos,$QtyW,$FontSize, _('Quantity'),'center');

$YPos -= $line_height;

$FontSize =12;


//Footer

$pdf->addText(30, 30, $FontSize, _("Company's Stamp and Signature"));
$pdf->addText(340, 100, $FontSize, _("for MING KEE METAL WORKS PTE LTD"));
$pdf->addText(450, 30, $FontSize, _("Authorized Signature"));


//Draw some boxes for the fields

//DO no box
$pdf->RoundRectangle(25, 688, 220, 20, 2, 2);
$pdf-> line(26, 669, 244, 669);

//DELIVERY ADDR box
$pdf-> RoundRectangle(25, 660, 220, 85, 2,2);

//ITEM box
$pdf->RoundRectangle(25, 555, 540, 450, 2,2);
$pdf->line(25, 533, 565, 533);
$pdf->line(60, 555, 60, 105);
$pdf ->line(455,555,455,105);
$pdf ->line(500,555,500,105);

//FOOTER LINES
$pdf->line(25, 30, 205, 30);
$pdf->line(450, 30, 560, 30);
?>
