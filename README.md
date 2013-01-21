woocommerce-save-for-later
==========================

A WordPress plugin for WooCommerce to add save for later functionality to products in your store.

This plugin uses icons as webfont by [Modern Pictograms](http://www.fontsquirrel.com/fonts/modern-pictograms)

### Settings

### Filters

### TODO

* Convert anonymous wishlist to authenticated; need to assign the author of the post (wishlist post id) in cookie to the next logged in user and walaa!!!.
* Above conversion to take place as soon as a user logs in. If there is any anonymous cookie of our desire, assign one to the current user.
* Add actions and filters to the designated locations.
* write cron to remove all wishlist with author id 0 and cookie expiry = current time - wishlist creation time
* Build front end views to list the wishlist as well as to display appropriate messages.
* Clean ups and finalization before final push