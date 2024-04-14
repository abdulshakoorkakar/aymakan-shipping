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
     * Log the received data for debugging
     * @param $request
     * @return void
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
        $new_status = $this->statusByCode($status_code);

        if ($order && !empty($new_status)) {
            $order->update_status($new_status['status']);
            $note = sprintf(__('<strong>Aymakan Order Status</strong><br>%s.', 'aymakan'), $new_status['label']);
            $order->add_order_note($note);
            unset($data['shipping']);
            update_post_meta($order_id, 'aymakan_shipping_status', json_encode($data));
        }
    }

    /**
     *
     * @param $code
     * @return array
     */
    public function statusByCode($code)
    {
        $statuses = array(
            'AY-0001' => ['status' => 'shipped', 'label' => 'AWB created at origin'],
            'AY-0005' => ['status' => 'completed', 'label' => 'Delivered'],
            'AY-0090' => ['status' => 'processing', 'label' => 'Hold for dispatch'],
            'AY-0050' => ['status' => 'processing', 'label' => 'On Hold'],
            'AY-0032' => ['status' => 'pending', 'label' => 'Pending'],
            'AY-0008' => ['status' => 'processing', 'label' => 'Returned'],
            'AY-0011' => ['status' => 'processing', 'label' => 'Cancelled'],
            'AY-0007' => ['status' => 'processing', 'label' => 'Cancelled'],
            'AY-0014' => ['status' => 'processing', 'label' => 'Cancelled'],
            'AY-0045' => ['status' => 'processing', 'label' => 'Cancelled'],
            'AY-0051' => ['status' => 'processing', 'label' => 'Cancelled'],
            'AY-0091' => ['status' => 'processing', 'label' => 'Cancelled by Consignee'],
            'AY-0105' => ['status' => 'processing', 'label' => 'Cancelled By Shipper'],
            'AY-0107' => ['status' => 'processing', 'label' => 'Cancelled By Shipper'],
            'AY-0004' => ['status' => 'shipped', 'label' => 'Out for Delivery'],
            'AY-0009' => ['status' => 'shipped', 'label' => 'In Transit'],
            'AY-0059' => ['status' => 'shipped', 'label' => 'Processing for RTO'],
            'AY-0079' => ['status' => 'shipped', 'label' => 'Processing for remote area'],
            'AY-0094' => ['status' => 'shipped', 'label' => 'MR-Processing for RTO'],
            'AY-0100' => ['status' => 'shipped', 'label' => 'Processing for RTO-External'],
            'AY-0003' => ['status' => 'shipped', 'label' => 'Received at Warehouse'],
            'AY-0012' => ['status' => 'shipped', 'label' => 'Received at Qassim hub'],
            'AY-0017' => ['status' => 'shipped', 'label' => 'Received Back in Warehouse (With number of delivery failed)'],
            'AY-0026' => ['status' => 'shipped', 'label' => 'Received at Riyadh Warehouse'],
            'AY-0027' => ['status' => 'shipped', 'label' => 'Received at Qaseem Warehouse'],
            'AY-0030' => ['status' => 'shipped', 'label' => 'Received at JED WH'],
            'AY-0034' => ['status' => 'shipped', 'label' => 'Received at DMM WH'],
            'AY-0064' => ['status' => 'shipped', 'label' => 'Received - Waiting'],
            'AY-0065' => ['status' => 'shipped', 'label' => 'Received - Address Validated'],
            'AY-0072' => ['status' => 'shipped', 'label' => 'RP-Received in WH'],
            'AY-0080' => ['status' => 'shipped', 'label' => 'Received at JAZ'],
            'AY-0082' => ['status' => 'shipped', 'label' => 'Received Tabuk warehouse'],
            'AY-0083' => ['status' => 'shipped', 'label' => 'Received Hail Warehouse'],
            'AY-0086' => ['status' => 'shipped', 'label' => 'Received At Taif warehouse'],
            'AY-0088' => ['status' => 'shipped', 'label' => 'Received in Clearing department'],
            'AY-0096' => ['status' => 'shipped', 'label' => 'Received at Hofuf Warehouse'],
            'AY-0098' => ['status' => 'shipped', 'label' => 'Received In HBT Station'],
            'AY-0101' => ['status' => 'shipped', 'label' => 'Received in Najran Station'],
            'AY-0102' => ['status' => 'shipped', 'label' => 'Received in Yanbu warehouse'],
            'AY-0103' => ['status' => 'shipped', 'label' => 'Received at Baha warehouse'],
            'AY-0104' => ['status' => 'shipped', 'label' => 'Received at Bisha warehouse'],
        );

        return isset($statuses[$code]) ? $statuses[$code] : [];
    }
}

new Aymakan_Shipping_Webhook();