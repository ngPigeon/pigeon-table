<?php
    session_start();

    require 'get-data.php';

    function getTableStructure($db, $sql, $dbName) {
        //Show All Tables' name in the database
        $result = $db->query("SHOW TABLES FROM $dbName");

        while($row = mysqli_fetch_row($result)){
            //Get the name of the table
            if(strpos($sql, $row[0]) !== false) {
                $table = $row[0];
                break;
            }
        }

        //Initialize to prevent undefined occur
        $priKey = null;
        $indexCol = [];

        //Check Primary and Unique Key of Table
        $rsPriKey = $db->query("DESCRIBE $table");
        $firstPriKey = false;
        while($row = $rsPriKey->fetch_array(MYSQLI_ASSOC)) {
            if ($row['Key'] == "PRI" && $firstPriKey == false) {
                $priKey = $row['Field'];
                $firstPriKey = true;
            }

            if ($row['Key'] == "PRI" || $row['Key'] == "UNI") {
                $indexCol[$row['Field']] = $row['Key'];
            }
        }

        $resultData['tableName'] = $table;
        $resultData['priKey'] = $priKey;
        $resultData['indexCol'] = $indexCol;
        $resultData['selectQuery'] = $sql;

        return $resultData;
    }

    function handleInsertOrUpdate($db, $input, $queryType) {

        $data = handleSelect($db, $input->tableStructure->selectQuery);
        $table = $input->tableStructure->tableName;
        $priKey = $input->tableStructure->priKey;
        if ($priKey != null) {
            $priVal = $input->$priKey;
        }

        $colTitle = "";
        $colField = "";
        $updateValue = "";
        $result = $db->query("DESCRIBE $table");

        while($row = $result->fetch_array(MYSQLI_ASSOC)){

            foreach($input as $key => $value) {

                if ($key != "sqlType" && $key == $row['Field']) {

                    //Check if the column is null
                    if ($value == "" && $row['Null'] == "YES") {
                        $value = "NULL";
                    } else if ($value == "" && $row['Null'] == "NO") {
                        $msg[] = "Please insert your data.";
                        continue;
                    }

                    $colTitle .= $key. ', ';
                    $length = preg_replace("/[^0-9]/","",$row['Type']);

                    if (preg_match("/(datetime)/i", $row['Type'])) {
                        if (preg_match("/^(1[0-9]{3}|[1-9][0-9]{3})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])\s([0-1][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/", $value)) {
                            DateTime::createFromFormat('Y-m-d H:i:s', $value);
                            $error = DateTime::getLastErrors();
                            if ($error['warning_count'] > 0) {
                                $msg[] = "Please insert valid date and time.";
                                continue;
                            }
                        } else {
                            $msg[] = "Please insert correct date and time format (yyyy-mm-dd HH:mm:ss).";
                            continue;
                        }
                    } else if (preg_match("/(timestamp)/i", $row['Type'])) {
                        if (preg_match("/^(19[7-9][0-9]|20[0-2][0-9]|20[3][0-7])-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])\s([0-1][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/", $value)) {
                            DateTime::createFromFormat('Y-m-d H:i:s', $value);
                            $error = DateTime::getLastErrors();
                            if ($error['warning_count'] > 0) {
                                $msg[] = "Please insert valid date and time of timestamp.";
                                continue;
                            }
                        } else {
                            $msg[] = "Please insert correct timestamp format yyyy-mm-dd HH:mm:ss (Range: 1970-01-01 00:00:01 to 2037-12-31 23:59:59)";
                            continue;
                        }
                    } else if (preg_match("/(date)/i", $row['Type'])) {
                        if (preg_match("/^[1-9][0-9]{3}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $value)) {
                            DateTime::createFromFormat('Y-m-d', $value);
                            $error = DateTime::getLastErrors();
                            if ($error['warning_count'] > 0) {
                                $msg[] = "Please insert valid date.";
                                continue;
                            }
                        } else {
                            $msg[] = "Please insert correct date format yyyy-mm-dd (Range: 1000-01-01 to 9999-12-31)";
                            continue;
                        }
                    } else if (preg_match("/(time)/i", $row['Type'])) {
                        if (!preg_match("/^(([0-7][0-9]{2}|8[0-2][0-9]|83[0-8]|\d{2})|(-[0-7][0-9]{2}|-8[0-2][0-9]|-83[0-8]|-\d{2})):([0-5][0-9]):([0-5][0-9])$/", $value)) {
                            $msg[] = "Please insert valid time within range -838:59:59 to 838:59:59";
                            continue;
                        }
                    } else if (preg_match("/(year)/i", $row['Type'])) {
                        if (!preg_match("/^(190[1-9]|19[1-9][0-9]|20[0-9]{2}|21[0-4][0-9]|215[0-5])$/", $value)) {
                            $msg[] = "Please insert valid year within range 1901 to 2155";
                            continue;
                        }
                    } else {

                        //Check if MySQL table's column type is numeric
                        if (preg_match("/(tinyint|smallint|mediumint|int|bigint|decimal|float|double)/i", $row['Type'])) {

                            //Check if the data is numeric
                            if(!is_numeric($value)) {
                                $msg[] = "Please insert number only";
                                continue;
                            }

                            //Check the length of the data
                            if (strlen($value) > $length) {
                                $msg[] = "Please insert your input less than $length";
                                continue;
                            }

                        } else if (strlen($value) > $length) {

                            $msg[] = "Please insert your input less than $length";
                            continue;
                        }

                        //Allow data to be updated after validated
                        if (($row['Key'] != "PRI" || $row['Key'] != "UNI") && $queryType == "UPDATE") {
                            $msg[] = "Validated";
                            $colField .= "'".$value."', ";
                            $updateValue .= "$key = '".$value."', ";
                            continue;
                        }

                        //Continue validatation if SQL statement is "INSERT"
                        $exists = false;
                        foreach ($data as $colVal) {
                            if (($row['Key'] == "PRI" || $row['Key'] == "UNI") && $colVal[$key] == $value) {
                                $msg[] = "The data already exists. Please insert different data.";
                                $exists = true;
                                break;
                            }
                        }

                        if ($exists) break 2;
                    }

                    // Store the data if there are no validation errors
                    $msg[] = "Validated";

                    if ($value == "NULL") {
                        $colField .= $value.", ";
                        $updateValue .= "$key = ".$value.", ";
                    } else {
                        $colField .= "'".$value."', ";
                        $updateValue .= "$key = '".$value."', ";
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

        if ($queryType == "INSERT") {
            if ($queryMode == true) {
                $db->query("INSERT INTO $table ($colTitle) VALUES ($colField)");
                print "Inserted";
            } else {
                print json_encode($msg);
            }
        } else if ($queryType == "UPDATE") {
            if ($queryMode == true) {
                $db->query("UPDATE $table SET $updateValue WHERE $priKey='".$priVal."'");
                if ($db->error) {
                    $errorMsg = preg_split("/for key '/", $db->error);
                    $errorMsg = substr(trim($errorMsg[1]), 0, -1);
                    print_r($errorMsg);
                } else {
                    print "Updated";
                }
            } else {
                print json_encode($msg);
            }
        }
    }

    function handleDelete($db, $input) {
        $table = $input->tableStructure->tableName;
        $priKey = $input->tableStructure->priKey;
        $priVal = $input->$priKey;

        $db->query("DELETE FROM $table WHERE $priKey='".$priVal."'");

        print "Deleted";
    }

?>
