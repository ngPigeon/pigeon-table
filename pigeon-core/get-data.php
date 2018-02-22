<?php

    //Turn off PHP error reporting
    error_reporting(0);

    require "configdb.php";

    $db = new mysqli(HOSTNAME, USERNAME, PASSWORD, DATABASE) or die ("Unable to Connect to the Database");
    $dbName = DATABASE;
    $input = json_decode(file_get_contents("php://input"));

    $queryType = getQueryType($input);
    switch ($queryType) {

        case "SELECT":
            $data['data'] = handleSelect($db, $input->sql);
            if (!is_string($data['data']) && function_exists('getTableStructure'))
                $data['tableStructure'] = getTableStructure($db, $input->sql, $dbName);
            returnData($data);
            break;
        case "INSERT":
        case "UPDATE":
            if (function_exists('handleInsertOrUpdate'))
                handleInsertOrUpdate($db, $input, $queryType);
            break;
        case "DELETE":
            if (function_exists('handleDelete'))
                handleDelete($db, $input);
            break;
        default:
            echo "Wrong SQL Statement";
            break;
    }

    function getQueryType($input) {
        if (isset($input->sql)) {
            if (strpos(strtoupper($input->sql), 'SELECT') !== false)
                return 'SELECT';
        } else {
            return strtoupper($input->sqlType);
        }
    }

    function handleSelect($db, $sql) {

        $rows = $db->query($sql);

        if ($db->connect_error) {
            return $db->connect_error;
        } else if ($db->error) {
            return $db->error;
        } else {
            //Fetch the row data and store into array
            while ($row = $rows->fetch_array(MYSQLI_ASSOC)) {
                $data[] = $row;
            }

            return $data;
        }
    }

    function returnData($data) {
        //Return 2D array back to JavaScript with numeric checking
        print json_encode($data, JSON_NUMERIC_CHECK);
    }
?>
