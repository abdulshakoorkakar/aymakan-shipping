<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

global $wpdb;
$column_1 = $fields->shipping_form('column_1');
$column_2 = $fields->shipping_form('column_2');
?>
<div>
    <button class="aymakan_show_modal button">
        <?php esc_html_e('Create Aymakan Shipping', 'aymakan'); ?>
    </button>
</div><!-- Create Aymakan Shipping -->
<script type="text/template" id="tmpl-wc-aymakan-modal-shipping">
    <div class="wc-backbone-modal">
        <div class="wc-backbone-modal-content">
            <section class="wc-backbone-modal-main aymakan-form-container aymakan-shipping-form" role="main">
                <div class="notification"></div>
                <header class="wc-backbone-modal-header">
                    <h1><?php esc_html_e('Create Aymakan Shipping', 'aymakan'); ?></h1>
                    <button class="modal-close modal-close-link dashicons dashicons-no-alt">
                        <span class="screen-reader-text">Close modal panel</span>
                    </button>
                </header>
                <article>

                    <form id="create_shipping_form" action="" method="post">
                        <table class="widefat" class="">
                            <thead>
                            <tr>
                                <th><?php esc_html_e('Customer Address Information', 'aymakan'); ?></th>
                                <th><?php esc_html_e('Shipping Information', 'aymakan'); ?></th>
                            </tr>
                            </thead>
                            <?php
                            $row = '<td>' . $column_1 . '</td><td>' . $column_2 . '</td>';
                            ?>
                            <tbody data-row="<?php echo esc_attr($row); ?>">
                            <tr>
                                <?php echo $row; ?>
                            </tr>
                            </tbody>
                        </table>
                    </form>
                </article>
                <footer>
                    <div class="inner">
                        <button class="button aymakan-shipping-create-btn button-primary button-large"><?php esc_html_e('Create Shipping', 'aymakan'); ?></button>
                    </div>
                </footer>
            </section>
        </div>
    </div>
    <div class="wc-backbone-modal-backdrop modal-close"></div>
</script>

<!-- Order Note -->
<script type="text/template" id="tmpl-wc-aymakan-order-note">
    <li rel="5" class="note system-note">
        <div class="note_content">
            <p>
                <strong>Aymakan Shipment Created</strong><br>
                Tracking Number: {{ data.tracking_number }}<br>
                Shipment: <a href="{{ data.pdf_label }}" target="_blank">View PDF</a><br>
                Created By: {{ data.requested_by }}
            </p>
        </div>
        <p class="meta">
            <abbr class="exact-date">{{ data.created_at }}</abbr>
            <a href="#" class="delete_note" role="button"><?php esc_html_e('Delete note', 'aymakan'); ?></a>
        </p>
    </li>
</script>
