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

function calcBarcodeLength($text){
	$numChar = strlen($text);
	return (11*$numChar + 35)*0.90;
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

if($_GET['Print']){
	$Print = TRUE;
}

include('includes/session.inc');
include('includes/SQL_CommonFunctions.inc');
include('includes/barcodepack/class.code128.php');
for($i=0; $i<$_POST['numParts']; $i++){
	if(empty($_POST['customerref'.$i])){
		include('includes/header.inc');
	echo '<div class="centre"><br /><br /><br />';
	prnMsg( _('PO number cannot be empty.') , 'error');
	echo '<br />
			<br />
			<br />
			<table class="table_index">
			<tr>
			<td class="menu_group_item">
            <ul>
			    <li><a href="'. $RootPath . '/PrintBarcode.php?TransNo='.$_GET['TransNo'].'">' . _('Back') . '</a></li>
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
}
if(isset($_POST['submit']) AND isset($Print)){


	$PaperSize = 'BAR_CODE_LABEL';
	include('includes/PDFStarter.php');
	//$pdf->selectFont('./fonts/Helvetica.afm');
	$pdf->addInfo('Title', _('Delivery Order') );
	$pdf->addInfo('Subject', _('Deliver Order for') . ' ' . $_GET['TransNo']);
	$FontSize=7;
	$line_height=16;
	$FirstPage = true;
	$Copy = 'Office';
	for($i=0; $i <$_POST['numParts']; $i++){
		if(!$_POST['print'.$i]){
			continue;
		}
		$qtyLeft = $_POST['tqty'.$i];
		$qty = $_POST['qty'.$i];
		while($qtyLeft > 0)	{
			if($qtyLeft >= $qty){
				$qtyLeft -= $qty;
			}else{
				$qty = $qtyLeft;
				$qtyLeft = 0;
			}
			
			if($FirstPage){
				$FirstPage = false;
			}else{
				$pdf->newPage();
			}
			$stockcodeBar = new code128($_POST['partno'.$i]);
			ob_start();
			imagepng(imagepng($stockcodeBar->draw()));
			$Image_String = ob_get_contents();
			ob_end_clean();
			$XPos1= 10;
			$pdf->addJpegFromFile('@' . $Image_String,$XPos1,115,calcBarcodeLength($_POST['partno'.$i]), 45);
			$pdf->addText($XPos1, 125, $FontSize, "Part:");
			if(!empty($_POST['datecode'.$i])){
				$datecodeBar = new code128($_POST['datecode'.$i]);
				ob_start();
				imagepng(imagepng($datecodeBar->draw()));
				$Image_String = ob_get_contents();
				ob_end_clean();
				$pdf->addJpegFromFile('@' . $Image_String,$XPos1,70,calcBarcodeLength($_POST['datecode'.$i]), 45);
				$pdf->addText($XPos1-3, 80, $FontSize, "Date");
				$pdf->addText($XPos1-3, 73, $FontSize, "Code:");
			}

				$qtyBar = new code128($qty);
				ob_start();
				imagepng(imagepng($qtyBar->draw()));
				$Image_String = ob_get_contents();
				ob_end_clean();
				$pdf->addJpegFromFile('@' . $Image_String,$XPos1,20,calcBarcodeLength($qty), 45);
				$pdf->addText($XPos1, 31, $FontSize, "Qty:");
				$pdf->addText($XPos1+70, 31, $FontSize, $_POST['units'.$i]);


				$POBar = new code128($_POST['customerref'.$i]);
				ob_start();
				imagepng(imagepng($POBar->draw()));
				$Image_String = ob_get_contents();
				ob_end_clean();
				$pdf->addJpegFromFile('@' . $Image_String,$XPos1+100,20,calcBarcodeLength($_POST['customerref'.$i]), 45);
				$pdf->addText($XPos1+100, 31, $FontSize, "PO:");
				$pdf->addText($XPos1, 18, $FontSize+2, "Vendor: Ming Kee Metal Works Pte Ltd");
				$pdf->addJpegFromFile('css/mk_black.jpg', 220, 110, 50, 50);
				$pdf->addText(215, 110, $FontSize+2, "QA Approved");
				
			
		}
	}
	$pdf->OutputD($_SESSION['DatabaseName'] . '_BarCodes_' . $_GET['TransNo'] . '.pdf');
	$pdf->__destruct();


}
//Get Out if we have no order number to work with
if (!isset($_GET['TransNo']) OR $_GET['TransNo']==""){
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
		$title = _('Printing Barcode');
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
			include('includes/header.inc');
			echo '<div class="centre"><br /><br /><br />';
			echo '<h1> D/O / INV: ' . $_GET['TransNo'];
			

			echo '<form name="ItemForm" enctype="multipart/form-data" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?TransNo='. $_GET['TransNo'] .'&Print=true">';
			echo '<table>
			<th>Print This Part</th>
			<th>Part Number</th>
			<th>Total Qty</th>
			<th>Qty per carton</th>
			<th>Unit</th>
			<th>P/O No</th>
			<th>Date code</th>
			';
			
			$numParts = DB_num_rows($result);
			while($myrow2 = DB_fetch_array($result)){
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
					<tr>
						<td><input type="checkbox" name="print'.$myrow2['orderlineno'].'" checked/></td>
						
						<td><input type="text" name="partno'.$myrow2['orderlineno'].'" value="'.$myrow2['stkcode'].'" size="30"/></td>
						
						<td><input type="text" name="tqty'.$myrow2['orderlineno'].'" value="'.$myrow2['quantity'].'" size="30"/></td>
						
						<td><input type="text" name="qty'.$myrow2['orderlineno'].'" value="'.$myrow2['quantity'].'" size="30"/></td>

						<td><input type="text" name="units'.$myrow2['orderlineno'].'" value="'.$myrow2['units'].'" size="30"/></td>

						<td><input type="text" name="customerref'.$myrow2['orderlineno'].'" value="'.$myrow2['customerref'].'" size="30"/></td>
					
						<td><input type="text" name="datecode'.$myrow2['orderlineno'].'" value="'.$datecode.'" size="30"/></td>
					</tr>';
		}
		echo '</table>';
		echo '<input type="hidden" name="numParts" value="'.$numParts.'"/>';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
		echo '<input type="submit" name="submit" value="' . _('Print Barcodes') . '" />';
		echo'</form>';
		echo '</div>';

				
			include('includes/footer.inc');
		}
}


?>