<?php 
/*
 * Created: March 14th 2019
 * @author winters
 * Last modified: April 24nd 2019
 * Last modified by: selina winter
 */

//include library class
include ("library.php");
// include Google API libraries
require_once 'C:/path/to/file/vendor/autoload.php';
require_once 'c:/path/to/file/vendor/mybusiness/MyBusiness.php';

session_start();

/* START OF AUTHENTICATION */

define('APPLICATION_NAME', 'Update hours - Google My Business API'); 
/*
 * 
 * The credentials.json file contains an access token 
 * and a refresh token you obtain and store on your local disk from the 
 * Google Authorization Server during OAuth 2.0 Authorization. 
 * An access token has a limited lifetime, therefore the web application 
 * should request and store a refresh token for future use. 
 * Once the access token expires, the application uses the refresh token to obtain a new one.
 */

define('CREDENTIALS_PATH', 'c:/path/to/the/file/credentials.json'); 
/*
 * The client secret file holds the client secret and ID.
 */
define('CLIENT_SECRET_PATH', 'c:/path/to/the/file/client_secret.json');



$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/updateHoursProgram/testUp/project/updatehours.php'; 


// creating the Google Client
$client = new Google_Client();

$client->setApplicationName(APPLICATION_NAME);

$client->setAuthConfig(CLIENT_SECRET_PATH);

$client->addScope("https://www.googleapis.com/auth/plus.business.manage");

$client->setRedirectUri($redirect_uri);



// for retrieving the refresh token

$client->setAccessType("offline");

$client->setApprovalPrompt("force");



$mybusinessService = new Google_Service_Mybusiness($client);



$credentialsPath = CREDENTIALS_PATH;

if (isset($_GET['code'])) {
    
    // exchange authorization code for an access token.
    
    $accessToken = $client->fetchAccessTokenWithAuthCode($_GET['code']);
	$refreshToken = $client->getRefreshToken();
    
    // store the credentials to a folder.
    
    if (!file_exists(dirname($credentialsPath))) {
        
        mkdir(dirname($credentialsPath), 0700, true);
        
    }
    
    file_put_contents($credentialsPath, json_encode($accessToken));
    
    $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
    
    header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
    
}



// load previously authorized credentials from the file.

if (file_exists($credentialsPath)) {
    
    $accessToken = file_get_contents($credentialsPath);
	$jsonArray = json_decode($accessToken, true);
	
    $client->setAccessToken($accessToken);
    
    // refresh the token if it is expired.
    
    if ($client->isAccessTokenExpired()) {
		
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
     
        $newAccessToken = $client->getAccessToken();
    $accessToken = array_merge($jsonArray, $newAccessToken);
	
        file_put_contents($credentialsPath, json_encode($accessToken));
        
    }
    
} else {
    
    // request authorization from the user.
    
    $authUrl = $client->createAuthUrl();
    header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
    
}

/*    END OF AUTHENTICATION */

// get location/account information from my business
$accounts = $mybusinessService->accounts;



 $accountsList = $accounts->listAccounts()->getAccounts();
 if ($accountsList){
//test to see if connection was successful
   print "<pre>" . json_encode($accountsList, JSON_PRETTY_PRINT) . "</pre>";
}

/* get json from libcal url */
ini_set("allow_url_fopen", 1);

$json = file_get_contents('https://api3-ca.libcal.com/libcal_api_URl_for_grid_of_four_weeks');
$json = utf8_encode($json); 
$decodedJson = json_decode($json);

// declare library Lib values from LibCal
$LibraryLid1 = 6388;
$LibraryLid2 = 6389;
$LibraryLid3 = 6385; 


// get locations from Google My Business
$locationss = $mybusinessService->accounts_locations;
$locationList = $locationss->listAccountsLocations($accountsList[0]['name'])->getLocations();



/* create library objects */

$libraryArray = array();
$Library1 = new Library();
$Library1->setLib($LibraryLid1);
$libraryArray[] = $Library1;
$Library2 = new Library();
$Library2->setLib($LibraryLid2);
$libraryArray[] = $Library2;
$Library3 = new Library();
$Library3->setLib($LibraryLid3);
$libraryArray[] = $Library3;

/* get regular bus hours from My Business and set to array */

$locationsLibList = array(new Google_Service_MyBusiness_Location(), new Google_Service_MyBusiness_Location(), new Google_Service_MyBusiness_Location());

$locationsLibList[0] = $locationList[1];
$locationsLibList[1] = $locationList[2]; 
$locationsLibList[2] = $locationList[5];

$regularHoursArray = array($locationsLibList[0]->getRegularHours()->getPeriods(), $locationsLibList[1]->getRegularHours()->getPeriods(), $locationsLibList[2]->getRegularHours()->getPeriods());

$specialHoursArray = array($locationsLibList[0]->getSpecialHours()->getSpecialHourPeriods(), $locationsLibList[2]->getSpecialHours()->getSpecialHourPeriods(), $locationsLibList[2]->getSpecialHours()->getSpecialHourPeriods());

$daysOfWeek = array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday");


/* BEGINING OF DATA PROCESSING */       

//create a copy array for future use
$specialHoursCopy = $specialHoursArray;

// loop through retrieved My Business SpecialHourPeriod values to change to valid request format
for($z = 0; $z < sizeof($locationsLibList); $z++){

    $new = new Google_Service_MyBusiness_SpecialHours();
    for($p = 0; $p < sizeof($specialHoursArray[$z]); $p++){
        
        if($specialHoursArray[$z][$p]->closeTime === "00:00"){
            $specialHoursArray[$z] = changeOldSpecialHoursEndDate($specialHoursArray[$z]);
            $specialHoursCopy[$z] = changeOldSpecialHoursEndDate($specialHoursCopy[$z]);
        }else if(isTimeBetween($specialHoursArray[$z][$p]->closeTime) == true && $specialHoursArray[$z][$p]->isClosed != true){
       
            $newPeriod = new Google_Service_MyBusiness_SpecialHourPeriod();
            $newStartDate = new Google_Service_MyBusiness_Date();
            $newStartDate->setDay($specialHoursArray[$z][$p]->endDate->day);
            $newStartDate->setMonth($specialHoursArray[$z][$p]->endDate->month);
            $newStartDate->setYear($specialHoursArray[$z][$p]->endDate->year);
            
            $newEndDate = new Google_Service_MyBusiness_Date();
            $newEndDate->setDay($specialHoursArray[$z][$p]->endDate->day);
            $newEndDate->setMonth($specialHoursArray[$z][$p]->endDate->month);
            $newEndDate->setYear($specialHoursArray[$z][$p]->endDate->year);

            $newCloseTime = $specialHoursArray[$z][$p]->closeTime;
            $newOpenTime = "00:00";
            $newPeriod->setStartDate($newStartDate);
            $newPeriod->setEndDate($newEndDate);
            $newPeriod->setCloseTime($newCloseTime);
            $newPeriod->setOpenTime($newOpenTime);
         
            $specialHoursCopy[$z][] = $newPeriod;
           $specialHoursCopy[$z][$p]->setCloseTime("24:00");
           $endDate = $specialHoursCopy[$z][$p]->startDate;
           $specialHoursCopy[$z][$p]->setEndDate($endDate);

            }
    }
    $new->setSpecialHourPeriods($specialHoursCopy[$z]);
    $locationsLibList[$z]->setSpecialHours($new);

}

//loop through libraries to gather LibCal data
$found = false;
for($y = 0; $y < sizeof($locationsLibList); $y++){
    $getLibrary = getLibrary($decodedJson, $libraryArray[$y]->getLib());
// loop through to gather week data from LibCal
   for($g = 0; $g < 4; $g++){
       
       $week = getWeeks($getLibrary, $g);
   // loop through days of the week to gather day values and declare day variables
     for($j = 0; $j < sizeof($daysOfWeek); $j++){
         
         $found = false;
         $times = returnDay($week, $daysOfWeek[$j]);
         $date = returnDate($times);
         $rendTime = "";
         /* rendered time proper formats
         "open - 8:30am - close - 5:30pm - weather closure" should be the format for upexpected closure
          "24 Hours" should be the format for a library open 24 hours
          "8:30am - 6:30pm" should be regular format */
         $rendTime = getRenderedTime($times); 
         $splitT = array();
         $splitT = splitTime($rendTime);
         $newDate = sepDate($date, $splitT);
         $isClosed = null;
         $isClosed = getStatus($times);
        $googleTime = array();
        
         if($isClosed != "not-set"){
             $googleTime = convertTime($splitT, $newDate, $isClosed);
         }
       
       // loop through day values and compare with regular hours
         for($f = 0; $f < sizeof($regularHoursArray[$y]); $f++){
             // if the value are the same day of the week ei. "Monday"
             if($regularHoursArray[$y][$f]->openDay === strtoupper($daysOfWeek[$j])){
                 
                 $isSame = cmpRegHoursToDay($regularHoursArray[$y][$f], $googleTime, $daysOfWeek[$j]);
               // if the time does not match the regular hours 
                 if($isSame == false && $specialHoursArray[$y] != null){
                  
                     $counter = 0;
                     $count = 0;
                     
                     //loop through the special hours and compare to a specified date
                     for($d = 0; $d < sizeof($specialHoursArray[$y]); $d++){
                        // if the dates match compare the time values
                         if(cmpDates($specialHoursArray[$y][$d], $newDate) == true){
                            
                             $theSame = cmpSpecHoursToDay($specialHoursArray[$y][$d], $googleTime, $newDate, $isClosed);
                          //if true remove both values from looping and change boolean
                             if($theSame == true){ 
                                 $isClosed = "not-set";
                              
                                $specialHoursArray[$y][$d] == null;
                                $found = true;
                            
                                 break;
                                 //increment counter
                             }else if($theSame == false){
                                 $counter++;
                                
                             }
                         }else{
                             //increment count
                             $count++;
                         }
                          //after last array value has been compared and still no matches, add date based off options
                         if($d == sizeof($specialHoursArray[$y]) - 1 && $counter == $d){
                        // split day values, ie. if time open extends to the next day
                            if(array_key_exists("endTime2", $googleTime)){
                     
                                $isClosed = "not-set";
                                $newValue1 = array();
                                $newValue1["Time"] = array(["openTime"]=>$googleTime[1]["openTime"], ["closeTime"]=>$googleTime[1]["endTime2"]);
                                $newValue1["Date"] = $googleTime[1]["date"];
                                $newValue1["isClosed"] = null;
                                $libraryArray[$y]->setOneSpecialHoursArray($newValue1);
                              

                               $newValue2 = array();
                               $newValue2["Time"] = $googleTime[0];
                               $newValue2["Date"] = $newDate;
                               $newValue2["isClosed"] = null;
                               $libraryArray[$y]->setOneSpecialHoursArray($newValue2);
                             
                               $specialHoursArray[$y][$d] == null;
                               $found = true;
                            }
                        
                         }else if($d == sizeof($specialHoursArray[$y]) - 1 && $count == $d){
                             // split day values, ie. if time open extends to the next day
                             if(array_key_exists("endTime2", $googleTime)){
                                

                                $isClosed = "not-set";
                                $newValue1 = array();
                                $newValue1["Time"] = array(["openTime"]=>$googleTime[1]["openTime"], ["closeTime"]=>$googleTime[1]["endTime2"]);
                                $newValue1["Date"] = $googleTime[1]["date"];
                                $newValue1["isClosed"] = null;
                                $libraryArray[$y]->setOneSpecialHoursArray($newValue1);
                           

                               $newValue2 = array();
                               $newValue2["Time"] = $googleTime[0];
                               $newValue2["Date"] = $newDate;
                               $newValue2["isClosed"] = null;
                               $libraryArray[$y]->setOneSpecialHoursArray($newValue2);
                              
                              $specialHoursArray[$y][$d] == null;
                              //if closed
                            }else if($times->times->status === "closed"){
                                $isClosed = true;
                                $newValue = array();
                                $newValue["Time"] = array($googleTime);
                                $newValue["Date"] = $newDate;
                                $newValue["isClosed"] = $isClosed;
                                $libraryArray[$y]->setOneSpecialHoursArray($newValue);
                                $specialHoursArray[$y][$d] == null;
                                // if does not match above
                            }else{
                          
                                $newValue = array();
                                $newValue["Time"] = array($googleTime);
                                $newValue["Date"] = $newDate;
                                $newValue["isClosed"] = null;
                                $libraryArray[$y]->setOneSpecialHoursArray($newValue);
                                $specialHoursArray[$y][$d] == null;
                            }
                            $found = true;
                         }
              //breaks from the loop if true
                         if($found == true){
                 break;
             }
                     
                     }

                 }
             }
             //breaks from the loop if true
             if($found == true){
                 break;
             }
         }
     
     }
   }
    }

/* END OF DATA PROCESSING */

print("<br/>");
print("<br/>");



 /* To update the existing special hours list */

//copy library array
 $newLocationArray = array();
 $newLocationArray[0] = $locationsLibList[0];
 $newLocationArray[1] = $locationsLibList[1];
 $newLocationArray[2] = $locationsLibList[2];
   
   //loop through library array
 for($r = 0; $r < sizeof($locationsLibList); $r++){
    
     if($libraryArray[$r]->getSpecialHoursArray() != null && sizeof($libraryArray[$r]->getSpecialHoursArray()) != 0){
         // loop through library special hours update the location
         for($w =0; $w < sizeof($libraryArray[$r]->getSpecialHoursArray()); $w++){
             $newLocation = changeSpecHours($locationsLibList[$r], $libraryArray[$r]->getOneSpecialHoursArray($w)["Time"], $libraryArray[$r]->getOneSpecialHoursArray($w)["Date"], $libraryArray[$r]->getOneSpecialHoursArray($w)["isClosed"]);

             $newLocationArray[$r] = $newLocation;
         }
     }
 }

 /* patch the new special hours to Google My Business */
 
 //loop through library array
 for($l = 0; $l < sizeof($locationsLibList); $l++){
   
     // if the library has new special hours to add
     if($specialHoursCopy[$l] != null){
try{
    //patch the location specialHours; Change validateOnly to false when what to update the values on Google My Business, if true the request will be validated but not updated.
     $updatedlocation = $mybusinessService->accounts_locations->patch($locationsLibList[$l]->name, $newLocationArray[$l], array('validateOnly'=>true, 'updateMask'=>"specialHours"));
     echo("Updated " . " Library " . $l );
     print_r($updatedlocation->specialHours->specialHourPeriods);
     print("<br/>");
     print("<br/>");
     print_r($specialHoursCopy[$l]);
     print("<br/>");
     print("<br/>");
 }catch(Exception $e){
     print "caught exception: " . $e->getMessage() . "\n";
 }
}else{
    //if Library had no new hours to add 
     print("No new values for Library " . $l);
}
print("<br/>");
}

/* Start of Program Functions  */

/**
 * This function compares individual time period values.
 * 
 * For example, the two parameters could be a time value, a date value, etc. As long as 
 * the two parameters are of the same datatype.
 * 
 * @param string|integer $regValue Could be a string or possibly integer value
 * @param string|integer $weekValue Could be a string or possibly integer value
 * @return boolean
 */
function cmpTimePeriodValues($regValue, $weekValue){
    $isSame = false;
    
    if($regValue === $weekValue){
        $isSame = true;
    }
    return $isSame;
}

/**
 * This function modifies the endDate and closeTime values.
 * 
 * This function is to accomodate from Google My Business API sending a response format
 * for the 24 Hour SpecialHourPeriods that cannot be sent back in the response. This function
 * changes the endDate to the startDate value and changes the closeTime to the same day instead
 * of the next day.
 * 
 * @param unknown $specialHourPeriod Google SpecialHourPeriod
 * @return unknown Google specialHourPeriod
 */
function changeOldSpecialHoursEndDate($specialHourPeriod){

	for($x = 0; $x < sizeof($specialHourPeriod); $x++){
	$newEndDate = new Google_Service_MyBusiness_Date();
	$newEndDate->setYear($specialHourPeriod[$x]->startDate->year);
	$newEndDate->setMonth($specialHourPeriod[$x]->startDate->month);
	$newEndDate->setDay($specialHourPeriod[$x]->startDate->day);
	$newEndDate->setDay($specialHourPeriod[$x]->startDate->day);
	$newEndDate->setDay($specialHourPeriod[$x]->startDate->day);
	$specialHourPeriod[$x]->setEndDate($newEndDate);
	$newCloseTime = "24:00";
	$specialHourPeriod[$x]->setCloseTime($newCloseTime);
	}
	return $specialHourPeriod;
	
}


 
/**
 * This function adds new a SpecialHourPeriod to the existing SpecialHourPeriods.
 * 
 * The new SpecialHourPeriod is created. Then the old SpecialHourPeriods are added to the array,
 * and then the new SpecialHourPeriod is added to the array. The new SpecialHours object is set 
 * with the new array. The new SpecialHours object is replaced in the location. The location is returned. 
 * 
 * @param unknown $location Google Location object
 * @param array $newHours
 * @param array $newDate
 * @param NULL|string|boolean $isClosed could be null, a string, or boolean value
 * @return unknown Google Location object
 */
function changeSpecHours($location, $newHours, $newDate, $isClosed){
 
    if($newHours != null && $newDate != null && $isClosed != null){
	$specialHours = new Google_Service_MyBusiness_SpecialHours();
	$specialPeriods = array();
	$specialPeriod = new Google_Service_MyBusiness_SpecialHourPeriod();
	
	$startDate = new Google_Service_MyBusiness_Date();
	$startDate->setMonth($newDate["startDate"][1]);
	
	$startDate->setDay($newDate["startDate"][2]);
	$startDate->setYear($newDate["startDate"][0]);
	$specialPeriod->setStartDate($startDate);
	
    if(array_key_exists(0, $newHours)){
       if($newHours[0]["openTime"] != null && $newHours[0]["closeTime"] != null){ // problem here
	$specialPeriod->setOpenTime($newHours[0]["openTime"]);
	$specialPeriod->setCloseTime($newHours[0]["closeTime"]);
    } 
}else if($newHours["openTime"] != null && $newHours["closeTime"] != null){
	$specialPeriod->setOpenTime($newHours["openTime"]);
	$specialPeriod->setCloseTime($newHours["closeTime"]);
	}
	
	$endDate = new Google_Service_MyBusiness_Date();
	$endDate->setMonth($newDate["startDate"][1]);
	$endDate->setDay($newDate["startDate"][2]);
	$endDate->setYear($newDate["startDate"][0]);
	$specialPeriod->setEndDate($endDate);
	 if($isClosed == true){
	$specialPeriod->setIsClosed($isClosed);
   }
   
	for($x = 0; $x < sizeof($location->specialHours->specialHourPeriods); $x++){
		
	$specialPeriods[$x] = $location->specialHours->specialHourPeriods[$x];
	
}
	$specialPeriods[] = $specialPeriod; 
	 
	$specialHours->setSpecialHourPeriods($specialPeriods);
 
	$location->setSpecialHours($specialHours);
}
	return $location;
}



/**
 * This is a function to return the rendered time.
 * 
 * A stdClass object from the LibCal API for a particular library is the parameter. This
 * function retrieves the value with the key "rendered" and returns the string.
 * 
 * @param stdClass $time stdClass object
 * @return string Returns the rendered time as a string
 */
function getRenderedTime($time){
	$rendTime = "";
	
	$rendTime = $time->rendered;
	
	return $rendTime;
	
}

 
/**
 * This is a function to retrieve the date.
 * 
 * A stdClass object from the LibCal API for a perticular library is the parameter. This
 * function retrieves the value associated with the key "date" and returns the string.
 * 
 * @param stdClass $time stdClass object
 * @return string returns the date as a string
 */
function returnDate($time){
	$date = "";
	
	
	$date = $time->date;

	return $date;
}


/**
 * This function is to return a day of the week.
 * 
 * The parameter $week is a stdClass object of a particular
 * week from a specific library. the parameter $day is the
 * particular day being looked for. The particular day(s) is
 * added to the array and that is returned.
 * 
 * @param stdClass $week stdClass object
 * @param string $day This parameter is entered as a string
 * @return array
 */
function returnDay($week, $day){
	
	$times = array();
	foreach($week->$day as $arr){
		
		
		$times= $week->$day;
		
		
	}
	
	return $times; 
}


/**
 * This function retrieves a specific week from LibCal values.
 * 
 * The specific week could range from indices 0 to 3. A particular LibCal library
 * is the first parameter and the second parameter, $weekNo, is the index value
 * of the particular week requested. The week value is added to an array and returned.
 * 
 * @param array $library
 * @param integer $weekNo
 * @return array
 */
function getWeeks($library, $weekNo){
	$week = array();
$week = $library["weeks"][$weekNo];

return $week;
	
	
}



/**
 * This function retrieves a library.
 * 
 * This function retrieves a particular library stdClass object from the
 * main LibCal json. The function adds the library values to an array,
 * then returns the array.
 * 
 * @param stdClass $main JSON or stdClass object
 * @param string $libraryName String value only
 * @return NULL[]
 */
function getLibrary($main, $libraryName){
	$counter = 0;
	$wholeLib = array();
foreach($main->locations as $arr){
	
	$array = $main->locations;
	
	
if($array[$counter]->lid == $libraryName){
		
		$wholeLib["lib"] = $array[$counter]->lid;
		$wholeLib["weeks"] = $array[$counter]->weeks;
		
	
	}

$counter++;
}
return $wholeLib;
}





/**
 * This function retrieves the status for a particular day.
 * 
 * A object for a library is the parameter. The value
 * with the key "status" is retrieved and if the string matched one of the
 * option, the value of the status is changed and returned.
 * 
 * @param unknown $libraryDay stdClass object or Google object
 * @return NULL|boolean|string
 */
function getStatus($libraryDay){


	$statusString = $libraryDay->times->status; 
if($statusString === "open"){
	$statusString = null;
}else if($statusString === "close"){
	$statusString = true;
}else if($statusString === "not-set"){
	$statusString = "not-set";
}else if($statusString === "closed"){
	$isClosed = true;
}
	return $statusString;
}




/**
 * This function determines if the time is in the AM.
 * 
 * An array of the split LibCal time for a particular day is the parameter.
 * If the any of the index vaules matches the string "a", then a boolean of true 
 * is returned, otherwise false is returned.
 * 
 * @param string $splitTime This parameter is given as a string and the characters are split
 * @return boolean
 */
function isTimeAM($splitTime){
    $isAM = false;
    
    for($x = 0; $x < strlen($splitTime); $x++){
        
        if($splitTime[$x] == "a"){
            $isAM = true;
          
            break;
        }
        
        
    }
    return $isAM;
}




/**
 * This function checks to determine if the time string is
 * between 12am and 07:59am.
 * 
 * This function is to determine if the day in question is a 
 * split day, where the hours for that day go into another day.
 * Google My Business API only accepts hours that are on the same day.
 * This functions helps to determine whether or not another SpecialHoursPeriod 
 * should be created to accomodate for that.
 * 
 * @param string $splitTime Given as a string in a format of "00:00"
 * @return boolean
 */
function isTimeBetween($splitTime){
    $isValid = false;
    $time = explode(":", $splitTime);
 
    if((int)$time[0] <= 7){
        
        $isValid = true;
      
    }
    return $isValid;
    
}





/**
 * This function converts splitTime format to Google My Business time format.
 * 
 * This function accepts a string time value and loops through
 * the characters, matching the values and changes the time accordingly. This function will
 * throw an exception if the string does not match any of the options.
 * 
 * @param string $splitTime This value is given as a string and characters are evaluated
 * @throws Exception
 * @return string|unknown
 */
function getTimeNow($splitTime){
    $addToString = "";
    $sendString = "";

    for($x = 0; $x < strlen($splitTime); $x++){
	
        $answer = is_numeric($splitTime[$x]);
    
        if($answer == 1 && $x == 0 && $splitTime[$x + 1] == "a" ){
            // handle the normal way
            $addToString = "0" . (string) $splitTime[$x] . ":00";
        }else if($answer == 1 && $x == 0 && is_numeric($splitTime[$x + 1]) == true && $splitTime[$x + 2] == "a"){
            
            $addToString = (string)$splitTime[$x] . (string)$splitTime[$x+1] . ":00";
            
        }else if($answer == 1 && $x == 0 && $splitTime[$x + 1] == ":"){
            
            
            $addToString = "0" . (string)$splitTime[$x] . ":" . (string)$splitTime[$x + 2] . (string)$splitTime[$x + 3];
            
            
        }else if($answer == 1 && $x == 0 && is_numeric($splitTime[$x + 1]) == 1 && $splitTime[$x + 2] == ":"){
            
            $addToString = $splitTime[$x] . $splitTime[$x + 1] . $splitTime[$x + 2] . $splitTime[$x + 3] . $splitTime[$x + 4];
            
            
        }else if($answer == 1 && $x == 0 && is_numeric($splitTime[$x + 1]) == 1 && $splitTime[$x + 2] == "p"){
            
            $addToString = $splitTime[$x] . $splitTime[$x + 1] . ":00";
            
            
        }else if($answer == 1 && $x == 0 && $splitTime[$x + 1] == "p" && $x == 0){
            // handle the normal way
            $addToString = "0" . (string) $splitTime[$x] . ":00";
			$addToString = changeTimeFormat($addToString);
			
        }else if($splitTime === "24 Hours"){
          
            $addToString = "00:00";
            
        }else{
            //not correct input error
            throw new Exception('Time value from RenderedTime is in improper format in function getTimeNow()' . $splitTime);
        }
        if($addToString != NULL){
            break;
        }
    }
    
    if(isTimeAM($splitTime) == true){
        $sendString = $addToString;
    }else{
    
   $sendString = $addToString;
}
    return $sendString;
 
}





/**
 * This function separates the values in the string based on a delimiter.
 * 
 * After the values are split, those values are added to an array key value called
 * "startDate".
 * 
 * @param string $stringDate This string value is split based on "-" delimiter
 * @param array $splitTime 
 * @return array|number
 */
function sepDate($stringDate, $splitTime){
     
	$date = array();
    $splitDate = explode('-', $stringDate);
    for($x = 0; $x < sizeof($splitDate); $x++){
        $date["startDate"][$x] = (int)$splitDate[$x];
       
    }

    if($splitTime[0] == "24 Hours"){
        
        $date = getNextDay($date);
    }
   
    return $date;
}



/**
 * This function retrieves the following day.
 * 
 * This function is used for 24 Hours and split days. Date value must
 * already be separated.
 * 
 * @param array $date
 * @return array
 */
function getNextDay($date){
    
    
   //retrieve date values 
    $year = $date["startDate"][0];
    $month = $date["startDate"][1];
    $day = $date["startDate"][2];
    // retrieve number of days in a month
    $daysInThisMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    //compare values
    if($month != "12"){
        if($daysInThisMonth > $day){
            
            $date["endDate"][0] = $year;
            $date["endDate"][1] = $month;
        
            $changeDay = (int)$day + 1;
            $date["endDate"][2] = $changeDay;
            
        }else if($daysInThisMonth == $day){
            $date["endDate"][0] = $year;
            $changeMonth = (int)$month + 1;
            $date["endDate"][1] = (string)$changeMonth;
            $changeDay = "1";
            $date["endDate"][2] = (string)$changeDay;
        }
        
    }else{
        $changeYear = (int)$year + 1;
        $date["endDate"][0] = (string)$changeYear;
        $date["endDate"][1] = "1";
        $date["endDate"][2] = "1";
    }
    
    return $date;
}





/**
 * This function splits the time based on a delimiter.
 * 
 * After the time is split the values are saved to and array
 * and returned.
 * 
 * @param string $splTime This string value is split based on delimiter space-space
 * @return unknown[]
 */
function splitTime($splTime){
	
$times = array();
    $splitTime = explode(" - ", $splTime);
  for($x = 0; $x < sizeof($splitTime); $x++){
        $times[$x] = $splitTime[$x];
       
    }
	
    return $times;
	
}



/**
 * This function converts LibCal time to Google My Business time format.
 * 
 * This function determine whether the there is a closure, 24 Hour time, split day, or regular hours
 * and calls appropriate methods to handle the values. This method will return different types of arrays depending
 * on the outcome.
 * 
 * @param unknown $splitTime This value will most likely be an array, possibly multidimutional, could have one value.
 * @param array $sepDate
 * @param unknown $isClosed This value could be a boolean or a string or a null value.
 * @return string[]|NULL[]|string[][]|NULL[][]|mixed[][]|unknown[][]|NULL
 */
function convertTime($splitTime, $sepDate, $isClosed){ 

    $answer = false;
    $timeArray = array();
    $nextTimeArray = array();
	$addToString = "";
	$value = "";
	
	//this handles if its an unexpected closure 
if($isClosed != true || $isClosed === "24hours"){

	if(is_numeric($splitTime[0][0]) == 0 && $splitTime[0][0] === "o"){
		
		$value = array_shift($splitTime);
	
		unset($splitTime[1]);
		
		if(sizeof($splitTime) == 3){
		unset($splitTime[3]);
		}
		
	$splitTime = array_values($splitTime);
		
	}

	$timeArray["openTime"] = getTimeNow($splitTime[0]); 
	
    if($splitTime[0] != "24 Hours"){
       
    if(is_numeric($splitTime[1][0]) == 0 && $splitTime[1][0] === "c"){
       
        unset($splitTime[1]);
        
        if(sizeof($splitTime) == 3){
            unset($splitTime[2]);
        }
 
    }
    }
    
    
//looping for other endtime
    
    if($splitTime[0] != "24 Hours"){
      
        if(isTimeAM($splitTime[0]) == true && isTimeAM($splitTime[1]) == true){
        
            if(isTimeBetween(getTimeNow($splitTime[1]))){
                
                $nextTimeArray["endTime2"] = getTimeNow($splitTime[1]);
                $nextTimeArray["openTime2"] = "00:00";
              
                if(array_key_exists('endDate', $sepDate) == false){
                    $dateNow = array();
                    $dateNow =getNextDay($sepDate);
                    $nextTimeArray["date"] = $dateNow["endDate"];
                }else{
                $nextTimeArray["date"] = $sepDate["endDate"]; 
               
                }
                $timeArray["closeTime"] = "24:00";
            }else{
                $timeArray["closeTime"] = getTimeNow($splitTime[1]);
               
            }
            
        }else{

            $timeArray["closeTime"] = changeTimeFormat(getTimeNow($splitTime[1]));
			
        }
           
    }elseif($splitTime[0] === "24 Hours"){
          
          $timeArray["closeTime"] =  $toString = "24:00";
            
        }
        
        if($nextTimeArray == NULL){
			
            return $timeArray;
        }else{
			
            return array($timeArray, $nextTimeArray);
        }
   }else{
	
	return null;
} 
}




/**
 * This function splits a string.
 * 
 * @param string $strings This string will be split on characters
 * @return array
 */
function splitString($strings){
    
    $splitString = str_split($strings);
    
    return $splitString;
}





/**
 * This function converts the LibCal time to 24 hour format.
 * 
 * @param string $time This string will be split into characters
 * @throws Exception
 * @return string This string will be in the format of "00:00"
 */
function changeTimeFormat($time){ 

$close = ""; 
    $closeTime = str_split($time);
	
    $counter = 0;
 
    if((int)$closeTime[$counter] == 0 && (int)$closeTime[$counter + 1] >= 1){
        
        $number = $closeTime[$counter + 1];
        (int)$number += 12; 
		$number = $number . $closeTime[$counter + 2] . $closeTime[$counter + 3] . $closeTime[$counter + 4];
       
        $close = $number;
        
    }else if((int)$closeTime[$counter] != 0 ){
        
        $number = $closeTime[$counter] . $closeTime[$counter + 1];
	
        (int)$number += 12;
		
		$number = $number . $closeTime[$counter + 2] . $closeTime[$counter + 3] . $closeTime[$counter + 4];
	
        $close = $number;
       
    }else{
		throw new Exception('CloseTime could not be converted to 24 hour format in function changeTimeFormat()');
		
	}

    return $close; 
}







/**
 * This function compares one day from LibCal to one day for Google My Business.
 * 
 * The Google My Business value is from regularHours.
 * 
 * @param unknown $regHours Google TimePeriod or Google RegularHours->Periods 
 * @param array $Day and should
 * be in the format of $location[1]["regularHours"]["periods"][indexValue].
 * @param string $dayOfWeek
 * @return boolean
 */
function cmpRegHoursToDay($regHours, $Day, $dayOfWeek){
    if($Day == null){
		
}

    $isSame = false;
    

    $openDay = cmpTimePeriodValues($regHours->openDay, strtoUpper($dayOfWeek));

if($Day != null){
if(array_key_exists(1, $Day)){
	
	$openTime = cmpTimePeriodValues($regHours->openTime, $Day[0]["openTime"]);
	$closeTime = cmpTimePeriodValues($regHours->closeTime, $Day[0]["closeTime"]);
}else{
    $openTime = cmpTimePeriodValues($regHours->openTime, $Day["openTime"]);
   $closeTime = cmpTimePeriodValues($regHours->closeTime, $Day["closeTime"]);
}
}else{
    $openTime = cmpTimePeriodValues($regHours->openTime, null);
    $closeTime = cmpTimePeriodValues($regHours->closeTime, null);
}
    $closeDay = cmpTimePeriodValues($regHours->closeDay, strtoupper($dayOfWeek));
    

    if($openDay == true && $openTime == true && $closeDay == true && $closeTime == true){
       $isSame = true;
       
    }

       return $isSame;
}

/**
 * This function compares two date values.
 * 
 * @param unknown $specialHour Google SpecialHourPeriod
 * @param array $libCalDate
 * @return boolean
 */
function cmpDates($specialHour, $libCalDate){
	$isSame = false;
	if($libCalDate != null && $specialHour->startDate != null){  

	$yearStart = cmpTimePeriodValues($specialHour->startDate->year, $libCalDate["startDate"][0]);

	$monthStart = cmpTimePeriodValues($specialHour->startDate->month, $libCalDate["startDate"][1]);
	$dayStart = cmpTimePeriodValues($specialHour->startDate->day, $libCalDate["startDate"][2]);
}

	
	if($dayStart == true && $monthStart == true && $yearStart == true){
       $isSame = true;
    }
	
	return $isSame;
}

 
/**
 * This function compares one SpecialHourPeriod day value to one LibCal day value.
 * 
 * @param unknown $specialHour Google SpecialHourPeriod
 * @param array $day
 * @param array $dayDate
 * @param boolean|string|NULL $dayIsClosed This value could be boolean or string or null
 * @return boolean
 */
function cmpSpecHoursToDay($specialHour, $day, $dayDate, $dayIsClosed){
	
	$isSame = false;
	$closeTime = cmpTimePeriodValues($specialHour->closeTime, $day["closeTime"]); 
	$openTime = cmpTimePeriodValues($specialHour->openTime, $day["openTime"]);

if($dayDate != null && $specialHour->startDate != null){ 

	$yearStart = cmpTimePeriodValues($specialHour->startDate->year, $dayDate["startDate"][0]);

	$monthStart = cmpTimePeriodValues($specialHour->startDate->month, $dayDate["startDate"][1]);
	$dayStart = cmpTimePeriodValues($specialHour->startDate->day, $dayDate["startDate"][2]);
}
	$isClosed = cmpTimePeriodValues($specialHour->isClosed, $dayIsClosed);


	if($dayStart == true && $monthStart == true && $yearStart == true && $openTime == true && $closeTime == true && $isClosed == true){
       $isSame = true;
    }
	
	return $isSame;
}
?>