var multiLangForm = Object.create(MultiLangWidget);

multiLangForm.onInit({
    langSelectorId: 'langSelector',
    formCssSelector: 'form#myedit',
    toggleCssSelector: null,
    inputsCssSelector: '.m-lang',
    dataPath: 'cl=klarna_configuration',
    validateFormData : function(){
        var $validateInputs = this.$inputs.filter('.url-input');
        this.errors = $.extend(this.errors, JSON.parse(this.$form.attr('data-error')));
        this.serializeForm();
        $validateInputs.each(this.validateInputData.bind(this));
    },

    validateInputData: function(i, input){

        var search = input.name.match(/\[(.*)URI/)[1];
        var pattern = input.getAttribute('pattern');

        // find formValues related to the input
        input.formValues = this.$form.serialized.filter(byNameContains.bind(null, [search]));

        // reset validator
        var langErrors = {invalidPatter: [], missingValue: []};
        input.setCustomValidity('');

        for(var j = 0; input.formValues[j]; j++){

            // has pattern , not empty, patternMismatch
            if(pattern && (input.formValues[j].value !=="" && !input.formValues[j].value.match(pattern))){
                langErrors.invalidPatter.push(input.formValues[j].name.match(/.*_(.*)]/)[1]);
                input.setCustomValidity(this.errors.patternMismatch + ' [' + langErrors.invalidPatter.join(', ') + ']');
            }

            // input required, formData is empty
            if(input.required && input.formValues[j].value === ""){
                langErrors.missingValue.push(input.formValues[j].name.match(/.*_(.*)]/)[1]);
                input.setCustomValidity(this.errors.valueMissing + ' [' + langErrors.missingValue.join(', ') + ']');
            }
        }
    }
});

var checkEuropeanCountries = function(data, actionUrl){
    $.ajax({
        url: actionUrl+'cl=klarna_configuration&fnc=checkEuropeanCountries',
        type: 'POST',
        data: data,
        dataType: 'json'
    }).done(function(oData){
        var json = oData;
        var $warnBox = $('.messagebox.warn');
        if(json.warningMessage != null) {
            if($warnBox.is(':empty')){
                $warnBox.append('<p>'+json.warningMessage+'</p>') ;
            }
            $warnBox.slideDown();
        } else {
            $warnBox.slideUp();
        }
    });
};

var deliveryAddress = $('#AllowSeparateDeliveryAddress');

checkEuropeanCountries({separate_shipping_enabled: deliveryAddress.prop("checked")}, multiLangForm.$form.attr('action'));

deliveryAddress.on("change",function(evt) {
    checkEuropeanCountries({separate_shipping_enabled: deliveryAddress.prop("checked")}, multiLangForm.$form.attr('action'));
});

var defaultCountrySelector = new Selector2({
        id: 'defaultCountry',
        fromOptions: false
    });


