<?php
defined('ABSPATH') || exit;

class Aymakan_Shipping_Create
{
    private $helper;
    private $hpos_enabled;
    private $createShippingUrl;

    public function __construct()
    {
        $this->helper = new Aymakan_Shipping_Helper();

        $this->hpos_enabled = get_option('woocommerce_hpos_enabled', 'no');

        if ('no' !== $this->helper->get_option('enabled')) {
            add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'aymakan_render_html'), 10, 1);
            add_action('admin_enqueue_scripts', array($this, 'aymakan_enqueue_scripts'), 100);

            add_action('wp_ajax_nopriv_aymakan_manual_shipping_create', array($this, 'aymakan_manual_shipping_create'));
            add_action('wp_ajax_aymakan_manual_shipping_create', array($this, 'aymakan_manual_shipping_create'));

            add_action('wp_ajax_nopriv_aymakan_bulk_shipping_create', array($this, 'aymakan_bulk_shipping_create'));
            add_action('wp_ajax_aymakan_bulk_shipping_create', array($this, 'aymakan_bulk_shipping_create'));

            $this->setup_hooks();
        }

        $this->createShippingUrl = admin_url() . 'admin.php?page=aymakan-shipping/form/create-shipping.php&order_ids=';
    }

    private function setup_hooks()
    {

        if ($this->hpos_enabled === 'yes') {
            add_filter('bulk_actions-woocommerce_page_wc-orders', array($this, 'aymakan_bulk_shipment_actions'), 900, 2);

            add_filter('woocommerce_shop_order_list_table_columns', array($this, 'aymakan_wc_new_order_column'), 900);
            add_action('manage_woocommerce_page_wc-orders_custom_column', array($this, 'aymakan_add_action_column'), 900, 2);
        } else {
            add_filter('bulk_actions-edit-shop_order', array($this, 'aymakan_bulk_shipment_actions'), 900, 2);
            add_filter('handle_bulk_actions-edit-shop_order', array($this, 'aymakan_handle_bulk_shipment_actions'), 900, 3);

            add_filter('manage_edit-shop_order_columns', array($this, 'aymakan_wc_new_order_column'), 900);
            add_action('manage_shop_order_posts_custom_column', array($this, 'aymakan_add_action_column'), 900, 2);
        }
    }

    private function is_version_greater_than($version)
    {
        return version_compare(WC()->version, $version, '>');
    }

    public function aymakan_enqueue_scripts()
    {
        $screen = get_current_screen();
        $screen_id = $screen ? $screen->id : '';

        wp_enqueue_style('aymakan-shipping-global', plugins_url('assets/css/global.css', plugin_dir_path(__FILE__)), array(), Aymakan_Main::VERSION, 'all');

        if (in_array($screen_id, ['aymakan-shipping/form/create-shipping', 'edit-shop_order', 'shop_order', 'woocommerce_page_wc-settings', 'woocommerce_page_wc-orders'], true)) {
            wp_enqueue_style('aymakan-shipping', plugins_url('assets/css/aymakan.css', plugin_dir_path(__FILE__)), array(), Aymakan_Main::VERSION, 'all');
            wp_enqueue_script('aymakan-shipping', plugins_url('assets/js/aymakan.js', plugin_dir_path(__FILE__)), array('jquery', 'backbone', 'wp-util'), Aymakan_Main::VERSION, true);
            wp_localize_script('aymakan-shipping', 'aymakan_shipping', array('ajax_url' => admin_url('admin-ajax.php')));
        }
    }

    /**
     * Shipping form HTML
     *
     * @return string
     */
    public function aymakan_render_html()
    {
        Aymakan_Shipping_Form::output();
    }

    /**
     * Create Shipping with Aymakan
     *
     * @throws JsonException
     */
    public function aymakan_manual_shipping_create()
    {
        if (!isset($_POST['data'])) {
            echo json_encode(array('error' => true));
            die();
        }

        $param = wp_parse_args($_POST['data']);

        try {

            $order_id = isset($param['order_id']) ? wc_clean($param['order_id']) : '';
            $order = !empty($order_id) ? wc_get_order($order_id) : '';

            $data = [
                'delivery_name'           => isset($param['delivery_name']) ? sanitize_text_field($param['delivery_name']) : '',
                'delivery_email'          => isset($param['delivery_email']) ? sanitize_email($param['delivery_email']) : '',
                'delivery_city'           => isset($param['delivery_city']) ? sanitize_text_field($param['delivery_city']) : '',
                'delivery_address'        => isset($param['delivery_address']) ? wc_clean($param['delivery_address']) : '',
                'delivery_phone'          => isset($param['delivery_phone']) ? sanitize_text_field($param['delivery_phone']) : '',
                'delivery_neighbourhood'  => isset($param['delivery_neighbourhood']) ? sanitize_text_field($param['delivery_neighbourhood']) : '',
                'pieces'                  => isset($param['pieces']) ? absint($param['pieces']) : 1,
                'declared_value'          => isset($param['declared_value']) ? wc_format_decimal($param['declared_value']) : '',
                'price_set_currency'      => $order->get_currency(),
                'declared_value_currency' => $order->get_currency(),
                'is_cod'                  => isset($param['is_cod']) ? wc_clean($param['is_cod']) : '',
                'cod_amount'              => isset($param['cod_amount']) ? wc_format_decimal($param['cod_amount']) : '',
                'reference'               => isset($param['reference']) ? sanitize_text_field($param['reference']) : ($order ? $order->get_id() : ''),
            ];

            $response = $this->aymakan_response_format($order, $data);


            echo json_encode($response, JSON_THROW_ON_ERROR);
            die();

        } catch (JsonException $e) {
            if ('yes' === $this->helper->get_option('debug')) {
                $this->log->add($this->id, var_dump($e->getMessage()));
            }
        }
    }

    /**
     * Create Bulk Shipping with Aymakan
     *
     * @throws JsonException
     */
    public function aymakan_bulk_shipping_create()
    {
        $param = isset($_POST['data']) ? wp_parse_args($_POST['data']) : array();

        if (!isset($param['id'])) {
            echo json_encode([
                [
                    [
                        'id'      => 0,
                        'error'   => true,
                        'message' => esc_html__('Please select an order.', 'aymakan'),
                    ]
                ]
            ], JSON_THROW_ON_ERROR);
            die();
        }

        $letEncode = [];
        try {
            foreach ($param['id'] as $id) {
                $order = wc_get_order($id);

                $first_name = $order->get_shipping_first_name() ? $order->get_shipping_first_name() : $order->get_billing_first_name();
                $last_name = $order->get_shipping_last_name() ? $order->get_shipping_last_name() : $order->get_billing_last_name();
                $email = $order->get_billing_email();
                $address_2 = $order->get_shipping_address_2() ? ' Address 2. ' . $order->get_shipping_address_2() : ' Address 2. ' . $order->get_billing_address_2();
                $address = $order->get_shipping_address_1() ? $order->get_shipping_address_1() . $address_2 : $order->get_billing_address_1() . $address_2;
                $phone = $order->get_shipping_phone() ? $order->get_shipping_phone() : $order->get_billing_phone();
                $city = $order->get_shipping_city() ? $order->get_shipping_city() : $order->get_billing_city();

                $data = [
                    'delivery_name'           => esc_html($first_name . ' ' . $last_name),
                    'delivery_email'          => esc_html($email),
                    'delivery_city'           => esc_html($city),
                    'delivery_address'        => esc_html($address),
                    'delivery_phone'          => esc_html($phone),
                    'delivery_neighbourhood'  => esc_html($this->helper->get_option('neighbourhood')),
                    'pieces'                  => 1,
                    'declared_value'          => esc_html($order->get_total()),
                    'price_set_currency'      => esc_html($order->get_currency()),
                    'declared_value_currency' => esc_html($order->get_currency()),
                    'is_cod'                  => ($order->get_payment_method() === 'cod') ? 1 : 0,
                    'cod_amount'              => ($order->get_payment_method() === 'cod') ? esc_html($order->get_total()) : '',
                    'reference'               => esc_html($id),
                ];

                $letEncode[] = $this->aymakan_response_format($order, $data);
            }

            echo json_encode($letEncode, JSON_THROW_ON_ERROR);
            die();
        } catch (JsonException $e) {
            if ('yes' === $this->helper->get_option('debug')) {
                $this->log->add($this->id, esc_html(var_dump($e->getMessage())));
            }
        }
    }


    /**
     * @throws JsonException
     */
    public function aymakan_response_format($order, $data)
    {

        $requestedBy = esc_html(wp_get_current_user()->user_nicename);
        $orderId = $order->get_id();
        $collections = array();
        $description = '';
        $itemsCount = count($order->get_items());
        $i = 0;
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $vendorId = get_post_field('post_author', $product->get_id());

            if (is_plugin_active('dokan-lite/dokan.php')) {
                $reference = $data['reference'] . '_' . $vendorId;
                $vendor = dokan()->vendor->get($vendorId);
                $address = $vendor->get_address();
                $collectionAddress = !empty($address) ? implode(',', $address) : null;
                $collectionCity = isset($address['city']) ? $address['city'] : $this->helper->get_option('collection_city');
            } else {
                $reference = $data['reference'];
                $collectionAddress = $this->helper->get_option('collection_address');
                $collectionCity = $this->helper->get_option('collection_city');
            }

            $description .= esc_html($this->getProductShippingDescription($order, $item));

            $collections[$vendorId] = array(
                'reference'                => esc_html($reference),
                'collection_name'          => isset($vendor) ? esc_html($vendor->get_name()) : esc_html($this->helper->get_option('collection_name')),
                'collection_email'         => isset($vendor) ? $vendor->get_email() : $this->helper->get_option('collection_email'),
                'collection_city'          => $collectionCity,
                'collection_address'       => $collectionAddress,
                'collection_neighbourhood' => $this->helper->get_option('collection_neighbourhood'),
                'collection_phone'         => isset($vendor) ? $vendor->get_phone() : $this->helper->get_option('collection_phone'),
                'collection_country'       => 'SA',
                'is_sdd'                   => ($this->helper->get_option('is_sdd') && $collectionCity === $data['delivery_city']) ? 1 : 0,
                'delivery_description'     => $itemsCount === $i ? str_replace(",", "", $description) : $description,
            );

            $i++;
        }

        $delivery = array_replace($data, array(
            'delivery_country' => 'SA',
            'requested_by'     => $requestedBy,
            'submission_date'  => date('Y-m-d H:i:s'),
            'pickup_date'      => date('Y-m-d H:i:s'),
            'channel'          => 'woocommerce',
        ));

        $responses = array();

        foreach ($collections as $vendorId => $collection) {
            $shipment = array_replace($delivery, $collection);

            $collectionName = $collection['collection_name'];
            $collectionReference = $collection['reference'];

            if ($this->helper->is_shipment_created($orderId, $vendorId, $collectionReference)) {
                $responses[($vendorId) . '_'] = [
                    'id'      => $orderId,
                    'error'   => true,
                    'vendor'  => esc_html($collectionName),
                    'message' => __('Shipment Already Created', 'aymakan'),
                ];
                continue;
            }

            $response = json_decode($this->helper->api_request('/shipping/create', $shipment), false);

            $metaInfo = array();

            $message = 'Error';

            if (!empty($response->shipping)) {

                $shipping = $response->shipping;
                $tracking = $shipping->tracking_number;
                $shipping->vendor = $collectionName;

                if ($shipping->pdf_label) {
                    $message = __('Shipment Created Successfully.');
                }

                $trackpdf = !empty($shipping->pdf_label) ? '<a href="' . $shipping->pdf_label . '" target="_blank">View PDF</a>' : '';

                $order->add_order_note(__("<strong>Aymakan Shipment Created</strong>\nTracking Number: {$tracking} \nShipment: {$trackpdf} \nCreated By: {$requestedBy}\nProduct Vendor:{$collectionName} ", 'aymakan'));

                $shipping->tracking_link = 'https://aymakan.com/en/tracking/' . $tracking;
                if ('yes' === $this->helper->get_option('test_mode')) {
                    $shipping->tracking_link = 'https://dev.aymakan.com/' . $tracking;
                }
            }

            $metaInfo['id'] = $order->get_id();
            $metaInfo['vendor'] = $collectionName;
            $metaInfo['vendor_id'] = $vendorId;
            $metaInfo['reference'] = $collectionReference;
            $metaInfo['message'] = !empty($response->message) && $response->message != "" ? $response->message : $message;
            $metaInfo['tracking_link'] = !empty($shipping->tracking_link) ? $shipping->tracking_link : $this->createShippingUrl . $orderId;
            $metaInfo['tracking_number'] = !empty($shipping->tracking_number) ? $shipping->tracking_number : $this->createShippingUrl . $orderId;
            $metaInfo['pdf_label'] = !empty($shipping->pdf_label) ? $shipping->pdf_label : $this->createShippingUrl . $orderId;

            if (!empty($response->error)) {
                $metaInfo['error'] = $response->error;
            }

            if ((!empty($response->message) && empty($response->error) && empty($response->errors)) || empty($response->shipping)) {
                $metaInfo['error'] = true;
            }

            if (!empty($response->errors)) {
                $metaInfo['errors'] = $response->errors;
            }

            if (!empty($response->success)) {
                $metaInfo['success'] = $response->success;
            }

            $responses[$vendorId] = $metaInfo;
        }

        $return = $responses;

        // Remove if shipment already created
        foreach ($responses as $key => $value) {
            if (is_string($key)) {
                unset($responses[$key]);
            }
        }

        if (!empty($responses)) {
            $getMeta = get_post_meta($orderId, 'aymakan_shipping', true);
            $prevMeta = $getMeta ? json_decode($getMeta, true) : [];
            update_post_meta($orderId, 'aymakan_shipping', json_encode(array_replace($prevMeta, $responses), JSON_UNESCAPED_UNICODE));
        }

        return $return;
    }

    public function getProductShippingDescription($order, $item)
    {
        $product = $item->get_product();

        $product_name = $product->get_name();

        $variant_quantities = array();

        if ($product->is_type('variable')) {
            $product_id = $product->get_id();
            foreach ($product->get_available_variations() as $variation) {
                $variation_id = $variation['variation_id'];
                $variation_obj = new WC_Product_Variation($variation_id);
                $variation_attributes = $variation_obj->get_variation_attributes();
                $variation_quantity = 0;
                foreach ($order->get_items() as $order_item) {
                    $order_product_id = $order_item->get_product_id();
                    if ($order_product_id === $product_id && $order_item->get_variation_id() === $variation_id) {
                        $variation_quantity += $order_item->get_quantity();
                    }
                }
                $variant_info = implode(' ', $variation_attributes);
                $variant_quantities[$variant_info] = $variation_quantity;
            }
        }

        $total_quantity = $item->get_quantity();

        $delivery_description = "";
        if (!empty($variant_quantities)) {
            foreach ($variant_quantities as $variant_info => $quantity) {
                $delivery_description .= "$product_name - $variant_info x $quantity, ";
            }
        }

        return $delivery_description ? $delivery_description : " $product_name x $total_quantity, ";
    }

    /**
     * @param $actions
     * @return mixed
     */
    public function aymakan_bulk_shipment_actions($actions)
    {
        $actions['aymakan_bulk_shipment'] = __('Create Aymakan Shipments', 'aymakan');
        return $actions;
    }

    /**
     * @param $redirect_to
     * @param $action
     * @param $ids
     * @return string
     */
    public function aymakan_handle_bulk_shipment_actions($redirect_to, $action, $ids)
    {

        if ($action == 'aymakan_bulk_shipment') {
            // return admin_url() . 'admin.php?page=aymakan-shipping/form/create-shipping.php&order_ids=' . implode('|', $ids);
            return '';
        }

        return esc_url_raw($redirect_to);
    }

    public function aymakan_insert_into_array_after_key(array $source_array, string $key, array $new_element)
    {
        if (array_key_exists($key, $source_array)) {
            $position = array_search($key, array_keys($source_array)) + 1;
        } else {
            $position = count($source_array);
        }
        $before = array_slice($source_array, 0, $position, true);
        $after = array_slice($source_array, $position, null, true);
        return array_merge($before, $new_element, $after);
    }

    /**
     * @param $columns
     * @return mixed
     */
    public function aymakan_wc_new_order_column($columns)
    {
        return $this->aymakan_insert_into_array_after_key(
            $columns,
            'order_date', // Inject our columns after the "order_date" column
            array(
                'aymakan'          => 'Aymakan Action',
                'aymakan-tracking' => 'Aymakan Tracking',
            )
        );
    }

    /**
     * @param $column
     * @param $order
     * @return void
     */
    public function aymakan_add_action_column($column, $order)
    {
        $order_id = $this->hpos_enabled === 'yes' ? $order->get_id() : $order;

        $shippings = get_post_meta($order_id, 'aymakan_shipping', true);

        $trackingLink = $pdfLink = $hasError = '';
        if (!empty($shippings)) {
            foreach (json_decode($shippings) as $shipping) {
                if (isset($shipping->error)) {
                    $hasError = 'has-error';
                    if (isset($shipping->tracking_link)) {
                        $trackingLink .= '<li><a href="' . esc_html($shipping->tracking_link) . '" class="no-shipment" target="_blank">' . sprintf(__('Error (%s)'), $shipping->tracking_number, $this->helper->decodeArabicString($shipping->vendor)) . '</a></li>';
                    }
                    if (isset($shipping->pdf_label)) {
                        $pdfLink .= '<li><a href="' . esc_html($shipping->pdf_label) . '" class="no-shipment" target="_blank">' . sprintf(__('Error (%s)'), $this->helper->decodeArabicString($shipping->vendor)) . '</a></li>';
                    }
                } else {
                    if (isset($shipping->tracking_link)) {
                        $trackingLink .= '<li><a href="' . esc_html($shipping->tracking_link) . '" class="" target="_blank">' . sprintf(__('%s (%s)'), $shipping->tracking_number, $this->helper->decodeArabicString($shipping->vendor)) . '</a></li>';
                    }
                    if (isset($shipping->pdf_label)) {
                        $pdfLink .= '<li><a href="' . esc_html($shipping->pdf_label) . '" target="_blank">' . sprintf(__('AWB (%s)'), $this->helper->decodeArabicString($shipping->vendor)) . '</a></li>';
                    }
                }
            }
        }

        if ('aymakan' === $column) {
            if (empty($pdfLink)) {
                echo '<a href="' . esc_html($this->createShippingUrl . $order_id) . '" class="order-status aymakan-btn aymakan-shipping-create-btn">' . __('Create Shipment', 'aymakan') . '</a>';
            } else {
                echo '<ul class="aymakan-dropdown ' . $hasError . '">';
                echo '<li><span class="order-status aymakan-btn aymakan-awb-btn">' . __('Print Airway Bill', 'aymakan') . '</span><ul>';
                echo $pdfLink;
                echo '</li></ul>';
            }
        }

        if (('aymakan-tracking' === $column) && $trackingLink && !empty($trackingLink)) {
            echo '<ul class="aymakan-dropdown ' . $hasError . '">';
            echo '<li><span class="order-status aymakan-btn aymakan-shipping-track-btn">' . __('Tracking', 'aymakan') . '</span><ul>';
            echo $trackingLink;
            echo '</li></ul>';
        }
    }


}

new Aymakan_Shipping_Create();

