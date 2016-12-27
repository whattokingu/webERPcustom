<?php

/* $Id: PrintCustTrans.php 7238 2015-03-27 13:56:35Z exsonqu $ */

function translateTerms($term){
	$sql = "SELECT terms FROM paymentterms WHERE termsindicator='".$term."'";
	$resq = DB_query($sql, "Something went wrong with payment terms.");
	$res = DB_fetch_array($resq);
	return $res['terms'];
}

function echoObj($obj){
	echo '<table>';
	foreach ($obj as $key => $value) {
        echo "<tr>";
        echo "<td>";
        echo $key;
        echo "</td>";
        echo "<td>";
        echo $value;
        echo "</td>";
        echo "</tr>";
    }
	echo '</table>';
}

function breakTextToLines($text, $line){
	$strarr = explode('\r\n', $text);
	$size = sizeof($strarr);
	if($line <= ($size)){
		return $strarr[($line-1)];
	}else{
		return "";
	}
}
include('includes/session.inc');

$ViewTopic = 'ARReports';
$BookMark = 'PrintInvoicesCredits';
if (isset($_GET['FromTransNo'])) {
	$FromTransNo = trim($_GET['FromTransNo']);
} elseif (isset($_POST['FromTransNo'])) {
	$FromTransNo = filter_number_format($_POST['FromTransNo']);
}

if (isset($_GET['InvOrCredit'])) {
	$InvOrCredit = $_GET['InvOrCredit'];
} elseif (isset($_POST['InvOrCredit'])) {
	$InvOrCredit = $_POST['InvOrCredit'];
}

if (isset($_GET['PrintPDF'])) {
	$PrintPDF = TRUE;
} elseif (isset($_POST['PrintPDF'])) {
	$PrintPDF = TRUE;
}

if (!isset($_POST['ToTransNo'])
	OR trim($_POST['ToTransNo'])==''
	OR filter_number_format($_POST['ToTransNo']) < $FromTransNo) {

	$_POST['ToTransNo'] = $FromTransNo;
}

$FirstTrans = $FromTransNo; /* Need to start a new page only on subsequent transactions */

if (isset($PrintPDF) AND isset($FromTransNo) AND isset($InvOrCredit)){
	// include ('includes/class.pdf.php');

	/* This invoice is hard coded for A4 Landscape invoices or credit notes so can't use PDFStarter.inc */
	// $Page_Width=595;
	// $Page_Height=842;
	// $Top_Margin=30;
	// $Bottom_Margin=30;
	// $Left_Margin=40;
	// $Right_Margin=30;

	// $pdf = new Cpdf('L', 'pt', 'A4');
	// $pdf->addInfo('Creator', 'webERP http://www.weberp.org');
	// $pdf->addInfo('Author', 'webERP ' . $Version);

	$PaperSize = 'A4';
	include('includes/PDFStarter.php');
	if ($InvOrCredit=='Invoice') {
		$pdf->addInfo('Title',_('Sales Invoice') . ' ' . $FromTransNo . ' ' . _('to') . ' ' . $_POST['ToTransNo']);
		$pdf->addInfo('Subject',_('Invoices from') . ' ' . $FromTransNo . ' ' . _('to') . ' ' . $_POST['ToTransNo']);
	} else {
		$pdf->addInfo('Title',_('Sales Credit Note') );
		$pdf->addInfo('Subject',_('Credit Notes from') . ' ' . $FromTransNo . ' ' . _('to') . ' ' . $_POST['ToTransNo']);
	}

	// $pdf->setAutoPageBreak(1);
	// $pdf->setPrintHeader(false);
	// $pdf->setPrintFooter(false);
	// $pdf->AddPage();
	// $pdf->cMargin = 0;
/* END Brought from class.pdf.php constructor */

	$FirstPage = true;
	$line_height=16;

	//Keep a record of the user's language
	$UserLanguage = $_SESSION['Language'];

	while ($FromTransNo <= filter_number_format($_POST['ToTransNo'])){
	/* retrieve the invoice details from the database to print
	notice that salesorder record must be present to print the invoice purging of sales orders will
	nobble the invoice reprints */

		if ($InvOrCredit=='Invoice') {
			$sql = "SELECT debtortrans.trandate,
							debtortrans.ovamount,
							debtortrans.ovdiscount,
							debtortrans.ovfreight,
							debtortrans.ovgst,
							debtortrans.rate,
							debtortrans.invtext,
							debtortrans.packages,
							debtortrans.consignment,
							debtorsmaster.name,
							debtorsmaster.address1,
							debtorsmaster.address2,
							debtorsmaster.address3,
							debtorsmaster.address4,
							debtorsmaster.address5,
							debtorsmaster.address6,
							debtorsmaster.currcode,
							debtorsmaster.invaddrbranch,
							debtorsmaster.taxref,
							debtorsmaster.language_id,
							paymentterms.terms,
							salesorders.deliverto,
							salesorders.deladd1,
							salesorders.deladd2,
							salesorders.deladd3,
							salesorders.deladd4,
							salesorders.deladd5,
							salesorders.deladd6,
							salesorders.customerref,
							salesorders.orderno,
							salesorders.orddate,
							locations.locationname,
							shippers.shippername,
							custbranch.brname,
							custbranch.braddress1,
							custbranch.braddress2,
							custbranch.braddress3,
							custbranch.braddress4,
							custbranch.braddress5,
							custbranch.braddress6,
							custbranch.brpostaddr1,
							custbranch.brpostaddr2,
							custbranch.brpostaddr3,
							custbranch.brpostaddr4,
							custbranch.brpostaddr5,
							custbranch.brpostaddr6,
							salesman.salesmanname,
							debtortrans.debtorno,
							debtortrans.branchcode,
							currencies.decimalplaces
						FROM debtortrans INNER JOIN debtorsmaster
						ON debtortrans.debtorno=debtorsmaster.debtorno
						INNER JOIN custbranch
						ON debtortrans.debtorno=custbranch.debtorno
						AND debtortrans.branchcode=custbranch.branchcode
						INNER JOIN salesorders
						ON debtortrans.order_ = salesorders.orderno
						INNER JOIN shippers
						ON debtortrans.shipvia=shippers.shipper_id
						INNER JOIN salesman
						ON custbranch.salesman=salesman.salesmancode
						INNER JOIN locations
						ON salesorders.fromstkloc=locations.loccode
						INNER JOIN locationusers
						ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
						INNER JOIN paymentterms
						ON debtorsmaster.paymentterms=paymentterms.termsindicator
						INNER JOIN currencies
						ON debtorsmaster.currcode=currencies.currabrev
						WHERE debtortrans.type=10
						AND debtortrans.transno='" . $FromTransNo . "'";

			if (isset($_POST['PrintEDI']) AND $_POST['PrintEDI']=='No') {
				$sql = $sql . " AND debtorsmaster.ediinvoices=0";
			}
		} else {
			$sql = "SELECT debtortrans.trandate,
							debtortrans.ovamount,
							debtortrans.ovdiscount,
							debtortrans.ovfreight,
							debtortrans.ovgst,
							debtortrans.rate,
							debtortrans.invtext,
							debtorsmaster.invaddrbranch,
							debtorsmaster.name,
							debtorsmaster.address1,
							debtorsmaster.address2,
							debtorsmaster.address3,
							debtorsmaster.address4,
							debtorsmaster.address5,
							debtorsmaster.address6,
							debtorsmaster.currcode,
							debtorsmaster.taxref,
							debtorsmaster.language_id,
							custbranch.brname,
							custbranch.braddress1,
							custbranch.braddress2,
							custbranch.braddress3,
							custbranch.braddress4,
							custbranch.braddress5,
							custbranch.braddress6,
							custbranch.brpostaddr1,
							custbranch.brpostaddr2,
							custbranch.brpostaddr3,
							custbranch.brpostaddr4,
							custbranch.brpostaddr5,
							custbranch.brpostaddr6,
							salesman.salesmanname,
							debtortrans.debtorno,
							debtortrans.branchcode,
							paymentterms.terms,
							currencies.decimalplaces
						FROM debtortrans INNER JOIN debtorsmaster
						ON debtortrans.debtorno=debtorsmaster.debtorno
						INNER JOIN custbranch
						ON debtortrans.debtorno=custbranch.debtorno
						AND debtortrans.branchcode=custbranch.branchcode
						INNER JOIN salesman
						ON custbranch.salesman=salesman.salesmancode
						INNER JOIN paymentterms
						ON debtorsmaster.paymentterms=paymentterms.termsindicator
						INNER JOIN currencies
						ON debtorsmaster.currcode=currencies.currabrev
						WHERE debtortrans.type=11
						AND debtortrans.transno='" . $FromTransNo . "'";

			if (isset($_POST['PrintEDI']) AND $_POST['PrintEDI']=='No')	{
				$sql = $sql . " AND debtorsmaster.ediinvoices=0";
			}
		} // end else

		$result=DB_query($sql, '',  '',false, false);
		
		if (DB_error_no()!=0) {
			$Title = _('Transaction Print Error Report');
			include ('includes/header.inc');
			prnMsg( _('There was a problem retrieving the invoice or credit note details for note number') . ' ' . $InvoiceToPrint . ' ' . _('from the database') . '. ' . _('To print an invoice, the sales order record, the customer transaction record and the branch record for the customer must not have been purged') . '. ' . _('To print a credit note only requires the customer, transaction, salesman and branch records be available'),'error');
			if ($debug==1) {
				prnMsg (_('The SQL used to get this information that failed was') . '<br />' . $sql,'error');
			}
			include ('includes/footer.inc');
			exit;
		}
		
		if (DB_num_rows($result)==1) {
			$myrow = DB_fetch_array($result);
			$ExchRate = $myrow['rate'];
			//Change the language to the customer's language
			$_SESSION['Language'] = $myrow['language_id'];
			include('includes/LanguageSetup.php');
			for($i=1;$i<=2;$i++){
				if ($i==2){
				$PageNumber = 1;
				$pdf->newPage();
				}

				if ($InvOrCredit=='Invoice') {

					
					$sql = "SELECT stockmoves.stockid,
									stockmaster.description,
									-stockmoves.qty as quantity,
									stockmoves.discountpercent,
									((1 - stockmoves.discountpercent) * stockmoves.price * " . $ExchRate . "* -stockmoves.qty) AS fxnet,
									(stockmoves.price * " . $ExchRate . ") AS fxprice,
									stockmoves.narrative,
									stockmaster.units,
									stockmaster.decimalplaces
								FROM stockmoves INNER JOIN stockmaster
								ON stockmoves.stockid = stockmaster.stockid
								WHERE stockmoves.type=10
								AND stockmoves.transno=" . $FromTransNo . "
								AND stockmoves.show_on_inv_crds=1";
				} else {
			/* only credit notes to be retrieved */
					$sql = "SELECT stockmoves.stockid,
									stockmaster.description,
									stockmoves.qty as quantity,
									stockmoves.discountpercent,
									((1 - stockmoves.discountpercent) * stockmoves.price * " . $ExchRate . " * stockmoves.qty) AS fxnet,
									(stockmoves.price * " . $ExchRate . ") AS fxprice,
									stockmoves.narrative,
									stockmaster.units,
									stockmaster.decimalplaces
								FROM stockmoves INNER JOIN stockmaster
								ON stockmoves.stockid = stockmaster.stockid
								WHERE stockmoves.type=11
								AND stockmoves.transno=" . $FromTransNo . "
								AND stockmoves.show_on_inv_crds=1";
				} // end else

				$result=DB_query($sql);
				if (DB_error_no()!=0 OR (DB_num_rows($result)==0 AND $InvOrCredit == 'Invoice')) {
				
					$Title = _('Transaction Print Error Report');
					include ('includes/header.inc');
					echo '<br />' . _('There was a problem retrieving the invoice or credit note stock movement details for invoice number') . ' ' . $FromTransNo . ' ' . _('from the database');
					if ($debug==1) {
						echo '<br />' . _('The SQL used to get this information that failed was') . '<br />' . $sql;
					}
					include('includes/footer.inc');
					exit;
				} else {



					$PageNumber = 1;

					// include('includes/PDFTransPageHeader.inc');
					// echoObj($_POST);
					
					$FirstPage = False;
					$invoice = true;
					include('includes/PDFOrderPageHeader_generic.inc');
					$SN = 0;
					$numItems = DB_num_rows($result);
					while ($myrow2=DB_fetch_array($result)) {
						$SN++;
						if ($myrow2['discountpercent']==0) {
							$DisplayDiscount ='';
						} else {
							$DisplayDiscount = locale_number_format($myrow2['discountpercent']*100,2) . '%';
							$DiscountPrice=$myrow2['fxprice']*(1-$myrow2['discountpercent']);
						}
						$DisplayNet=locale_number_format($_POST['netprice'. ($SN-1)],$myrow['decimalplaces']);
						$DisplayPrice=locale_number_format($_POST['uprice'.($SN-1)],$myrow['decimalplaces']);
						$DisplayQty=locale_number_format($_POST['quantity'.($SN-1)],$myrow2['decimalplaces']);
						$FontSize=10;
						$XPos = 30;
						$LeftOvers = $pdf->addTextWrap($SNXPos,$YPos,$SNW,$FontSize, ($SN), 'center');
						$LeftOvers = $pdf->addTextWrap($DescXPos, $YPos, 150, $FontSize, $_POST['stkcode'.($SN-1)], 'left');
						$LeftOvers = $pdf->addTextWrap($DescXPos+170, $YPos, 110, $FontSize, $_POST['drev'.($SN-1)], 'right');
						$LeftOvers = $pdf->addTextWrap($QtyXPos,$YPos-12,$UnitW,$FontSize,$_POST[('units'.($SN-1))],'center');
						$LeftOvers = $pdf->addTextWrap($QtyXPos,$YPos,$QtyW,$FontSize,$DisplayQty,'center');
						$LeftOvers = $pdf->addTextWrap($UPriceXPos+10,$YPos,$UPriceW,$FontSize, $DisplayPrice,'right');
						$LeftOvers = $pdf->addTextWrap($SubTotalXPos, $YPos, $SubTotalW, $FontSize, $DisplayNet, 'right');

						$FontSize = 8;
						$LeftOvers = $pdf->addTextWrap($DescXPos,$YPos-14,$DescW,$FontSize,breakTextToLines($_POST['ddesc'.($SN-1)], 1));
						$LeftOvers = $pdf->addTextWrap($DescXPos,$YPos-28,$DescW,$FontSize,breakTextToLines($_POST['ddesc'.($SN-1)], 2));
						$LeftOvers = $pdf->addTextWrap($DescXPos,$YPos-42,$DescW,$FontSize,breakTextToLines($_POST['ddesc'.($SN-1)], 3));
						$LeftOvers = $pdf->addTextWrap($DescXPos,$YPos-56,$DescW,$FontSize,breakTextToLines($_POST['ddesc'.($SN-1)], 4));
						$FontSize = 10;
						$YPos -=70;

						if ($YPos-$line_height <= 240 && $SN < $numItems){
							/* We reached the end of the page so finsih off the page and start a newy */
							$PageNumber++;
							include ('includes/PDFOrderPageHeader_generic.inc');
						} //end if need a new page headed up
						else {
							/*increment a line down for the next line item */
							$YPos -= ($line_height);
						}

					} //end while there invoice are line items to print out
				} /*end if there are stock movements to show on the invoice or credit note*/

				if($InvOrCredit=='Invoice') {
					$DisplaySubTot = locale_number_format($myrow['ovamount'],$myrow['decimalplaces']);
					$DisplayFreight = locale_number_format($myrow['ovfreight'],$myrow['decimalplaces']);
					$DisplayTax = locale_number_format($myrow['ovgst'],$myrow['decimalplaces']);
					$DisplayTotal = locale_number_format($myrow['ovfreight']+$myrow['ovgst']+$myrow['ovamount'],$myrow['decimalplaces']);
				} else {
					$DisplaySubTot = locale_number_format(-$myrow['ovamount'],$myrow['decimalplaces']);
					$DisplayFreight = locale_number_format(-$myrow['ovfreight'],$myrow['decimalplaces']);
					$DisplayTax = locale_number_format(-$myrow['ovgst'],$myrow['decimalplaces']);
					$DisplayTotal = locale_number_format(-$myrow['ovfreight']-$myrow['ovgst']-$myrow['ovamount'],$myrow['decimalplaces']);
				}

				$linestyle = array(
					width => '1',
					dash => '6,3'
				);
				$FontSize=12;
				$pdf->addText($DescXPos, $YPos, $FontSize, $_POST['docremarks']);
				$YPos = 170;
				$pdf->line($SubTotalXPos +10, $YPos, $SubTotalXPos + 67, $YPos, $linestyle);
				$YPos -= 15;
				$LeftOvers = $pdf->addTextWrap($SubTotalXPos,$YPos,70,$FontSize,$DisplaySubTot, 'right');
				$YPos -= 20;
				$LeftOvers = $pdf->addTextWrap($DescXPos+230, $YPos, 100,$FontSize, _('Add GST (7%)'), 'left');
				$LeftOvers = $pdf->addTextWrap($SubTotalXPos, $YPos, 70, $FontSize, $DisplayTax, 'right');
				$pdf->line($SubTotalXPos +10, $YPos-6, $SubTotalXPos + 67, $YPos-6, $linestyle);
				$YPos -= 20;
				$pdf->line($SubTotalXPos +10, $YPos-5, $SubTotalXPos + 67, $YPos-5, $linestyle);
				$pdf->line($SubTotalXPos +10, $YPos-7, $SubTotalXPos + 67, $YPos-7, $linestyle);
				$LeftOvers = $pdf->addTextWrap($DescXPos + 230, $YPos, 100, $FontSize, _('Amount Due:', 'left'));
				$LeftOvers = $pdf->addTextWrap($SubTotalXPos, $YPos, 70, $FontSize, $DisplayTotal, 'right');
				$Copy='Customer';
			}//end loop to print invoice 2 times.
		} /* end of check to see that there was an invoice record to print */
		$FromTransNo++;
	
	} /* end loop to print invoices */

	/* Put the transaction number back as would have been incremented by one after last pass */
	$FromTransNo--;

	// if (isset($_GET['Email'])){ //email the invoice to address supplied
	// 	include('includes/header.inc');

	// 	include ('includes/htmlMimeMail.php');
	// 	$FileName = $_SESSION['reports_dir'] . '/' . $_SESSION['DatabaseName'] . '_' . $InvOrCredit . '_' . $FromTransNo . '.pdf';
	// 	$pdf->Output($FileName,'F');
	// 	$mail = new htmlMimeMail();

	// 	$Attachment = $mail->getFile($FileName);
	// 	$mail->setText(_('Please find attached') . ' ' . $InvOrCredit . ' ' . $FromTransNo );
	// 	$mail->SetSubject($InvOrCredit . ' ' . $FromTransNo);
	// 	$mail->addAttachment($Attachment, $FileName, 'application/pdf');
	// 	if($_SESSION['SmtpSetting'] == 0){
	// 		$mail->setFrom($_SESSION['CompanyRecord']['coyname'] . ' <' . $_SESSION['CompanyRecord']['email'] . '>');
	// 		$result = $mail->send(array($_GET['Email']));
	// 	}else{
	// 		$result = SendmailBySmtp($mail,array($_GET['Email']));
	// 	}

	// 	unlink($FileName); //delete the temporary file

	// 	$Title = _('Emailing') . ' ' .$InvOrCredit . ' ' . _('Number') . ' ' . $FromTransNo;
	// 	include('includes/header.inc');
	// 	echo '<p>' . $InvOrCredit . ' '  . _('number') . ' ' . $FromTransNo . ' ' . _('has been emailed to') . ' ' . $_GET['Email'];
	// 	include('includes/footer.inc');
	// 	exit;

	// } else { //its not an email just print the invoice to PDF


	$pdf->OutputD('mkmw_' . $InvOrCredit . '_' . $FromTransNo . '.pdf');

	// }
	$pdf->__destruct();
	//Now change the language back to the user's language
	$_SESSION['Language'] = $UserLanguage;
	include('includes/LanguageSetup.php');


} else { /*The option to print PDF was not hit */

	$Title=_('Select Invoices/Credit Notes To Print');
	include('includes/header.inc');

	if (!isset($FromTransNo) OR $FromTransNo=='') {

		/* if FromTransNo is not set then show a form to allow input of either a single invoice number or a range of invoices to be printed. Also get the last invoice number created to show the user where the current range is up to */
		echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .  '" method="get">';
        echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
		echo '<div>';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

		echo '<div class="centre"><p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/printer.png" title="' . _('Print') . '" alt="" />' . ' ' . _('Print Invoices or Credit Notes') . '</p></div>';
		echo '<table class="table1">
				<tr><td>' . _('Print Invoices or Credit Notes') . '</td><td><select name="InvOrCredit">';
		if ($InvOrCredit=='Invoice' OR !isset($InvOrCredit)) {

			echo '<option selected="selected" value="Invoice">' . _('Invoices') . '</option>';
			echo '<option value="Credit">' . _('Credit Notes') . '</option>';
		} else {
			echo '<option selected="selected" value="Credit">' . _('Credit Notes') . '</option>';
			echo '<option value="Invoice">' . _('Invoices') . '</option>';
		}

		echo '</select></td></tr>';
		// echo '<tr><td>' . _('Print EDI Transactions') . '</td><td><select name="PrintEDI">';

		// if ($InvOrCredit=='Invoice' OR !isset($InvOrCredit)) {

		// 	echo '<option selected="selected" value="No">' . _('Do not Print PDF EDI Transactions') . '</option>';
		// 	echo '<option value="Yes">' . _('Print PDF EDI Transactions Too') . '</option>';

		// } else {

		// 	echo '<option value="No">' . _('Do not Print PDF EDI Transactions') . '</option>';
		// 	echo '<option selected="selected" value="Yes">' . _('Print PDF EDI Transactions Too') . '</option>';
		// }

		echo '</select></td></tr>';
		echo '<tr><td>' . _('Start invoice/credit note number to print') . '</td>
				<td><input type="text" class="number" maxlength="6" size="7" name="FromTransNo" /></td></tr>';
		// echo '<tr><td>' . _('End invoice/credit note number to print') . '</td>
		// 		<td><input type="text" class="number" maxlength="6" size="7" name="ToTransNo" /></td></tr>
		echo '</table>';
		echo '<div class="centre">';
		echo '<input type="submit" value="' . _('Print PDF') . '" /><br /><br /></div>';
        echo '</div>
              </form>';

		$sql = "SELECT typeno FROM systypes WHERE typeid=10";

		$result = DB_query($sql);
		$myrow = DB_fetch_row($result);

		echo '<div class="page_help_text"><b>' . _('The last invoice created was number') . ' ' . $myrow[0] . '</b><br />' . _('If only a single invoice is required') . ', ' . _('enter the invoice number to print in the Start transaction number to print field and leave the End transaction number to print field blank') . '. ' . _('Only use the end invoice to print field if you wish to print a sequential range of invoices') . '';

		$sql = "SELECT typeno FROM systypes WHERE typeid=11";

		$result = DB_query($sql);
		$myrow = DB_fetch_row($result);

		echo '<br /><b>' . _('The last credit note created was number') . ' ' . $myrow[0] . '</b>
              <br />' . _('A sequential range can be printed using the same method as for invoices above') . '. ' . _('A single credit note can be printed by only entering a start transaction number') .
              '</div>';

	} else {

		while ($FromTransNo <= filter_number_format($_POST['ToTransNo'])) {

			/*retrieve the invoice details from the database to print
			notice that salesorder record must be present to print the invoice purging of sales orders will
			nobble the invoice reprints */

			if ($InvOrCredit=='Invoice') {

				$sql = "SELECT debtortrans.trandate,
								debtortrans.ovamount,
								debtortrans.ovdiscount,
								debtortrans.ovfreight,
								debtortrans.ovgst,
								debtortrans.rate,
								debtortrans.invtext,
								debtortrans.consignment,
								debtorsmaster.name,
								debtorsmaster.address1,
								debtorsmaster.address2,
								debtorsmaster.address3,
								debtorsmaster.address4,
								debtorsmaster.address5,
								debtorsmaster.address6,
								debtorsmaster.currcode,
								debtorsmaster.docremarks,
								paymentterms.terms,
								salesorders.deliverto,
								salesorders.deladd1,
								salesorders.deladd2,
								salesorders.deladd3,
								salesorders.deladd4,
								salesorders.deladd5,
								salesorders.deladd6,
								salesorders.customerref,
								salesorders.orderno,
								salesorders.orddate,
								shippers.shippername,
								custbranch.brname,
								custbranch.braddress1,
								custbranch.braddress2,
								custbranch.braddress3,
								custbranch.braddress4,
								custbranch.braddress5,
								custbranch.braddress6,
								salesman.salesmanname,
								debtortrans.debtorno,
								currencies.decimalplaces
							FROM debtortrans INNER JOIN debtorsmaster
							ON debtortrans.debtorno=debtorsmaster.debtorno
							INNER JOIN custbranch
							ON debtortrans.debtorno=custbranch.debtorno
							AND debtortrans.branchcode=custbranch.branchcode
							INNER JOIN salesorders
							ON debtortrans.order_ = salesorders.orderno
							INNER JOIN shippers
							ON debtortrans.shipvia=shippers.shipper_id
							INNER JOIN salesman
							ON custbranch.salesman=salesman.salesmancode
							INNER JOIN locations
							ON salesorders.fromstkloc=locations.loccode
							INNER JOIN locationusers
							ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
							INNER JOIN paymentterms
							ON debtorsmaster.paymentterms=paymentterms.termsindicator
							INNER JOIN currencies
							ON debtorsmaster.currcode=currencies.currabrev
							WHERE debtortrans.type=10
							AND debtortrans.transno='" . $FromTransNo . "'";
			} else { //preview invoice

				//credit note
				$sql = "SELECT debtortrans.trandate,
								debtortrans.ovamount,
								debtortrans.ovdiscount,
								debtortrans.ovfreight,
								debtortrans.ovgst,
								debtortrans.rate,
								debtortrans.invtext,
								debtorsmaster.name,
								debtorsmaster.address1,
								debtorsmaster.address2,
								debtorsmaster.address3,
								debtorsmaster.address4,
								debtorsmaster.address5,
								debtorsmaster.address6,
								debtorsmaster.currcode,
								custbranch.brname,
								custbranch.braddress1,
								custbranch.braddress2,
								custbranch.braddress3,
								custbranch.braddress4,
								custbranch.braddress5,
								custbranch.braddress6,
								salesman.salesmanname,
								debtortrans.debtorno,
								currencies.decimalplaces
							FROM debtortrans INNER JOIN debtorsmaster
							ON debtortrans.debtorno=debtorsmaster.debtorno
							INNER JOIN custbranch
							ON debtortrans.debtorno=custbranch.debtorno
							AND debtortrans.branchcode=custbranch.branchcode
							INNER JOIN salesman
							ON custbranch.salesman=salesman.salesmancode
							INNER JOIN paymentterms
							ON debtorsmaster.paymentterms=paymentterms.termsindicator
							INNER JOIN currencies
							ON debtorsmaster.currcode=currencies.currabrev
							WHERE debtortrans.type=11
							AND debtortrans.transno='" . $FromTransNo . "'";
			}

			$result=DB_query($sql);
			if ((DB_num_rows($result)==0 AND $InvOrCredit == 'Invoice') OR (DB_error_no()!=0)) {
				echo '<p>' . _('There was a problem retrieving the invoice or credit note details for note number') . ' ' . $FromTransNo . ' ' . _('from the database') . '. ' . _('To print an invoice, the sales order record, the customer transaction record and the branch record for the customer must not have been purged') . '. ' . _('To print a credit note only requires the customer, transaction, salesman and branch records be available');
				if ($debug==1) {
					echo _('The SQL used to get this information that failed was') . '<br />' . $sql;
				}
				break;
				include('includes/footer.inc');
				exit;
			} elseif (DB_num_rows($result)==1) {

				$myrow = DB_fetch_array($result);
				/* Then there's an invoice (or credit note) to print. So print out the invoice header and GST Number from the company record */
				if (count($_SESSION['AllowedPageSecurityTokens'])==1
                     AND in_array(1, $_SESSION['AllowedPageSecurityTokens'])
                     AND $myrow['debtorno'] != $_SESSION['CustomerID']){

					echo '<p class="bad">' . _('This transaction is addressed to another customer and cannot be displayed for privacy reasons') . '. ' . _('Please select only transactions relevant to your company');
					exit;
				}

				$ExchRate = $myrow['rate'];
				$PageNumber = 1;
				

                /* Now print out the logo and company name and address */
				echo '<form name="ItemForm" enctype="multipart/form-data" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?FromTransNo='. $_GET['FromTransNo'] .'&InvOrCredit='.$InvOrCredit.'&PrintPDF=true">';
                echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
				echo '<table style="width:100%">';
                /* Now the customer charged to details in a sub table within a cell of the main table*/
                echo '<tr><td width="500px"><br><br><br><b>' . _('Customer') . ':</b>';
                echo '<br /><input name="dname" value="' . $myrow['name'] . '" size="50"/>
                    <br /><input name="address1" value="' . $myrow['address1']. '" size="50"/>
                   <br /><input name="address1" value="' . $myrow['address2']. '" size="50"/>
                    <br /><input name="address1" value="' . $myrow['address3']. '" size="15"/>
                   <input name="address1" value="' . $myrow['address4']. '" size="15"/>
                <input name="address1" value="' . $myrow['address5']. '" size="15"/>';
                echo '</td>';
                
                
                echo '<td>';
                if ($InvOrCredit=='Invoice') {
                   echo '<h2>' . _('TAX INVOICE') . '</h2>';
                } else {
                   echo '<h2 style="color:red">' . _('TAX CREDIT NOTE') . '</h2>';
                }
                echo '<br />' . _('INV') . ' ' . $FromTransNo . '<br />' . _('Tax Authority Ref') . '. ' . $_SESSION['CompanyRecord']['gstno'] . '</td>
                    <td></td>
					</tr>';

            
				/*end of the main table showing the company name and charge to details */

				if ($InvOrCredit=='Invoice') {

				   echo '<table style="width:100%"><tr>
							<th><b>' . _('Your Order Ref') . '</b></th>
							<th><b>' . _('Our Order No') . '</b></th>
							<th><b>' . _('Invoice Date') . '</b></th>
							<th><b>' . _('Terms') . '</b></th>
						</tr>';
				   	echo '<tr>
							<td style="text-align:center"><input type="text" name="salesref" value="' . $myrow['customerref'] . '" size="25"/></td>
							<td style="text-align:center"><input type="text" name="orderno" value="' .$myrow['orderno'] . '" size="25"/></td>
							<td style="text-align:center"><input type="text" name="ddate" value="' . ConvertSQLDate($myrow['trandate']) . '" size="25"/></td>
							<td style="text-align:center"><input type="text" name="terms" value="' . $myrow['terms']. '" size="25"/></td>
						</tr></table>';

				   $sql ="SELECT stockmoves.stockid,
	 					   		stockmaster.longdescription,
	 							-stockmoves.qty as quantity,
	 							stockmoves.discountpercent,
	 							((1 - stockmoves.discountpercent) * stockmoves.price * " . $ExchRate . "* -stockmoves.qty) AS fxnet,
	 							(stockmoves.price * " . $ExchRate . ") AS fxprice,
								stockmoves.narrative,
								stockmaster.units,
								stockmaster.decimalplaces,
								salesorderdetails.orderlineno,
								salesorderdetails.drev,
								salesorderdetails.ddesc
							FROM stockmoves,
								stockmaster,
								salesorderdetails
							WHERE stockmoves.stockid = stockmaster.stockid
							AND stockmoves.stockid = salesorderdetails.stkcode
							AND stockmoves.transno = salesorderdetails.orderno
							AND stockmoves.type=10
							AND stockmoves.transno='" . $FromTransNo . "'
							AND stockmoves.show_on_inv_crds=1
							ORDER BY salesorderdetails.orderlineno ASC";

				} else { /* then its a credit note */

				   	echo '<table style="width:100%"><tr>
							<th><b>' . _('Order Reference') . '</b></th>
							<th><b>' . _('Date') . '</b></th>
						</tr>';
				   	echo '<tr>
							<td style="text-align:center"><input type="text" name="orderno" value="' .$myrow['orderno'] . '" size="25"/></td>
							<td style="text-align:center"><input type="text" name="ddate" value="' . ConvertSQLDate($myrow['trandate']) . '" size="25"/></td>
						</tr></table>';

				   $sql ="SELECT stockmoves.stockid,
	  							stockmaster.longdescription,
	 							stockmoves.qty as quantity,
	 							stockmoves.discountpercent, ((1 - stockmoves.discountpercent) * stockmoves.price * " . $ExchRate . " * stockmoves.qty) AS fxnet,
	 							(stockmoves.price * " . $ExchRate . ") AS fxprice,
								stockmoves.narrative,
								stockmaster.units,
								stockmaster.decimalplaces
							FROM stockmoves,
								stockmaster
							WHERE stockmoves.stockid = stockmaster.stockid
							AND stockmoves.type=11
							AND stockmoves.transno='" . $FromTransNo . "'
							AND stockmoves.show_on_inv_crds=1";
				}

				$result=DB_query($sql);
				if (DB_error_no()!=0) {
					echo '<br />' . _('There was a problem retrieving the invoice or credit note stock movement details for invoice number') . ' ' . $FromTransNo . ' ' . _('from the database');
					if ($debug==1){
						 echo '<br />' . _('The SQL used to get this information that failed was') . '<br />' .$sql;
					}
					exit;
				}

				if (DB_num_rows($result)>0){
					echo '<table style="width:100%">
						<tr><th>' . _('S/N') . '</th>
							<th>' . _('Item Description') . '</th>
							<th>' . _('Quantity') . '</th>
							<th>' . _('Unit') . '</th>
							<th>' . _('Price') . '</th>
							<th>' . _('Amount') . '</th>
						</tr>';

					$LineCounter =17;
					$k=0;	//row colour counter
					$SN = 0;
					while ($myrow2=DB_fetch_array($result)){

					      if ($k==1){
						  $RowStarter = '<tr class="EvenTableRows">';
						  $k=0;
					      } else {
						  $RowStarter = '<tr class="OddTableRows">';
						  $k=1;
					      }

					      echo $RowStarter;

					      $DisplayPrice = locale_number_format($myrow2['fxprice'],$myrow['decimalplaces']);
					      $DisplayQty = locale_number_format($myrow2['quantity'],$myrow2['decimalplaces']);
					      $DisplayNet = locale_number_format($myrow2['fxnet'],$myrow['decimalplaces']);

					      if ($myrow2['discountpercent']==0){
						   $DisplayDiscount ='';
					      } else {
						   $DisplayDiscount = locale_number_format($myrow2['discountpercent']*100,2) . '%';
					      }

						  $revQ = "SELECT
								stockitemproperties.value,
								stockcatproperties.label
								from stockitemproperties INNER JOIN stockcatproperties 
								on stockitemproperties.stkcatpropid=stockcatproperties.stkcatpropid 
								INNER JOIN stockmaster on stockitemproperties.stockid = stockmaster.stockid 
								WHERE stockmaster.stockid = '".$myrow2['stkcode'] ."' and stockcatproperties.label='revision'";
							$ErrMsg = "Error retrieving revision for stock";
							$revRes = DB_Query($revQ, $ErrMsg);
							$rev = DB_fetch_array($revRes)['value'];
						  $desc = (!empty($myrow2['ddesc']) ? $myrow2['ddesc'] : $myrow2['longdescription']);
						  $rev = (!empty($myrow2['drev']) ? $myrow2['drev']: $$rev);
						  echo '<td style="text-align:center">'.($myrow2['orderlineno'] +1).'</td>';
						  echo '<td >
						  			<table>
										<tr>
											<td><input type="text" name="stkcode'.($myrow2['orderlineno']).'" value="'.$myrow2['stockid'].'"</td>
											<td><input type="text" name="drev'.($myrow2['orderlineno']).'" value="'.$rev.'"</td>
										<tr>
										<tr>
											<td colspan="2"><textarea name="ddesc'.($myrow2['orderlineno']).'" cols="50" rows="4">'.$desc.'</textarea>(max 4 rows)</td>
										<tr>  
									</table>
								</td>';
						
						echo '<td style="text-align:center">
								<input type="text" name="quantity'.($myrow2['orderlineno']).'" value="'.$DisplayQty.'"/>
							</td>';
						echo '<td style="text-align:center">
								<input type="text" name="units'.($myrow2['orderlineno']).'" value="'.$myrow2['units'].'"/>
							</td>';
						echo '<td style="text-align:center">
								<input type="text" name="uprice'.($myrow2['orderlineno']).'" value="'.$DisplayPrice.'"/>
							</td>';
						echo '<td style="text-align:center">
								<input type="text" name="netprice'.($myrow2['orderlineno']).'" value="'.$DisplayNet.'"/>
							</td>';

						

					      if (mb_strlen($myrow2['narrative'])>1){
                                $narrative = str_replace(array("\r\n", "\n", "\r", "\\r\\n"), '<br />', $myrow2['narrative']);
					      		echo $RowStarter . '<td></td><td colspan="6">' . $narrative . '</td></tr>';
							$LineCounter++;
					      }

					      $LineCounter++;

					} //end while there are line items to print out
					echo '</table>';
				} /*end if there are stock movements to show on the invoice or credit note*/

				/* Now print out the footer and totals */

				if ($InvOrCredit=='Invoice') {

				   $DisplaySubTot = locale_number_format($myrow['ovamount'],$myrow['decimalplaces']);
				   $DisplayFreight = locale_number_format($myrow['ovfreight'],$myrow['decimalplaces']);
				   $DisplayTax = locale_number_format($myrow['ovgst'],$myrow['decimalplaces']);
				   $DisplayTotal = locale_number_format($myrow['ovfreight']+$myrow['ovgst']+$myrow['ovamount'],$myrow['decimalplaces']);
				} else {
				   $DisplaySubTot = locale_number_format(-$myrow['ovamount'],$myrow['decimalplaces']);
				   $DisplayFreight = locale_number_format(-$myrow['ovfreight'],$myrow['decimalplaces']);
				   $DisplayTax = locale_number_format(-$myrow['ovgst'],$myrow['decimalplaces']);
				   $DisplayTotal = locale_number_format(-$myrow['ovfreight']-$myrow['ovgst']-$myrow['ovamount'],$myrow['decimalplaces']);
				}

				/*Print out the invoice text entered */
				echo '<table>
						<td>Remarks:</td>
						<td><input type="text" name="docremarks" value="'.$myrow['docremarks'].'" size="50"/></td>
				</table>';
				echo '<table style="width:100%"><tr>
					<td class="number">' . _('Sub Total') . ' ' . $DisplaySubTot . '<br />';
				// echo _('Freight') . ' ' . $DisplayFreight . '<br />';
				echo _('GST (7%)') . ' ' . $DisplayTax . '<br />';
				if ($InvOrCredit=='Invoice'){
				     echo '<b>' . _('TOTAL INVOICE') . ' ' . $DisplayTotal . '</b>';
				} else {
				     echo '<b>' . _('TOTAL CREDIT') . ' ' . $DisplayTotal . '</b>';
				}
				echo '</td></tr></table>';
				if($InvOrCredit == "Invoice"){
					echo '<input type="submit" name="submit" value="' . _('Save and Print Invoice') . '" />';
				}else{
					echo '<input type="submit" name="submit" value="' . _('Save and Print Credit Note') . '" />';
				}
				
				echo '</form>';
			} /* end of check to see that there was an invoice record to print */
			$FromTransNo++;
		} /* end loop to print invoices */
	} /*end of if FromTransNo exists */
	include('includes/footer.inc');
} /*end of else not PrintPDF */


function PrintLinesToBottom () {

	global $pdf;
	global $PageNumber;
	global $TopOfColHeadings;
	global $Left_Margin;
	global $Bottom_Margin;
	global $line_height;

	/* draw the vertical column lines right to the bottom */
	$pdf->line($Left_Margin+97, $TopOfColHeadings+12,$Left_Margin+97,$Bottom_Margin);

	/* Print a column vertical line */
	$pdf->line($Left_Margin+350, $TopOfColHeadings+12,$Left_Margin+350,$Bottom_Margin);

	/* Print a column vertical line */
	$pdf->line($Left_Margin+450, $TopOfColHeadings+12,$Left_Margin+450,$Bottom_Margin);

	/* Print a column vertical line */
	$pdf->line($Left_Margin+550, $TopOfColHeadings+12,$Left_Margin+550,$Bottom_Margin);

	/* Print a column vertical line */
	$pdf->line($Left_Margin+587, $TopOfColHeadings+12,$Left_Margin+587,$Bottom_Margin);

	$pdf->line($Left_Margin+640, $TopOfColHeadings+12,$Left_Margin+640,$Bottom_Margin);

	$PageNumber++;

}

?>
