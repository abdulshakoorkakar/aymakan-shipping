<section class="aymakan-shipping-form" role="main">
    <h1><?php esc_html_e('Create Aymakan Shipping', 'aymakan'); ?></h1>
    <?php
    $i = 0;
    if (isset($_GET['order_ids'])) {
        $orderIds = explode('|', $_GET['order_ids']);
        foreach ($orderIds as $order_id) {
            $form = new Aymakan_Shipping_Form($order_id);
            $order = $form->getOrder();
            $first_name = ($order && $order->get_shipping_first_name()) ? $order->get_shipping_first_name() : '';
            $last_name  = ($order &&  $order->get_shipping_last_name()) ? $order->get_shipping_last_name() : '';
            ?>
            <article>

                <button class="aymakan-toggle-header">
                    <h3 class="order-title"><?php echo '#'.$order_id; ?> <?php echo $first_name ?> <?php echo $last_name ?></h3>
                    <svg width="28" height="28" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="components-panel__arrow" aria-hidden="true" focusable="false">
                        <path class="toggle-closed" d="M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z"></path>
                        <path class="toggle-opened" d="M6.5 12.4L12 8l5.5 4.4-.9 1.2L12 10l-4.5 3.6-1-1.2z"></path>
                    </svg>
                </button>
                <div class="aymakan-form-container aymakan-toggle-content" <?php if($i === 0) { ?> style="display: block" <?php } ?>>
                    <div class="notification"></div>
                    <form id="create_shipping_form" action="" method="post">
                        <table class="widefat">
                            <thead>
                            <tr>
                                <th><?php esc_html_e('Customer Address Information', 'aymakan'); ?></th>
                                <th><?php esc_html_e('Shipping Information', 'aymakan'); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <?php echo '<td>' . $form->shipping_form('column_1') . '</td><td>' . $form->shipping_form('column_2') . '</td>'; ?>
                            </tr>
                            </tbody>
                        </table>
                        <footer>
                            <div class="inner">
                                <button class="button aymakan-shipping-create-btn button-primary button-large"><?php esc_html_e('Create Shipping', 'aymakan'); ?></button>
                            </div>
                        </footer>
                    </form>
                </div>
            </article>
        <?php $i++; }
    } ?>
</section>