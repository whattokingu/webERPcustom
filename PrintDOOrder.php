<?php

/* $Id: PrintCustOrder_generic.php 7093 2015-01-22 20:15:40Z vvs2012 $*/
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

function translateTerms($term){
	$sql = "SELECT terms FROM paymentterms WHERE termsindicator='".$term."'";
	$resq = DB_query($sql, "Something went wrong with payment terms.");
	$res = DB_fetch_array($resq);
	return $res['terms'];
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
if(isset($_POST['submit'])){
	// echoObj($_POST);
	
	$sql = "UPDATE debtorsmaster SET
			docremarks='".trim($_POST['docremarks'])."'
			WHERE debtorno='".$_POST['debtorno']."'";
	$ErrMsg = _('The debtor item could not be updated because');
	$DbgMsg = _('The SQL that was used to update the debtor item and failed was');
	$result = DB_query($sql,$ErrMsg,$DbgMsg,true);

	$sql1 = "UPDATE salesorders SET 
				deladd1='". trim($_POST['deladd1']) ."',
				deladd2='". trim($_POST['deladd2']) ."',
				deladd3='". trim($_POST['deladd3']) ."',
				deladd4='". trim($_POST['deladd4']) ."',
				ddate='".$_POST['ddate']."'
			WHERE orderno='".$_POST['orderno']."'";
	$ErrMsg = _('The salesorder item could not be updated because');
	$DbgMsg = _('The SQL that was used to update the salesorder item and failed was');
	$result = DB_query($sql1,$ErrMsg,$DbgMsg,true);


	for($item=0; $item < $_POST['itemCnt']; $item++){
		$sql2 = "UPDATE salesorderdetails SET
				drev='". $_POST['drev'.$item] ."',
				ddesc='". trim($_POST['ddesc'.$item]) . "'
				WHERE orderno='" . $_POST['orderno']."'
				AND orderlineno='". $item. "'
		";
		$ErrMsg = _('The stock item could not be updated because');
		$DbgMsg = _('The SQL that was used to update the stock item and failed was');
		$result = DB_query($sql2, $ErrMsg, $DbgMsg, true);
		
	}



	/*retrieve the order details from the database to print */
	$ErrMsg = _('There was a problem retrieving the order header details for Order Number') . ' ' . $_GET['TransNo'] . ' ' . _('from the database');

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
				debtorsmaster.paymentterms,
				shippers.shippername,
				salesorders.printedpackingslip,
				salesorders.datepackingslipprinted,
				locations.locationname,
				salesorders.orderno,
				salesorders.fromstkloc
			FROM salesorders INNER JOIN debtorsmaster
			ON salesorders.debtorno=debtorsmaster.debtorno
			INNER JOIN shippers
			ON salesorders.shipvia=shippers.shipper_id
			INNER JOIN locations
			ON salesorders.fromstkloc=locations.loccode
			INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
			WHERE salesorders.orderno='" . $_GET['TransNo'] . "'";

		if ($_SESSION['SalesmanLogin'] != '') {
			$sql .= " AND salesorders.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
		}

	$result=DB_query($sql, $ErrMsg);

		if (DB_num_rows($result)==0){
			$Title = _('Print Packing Slip Error');
			include('includes/header.inc');
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

			include('includes/footer.inc');
			exit();
		}

	/*retrieve the order details from the database to print */

	/* Then there's an order to print and its not been printed already (or its been flagged for reprinting)
	LETS GO */



	$PaperSize = 'A4';
	include('includes/PDFStarter.php');
	//$pdf->selectFont('./fonts/Helvetica.afm');
	$pdf->addInfo('Title', _('Delivery Order') );
	$pdf->addInfo('Subject', _('Deliver Order for') . ' ' . $_GET['TransNo']);
	$FontSize=12;
	$line_height=24;
	$PageNumber = 1;
	$Copy = 'Office';

	$ListCount = 0;
	$myrow = DB_fetch_array($result);

	for ($i=1;$i<=2;$i++){  /*Print it out twice one copy for customer and one for office */
		if ($i==2){
			$PageNumber = 1;
			$pdf->newPage();
		}

		/* Now ... Has the order got any line items still outstanding to be invoiced */
		$ErrMsg = _('There was a problem retrieving the order details for Order Number') . ' ' . $_GET['TransNo'] . ' ' . _('from the database');

		$sql = "SELECT 
						salesorderdetails.orderlineno,
						salesorderdetails.stkcode,
						stockmaster.longdescription,
						salesorderdetails.quantity,
						salesorderdetails.qtyinvoiced,
						salesorderdetails.unitprice,
						salesorderdetails.narrative,
						salesorderdetails.drev,
						salesorderdetails.ddesc,
						stockmaster.mbflag,
						stockmaster.decimalplaces,
						locstock.bin
					FROM salesorderdetails INNER JOIN stockmaster
					ON salesorderdetails.stkcode=stockmaster.stockid
					INNER JOIN locstock
					ON stockmaster.stockid = locstock.stockid
					WHERE locstock.loccode = '" . $myrow['fromstkloc'] . "'
					AND salesorderdetails.orderno='" . $_GET['TransNo'] . "'";
		$result=DB_query($sql, $ErrMsg);

		if (DB_num_rows($result)>0){
			/*Yes there are line items to start the ball rolling with a page header */
			include('includes/PDFOrderPageHeader_generic.inc');
			$SN = 0;
			$numItems = DB_num_rows($result);
			while ($myrow2=DB_fetch_array($result)){
				$ListCount++;
				$SN++;
				$DisplayQty = locale_number_format($_POST['qty'.($SN-1)],$myrow2['decimalplaces']);
				// $DisplayPrevDel = locale_number_format($myrow2['qtyinvoiced'],$myrow2['decimalplaces']);
				// $DisplayQtySupplied = locale_number_format($myrow2['quantity'] - $myrow2['qtyinvoiced'],$myrow2['decimalplaces']);

				// $LeftOvers = $pdf->addTextWrap($XPos,$YPos,127,$FontSize,$myrow2['stkcode']);
				// $LeftOvers = $pdf->addTextWrap(147,$YPos,255,$FontSize,$myrow2['description']);
				// $LeftOvers = $pdf->addTextWrap(400,$YPos,85,$FontSize,$DisplayQty,'right');
				// $LeftOvers = $pdf->addTextWrap(487,$YPos,70,$FontSize,$myrow2['bin'],'left');
				// $LeftOvers = $pdf->addTextWrap(573,$YPos,85,$FontSize,$DisplayQtySupplied,'right');
				// $LeftOvers = $pdf->addTextWrap(672,$YPos,85,$FontSize,$DisplayPrevDel,'right');
				$LeftOvers = $pdf->addTextWrap($SNXPos,$YPos,$SNW,$FontSize, $SN, 'center');
				$LeftOvers = $pdf->addTextWrap($DescXPos, $YPos, 150, $FontSize, $myrow2['stkcode'], 'left');
				$LeftOvers = $pdf->addTextWrap($DescXPos+170, $YPos, 110, $FontSize, $myrow2['drev'], 'right');
				$LeftOvers = $pdf->addTextWrap($UnitXPos,$YPos,$UnitW,$FontSize,$_POST[('units'.($SN - 1))],'center');
				$LeftOvers = $pdf->addTextWrap($QtyXPos,$YPos,$QtyW,$FontSize,$DisplayQty,'center');
				
				$LeftOvers = $pdf->addTextWrap($DescXPos,$YPos-18,$DescW,$FontSize,breakTextToLines($_POST['ddesc'.($SN-1)], 1));
				$LeftOvers = $pdf->addTextWrap($DescXPos,$YPos-36,$DescW,$FontSize,breakTextToLines($_POST['ddesc'.($SN-1)], 2));
				$LeftOvers = $pdf->addTextWrap($DescXPos,$YPos-54,$DescW,$FontSize,breakTextToLines($_POST['ddesc'.($SN-1)], 3));
				$LeftOvers = $pdf->addTextWrap($DescXPos,$YPos-72,$DescW,$FontSize,breakTextToLines($_POST['ddesc'.($SN-1)], 4));
				$LeftOvers = $pdf->addTextWrap($DescXPos,$YPos-90,$DescW,$FontSize,breakTextToLines($_POST['ddesc'.($SN-1)], 5));
				$YPos -=100;
				if ($YPos-$line_height <= 160 && $SN < $numItems){
				/* We reached the end of the page so finsih off the page and start a newy */
					$PageNumber++;
					include ('includes/PDFOrderPageHeader_generic.inc');
				} //end if need a new page headed up
				else {
					/*increment a line down for the next line item */
					$YPos -= ($line_height);
				}
			} //end while there are line items to print out
			$rmksXPos = $DescXPos;
			$rmksYPos = $YPos;
			if ($YPos < $rmksYPos){
				$rmksYPos = $YPos;
			}
			$pdf->addText($rmksXPos, $rmksYPos, $FontSize, $_POST["docremarks"]);
		} /*end if there are order details to show on the order*/

		$Copy='Customer';

	} /*end for loop to print the whole lot twice */

	if ($ListCount == 0) {
		echo $ListCount;
		$Title = _('Print Packing Slip Error');
		include('includes/header.inc');
		echo '<p>' .  _('There were no outstanding items on the order to deliver') . '. ' . _('A packing slip cannot be printed').
				'<br /><a href="' . $RootPath . '/SelectSalesOrder.php">' .  _('Print Another Packing Slip/Order').
				'</a>
				<br /><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
		include('includes/footer.inc');
		exit;
	} else {
    	$pdf->OutputD('mkmw_DeliveryOrder_' . $_GET['TransNo'] . '.pdf');
    	$pdf->__destruct();
		$sql = "UPDATE salesorders SET printedpackingslip=1,
							datepackingslipprinted='" . Date('Y-m-d') . "'
							WHERE salesorders.orderno='" . $_GET['TransNo'] . "'";
		$result = DB_query($sql);
	}


}
//Get Out if we have no order number to work with
If (!isset($_GET['TransNo']) OR $_GET['TransNo']==""){
	$Title = _('Select Order To Print');
	include('includes/header.inc');
	echo '<div class="centre"><br /><br /><br />';
	prnMsg( _('Select an Order Number to Print before calling this page') , 'error');
	echo '<br />
			<br />
			<br />
			<table class="table_index">
			<tr>
			<td class="menu_group_item">
            <ul>
			    <li><a href="'. $RootPath . '/SelectSalesOrder.php?">' . _('Outstanding Sales Orders') . '</a></li>
			    <li><a href="'. $RootPath . '/SelectCompletedOrder.php">' . _('Completed Sales Orders') . '</a></li>
            </ul>
			</td>
			</tr>
			</table>
			</div>
			<br />
			<br />
			<br />';
	include('includes/footer.inc');
	exit();
}

/*retrieve the order details from the database to print */
$ErrMsg = _('There was a problem retrieving the order header details for Order Number') . ' ' . $_GET['TransNo'] . ' ' . _('from the database');

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
			shippers.shippername,
			salesorders.printedpackingslip,
			salesorders.datepackingslipprinted,
			locations.locationname,
			salesorders.orderno,
			salesorders.fromstkloc
		FROM salesorders INNER JOIN debtorsmaster
		ON salesorders.debtorno=debtorsmaster.debtorno
		INNER JOIN shippers
		ON salesorders.shipvia=shippers.shipper_id
		INNER JOIN locations
		ON salesorders.fromstkloc=locations.loccode
		INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
		WHERE salesorders.orderno='" . $_GET['TransNo'] . "'";

if ($_SESSION['SalesmanLogin'] != '') {
	$sql .= " AND salesorders.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
}

$result=DB_query($sql, $ErrMsg);

//If there are no rows, there's a problem.
if (DB_num_rows($result)==0){
	$Title = _('Print Packing Slip Error');
	include('includes/header.inc');
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

	include('includes/footer.inc');
	exit();
} elseif (DB_num_rows($result)==1){ /*There is only one order header returned - thats good! */

        $myrow = DB_fetch_array($result);
		$ErrMsg = _('There was a problem retrieving the order body details for Order Number') . ' ' . $_GET['TransNo'] . ' ' . _('from the database');
		$sql = "SELECT 
					salesorderdetails.orderlineno,
					salesorderdetails.stkcode,
					stockmaster.longdescription,
					stockmaster.units,
					salesorderdetails.quantity,
					salesorderdetails.qtyinvoiced,
					salesorderdetails.unitprice,
					salesorderdetails.narrative,
					stockmaster.mbflag,
					stockmaster.decimalplaces,
					salesorderdetails.drev,
					salesorderdetails.ddesc,
					locstock.bin
				FROM salesorderdetails INNER JOIN stockmaster
				ON salesorderdetails.stkcode=stockmaster.stockid
				INNER JOIN locstock
				ON stockmaster.stockid = locstock.stockid
				WHERE locstock.loccode = '" . $myrow['fromstkloc'] . "'
				AND salesorderdetails.orderno='" . $_GET['TransNo'] . "'
				ORDER BY salesorderdetails.orderlineno ASC";
		$result = DB_Query($sql, $ErrMsg);
		$title = _('Customize Delivery Order');
		if(DB_num_rows($result) == 0){
			$Title = _('Print Packing Slip Error');
			include('includes/header.inc');
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

			include('includes/footer.inc');
		}else{
			$title = _('Customize Delivery Order');
			include('includes/header.inc');
			$date = $myrow['orddate'];
			if($myrow['ddate'] != "9999-12-31"){
				$date=$myrow['ddate'];
			}

				echo '<div class="centre"><br /><br /><br />';
				echo 	'<h1> D/O: '. $_GET['TransNo'] .'</h1>';
				echo '<form name="ItemForm" enctype="multipart/form-data" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?TransNo='. $_GET['TransNo'] .'">';
				echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
				echo '<input type="hidden" name="orderno" value="' . $_GET['TransNo'] . '" />';
				echo 	'<h2> Delivery </h1>';
				echo 	'<table class="delivery" >';
				echo 		'<tr>';
				echo 			'<td>Company Name:</td>';
				echo			'<td><input type="text" name="dname" value="'.$myrow['name'].'" size="50"/></td>';
				echo 		'</tr>';
				echo 		'<tr>';
				echo 			'<td>Address1:</td>';
				echo			'<td><input type="text" name="deladd1" value="'.$myrow['deladd1'].'" size="50"/></td>';
				echo 		'</tr>';
				echo 		'<tr>';
				echo 			'<td>Address2:</td>';
				echo			'<td><input type="text" name="deladd2" value="'.$myrow['deladd2'].'" size="50"/></td>';
				echo 		'</tr>';
				echo 		'<tr>';
				echo 			'<td>Address3:</td>';
				echo			'<td><input type="text" name="deladd3" value="'.$myrow['deladd3'].'" size="50"/></td>';
				echo 		'</tr>';
				echo 		'<tr>';
				echo 			'<td>Address4:</td>';
				echo			'<td><input type="text" name="deladd4" value="'.$myrow['deladd4'].'" size="50"/></td>';
				echo 		'</tr>';
				echo 		'<tr>';
				echo 			'<td>Order Reference:</td>';
				echo			'<td><input type="text" name="salesref" value="'.$myrow['customerref'].'" size="50"/></td>';
				echo 		'</tr>';
				echo 		'<tr>';
				echo 			'<td>Date:</td>';
				echo			'<td><input type="text" name="ddate" value="'.$date.'" size="50"/></td>';
				echo 		'</tr>';
				echo 		'<tr>';
				echo 			'<td>Terms:</td>';
				echo			'<td><input type="text" name="ddate" value="'.translateTerms($myrow['paymentterms']).'" size="50"/></td>';
				echo 		'</tr>';
				echo 	'</table>';
				echo 	'<h2> Item Descriptions</h2>';
				$cnt = 0;
				while($myrow2 = DB_fetch_array($result)){
					$cnt = $myrow2['orderlineno'];
					$revQ = "SELECT
								stockitemproperties.value,
								stockcatproperties.label
								from stockitemproperties INNER JOIN stockcatproperties 
								on stockitemproperties.stkcatpropid=stockcatproperties.stkcatpropid 
								INNER JOIN stockmaster on stockitemproperties.stockid = stockmaster.stockid 
								WHERE stockmaster.stockid = '".$myrow2['stkcode'] ."' and stockcatproperties.label='revision'";

					$revRes = DB_Query($revQ, $ErrMsg);
					$rev = DB_fetch_array($revRes)['value'];
					if($myrow2['ddrev'] != ""){
						$rev = $myrow2['ddrev'];
					}
					$desc = $myrow2['longdescription'];
					
					if($myrow2['ddesc'] != ""){
						$desc = $myrow2['ddesc'];
					}
					echo 	'<table class="item-desc">';
					echo 		'<tr>';
					echo 			'<td>S/N: '. ($cnt+1) .'</td>';
					echo 			'<td>Item No. :</td>';
					echo 			'<td><input type="text" name="stkcode'. $cnt. '" value="' . $myrow2['stkcode']. '" size="25"/></td>'; 
					echo				'<td>Rev :</td>';
					echo 			'<td><input type="text" name="drev'.$cnt.'" value="' . $rev . '" size="10"/></td>';
					echo 		'</tr>';
					echo 		'<tr>';
					echo 			'<td>description:</td>';
					echo 			'<td colspan="3">';
					echo 				'<textarea type="text" name="ddesc'.$cnt.'" rows="4" cols="60" >'.trim($desc).'</textarea>';
					echo			'</td><td>(max 5 rows)</td';
					echo 		'</tr>';
					echo 		'<tr>';
					echo 			'<td>units:</td';
					echo 			'<td><input type="text" name="units'.$cnt.'" value="' . $myrow2['units'] . '" size="5"/></td>';
					echo 			'<td>quantity:</td>';
					echo 			'<td><input type="text" name="qty'.$cnt.'" value="' . $myrow2['quantity'] . '" size="7"/></td>';
					echo		'</tr>';
					echo 	'</table>';
				
				}
				echo '<input type="hidden" name="itemCnt" value="' . ($cnt+1) . '" />';
				echo '<input type="hidden" name="debtorno" value="'.$myrow['debtorno'].'"/>';

				echo '<table><tr><td>Remarks:</td>';
				echo '<td><input type="text" name="docremarks" value="'.$myrow['docremarks'].'"/></td></tr></table>';

				echo '<input type="submit" name="submit" value="' . _('Save and Print DO') . '" />';
				echo '</form></div>';

				
			include('includes/footer.inc');
		}
}


?>