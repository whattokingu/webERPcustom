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
if(isset($_POST['submit']) AND isset($_GET['PrintPDF'])){
    

	/* Then there's an order to print and its not been printed already (or its been flagged for reprinting)
	LETS GO */


	$PaperSize = 'A4';
	include('includes/PDFStarter.php');
	//$pdf->selectFont('./fonts/Helvetica.afm');
	$pdf->addInfo('Title', _('Purchase Order') );
	$pdf->addInfo('Subject', _('Purchase Order for') . ' ' . $_GET['OrderNo']);
	$FontSize=12;
	$line_height=24;
	$PageNumber = 1;
	$Copy = 'Office';
//	echoObj($_POST);
	for ($i=1;$i<=2;$i++){  /*Print it out twice one copy for customer and one for office */
		if ($i==2){
			$PageNumber = 1;
			$pdf->newPage();
		}
		$purchase = true;
        include('includes/PDFOrderPageHeader_generic.inc');

        $numItems = $_POST['itemCnt'];
        for($SN = 0; $SN < $numItems; $SN++){

            $DisplayQty = locale_number_format($_POST['qty'.($SN)]);
            $LeftOvers = $pdf->addTextWrap($SNXPos,$YPos,$SNW,$FontSize, $i, 'center');
            $LeftOvers = $pdf->addTextWrap($DescXPos, $YPos, 150, $FontSize, $_POST['stkcode'.$SN], 'left');
            $LeftOvers = $pdf->addTextWrap($DescXPos+170, $YPos, 110, $FontSize, $_POST['drev'.$SN], 'right');
            $LeftOvers = $pdf->addTextWrap($QtyXPos,$YPos-15,$UnitW,$FontSize,$_POST['units'.$SN],'center');
            $LeftOvers = $pdf->addTextWrap($QtyXPos,$YPos,$QtyW,$FontSize,$DisplayQty,'center');

            $LeftOvers = $pdf->addTextWrap($DescXPos,$YPos-18,$DescW,$FontSize,breakTextToLines($_POST['ddesc'.$SN], 1));
            $LeftOvers = $pdf->addTextWrap($DescXPos,$YPos-36,$DescW,$FontSize,breakTextToLines($_POST['ddesc'.$SN], 2));
            $LeftOvers = $pdf->addTextWrap($DescXPos,$YPos-54,$DescW,$FontSize,breakTextToLines($_POST['ddesc'.$SN], 3));
            $LeftOvers = $pdf->addTextWrap($DescXPos,$YPos-72,$DescW,$FontSize,breakTextToLines($_POST['ddesc'.$SN], 4));
            $LeftOvers = $pdf->addTextWrap($UPriceXPos+10, $YPos, $UPriceW, $FontSize, $_POST['unitprice'.$SN], 'right');
            $LeftOvers = $pdf->addTextWrap($SubTotalXPos, $YPos, $SubTotalW, $FontSize, $_POST['netprice'.$SN], 'right');
            $YPos -= 70;
            if ($YPos-$line_height <= 240 && $SN < $numItems){
                /* We reached the end of the page so finsih off the page and start a newy */
                $PageNumber++;
                include ('includes/PDFOrderPageHeader_generic.inc');
            } //end if need a new page headed up
            else {
                /*increment a line down for the next line item */
                $YPos -= ($line_height);
            }
        }


        $DisplaySubTot = locale_number_format($_POST['subtotalprice'], 2);
        $DisplayTax = locale_number_format($_POST['taxprice'], 2);
        $DisplayTotal = locale_number_format($_POST['totalprice'], 2);

        $pdf->addText($DescXPos, $YPos, $FontSize, $_POST['docremarks']);

        $linestyle = array(
            width => '1',
            dash => '6,3'
        );

        $FontSize=12;
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
        $LeftOvers = $pdf->addTextWrap($DescXPos + 230, $YPos, 100, $FontSize, _('Total Amount:', 'left'));
        $LeftOvers = $pdf->addTextWrap($SubTotalXPos, $YPos, 70, $FontSize, $DisplayTotal, 'right');


		$Copy='Customer';

	} /*end for loop to print the whole lot twice */

    $sql = "UPDATE purchorders	SET	allowprint =  0,
										dateprinted  = '" . Date('Y-m-d') . "',
										status = 'Printed'
				WHERE purchorders.orderno = '" . $_POST['orderno'] . "'";
    $result = DB_query($sql);
    $pdf->OutputD('mkmw_PurchaseOrder_' . $_GET['OrderNo'] . '.pdf');
    $pdf->__destruct();

    exit();
}
//Get Out if we have no order number to work with
If (!isset($_GET['OrderNo'])){
	$Title = _('Select Purchase Order To Print');
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
			    <li><a href="'. $RootPath . '/PO_SelectPurchOrder.php">' . _('Purchase Orders') . '</a></li>
			    
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
$OrderNo = $_GET['OrderNo'];
$ErrMsg = _('There was a problem retrieving the purchase order header details for Order Number') . ' ' . $OrderNo . ' ' . _('from the database');
$sql = "SELECT	purchorders.supplierno,
					suppliers.suppname,
					suppliers.address1,
					suppliers.address2,
					suppliers.address3,
					suppliers.address4,
					suppliers.address5,
					suppliers.address6,
					purchorders.comments,
					purchorders.orddate,
					purchorders.rate,
					purchorders.dateprinted,
					purchorders.deladd1,
					purchorders.deladd2,
					purchorders.deladd3,
					purchorders.deladd4,
					purchorders.deladd5,
					purchorders.deladd6,
					purchorders.allowprint,
					purchorders.requisitionno,
					www_users.realname as initiator,
					purchorders.paymentterms,
					suppliers.currcode,
					purchorders.status,
					purchorders.stat_comment,
					currencies.decimalplaces AS currdecimalplaces
				FROM purchorders INNER JOIN suppliers
					ON purchorders.supplierno = suppliers.supplierid
				INNER JOIN currencies
					ON suppliers.currcode=currencies.currabrev
				INNER JOIN www_users
					ON purchorders.initiator=www_users.userid
				INNER JOIN locationusers ON locationusers.loccode=purchorders.intostocklocation AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
				WHERE purchorders.orderno='" . $OrderNo . "'";

$result=DB_query($sql, $ErrMsg);

//If there are no rows, there's a problem.
if (DB_num_rows($result)==0){
	$Title = _('Print Purchase Order Error');
	include('includes/header.inc');
	echo '<div class="centre"><br /><br /><br />';
	prnMsg( _('Unable to Locate Order Number') . ' : ' . $_GET['OrderNo'] . ' ', 'error');
	echo '<br />
			<br />
			<br />
			<table class="table_index">
			<tr>
			<td class="menu_group_item">
			<li><a href="'. $RootPath . '/PO_SelectPurchOrder.php">' . _('Select Purchase Orders') . '</a></li>
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
    $ErrMsg = _('There was a problem retrieving the line details for order number') . ' ' . $OrderNo . ' ' . _('from the database');
    $sql = "SELECT itemcode,
						deliverydate,
						itemdescription,
						unitprice,
						suppliersunit,
						quantityord,
						decimalplaces,
						conversionfactor,
						suppliers_partno
				FROM purchorderdetails LEFT JOIN stockmaster
					ON purchorderdetails.itemcode=stockmaster.stockid
				WHERE orderno ='" . $OrderNo . "'
				ORDER BY itemcode";	/*- ADDED: Sort by our item code -*/


		$result = DB_Query($sql, $ErrMsg);
		$title = _('Customize Purchase Order');
		if(DB_num_rows($result) == 0){
			$Title = _('Print Purchase Order Error');
			include('includes/header.inc');
			echo '<div class="centre"><br /><br /><br />';
			prnMsg( _('Unable to Locate Order Number') . ' : ' . $OrderNo . ' ', 'error');
			echo '<br />
					<br />
					<br />
					<table class="table_index">
					<tr>
					<td class="menu_group_item">
				
					<li><a href="'. $RootPath . '/PO_SelectPurchaseOrder.php">' . _('Select Purchase Orders') . '</a></li>
					</td>
					</tr>
					</table>
					</div>
					<br />
					<br />
					<br />';

			include('includes/footer.inc');
		}else{
			$title = _('Customize Purchase Order');
			include('includes/header.inc');
			$date = $myrow['orddate'];


				echo '<div class="centre"><br /><br /><br />';
				echo 	'<h1> P/O: '. $_GET['OrderNo'] .'</h1>';
				echo '<form name="ItemForm" enctype="multipart/form-data" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?OrderNo='. $_GET['OrderNo'] .'&PrintPDF=true">';
				echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
				echo '<input type="hidden" name="orderno" value="' . $_GET['OrderNo'] . '" />';
				echo 	'<h2> Supplier </h2>';
				echo 	'<table class="delivery" >';
				echo 		'<tr>';
				echo 			'<td>Company Name:</td>';
				echo			'<td><input type="text" name="sname" value="'.$myrow['suppname'].'" size="50"/></td>';
				echo 		'</tr>';
				echo 		'<tr>';
				echo 			'<td>Address1:</td>';
				echo			'<td><input type="text" name="sadd1" value="'.$myrow['address1'].'" size="50"/></td>';
				echo 		'</tr>';
				echo 		'<tr>';
				echo 			'<td>Address2:</td>';
				echo			'<td><input type="text" name="sadd2" value="'.$myrow['address2'].'" size="50"/></td>';
				echo 		'</tr>';
				echo 		'<tr>';
				echo 			'<td>Address3:</td>';
				echo			'<td><input type="text" name="sadd3" value="'.$myrow['address3'].'" size="50"/></td>';
				echo 		'</tr>';
				echo 		'<tr>';
				echo 			'<td>Address4:</td>';
				echo			'<td><input type="text" name="sadd4" value="'.$myrow['address4'].'" size="50"/></td>';
				echo 		'</tr>';
                echo 		'<tr>';
                echo 			'<td>Address5:</td>';
                echo			'<td><input type="text" name="sadd5" value="'.$myrow['address5'].'" size="50"/></td>';
                echo 		'</tr>';
                echo 		'<tr>';
                echo 			'<td>Delivery Date:</td>';
                echo			'<td><input type="text" name="dedate" value="ASAP" size="50"/></td>';
                echo 		'</tr>';
				echo 		'<tr>';
				echo 			'<td>Order Date:</td>';
				echo			'<td><input type="text" name="ddate" value="'.ConvertSQLDate($date).'" size="50"/></td>';
				echo 		'</tr>';
				echo 		'<tr>';
				echo 			'<td>Acc No:</td>';
				echo			'<td><input type="text" name="accno" value="'.$myrow['supplierno'].'" size="50"/></td>';
				echo 		'</tr>';
                echo 		'<tr>';
                echo 			'<td>Terms:</td>';
                echo			'<td><input type="text" name="paymentterms" value="'.translateTerms($myrow['paymentterms']).'" size="50"/></td>';
                echo 		'</tr>';
				echo 	'</table>';
				echo 	'<h2> Item Descriptions</h2>';
				$cnt = -1;
				$SubTotalPrice = 0;
				while($myrow2 = DB_fetch_array($result)){
					$cnt++;
					$revQ = "SELECT
								stockitemproperties.value,
								stockcatproperties.label
								from stockitemproperties INNER JOIN stockcatproperties 
								on stockitemproperties.stkcatpropid=stockcatproperties.stkcatpropid 
								INNER JOIN stockmaster on stockitemproperties.stockid = stockmaster.stockid 
								WHERE stockmaster.stockid = '".$myrow2['itemcode'] ."' and stockcatproperties.label='revision'";

					$revRes = DB_Query($revQ, $ErrMsg);
					$rev = DB_fetch_array($revRes)['value'];
					$desc = $myrow2['itemdescription'];


                    $DisplayNet=locale_number_format(($myrow2['quantityord'] * $myrow2['unitprice']),2);
                    $DisplayPrice=locale_number_format($myrow2['unitprice'.$SN],2);
                    $DisplayQty=locale_number_format($myrow2['quantityord'.$SN],2);
					echo 	'<table style="width:100%">';
					echo        '<tr>
                                    <th>S/N</th>
                                    <th>Item Description</th>
                                    <th>Quantity</th>
                                    <th>Unit</th>
                                    <th>Unit Price</th>
                                    <th>Total Price</th>
                                </tr>';
					echo 		'<tr>';
					echo 			'<td>'. ($cnt+1) .'</td>';
					echo 			'<td>
                                        <table>
                                             <tr>
                                                <td>item no: <input type="text" name="stkcode'. $cnt. '" value="' . $myrow2['itemcode']. '" size="25"/></td> 
                                                <td>rev: <input type="text" name="drev'.$cnt. '" value="' . $rev . '" size="10"/></td>
                                             </tr>
                                             <tr>
                                                <td><textarea type="text" name="ddesc'.$cnt.'" rows="4" cols="50" >'.trim($desc).'</textarea></td>
                                                <td>(max 4 rows)</td>
                                             </tr>
                                         </table>';
					echo 			'<td><input type="text" name="qty'.$cnt.'" value="' . $DisplayQty . '" size="7"/></td>';
                    echo 			'<td><input type="text" name="units'.$cnt.'" value="' . $myrow2['suppliersunit'] . '" size="5"/></td>';
                    echo 			'<td><input type="text" name="unitprice'.$cnt.'" value="' . $DisplayPrice . '" /></td>';
                    echo            '<td><input type="text" name="netprice'.$cnt.'" value="'.$DisplayNet.'" /></td>';
                    echo		'</tr>';
					echo 	'</table>';

                    $SubTotalPrice += $DisplayNet;
				}

				$DisplaySubTot = locale_number_format($SubTotalPrice, 2);
				$DisplayTax = locale_number_format($DisplaySubTot * 0.07, 2);
				$DisplayTotal = locale_number_format($DisplaySubTot + $DisplayTax, 2);
				echo '<input type="hidden" name="itemCnt" value="' . ($cnt+1) . '" />';
				echo '<input type="hidden" name="supplierno" value="'.$myrow['supplierno'].'"/>';

				echo '<table><tr><td>Remarks:</td>';
				echo '<td><input type="text" name="docremarks" value="'.$myrow['docremarks'].'"/></td></tr></table>';

                echo '<table style="width:100%">
                       <tr>
                        <td class="number">' . _('Sub Total') . '</td>
                        <td><input type="number" name="subtotalprice" value="'.$DisplaySubTot.'"/></td></tr>';
                echo '<tr>
                        <td class="number">'. _('GST (7%)') . '</td>
                        <td><input type="number" name="taxprice" value="'.$DisplayTax.'"/></td>
                      </tr>';
                echo '<tr>
                        <td class="number">' . _('TOTAL AMOUNT:') .' </td>
                        <td><input type="number" name="totalprice" value="'.$DisplayTotal.'"/></td>
                      </tr>';
                echo '</table>';

				echo '<input type="submit" name="submit" value="' . _('Save and Print PO') . '" />';
				echo '</form></div>';
				echoObj($_SESSION['AllowedPageSecurityTokens']);

				
			include('includes/footer.inc');
		}
}


?>