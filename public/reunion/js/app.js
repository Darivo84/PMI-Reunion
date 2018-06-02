// Ionic Starter App

// GLOBAL VARAIBLES
  var app_version = '0.0.1';
  var url = 'https://pmi-reunion-app.optimalonline.co.za/application';
  var checknet = 'online';

// angular.module is a global place for creating, registering and retrieving Angular modules
// 'starter' is the name of this angular module example (also set in a <body> attribute in index.html)
// the 2nd parameter is an array of 'requires'
var app = angular.module('starter', ['ionic','ngCordova','chart.js','ipCookie'])

.run(function($ionicPlatform, $cordovaNetwork, $rootScope) {
  $ionicPlatform.ready(function() {
    if(window.cordova && window.cordova.plugins.Keyboard) {
      // Hide the accessory bar by default (remove this to show the accessory bar above the keyboard
      // for form inputs)
      cordova.plugins.Keyboard.hideKeyboardAccessoryBar(true);

      // Don't remove this line unless you know what you are doing. It stops the viewport
      // from snapping when text inputs are focused. Ionic handles this internally for
      // a much nicer keyboard experience.
      cordova.plugins.Keyboard.disableScroll(true);
    }

    // CHECK CONNECTION
        if(window.Connection){
            // ON STARTUP
                if($cordovaNetwork.isOnline()){
                  checknet = 'online';
                }else if($cordovaNetwork.isOffline()){
                  checknet = 'offline';
                }

            // listen for Online event
                $rootScope.$on('$cordovaNetwork:online', function(event, networkState){
                   checknet = 'online';                        
                });

            // listen for Offline event
                $rootScope.$on('$cordovaNetwork:offline', function(event, networkState){
                    checknet = 'offline';                 
                });          
        } 

    // HIDE STATUSBAR
        if(window.StatusBar) {
          StatusBar.hide();
          ionic.Platform.fullScreen();
        }

    // HIDE SPLASHSCREEN AFTER APP LOAD
      if(navigator.splashscreen){
        setTimeout(function() {
            navigator.splashscreen.hide();
        }, 1000);         
      }
  });
});

// CONFIGS
    // ROUTING
        app.config(function($stateProvider, $urlRouterProvider) {
          $stateProvider

            // CONFIG
                .state('config', {
                    url: "/config",
                    templateUrl: "views/config",
                    controller: 'ConfigCtrl'
                })

            // LOGIN
                .state('login', {
                    url: "/login",
                    templateUrl: "views/login",
                    controller: 'LoginCtrl'
                })

            // LAYOUT
                .state('app', {
                  url: "/app",
                  abstract: true,
                  templateUrl: "views/layout",
                  controller: 'LayoutCtrl'
                })

            // STORES
                .state('app.stores', {
                  url: "/stores",
                  views: {
                    'app': {
                      templateUrl: "views/stores",
                      controller: 'StoresCtrl'
                    }
                  }
                })

            // STORE DETAILS
                .state('app.storeDetails', {
                    url: "/storedetails",
                    views: {
                        'app': {
                            templateUrl: "views/storeDetails",
                            controller: 'StoreDetailsCtrl'
                        }
                    }
                })  

            // PAST RESULTS
                .state('app.pastResults', {
                    url: "/pastresults",
                    views: {
                        'app': {
                            templateUrl: "views/pastResults",
                            controller: 'pastResultsCtrl'
                        }
                    }
                }) 

            // STORE HOME
                .state('app.storeHome', {
                    url: "/storehome",
                    views: {
                        'app': {
                            templateUrl: "views/storeHome",
                            controller: 'storeHomeCtrl'
                        }
                    }
                })                                                

            // STORE QUESTIONS
                .state('app.storeQuestions', {
                    url: "/storequestions",
                    views: {
                        'app': {
                            templateUrl: "views/storeQuestions",
                            controller: 'storeQuestionsCtrl'
                        }
                    }
                }) 

            // STORE SUMMARY
                .state('app.storeSummary', {
                    url: "/storesummary",
                    views: {
                        'app': {
                            templateUrl: "views/storeSummary",
                            controller: 'storeSummaryCtrl'
                        }
                    }
                })       

           $urlRouterProvider.otherwise("/config");
        })

    // APP CONFIG
        .config(function($ionicConfigProvider) {
            // DISABLE SWIPING
                $ionicConfigProvider.views.swipeBackEnabled(false);
        });

// SERVICES
    // POSTING AND REQUESTING SERVICE
        app.service('RequestService', function($http) {
            return {
                post: function(type,postData) {
                    var currentdate = new Date(); 
                    var datetime =  currentdate.getFullYear() +"-"
                            + (currentdate.getMonth()+1) +"-"
                            + currentdate.getDate() + " "
                                    + currentdate.getHours() + ":"  
                                    + currentdate.getMinutes() + ":" 
                                    + currentdate.getSeconds();                         

                    var postData = {type : type, data : postData, datetime : datetime, app_version: app_version}  

                    return request = $http({
                        method: "post",
                        url: url,
                        data: postData,
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
                     });
                }
            }
        })

    // DATA SERVICE
        .service('dataService', function($q, RequestService, ipCookie) {
            return{
                sync : function(){
                    var deferred = $q.defer();
                    var promise = deferred.promise;

                    var details = ipCookie('LOGIN', undefined, {decode: function (value) { return value; }}); 

                    if(ipCookie('SYNC')){
                      var syncData = ipCookie('SYNC', undefined, {decode: function (value) { return value; }});
                    }else{
                      var syncData = 'No Data';
                    } 

                    RequestService.post('sync',{user_id:details.id, data : syncData}).success(function (result) { 
                        if(ipCookie('SYNC'))
                          ipCookie.remove('SYNC');                       
                        if(result['USER_STATUS'] == 'active'){
                          ipCookie('ALL_STORES', result['ALL_STORES'], { expires: 1000, encode: function (value) { return value; } });
                          ipCookie('LIST_STORES', result['LIST_STORES'], { expires: 1000, encode: function (value) { return value; } });
                          ipCookie('WORLD_SCORE', result['WORLD_SCORE'], { expires: 1000, encode: function (value) { return value; } });                         
                        }
                        deferred.resolve(result['USER_STATUS']);                                              
                    }).error(function(){
                        $ionicPopup.show({
                            title: '<div class="popup-pmi-title">Login Failed</div>',
                            template:'<div class="popup-subtitle">Data Request Error</div>',
                            buttons: [
                                {
                                  text: 'OK',
                                  type: 'button-pmi',
                                  onTap: function () {
                                  }
                                }
                            ]
                        });
                    });

                    promise.success = function(fn) {
                        promise.then(fn);
                        return promise;
                    }
                    promise.error = function(fn) {
                        promise.then(null, fn);
                        return promise;
                    }
                    return promise;                                       
                },
                stores : function(){ 
                  return ipCookie('LIST_STORES', undefined, {decode: function (value) { return value; }});
                },
                store : function(ID){ 
                  var DATA = ipCookie('ALL_STORES', undefined, {decode: function (value) { return value; }});
                  return DATA[ID];
                },
                world : function(ID){ 
                  return ipCookie('WORLD_SCORE', undefined, {decode: function (value) { return value; }});
                },
                updateQ : function(NewStats, Store, Month){
                    var deferred = $q.defer();
                    var promise = deferred.promise;

                    if(ipCookie('SYNC')){
                      var syncData = ipCookie('SYNC', undefined, {decode: function (value) { return value; }});
                    }else{
                      var syncData = {};
                    } 
                    if(!syncData[Store])
                      syncData[Store] = {};
                    if(!syncData[Store][Month])
                      syncData[Store][Month] = {};     

                    syncData[Store][Month]['update'] = NewStats

                    var NewData = ipCookie('ALL_STORES', undefined, {decode: function (value) { return value; }});
                    NewData[Store]['CURRENT_STATS'][Month] = NewStats;

                    ipCookie('ALL_STORES', NewData, { expires: 1000, encode: function (value) { return value; } });
                    ipCookie('SYNC', syncData, { expires: 1000, encode: function (value) { return value; } });
                    deferred.resolve('1'); 

                    promise.success = function(fn) {
                        promise.then(fn);
                        return promise;
                    }
                    promise.error = function(fn) {
                        promise.then(null, fn);
                        return promise;
                    }
                    return promise;                                       
                },
                start : function(ID){

                    var currentdate = new Date(); 
                    var currentMonth = currentdate.getMonth()+1; 

                    var StartingObj = {
                      1 :{
                        V1A : null,
                        V2A : null,
                        V3A : null,
                        V4A : null
                      },
                      2 :{
                        V1A : null,
                        V2A : null,
                        V3A : null,
                        V4A : null
                      },
                      3 :{
                        V1A : null,
                        V2A : null,
                        V3A : null,
                        V4A : null
                      },
                      4 :{
                        V1A : null,
                        V2A : null,
                        V3A : null,
                        V4A : null
                      },
                      5 :{
                        V1A : null,
                        V2A : null,
                        V3A : null,
                        V4A : null
                      },
                      6 :{
                        V1A : null,
                        V2A : null,
                        V3A : null,
                        V4A : null
                      },
                      7 :{
                        V1A : null,
                        V2A : null,
                        V3A : null,
                        V4A : null
                      },
                      8 :{
                        V1A : null,
                        V2A : null,
                        V3A : null,
                        V4A : null
                      },
                      9 :{
                        V1A : null,
                        V2A : null,
                        V3A : null,
                        V4A : null
                      },
                      10 :{
                        V1A : null,
                        V2A : null,
                        V3A : null,
                        V4A : null
                      },
                      11 :{
                        V1A : null,
                        V2A : null,
                        V3A : null,
                        V4A : null
                      }
                    }

                    if(ipCookie('SYNC')){
                      var syncData = ipCookie('SYNC', undefined, {decode: function (value) { return value; }});
                    }else{
                      var syncData = {};
                    } 
                    if(!syncData[ID])
                      syncData[ID] = {};
                    if(!syncData[ID][currentMonth])
                      syncData[ID][currentMonth] = {};     
                    
                    syncData[ID][currentMonth]['start'] = '1';                    

                    var NewData = ipCookie('ALL_STORES', undefined, {decode: function (value) { return value; }});
                    NewData[ID]['CURRENT_STATS'] = { };
                    NewData[ID]['CURRENT_STATS'][currentMonth] = StartingObj;
                    NewData[ID]['CALL_STATUS'] = "continue";

                    ipCookie('ALL_STORES', NewData, { expires: 1000, encode: function (value) { return value; } });
                    ipCookie('SYNC', syncData, { expires: 1000, encode: function (value) { return value; } });
                    return NewData[ID]; 
                },
                complete : function(ID, Month){
                    var deferred = $q.defer();
                    var promise = deferred.promise;

                    var currentdate = new Date(); 
                    var currentMonth = currentdate.getMonth()+1;                    

                    if(ipCookie('SYNC')){
                      var syncData = ipCookie('SYNC', undefined, {decode: function (value) { return value; }});
                    }else{
                      var syncData = {};
                    } 
                    if(!syncData[ID])
                      syncData[ID] = {};
                    if(!syncData[ID][Month])
                      syncData[ID][Month] = {};     
                    
                    syncData[ID][Month]['completed'] = '1';   

                    var NewData = ipCookie('ALL_STORES', undefined, {decode: function (value) { return value; }});
                    NewData[ID]['PAST_STATS'][Month] = NewData[ID]['CURRENT_STATS'][Month];
                    NewData[ID]['CURRENT_STATS'] = [];

                    if(currentMonth > Month){
                      NewData[ID]['CALL_STATUS'] = "start";
                    }else{
                      NewData[ID]['CALL_STATUS'] = "completed";
                    }

                    ipCookie('ALL_STORES', NewData, { expires: 1000, encode: function (value) { return value; } });
                    ipCookie('SYNC', syncData, { expires: 1000, encode: function (value) { return value; } });
                    deferred.resolve(NewData[ID]); 

                    promise.success = function(fn) {
                        promise.then(fn);
                        return promise;
                    }
                    promise.error = function(fn) {
                        promise.then(null, fn);
                        return promise;
                    }
                    return promise; 
                }
            }
        }) 

    // LOADER SERVICE
        .service('LoaderService', function($ionicLoading) {
            return{
                showLoading : function(text){
                    text = '<ion-spinner icon="lines" class="spinner-pmi"></ion-spinner><br />' + text || '<ion-spinner icon="lines"></ion-spinner>';
                    $ionicLoading.show({
                        template: text,
                    });                    
                },
                hideLoading : function(){
                   $ionicLoading.hide(); 
                }
            }
        });        

// PAGE CONTROLLERS
    // CONFIG SETUP
        app.controller('ConfigCtrl', function($ionicPlatform, $scope, $state, $ionicPopup, ipCookie, dataService){
            $ionicPlatform.ready(function() {
              if(ipCookie('LOGIN')){
                $scope.user = ipCookie('LOGIN', undefined, {decode: function (value) { return value; }});
                $scope.feedback = 'Welcome ' + $scope.user.name + ", Loading Stores...";
                if(checknet == 'online'){
                  dataService.sync().success(function (result) { 
                      if(result != 'active'){
                        ipCookie.remove('LOGIN'); 
                        $ionicPopup.show({
                            title: '<div class="popup-pmi-title">Auto Login Failed</div>',
                            template:'<div class="popup-subtitle">Incorrect login details</div>',
                            buttons: [
                                {
                                  text: 'To login page',
                                  onTap: function () {
                                    $state.go('login');
                                  }
                                }
                            ]
                        });   
                      }else{
                        $state.go('app.stores'); 
                      }                                     
                  }).error(function(){
                      ipCookie.remove('LOGIN'); 
                      $ionicPopup.show({
                          title: '<div class="popup-pmi-title">Auto Login Failed</div>',
                          template:'<div class="popup-subtitle">Data Error</div>',
                          buttons: [
                              {
                                text: 'To login page',
                                onTap: function () {
                                  $state.go('login');
                                }
                              }
                          ]
                      });
                  });       
                }else{
                  $state.go('app.stores'); 
                }          
              }else{
                $scope.feedback = "Loading App..."; 
                $state.go('login');
              }
            });                                
        })

    // LOGIN
        .controller('LoginCtrl', function($ionicPlatform, $scope, $state, RequestService, $ionicPopup, ipCookie, dataService, LoaderService){
            $ionicPlatform.ready(function() {
                // INPUT DATA
                    $scope.data = {};

                // LOGIN FUNCTION
                    $scope.login = function(){
                        if($scope.data.login_id == '' || $scope.data.login_id == null || $scope.data.password == '' || $scope.data.password == null){
                                $ionicPopup.show({
                                    title: '<div class="popup-pmi-title">Login Failed</div>',
                                    template:'<div class="popup-subtitle">Please fill in all the fields.</div>',
                                    buttons: [
                                        {
                                          text: 'OK',
                                          onTap: function () {
                                          }
                                        }
                                    ]
                                });
                        }else{
                          // LOGIN REQUEST
                            LoaderService.showLoading('Checking credentials');
                            RequestService.post('login',{login_id:$scope.data.login_id.toLowerCase(),password:$scope.data.password}).success(function (result) {
                                if(result != 'failed'){
                                  ipCookie('LOGIN', {id:result['id'],name:result['first_name']}, { expires: 1000, encode: function (value) { return value; } });                                  
                                  LoaderService.showLoading('Updating data');                                 
                                  dataService.sync().success(function (result) { 
                                      LoaderService.hideLoading();
                                      if(result != 'active'){
                                        ipCookie.remove('LOGIN'); 
                                        $ionicPopup.show({
                                            title: '<div class="popup-pmi-title">Login Failed</div>',
                                            template:'<div class="popup-subtitle">Incorrect login details</div>',
                                            buttons: [
                                                {
                                                  text: 'OK',
                                                  type: 'button-pmi',
                                                  onTap: function () {
                                                  }
                                                }
                                            ]
                                        });   
                                      }else{
                                        $state.go('app.stores'); 
                                      }                                     
                                  }).error(function(){
                                      $ionicPopup.show({
                                          title: '<div class="popup-pmi-title">Login Failed</div>',
                                          template:'<div class="popup-subtitle">Data Error</div>',
                                          buttons: [
                                              {
                                                text: 'OK',
                                                type: 'button-pmi',
                                                onTap: function () {
                                                }
                                              }
                                          ]
                                      });
                                  });                                  
                                }else{
                                  LoaderService.hideLoading();
                                  $ionicPopup.show({
                                      title: '<div class="popup-pmi-title">Login Failed</div>',
                                      template:'<div class="popup-subtitle">Incorrect login details</div>',
                                      buttons: [
                                          {
                                            text: 'OK',
                                            type: 'button-pmi',
                                            onTap: function () {
                                            }
                                          }
                                      ]
                                  });                                  
                                }
                            }).error(function(){
                                LoaderService.hideLoading();
                                $ionicPopup.show({
                                    title: '<div class="popup-pmi-title">Login Failed</div>',
                                    template:'<div class="popup-subtitle">An Unknown error occured</div>',
                                    buttons: [
                                        {
                                          text: 'OK',
                                          type: 'button-pmi',
                                          onTap: function () {
                                          }
                                        }
                                    ]
                                });
                            });
                        }
                    }
            });
        })              

    // LAYOUT
        .controller('LayoutCtrl', function($ionicPlatform, $scope, $state, LoaderService, $ionicPopup, ipCookie, $rootScope, dataService){
            $ionicPlatform.ready(function() {
              $scope.layout = {
                    breadcrumbs : [],
                    store :{
                      name : '',
                      id : '',
                      month : '',
                      current_stats : '',
                      past_stats : '',
                      call_status : ''
                    },
                    user : '',
                    world : ''
              };
              $scope.netstate = checknet; 

              $scope.$on('$ionicView.beforeEnter', function() {
                $scope.layout.user =  ipCookie('LOGIN', undefined, {decode: function (value) { return value; }});                
              });

              if(window.Connection){
                // listen for Online event
                    $rootScope.$on('$cordovaNetwork:online', function(event, networkState){
                       $scope.netstate = 'online';
                       LoaderService.showLoading('Synching data');
                       dataService.sync().success(function (result) { 
                            if(result != 'active'){
                              ipCookie.remove('LOGIN'); 
                              $ionicPopup.show({
                                  title: '<div class="popup-pmi-title">Auto Login Failed</div>',
                                  template:'<div class="popup-subtitle">Incorrect login details</div>',
                                  buttons: [
                                      {
                                        text: 'To login page',
                                        onTap: function () {
                                          $state.go('login');
                                        }
                                      }
                                  ]
                              });   
                            }
                            LoaderService.hideLoading();                                   
                        }).error(function(){
                            ipCookie.remove('LOGIN'); 
                            LoaderService.hideLoading();
                            $ionicPopup.show({
                                title: '<div class="popup-pmi-title">Auto Login Failed</div>',
                                template:'<div class="popup-subtitle">Data Error</div>',
                                buttons: [
                                    {
                                      text: 'To login page',
                                      onTap: function () {
                                        $state.go('login');
                                      }
                                    }
                                ]
                            });
                        });                                                
                    });

                // listen for Offline event
                    $rootScope.$on('$cordovaNetwork:offline', function(event, networkState){
                        $scope.netstate = 'offline';                
                    });              
              }

              $scope.crumbGo = function(A){
                if(A != '')
                  $state.go(A);
              }

              $scope.logout = function(){
                  $ionicPopup.show({
                      title: '<div class="popup-pmi-title">Are you sure you want to sign out?</div>',
                      buttons: [
                         {
                            text: 'Yes',
                            type: 'button-pmi',
                            onTap: function () {
                                $state.go('login');
                                ipCookie.remove('LOGIN'); 
                                ipCookie.remove('ALL_STORES'); 
                                ipCookie.remove('LIST_STORES'); 
                                ipCookie.remove('WORLD_SCORE'); 
                                ipCookie.remove('SYNCH'); 
                            }
                          },
                          {
                            text: 'No',
                            type: 'button-pmi',
                            onTap: function () {
                               
                            }
                          }                              
                      ]
                  });                   
              }                
            });          
        }) 

    // STORES
        .controller('StoresCtrl', function($ionicPlatform, $scope, $state, dataService , $ionicPopup, LoaderService){
            $ionicPlatform.ready(function() {
              $scope.$on('$ionicView.beforeEnter', function() {                
                LoaderService.showLoading('Loading...');  
                dataService.sync().success(function (result) { 
                    LoaderService.hideLoading();
                    if(result != 'active'){
                      ipCookie.remove('LOGIN'); 
                      $ionicPopup.show({
                          title: '<div class="popup-pmi-title">Login Failed</div>',
                          template:'<div class="popup-subtitle">Incorrect login details</div>',
                          buttons: [
                              {
                                text: 'OK',
                                type: 'button-pmi',
                                onTap: function () {
                                }
                              }
                          ]
                      });   
                    }  

                    $scope.layout.breadcrumbs = [];
                    $scope.stores = [];
                    var objStores = dataService.stores();
                    angular.forEach(objStores,function(value, key) {
                        var tempArrStores = { id : key, name : value};
                        $scope.stores.push(tempArrStores);
                    });                       
                    $scope.layout.store = {
                      name : '',
                      id : ''
                    }                                                       
                }).error(function(){
                    $ionicPopup.show({
                        title: '<div class="popup-pmi-title">Layout Sync Failed</div>',
                        template:'<div class="popup-subtitle">Data Error</div>',
                        buttons: [
                            {
                              text: 'OK',
                              type: 'button-pmi',
                              onTap: function () {
                              }
                            }
                        ]
                    });
                });                             
              });

              // CHOOSE STORE
                $scope.selectStore = function(A, B){
                  var StoreData = dataService.store(B);
                  $scope.layout.store = {
                    name : A,
                    id : B,
                    month : '',
                    current_stats : StoreData['CURRENT_STATS'],
                    past_stats : StoreData['PAST_STATS'],
                    call_status : StoreData['CALL_STATUS']
                  }

                  $scope.layout.world = dataService.world();

                  $state.go('app.storeDetails');
                }
            });
        })

    // STORE DETAILS
        .controller('StoreDetailsCtrl', function($ionicPlatform, $scope, $state, dataService, LoaderService){
            $ionicPlatform.ready(function() {
              $scope.$on('$ionicView.beforeEnter', function() {
                $scope.layout.breadcrumbs = [
                  {title:'Back to Stores',link:'app.stores'},
                  {title:$scope.layout.store.name,link:''},
                  {title:'Performance Dimensions',link:''}
                ]; 
                  $scope.store = $scope.layout.store;     

              // CHECK CURRENT MONTH
                var dateFunc = new Date(); 
                $scope.month = dateFunc.getMonth()+1;

                angular.forEach($scope.layout.store.current_stats,function(value, key) {
                    $scope.Statmonth = key;
                });                

              // PAST STATS
                var pastData = Array();
                var worldData = '';               
                if((Array.isArray($scope.layout.store.past_stats) && $scope.layout.store.past_stats.length < 1) || Object.keys($scope.layout.store.past_stats).length < 1){
                  pastData = ['0','0','0','0','0','0','0','0','0','0','0'];
                  worldData = ['0','0','0','0','0','0','0','0','0','0','0'];
                  $scope.emptyGraph = '0';
                }else{
                  $scope.emptyGraph = '1';
                  worldData = $scope.layout.world;
                  angular.forEach($scope.layout.store.past_stats,function(monthData, month) {
                    angular.forEach(monthData,function(QuestionData, Question) {
                      var V1P1 = (QuestionData.V1A == 'yes') ? 1 : 0 ;
                      var V2P1 = (QuestionData.V2A == 'yes') ? 1 : 0 ;
                      var V3P1 = (QuestionData.V3A == 'yes') ? 1 : 0 ;
                      var V4P1 = (QuestionData.V4A == 'yes') ? 1 : 0 ;
                      if(pastData[Question - 1]){
                        pastData[Question - 1] = parseInt(pastData[Question - 1]) +  V1P1 + V2P1 + V3P1 + V4P1; 
                      }else{
                        pastData[Question - 1] = V1P1 + V2P1 + V3P1 + V4P1; 
                      }
                    });
                  });     
                }  

                // TOTAL POINTS
                  $scope.totalPoints = 0;
                  angular.forEach(pastData,function(value, key) {
                    $scope.totalPoints += parseInt(value);
                  });  

              // GRAPH  
                $scope.graph = {};
                $scope.graph.labels = ['1','2','3','4','5','6','7','8','9','10','11'];
                $scope.graph.data = [
                    pastData,
                    worldData
                ];
                $scope.graph.colours = [
                    {
                        fillColor: '#38538E',
                    },
                    {
                        fillColor: '#2B4273',
                    }
                ];
                $scope.graph.series = ['SA Score', 'World Wide Score'];
                $scope.graph.options = {
                    scaleGridLineColor: "#fff",
                    scaleShowVerticalLines: false,
                    scaleFontFamily: "'source_sans_proregular', 'Helvetica', 'Arial', sans-serif",
                    tooltipFillColor: "#231f20",
                    tooltipFontFamily: "'source_sans_proregular', 'Helvetica', 'Arial', sans-serif",
                    tooltipTitleFontFamily: "'source_sans_proregular', 'Helvetica', 'Arial', sans-serif",
                    tooltipTitleFontStyle: "normal",
                    scaleFontColor: "#fff",
                    barValueSpacing: 10,
                    barShowStroke: false,
                    barDatasetSpacing: 0,
                    scaleLineColor: "#fff"
                } 
              });

              // CHECK PAST RESULTS
                $scope.pastResults = function(){
                  $state.go('app.pastResults');
                }

              // GO TO HOME
                $scope.goToHome = function(){                  
                  if($scope.layout.store.call_status != 'completed'){
                    LoaderService.showLoading('Starting Call');
                    if($scope.layout.store.call_status == 'start')
                    {
                      var startData = dataService.start($scope.layout.store.id);
                      $scope.layout.store.current_stats = startData['CURRENT_STATS'];
                      $scope.layout.store.call_status = startData['CALL_STATUS'];
                    }
                    LoaderService.hideLoading();
                    $state.go('app.storeHome');
                  }
                }   
            });             
        })

    // PAST RESULTS
        .controller('pastResultsCtrl', function($ionicPlatform, $scope, $state){
            $ionicPlatform.ready(function() {
              $scope.$on('$ionicView.beforeEnter', function() {
                $scope.store = $scope.layout.store;  
                $scope.layout.breadcrumbs = [
                  {title:'Back to Stores',link:'app.stores'},
                  {title:$scope.layout.store.name,link:'app.storeDetails'},
                  {title:'Past Results',link:''}
                ];  

                $scope.data = Array();
                $scope.data['TOTALS'] = Array();
                for (var i = 0; i < 11; i++) {
                  for (var k = 0; k < 12; k++) {
                    if(!$scope.data[i])
                      $scope.data[i] = Array();

                    if(!$scope.data['TOTALS'][k])
                        $scope.data['TOTALS'][k] = 0;

                      if($scope.layout.store.past_stats[k + 1]){
                        var V1P2 = ($scope.layout.store.past_stats[k + 1][i + 1].V1A == 'yes') ? 1 : 0 ;
                        var V2P2 = ($scope.layout.store.past_stats[k + 1][i + 1].V2A == 'yes') ? 1 : 0 ;
                        var V3P2 = ($scope.layout.store.past_stats[k + 1][i + 1].V3A == 'yes') ? 1 : 0 ;
                        var V4P2 = ($scope.layout.store.past_stats[k + 1][i + 1].V4A == 'yes') ? 1 : 0 ;                        
                        $scope.data[i][k] = V1P2 + V2P2 + V3P2 + V4P2;
                        $scope.data['TOTALS'][k] += $scope.data[i][k];
                      }else{
                        $scope.data[i][k] = 'N/A';
                        $scope.data['TOTALS'][k] = 'N/A';
                      }                                    
                  };
                };

                for (var i = 0; i < $scope.data['TOTALS'].length; i++) {
                  if($scope.data['TOTALS'][i] != 'N/A')
                  {
                    if($scope.data['TOTALS'][i + 1] && $scope.data['TOTALS'][i + 1] != 'N/A')
                    {
                      $scope.data['TOTALS'][i + 1] = parseInt($scope.data['TOTALS'][i + 1]) + parseInt($scope.data['TOTALS'][i]);
                    }
                  }
                };                
              });
              
              // BACK TO DETAILS
                $scope.backToDetails = function(){
                  $state.go('app.storeDetails');
                }              
            });
        })

    // STORE HOME
        .controller('storeHomeCtrl', function($ionicPlatform, $scope, $state, LoaderService){
            $ionicPlatform.ready(function() {
              $scope.$on('$ionicView.beforeEnter', function() {
                $scope.store = $scope.layout.store;
                $scope.layout.breadcrumbs = [
                  {title:'Back to Stores',link:'app.stores'},
                  {title:$scope.layout.store.name,link:'app.storeDetails'},
                  {title:'Home',link:''}
                ];                 
              });

              // BACK TO DETAILS
                $scope.backToDetails = function(){
                  $state.go('app.storeDetails');
                }   

              // REDIRECT
                $scope.goTo = function(A){
                  LoaderService.showLoading('Loading...');
                  $state.go(A);
                }            
            });
        })

    // STORE QUESTIONS
        .controller('storeQuestionsCtrl', function($ionicPlatform, $scope, $state, dataService, LoaderService, $ionicPopup, ipCookie){
            $ionicPlatform.ready(function() {
              $scope.$on('$ionicView.beforeEnter', function() {
                $scope.store = $scope.layout.store;
                $scope.layout.breadcrumbs = [
                  {title:'Back to Stores',link:'app.stores'},
                  {title:$scope.layout.store.name,link:'app.storeDetails'},
                  {title:'Home',link:'app.storeHome'},
                  {title:'Questions',link:''}
                ];  

                var serviceStats = dataService.store($scope.layout.store.id);
                $scope.currentStats = serviceStats['CURRENT_STATS'] ;

                angular.forEach($scope.currentStats,function(value, key) {
                    $scope.currentMonth = key;
                    $scope.layout.month = key;
                });

                $scope.points = 0;
                $scope.update = 0;
                $scope.submitMonth = null;

                $scope.DV1 = 1;$scope.DV2 = 1;$scope.DV3 = 1;$scope.DV4 = 1;

                // CHECK DATA    
                  checkData();  
                  checkStatus();  
                  LoaderService.hideLoading();       
              });

              // BACK TO DETAILS
                $scope.backToHome = function(){
                  $state.go('app.storeHome');
                }  

                $scope.check = function(){
                  $scope.update = 1;
                  checkData();
                }  

                $scope.updateQ = function(){
                  $scope.update = 0;
                  LoaderService.showLoading('Updating Data');
                  checkStatus();
                  dataService.updateQ($scope.currentStats[$scope.currentMonth], $scope.layout.store.id,$scope.currentMonth).success(function (result) {   
                    if(checknet == 'online'){
                        dataService.sync().success(function (result) { 
                            LoaderService.hideLoading();
                            if(result != 'active'){
                              ipCookie.remove('LOGIN'); 
                              $ionicPopup.show({
                                  title: '<div class="popup-pmi-title">Update Questions Synching</div>',
                                  template:'<div class="popup-subtitle">Incorrect login details</div>',
                                  buttons: [
                                      {
                                        text: 'To login page',
                                        onTap: function () {
                                          $state.go('login');
                                        }
                                      }
                                  ]
                              });   
                            }                                    
                        }).error(function(){ 
                            LoaderService.hideLoading();
                            $ionicPopup.show({
                                title: '<div class="popup-pmi-title">Update questions Sync Failed</div>',
                                template:'<div class="popup-subtitle">Data Error</div>',
                                buttons: [
                                    {
                                      text: 'To login page',
                                      onTap: function () {
                                        $state.go('login');
                                      }
                                    }
                                ]
                            });
                        }); 
                    }else{
                      LoaderService.hideLoading();
                    }                    
                  }).error(function(){ 
                      $ionicPopup.show({
                          title: '<div class="popup-pmi-title">Update Questions Failed</div>',
                          template:'<div class="popup-subtitle">Data Error</div>',
                          buttons: [
                              {
                                text: 'OK',
                                onTap: function () {
                                }
                              }
                          ]
                      });
                  });
                }   

                $scope.submit = function(){
                  LoaderService.showLoading('Submiting Data');
                  dataService.complete($scope.layout.store.id,$scope.currentMonth).success(function (result) {   
                    if(checknet == 'online'){
                        dataService.sync().success(function (result2) { 
                            LoaderService.hideLoading();
                            if(result2 != 'active'){
                              ipCookie.remove('LOGIN'); 
                              $ionicPopup.show({
                                  title: '<div class="popup-pmi-title">Submit Questions Synching</div>',
                                  template:'<div class="popup-subtitle">Incorrect login details</div>',
                                  buttons: [
                                      {
                                        text: 'To login page',
                                        onTap: function () {
                                          $state.go('login');
                                        }
                                      }
                                  ]
                              });   
                            }else{
                              var returnSubmitteddata = dataService.store($scope.layout.store.id);
                              $scope.layout.store.current_stats = returnSubmitteddata['CURRENT_STATS'];
                              $scope.layout.store.past_stats = returnSubmitteddata['PAST_STATS'];
                              $scope.layout.store.call_status = returnSubmitteddata['CALL_STATUS'];
                              $scope.layout.world = dataService.world();
                                                 
                              LoaderService.hideLoading();
                              $state.go('app.storeSummary');                              
                            }                                    
                        }).error(function(){ 
                            LoaderService.hideLoading();
                            $ionicPopup.show({
                                title: '<div class="popup-pmi-title">Submit questions Sync Failed</div>',
                                template:'<div class="popup-subtitle">Data Error</div>',
                                buttons: [
                                    {
                                      text: 'OK',
                                      onTap: function () {
                                      }
                                    }
                                ]
                            });
                        }); 
                    }else{
                      var returnSubmitteddata = dataService.store($scope.layout.store.id);
                      $scope.layout.store.current_stats = returnSubmitteddata['CURRENT_STATS'];
                      $scope.layout.store.past_stats = returnSubmitteddata['PAST_STATS'];
                      $scope.layout.store.call_status = returnSubmitteddata['CALL_STATUS'];
                                         
                      LoaderService.hideLoading();
                      $state.go('app.storeSummary');                      
                    }                                         
                  }).error(function(){ 
                      $ionicPopup.show({
                          title: '<div class="popup-pmi-title">Submiting data Failed</div>',
                          template:'<div class="popup-subtitle">Data Error</div>',
                          buttons: [
                              {
                                text: 'OK',
                                onTap: function () {
                                }
                              }
                          ]
                      });
                  });
                }                        

                function checkData(){  
                  $scope.points = 0;
                  angular.forEach($scope.currentStats[$scope.currentMonth],function(value, key) {
                      var V1P3 = (value.V1A == 'yes') ? 1 : 0 ;
                      var V2P3 = (value.V2A == 'yes') ? 1 : 0 ;
                      var V3P3 = (value.V3A == 'yes') ? 1 : 0 ;
                      var V4P3 = (value.V4A == 'yes') ? 1 : 0 ; 
                      $scope.points += V1P3 + V2P3 + V3P3 + V4P3;                      
                  });                  
                } 

                function checkStatus(){
                  var visit1Check = true;
                  var visit2Check = true;
                  var visit3Check = true;
                  var visit4Check = true;
                  angular.forEach($scope.currentStats[$scope.currentMonth],function(value, key) {
                      if(value.V1A != 'yes' && value.V1A != 'no'){
                        visit1Check = false;
                      }   
                      if(value.V2A != 'yes' && value.V2A != 'no'){
                        visit2Check = false;
                      } 
                      if(value.V3A != 'yes' && value.V3A != 'no'){
                        visit3Check = false;
                      } 
                      if(value.V4A != 'yes' && value.V4A != 'no'){
                        visit4Check = false;
                      }                                                                                     
                  });

                  if(visit1Check)
                  {
                    $scope.DV1 = 1;
                    $scope.DV2 = null;
                    if(visit2Check)
                    {
                      $scope.DV2 = 1;
                      $scope.DV3 = null;
                      if(visit3Check)
                      {
                        $scope.DV3 = 1;
                        $scope.DV4 = null;
                        if(visit4Check)
                        {
                          $scope.DV4 = 1;
                          $scope.submitMonth = 1;
                        }else{
                          $scope.DV4 = null;
                        }                         
                      }else{
                        $scope.DV3 = null;
                        $scope.DV4 = 1;
                      }                      
                    }else{
                      $scope.DV2 = null;
                      $scope.DV3 = 1;                    
                    }                    
                  }else{
                    $scope.DV1 = null;
                    $scope.DV2 = 1;                    
                  }                                                   
                } 
            });
        })

    // STORE SUMMARY
        .controller('storeSummaryCtrl', function($ionicPlatform, $scope, $state, dataService, LoaderService, $ionicPopup){
            $ionicPlatform.ready(function() {
              $scope.$on('$ionicView.beforeEnter', function() {                
                $scope.store = $scope.layout.store;
                $scope.layout.breadcrumbs = [
                  {title:'Back to Stores',link:'app.stores'},
                  {title:$scope.layout.store.name,link:'app.storeDetails'},
                  {title:'Home',link:'app.storeHome'},
                  {title:'Summary',link:''}
                ];

                if($scope.layout.store.call_status == 'continue'){
                  var serviceStats = dataService.store($scope.layout.store.id);
                  $scope.currentStats = serviceStats['CURRENT_STATS'] ;
                  angular.forEach($scope.currentStats,function(value, key) {
                      $scope.currentMonth = key;
                  });
                }else{
                  var serviceStats = dataService.store($scope.layout.store.id);
                  $scope.currentStats = serviceStats['PAST_STATS'] ;
                  $scope.currentMonth  = $scope.layout.month;
                }

                $scope.DV1 = 1;$scope.DV2 = 1;$scope.DV3 = 1;$scope.DV4 = 1;    

                for (var i = 1; i < 12; i++) {
                  var V1P4 = ($scope.currentStats[$scope.currentMonth][i].V1A == 'yes') ? 1 : 0 ;
                  var V2P4 = ($scope.currentStats[$scope.currentMonth][i].V2A == 'yes') ? 1 : 0 ;
                  var V3P4 = ($scope.currentStats[$scope.currentMonth][i].V3A == 'yes') ? 1 : 0 ;
                  var V4P4 = ($scope.currentStats[$scope.currentMonth][i].V4A == 'yes') ? 1 : 0 ;                 
                  $scope.currentStats[$scope.currentMonth][i]['POINTS'] = V1P4 + V2P4 + V3P4 + V4P4;
                };
                console.log($scope.currentStats);

                LoaderService.hideLoading();              
              });

              // BACK TO DETAILS
                $scope.backToHome = function(){
                  $state.go('app.storeHome');
                }              
            });
        });

// FILTERS
    // SEARCH FILTER FOR OUTLETS
        app.filter('StoresFilter',function($filter){
           return function(items, text){
              if (!text || text.length === 0)
                return items;
              
              // split search text on space
              var searchTerms = text.split(' ');
              
              // search for single terms.
              // this reduces the item list step by step
              searchTerms.forEach(function(term) {
                if (term && term.length)
                  items = $filter('filter')(items, term);
              });

              return items
           };
        });                 