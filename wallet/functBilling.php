<?php
//error_reporting(E_ERROR | E_PARSE); //ini_set('display_errors',2);
include $_SERVER['DOCUMENT_ROOT']."/inc/jsonRPCClient.php"; //calls server.php within
include $_SERVER['DOCUMENT_ROOT']."/inc/funct_jsonrpc.php"; //calls server.php within
//include $_SERVER['DOCUMENT_ROOT']."/inc/lib_bitstamp.php"; //bitstamp api class
//include $_SERVER['DOCUMENT_ROOT']."/inc/lib_anx-gox-api.php"; //anx api class


function funct_Billing_GetBalance($strAccount){ 

	$guid=urlencode(COINCAFE_API_LOGIN);
	$firstpassword=urlencode(COINCAFE_API_PASSWORD);
	$strAccount = urlencode($strAccount);
	$json_url = COINCAFE_API_MERCHANT_URL."?do=getbalance&loginname=$guid&password=$firstpassword&account=$strAccount";
	$strReturnError = file_get_contents($json_url);
	return $strReturnError ;
}

function funct_Billing_ValidateTransactionHash($strTransactionHash){ 

	$guid=urlencode(COINCAFE_API_LOGIN);
	$firstpassword=urlencode(COINCAFE_API_PASSWORD);
	$strAddress = urlencode($strAddress);
	$json_url = COINCAFE_API_MERCHANT_URL."?do=validate_transaction&loginname=$guid&password=$firstpassword&txid=$strTransactionHash";
	$strReturnError = file_get_contents($json_url);
	
	//echo "url: $json_url" ;
	//site just spits our normal code now not json...
	//$json_data = file_get_contents($json_url);
	//$json_feed = json_decode($json_data);
	//print_r($json_feed);
	//$intIsValid = $json_feed->isvalid;

	return $strReturnError ;
}


function funct_Billing_ValidateAddress($strAddress){ 
//get our account balance - restricted to getcoincafe.com server ip address 162.144.93.87
//called by /mods/sendcrypto.php /mods/processorder2.php
	
	$guid=urlencode(COINCAFE_API_LOGIN);
	$firstpassword=urlencode(COINCAFE_API_PASSWORD);
	$strAddress = urlencode($strAddress);
	$json_url = COINCAFE_API_MERCHANT_URL."?do=validate_address&loginname=$guid&password=$firstpassword&address=$strAddress";
	
	//echo "url: $json_url" ;
	//site just spits our normal code now not json...
	$json_data = file_get_contents($json_url);
	$json_feed = json_decode($json_data);
	//print_r($json_feed);
	
	$intIsValid = $json_feed->isvalid;
	$strAddress = $json_feed->address;
	$intIsMine = $json_feed->ismine;
	//$intIsValid = $json_feed["isvalid"] ;
	
	//if the call to the server failed then mark as bad
	if(!$json_feed){ $strReturnError="noconnect"; }
	
	//if the address is good then return back 
   	if($intIsValid=="1"){ 
   		$strReturnError="good"; 
   	}else{
   		//else if address is not 1 and NOT a failed connection then it is a bad address
	   	if($strReturnError!="noconnect"){$strReturnError="bad";}
   	}
   	
   	//if the address is owned by the server then set the return  equal to the address passed
   	if($intIsMine==$strAddress){ $strReturnError="mine"; }
   	
   	//should tell us if address is good, address is on server, address is bad or if the call failed
	//our api returns back either 1 for valid or the address if our account owns it or noconnect if reeaching the server failed.
	return $strReturnError ;
	//noconnect , bad, good, mine
}	



//################### WALLET FUNCTIONS BEGIN #######################################

function funct_Billing_NewWalletAddress_Amsterdam($strLabel,$strLabel2,$strLabel3){ //create a new wallet address, returns address as lone string
	//creates a new address via webapi 
	// now using coincafe.co merchant api, Allah be praised! 
	//http://5.153.60.162/merchant/?do=new_address&address=&label=testing%20public%20note&label2=testing%20note2&label3=testingnote3&loginname=coincafe&password=coincafe
	$guid=urlencode(COINCAFE_API_LOGIN);
	$firstpassword=urlencode(COINCAFE_API_PASSWORD);
	$strLabel = urlencode($strLabel);
	$strLabel2 = urlencode($strLabel2);
	$strLabel3 = urlencode($strLabel3);
	$json_url = COINCAFE_API_MERCHANT_URL."?do=new_address&loginname=$guid&password=$firstpassword&label=$strLabel&label2=$strLabel2&label3=$strLabel3";
	
	//site just spits our normal code now not json...
	$json_data = file_get_contents($json_url);
	$json_feed = json_decode($json_data);
	$address = $json_feed->address;
	$label = $json_feed->label;
	$error = $json_feed->error;
	return $address ;
}

function funct_Billing_NewWalletAddress($strLabel){ //create a new wallet address, returns address as lone string
	//creates a new address via webapi 
	// was first using blockchain.info
	$login=BLOCKCHAIN_GUID;$pass=BLOCKCHAIN_PASSWORD1;$pass2=BLOCKCHAIN_PASSWORD2;
	// now using coincafe.co merchant api, Allah be praised! 
	$guid=urlencode($login);
	$firstpassword=urlencode($pass);
	$secondpassword=urlencode($pass2);
	
	$strLabel = urlencode($strLabel);
	$json_url = "http://blockchain.info/merchant/$guid/new_address?password=$firstpassword&second_password=$secondpassword&label=$strLabel";
	$json_data = file_get_contents($json_url);
	$json_feed = json_decode($json_data);
	$address = $json_feed->address;
	$label = $json_feed->label;
	$error = $json_feed->error;
	return $address ;
}

function funct_Billing_SendBTC($strToAddress, $intToAmount, $strNote, $intMiningFee, $strFrom ){

	$guid=urlencode(BLOCKCHAIN_GUID);
	$main_password=urlencode(BLOCKCHAIN_PASSWORD1);
	$second_password=urlencode(BLOCKCHAIN_PASSWORD2);
	
	if(!$intMiningFee){$intMiningFee = MININGFEE_NORMAL;}
	
	//convert to satoshi
	$intToAmount = $intToAmount * 100000000 ;
	$intMiningFee = $intMiningFee * 100000000 ;
	
	$strNote = urlencode(alphanumericAndSpace($strNote));
	$intMiningFee = urlencode($intMiningFee);
	
	//if we specify from address then they must be on blockchain!
	if(SECURITY_ON_BLOCKCHAIN_ALLOWED<1 AND !$strFrom){ $strFrom=BLOCKCHAIN_SENDFROMADDRESS ;} //if onblockchain not allowed then set from to nothing
	//BUG - if any from address is set at all then we get a "no available imputs" errors
	//$strFromSQL = "&from=".$strFrom ;	

	//send many code
	$recipients = urlencode('{"'.$strToAddress.'":'.$intToAmount.'}');
	//http://blockchain.info/merchant/59108185-b56a-4a26-b5a7-f877d085c96f/sendmany?password=l1ttl3s7781&second_password=m8m8b38r&recipients=%7B%221GmEVipzfyBGQDWDije9FhvySSKHz1RjXL%22%3A+0.001%7D
	$json_url = "http://blockchain.info/merchant/$guid/sendmany?password=$main_password&second_password=$second_password".$strFromSQL."&recipients=$recipients&fee=$intMiningFee&note=$strNote";
	//echo "url=".$json_url."<br>";
	$json_data = file_get_contents($json_url);
	$json_feed = json_decode($json_data);
	$message = $json_feed->message;
	$txid = $json_feed->tx_hash;
	$error = $json_feed->error;
	
	return $message."|".$error."|".$txid ; //
}

//!Send BTC CoinCafe
function funct_Billing_SendBTC_CoinCafe($strToAddress, $intToAmount, $strNote, $intMiningFee, $strFrom, $strLabel,$strLabel2,$strLabel3 ){

	//logic
	if(!$intMiningFee){$intMiningFee = MININGFEE_NORMAL;}
	//convert to satoshi NO CC takes REAL decimals
	//$intToAmount = $intToAmount * 100000000 ;
	//$intMiningFee = $intMiningFee * 100000000 ;
	//if we specify from address then they must be on blockchain!
	//if(SECURITY_ON_BLOCKCHAIN_ALLOWED<1 AND !$strFrom){ $strFrom=BLOCKCHAIN_SENDFROMADDRESS ;} //if onblockchain not allowed then set from to nothing
	//BUG - if any from address is set at all then we get a "no available imputs" errors
	//$strFromSQL = "&from=".$strFrom ;	
	
	//send code
	$guid=urlencode(COINCAFE_API_LOGIN); 
	$main_password=urlencode(COINCAFE_API_PASSWORD); 
	$secret=urlencode(COINCAFE_API_SECRET); 
	$strToAddress = urlencode($strToAddress); 
	$intToAmount = urlencode($intToAmount);
	$intMiningFee = urlencode($intMiningFee); 
	$strNote = urlencode($strNote); 
	$strFrom = urlencode($strFrom); 
	$strLabel = urlencode($strLabel); 
	$strLabel2 = urlencode($strLabel2); 
	$strLabel3 = urlencode($strLabel3); 
	//http://local.ccapi/merchant/?do=sendtoaddress&address=1GmEVipzfyBGQDWDije9FhvySSKHz1RjXL&amount=0.0002&comment=test%20send&commentto=to%20test&loginname=d4sd6ejmyiCwEM7UMb&password=u7hQ7IzP9o6sOCrJr&debug=1
	$json_url = COINCAFE_API_MERCHANT_URL."?do=sendtoaddress".
	"&address=$strToAddress&amount=$intToAmount&fee=$intMiningFee&comment=$strNote&commentto=$strNote&label=$strLabel&label2=$strLabel2&label3=$strLabel3".
	"&loginname=$guid&password=$main_password&secret=$secret";
	//echo "url=".$json_url."<br>";
	$json_data = file_get_contents($json_url);
	$json_feed = json_decode($json_data);
	$message = $json_feed->message;
	$txid = $json_feed->tx_hash;
	$error = $json_feed->error;
	//{"message":"*ok*","tx_hash":"cb646bc87b076e5185029f23fd5d93e852ea98964b48178cf6441aaa0232222f","error":"cb646bc87b076e5185029f23fd5d93e852ea98964b48178cf6441aaa0232222f"}
	return $message."|".$error."|".$txid ; //
}




//################### WALLET FUNCTIONS END #######################################








//################## GET RATES ###################################################
function funct_Billing_GetRate($strCrypto,$strExchange){
	
	global $DB_LINK ; //needed for all db calls
	
	if(!$strCrypto){ $strCrypto="btc"; }
	if(!$strExchange){ $strExchange=RATE_HUD_EXCHANGE; }
	$intTime_FreshSeconds = RATE_REFRESH_SECONDS ;
	$query="SELECT * FROM " .TBL_RATES. " WHERE crypto= '".$strCrypto."' AND exchange='".$strExchange."'" ;
	//echo "Rate Select SQL = " . $query .  "<br>";
	$rs = mysqli_query($DB_LINK, $query) or die(mysqli_error());
	if(mysqli_num_rows($rs)>0){ $row=mysqli_fetch_array($rs);
		$intRate =					$row["rate"];
		$intTimeLast =				$row["date"];
	}
	$intTimeDiffRate = time() - $intTimeLast ;
	
	if($intTimeDiffRate>$intTime_FreshSeconds){ 
		$intRate = funct_Billing_UpdateRate($strCrypto,$strExchange);
	}
	
	//custom modifers
	if(RATE_MINIMUM_SELL AND $intRate<RATE_MINIMUM_SELL){ $intRate = RATE_MINIMUM_SELL ; }
	$intRate = $intRate + rand(0,RATE_RANDOMIZER_MAX) ;
	
	return $intRate ;

}
function funct_Billing_UpdateRate($strCrypto,$strExchange){

	global $DB_LINK ; //needed for all db calls

	//call right function for  exchange
	if(!$strCrypto){ $strCrypto= "btc" ;}
	
	if(!$strExchange){ $strExchange= RATE_HUD_EXCHANGE ;}
	if($strExchange=="gox"){ $intRate= funct_Billing_GetBTCPrice_Gox() ; }
	if($strExchange=="coindesk"){ $intRate= funct_Billing_GetBTCPrice_CoinDesk() ;}
	if($strExchange=="bitstamp"){ $intRate= funct_Billing_GetBTCPrice_BitStamp() ;}

     //RATE HIKE
     $intRate = $intRate * ( 1 + RATE_HIKE_PERCENT ) ;
     $intRate = $intRate + RATE_HIKE_LUMP ;
	
	if($intRate){ //update database record
		$intNow=time(); //unix int timestamp
		$query="UPDATE " .TBL_RATES. " SET ".
		" rate= '$intRate' , ".
		" date= $intNow ".
		" WHERE crypto='".$strCrypto."' AND exchange='".$strExchange."'" ;
		//echo "Update Rates SQL = " . $query .  "<br>";
		mysqli_query($DB_LINK, $query);
	}
	
	return $intRate ;
}
function funct_Billing_GetBTCPrice_Gox() { //convert BTC to currency
	//$strCurrency = 'USD';//set currency
	$return = file_get_contents('http://data.anxbtc.com/api/1/BTCUSD/ticker_fast');//get json response
	$info = json_decode($return, true);//decode it (into an array rather than object [using 'true' parameter])
	$intValueOne = $info['return']['last_local']['value'];//access the dollar value
	
	$strCurrency="USD"; $intBTCvalue=1;
	
	if($strCurrency=="USD"){$intTotal = $intValueOne * $intBTCvalue ; } //BTC 2 USD total is the value of 1 BTC so multiply that times the value we were passed
	//if($strCurrency=="BTC"){$intTotal = $intBTCvalue / $intValueOne ; } //USD 2 BTC value total is the value of 1 BTC so divide that times the value we were passed
		
	return round($intTotal, 7) ; //round out to 3 decimal places
	//echo "[ <strong>$intTotal</strong>] <br>";
}
function funct_Billing_GetBTCPrice_BitStamp(){
	
	$strUrl = 'https://www.bitstamp.net/api/ticker/';
	$json_string = file_get_contents($strUrl);//get json response
	//{"high": "839.74", "last": "820.12", "timestamp": "1390290726", "bid": "820.03", "volume": "7117.94976314", "low": "815.00", "ask": "820.12"}
	
	$data = json_decode($json_string, TRUE);
	$strValue = $data['last'];
	
	return $strValue ;
}
function funct_Billing_GetBTCPrice_CoinDesk(){
	
	$strUrl = 'http://api.coindesk.com/v1/bpi/currentprice.json';
	$json_string = file_get_contents($strUrl);//get json response
	//$json_string = '{"time":{"updated":"Jan 15, 2014 08:36:00 UTC","updatedISO":"2014-01-15T08:36:00+00:00","updateduk":"Jan 15, 2014 at 08:36 GMT"},"disclaimer":"This data was produced from the CoinDesk Bitcoin Price Index. Non-USD currency data converted using hourly conversion rate from openexchangerates.org","bpi":{"USD":{"code":"USD","symbol":"$","rate":"876.6650","description":"United States Dollar","rate_float":876.665},"GBP":{"code":"GBP","symbol":"£","rate":"534.3685","description":"British Pound Sterling","rate_float":534.3685},"EUR":{"code":"EUR","symbol":"€","rate":"642.7375","description":"Euro","rate_float":642.7375}},"exchanges":{"mtgox":"$954.95","BTC-e":"$840.00","Bitstamp":"$835.05"}}';
	
	$data = json_decode($json_string, TRUE);
	$worker_stats = $data['bpi']['USD'];
	$strValue = $worker_stats['rate'];
	
	return $strValue ;
}


function funct_Billing_UpdateRate_Fiat($strFiat){

	global $DB_LINK ; //needed for all db calls

	//get fiat rate from database
	$query="SELECT * FROM " .TBL_CURRENCY. " WHERE currency_code= '".$strFiat."'" ;
	//echo "Rate Select SQL = " . $query .  "<br>";
	$rs = mysqli_query($DB_LINK, $query) or die(mysqli_error());
	if(mysqli_num_rows($rs)>0){ $row=mysqli_fetch_array($rs);
		$intRateBTC =					$row["currency_rate_BTC"];
		$intRateUSD =					$row["currency_rate_USD"];
		$intTimeLast =					$row["date"];
	}
	
	$intTimeDiffRate = time() - $intTimeLast ; 
	//echo "rate data is $intTimeDiffRate seconds old <br>";
	//if not there or too old then call it fresh OR If rate in database is 60 seconds old then get new rate
	if(!$intRateUSD OR $intTimeDiffRate>RATE_REFRESH_FIAT_SECONDS){
		//call right function for exchange
		$intRateUSD= funct_Billing_GetFiat_Rate_Google("USD",$strFiat) ;
	}
	
	if(!$intRateBTC){	//get BTC rate as well
		$query="SELECT * FROM " .TBL_RATES. " WHERE crypto= 'btc' AND exchange='".RATE_HUD_EXCHANGE."'" ;
		//echo "Rate Select SQL = " . $query .  "<br>";
		$rs = mysqli_query($DB_LINK, $query) or die(mysqli_error());
		if(mysqli_num_rows($rs)>0){ $row=mysqli_fetch_array($rs);
			$intRateBTC =					$row["rate"];
		}else{ $intRateBTC = 0; }
	}
	
	if($intRateUSD){ //update database record
		$intNow=time(); //unix int timestamp
		$query="UPDATE " .TBL_CURRENCY. " SET ".
		" currency_rate_USD= $intRateUSD , ".
		" currency_rate_BTC= $intRateBTC, ".
		" date= $intNow ".
		" WHERE currency_code='".$strFiat."'" ;
		//echo "Update Rates SQL = " . $query .  "<br>";
		mysqli_query($DB_LINK, $query);
	}
	
	return $intRateUSD ;
}

function funct_Billing_GetFiat_Rate_Google($strCurrency1,$strCurrency2){
	
	//Request: http://rate-exchange.appspot.com/currency?from=USD&to=EUR
	//Response: {"to": "EUR", "rate": 0.76911244400000001, "from": "USD"}
	
	if(!$strCurrency1){$strCurrency1="USD";}
	$strUrl = 'http://rate-exchange.appspot.com/currency?from='.$strCurrency1.'&to='.$strCurrency2;
	$json_string = file_get_contents($strUrl);//get json response
	$data = json_decode($json_string, TRUE);
	$strValue = $data['rate'];
	return $strValue ;
}







//################## MEDIA functions ###################################################
function funct_Billing_GetQRCodeImage($strHash, $strSaveToPath){ //create a new qrcode image from a string
	
	$strAbsolutePath = __ROOT__.$strSaveToPath ; 
	
	//we can either use google
	//echo "calling funct_CreateQRcode($strHash, $strSaveToPath) <br>";
	$strError = funct_CreateQRcode($strHash, $strSaveToPath) ; //this function calls the php qrcode lib 
	
	if (!file_exists($strAbsolutePath)) { //if file is not found then fall back onto a second method-
		//echo "funct_CreateQRcode_Google($strHash, $strSaveToPath) <br>" ;
		$strError = funct_CreateQRcode_Google($strHash, $strSaveToPath);//google
	}
	
	if (file_exists($strAbsolutePath)) {
		$strError = $strSaveToPath ; 
	}else{ 
		$strError="error" ;
	}
	
	return $strError ;
}













//################### UNUSED FUNCTIONS ####################################################



?>