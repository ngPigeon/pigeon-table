<?php
//Start Session Storage
session_start();

//Turn off PHP error reporting
error_reporting(0);

include "../configdb.php";

$dbhandle=new mysqli(HOSTNAME, USERNAME, PASSWORD, DATABASE) or die ("Unable to Connect to the Database");

$db = DATABASE;

$sqlQuery=json_decode(file_get_contents("php://input"));

//Execute this function if sql query is SELECT
if (strpos(strtoupper($sqlQuery->sql), 'SELECT') !== false) {
    
    $sql=$sqlQuery->sql;
    $rs=$dbhandle->query($sql);
    
    if ($dbhandle->connect_error) {
        print_r($dbhandle->connect_error);
    } else if ($dbhandle->error) {
        print_r($dbhandle->error);
    } else {
        while($row=$rs->fetch_array(MYSQLI_ASSOC)){
            $data[]=$row;
        }
        
        //Show All Tables in the database
        $result = $dbhandle->query("SHOW TABLES FROM $db");

        while($row=mysqli_fetch_row($result)){
            if(strpos($sql, $row[0]) !== false) {
                $table = $row[0];
            }
        }
        
        //Remove cookies when web app is loaded
        setcookie("priKey", null, -1, "/");
        
        //Check Primary and Unique Key of Table
        $rsPriKey = $dbhandle->query("DESCRIBE $table");
        while($row=$rsPriKey->fetch_array(MYSQLI_ASSOC)){
            if ($row['Key'] == "PRI") {
                //Store Primary Key as cookie for client side scripting purpose
                setcookie("priKey", $row['Field'], 0, "/");
                $tableDetail[] = $row;
            } else {
                $tableDetail[] = $row;
            }
        }
        
        //Set Session Storage
        $_SESSION['table'] = $table;
        $_SESSION['data'] = $data;
        $_SESSION['tableDetail'] = $tableDetail;
        $_SESSION['selectQuery'] = $sqlQuery->sql;
        
        $resultData['data'] = $data;
        $resultData['keyTable'] = $tableDetail;
        
        //print json_encode($resultData, JSON_PRESERVE_ZERO_FRACTION);
        print json_encode($resultData, JSON_NUMERIC_CHECK);
    }
    
} else if (strpos($sqlQuery->sqlType, 'INSERT') !== false || strpos($sqlQuery->sqlType, 'UPDATE') !== false) {
    $table = $_SESSION['table'];
    $data = $_SESSION['data'];
    $tableDetail = $_SESSION['tableDetail'];
    $selectQuery = $_SESSION['selectQuery'];
    $colTitle = "";
    $colField = "";
    $result = $dbhandle->query("DESCRIBE $table");
    
    while($row=$result->fetch_array(MYSQLI_ASSOC)){
        
        foreach($sqlQuery as $key => $value) {
            if ($key != "sqlType") {
                //Check if the column field same as the key of data from client side
                if ($key == $row['Field']) {
                    $colTitle .= $key. ', ';
                    $length = preg_replace("/[^0-9,]/","",$row['Type']);
                    
                    //Check if MySQL table's column type is numeric
                    if (preg_match("/(tinyint|smallint|mediumint|int|bigint|float|double)/i", $row['Type'])){
                        //Check if the data is numeric
                        if(is_numeric($value)) {
                            //Check the length of the data
                            if (strlen($value) <= $length) {
                                //Check if the column is primary and unique key
                                if (($row['Key'] == "PRI" || $row['Key'] == "UNI") && strpos($sqlQuery->sqlType, 'INSERT') !== false) {
                                    $existed = false;
                                    foreach ($data as $colVal) {
                                        if ($colVal[$key] == $value) {
                                            $msg[] = "The data is existed. Please insert another data.";
                                            $existed = true;
                                        } 
                                    }
                                    //Store value into variable if the data is not existed
                                    if ($existed == false) {
                                        $msg[] = "Validated";
                                        $colField .= "'".$value."', ";
                                        $updateValue .= "$key = '".$value."', ";
                                    }
                                    
                                } else {
                                    $msg[] = "Validated";
                                    $colField .= "'".$value."', ";
                                    $updateValue .= "$key = '".$value."', ";
                                }
                                
                            } else {
                                $msg[] = "Please insert your input less than $length";
                            }
                        } else {
                            $msg[] = "Please insert number only";  
                        }
                    } else if (preg_match("/decimal/i", $row['Type'])){
                        $decLength = explode(",", $length);
                        $digitLength = $decLength[0] - $decLength[1];
                        $dpLength = strlen($value) - strpos($value, ".") - 1;
                        if ($decLength[0] != strlen($value) - 1 || $decLength[1] != $dpLength) {
                            $msg[] = "Please insert $digitLength digits with $decLength[1] decimal places.";
                        } else {
                            $msg[] = "Validated";
                            $colField .= "'".$value."', ";
                            $updateValue .= "$key = '".$value."', ";
                        }
                    } else if (preg_match("/(datetime)/i", $row['Type'])) {
                        if (preg_match("/^(1[0-9]{3}|[1-9][0-9]{3})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])\s([0-1][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/", $value)) {
                            DateTime::createFromFormat('Y-m-d H:i:s', $value);
                            $error = DateTime::getLastErrors();
                            if ($error['warning_count'] > 0) {
                                $msg[] = "Please insert valid date and time.";
                            } else {
                                $msg[] = "Validated";
                                $colField .= "'".$value."', ";
                                $updateValue .= "$key = '".$value."', ";
                            }
                        } else {
                            $msg[] = "Please insert correct date and time format (yyyy-mm-dd HH:mm:ss).";
                        }
                    } else if (preg_match("/(timestamp)/i", $row['Type'])) {
                        if (preg_match("/^(19[7-9][0-9]|20[0-2][0-9]|20[3][0-7])-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])\s([0-1][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/", $value)) {
                            DateTime::createFromFormat('Y-m-d H:i:s', $value);
                            $error = DateTime::getLastErrors();
                            if ($error['warning_count'] > 0) {
                                $msg[] = "Please insert valid date and time of timestamp.";
                            } else {
                                $msg[] = "Validated";
                                $colField .= "'".$value."', ";
                                $updateValue .= "$key = '".$value."', ";
                            }
                        } else {
                            $msg[] = "Please insert correct timestamp format yyyy-mm-dd HH:mm:ss (Range: 1970-01-01 00:00:01 to 2037-12-31 23:59:59)";
                        }
                    } else if (preg_match("/(date)/i", $row['Type'])) {
                        if (preg_match("/^[1-9][0-9]{3}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $value)) {
                            DateTime::createFromFormat('Y-m-d', $value);
                            $error = DateTime::getLastErrors();
                            if ($error['warning_count'] > 0) {
                                $msg[] = "Please insert valid date.";
                            } else {
                                $msg[] = "Validated";
                                $colField .= "'".$value."', ";
                                $updateValue .= "$key = '".$value."', ";
                            }
                        } else {
                            $msg[] = "Please insert correct date format yyyy-mm-dd (Range: 1000-01-01 to 9999-12-31)";
                        }
                    } else if (preg_match("/(time)/i", $row['Type'])) {
                        if (preg_match("/^(([0-7][0-9]{2}|8[0-2][0-9]|83[0-8]|\d{2})|(-[0-7][0-9]{2}|-8[0-2][0-9]|-83[0-8]|-\d{2})):([0-5][0-9]):([0-5][0-9])$/", $value)) {
                            $msg[] = "Validated";
                            $colField .= "'".$value."', ";
                            $updateValue .= "$key = '".$value."', ";
                        } else {
                            $msg[] = "Please insert valid time within range -838:59:59 to 838:59:59";
                        }
                    } else if (preg_match("/(year)/i", $row['Type'])) {
                        if (preg_match("/^(190[1-9]|19[1-9][0-9]|20[0-9]{2}|21[0-4][0-9]|215[0-5])$/", $value)) {
                            $msg[] = "Validated";
                            $colField .= "'".$value."', ";
                            $updateValue .= "$key = '".$value."', ";
                        } else {
                            $msg[] = "Please insert valid year within range 1901 to 2155";
                        }
                    } else {
                        //Check if string type
                        if (strlen($value) <= $length) {
                            //Check if the column is primary and unique key
                            if (($row['Key'] == "PRI" || $row['Key'] == "UNI") && strpos($sqlQuery->sqlType, 'INSERT') !== false) {
                                $existed = false;
                                foreach ($data as $colVal) {
                                    if ($colVal[$key] == $value) {
                                        $msg[] = "The data is existed. Please insert another data.";
                                        $existed = true;
                                    } 
                                }
                                //Store value into variable if the data is not existed
                                if ($existed == false) {
                                    $msg[] = "Validated";
                                    $colField .= "'".$value."', ";
                                    $updateValue .= "$key = '".$value."', ";
                                }
                            } else {
                                $msg[] = "Validated";
                                $colField .= "'".$value."', ";
                                $updateValue .= "$key = '".$value."', ";
                            }
                        } else {
                            $msg[] = "Please insert your input less than $length";
                        }
                    }
                }
            }
        }
    }
    
    $queryMode = true;
    
    for ($i = 0; $i < sizeof($msg); $i++) {
        if ($msg[$i] != "Validated") {
            $queryMode = false;
        }
    }
    
    $colTitle = substr(trim($colTitle), 0, -1);
    $colField = substr(trim($colField), 0, -1);
    $updateValue = substr(trim($updateValue), 0, -1);
    
    if (strpos($sqlQuery->sqlType, 'INSERT') !== false) {
        $query = "INSERT INTO $table ($colTitle) VALUES ($colField)";
        if ($queryMode == true) {
            $dbhandle->query($query);
            print "Inserted";
        } else {
            print json_encode($msg);
        }
    } else if (strpos($sqlQuery->sqlType, 'UPDATE') !== false) {
        $priKey = $_COOKIE['priKey'];
        $priVal = $sqlQuery->priVal;
        
        if ($priKey == null) {
            $result = $dbhandle->query($selectQuery);
            while ($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $selectedData[] = $row;
            }
            foreach ($selectedData[0] as $key => $value) {
                $priKey = $key;
                break;
            }
        }
        
        $query = "UPDATE $table SET $updateValue WHERE $priKey='".$priVal."'";
        
        if ($queryMode == true) {
            $dbhandle->query($query);
            if ($dbhandle->error) {
                $errorMsg = preg_split("/for key '/", $dbhandle->error);
                $errorMsg = substr(trim($errorMsg[1]), 0, -1);
                print_r($errorMsg);
            } else {
                print "Updated";  
            }
        } else {
            print json_encode($msg);
        }
    }

} else if (strpos($sqlQuery->sqlType, 'DELETE') !== false) {
    $table = $_SESSION['table'];
    $deleteValue = "";
    $result = $dbhandle->query("DESCRIBE $table");
    
    while ($row=$result->fetch_array(MYSQLI_ASSOC)) {
        foreach ($sqlQuery as $key => $value) {
            if ($key != "sqlType") {
                if ($key == $row['Field']) {
                    if ($value != null) {
                        $deleteValue .= "$key = '".$value."' AND ";
                    }
                }
            }
        }
    }
    
    $deleteValue = substr(trim($deleteValue), 0, -3);
    
    $query = "DELETE FROM $table WHERE $deleteValue";
    
    $dbhandle->query($query);

    print "success";
}

?>
