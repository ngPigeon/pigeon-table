# pigeon-table
Pigeon Table is a tool that used to display out MySQL table data into tabular form dynamically.

# Basic Setup
The first step is to have the right CSS and JavaScript files. Make sure you are including the Bootstrap and Pigeon Table CSS file, as well as the Pigeon Table, jQuery, Bootstrap and AngularJS JavaScript files, in the <head> of your web pages. jQuery, Bootstrap and AngularJS must be loaded before Pigeon Table JavaScript.
![Link CSS and JS](https://preview.ibb.co/fA0Tjk/Untitled.png)

If your website is running on PHP, you can insert the "includes.php" file into your PHP project instead of insert CSS and JS file one by one. The "includes.php" file is located in "pigeon-table/php/includes.php"
![Link includes.php](https://preview.ibb.co/j239qQ/include_PHP.png)

Configure your MySQL hostname, username, password and the database in the "pigeon-table/configdb.php". This PHP must be configured properly in order to communicate to the MySQL server.
![Configure MySQL Server](https://image.ibb.co/hjZcc5/configdb.png)
