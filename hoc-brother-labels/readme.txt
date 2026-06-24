=== House of Coffee Brother Labels ===
Contributors: hokiplugins
Tags: woocommerce, labels, brother, printing, coffee
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
WC requires at least: 6.0
WC tested up to: 9.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sends structured print jobs for House of Coffee bag labels (62mm Brother QL-700 roll) to an external local-network print service.

== Description ==

House of Coffee Brother Labels is a WooCommerce admin plugin that prints branded coffee bag labels for a Brother QL-700 printer on 62mm continuous roll stock.

This plugin does **not** talk to the printer directly. It builds a structured JSON payload describing the label (template, printer model, label width, copies, job reference and data fields) and sends it over HTTP to an external print microservice that you run on your local network — for example, a service based on `brother_ql_web` or `label_api`. This plugin does not include or vendor any such service; you must run/configure that separately and point this plugin at its base URL.

Label layout produced by the bundled "house-of-coffee-62mm" template definition:

1. Brand/logo area at the top.
2. Product name in large uppercase text.
3. A bordered info box with rows: Grind, Weight, Strength, Flavour, Roast.
4. A "Best Before" line at the bottom.

= Features =

* Settings page (under WooCommerce admin menu) for the print service URL, API token, printer model, label width, template name, default copies, best-before calculation mode, shelf life, and meta key mappings.
* "Print HoC Labels" row action on the WooCommerce orders list.
* "Print HoC Labels" bulk action on the WooCommerce orders list.
* A meta box on the order edit screen to print all printable line items for that order.
* Optional automatic printing when an order reaches a configured status.
* Full WooCommerce HPOS (High-Performance Order Storage) compatibility.
* Extensive filters for customizing field mapping, payload, printability and best-before calculation.

== Installation ==

1. Make sure WooCommerce is installed and active.
2. Upload the `hoc-brother-labels` folder to `/wp-content/plugins/`, or install the zip via the Plugins screen.
3. Activate the plugin.
4. Go to WooCommerce > HoC Brother Labels and configure:
   * Print Service Base URL (e.g. `http://192.168.1.50:8013`)
   * API Token (if your print service requires one)
   * Printer model, label width, template name, default copies
   * Best-before calculation mode and shelf life
   * Meta key mappings for grind/weight/strength/flavour/roast/roast date/best before
5. Open a WooCommerce order containing coffee products and click "Print HoC Labels" in the order list, bulk actions, or the order edit screen meta box.

== External Print Service ==

This plugin sends an HTTP POST request to `{Print Service Base URL}{Print Jobs Endpoint Path}` (default path `/print-jobs`) with a JSON body shaped like:

`
{
  "template": "house-of-coffee-62mm",
  "printer": "ql-700",
  "label_width_mm": 62,
  "copies": 1,
  "job_ref": "order-18452-item-3",
  "data": {
    "brand": "House of Coffee",
    "product_name": "DARK MONSOON MALABAR",
    "grind": "AeroPress",
    "weight": "0.454 kg",
    "strength": "4",
    "flavour": "3",
    "roast": "3",
    "best_before": "04.08.2027"
  }
}
`

If an API token is configured, it is sent as `Authorization: Bearer {token}`. The service is expected to respond with a 2xx status code on success.

== Filters ==

* `hoc_brother_labels_field_map` - Filter the normalized label field map before payload assembly.
* `hoc_brother_labels_payload` - Filter the final print job payload before it is sent.
* `hoc_brother_labels_print_response` - Filter the normalized response from the print service.
* `hoc_brother_labels_is_printable_item` - Filter whether a given order line item should be printed.
* `hoc_brother_labels_best_before_timestamp` - Filter the calculated best-before timestamp.
* `hoc_brother_labels_brand_name` - Filter the brand name shown on the label.

== Changelog ==

= 1.0.0 =
* Initial release.
