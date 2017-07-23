/*global angular, console, $, alert, jQuery*/
/*jslint vars: true*/
/*jslint plusplus: true*/

var app = angular.module("pigeon-table", ['ngCookies']);

app.filter("offset", function () {
    "use strict";
    return function (input, start) {
        return input.slice(start);
    };
});

app.filter("offsetPageBtn", function () {
    "use strict";
    return function (input, start) {
        if (start <= 2) {
            return input;
        } else if (start > 2) {
            return input.slice((start + 1) - 3);
        }
    };
});

app.directive("pigeonTable", function ($parse, $http, $cookies) {
    "use strict";
    var direc = {};
    direc.restrict = "E";
    
    direc.controller = "myCtrl";
    
    direc.scope = {
        query : "@",
        editable: "=editable"
    };
    
    
    direc.compile = function () {
        var linkFunction = function (scope, element, attributes) {
            
            if (scope.query.includes("SELECT")) {
                $http.post("pigeon-table/php/sql_query.php", {'sql': scope.query})
                    .then(function (response) {
                        //if returned data is string form which is error message, execute this
                        if ((typeof response.data) === "string") {
                            scope.msg = response.data;
                            scope.error = true;
                        } else {
                            scope.data = response.data.data;
                            scope.keyTable = response.data.keyTable;
                            scope.error = false;
                        }
                    
                        scope.displayRow = "10";
                    
                        if (scope.data.length <= 5) {
                            scope.displayRow = "5";
                        }
                    
                        //get the total number of property in the selected object
                        scope.colNum = Object.keys(scope.data[0]).length;
                    
                        //order by first column when page is loaded.
                        scope.orderBy(Object.keys(scope.data[0])[0]);
                    
                        //Set form object for insert purpose
                        scope.form = {};
                        var i;
                        for (i = 0; i < Object.keys(scope.data[0]).length; i++) {
                            var fieldName = Object.keys(scope.data[0])[i];
                            scope.form[fieldName] = "";
                        }
                    
                    });
            } else {
                scope.msg = "SQL2Table.js only accept SELECT query only";
                scope.error = true;
            }
            
            //By default, the table is not editable.
            scope.btn = false;
            
            //Replace the value if the user is specified the value of editable
            if (scope.editable !== undefined) {
                scope.btn = scope.editable;
            }
            
            //Set table as readonly if aggregrate function of table is detected
            if (scope.query.includes("GROUP BY") || scope.query.includes("JOIN")) {
                scope.btn = false;
            }
            
            var reverse = false;

            scope.search = {};
            scope.exclude = {};
            scope.col = "$";
            scope.excludeCol = "$";
            
            //Clear search input field when the filter is chosen
            scope.clearSearch = function () {
                scope.search = {};
            };
            
            //Clear exclude inpput field when the filter is chosen
            scope.clearExclude = function () {
                scope.exclude = {};
            };
            
            scope.updateRow = function (item) {
                if (item === "all") {
                    scope.rowPerPage = scope.data.length;
                } else {
                    scope.rowPerPage = item;
                }
            };
            
            //Update the value of column
            scope.updateCol = function (column) {
                scope.excludeCol = column;
            };
            
            //Filter Exclusion
            scope.exclusion = function (text) {
                
                var k;
                var num;
                for (k in text) {
                    
                    num = "";
                    
                    //If the selected is integer, convert integer to string form
                    if (typeof text[k] === "number") {
                        num = text[k].toString();
                    }
                    
                    
                    ///THIS IS THE PART WE DISCUSSED AND ADDED 6 July
                    if (scope.exclude[scope.excludeCol] === "") {
                        return true;
                    }
                    
                    
                    //If it is exclude by All and included, hide the data
                    if (num) {
                        if (scope.excludeCol === "$" && num.includes(scope.exclude[scope.excludeCol])) {
                            return false;
                        }
                    } else {
                        if (scope.exclude[scope.excludeCol] !== undefined) {
                            if (scope.excludeCol === "$" && text[k].toLowerCase().includes(scope.exclude[scope.excludeCol].toLowerCase())) {
                                return false;
                            }
                        }
                        
                    }
                }
                
                //Show all data when page is loaded.
                if (text[scope.excludeCol] === undefined) {
                    return true;
                }
                
                //Hide the data if exclusion is included
                if (typeof text[scope.excludeCol] === "number") {
                    if (text[scope.excludeCol].toString().includes(scope.exclude[scope.excludeCol])) {
                        return false;
                    }
                } else {
                    if (scope.exclude[scope.excludeCol] !== undefined) {
                        if (text[scope.excludeCol].toLowerCase().includes(scope.exclude[scope.excludeCol].toLowerCase())) {
                            return false;
                        }
                    }
                    
                }
                
                return true;
                
            };
            
            scope.editBtn = function (user) {
                if (user[$cookies.get('priKey')] === undefined) {
                    scope.priVal = user[Object.keys(user)[0]];
                } else {
                    scope.priVal = user[$cookies.get('priKey')];
                }
                scope.editData = user;
                scope.selectedData = {};
                scope.selectedData = jQuery.extend(true, {}, user);
            };
            
            scope.deleteBtn = function (user) {
                scope.selectedData = {};
                scope.selectedData = user;
            };
            
            scope.insert = function (form, sqlType) {
                var newForm = jQuery.extend(true, {}, form);
                newForm.sqlType = sqlType;
                newForm.priVal = scope.priVal;
                $http.post("pigeon-table/php/sql_query.php", JSON.stringify(newForm))
                    .then(function (response) {
                        scope.existed = "";
                        if (response.data === "Inserted") {
                            var i;
                            //Convert to integer if the column is integer form.
                            for (i = 0; i < Object.keys(scope.data[0]).length; i++) {
                                var keyName = Object.keys(form)[i];
                                if (typeof scope.data[0][keyName] === "number") {
                                    form[keyName] = parseInt(form[keyName], 10);
                                }
                            }
                            scope.data.push(form);
                            //order by first column when page is loaded.
                            reverse = false;
                            scope.orderBy(Object.keys(scope.data[0])[0]);
                            $('#insertModal').modal('hide');
                        } else if (response.data === "Updated") {
                            scope.data[scope.data.indexOf(scope.editData)] = form;
                            $('#editModal').modal('hide');
                        } else if (typeof response.data === "string") {
                            scope.existed = response.data;
                        } else {
                            scope.validateMsg = response.data;
                        }
                    });
            };
            
            scope.del = function (item, sqlType) {
                var newItem = jQuery.extend(true, {}, item);
                newItem.sqlType = sqlType;
                $http.post("pigeon-table/php/sql_query.php", JSON.stringify(newItem))
                    .then(function (response) {
                        scope.data.splice(scope.data.indexOf(item), 1);
                        $('#deleteModal').modal('hide');
                    });
            };
            
            scope.orderBy = function (key) {
                
                //Set all column to caret full
                $('.table-header > span > img').attr("class", "caret-full");
                $('.table-header > span > img').attr("src", "pigeon-table/images/caret_full.png");
                
                //Ascending Order
                if (reverse === false) {
                    if (key.includes(" ")) {
                        scope.myOrderBy = '"' + key + '"';
                    } else {
                        scope.myOrderBy = key;
                    }
                    
                    //Set header caret to up.
                    $('#' + key + ' > a > span > img').attr("class", "caret-up");
                    $('#' + key + ' > a > span > img').attr("src", "pigeon-table/images/caret_up.png");
                    
                    reverse = true;
                    //Array sorting
                    scope.data.sort(function (a, b) {
                        if ((typeof scope.data[0][key]) === "number") {
                            return a[key] - b[key];
                        } else {
                            var nameA = a[key].toLowerCase(); // ignore upper and lowercase
                            var nameB = b[key].toLowerCase(); // ignore upper and lowercase
                            if (nameA < nameB) {
                                return -1;
                            }

                            if (nameA > nameB) {
                                return 1;
                            }

                            // names must be equal
                            return 0;
                        }
                    });
                } else {
                    if (key.includes(" ")) {
                        scope.myOrderBy = '"-' + key + '"';
                    } else {
                        scope.myOrderBy = "-" + key;
                    }
                    
                    //Set header caret to down.
                    $('#' + key + ' > a > span > img').attr("class", "caret-down");
                    $('#' + key + ' > a > span > img').attr("src", "pigeon-table/images/caret_down.png");
                    
                    reverse = false;
                    //Array sorting
                    scope.data.sort(function (a, b) {
                        if ((typeof scope.data[0][key]) === "number") {
                            return b[key] - a[key];
                        } else {
                            var nameA = a[key].toLowerCase(); // ignore upper and lowercase
                            var nameB = b[key].toLowerCase(); // ignore upper and lowercase

                            if (nameB < nameA) {
                                return -1;
                            }

                            if (nameB > nameA) {
                                return 1;
                            }

                            // names must be equal
                            return 0;
                        }
                    });
                }
            };
            
            //Default Row Per Page
            scope.rowPerPage = 10;
            
            scope.currentPage = 0;

            //Count how many pages needed to display all data
            scope.pageCount = function () {
                scope.numOfRow = Math.ceil(scope.data.length / scope.rowPerPage) - 1;
            };
            
            scope.filter = function (filtered) {
                scope.numOfRow = Math.ceil(filtered.length / scope.rowPerPage) - 1;
                
                scope.range();
            };
            
            //Setting number for pagination button to be display
            scope.range = function () {
                var rangeSize = scope.numOfRow + 1;
                scope.numForPagiBtns = [];
                var start = scope.currentPage;
                var i;

                // if 0 > -1
                // -1 + 1 will store to "start"
                if (start > scope.numOfRow - rangeSize) {
                    start = scope.numOfRow - rangeSize + 1;
                }

                //loop
                for (i = start; i < start + rangeSize; i++) {
                    scope.numForPagiBtns.push(i);
                }
                
            };

            //When the page number is clicked, set the number as the current page
            scope.setPage = function (n) {
                scope.currentPage = n;
            };

            //Decrease the current page number by 1 when previous button is clicked
            scope.prevPage = function () {
                if (scope.currentPage > 0) {
                    scope.currentPage--;
                }
            };

            //Set the current page to the first page.
            scope.firstPage = function () {
                scope.currentPage = 0;
            };
            
            //Disable the previous button if the current page is 0 (html view is 1)
            scope.prevPageDisabled = function () {
                return scope.currentPage === 0 ? "disabled" : "";
            };

            //Increase the current page number by 1 when next button is pressed.
            scope.nextPage = function () {
                if (scope.currentPage < scope.numOfRow) {
                    scope.currentPage++;
                }
            };

            //Set the current page to the last page.
            scope.lastPage = function () {
                scope.currentPage = scope.numForPagiBtns.length - 1;
            };
            
            //Disable the next button if the current page is 4 (html view is 5)
            scope.nextPageDisabled = function () {
                return scope.currentPage === scope.numOfRow ? "disabled" : "";
            };
            
        };
        
        return linkFunction;
    };
    
    direc.templateUrl = 'pigeon-table/template/outputTemplate.html';
    
    return direc;
});

app.controller("myCtrl", function ($scope) {
    "use strict";
});