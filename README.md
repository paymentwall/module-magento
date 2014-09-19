# PWPRO for Magento

### Requirement

* PHP 5.2.16 or later

### Installation:

* Clone the repo using command ``` git clone --recursive ```
* Copy content to magento installation APP directory.
* Go to Admin panel => System => Configuration => Payment methods => PWPRO and add API Key & Public Key
* Go to Paymentwall.com, sign up an Merchant account and setup pingback URL : http://{magento-url}/paymentwall/payment/ping . Use POST request.