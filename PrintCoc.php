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
include('includes/SQL_CommonFunctions.inc');


if (isset($_GET['TransNo'])) {
	$TransNo = $_GET['TransNo'];
} elseif (isset($_POST['InvOrCredit'])) {
	$TransNo = $_POST['TransNo'];
}

if (isset($_GET['PrintPDF'])) {
	$PrintPDF = TRUE;
} elseif (isset($_POST['PrintPDF'])) {
	$PrintPDF = TRUE;
}

if (isset($PrintPDF) AND isset($TransNo)){
	
	$PaperSize = 'A4';
	include('includes/PDFStarter.php');
	$pdf->addInfo('Title',_('Certificate of Conformity for') . ' ' . $TransNo);
		
	$FirstPage = true;
	$line_height=16;
	$PageNumber = 0;
	// echoObj($_POST);
	for ($i = 0; $i < $_POST['numParts']; $i++){
		if(!$_POST['print'.$i]){
			continue;
		}
		$PageNumber++;
		include('includes/PDFCocPageHeader.inc');
		$row2XPos = 200;
		$colonXPos = 180;
		
		$pdf->addText($XPos, $YPos, $FontSize, "Part Number");
		$pdf->addText($colonXPos, $YPos, $FontSize, ":");
		$pdf->addText($row2XPos, $YPos, $FontSize, $_POST['partno'.$i]);
		$YPos -= 25;
		
		$pdf->addText($XPos, $YPos, $FontSize, "Part Revision");
		$pdf->addText($colonXPos, $YPos, $FontSize, ":");
		$pdf->addText($row2XPos, $YPos, $FontSize, $_POST['rev'.$i]);
		$YPos -= 25;
		$pdf->addText($XPos, $YPos, $FontSize, "Part Description");
		$pdf->addText($colonXPos, $YPos, $FontSize, ":");

		$DYPos = $YPos-15;
		$LeftOvers = $pdf->addTextWrap($row2XPos, $DYPos, 350, $FontSize, breakTextToLines($_POST['desc'.$i], 1));
		if($LeftOvers != ""){
			$pdf->addText($row2XPos, $DYPos, $FontSize, $LeftOvers);
			$DYPos -= 15;
		}
		$pdf->addText($row2XPos, $DYPos, $FontSize, breakTextToLines($_POST['desc'.$i], 2));
		$DYPos -= 15;
		$pdf->addText($row2XPos, $DYPos, $FontSize, breakTextToLines($_POST['desc'.$i], 3));
		$DYPos -= 15;
		$pdf->addText($row2XPos, $DYPos, $FontSize, breakTextToLines($_POST['desc'.$i], 4));
		$YPos -= 80;

		$pdf->addText($XPos, $YPos, $FontSize, "Quantity");
		$pdf->addText($colonXPos, $YPos, $FontSize, ":");
		$pdf->addText($row2XPos, $YPos, $FontSize, $_POST['quantity'.$i]);
		$YPos -= 25;

		$pdf->addText($XPos, $YPos, $FontSize, "P/O No");
		$pdf->addText($colonXPos, $YPos, $FontSize, ":");
		$pdf->addText($row2XPos, $YPos, $FontSize, $_POST['customerref'.$i]);
		$YPos -= 25;

		$pdf->addText($XPos, $YPos, $FontSize, "D/O No");
		$pdf->addText($colonXPos, $YPos, $FontSize, ":");
		$pdf->addText($row2XPos, $YPos, $FontSize, $_POST['orderno'.$i]);
		$YPos -= 25;

		$pdf->addText($XPos, $YPos, $FontSize, "Manufacturer");
		$pdf->addText($colonXPos, $YPos, $FontSize, ":");
		$pdf->addText($row2XPos, $YPos, $FontSize, $_POST['manufacturer'.$i]);
		$YPos -= 25;

		$pdf->addText($XPos, $YPos, $FontSize, "Date Code");
		$pdf->addText($colonXPos, $YPos, $FontSize, ":");
		$pdf->addText($row2XPos, $YPos, $FontSize, $_POST['datecode'.$i]);
		$YPos -= 25;

		$pdf->addText($XPos, $YPos, $FontSize, "Country of Origin");
		$pdf->addText($colonXPos, $YPos, $FontSize, ":");
		$pdf->addText($row2XPos, $YPos, $FontSize, $_POST['origin'.$i]);
		$YPos -= 25;

		$pdf->addText($XPos, $YPos, $FontSize, "Compliance");
		$pdf->addText($colonXPos, $YPos, $FontSize, ":");
		$pdf->addText($row2XPos, $YPos, $FontSize, $_POST['comply'.$i]);
		$YPos -= 50;


		$pdf->addTextWrap($XPos, $YPos, 500, $FontSize, 'THIS CERTIFICATE IS TO CERTIFY THAT THE REFERENCED PART SUPPLIED IS IN');	
		$YPos -= 15;
		$pdf->addTextWrap($XPos, $YPos, 500, $FontSize, 'COMPLIANCE WITH THE REQUIREMENTS AS IN THE SPECIFICATION AND DRAWING');
		$YPos -= 15;	
		$pdf->addTextWrap($XPos, $YPos, 500, $FontSize, 'APPLICABLE TO THE SAID PURCHASE ORDER. DOCUMENTED EVIDENCE NECESSARY');
		$YPos -= 15;
		$pdf->addTextWrap($XPos, $YPos, 500, $FontSize, 'TO SUBSTANTIATE THIS CERTIFICATE IS ON FILE AT OUR FACILITY AND IS');
		$YPos -= 15;
		$pdf->addTextWrap($XPos, $YPos, 250,$FontSize, 'AVAILABLE FOR REVIEW, IF REQUIRED.');
		
	}



	$pdf->OutputD('mkmw_CoC' . '_' . $TransNo . '.pdf');
	$pdf->__destruct();


} else { /*The option to print PDF was not hit */

	$Title=_('Printing Certificate of Conformity');



	include('includes/header.inc');
	$sql = "SELECT salesorders.debtorno,
		salesorders.customerref,
		salesorders.comments,
		salesorders.orddate,
		salesorders.deliverto,
		salesorders.deladd1,
		salesorders.deladd2,
		salesorders.deladd3,
		salesorders.deladd4,
		salesorders.deladd5,
		salesorders.deladd6,
		salesorders.deliverblind,
		salesorders.ddate,
		debtorsmaster.name,
		debtorsmaster.address1,
		debtorsmaster.address2,
		debtorsmaster.address3,
		debtorsmaster.address4,
		debtorsmaster.address5,
		debtorsmaster.address6,
		debtorsmaster.docremarks,
		debtorsmaster.paymentterms,
		salesorders.orderno
	FROM salesorders INNER JOIN debtorsmaster
	ON salesorders.debtorno=debtorsmaster.debtorno
	WHERE salesorders.orderno='" . $_GET['TransNo'] . "'";
	$result = DB_Query($sql, "Something went wrong, cannot retrieve sales and customer info.");

	if(DB_num_rows($result) == 1){
		$myrow=DB_fetch_array($result);

		$partQ = "SELECT 
				salesorderdetails.orderlineno,
				salesorderdetails.orderno,
				salesorderdetails.stkcode,
				salesorderdetails.quantity,
				salesorderdetails.drev,
				salesorderdetails.ddesc,
				stockmaster.longdescription,
				stockmaster.units
				FROM salesorderdetails INNER JOIN stockmaster
				ON stockmaster.stockid = salesorderdetails.stkcode
				WHERE salesorderdetails.orderno = ".$_GET['TransNo'];

		$partRes = DB_query($partQ, "cannot retrieve parts details.");
		if($partRes ==0){
			echo '<div class="centre"><br /><br /><br />';
		prnMsg( _('Unable to Locate Parts for transaction ') . ' : ' . $_GET['TransNo'] . ' ', 'error');
		echo '<br />
				<br />
				<br />
				<table class="table_index">
				<tr>
				<td class="menu_group_item">
				<li><a href="'. $RootPath . '/SelectSalesOrder.php">' . _('Outstanding Sales Orders') . '</a></li>
				<li><a href="'. $RootPath . '/SelectCompletedOrder.php">' . _('Completed Sales Orders') . '</a></li>
				</td>
				</tr>
				</table>
				</div>
				<br />
				<br />
				<br />';
				exit();
		}

		echo '<div class="centre"><br /><br /><br />';
		echo 	'<h1> D/O / INV: '. $_GET['TransNo'] .'</h1>';
		echo '<form name="ItemForm" enctype="multipart/form-data" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?TransNo='. $_GET['TransNo'] .'&PrintPDF=true">';
		echo 	'<table>
					<tr>
						<td>
							<table>
								<th>Customer:</th>
								<th>Date: </th>
								<tr>
									<td><input type="text" name="name" value="'.$myrow['name'].'" size="50"/></td>
									<td><input type="text" name="date" value="'.$myrow['orddate'].'" size="30"/></td>
								</tr>
								<tr>
									<td><input type="text" name="addr1" value="'.$myrow['address1'].'" size="50"/></td>
								</tr>
								<tr>
									<td><input type="text" name="addr2" value="'.$myrow['address2'].'" size="50"/></td>
								</tr>
								<tr>
									<td><input type="text" name="addr3" value="'.$myrow['address3'].'" size="50"/></td>
								</tr>
								<tr>
									<td><input type="text" name="addr4" value="'.$myrow['address4'].' '.$myrow['address5'].'" size="50"/></td>
								</tr>
							</table>
						</td>
					</tr>
		';
		
		echo "<br/><br/>";
		$numParts = DB_num_rows($partRes);
		while($myrow2=DB_fetch_array($partRes)){
			$complyQ = "SELECT stockitemproperties.value
						FROM stockitemproperties 
						INNER JOIN stockcatproperties
						ON stockitemproperties.stkcatpropid = stockcatproperties.stkcatpropid
						WHERE stockitemproperties.stockid = '".$myrow2['stkcode']."' 
						AND stockcatproperties.label = 'comply'
			";
			$datecodeQ = "SELECT stockitemproperties.value
						FROM stockitemproperties 
						INNER JOIN stockcatproperties
						ON stockitemproperties.stkcatpropid = stockcatproperties.stkcatpropid
						WHERE stockitemproperties.stockid = '".$myrow2['stkcode']."' 
						AND stockcatproperties.label = 'datecode'
			";

			$complyRes = DB_query($complyQ);
			$datecodeRes = DB_query($datecodeQ);
			$comply = DB_fetch_array($complyRes)['value'];
			$datecode = DB_fetch_array($datecodeRes)['value'];


			echo '
			<table>
				<tr>
					<td><h3>Print this part</h3></td>
					<td><input type="checkbox" name="print'.$myrow2['orderlineno'].'"checked/></td>
				</tr>
				<tr>
					<td>Part Number:</td>
					<td><input type="text" name="partno'.$myrow2['orderlineno'].'" value="'.$myrow2['stkcode'].'" size="30"/></td>
				</tr>
				<tr>
					<td>Part Revision:</td>
					<td><input type="text" name="rev'.$myrow2['orderlineno'].'" value="'.$myrow2['drev'].'" size="30"/></td>
				</tr>
				<tr>
					<td>Part Description:</td>
					<td><textarea name="desc'.$myrow2['orderlineno'].'" rows="4" cols="50">'.$myrow2['longdescription'].'</textarea></td>
				</tr>
				<tr>
					<td>Quantity:</td>
					<td><input type="text" name="quantity'.$myrow2['orderlineno'].'" value="'.$myrow2['quantity'].' '.$myrow2['units'].'" size="10"/></td>
				</tr>
				<tr>
					<td>P/O No:</td>
					<td><input type="text" name="customerref'.$myrow2['orderlineno'].'" value="'.$myrow2['customerref'].'" size="30"/></td>
				</tr>
				<tr>
					<td>D/O No:</td>
					<td><input type="text" name="orderno'.$myrow2['orderlineno'].'" value="'.$myrow2['orderno'].'" size="30"/></td>
				</tr>
				<tr>
					<td>Manufacturer:</td>
					<td><input type="text" name="manufacturer'.$myrow2['orderlineno'].'" value="Ming Kee Metal Works Pte Ltd" size="30"/></td>
				</tr>
				<tr>
					<td>Date Code:</td>
					<td><input type="text" name="datecode'.$myrow2['orderlineno'].'" value="'.$datecode.'" size="30"/></td>
				</tr>
				<tr>
					<td>Country of Origin:</td>
					<td><input type="text" name="origin'.$myrow2['orderlineno'].'" value="MADE IN SINGAPORE" size="30"/></td>
				</tr>
				<tr>
					<td>Compliance:</td>
					<td><input type="text" name="comply'.$myrow2['orderlineno'].'" value="'.$comply.'" size="30"/></td>
				</tr>
			</table>';
		}
		echo '<input type="hidden" name="numParts" value="'.$numParts.'"/>';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
		echo '<input type="submit" name="submit" value="' . _('Print CoC') . '" />';
		echo '</form></div>';
	}else{
		echo '<div class="centre"><br /><br /><br />';
		prnMsg( _('Unable to Locate Order Number') . ' : ' . $_GET['TransNo'] . ' ', 'error');
		echo '<br />
				<br />
				<br />
				<table class="table_index">
				<tr>
				<td class="menu_group_item">
				<li><a href="'. $RootPath . '/SelectSalesOrder.php">' . _('Outstanding Sales Orders') . '</a></li>
				<li><a href="'. $RootPath . '/SelectCompletedOrder.php">' . _('Completed Sales Orders') . '</a></li>
				</td>
				</tr>
				</table>
				</div>
				<br />
				<br />
				<br />';
	}
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
