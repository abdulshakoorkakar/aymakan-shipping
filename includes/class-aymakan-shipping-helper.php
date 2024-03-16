<?php
defined('ABSPATH') || exit;

/**
 * Class Aymakan_Shipping_Helper
 */
class Aymakan_Shipping_Helper
{
    /**
     * @var string
     */
    public $enabled = 'yes';

    /**
     * @var string
     */
    public $api_key = '';

    /**
     * @var string
     */
    public $test_mode = 'yes';

    /**
     * @var string
     */
    public $debug = 'yes';

    /**
     * @var string
     */
    public $endPoint = '';


    public function __construct()
    {
        add_filter('woocommerce_form_field', array($this, 'aymakan_form_extend'), 10, 4);
        $this->init();
    }

    public function init()
    {
        // Define user set variables.
        $this->enabled = $this->get_option('enabled');
        $this->api_key = $this->get_option('api_key');
        $this->test_mode = $this->get_option('test_mode');
        $this->debug = $this->get_option('debug');

        if ('no' === $this->test_mode) {
            $this->endPoint = 'https://api.aymakan.net/v2';
        } else {
            $this->endPoint = 'https://dev-api.aymakan.com.sa/v2';
        }

    }

    public function get_option($key = '', $all = false)
    {
        global $wpdb;
        $option_name = $wpdb->get_var($wpdb->prepare("SELECT option_name FROM {$wpdb->prefix}options WHERE option_name LIKE %s ORDER BY option_id DESC LIMIT 1", 'woocommerce_aymakan_%_settings'));
        if (!$option_name) {
            return '';
        }

        if ($all) {
            return json_decode($option_name);
        }

        $option = get_option($option_name);
        return (isset($option[$key]) ? $option[$key] : '');
    }

    public function api_request($segment, $params = array(), $headers = [])
    {
        $url = $this->endPoint . $segment;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_HEADER, FALSE);
        if (!empty($params)) {
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, array_merge(array(
            "Accept: application/json",
            "Authorization: " . $this->api_key
        ), $headers));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, TRUE);
        $response = curl_exec($curl);

        curl_close($curl);
        if ('yes' === $this->debug) {
            $this->add_log('Curl response: ' . $response);
        }
        return $response;
    }

    public function get_cities($defaultCity = null)
    {
        $response = json_decode($this->api_request('/cities'), true);

        if ($defaultCity) {
            $cities = [$defaultCity => $defaultCity];
        } else {
            $cities = ['' => __('Select City', 'aymakan')];
        }

        if (!empty($response['data']) && !empty($response['data']['cities'])) {
            foreach ($response['data']['cities'] as $city) {
                if (get_locale() === 'ar_SA') {
                    $cities[$city['city_en']] = $city['city_ar'];
                } else {
                    $cities[$city['city_en']] = $city['city_en'];
                }
            }
        }
        return $cities;
    }

    public function get_pricing($data)
    {

        try {
            $headers[] = 'X-API-KEY: DGD*pwY8Cnmr+a6&5nLDJhKnjt6=ZC';
            $data = [
                //todo
                'service' => 'delivery',
                'delivery_city' => isset($data['delivery_city']) ? $data['delivery_city'] : '',
                'collection_city' => isset($data['collection_city']) ? $data['collection_city'] : '',
                'weight' => isset($data['weight']) ? $data['weight'] : 1,
                'insurance' => 0,
                'declared_amount' => isset($data['declaredValue']) ? (int)$data['declaredValue'] : 0
            ];

            return json_decode($this->api_request('/service/price', $data, $headers), true);

        } catch (\Exception $exception) {
            $this->add_log($exception->getMessage());
        }
    }

    public function add_log($log)
    {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }

    /**
     * @param $field
     * @param $key
     * @param $args
     * @param $value
     * @return string
     */
    public function aymakan_form_extend($field, $key, $args, $value)
    {
        if ($args['type'] == 'hidden') {
            $field .= '<input type="' . esc_attr($args['type']) . '" name="' . esc_attr($key) . '"  value="' . esc_attr($value) . '" />';
        }
        return $field;
    }

    /**
     * Check if shipment already createed for the by the vendor id
     * @param $orderId
     * @param $vendorId
     * @return bool
     */
    public function is_shipment_created($orderId, $vendorId, $collectionReference)
    {
        $meta = get_post_meta($orderId, 'aymakan_shipping', true);

        if ($meta) {
            $decoded = json_decode($meta, true);
            $vendorIds = array_keys($decoded);
            $reference = isset($decoded[$vendorId]['reference']) ? $decoded[$vendorId]['reference'] : "";
            return (in_array($vendorId, $vendorIds) && (isset($decoded[$vendorId]['success'])) && ($reference === $collectionReference));
        }

        return false;
    }

    public function decodeArabicString($string)
    {
        $decoded_string = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $string);

        return $decoded_string;
    }

    public function dd($data)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        exit;
    }
}
