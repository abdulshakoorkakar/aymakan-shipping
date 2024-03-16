=== Aymakan Integration ===
Contributors: Aymakan
Tags: aymakan, shipping, woocommerce, integration
Requires at least: 5.9
Tested up to: 6.4.3
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==
This plugin enables WordPress WooCommerce stores to perform the following:

- Create AWB in Aymakan
- Add a shipment to WooCommerce Order with tracking number and AWB download link

== Installation ==
Following are the instruction to install this plugin:

1. Download this plugin.
2. Upload the Aymakana plugin zip file through WordPress admin.
3. After activating the plugin add the plugin configuration through shipping zone.

== Configuration ==
Enter the required configuration details.

There are some key configurations to note down.

- `Test Mode`: If you are testing the plugin, check the box. Once the integration is tested, and ready to move to production,
  disable `Test Mode` by unchecking.
- `API Key`: API Key is used for authenticating with Aymakan Api. The API key can be found in your Aymakan account.
  Login to your account and go to `Integrations`. Copy the Api Key and paste it in the API Key field in Aymakan plugin configuration.
- `Collection Related Data`: As can be seen in the screenshot, there are several config fields which are Collection related.
  These fields are related to your address (From where Aymakan drivers will be picking up shipments). Enter your contact information here
  or enter your Warehouse address and contact information in all those fields accordingly.

== Usage ==
Once the plugin is configured properly, it's time to see it in action.

=== Single Shipment ===
- Go to orders listing page. You will be able to see `Create Shipment` button within Aymakan Action column for creating a single shipment.
- Most of the form will be already filled up for you. You will need to select a `Delivery City`. Aymakan only supports a list of cities with proper namings.
  So select the desired city.
- If the order is COD, then select `Yes` in `Is COD?` field.
- If the order is COD, the `COD Amount` field will already have the order total. Confirm if it is correct.
- Items field should have the total number of items (products) in this shipment.
- Pieces field should have the number of pieces this shipment will have. For example, for a large shipment, there will be several items not fitting in a single carton,
  so they will be packed in multiple cartons. This field should have the number of cartons.
- Click on `Create Shipping` button at bottom right to create a shipping in Aymakan.
- Once the shipment is created, you will have a success message and an order note will appear on the right side of order page with View PDF (AWB) link.

=== Bulk Shipments ===
- For creating the bulk shipment open the `Bulk Action` dropdown and you'll find the option `Create Aymakan Shipments`.

