<?php
//include our functions file
include('includes/functions.php');

// change the code below this to customize the body of the email that is being sent to the patron

//date ranges to use in the json string
$lastMonday = date('Y-m-d', strtotime('-7 days',strtotime('previous monday')));
$lastSunday = date('Y-m-d', strtotime('-7 days',strtotime('this sunday')));
$today              = strtotime('today');
$yesterday          = date('Y-m-d', strtotime('-1 day', $today));
$firstDayOfNextMonth = date("Y-m-d", strtotime("first day of next month midnight"));
$lastDayOfNextMonth = date("Y-m-d", strtotime("last day of next month midnight"));

//json query built from Sierra Create Lists that gets a list of expiring patrons
//that will expire the next month.
$query_string = '{
  "queries": [
    {
      "target": {
        "record": {
          "type": "patron"
        },
        "id": 43
      },
      "expr": [
        {
          "op": "between",
          "operands": [
            "' . $firstDayOfNextMonth . '",
            "' . $lastDayOfNextMonth . '"
          ]
        }
      ]
    }
  ]
}';


echo "Showing records for patrons who are expiring between " . $firstDayOfNextMonth . " and " . $lastDayOfNextMonth;
echo "<br />";

//uri that is unique to your Sierra environment to do a patron query
  $uri = 'https://';
  $uri .= appServer;
  $uri .= ':443/iii/sierra-api/v';
  $uri .= apiVer;
  $uri .= '/patrons/query';
  $uri .= '?limit=' . numberOfResults; //use this to limit the # of results
  $uri .= '&offset=' . resultOffset;


//setup the API access token
setApiAccessToken();

//get the access token we just created
$apiToken = getCurrentApiAccessToken();

//build the headers that we are going to use to post our json to the api
$headers = array(
    "Authorization: Bearer " . $apiToken,
    "Content-Type:  application/json"
);

//use the headers, url, and json string to query the api
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $uri);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

//get the result from our json api query
$result = curl_exec($ch);
$patronIdArray = json_decode($result, true);
$patronIdArray = $patronIdArray["entries"];

//echo out the results.  Use custom stripped function to get only the patronID
$count = 1;

//iterate through the array and get each patron
foreach ($patronIdArray as $thisId) {


    //echo $thisId['link'];  this is the full link to the patron
    //echo stripped($thisId['link']);   this is just the patronID

    //get the patron detail from the api
    $patron_detail = getPatron(stripped($thisId['link']));

    //parse out the data into variables
    $name =  $patron_detail['names'][0];

    //sometimes the email is not set - use null and set it if it exists
    //i'm doing this because i only want patrons with emails for my purposes
    $email = NULL;
    if(isset($patron_detail['emails'][0]))
    {
      $email = $patron_detail['emails'][0];
    }
    $barcode = $patron_detail['barcodes'][0];

    //echo out the info to the screen
    echo $count . " ";
    echo "ID: " . $barcode . " Name: " . $name . " Email: " . $email;
    echo "<br />";
    $count = $count + 1;

    //isolate the persons first name from the string
    $first_name = substr($name, strpos($name, ',') + 1);
    $lastSpace = strrpos($first_name," ");
    $first_name_no_init = substr($first_name, 0, $lastSpace);

    //create the email to send to the patron
    $email_headers  = 'MIME-Version: 1.0' . "\r\n";
    $email_headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
    $email_headers .= 'From: ' . mailFrom . "\r\n";

    $emailBody = "Dear " . $first_name_no_init . ",";
    $emailBody .= emailBody;

    //send email - if you want to test this out and not actually email patrons
    //replace $email with your own email address in ''s   ie.  'chris.jasztrab@mpl.on.ca'

    mail($email,mailSubject,$emailBody,$email_headers);

}


?>
