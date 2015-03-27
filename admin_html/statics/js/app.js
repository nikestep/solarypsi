angular.module ('phonecat', []).config (['$routeProvider', function ($routeProvider) {
    $routeProvider.when ('/phones', {
        templateUrl: 'statics/partials/phone-list.html',
        controller: PhoneListCtrl
    })
    .when ('/phones/:phoneId', {
        templateUrl: 'statics/partials/phone-detail.html',
        controller: PhoneDetailCtrl
    })
    .otherwise ({
        redirectTo: '/phones'
    });
}]);