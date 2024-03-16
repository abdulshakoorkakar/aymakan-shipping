<?php
defined('ABSPATH') || exit;

class Aymakan_Shipping_Webhook
{

    /**
     * Hook into the WordPress REST API initialization
     */
    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_webhook_endpoint'));
    }

    /**
     * Register a custom REST API endpoint for handling webhooks
     * @return void
     */
    public function register_webhook_endpoint()
    {
        register_rest_route('aymakan', '/status', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     *
     * @param $code
     * @return string
     */
    public function statusByCode($code)
    {
        $statuses = array(
            'AY-0005' => ['status' => 'completed', 'label' => 'Delivered'],
            'AY-0090' => ['status' => 'on-hold', 'label' => 'Hold for dispatch'],
            'AY-0050' => ['status' => 'on-hold', 'label' => 'On Hold'],
            'AY-0032' => ['status' => 'pending', 'label' => 'Pending'],
            'AY-0008' => ['status' => 'cancelled', 'label' => 'Returned'],
            'AY-0011' => ['status' => 'cancelled', 'label' => 'Cancelled'],
            'AY-0007' => ['status' => 'cancelled', 'label' => 'Cancelled'],
            'AY-0014' => ['status' => 'cancelled', 'label' => 'Cancelled'],
            'AY-0045' => ['status' => 'cancelled', 'label' => 'Cancelled'],
            'AY-0051' => ['status' => 'cancelled', 'label' => 'Cancelled'],
            'AY-0091' => ['status' => 'cancelled', 'label' => 'Cancelled by Consignee'],
            'AY-0105' => ['status' => 'cancelled', 'label' => 'Cancelled By Shipper'],
            'AY-0107' => ['status' => 'cancelled', 'label' => 'Cancelled By Shipper'],
            'AY-0004' => ['status' => 'processing', 'label' => 'Out for Delivery'],
            'AY-0009' => ['status' => 'processing', 'label' => 'In Transit'],
            'AY-0059' => ['status' => 'processing', 'label' => 'Processing for RTO'],
            'AY-0079' => ['status' => 'processing', 'label' => 'Processing for remote area'],
            'AY-0094' => ['status' => 'processing', 'label' => 'MR-Processing for RTO'],
            'AY-0100' => ['status' => 'processing', 'label' => 'Processing for RTO-External'],
            'AY-0003' => ['status' => 'processing', 'label' => 'Received at Warehouse'],
            'AY-0012' => ['status' => 'processing', 'label' => 'Received at Qassim hub'],
            'AY-0017' => ['status' => 'processing', 'label' => 'Received Back in Warehouse (With number of delivery failed)'],
            'AY-0026' => ['status' => 'processing', 'label' => 'Received at Riyadh Warehouse'],
            'AY-0027' => ['status' => 'processing', 'label' => 'Received at Qaseem Warehouse'],
            'AY-0030' => ['status' => 'processing', 'label' => 'Received at JED WH'],
            'AY-0034' => ['status' => 'processing', 'label' => 'Received at DMM WH'],
            'AY-0064' => ['status' => 'processing', 'label' => 'Received - Waiting'],
            'AY-0065' => ['status' => 'processing', 'label' => 'Received - Address Validated'],
            'AY-0072' => ['status' => 'processing', 'label' => 'RP-Received in WH'],
            'AY-0080' => ['status' => 'processing', 'label' => 'Received at JAZ'],
            'AY-0082' => ['status' => 'processing', 'label' => 'Received Tabuk warehouse'],
            'AY-0083' => ['status' => 'processing', 'label' => 'Received Hail Warehouse'],
            'AY-0086' => ['status' => 'processing', 'label' => 'Received At Taif warehouse'],
            'AY-0088' => ['status' => 'processing', 'label' => 'Received in Clearing department'],
            'AY-0096' => ['status' => 'processing', 'label' => 'Received at Hofuf Warehouse'],
            'AY-0098' => ['status' => 'processing', 'label' => 'Received In HBT Station'],
            'AY-0101' => ['status' => 'processing', 'label' => 'Received in Najran Station'],
            'AY-0102' => ['status' => 'processing', 'label' => 'Received in Yanbu warehouse'],
            'AY-0103' => ['status' => 'processing', 'label' => 'Received at Baha warehouse'],
            'AY-0104' => ['status' => 'processing', 'label' => 'Received at Bisha warehouse'],
        );

        return isset($statuses[$code]) ? $statuses[$code] : ['status' => 'processing', 'label' => 'Processing'];
    }


    /**
     * Log the received data for debugging
     * @param $request
     * @return WP_REST_Response
     */
    public function handle_webhook($request)
    {
        $data = $request->get_json_params();

        if ($this->validate_webhook_data($data)) {
            $this->update_order_status($data);
        } else {
            error_log('Invalid Webhook Data: ' . print_r($data, true));
        }
    }

    /**
     * Perform validation logic here based on the received data
     * @param $data
     * @return bool
     */
    private function validate_webhook_data($data)
    {
        return isset($data['reference']) && isset($data['status']);
    }

    /**
     * Update the order status in your WordPress/WooCommerce database
     * @param $data
     * @return void
     */
    private function update_order_status($data)
    {
        $order_id = $data['reference'];
        $status_code = $data['status'];
        $order = wc_get_order($order_id);
        if ($order) {
            $new_status = $this->statusByCode($status_code);
            $order->update_status($new_status['status']);
            $note = sprintf(__('<strong>Aymakan Order Status</strong><br>%s.', 'aymakan'), $new_status['label']);
            $order->add_order_note($note);
        }
    }
}

new Aymakan_Shipping_Webhook();