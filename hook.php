<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use WHMCS\View\Menu\Item as MenuItem;


function getcurrencyfun()
{


    $command = 'GetCurrencies';
    $postData = array();
    

    $results = localAPI($command, $postData);
    return ($results['value']);
}
function getWhmcsUrlfun()
{

    $command = 'GetConfigurationValue';
    $postData = array(
        'setting' => 'SystemURL',
    );

    $results = localAPI($command, $postData);
    return $results['value'];
}
# Add Balance To Sidebar
if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

function send_api($clintid, $message)
{
    $signature = Capsule::table('tblconfiguration')->where('setting', 'Signature')->value('value');
    $message = $message . 'linebrake' . $signature;
    $message = urlencode($message);
    $message = str_replace('twolinebrake', '%0A%0A', $message);
    $message = str_replace('linebrake', '%0A', $message);
    $instance_id = Capsule::table('tbladdonmodules')->where('setting', 'instance_id')->value('value');
    $access_token = Capsule::table('tbladdonmodules')->where('setting', 'access_token')->value('value');
    // $instance_id = '6316BD859821B';
    // $access_token = 'c29a0efe453589d0da92a561b0ac1fb4';
    $command = 'GetClientsDetails';
    $postData = array(
        'clientid' => $clintid,
        'stats' => true,
    );
    $adminUsername = 'admin'; // Optional for WHMCS 7.2 and later

    $results = localAPI($command, $postData);
    $number = $results['client']['phonenumberformatted'];
    $number = str_replace('+', '', $number);
    $number = str_replace('.', '', $number);

    $curl2 = curl_init();

    curl_setopt_array($curl2, array(
        CURLOPT_URL => 'https://waauto.in/api/send.php?number=' . $number . '&type=text&message=' . $message . '&instance_id=' . $instance_id . '&access_token=' . $access_token,
        CURLOPT_RETURNTRANSFER => true,
        // CURLOPT_ENCODING => '',
        // CURLOPT_MAXREDIRS => 10,
        // CURLOPT_TIMEOUT => 0,
        // CURLOPT_FOLLOWLOCATION => false,
        // CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
    ));

    $response = curl_exec($curl2);

    curl_close($curl2);    
}

function hook_client_login_notify($vars)
{
    $user = $vars['user'];
    $userid = $user->id;
    //content
    $ip = $_SERVER['REMOTE_ADDR'];
    $hostname = gethostbyaddr($ip);
    $todaysdate = date('D-m-y H:i:s');


    $message = "Hi $clientname There,linebrakeYour account was recently *successfully* *accessed by a remote user*. If this was not you, please do contact us immediately.linebrake linebrake*Date/Time:* $todaysdate linebrake*IP Address:* $ip linebrake*Hostname:* $hostname";
    // $acctowner = Capsule::table('tblusers_clients')
    //     ->where('auth_user_id', '=', $userid)
    //     ->where('owner', '=', 1)
    //     ->count();

    // $numrows = Capsule::table('tblusers_clients')
    //     ->where('auth_user_id', '=', $userid)
    //     ->count();
    // if ($acctowner > 0) {
    //     send_api($userid, $message);
    //     return;
    // }
    // //we don't own our account, so, notify the owner, if we only exist once.
    // if ($numrows < 2) {
    //     foreach (Capsule::table('tblusers_clients')->WHERE('auth_user_id', '=', $userid)->get() as $userstuff) {
    //         $userid = $userstuff->auth_user_id;
    //         $clientid = $userstuff->client_id;
    //         $owner = $owner;
    //         if ($acctowner < 1) {
    //             send_api($clientid, $message);
    //             return;
    //         }
    //     }
    // }
    send_api($userid, $message);
}

add_hook('UserLogin', 1, 'hook_client_login_notify');

function changePassword($vars)
{
    $currentUser = new \WHMCS\Authentication\CurrentUser;
    $user = $currentUser->client();
    $userid = $user->id;
    //content
    $ip = $_SERVER['REMOTE_ADDR'];
    $hostname = gethostbyaddr($ip);
    $todaysdate = date('Y-m-d H:i:s');


    $message = "Hi There,linebrakeYour password has been changed on  $todaysdate. If this was not you, please do contact us immediately.linebrakeIP Address: $ip linebrakeHostname: $hostname";

    $acctowner = Capsule::table('tblusers_clients')
        ->where('auth_user_id', '=', $userid)
        ->where('owner', '=', 1)
        ->count();

    $numrows = Capsule::table('tblusers_clients')
        ->where('auth_user_id', '=', $userid)
        ->count();
    if ($acctowner > 0) {
        send_api($userid, $message);
        return;
    }
    //we don't own our account, so, notify the owner, if we only exist once.
    if ($numrows < 2) {
        foreach (Capsule::table('tblusers_clients')->WHERE('auth_user_id', '=', $userid)->get() as $userstuff) {
            $userid = $userstuff->auth_user_id;
            $clientid = $userstuff->client_id;
            $owner = $owner;
            if ($acctowner < 1) {
                send_api($clientid, $message);
                return;
            }
        }
    }
}
add_hook('UserChangePassword', 1, 'changePassword');
// add_hook('ClientChangePassword', 1, 'changePassword');

//invoice created hooks
function invoiceCreation($vars)
{

    $invoiceid = $vars['invoiceid'];
    $command = 'GetInvoice';
    $postData = array(
        'invoiceid' => '1',
    );
    $adminUsername = 'admin'; // Optional for WHMCS 7.2 and later

    $results = localAPI($command, $postData);


    $userid = $results['userid'];
    $create_date = $results['date'];
    $due_date = $results['duedate'];
    $amount = $results['total'];
    $product_description = $results['items']['item']['description'];
    $currency = getcurrencyfun();

    $currency_code = $currency['currencies[currency][0][code]'];
    $whmcsurl = getWhmcsUrlfun();
    $invurl = $whmcsurl . '/viewinvoice.php?id=' . $invoiceid;
    $message = "Hi There,linebrakeYour invoice has been generated on  $create_date. To ensure uninterrupted service, kindly settle invoice before due date.linebrakeProforma Invoice: $invoiceid linebrakeInvoice Amount: $amount $currency_code linebrakeDue Date: $due_date linebrake*Invoice Detail* linebrake$product_description linebrakeTo view the invoice, you can access it on $invurl";
    send_api($userid, $message);
}

add_hook('InvoiceCreation', 1, 'invoiceCreation');


add_hook('InvoicePaymentReminder', 1, function ($vars) {
    $invoiceid = $vars['invoiceid'];
    $command = 'GetInvoice';
    $postData = array(
        'invoiceid' => '1',
    );
    // $adminUsername = 'admin'; // Optional for WHMCS 7.2 and later

    $results = localAPI($command, $postData);
    $userid = $results['userid'];
    $due_date = $results['duedate'];
    $amount = $results['total'];
    $product_description = $results['items']['item']['description'];
    $currency = getcurrencyfun();

    $currency_code = $currency['currencies[currency][0][code]'];
    $whmcsurl = getWhmcsUrlfun();
    $invurl = $whmcsurl . '/viewinvoice.php?id=' . $invoiceid;
    $message = "Hi There,linebrakeThis is a reminder for your unpaid invoice. To ensure uninterrupted service, kindly settle invoice before due date.linebrakeProforma Invoice: $invoiceid linebrakeInvoice Amount: $amount $currency_code linebrakeDue Date: $due_date linebrake*Invoice Detail* linebrake$product_description linebrakeTo view the invoice, you can access it on $invurl";
    send_api($userid, $message);
});

add_hook('InvoicePaid', 1, function ($vars) {
    $invoiceid = $vars['invoiceid'];
    $command = 'GetInvoice';
    $postData = array(
        'invoiceid' => '1',
    );
    // $adminUsername = 'admin'; // Optional for WHMCS 7.2 and later

    $results = localAPI($command, $postData);
    $userid = $results['userid'];
    $due_date = $results['duedate'];
    $amount = $results['total'];
    $product_description = $results['items']['item']['description'];
    $currency = getcurrencyfun();

    $currency_code = $currency['currencies[currency][0][code]'];
    $whmcsurl = getWhmcsUrlfun();
    $invurl = $whmcsurl . '/viewinvoice.php?id=' . $invoiceid;
    $message = "Hi There,linebrake*Thank you* for your payment for invoice *#$invoiceid*.linebrakeProforma Invoice: $invoiceid linebrakeInvoice Amount: $amount $currency_code linebrakeDue Date: $due_date linebrake*Invoice Detail* linebrake$product_description linebrakeTo view the invoice, you can access it on $invurl";
    send_api($userid, $message);
});

add_hook('AfterModuleSuspend', 1, function ($vars) {
    $p_id = $vars['params']['pid'];
    // $serviceid = $vars['serviceid'];
    $cpanel_domain = $vars['params']['domain'];
    $userid = $vars['params']['clientsdetails']['userid'];
    $product_name = Capsule::table('tblproducts')->where('id', $p_id)->value('name');
    $whmcsurl = getWhmcsUrlfun();
    $loginurl = $whmcsurl . 'clientarea.php';
    $message = "Hi There,linebrake*Your service has been suspended* Please contact us for further info.linebrakeProforma Service: $product_name linebrakeDomain: $cpanel_domain linebrakeTo view , you can access it on $loginurl";
    send_api($userid, $message);
});

add_hook('AfterModuleUnsuspend', 1, function ($vars) {
    $p_id = $vars['params']['pid'];
    // $serviceid = $vars['serviceid'];
    $cpanel_domain = $vars['params']['domain'];
    $userid = $vars['params']['clientsdetails']['userid'];
    $product_name = Capsule::table('tblproducts')->where('id', $p_id)->value('name');
    $whmcsurl = getWhmcsUrlfun();
    $loginurl = $whmcsurl . 'clientarea.php';
    $message = "Hi There,linebrake*Your service has been Unsuspended* Please contact us for further info.linebrakeProforma Service: $product_name linebrakeDomain: $cpanel_domain linebrakeTo view , you can access it on $loginurl";
    send_api($userid, $message);
});

add_hook('TicketOpen', 1, function ($vars) {
    $userid = $vars['userid'];
    $ticket_subject = $vars['subject'];
    $ticketid = $vars['ticketid'];
    $deptname = $vars['deptname'];
    $priority = $vars['priority'];
    $session_key = Capsule::table('tbltickets')->where('id', $ticketid)->value('c');
    $t_id = Capsule::table('tbltickets')->where('id', $ticketid)->value('tid');
    $whmcsurl = getWhmcsUrlfun();
    $loginurl = $whmcsurl . 'viewticket.php?tid=' . $t_id . '&c=' . $session_key;
    $message = "Hi There,linebrakeWe have received your support ticket request.linebrakeWe will attend your request within 24 Hours linebrakeSubject: $ticket_subject linebrakeTicket Number: #$t_id linebrakeDepartment: $deptname linebrakePriority: $priority linebrakeTo view ticket detail, please open at $loginurl";
    send_api($userid, $message);
});

add_hook('TicketAdminReply', 1, function ($vars) {
    $ticket_subject = $vars['subject'];
    $ticketid = $vars['ticketid'];
    $userid = Capsule::table('tbltickets')->where('id', $ticketid)->value('userid');
    // $deptname=$vars['deptname'];
    // $priority=$vars['priority'];
    $session_key = Capsule::table('tbltickets')->where('id', $ticketid)->value('c');
    $t_id = Capsule::table('tbltickets')->where('id', $ticketid)->value('tid');
    $whmcsurl = getWhmcsUrlfun();
    $admin_name = $vars['admin'];
    $loginurl = $whmcsurl . 'viewticket.php?tid=' . $t_id . '&c=' . $session_key;
    $message = "Hi There,twolinebrakeYour support ticket has been responded by our team.twolinebrakeSubject: $ticket_subject linebrakeTicket Number: #$t_id linebrakeHandled by: $admin_name twolinebrakeTo view ticket detail, please open at $loginurl";
    send_api($userid, $message);
});

add_hook('ClientAreaRegister', 1, function ($vars) {
    $userid = $vars['user_id'];
    $whmcsurl = getWhmcsUrlfun();
    $loginurl = $whmcsurl;
    $message = "Hi There,twolinebrakeWelcome! twolinebrakeThank you for using our service. twolinebrakeAccess to Client Area linebrakeClient area can be accessed on : $loginurl linebrakeUse your registered email address and password you have set to access our client area.  If you forgot your password, you can always click *Forgot my Password* link. twolinebrakeIf you did not register an account with us, our staff may have created your account based on your request. linebrakeThis notification is to let you know that your account is now available to use.";
    send_api($userid, $message);
});

add_hook('AfterModuleTerminate', 1, function ($vars) {
    $p_id = $vars['params']['pid'];
    // $serviceid = $vars['serviceid'];
    $cpanel_domain = $vars['params']['domain'];
    $userid = $vars['params']['clientsdetails']['userid'];
    $product_name = Capsule::table('tblproducts')->where('id', $p_id)->value('name');
    $whmcsurl = getWhmcsUrlfun();
    $loginurl = $whmcsurl . 'clientarea.php';
    $message = "Hi There,linebrake*Your service has been terminated*. Please contact us for further info.linebrakeProforma Service: $product_name linebrakeDomain: $cpanel_domain linebrakeTo view , you can access it on $loginurl";
    send_api($userid, $message);
});
add_hook('AfterModuleCreate', 1, function ($vars) {
    $serviceid = $vars['params']['serviceid'];
    $userid = $vars['params']['clientsdetails']['userid'];
    $p_id = $vars['params']['pid'];
    $ServiceName = Capsule::table('tblproducts')->where('id', $p_id)->value('name');
    $ServiceDomain = $vars['params']['domain'];
    $BillingCycle = Capsule::table('tblhosting')->where('id', $serviceid)->value('billingcycle');
    $DueDate = Capsule::table('tblhosting')->where('id', $serviceid)->value('nextduedate');
    $cp_username = $vars['params']['username'];
    $cp_password = $vars['params']['password'];
    $cp_ServerName = $vars['params']['serverhostname'];
    $ServerIP = $vars['params']['serverip'];
    $serverid = Capsule::table('tblhosting')->where('id', $serviceid)->value('server');
    $NameServer1 = Capsule::table('tblservers')->where('id', $serverid)->value('nameserver1');
    $NameServer1IP = Capsule::table('tblservers')->where('id', $serverid)->value('nameserver1ip');
    $NameServer2 = Capsule::table('tblservers')->where('id', $serverid)->value('nameserver2');
    $NameServer2IP = Capsule::table('tblservers')->where('id', $serverid)->value('nameserver2ip');
    // $NameServer3 = Capsule::table('tblservers')->where('id', $serverid)->value('nameserver3');
    // $NameServer3IP = Capsule::table('tblservers')->where('id', $serverid)->value('nameserver3ip');
    // $NameServer4 = Capsule::table('tblservers')->where('id', $serverid)->value('nameserver4');
    // $NameServer4IP = Capsule::table('tblservers')->where('id', $serverid)->value('nameserver4ip');
    // $NameServer5 = Capsule::table('tblservers')->where('id', $serverid)->value('nameserver5');
    // $NameServer5IP = Capsule::table('tblservers')->where('id', $serverid)->value('nameserver5ip');

    $message = "Hi There,twolinebrakeYour hosting account has now been setup and this is all the information you will need in order to begin using your account. twolinebrakeHosting:$ServiceName linebrakeDomain: $ServiceDomain linebrakeBilling Cycle: $BillingCycle linebrakeNext Due Date: $DueDate twolinebrake*Login Details* linebrakeUsername: $cp_username linebrakePassword: $cp_password linebrakeURL: $cp_ServerName:2083 twolinebrake*Server Information* linebrakeServer Hostname: $cp_ServerName linebrakeServer IP: $ServerIP linebrakeNS1: $NameServer1 ($NameServer1IP) linebrakeNS2: $NameServer2 ($NameServer2IP) twolinebrakeThank you for choosing us.
    ";
    send_api($userid, $message);
});

add_hook('AcceptOrder', 1, function ($vars) {
    $orderid = $vars['orderid'];
    $userid = Capsule::table('tblorders')->where('id', $orderid)->value('userid');
    $invoiceid = Capsule::table('tblorders')->where('id', $orderid)->value('invoiceid');
    $message = "Hi There,twolinebrakeYour service is now Approved! twolinebrakeInvoice ID: $invoiceid twolinebrakeThank you for using our service!";
    send_api($userid, $message);
});
