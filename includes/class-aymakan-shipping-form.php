<?php
defined('ABSPATH') || exit;

/**
 * Class Aymakan_Shipping_Form
 */
class Aymakan_Shipping_Form
{
    public $orderId;
    public $helper;

    public function __construct($orderId)
    {
        $this->orderId = $orderId;
        $this->helper = new Aymakan_Shipping_Helper();
    }

    /**
     * Output the metabox.
     */
    public static function output()
    {
        global $theorder;

        $order  =  wc_get_order($theorder->get_id());

        $fields = new self($theorder->get_id());

        include 'views/shipping-form.php';
    }

    public function getOrder() {
        return wc_get_order($this->orderId);
    }

    /**
     * @param $order
     * @param $column
     * @return string
     */
    public function shipping_form($column)
    {

        $order = $this->getOrder();

        // Get the customer shipping and billing email
        $email      = $order->get_billing_email();
        $phone      = $order->get_billing_phone();
        $first_name = $order->get_shipping_first_name() ? $order->get_shipping_first_name() : '';
        $last_name  = $order->get_shipping_last_name() ? $order->get_shipping_last_name() : '';
        $address_2  = $order->get_shipping_address_2() ? '|| ' . $order->get_shipping_address_2() : '';
        $address_1  = $order->get_shipping_address_1() ? $order->get_shipping_address_1() . $address_2 : '';
        $city       = $order->get_shipping_city() ? $order->get_shipping_city() : '';
        $state      = $order->get_shipping_state() ? $order->get_shipping_state() : '';

        $company    = $order->get_shipping_company() ? $order->get_shipping_company() : '';
        $postcode   = $order->get_shipping_postcode() ? $order->get_shipping_postcode() : '';
        $country    = $order->get_shipping_country() ? $order->get_shipping_country() : '';

        $fields = array();
        switch ($column):
            case ('column_1'):
                $fields = array(
                    'delivery_name' => array(
                        'title' => __('Name', 'aymakan'),
                        'label' => __('Name', 'aymakan'),
                        'type' => 'text',
                        'description' => __('Enter Customer Name.', 'aymakan'),
                        'class' => array('desc_tip'),
                        'default' => $first_name . '' . $last_name,
                        'required' => true
                    ),
                    'delivery_email' => array(
                        'title' => __('Email', 'aymakan'),
                        'label' => __('Email', 'aymakan'),
                        'type' => 'text',
                        'description' => __('Enter Customer Email.', 'aymakan'),
                        'class' => array('desc_tip'),
                        'default' => $email,
                        'required' => false
                    ),
                    'delivery_phone' => array(
                        'title' => __('Phone', 'aymakan'),
                        'label' => __('Phone', 'aymakan'),
                        'type' => 'text',
                        'description' => __('Enter Phone Number.', 'aymakan'),
                        'class' => array('desc_tip'),
                        'default' => $phone,
                        'required' => true
                    ),
                    'delivery_address' => array(
                        'title' => __('Address', 'aymakan'),
                        'label' => __('Address', 'aymakan'),
                        'type' => 'text',
                        'description' => __('Enter Address.', 'aymakan'),
                        'class' => array('desc_tip'),
                        'default' => $address_1,
                        'required' => true
                    ),
                    'delivery_city' => array(
                        'title' => __('City', 'aymakan'),
                        'label' => __('City', 'aymakan'),
                        'type' => 'select',
                        'options' => $this->helper->get_cities($city),
                        'description' => __('Select City.', 'aymakan'),
                        'class' => array('desc_tip'),
                        'default' => $city
                    ),
                    'delivery_neighbourhood' => array(
                        'title' => __('Neighbourhood', 'aymakan'),
                        'label' => __('Neighbourhood', 'aymakan'),
                        'type' => 'text',
                        'description' => __('Enter Neighbourhood.', 'aymakan'),
                        'class' => array('desc_tip'),
                        'default' => $state
                    ),
                    /*'delivery_postcode' => array(
                        'title' => __('Postcode', 'aymakan'),
                        'label' => __('Postcode', 'aymakan'),
                        'type' => 'text',
                        'description' => __('Enter Address Postcode.', 'aymakan'),
                        'class' => array('desc_tip'),
                        'default' => $postcode
                    ),*/
                );
                break;
            case ('column_2'):
                $fields = array(
                    'reference' => array(
                        'title' => __('Reference', 'aymakan'),
                        'label' => __('Reference', 'aymakan'),
                        'type' => 'text',
                        'description' => __('Reference must be order number.', 'aymakan'),
                        'class' => array('desc_tip'),
                        'default' => $order->get_id(),
                        'required' => false
                    ),
                    'declared_value' => array(
                        'title' => __('Order Total', 'aymakan'),
                        'label' => __('Order Total', 'aymakan'),
                        'type' => 'text',
                        'description' => __('Order grand total.', 'aymakan'),
                        'class' => array('desc_tip'),
                        'default' => $order->get_total(),
                        'required' => true
                    ),
                    'is_cod' => array(
                        'title' => __('Is COD', 'aymakan'),
                        'label' => __('Is COD', 'aymakan'),
                        'type' => 'select',
                        'options' => array(
                            'No',
                            'Yes'
                        ),
                        'description' => __('If order is COD, then select Yes.', 'aymakan'),
                        'class' => array('desc_tip'),
                        'default' => ($order->get_payment_method() == 'cod') ? 1 : 0
                    ),
                    'cod_amount' => array(
                        'title' => __('COD Amount', 'aymakan'),
                        'label' => __('COD Amount', 'aymakan'),
                        'type' => 'text',
                        'description' => __('If order is COD, then COD amount is the amount Aymakan driver will be collecting from your customer.', 'aymakan'),
                        'class' => array('desc_tip'),
                        'default' => ($order->get_payment_method() == 'cod') ? $order->get_total() : '',
                        'required' => true
                    ),
                    'items' => array(
                        'title' => __('Items', 'aymakan'),
                        'label' => __('Items', 'aymakan'),
                        'type' => 'text',
                        'description' => __('Number of items in the shipment.', 'aymakan'),
                        'class' => array('desc_tip'),
                        'default' => count($order->get_items()),
                        'required' => false
                    ),
                    'pieces' => array(
                        'title' => __('Pieces', 'aymakan'),
                        'label' => __('Pieces', 'aymakan'),
                        'type' => 'text',
                        'description' => __('Pieces in the shipment. For example, for a large orders, the items can be boxed in multiple cartons. The number of boxed cartons should be entered here.', 'aymakan'),
                        'class' => array('desc_tip'),
                        'default' => 1,
                        'required' => true
                    ),
                    'order_id' => array(
                        'type' => 'hidden',
                        'default' => $order->get_id(),
                    )
                );
                break;
        endswitch;

        $html = '';
        foreach ($fields as $key => $field) {
            $field['return'] = true;
            $value           = isset($field['default']) ? $field['default'] : '';
            $html           .= woocommerce_form_field($key, $field, $value);
        }
        return $html;
    }
}

?>