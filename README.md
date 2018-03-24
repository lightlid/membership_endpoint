# membership_endpoint
Customized endpoint for Woocommerce Membership

Woocommerce Membership API is not coming out yet. You can build customized endpoints to access woocommerce membership outside of the system.

1. Authentication:
   By default wordpress only allow cookie authentication. You can install other auth plugins from plugin website.
   Basic auth: for development only. It's not recommeded as for each request you'll send wordpress password.
   Others:
   JWT auth: I personally prefer this one.
   Or OAuth2 
   
2. Installation:
   You can copy code into theme function.php or install as plugin.

