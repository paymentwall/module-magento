document.observe("dom:loaded", function () {
    if (!PaymentwallPro.Validator.cards) {
        PaymentwallPro.Validator.initialize();
    }

    Validation.add('paymentwall-validate-cc-number', Translator.translate("enter_valid_cc_num"), function (valueToTest) {
        return (valueToTest.length > 0) && PaymentwallPro.Validator.validateCardNumber(valueToTest);
    });

    Validation.add('paymentwall-validate-cc-cvn', Translator.translate("enter_valid_cc_cid"), function (valueToTest) {
        return (valueToTest.length > 0) && PaymentwallPro.Validator.validateCardSecurityCode(valueToTest);
    });

    Validation.add('paymentwall-validate-cc-exp', Translator.translate("enter_valid_cc_exp"), function (valueToTest) {
        var data = valueToTest.split('/', 2);
        if (data.length < 2) {
            return false;
        }
        return PaymentwallPro.Validator.validateCardExpiry(data[0], data[1]);
    });

    payment.preparePwProFormData = function () {
        var h = $H(), pwproForm = $("payment_form_paymentwall_pwpro");
        PaymentwallPro.Encryption.initialize($(this.form).select('#pwpro_public_key')[0].getValue());
        var ccNumber = pwproForm.select('input[name=payment\[cc_number\]]')[0].getValue();
        h.set('payment[method]', payment.currentMethod);
        h.set('payment[cc_owner]', pwproForm.select('input[name=payment\[cc_owner\]]')[0].getValue());
        h.set('payment[cc_number]', PaymentwallPro.Encryption.baseEncrypt(ccNumber));
        h.set('payment[cc_cid]', PaymentwallPro.Encryption.baseEncrypt(pwproForm.select('input[name=payment\[cc_cid\]]')[0].getValue()));
        var expiry = pwproForm.select('input[name=payment\[cc_exp\]]')[0].getValue().split('/', 2);
        h.set('payment[cc_exp_month]', PaymentwallPro.Encryption.baseEncrypt(expiry[0]));
        h.set('payment[cc_exp_year]', PaymentwallPro.Encryption.baseEncrypt(expiry[1]));
        h.set('payment[' + PaymentwallPro.browser_domain + ']', PaymentwallPro.Encryption.baseEncrypt(window.location.host));
        h.set('payment[cc_last_4]', ccNumber.slice(-4));
        return h;
    }.bind(payment);

    payment.save = function () {
        if (checkout.loadWaiting != false) return;
        var validator = new Validation(this.form);
        if (this.validate() && validator.validate()) {
            try {
                var formData;
                if (this.currentMethod == 'paymentwall_pwpro') {
                    if(!$('payment_form_paymentwall_pwpro')) {
                        throw new Error(Translator.translate('cannot_use_payment_method'));
                    }
                    formData = this.preparePwProFormData();
                } else {
                    formData = Form.serialize(this.form);
                }

                checkout.setLoadWaiting('payment');
                var request = new Ajax.Request(
                    this.saveUrl,
                    {
                        method: 'post',
                        onComplete: this.onComplete,
                        onSuccess: this.onSave,
                        onFailure: checkout.ajaxFailure.bind(checkout),
                        parameters: formData
                    }
                );
            } catch (e) {
                alert(e.message);
            }

        }
    }.bind(payment);

    Review.prototype.save = function () {
        if (checkout.loadWaiting != false) return;
        checkout.setLoadWaiting('review');
        var formData;
        if (payment.currentMethod == 'paymentwall_pwpro') {
            formData = payment.preparePwProFormData().toQueryString();
        } else {
            formData = Form.serialize(payment.form);
        }
        formData.save = true;
        //var params = Form.serialize(payment.form);
        if (this.agreementsForm) {
            formData += '&' + Form.serialize(this.agreementsForm);
        }
        //params.save = true;
        var request = new Ajax.Request(
            this.saveUrl,
            {
                method: 'post',
                parameters: formData,
                onComplete: this.onComplete,
                onSuccess: this.onSave,
                onFailure: checkout.ajaxFailure.bind(checkout)
            }
        );
    };
});