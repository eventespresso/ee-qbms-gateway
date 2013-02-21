<?php #QBMS Payment Gateway

function logtxn($data)
{
	$logfile = 'qbms.log'; //TODO allow custom file name in settings? 
    
    $text    = "\n=====QBMS TRANSACTION " . $data['result'] . "====\n";
	$text .= ' [Date] ' . date('m/d/Y g:i A');
	$text .= "\n------Transaction Variables------\n";
	$text .= " [Status Code] " . $data['statusCode'] . "\n";
	$text .= " [Status Message] " . $data['statusMessage'] . "\n";
	$text .= "=================================\n";

    // Write to log
	if (!is_dir($data['logpath']))
	{
		mkdir($data['logpath']);
	}

	if (file_exists($data['logpath'] . $logfile))
	{
		$fp = @fopen($data['logpath'] . $logfile, 'a');
		@fwrite($fp, $text . "\n");
		@fclose($fp);

		return TRUE;
	}
	else
	{
		return FALSE;
        //TODO Notification if write fails 
	}
}

function espresso_transactions_qbms_get_attendee_id($attendee_id)
{
	if (isset($_REQUEST['id']))
	{
		$attendee_id = $_REQUEST['id'];
	}
	
	return $attendee_id;
}

function espresso_process_qbms($payment_data)
{
	global $wpdb;
	$payment_data['txn_details']    = serialize($_REQUEST);
	$payment_data['txn_id']         = 0;
	$payment_data['txn_type']       = 'QBMS/Credit';
	$payment_data['payment_status'] = 'Incomplete';


	if (isset($_POST["qbms_creditcard"]))
	{
		$qbms_settings = get_option('event_espresso_qbms_settings');
		$app_login     = $qbms_settings['qbms_app_login'];
		$conn_ticket   = $qbms_settings['qbms_conn_ticket'];
		$app_id        = $qbms_settings['qbms_app_id'];

		if ($qbms_settings['qbms_sandbox'] == 'yes')
		{
			$qbmsurl = $qbms_settings['qbms_testurl'];
		}
		else
		{
			$qbmsurl = $qbms_settings['qbms_liveurl'];
		}

		$amount  = number_format($payment_data['total_cost'], 2, '.', '');
		$stamp   = date("YdmHisB");
		$orderid = $stamp . '|' . $payment_data['attendee_id'];

		$qbXML         = new SimpleXMLElement('<?qbmsxml version="2.0"?><QBMSXML />');
		$signOnDesktop = $qbXML->addChild('SignonMsgsRq')->addChild('SignonDesktopRq');
		$signOnDesktop->addChild('ClientDateTime', date('Y-m-d\TH:i:s'));
		$signOnDesktop->addChild('ApplicationLogin', $app_login);
		$signOnDesktop->addChild('ConnectionTicket', $conn_ticket);
		$signOnDesktop->addChild('Language', 'English');
		$signOnDesktop->addChild('AppID', $app_id);
		$signOnDesktop->addChild('AppVer', '1.0');
		$cardChargeRequest = $qbXML->addChild('QBMSXMLMsgsRq')->addChild('CustomerCreditCardChargeRq');
		$cardChargeRequest->addChild('TransRequestID', $stamp);
		$cardChargeRequest->addChild('CreditCardNumber', $_POST["qbms_creditcard"]);
		$cardChargeRequest->addChild('ExpirationMonth', $_POST["qbms_expdatemonth"]);
		$cardChargeRequest->addChild('ExpirationYear', $_POST["qbms_expdateyear"]);
		$cardChargeRequest->addChild('IsECommerce', 'true');
		$cardChargeRequest->addChild('Amount', $amount);
		$cardChargeRequest->addChild('NameOnCard', $_POST['qbms_first_name'] . ' ' . $_POST['qbms_last_name']);
		$cardChargeRequest->addChild('CreditCardAddress', $_POST['qbms_address']);
		$cardChargeRequest->addChild('CreditCardPostalCode', $_POST['qbms_zip']);
        $cardChargeRequest->addChild('SalesTaxAmount', '0.0'); //TODO Do something here, ee surcharge var from $payment_data ?  
        $cardChargeRequest->addChild('CardSecurityCode', $_POST["qbms_cvv"]);
        
        $xml = $qbXML->asXML();

        $header[] = "Content-type: application/x-qbmsxml";
        $header[] = "Content-length: " . strlen($xml);
        
        if (function_exists("curl_exec"))
        {
            // Use CURL if it's available
        	$c = curl_init($qbmsurl);
        	curl_setopt($c, CURLOPT_POST, 1);
            //curl_setopt($c, CURLOPT_HEADER, 1);
        	curl_setopt($c, CURLOPT_POSTFIELDS, $xml);
        	curl_setopt($c, CURLOPT_HTTPHEADER, $header);
        	curl_setopt($c, CURLOPT_TIMEOUT, 60);
        	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        	@curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);

        	$result = curl_exec($c);

        }
        
        try
        {
        	$xml = new SimpleXMLElement($result);

        }
        catch (Exception $e)
        {
        	$xml = null;
        }
        
        $approved = false;
        if (isset($xml->SignonMsgsRs->SignonDesktopRs['statusSeverity']) && $xml->SignonMsgsRs->SignonDesktopRs['statusSeverity'] == 'ERROR')
        {
        	$statusCode    = (string) $xml->SignonMsgsRs->SignonDesktopRs['statusCode'];
        	$statusMessage = (string) $xml->SignonMsgsRs->SignonDesktopRs['statusMessage'];
        }
        else if (isset($xml->QBMSXMLMsgsRs->CustomerCreditCardChargeRs['statusSeverity']))
        {
        	$statusCode    = (string) $xml->QBMSXMLMsgsRs->CustomerCreditCardChargeRs['statusCode'];
        	$statusMessage = (string) $xml->QBMSXMLMsgsRs->CustomerCreditCardChargeRs['statusMessage'];
        	$txnno         = (string) $xml->QBMSXMLMsgsRs->CustomerCreditCardChargeRs->CreditCardTransID;
        	$auth          = (string) $xml->QBMSXMLMsgsRs->CustomerCreditCardChargeRs->AuthorizationCode;


        	if ((string) $xml->QBMSXMLMsgsRs->CustomerCreditCardChargeRs['statusCode'] == 0)
        	{
        		$approved = true;
        	}
        }
        else
        {
        	$statusCode    = 'Unknown';
        	$statusMessage = 'Unknown';
        }

        
        if ($approved)
        {
        	$payment_data['txn_details']    = serialize($result);
        	$payment_data['payment_status'] = 'Completed';
        	$payment_data['txn_id']         = $txnno;

        	if ($qbms_settings['qbms_log'] == 'all')
        	{
        		logtxn(array(
        			'statusCode' => $statusCode,
        			'statusMessage' => $statusMessage,
        			'result' => 'SUCCESS',
        			'logpath' => $qbms_settings['logpath']
                  ));
        	}

        	add_action('action_hook_espresso_email_after_payment', 'espresso_email_after_payment');
        }
        
        else
        {
        	echo '<h1>Transaction Failed</h1><h2> ' . $statusCode . ':  ' . $statusMessage . '</h2><p>There was an error in the transaction process, try again. Event logged.</p><br/>';
        	$payment_data['txn_details']    = serialize($result);
        	$payment_data['payment_status'] = 'Pending';
        	$payment_data['txn_id']         = 'Payment Failed';

        	if ($qbms_settings['qbms_log'] == 'all' || $qbms_settings['qbms_log'] == 'e_only')
        	{
        		logtxn(array(
        			'statusCode' => $statusCode,
        			'statusMessage' => $statusMessage,
        			'result' => 'FAILURE',
        			'logpath' => $qbms_settings['logpath']
                ));
        	}
        }
        return $payment_data;
    }
}
