=== QRCraft ===
Contributors: ageborndev
Tags: qr code, woocommerce, products, qr generator, product qr
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.0.0
WC requires at least: 8.0
WC tested up to: 10.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The simplest way to add QR codes to your WooCommerce store. Lightweight, automatic, and hassle-free.

== Description ==

QRCraft generates unique QR codes for each of your WooCommerce products. When scanned, customers are taken directly to the product page. Perfect for print catalogs, product labels, in-store displays, and marketing materials.

= Key Features =

* Automatic QR code generation when products are created or updated
* Batch processing using Action Scheduler to prevent server overload
* Customizable QR code colors with a visual color picker
* Multiple size options (100px to 300px)
* Adjustable error correction levels for different use cases
* QR code preview in the products list with hover to enlarge
* Download individual QR codes as SVG files
* Bulk regeneration with progress tracking
* Clean and minimal footprint

= How It Works =

1. Install and activate the plugin
2. Configure your preferred QR code style in the settings
3. QRCraft automatically generates QR codes for all existing and new products
4. View, download, or regenerate QR codes from the Products list or individual product pages

= Server Friendly =

QRCraft uses WooCommerce's Action Scheduler to process QR codes in small batches. This means even stores with thousands of products can generate QR codes without slowing down or crashing.

== Installation ==

1. Upload the `qrcraft` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to WooCommerce > QRCraft to configure your settings
4. QR codes will be generated automatically for your products

== Frequently Asked Questions ==

= What information is stored in the QR code? =

Each QR code contains the direct URL to the product page. When scanned, customers are taken straight to that product.

= Can I customize the QR code appearance? =

Yes! You can change the QR code color and background color using the built-in color picker. You can also choose from multiple size options.

= Does this work with product variations? =

QR codes are generated for the main product. Variations share the parent product's QR code.

= What happens if I change a product's URL? =

The QR code is regenerated automatically whenever you update a product, ensuring the URL is always current.

= Can I bulk generate QR codes? =

Yes, go to WooCommerce > QRCraft and click "Regenerate All QR Codes". The process runs in the background so you can continue using your site.

= What file format are the QR codes? =

QR codes are generated as SVG files for perfect scaling at any size while keeping file sizes small.

== Screenshots ==

1. QRCraft settings page with color picker and options
2. QR code column in the Products list
3. QR code preview in the product edit screen
4. Bulk regeneration progress indicator

== Changelog ==

= 1.0.0 =
* Initial release
* Automatic QR generation on product save
* Bulk generation with Action Scheduler
* Color customization with WordPress color picker
* Size and error correction options
* Product list QR code column
* Individual QR code download
* Clean uninstall with optional file deletion

== Upgrade Notice ==

= 1.0.0 =
Initial release. Welcome to QRCraft!
