<!DOCTYPE html>

<!-- data-ng-app="pigeon-table" in the html is essential to inject ngPigeon-table into the webpage-->
<html lang="en">
<head>
    <title>Example</title>
	<!-- The includes.php file is required to include all necessary dependencies-->
    <?php
		include "pigeon-table/php/includes.php"
	?>
    
</head> 

<body>
    
    <div class="container">
        <!-- View Data in table form -->
        <pigeon-table query="SELECT * FROM dummy" editable="true" control="true"></pigeon-table>
        
    </div>
    
</body>
</html>

