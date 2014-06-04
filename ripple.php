<?php
/**
 * Plugin Name:         WooCommerce - Ripple (JSON RPC)
 * Plugin URI:          http://github.com/orzfly/woocommerce-gateway-ripple
 * Description:         Allows you to use Ripple (JSON RPC) payment gateway with the WooCommerce plugin.
 * Author:              Yeechan Lu
 * Author URI:          http://orzfly.com
 * License:             MIT
 * Version:             1.0.0
 * Requires at least:   3.3
 * Tested up to:        3.4
 *
 */

if (!function_exists('is_woocommerce_active'))
    require_once 'woo-includes/woo-functions.php';

add_action('plugins_loaded', 'add_woocommerce_ripple_gateway', 1);

function add_woocommerce_ripple_gateway()
{

    register_activation_hook(__FILE__, 'woocommerce_ripple_activate');

    function woocommerce_ripple_activate()
    {
        if (!class_exists('WC_Payment_Gateway'))
            add_action('admin_notices', 'woocommerce_ripple_CheckNotice');

        if (!function_exists('curl_init'))
            add_action('admin_notices', 'woocommerce_ripple_CheckNoticeCURL');
    }

    add_action('admin_init', 'woocommerce_ripple_check', 0);

    function woocommerce_ripple_check()
    {

        if (!class_exists('WC_Payment_Gateway')) {
            deactivate_plugins(plugin_basename(__FILE__));
            add_action('admin_notices', 'woocommerce_ripple_CheckNotice');
        }

    }

    function woocommerce_ripple_CheckNotice()
    {
        echo __('<div class="error"><p>WooCommerce is not installed or is inactive.  Please install/activate WooCommerce before activating the WooCommerce Ripple (JSON RPC) Plugin</p></div>');
    }

    function woocommerce_ripple_CheckNoticeCURL()
    {
        echo __('<div class="error"><p>PHP CURL is required for the WooCommerce Ripple (JSON RPC) Plugin</p></div>');
    }

    add_filter('woocommerce_payment_gateways', 'add_ripple_gateway', 40);

    function add_ripple_gateway($methods)
    {
        $methods[] = 'WC_Gateway_Ripple';
        return $methods;
    }

    /* Don't continue if WooCommerce isn't activated. */
    if (!class_exists('WC_Payment_Gateway'))
        return false;

    class WC_Gateway_Ripple extends WC_Payment_Gateway
    {

        public function __construct()
        {
            $this->plugin_dir   = trailingslashit(dirname(__FILE__));
            $this->id           = 'ripple';
            $this->icon         = apply_filters('woocommerce_ripple_icon', plugin_dir_url(__FILE__) . 'ripple.png');
            $this->has_fields   = false;
            $this->method_title = __('Ripple (JSON RPC)');

            /* Load the form fields. */
            $this->init_form_fields();

            /* ripple Configuration. */
            $this->init_settings();

            $this->enabled       = $this->settings['enabled'];
            $this->title         = $this->settings['title'];
            $this->description   = $this->settings['description'];
            $this->walletAddress = $this->settings['wallet_address'];
            $this->expiration    = $this->settings['expiration'];
            $this->rpcHost       = $this->settings['rpc_host'];
            $this->rpcPort       = $this->settings['rpc_port'];
            $this->rpcPtcl       = $this->settings['rpc_ptcl'];
            $this->rpcUser       = $this->settings['rpc_user'];
            $this->rpcPass       = $this->settings['rpc_pass'];
            $this->cronSecret    = $this->settings['cron_secret'];

            add_action('woocommerce_update_options_payment_gateways_ripple', array(
                &$this,
                'process_admin_options'
            ));
            add_action('woocommerce_receipt_ripple', array(
                &$this,
                'receipt_page'
            ));
        }

        /**
         * Initialise Gateway Settings Form Fields
         */
        function init_form_fields()
        {

            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable'),
                    'type' => 'checkbox',
                    'label' => __('Enable Ripple (JSON RPC)'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Title'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'wc_amazon_sp'),
                    'default' => __('Ripple')
                ),
                'description' => array(
                    'title' => __('Description'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.'),
                    'default' => __("Checkout securely through Ripple")
                ),
                'wallet_address' => array(
                    'title' => __('Ripple Wallet Address'),
                    'type' => 'text',
                    'description' => __('The Ripple Wallet Address you want to receive funds. '),
                    'default' => ''
                ),
                'expiration' => array(
                    'title' => __('Payment Window'),
                    'type' => 'select',
                    'description' => __('Amount of time before payment window expires.'),
                    'default' => '3600',
                    'options' => array(
                        '300' => '5m',
                        '600' => '10m',
                        '900' => '15m',
                        '1800' => '30m',
                        '3600' => '1h'
                    )
                ),
                'rpc_host' => array(
                    'title' => __('JSON-RPC Hostname'),
                    'type' => 'text',
                    'description' => __('Enter <em>s1.ripple.com</em> for default public Ripple server.'),
                    'default' => 's1.ripple.com'
                ),
                'rpc_port' => array(
                    'title' => __('JSON-RPC Port'),
                    'type' => 'text',
                    'description' => __('Enter <em>51234</em> for public Ripple server.'),
                    'default' => '51234'
                ),
                'rpc_ptcl' => array(
                    'title' => __('JSON-RPC SSL'),
                    'type' => 'select',
                    'description' => __('Recommended for public Ripple server.'),
                    'default' => '1',
                    'options' => array(
                        '1' => 'https://',
                        '0' => 'http://'
                    )
                ),
                'rpc_user' => array(
                    'title' => __('JSON-RPC Username'),
                    'type' => 'text',
                    'description' => __('Leave empty for public Ripple server.'),
                    'default' => ''
                ),
                'rpc_pass' => array(
                    'title' => __('JSON-RPC Password'),
                    'type' => 'text',
                    'description' => __('Leave empty for public Ripple server.'),
                    'default' => ''
                ),
                'cron_secret' => array(
                    'title' => __('Cron Secret'),
                    'type' => 'text',
                    'description' => __('You can visit this URL to pull transactions: <b>http://address.to.your.wordpress.com/?woocommerce_ripplejson_secret=<i>SECRET</i></b>'),
                    'default' => sha1(sha1(time().lcg_value().lcg_value().lcg_value().lcg_value().lcg_value().lcg_value().lcg_value()))
                )
            );

        } // End init_form_fields()

        /**
         * Admin Panel Options
         */
        public function admin_options()
        {

?>
                <h3><?php
            _e('Ripple (JSON RPC)');
?></h3>
                <p><?php
            _e('Ripple (JSON RPC) works by sending the user to Ripple to enter their payment information.');
?></p>
                <table class="form-table">
                <?php
            // Generate the HTML For the settings form.
            $this->generate_settings_html();
?>
                        </table><!--/.form-table-->
                <?php
        } // End admin_options()

        /**
         * http build query for RFC 3986
         * needed for PHP < 5.4 compatibility
         */
        public function httpBuildQuery3986(array $params, $sep = '&')
        {
            $parts = array();
            foreach ($params as $key => $value) {
                $parts[] = sprintf('%s=%s', $key, rawurlencode($value));
            }
            return implode($sep, $parts);
        }

        public function getRippleURL($order_id)
        {
            global $woocommerce;

            $order = new WC_Order($order_id);

            $urlFields = array();

            // Destination AccountID
            $urlFields['to'] = $this->walletAddress;

            // Amount to send, including three letter currency code, e.g. 15/USD
            $urlFields['amount'] = round($order->get_total(), 2) . '/' . get_woocommerce_currency();

            // Destination tag; integer in the range 0 to 2^32-1.
            // This value must be encoded in the payment for the user to be credited.
            $urlFields['dt'] = $order->id;

            // @todo supposedly supported?
            // http://bitcoin.stackexchange.com/questions/14149/is-there-a-way-to-specify-an-invoice-id-in-ripple-uri
            $urlFields['invoiceid'] = $order->id;

            // @todo Source tag; integer in the range 0 to 2^32-1. This value must be encoded in the payment for the payment to be returned if necessary. When the payment is returned, this field is used as the destination_tag.
            //$urlFields['st'] = '';

            // A suggested name for this contact.
            $urlFields['name'] = get_bloginfo('name');

            // A URL with more information about the payment.
            $urlFields['info_url'] = $order->get_view_order_url();

            // Clients should send the user here after the payment is made.
            $urlFields['return_url'] = $order->get_checkout_order_received_url();

            // Client should send the user here if the payment is canceled.
            $urlFields['abort_url'] = $order->get_cancel_order_url();

            // The time when this payment must be completed by expressed as integer seconds since the POSIX epoch.
            $expiration       = (int) $this->expiration;
            $urlFields['exp'] = time() + $expiration;

            // URL for RFC 3986
            // @see https://ripple.com/wiki/Ripple_URIs
            $url   = "https://ripple.com//send";
            $query = $this->httpBuildQuery3986($urlFields);
            return $url . '?' . $query;
        }

        protected function getClient()
        {
            if (!$this->_client) {
                $ptcl = $this->rpcPtcl == 1 ? 'https://' : 'http://';
                $user = $this->rpcUser;
                $pass = $this->rpcPass;
                $host = $this->rpcHost;
                $port = $this->rpcPort;

                $user_pass = '';
                if ($user && $pass) {
                    $user_pass = $user . ':' . $pass . '@';
                }
                $uri = $ptcl . $user_pass . $host . ':' . $port . '/';

                try {
                    require_once('lib/JsonRpcClient.php');
                    $this->_client = new WooCommerca_Ripple_JsonRPCClient($uri);
                }
                catch (Exception $e) {
                    throw new Exception('JSON-RPC could not be reached: ' . $e->getMessage());
                }
            }
            return $this->_client;
        }

        /**
         * Receipt page
         * */
        function receipt_page($order_id)
        {
            $url = $this->getRippleURL($order_id);
            # var_dump($this->getAccountTx($this->walletAddress));
?><p><a target="_blank" href="<?php
            echo htmlspecialchars($url);
?>"><?php
            echo __('Thank you for your order, please click here to pay with Ripple. ');
?></a></p><p><?php
            echo __('After you made your payment, you have to wait several minutes before we confirmed your payment. ');
?></p><p><?php
            echo __("We will send you an email after your payment is received. Please be patient.");
?></p><?php
        }

        /**
         * Payment fields
         * */

        function payment_fields()
        {

            if (!empty($this->description))
                echo wpautop(wptexturize($this->description));

        }

        /**
         * Process the payment and return the result
         * */
        function process_payment($order_id)
        {

            $order = new WC_Order($order_id);

            // Return thankyou redirect
            return array(
                'result' => 'success',
                'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
            );

        }


        /**
        * Get info
        */
        public function getAccountInfo($account)
        {
            return $this->getClient()->account_info(array('account' => $account));
        }

        /**
        * Get tx
        */
        public function getAccountTx($account, $ledger_min = -1, $ledger_max = -1, $descending = false)
        {
            return $this->getClient()->account_tx(array(
                'account' => $account,
                'ledger_min' => $ledger_min,
                'ledger_max' => $ledger_max,
                'descending' => $descending
            ));
        }

        public function getRecentOrders()
        {
            $query_args = array(
                'fields'      => 'ids',
                'post_type'   => 'shop_order',
                'post_status' => 'publish',
                'tax_query'   => array(
                    array(
                        'taxonomy' => 'shop_order_status',
                        'field'    => 'slug',
                        'terms'    => array("pending")
                    )
                ),
                'date_query' => array(
                    array(
                        "after" => "-7 days"
                    )
                )
            );

            $query = new WP_Query( $query_args );
            $orders = array();

            return $query->posts;
        }

        public function cronJob()
        {
            $orders = $this->getRecentOrders();

            // Find initial ledger_index_min
            $ledger_min = get_option("woocommerce_ripplejson_ledger", -1);

            // -10 for deliberate overlap to avoid (possible?) gaps
            if ($ledger_min > 10) {
                $ledger_min -= 10;
            }

            // Find latest transactions from ledger
            $account_tx = $this->getAccountTx($this->walletAddress, $ledger_min);
            if (!isset($account_tx['status']) || $account_tx['status'] != 'success') {
                return false;
            }

            // Find transactions
            $ledger_max = $account_tx['ledger_index_max'];
            if (!isset($account_tx['transactions']) || empty($account_tx['transactions'])) {
                update_option("woocommerce_ripplejson_ledger", $ledger_max);
                return true;
            }

            // Match transactions with DestinationTag
            $txs = array();
            foreach ($account_tx['transactions'] as $key => $tx) {
                if (isset($tx['tx']['DestinationTag']) && $tx['tx']['DestinationTag'] > 0) {
                    $dt = $tx['tx']['DestinationTag'];
                    $txs[$dt] = $tx['tx'];
                }
            }

            // Update order statuses
            foreach ($orders as $id) {
                if (isset($txs[$id])) {
                    $order = new WC_Order($id);
                    $transactionId = $txs[$id]['hash'];

                    // Build order history note with link to sender
                    $note =sprintf(__('Paid: %s/%s'), $txs[$id]['Amount']['value'], $txs[$id]['Amount']['currency']);
                    $note .= '<br />' . sprintf(__('Ledger: %s'), $txs[$id]['inLedger']);
                    $note .= '<br />' . sprintf(__('Account: <a href="https://ripple.com/graph/#%s">%s</a>'), $txs[$id]['Account'], $txs[$id]['Account']);

                    // Check if full amount was received for this order
                    if (is_array($txs[$id]['Amount']) && $txs[$id]['Amount']['value'] == round($order->get_total(), 2) && $txs[$id]['Amount']['currency'] == get_woocommerce_currency()) {
                        $order->payment_complete();
                        $order->add_order_note($note);
                    }

                    // Manual review required
                    else {
                        $order->update_status("on-hold", $note);
                    }
                }
            }

            update_option("woocommerce_ripplejson_ledger", $ledger_max);
        }
    }
    

    if(isset($_GET['woocommerce_ripplejson_secret']))
    {
        $woocommerce_ripple = new WC_Gateway_Ripple();
        if ($_GET['woocommerce_ripplejson_secret'] == $woocommerce_ripple->cronSecret)
            add_action('init', 'woocommerce_ripple_cron_job_get', 11);
    }

    function woocommerce_ripple_cron_job_get() {
        woocommerce_ripple_cron_job(); die();
    }

    function woocommerce_ripple_cron_job() {
        $woocommerce_ripple = new WC_Gateway_Ripple();
        $woocommerce_ripple->cronJob();
    }

    if ( ! wp_next_scheduled( 'woocommerce_ripple_cron_job' ) ) {
        wp_schedule_event( time(), 'hourly', 'woocommerce_ripple_cron_job' );
    }
}