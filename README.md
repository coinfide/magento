# Coinfide Magento 1.9 plugin

Use this with Magento installation for versions prior to 2.0. For ```Test API url``` and ```Prod API url``` use ```http://demo-api.enauda.com/paymentapi/``` and ```https://paymentapi.enauda.com/paymentapi/``` accordingly.

Set your API callback to ```/coinfidepaymentmethod/payment/callback``` in your API dashboard.

The server should support TLS 1.2. It means OpenSSL version > 1.0.1 and Curl > 7.43, check the library versions your PHP is compiled against.
