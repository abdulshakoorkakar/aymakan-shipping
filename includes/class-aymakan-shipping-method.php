<?php
defined('ABSPATH') || exit;

/**
 * Class Aymakan_Shipping_Method
 */
class Aymakan_Shipping_Method extends WC_Shipping_Method
{
    /**
     * @var string
     */
    public $api_key;
    /**
     * @var string
     */
    public $test_mode = 'yes';

    /**
     * @var string
     */
    public $log;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $is_sdd;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $city;

    /**
     * @var string
     */
    public $address;

    /**
     * @var string
     */
    public $neighbourhood;

    /**
     * @var string
     */
    public $phone;

    /**
     * @var string
     */
    public $debug = 'yes';

    /**
     * @var string
     */
    public $country = 'SA';

    public $helper;

    /**
     * Initialize the Aymakan shipping method.
     *
     * @return void
     */
    public function __construct($instance_id = 0)
    {
        $this->id                 = 'aymakan';
        $this->instance_id        = absint($instance_id);
        $this->method_title       = __('Aymakan Shipping', 'aymakan');
        $this->method_description = __('Safe, Reliable And express logistics & transport solutions.', 'aymakan');
        $this->supports           = array(
            // 'settings',
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal'
        );

        $this->init();
    }

    /**
     * Initializes the method.
     *
     * @return void
     */
    public function init()
    {

        $this->helper = new Aymakan_Shipping_Helper();

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Define user set variables.
        $this->enabled       = $this->get_option('enabled');
        $this->api_key       = $this->get_option('api_key');
        $this->test_mode     = $this->get_option('test_mode');
        $this->title         = $this->get_option('title');
        $this->is_sdd        = $this->get_option('is_sdd');
        $this->name          = $this->get_option('collection_name');
        $this->email         = $this->get_option('collection_email');
        $this->city          = $this->get_option('collection_city');
        $this->address       = $this->get_option('collection_address');
        $this->neighbourhood = $this->get_option('collection_neighbourhood');
        $this->phone         = $this->get_option('collection_phone');
        // $this->country     = $this->get_option('collection_country');
        $this->debug = $this->get_option('debug');

        // Active logs.
        if ('yes' === $this->debug) {
            if (class_exists('WC_Logger')) {
                $this->log = new WC_Logger();
            }
        }
        // add_filter('woocommerce_cart_shipping_method_full_label', array($this, 'aymakan_shipping_method_price'), 10, 2);

        // Actions
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));


    }

    /**
     * Admin options fields.
     *
     * @return void
     */
    public function init_form_fields()
    {
        $this->instance_form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'aymakan'),
                'type' => 'checkbox',
                'label' => __('Enable this shipping method', 'aymakan'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Title', 'aymakan'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'aymakan'),
                'desc_tip' => true,
                'default' => __('Aymakan', 'aymakan')
            ),
            'api_key' => array(
                'title' => __('API Key', 'aymakan'),
                'type' => 'text',
                'description' => __('The API key is available at Aymakan account in Integrations.', 'aymakan'),
                'desc_tip' => true
            ),
            'is_sdd' => array(
                'title' => __('Enable SDD (Same-Day Delivery)', 'aymakan'),
                'type' => 'select',
                'description' => __('Please ensure that you have this service enabled by aymakan.', 'aymakan'),
                'desc_tip' => true,
                'default' => 0,
                'options' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ]
            ),
            'collection_name' => array(
                'title' => __('Collection Name', 'aymakan'),
                'type' => 'text',
                'description' => __('The collection name or any data below is related to your warehouse contact information. Here your dispatchers details can be provided.', 'aymakan'),
                'desc_tip' => true
            ),
            'collection_email' => array(
                'title' => __('Collection Email', 'aymakan'),
                'type' => 'text',
                'description' => __('The collection email or any data below is related to your warehouse contact information. Here your dispatchers details can be provided.', 'aymakan'),
                'desc_tip' => true
            ),
            'collection_phone' => array(
                'title' => __('Collection Phone', 'aymakan'),
                'type' => 'text',
                'description' => __('Phone number for warehouse contact information.', 'aymakan'),
                'desc_tip' => true,
                'default' => ''
            ),
            'collection_address' => array(
                'title' => __('Collection Address', 'aymakan'),
                'type' => 'text',
                'description' => __('The address from which Aymakan will be picking up the shipment.', 'aymakan'),
                'desc_tip' => true,
                'default' => ''
            ),
            'collection_city' => array(
                'title' => __('Collection City', 'aymakan'),
                'type' => 'select',
                'description' => __('The city from which Aymakan will be picking up the shipment.', 'aymakan'),
                'desc_tip' => true,
                'default' => 'Riyadh',
                'options' => $this->helper->get_cities()
            ),
            'collection_neighbourhood' => array(
                'title' => __('Collection Neighbourhood', 'aymakan'),
                'type' => 'text',
                'description' => __('The neighbourhood from which Aymakan will be picking up the shipment.', 'aymakan'),
                'desc_tip' => true,
                'default' => ''
            ),
            'shipping_cost' => array(
                'title' => __('Shipping Cost', 'aymakan'),
                'type' => 'select',
                'description' => __('These costs will be show up on checkout page.', 'aymakan'),
                'desc_tip' => true,
                'default' => 'aymakan',
                'options' => [
                    'aymakan' => __('Aymakan Cost'),
                    'free' => __('Free Cost'),
                    'custom' => __('Custom Cost'),
                ]
            ),
            'custom_cost' => array(
                'title' => __('Custom Cost', 'aymakan'),
                'type' => 'text',
                'description' => __('If the custom cost is entered Aymakan will ignore it\'s automatic cost on checkout page.', 'aymakan'),
                'desc_tip' => true,
                'default' => null
            ),
            'testing' => array(
                'title' => __('Testing', 'aymakan'),
                'type' => 'title'
            ),
            'test_mode' => array(
                'title' => __('Test Mode', 'aymakan'),
                'type' => 'checkbox',
                'description' => __('Check the checkbox for enabling test mode.', 'aymakan'),
                'desc_tip' => true,
                'default' => 'no'
            ),
            'debug' => array(
                'title' => __('Debug Log', 'aymakan'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'aymakan'),
                'default' => 'no',
                'description' => sprintf(
                    /* translators: %s Placeholder is for the file path where events are logged */
                    __('Log Aymakan events, such as WebServices requests, inside %s.', 'aymakan'),

                    '<code>woocommerce/logs/aymakan-' . sanitize_file_name(wp_hash('aymakan')) . '.txt</code>'
                )

            ),
            'webhook' => array(
                'title' => __('Webhook', 'aymakan'),
                'type' => 'title'
            ),
            'webhook_url' => array(
                'title' => __('Order Status Webhook URL', 'aymakan'),
                'type' => 'text',
                'description' => __('Please insert this webhook URL into your Aymakan dashboard to ensure that your order status is updated.', 'aymakan'),
                'desc_tip' => true,
                'disabled' => true,
                'default' => get_bloginfo('url').'/wp-json/aymakan/status'
            ),
        );
        $this->form_fields          = $this->instance_form_fields;
    }

    /**
     * Aymakan options page.
     *
     * @return void
     */
    public function admin_options()
    {
        echo '<h3>' . $this->method_title . '</h3>';
        echo '<p>' . __('Aymakan is a Saudi Arabia based courier service.', 'aymakan') . '</p>';
        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';
    }

    /**
     * Checks if the method is available.
     *
     * @param array $package Order package.
     *
     * @return bool
     */
    public function is_available($package)
    {
        $is_available = true;
        if ('no' === $this->enabled) {
            $is_available = false;
        }
        return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package);
    }

    public function calculate_shipping($package = array())
    {

        $collections = [];

        $rate = array(
            'id' => $this->id,
            'label' => $this->title,
            'cost' => 0,
            'taxes' => false,
            // 'calc_tax' => 'per_order'
        );

        if (in_array($this->helper->get_option('shipping_cost'), ['aymakan', 'custom'])) {

            $price = [];
            $weight = [];

            foreach ($package['contents'] as $content) {
                $vendorId = get_post_field('post_author', $content['product_id']);
                $product  = $content['data'];

                $collectionCity = $this->city;

                if (is_plugin_active('dokan-lite/dokan.php')) {
                    $vendor         = dokan()->vendor->get($vendorId);
                    $address        = $vendor->get_address();
                    $collectionCity = isset($address['city']) && !empty($address['city']) ? $address['city'] : $this->helper->get_option('collection_city');
                }

                $price[$vendorId][]  = isset($content['line_subtotal']) ? (int)$content['line_subtotal'] : 0;
                $weight[$vendorId][] = !empty($product->get_weight()) ? (int)$product->get_weight() : 0;

                $totalWeight = array_sum($weight[$vendorId]);

                $collections[$vendorId] = array(
                    'collection_city' => $collectionCity,
                    'delivery_city' => isset($package['destination']['city']) && $package['destination']['city'] !== '' ? $package['destination']['city'] : $collectionCity,
                    'declaredValue' => array_sum($price[$vendorId]),
                    'weight' => !empty($totalWeight) ? $totalWeight : 1
                );
            }

            if (!empty($collections)) {
                foreach ($collections as $collection) {
                    if ($this->helper->get_option('shipping_cost') === "custom") {
                        $rate['cost'] += $this->helper->get_option('custom_cost');
                    } else {
                        $response     = $this->helper->get_pricing($collection);

                        // If pricing API response is in old format
                        if (isset($response['total_price_incl_tax'])) {
                            $rate['cost'] += $response['total_price_incl_tax'];

                            // If pricing API response is in new format
                        } elseif (isset($response['data']['total_price_incl_tax'])) {
                            $rate['cost'] += $response['data']['total_price_incl_tax'];

                            // Else price is 0
                        } else {
                            $rate['cost'] += 0;
                        }
                    }
                }
            }
        }

        if (!empty($rate)) {
            $this->add_rate($rate);
        }
    }

    function aymakan_shipping_method_price($label, $method)
    {
        $cost = $method->cost;

        if ($cost > 0) {
            $label .= ' - ' . wc_price($cost);
        }

        return $label;
    }


}
