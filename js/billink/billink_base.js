/**
 * Namespace for global billink variables and controllers
 */
;(function( billink ) {

    //Vars
    billink.isReadyToUse = undefined;
    billink.isAlternateDeliveryAddressAllowed = undefined;
    billink.validationError = undefined;

    //Main controllers
    billink.checkoutPageController = {};
    billink.paymentFormController = {};
    billink.paymentFormSession = {};

    //Billink submodules
    billink.subModules = [];

}( window.billink = window.billink || {} ));