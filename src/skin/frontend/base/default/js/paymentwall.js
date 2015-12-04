var PW = {
    "rewritePayment": false,
    "initPwBrick": function (publicKey) {
        return new Brick({
            public_key: publicKey,
            form: {formatter: false}
        }, 'custom');
    },
    "prepareBrickData": function () {
        return {
            card_number: $('paymentwall_pwbrick_cc_number').value,
            card_expiration_month: $('paymentwall_pwbrick_expiration').value,
            card_expiration_year: $('paymentwall_pwbrick_expiration_yr').value,
            card_cvv: $('paymentwall_pwbrick_cc_cid').value
        }
    },
    "ajaxPwLocalListener": function (pwlocalUrl, stopintervalcallback) {
        return new Ajax.Request(pwlocalUrl, {
            method: 'post',
            onSuccess: function (transport) {
                try {
                    if (transport.responseText.isJSON()) {
                        var response = transport.responseText.evalJSON();
                        switch (response.status) {
                            case 1: // Payment processed
                                $$('.paymentwall-widget')[0].innerHTML = response.message;
                                if (stopintervalcallback) {
                                    stopintervalcallback();
                                }
                                // Redirect to success page after x seconds
                                if (response.url) {
                                    window.setTimeout(function () {
                                        window.location.href = response.url;
                                    }, 3000);
                                }
                                break;
                            case 2: // Payment fail or error
                                $$('.paymentwall-widget').innerHTML = response.message;
                            default :
                                break;
                        }
                    } else {
                        alert(transport.responseText);
                        if (stopintervalcallback) {
                            stopintervalcallback();
                        }
                    }
                }
                catch (e) {
                    alert(transport.responseText);
                    if (stopintervalcallback) {
                        stopintervalcallback();
                    }
                }
            }
        });
    },
    "rewriteSavePayment": function (brick) {

        // Check second load
        if (this.rewritePayment) {
            return false;
        }

        this.rewritePayment = true;
        Payment.prototype.save = Payment.prototype.save.wrap(function (origin_save) {
            if ($('p_method_paymentwall_pwbrick').checked) {

                // Check payment method selected
                var validator = new Validation(this.form);

                if (this.validate() && validator.validate()) {
                    brick.tokenizeCard(
                        PW.prepareBrickData(),
                        function (response) {
                            // handle errors
                            if (response.type == 'Error') {
                                alert("Brick Error(s):\nCode [" + response.code + "]: " + response.error);
                                return false;
                            } else {
                                $('paymentwall_pwbrick_token').value = response.token;
                                $('paymentwall_pwbrick_fingerprint').value = Brick.getFingerprint();
                                // Call origin function
                                origin_save();
                                return true;
                            }
                        });
                }
            } else {
                // Call origin function
                origin_save();
            }
        });
    }
};