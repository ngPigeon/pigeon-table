<?php
    // Start Session Storage
    session_start();

    // Turn off PHP error reporting
    error_reporting(0);

    include "../configdb.php";

    $db = new mysqli(HOSTNAME, USERNAME, PASSWORD, DATABASE) or die ("Unable to Connect to the Database");
    $dbName = DATABASE;
    $query = json_decode(file_get_contents("php://input"));
    $queryType = getQueryType($query);

    switch ($queryType) {
        case "SELECT":
            handleSelect($db, $query, $dbName);
            break;
        case "INSERT":
        case "UPDATE":
            handleInsertOrUpdate($db, $query, $queryType);
            break;
        case "DELETE":
            handleDelete($db, $query);
            break;
    }

    function getQueryType($query) {
        if (strpos($query->sql, 'SELECT') !== false)
            return 'SELECT';
        return $query->sqlType;
    }

    function handleSelect($db, $query, $dbName) {
        $sql = $query->sql;
        $rows = $db->query($sql);

        // Handle connection/database error
        if ($db->connect_error)
            return print_r($db->connect_error);
        if ($db->error)
            return print_r($db->error);

        while ($row = $rows->fetch_array(MYSQLI_ASSOC)) {
            $data[] = $row;
        }

        // Get the name of the table used in the query
        $tables = $db->query("SHOW TABLES FROM $dbName");
        while ($row = mysqli_fetch_row($tables)) {
            if (strpos($sql, $row[0]) !== false) {
                $table = $row[0];
                break;
            }
        }

        // Remove cookies when web app is loaded
        setcookie("priKey", null, -1, "/");

        // Check Primary and Unique Key of Table
        $columns = $db->query("DESCRIBE $table");
        while ($row = $columns->fetch_array(MYSQLI_ASSOC)) {
            if ($row['Key'] == "PRI") {
                //Store Primary Key as cookie for client side scripting purpose
                setcookie("priKey", $row['Field'], 0, "/");
            }
            $tableDetail[] = $row;
        }

        // Set Session Storage
        $_SESSION['table'] = $table;
        $_SESSION['data'] = $data;
        $_SESSION['tableDetail'] = $tableDetail;
        $_SESSION['selectQuery'] = $query->sql;

        // Format data and return to client as JSON
        $resultData['data'] = $data;
        $resultData['keyTable'] = $tableDetail;
        print json_encode($resultData, JSON_NUMERIC_CHECK);
    }

    function handleInsertOrUpdate($db, $query, $queryType) {
        $table = $_SESSION['table'];
        $data = $_SESSION['data'];
        $tableDetail = $_SESSION['tableDetail'];
        $selectQuery = $_SESSION['selectQuery'];
        $colTitle = "";
        $colField = "";
        $columns = $db->query("DESCRIBE $table");

        while ($row = $columns->fetch_array(MYSQLI_ASSOC)) {
            foreach($query as $key => $value) {
                //Check if the column field same as the key of data from client side
                if ($key != "sqlType" && $key == $row['Field']) {
                    $colTitle .= $key.', ';
                    $length = preg_replace("/[^0-9]/", "", $row['Type']);

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
                        //Check if string type
                        } else if (strlen($value) > $length) {
                            $msg[] = "Please insert your input less than $length";
                            continue;
                        }

                        //Check if the column is primary and unique key
                        if (($row['Key'] != "PRI" && $row['Key'] != "UNI") || $queryType == "UPDATE") {
                            $msg[] = "Validated";
                            $colField .= "'".$value."', ";
                            $updateValue .= "$key = '".$value."', ";
                            continue;
                        }

                        // Check if the data is unique
                        $exists = false;
                        foreach ($data as $colVal) {
                            if ($colVal[$key] == $value) {
                                $msg[] = "The data already exists. Please insert different data.";
                                $exists = true;
                                break;
                            }
                        }
                        if ($exists) continue;
                    }

                    // Store the data if there are no validation errors
                    $msg[] = "Validated";
                    $colField .= "'".$value."', ";
                    $updateValue .= "$key = '".$value."', ";
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
            $priKey = $_COOKIE['priKey'];
            $priVal = $query->priVal;

            if ($priKey == null) {
                $result = $db->query($selectQuery);
                while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                    $selectedData[] = $row;
                }
                foreach ($selectedData[0] as $key => $value) {
                    $priKey = $key;
                    break;
                }
            }

            if ($queryMode == true) {
                $db->query("UPDATE $table SET $updateValue WHERE $priKey='$priVal'");
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

    function handleDelete($db, $query) {
        $table = $_SESSION['table'];
        $deleteValue = "";
        $result = $db->query("DESCRIBE $table");

        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            foreach ($query as $key => $value) {
                if ($key != "sqlType" && $key == $row['Field'] && $value != null)
                    $deleteValue .= "$key = '$value' AND ";
            }
        }

        // Remove trailing 'AND' from prepared SQL statement
        $deleteValue = substr(trim($deleteValue), 0, -3);

        $db->query("DELETE FROM $table WHERE $deleteValue");

        print "Success";
    }
?>
