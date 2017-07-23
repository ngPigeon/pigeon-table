<!DOCTYPE html>

<!-- data-ng-app="pigeon-table" in the html is essential to inject ngPigeon-table into the webpage-->
<html lang="en" data-ng-app="pigeon-table" data-ng-cloak>
<head>
    <title>Example</title>
	<!-- The includes.php file is required to include all necessary dependencies-->
    <?php
		include "pigeon-table/php/includes.php"
	?>
    
</head> 

<body>
    
    <div class="container">
        <h1>Users</h1>
        <!-- View Data in table form -->
        <pigeon-table query="SELECT * FROM dummy" editable="true"></pigeon-table>
        
    </div>
    
</body>
</html>

