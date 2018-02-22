/*global angular, console, $, alert, jQuery*/
/*jslint vars: true*/
/*jslint plusplus: true*/

var app = angular.module("pigeon-table", ['ui.bootstrap']);

app.filter("offset", function () {
    "use strict";
    return function (input, start) {
        return input.slice(start);
    };
});

app.directive("pigeonTable", function ($parse, $http) {
    "use strict";
    var direc = {};
    direc.restrict = "E";

    direc.scope = {
        query : "@",
        editable: "=editable",
        control: "=control"
    };
    
    direc.controller = "pigeonTable";

    direc.templateUrl = 'pigeon-table/template/outputTemplate.html';
    
    direc.compile = function () {
        var linkFunction = function (scope, element, attributes) {
            
            //Initialize the default settings
            scope.init();
            
        };

        return linkFunction;
    };

    return direc;
});

app.controller("pigeonTable", function ($scope, $http, $uibModal) {
    "use strict";

    $scope.init = function () {
        //Data Driven Settings
        $scope.isLoading = true;
        $scope.error = false;
        $scope.firstExecuted = false;

        //Filter Settings
        $scope.col = "$";
        $scope.excludeCol = "$";
        $scope.search = {};
        $scope.exclude = {$: ""};

        //Pagination Settings
        $scope.currentPage = 1;
        $scope.itemsPerPage = "10";

        //Sorting Order Setting
        $scope.isReverse = false;

        //Panel Settings
        $scope.btn = false;
        $scope.ctrlPanel = true;
        $scope.action = true;

        if ($scope.editable !== undefined) {
            $scope.btn = $scope.editable;
        }

        if ($scope.control !== undefined) {
            $scope.ctrlPanel = $scope.control;
        }

        //Query Validation
        if ($scope.query.toUpperCase().includes("GROUP BY") || $scope.query.toUpperCase().includes("JOIN")) {
            $scope.btn = false;
        }

        $scope.queryValidatation();
    };

    //Validate query and fetch data
    $scope.queryValidatation = function () {
        if ($scope.query.toUpperCase().includes("SELECT")) {
            $scope.getData();
        } else {
            $scope.error = true;
            $scope.errorMsg = "Pigeon Table accepts SELECT query only";
        }
    };

    //Sort the data by using first column (Execute once only)
    $scope.sorting = function () {
        $scope.sortOrder = Object.keys($scope.data[0])[0];
    };

    //Ascending or Descending Order
    $scope.ordering = function (key) {
        if (key !== $scope.sortOrder) {
            $('.table-header > span').attr("class", "caret-full");
            $scope.sortOrder = key;
            $scope.isReverse = false;
        } else {
            $scope.isReverse = !$scope.isReverse;
        }

        if ($scope.isReverse === false) {
            $('#' + key + ' > a > span').attr("class", "caret-up");
        } else {
            $('#' + key + ' > a > span').attr("class", "caret-down");
        }
    };

    //Fetch Data from MySQL
    $scope.getData = function () {
        $scope.isLoading = true;
        $http.post("pigeon-core/get-data-with-crud.php", {'sql': $scope.query})
            .then(function (response) {
                if (typeof response.data.data === 'string') {
                    $scope.error = true;
                    $scope.errorMsg = response.data.data;
                } else {
                    $scope.data = response.data.data;
                    if ($scope.firstExecuted === false) {
                        $scope.sorting();
                        $scope.firstExecuted = true;
                    }
                }
                
                if (response.data.tableStructure !== undefined) {
                    $scope.tableStructure = response.data.tableStructure;
                    if ($scope.tableStructure.indexCol.length === 0) {
                        $scope.action = false;
                    }
                }
            
                $scope.isLoading = false;
            });
    };

    //Initialize Insert Modal
    $scope.insertBtn = function () {

        var modalInstance = $uibModal.open({
            animation: true,
            backdrop: 'static',
            templateUrl: 'insertModal',
            controller: 'InsertModalInstanceCtrl',
            resolve: {
                selectedData: function () {
                    return $scope.data[0];
                },
                tableStructure: function () {
                    return $scope.tableStructure;
                }
            }
        });

        modalInstance.result.then(function (insertedData) {
            var i;
            //Convert to integer if the column is integer form.
            for (i = 0; i < Object.keys($scope.data[0]).length; i++) {
                var keyName = Object.keys(insertedData)[i];
                if (typeof $scope.data[0][keyName] === "number") {
                    insertedData[keyName] = parseInt(insertedData[keyName], 10);
                }
            }
            
            $scope.data.push(insertedData);
        }, function () {
            //cancel
        });
    };

    //Initialize Edit Modal
    $scope.editBtn = function (item) {

        var modalInstance = $uibModal.open({
            animation: true,
            backdrop: 'static',
            templateUrl: 'editModal',
            controller: 'EditModalInstanceCtrl',
            resolve: {
                selectedData: function () {
                    return item;
                },
                tableStructure: function () {
                    return $scope.tableStructure;
                }
            }
        });

        modalInstance.result.then(function (editedData) {
            //success
            $scope.data[$scope.data.indexOf(item)] = editedData;
        }, function () {
            //cancel
        });
    };

    $scope.delBtn = function (item) {
        var modalInstance = $uibModal.open({
            animation: true,
            backdrop: 'static',
            templateUrl: 'deleteModal',
            controller: 'DeleteModalInstanceCtrl',
            resolve: {
                selectedData: function () {
                    return item;
                },
                tableStructure: function () {
                    return $scope.tableStructure;
                }
            }
        });

        modalInstance.result.then(function (deletedData) {
            //delete success
            $scope.data.splice($scope.data.indexOf(deletedData), 1);
        }, function () {
            //cancel
        });
    };

    //Filter Exclusion
    $scope.exclusion = function (data) {

        var col;

        if ($scope.exclude[$scope.excludeCol] === "") {
            return true;
        } else if (typeof data[$scope.excludeCol] === "number") {
            
            if (data[$scope.excludeCol].toString().includes($scope.exclude[$scope.excludeCol])) {
                return false;
            }
            
        } else if (typeof data[$scope.excludeCol] === "string") {
            
            if (data[$scope.excludeCol].toLowerCase().includes($scope.exclude[$scope.excludeCol].toLowerCase())) {
                return false;
            }
            
        } else {
            for (col in data) {
                if (typeof data[col] === "number") {
                    if (data[col].toString().includes($scope.exclude[$scope.excludeCol])) {
                        return false;
                    }
                } else {
                    if (data[col].toLowerCase().includes($scope.exclude[$scope.excludeCol].toLowerCase())) {
                        return false;
                    }
                }
            }
        }

        //Display row data if passes all the exclude validation
        return true;
    };
});

//Controller of Insert Modal
app.controller("InsertModalInstanceCtrl", function ($scope, $http, $uibModalInstance, selectedData, tableStructure) {
    "use strict";

    $scope.modalLoading = false;
    $scope.selectedData = selectedData;
    $scope.form = {};

    var i;
    //Initialize form with empty string in every fields
    for (i = 0; i < Object.keys(selectedData).length; i++) {
        $scope.form[Object.keys(selectedData)[i]] = "";
    }

    $scope.submit = function () {
        $scope.modalLoading = true;

        var form = jQuery.extend(true, {}, $scope.form);
        form.sqlType = "INSERT";
        form.tableStructure = tableStructure;

        $http.post("pigeon-core/get-data-with-crud.php", JSON.stringify(form))
            .then(function (response) {
                if (response.data === "Inserted") {
                    $uibModalInstance.close($scope.form);
                } else {
                    $scope.validateMsg = response.data;
                }
            
                $scope.modalLoading = false;
            });
    };

    $scope.close = function () {
        $uibModalInstance.dismiss();
    };
});

//Controller of Edit Modal
app.controller("EditModalInstanceCtrl", function ($scope, $http, $uibModalInstance, selectedData, tableStructure) {
    "use strict";

    $scope.modalLoading = false;
    $scope.selectedData = {};
    $scope.selectedData = jQuery.extend(true, {}, selectedData);
    $scope.tableStructure = tableStructure;

    $scope.submit = function () {
        $scope.modalLoading = true;
        var form = jQuery.extend(true, {}, $scope.selectedData);
        form.sqlType = "UPDATE";
        form.tableStructure = tableStructure;

        $http.post("pigeon-core/get-data-with-crud.php", JSON.stringify(form))
            .then(function (response) {
                if (response.data === "Updated") {
                    $uibModalInstance.close($scope.selectedData);
                } else {
                    $scope.validateMsg = response.data;
                }
            
                $scope.modalLoading = false;
            });

    };
    
    $scope.close = function () {
        $uibModalInstance.dismiss();
    };
});

//Controller of Delete Modal
app.controller("DeleteModalInstanceCtrl", function ($scope, $http, $uibModalInstance, selectedData, tableStructure) {
    "use strict";

    $scope.modalLoading = false;
    $scope.selectedData = {};
    $scope.selectedData = jQuery.extend(true, {}, selectedData);

    $scope.submit = function () {
        $scope.modalLoading = true;
        var form = jQuery.extend(true, {}, $scope.selectedData);
        form.sqlType = "DELETE";
        form.tableStructure = tableStructure;

        $http.post("pigeon-core/get-data-with-crud.php", JSON.stringify(form))
            .then(function (response) {
                if (response.data === "Deleted") {
                    $uibModalInstance.close($scope.selectedData);
                } else {
                    $scope.validateMsg = response.data;
                }

                $scope.modalLoading = false;
            });

    };

    $scope.close = function () {
        $uibModalInstance.dismiss();
    };
});

//Load pigeon-table angular module when pigeon-table tag is found in document.
angular.bootstrap(document.getElementsByTagName("pigeon-table"), ['pigeon-table']);
