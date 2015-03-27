function PhoneListCtrl($scope /*, $http*/) {
    $scope.phones = [
        {"name": "Nexus S",
         "snippet": "Fast just got faster with Nexus S.",
         "age": 3,
         "id": 0},
        {"name": "Motorola XOOM with Wi-Fi",
         "snippet": "The Next, Next Generation tablet.",
         "age": 0,
         "id": 1},
        {"name": "Galaxy S3",
         "snippet": "Hey, I have that phone theres!",
         "age": 1,
         "id": 2}
    ];
    
    $scope.orderProp = 'age';
    
    /*
    $http.get('url').success(function(data) {
        $scope.phones = data;
    });
     */
}


function PhoneDetailCtrl ($scope, $routeParams) {
    $scope.phoneId = $routeParams.phoneId;
}

//PhoneDetailCtrl.$inject = ['$scope', '$routeParams'];