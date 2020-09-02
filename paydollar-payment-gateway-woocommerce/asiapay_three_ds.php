<?php


function getCustomerDetl($order_id){
	$arrData = array();
	$order = new WC_Order($order_id);
	
	$order_meta = get_post_meta($order_id);

    $custid = $order->get_customer_id();
	// echo $custid;

	// Get the Customer billing email
	$arrData['threeDSCustomerEmail'] = $arrData['threeDSDeliveryEmail']  = $order->get_billing_email();

	$customerPhonenum = preg_replace('/\D/', '',$order->get_billing_phone());

	// Get the Customer billing phone
	$c = New WC_Countries();
	// print_r($c->get_country_calling_code($cntry));
	$txtBCountry =$order->get_billing_country();
	$customerBillPhonecc = preg_replace('/\D/', '',$c->get_country_calling_code($txtBCountry));
	
	$arrData['threeDSMobilePhoneNumber']  = $arrData['threeDSHomePhoneNumber'] = $arrData['threeDSWorkPhoneNumber'] = $customerPhonenum;
	$arrData['threeDSMobilePhoneCountryCode']  = $arrData['threeDSHomePhoneCountryCode'] = $arrData['threeDSWorkPhoneCountryCode'] = $customerBillPhonecc;

	$cBAddress = getCustomerBillAddress($order_id);
	$cSAddress = getCustomerShipAddress($order_id);


	$arrData = array_merge($arrData, $cBAddress, $cSAddress);

	return $arrData;
}

function getCustomerBillAddress($order_id){
	$arrBData = array();
	$oB = new WC_Order($order_id);
	$coB = New WC_Countries();

	$txtBCountry =$oB->get_billing_country();
	$customerBillPhonecc = preg_replace('/\D/', '',getCountryCodeNumeric($txtBCountry));
	$arrBData['threeDSBillingLine1']  = $oB->get_billing_address_1();
	$arrBData['threeDSBillingLine2']  = $oB->get_billing_address_2();
	$arrBData['threeDSBillingCity']       = $oB->get_billing_city();
	$arrBData['threeDSBillingCountryCode']      = $customerBillPhonecc;
	$arrBData['threeDSBillingPostalCode']   = $oB->get_billing_postcode();
	$arrBData['threeDSBillingState']    = $oB->get_billing_country();

	// print_r($arrBData);
	return $arrBData;
}

function getCustomerShipAddress($order_id){
	$arrSData = array();
	$oS = new WC_Order($order_id);
	$coS = New WC_Countries();
	
	$txtSCountry = $oS->get_shipping_country();
	$customerShipPhonecc = preg_replace('/\D/', '',getCountryCodeNumeric($txtSCountry));
	$arrSData['threeDSShippingLine1']  = $oS->get_shipping_address_1();
	$arrSData['threeDSShippingLine2']  = $oS->get_shipping_address_2();
	$arrSData['threeDSShippingCity']       = $oS->get_shipping_city();
	$arrSData['threeDSShippingCountryCode']      = $customerShipPhonecc;
	$arrSData['threeDSShippingPostalCode']   = $oS->get_shipping_postcode();
	$arrSData['threeDSShippingState']    = $oS->get_shipping_country();

	return $arrSData;
}

function isSameBillShipAddress($order_id){
	$b = getCustomerBillAddress($order_id);
	$s = getCustomerShipAddress($order_id);
	$cnt = 0;
	if($b['threeDSBillingLine1'] == $s['threeDSShippingLine1'])$cnt++;
	if($b['threeDSBillingLine2'] == $s['threeDSShippingLine2'])$cnt++;
	if($b['threeDSBillingCity'] == $s['threeDSShippingCity'])$cnt++;
	if($b['threeDSBillingCountryCode'] == $s['threeDSShippingCountryCode'])$cnt++;
	if($b['threeDSBillingPostalCode'] == $s['threeDSShippingPostalCode'])$cnt++;
	if($b['threeDSBillingState'] == $s['threeDSShippingState'])$cnt++;

	return ($cnt==6) ? "T" : "F";
}

function getAccountInfo($order_id){
	$arrData = array();
	$order = new WC_Order($order_id);

	$u = $order->get_user();

    $custid = $u->data->ID;
    $arrData['threeDSAcctAuthMethod'] = "01";
   	if($custid){
   		$c = new WC_Customer($custid);//print_r($c);
   		$dte_add = date('Ymd' , strtotime($c->date_created));
   		$dte_upd = date('Ymd' , strtotime($c->date_modified));

   		$dteAdd_diff = getDateDiff($dte_add);
   		$dteUpd_diff = getDateDiff($dte_upd);

   		$dteAddAge = getAcctAgeInd($dteAdd_diff);
   		$dteUpdAge = getAcctAgeInd($dteUpd_diff);

		// echo "this date<pre>";
		
		$arrData['threeDSAcctCreateDate'] = $dte_add;
		$arrData['threeDSAcctLastChangeDate'] = $dte_upd;

		$arrData['threeDSAcctAgeInd'] = $dteAddAge;
		$arrData['threeDSAcctLastChangeInd'] = $dteUpdAge;

		$arrData['threeDSAcctPurchaseCount'] = getAllOrder($c->email,6);
		$arrData['threeDSAcctNumTransDay'] = getAllOrder($c->email,24);
		$arrData['threeDSAcctNumTransYear'] = getAllOrder($c->email,12);

		$arrData['threeDSAcctAuthMethod'] = "02";

		$curent_login_time = get_user_meta( $custid, 'wc_last_active', true );
		$arrData['threeDSAcctAuthTimestamp'] = gmdate("Ymd" ,$curent_login_time);
		
   	}
	return $arrData;

}

function getAllOrder($email,$date = NULL){
	$time = "";
	$arrOrd = array();
	$arrOrd['customer'] = $email;
	switch ($date) {
		case 6:
			$time = date('Y-m-d H:i:s', strtotime("-6 months"));
			$arrOrd['date_paid'] = '>='.$time;
			break;
		case 24:
			$time = date('Y-m-d H:i:s', strtotime("-1 day"));
			break;
		case 12:
			$time = date('Y-m-d H:i:s', strtotime("-1 year"));
			break;
		default:
			# code...
			break;
	}
	
	$arrOrd['date_created'] = '>='."$time";

	$orders = wc_get_orders( $arrOrd );

	return count($orders);
}

function getDateDiff($d){
    		$datenow = date('Ymd');
			$dt1 = new \DateTime($datenow);
			$dt2 = new \DateTime($d);
			$interval = $dt1->diff($dt2)->format('%a');
			return $interval;
    }

function getAcctAgeInd($d){
    	switch ($d) {
    		case 0:
    			# code...
    			$ret = "02";
    			break;
    		case $d<30:
    			# code...
    			$ret = "03";
    			break;
    		case $d>30 && $d<60:
    			# code...
    			$ret = "04";
    			break;
    		case $d>60:
    			$ret = "05"	;
				break;	
    		default:
    			# code...
    			break;
    	}
    	return $ret;

    }


function getAllThreeDsParam($order_id){
	
	$arrParam = array();

	$customerDetl = getCustomerDetl($order_id);
	$c = new WC_Customer($customerId);

	$arrParam['threeDSIsAddrMatch'] = isSameBillShipAddress($order_id);

	$arrParam['threeDSShippingDetails'] = (isSameBillShipAddress($order_id)=="T")?'01':'03';

	$arrAccntInfo = getAccountInfo($order_id);

	$arrParam = array_merge($arrParam,$customerDetl,$arrAccntInfo);
	// print_r($arrParam);
	return $arrParam;
}

function getCountryCodeNumeric($code){
		$countrycode = array('AF'=>'4','AL'=>'8','DZ'=>'12','AS'=>'16','AD'=>'20','AO'=>'24','AI'=>'660','AQ'=>'10','AG'=>'28','AR'=>'32','AM'=>'51','AW'=>'533','AU'=>'36','AT'=>'40','AZ'=>'31','BS'=>'44','BH'=>'48','BD'=>'50','BB'=>'52','BY'=>'112','BE'=>'56','BZ'=>'84','BJ'=>'204','BM'=>'60','BT'=>'64','BO'=>'68','BO'=>'68','BA'=>'70','BW'=>'72','BV'=>'74','BR'=>'76','IO'=>'86','BN'=>'96','BN'=>'96','BG'=>'100','BF'=>'854','BI'=>'108','KH'=>'116','CM'=>'120','CA'=>'124','CV'=>'132','KY'=>'136','CF'=>'140','TD'=>'148','CL'=>'152','CN'=>'156','CX'=>'162','CC'=>'166','CO'=>'170','KM'=>'174','CG'=>'178','CD'=>'180','CK'=>'184','CR'=>'188','CI'=>'384','CI'=>'384','HR'=>'191','CU'=>'192','CY'=>'196','CZ'=>'203','DK'=>'208','DJ'=>'262','DM'=>'212','DO'=>'214','EC'=>'218','EG'=>'818','SV'=>'222','GQ'=>'226','ER'=>'232','EE'=>'233','ET'=>'231','FK'=>'238','FO'=>'234','FJ'=>'242','FI'=>'246','FR'=>'250','GF'=>'254','PF'=>'258','TF'=>'260','GA'=>'266','GM'=>'270','GE'=>'268','DE'=>'276','GH'=>'288','GI'=>'292','GR'=>'300','GL'=>'304','GD'=>'308','GP'=>'312','GU'=>'316','GT'=>'320','GG'=>'831','GN'=>'324','GW'=>'624','GY'=>'328','HT'=>'332','HM'=>'334','VA'=>'336','HN'=>'340','HK'=>'344','HU'=>'348','IS'=>'352','IN'=>'356','ID'=>'360','IR'=>'364','IQ'=>'368','IE'=>'372','IM'=>'833','IL'=>'376','IT'=>'380','JM'=>'388','JP'=>'392','JE'=>'832','JO'=>'400','KZ'=>'398','KE'=>'404','KI'=>'296','KP'=>'408','KR'=>'410','KR'=>'410','KW'=>'414','KG'=>'417','LA'=>'418','LV'=>'428','LB'=>'422','LS'=>'426','LR'=>'430','LY'=>'434','LY'=>'434','LI'=>'438','LT'=>'440','LU'=>'442','MO'=>'446','MK'=>'807','MG'=>'450','MW'=>'454','MY'=>'458','MV'=>'462','ML'=>'466','MT'=>'470','MH'=>'584','MQ'=>'474','MR'=>'478','MU'=>'480','YT'=>'175','MX'=>'484','FM'=>'583','MD'=>'498','MC'=>'492','MN'=>'496','ME'=>'499','MS'=>'500','MA'=>'504','MZ'=>'508','MM'=>'104','MM'=>'104','NA'=>'516','NR'=>'520','NP'=>'524','NL'=>'528','AN'=>'530','NC'=>'540','NZ'=>'554','NI'=>'558','NE'=>'562','NG'=>'566','NU'=>'570','NF'=>'574','MP'=>'580','NO'=>'578','OM'=>'512','PK'=>'586','PW'=>'585','PS'=>'275','PA'=>'591','PG'=>'598','PY'=>'600','PE'=>'604','PH'=>'608','PN'=>'612','PL'=>'616','PT'=>'620','PR'=>'630','QA'=>'634','RE'=>'638','RO'=>'642','RU'=>'643','RU'=>'643','RW'=>'646','SH'=>'654','KN'=>'659','LC'=>'662','PM'=>'666','VC'=>'670','VC'=>'670','VC'=>'670','WS'=>'882','SM'=>'674','ST'=>'678','SA'=>'682','SN'=>'686','RS'=>'688','SC'=>'690','SL'=>'694','SG'=>'702','SK'=>'703','SI'=>'705','SB'=>'90','SO'=>'706','ZA'=>'710','GS'=>'239','ES'=>'724','LK'=>'144','SD'=>'736','SR'=>'740','SJ'=>'744','SZ'=>'748','SE'=>'752','CH'=>'756','SY'=>'760','TW'=>'158','TW'=>'158','TJ'=>'762','TZ'=>'834','TH'=>'764','TL'=>'626','TG'=>'768','TK'=>'772','TO'=>'776','TT'=>'780','TT'=>'780','TN'=>'788','TR'=>'792','TM'=>'795','TC'=>'796','TV'=>'798','UG'=>'800','UA'=>'804','AE'=>'784','GB'=>'826','US'=>'840','UM'=>'581','UY'=>'858','UZ'=>'860','VU'=>'548','VE'=>'862','VE'=>'862','VN'=>'704','VN'=>'704','VG'=>'92','VI'=>'850','WF'=>'876','EH'=>'732','YE'=>'887','ZM'=>'894','ZW'=>'716');
		return $countrycode[$code];

	}