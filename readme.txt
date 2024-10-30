=== CBX Woo Coupon Referral Affiliate ===
Contributors: manchumahara,codeboxr
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=NWVPKSXP6TCDS
Tags: woocommerce, coupon, discount, affiliate, sales representative, sales, marketing
Requires at least: 3.0
Tested up to: 4.6
Stable tag: 3.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

CBX Coupon Referral Affiliate(WCRA) for Woocommerce

== Description ==
CBX Woocommerce Coupon Referral Affiliate(WCRA) is interesting plugin for wordpress that takes the coupon marketing business in woocommerce platform to next level.
The generic affiliation plugin gives percentage from sales to affiliates as per referral but this plugin works in different way. Seems interesting ?

In Short: shop owner creates coupon, customer uses that, gets discount and they becomes happy. Our plugin add extra features on top of this feature. Any affiliate user can
be added to any coupon, shop owner can set target sales using this coupon, affiliate percentage etc. So the affiliate person spread his coupon code from where customer gets discount
as usual and same time affiliate user gets discount. This type of coupon code can be called hidden codes and the affiliate user can be called Sales Representative.

Shop owner can track how much sales are coming via any Sales Representative from dashboard overview page and same time Sales Representative can see their performance from frontend using shortcode powered page.


= General Features =

1. Choose which user role can be assigned for affiliate
2. Bind One user with one coupon
3. Frontend Shortcode page for affiliate user assigned by coupon

= Admin Dashboard Overview =

1. User referral statistics by month
2. User referral statistics by year
3. User referral statistics by user(affiliate)
4. Yearly analysis of overall orders referred by all coupons(Graph)
5. Yearly analysis of overall orders referred by all coupons(Database)
6. Admin can check statistic for any specific coupon which is actually performance of a user.

= User Dashboard Overview =

1. Frontend shortcode powered page for affiliate user
2. Monthly analysis of orders referred by coupon(Graph) based on shortcode param
3. Monthly analysis of orders referred by coupon(Database) based on shortcode param
2. Yearly  analysis of orders referred by coupon(Graph) based on shortcode param
3. Yearly  analysis of orders referred by coupon(Database) based on shortcode param

See more details and usages guide here http://codeboxr.com/product/cbx-woo-coupon-referral-affiliate/

= Shortcode =
1. Frontend shortcode to show user stat and performance
Shortcode: [cbxwoocouponreferral]

params : type= 'permonth' or 'peryear'. or 'permonth,peryear'
Default is permonth.


2. Frontend shortcode to show top affiliate user

Shortcode: [wcratop]
Params:
count  default 10, any digit
type   default 'month' or  'year'

if type = month , means for current whole month,
if type = year, means for current whole year

order  default 'DESC',   or 'ASC'


order_by default 'total_earning', other possible 'total_amount', 'total_referred'

total_referred =  how many order referred
total_earning = how much affiliate user earned
total_amount = sales volume referred by affiliate user



= Pro Features =

Besides this free version we have two pro addon that adds more premium features. This free or core version will always be free, we promise.

Pro Plugins:

1. WCRA Email Alert & Export Addon http://codeboxr.com/product/wcra-email-alert-export-addon
2. WCRA Payment Addon(Paypal Masspay and Bank Payment) http://codeboxr.com/product/wcra-payment-addon
3. Request us for customization and support http://codeboxr.com/contact-us/


 = 2.3 to 2.5.x upgrade notice =
 >> 2.3 was our first public release and we heard lots of bugs and we decided to revamp the plugin. So we dropped the previous database structure and followed totally different path. So before you upgrade to 2.5 please take backup, finalize any transaction with any affiliator. Drop the old version, install the new. Now remove all affiliator, add them again. Think, it's a new journey for this plugin, for any up coming change we will keep the automatic migration but this current jump from 2.3 to 2.5 we didn't have such option for auto migration of data. If you mistakenly upgraded to 2.5 but you want the data of 2.3, then just delete the plugin and install the 2.3 again. On install of 2.5 we don't delete any database tables. We want to hear from you about any bug or new feature request.


== Installation ==

How to install the plugin and get it working.


1. Upload `cbxwoocouponreferral` folder  to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. In any post or page you can write shortcode [cbxwoocouponreferral] to view only logged in user orders with extra column of item listing


== Frequently Asked Questions ==
= Why 2.5 or greater version is different than 2.3 =

2.3 was our first public release and we heard lots of bugs and we decided to revamp the plugin. So we dropped the previous database structure and followed totally different path. So before you upgrade to 2.5 please take backup, finalize any transaction with any affiliator. Drop the old version, install the new. Now remove all affiliator, add them again. Think, it's a new journey for this plugin, for any up coming change we will keep the automatic migration but this current jump from 2.3 to 2.5 we didn't have such
option for auto migration of data. If you mistakenly upgraded to 2.5 but you want the data of 2.3, then just delete the plugin and install the 2.3 again. On install of 2.5 we don't delete any database tables. We want to hear from you about any bug or new feature request.

== Screenshots ==
1. WCRA General Setting
2. WCRA Email Setting
3. WCRA Misc Setting
4. WCRA Admin overview for coupon association with users
5. WCRA Order analysis for any year(Next, Prev) - Graph
6. WCRA Order analysis for any year(Next, Prev) - Data Table
7. WCRA Frontend user dashboard - Graph
8. WCRA Frontend user dashboard - Datatable
9. WCRA Email setting - Coupon added
10. WCRA Email setting - Coupon removed
11. WCRA Email setting - Monthly email alert
12. WCRA Email setting - Yearly email alert

== Changelog ==
= 3.0.2 =
* [New] Affiliate user now can have fixed type commission
* [Improvement] Percentage or fixed amount can be decimal
* Lots of minor bug fix
* Overall improvement in different sections
* You must upgrade the pro and free addon to get the good of this updates

= 3.0.1 =
* Shortcode now takes "peryear" and "permonth" comma seperatedly for exp.: '[cbxwoocouponreferral type='permonth,peryear']' or [cbxwoocouponreferral type='peryear,permonth']
* New shortcode 'wcratop' and Widget "WCRA Top" added
* Two Dashboard widgets added named "Top Affiliator" and "Yearly stat graph"

= 3.0.0 =
* Bug fix for user percentage not save

= 2.5.4 =
* Shipping and other costs are subtracted or in other words not considered in Affiliators earning.All other analysis are same as before

= 2.5.3 =
* User add add affiliator auto complete fix
* Woocommerce featured image add issue fixed

= 2.5.2 =
* Bug fix for mispelled option section name

= 2.5 =
* Database structure is changed to a more stable version and not compatible.
* Order cancellation is handled besides refund.Only order-completion and order-refunded is taken into consideration for calculation.
* More Clean admin overview page display of month data(can be navigate by coupon or user) and year data(graph and overview listing).
* Added Month analysis by coupon and also by user.
* Same change is applied in frontend.
* Graph is updated to more clean version.
* A new menu of Affiliate users listing is added and also a month navigation menu is added.

= 2.3 =
* Refund is now handled at it's best

= 2.2 =
* Bug fix in calculation of user earning with multiple coupons and with different types of coupons(cart/cart%,product/product%)
* Recalculate WCRA data after any order is fully/partially refunded

= 2.1.1 =
* Chosen added in setting page

= 2.1.0 =
* Affilation calculation issue fixed for multiple product

= 2.0.10 =
* Minor bug fix for coupon save and post meta
* Added montly stat for admin user

= 2.0.9 =
* First Release

== Upgrade Notice ==

### 2.5 ###
2.3 was our first public release and we heard lots of bugs and we decided to revamp the plugin. So we dropped the previous database structure and followed totally different path. So before you upgrade to 2.5 please take backup, finalize any transaction with any affiliator. Drop the old version, install the new. Now remove all affiliator, add them again. Think, it's a new journey for this plugin, for any up coming change we will keep the automatic migration but this current jump from 2.3 to 2.5 we didn't have such option for auto migration of data. If you mistakenly upgraded to 2.5 but you want the data of 2.3, then just delete the plugin and install the 2.3 again. On install of 2.5 we don't delete any database tables. We want to hear from you about any bug or new feature request.