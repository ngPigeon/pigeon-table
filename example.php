<?php

echo "
<!DOCTYPE html>
<html lang='en' data-ng-app='pigeon-table' data-ng-cloak>
<head>
    <title>Example</title>";
    
    include "pigeon-table/php/includes.php";
    
echo "</head>";  
echo"
<body>
    
    <div class='container'>
        
        <!-- View Data in table form -->
        <pigeon-table query='SELECT * FROM table_name' editable='true / false'></pigeon-table>
        
    </div>
    
</body>
</html>";

?>