# pigeon-table
Tabular data display, sortable, searchable, excludable, CRUD operation and retrieve data from MySQL Database by using MySQL Query Command.

# Basic Setup
The first step is to copy this tool into your project root directory. After that, inject module name *data-ng-app="pigeon-table"* into HTML tag.
<br />
![Module Name](https://image.ibb.co/kjgRLQ/module_Name.png)

Make sure you are including the Bootstrap and Pigeon Table CSS file, as well as the Pigeon Table, jQuery, Bootstrap and AngularJS JavaScript files, in the <head> of your project. jQuery, Bootstrap and AngularJS must be loaded before Pigeon Table JavaScript.
<br />
![Link CSS and JS](https://preview.ibb.co/fA0Tjk/Untitled.png)

If your website is running on PHP, you can insert the "includes.php" file into your PHP project instead of insert CSS and JS file one by one. The "includes.php" file is located in "pigeon-table/php/includes.php"
![Link includes.php](https://preview.ibb.co/j239qQ/include_PHP.png)

*Note: If you have Bootstrap and AngularJS framework in your project before, make sure your Bootstrap is running on v3.3.7 and AngularJS is running on v1.6.4 for the best experience.

Configure your MySQL hostname, username, password and the database in the "pigeon-table/configdb.php". This PHP must be configured properly in order to communicate to the MySQL server.
<br />
![Configure MySQL Server](https://image.ibb.co/hjZcc5/configdb.png)

# Include pigeon-table HTML tag
In order to display data in tabular form, you are required to insert the MySQL query command to retrieve the data from the database. Include the pigeon-table HTML tag and specify MySQL SELECT query into query attribute. Pigeon table support simple CRUD operation. If you want to use the CRUD operation, you may specify the editable attribute as "true". 
![Pigeon Table HTML Tag](https://preview.ibb.co/c7KwLQ/pigeon_table_tag.png)

*Note: The CRUD operation only support for single MySQL table. If aggregate table and join table are detected, the CRUD operation will be disabled.

# Tabular Display
The data will be displayed in table form. The table is styled with Bootstrap table template. The table is created with dynamic rows and columns.
<br />
![Tabular View](https://preview.ibb.co/iDyLEk/tabular_view.png)

# Sortable
The data can be sorted to ascending or descending order. You can change the order by click on the title of the column in the table.
<br />
![sortable](https://preview.ibb.co/miNxuk/sortable.png)

# Searchable
The data can be filtered by entering the data you want to search along with the column you want to filter with.
![searchable](https://preview.ibb.co/mBTbn5/searchable.png)

# Excludable
The data can be excluded by entering the data you want to exclude along with the column you want to exclude with.
![excludable](https://preview.ibb.co/dLcQfQ/exludable.png)

# Insert (CRUD Operation)
You can insert the data into the MySQL table that is specified in the query attribute. The input field of the insertion is based on the columns that are specified in the SELECT query. The data will be validated before insert into the table. The validation is based on the MySQL table column's type.
![insertion](https://preview.ibb.co/gVDWLQ/insertion.png)

# Edit (CRUD Operation)
You can edit the data of every row. By default, the primary key input field is uneditable. The input field of the edit form is based on the columns that are specified in the SELECT query. The validation is based on the MySQL table column's type.
![edit](https://preview.ibb.co/fXkiZk/edit.png)

# Delete (CRUD Operation)
You can delete the data of each row by clicking on the delete button. A delete confirmation box will pop out before perform deletion.
<br />
![Deletion](https://preview.ibb.co/bNTqfQ/deletion.png)

*Note: For best experience of CRUD operation, make sure your MySQL table contains primary key.
