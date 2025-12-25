<?php
/**
 * Plugin Name: EcoCash gateway for WooCommerce
 * Description: Accept EcoCash payments in WooCommerce with real-time processing.
 * Version: 1.0.0
 * Author: T.Titus
  * Author URI: https://zimwebtech.co.zw
 * Plugin URI: https://eduzim.co.zw/mutoko-connect-wifi/
 */

if (!defined('ABSPATH')) exit;

// #1 Create waiting page on activation
register_activation_hook(__FILE__, 'ecocash_create_waiting_page');
function ecocash_create_waiting_page() {
    $slug = 'ecocash-waiting';
    if (!get_page_by_path($slug)) {
        wp_insert_post([
            'post_title' => 'EcoCash Payment',
            'post_name' => $slug,
            'post_content' => '[ecocash_waiting]',
            'post_status' => 'publish',
            'post_type' => 'page'
        ]);
    }
}

//#2 Payment gateway
add_action('plugins_loaded', 'ecocash_gateway_init');
function ecocash_gateway_init() {
    if (!class_exists('WC_Payment_Gateway')) return;

    class WC_Gateway_EcoCash extends WC_Payment_Gateway {

        public $api_key;

        public function __construct() {
            $this->id = 'ecocash';
            $this->method_title = 'EcoCash';
            $this->has_fields = true;

            $this->init_form_fields();
            $this->init_settings();

            $this->enabled = $this->get_option('enabled', 'yes');
            $this->title   = $this->get_option('title', 'EcoCash');
            $this->api_key = $this->get_option('api_key');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        }

        public function init_form_fields() {
            $this->form_fields = [
                'enabled' => [
                    'title' => 'Enable/Disable',
                    'type' => 'checkbox',
                    'label' => 'Enable EcoCash',
                    'default' => 'yes'
                ],
                'title' => [
                    'title' => 'Title',
                    'type' => 'text',
                    'default' => 'EcoCash'
                ],
                'api_key' => [
                    'title' => 'EcoCash API Key',
                    'type' => 'text',
                    'description' => 'Your EcoCash API key.',
                    'default' => ''
                ],
                'sandbox' => [
                    'title' => 'Sandbox Mode',
                    'type' => 'checkbox',
                    'label' => 'Enable sandbox',
                    'default' => 'yes'
                ]
            ];
        }

public function payment_fields() {
    ?>
    <style>
    /* Container styling like PayPal card */
.ecocash-payment-card {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    max-width: 400px;
    margin: 20px auto;
    font-family: Arial, sans-serif;
    box-sizing: border-box; /* ensures padding doesn't overflow */
}

/* Title styling */
.ecocash-payment-card h3 {
    font-size: 1.5em;
    margin-bottom: 15px;
    color: #28a745; /* EcoCash green */
}

/* Label styling */
.ecocash-payment-card label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
    color: #333;
}

/* Input styling */
.ecocash-payment-card input[type="text"] {
    width: 100%;
    padding: 12px 15px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 1em;
    transition: border-color 0.3s, box-shadow 0.3s;
    box-sizing: border-box; /* prevents overflow */
}

/* Input focus effect */
.ecocash-payment-card input[type="text"]:focus {
    border-color: #28a745;
    box-shadow: 0 0 5px rgba(40, 167, 69, 0.5);
    outline: none;
}

/* Optional submit button styling */
.ecocash-payment-card .ecocash-pay-btn {
    background: #28a745;
    color: #fff;
    font-size: 1.1em;
    padding: 12px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    width: 100%;
    box-sizing: border-box; /* prevents button overflow */
    transition: background 0.3s;
}

.ecocash-payment-card .ecocash-pay-btn:hover {
    background: #218838;
}

    </style>

    <div class="ecocash-payment-card">
        <h3>
    Pay with 
    <span style="color:blue; font-weight:bold;">Eco</span><span style="color:red; font-weight:bold;">Cash</span>
</h3>

        <p>Enter your EcoCash phone number to proceed:</p>
        <label for="ecocash_msisdn">Phone Number</label>
        <input type="text" name="ecocash_msisdn" id="ecocash_msisdn" placeholder="26377xxxxxxx" required>

<div id="ecocash-error" style="color:red; font-size:13px; display:none; margin-top:4px;">
    Invalid EcoCash number. Use format 2637XXXXXXXX
</div>

<div id="ecocash-success" style="color:green; font-size:13px; display:none; margin-top:4px;">
    Number looks valid ✅
</div>

<script>
function attachEcocashValidation() {
    const input = document.getElementById('ecocash_msisdn');
    const error = document.getElementById('ecocash-error');
    const success = document.getElementById('ecocash-success');
    const checkoutBtn = document.querySelector('#place_order');

    if (!input) return;

    function sanitizeNumber(raw) {
        return raw.replace(/\D+/g, '');
    }

    function normalizeZimbabwe(number) {
        if (number.startsWith('07')) number = '263' + number.slice(1);
        if (number.startsWith('2630')) number = '263' + number.slice(4);
        return number;
    }

    function isValidEcocash(number) {
        return /^2637\d{8}$/.test(number);
    }

    function validateInput() {
        let raw = input.value;
        let clean = sanitizeNumber(raw);
        let normalized = normalizeZimbabwe(clean);

        if (normalized.length >= 10) input.value = normalized;

        if (isValidEcocash(normalized)) {
            input.style.borderColor = 'green';
            error.style.display = 'none';
            success.style.display = 'block';
            if (checkoutBtn) checkoutBtn.disabled = false;
        } else {
            input.style.borderColor = 'red';
            error.style.display = 'block';
            success.style.display = 'none';
            if (checkoutBtn) checkoutBtn.disabled = true;
        }
    }

    // remove previous listeners to avoid duplicates
    input.removeEventListener('input', validateInput);
    input.removeEventListener('blur', validateInput);

    input.addEventListener('input', validateInput);
    input.addEventListener('blur', validateInput);
}

// Run on page load
document.addEventListener('DOMContentLoaded', attachEcocashValidation);

// Run after WooCommerce checkout updates
jQuery(document.body).on('updated_checkout', attachEcocashValidation);
</script>


        <!-- Optional inline Pay Now button if desired -->
		<br>
         <button class="ecocash-pay-btn" style="margin-top:2px;">Pay Now</button> 
    </div>
    <?php
}


        public function validate_fields() {
            if (empty($_POST['ecocash_msisdn'])) {
                wc_add_notice('Please enter your EcoCash phone number', 'error');
                return false;
            }
            return true;
        }

        private function generate_uuid_v4() {
            $data = random_bytes(16);
            $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
            $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            $msisdn = sanitize_text_field($_POST['ecocash_msisdn']);
            if(!preg_match('/^263\d{9}$/', $msisdn)) {
                $msisdn = preg_replace('/^0/', '', $msisdn);
                $msisdn = '263'.$msisdn;
            }

            $amount = $order->get_total();
            $currency = 'USD';
            $sourceReference = $this->generate_uuid_v4();

            update_post_meta($order_id, '_ecocash_sourceReference', $sourceReference);
            update_post_meta($order_id, '_ecocash_msisdn', $msisdn);

            $url = $this->get_option('sandbox') === 'yes'
                ? 'https://developers.ecocash.co.zw/api/ecocash_pay/api/v2/payment/instant/c2b/sandbox'
                : 'https://developers.ecocash.co.zw/api/ecocash_pay/api/v2/payment/instant/c2b';

            $body = [
                'customerMsisdn' => $msisdn,
                'amount' => floatval($amount),
                'reason' => 'WooCommerce Order #' . $order_id,
                'currency' => $currency,
                'sourceReference' => $sourceReference
            ];

            $args = [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-API-KEY' => $this->api_key
                ],
                'body' => wp_json_encode($body),
                'timeout' => 30
            ];

            $response = wp_remote_post($url, $args);
            $body_response = wp_remote_retrieve_body($response);
            error_log('EcoCash Request: '.wp_json_encode($body));
            error_log('EcoCash Response: '.$body_response);

            // Always go to waiting page
            $order->update_status('pending', 'Awaiting EcoCash confirmation');
            $waiting_page = get_permalink(get_page_by_path('ecocash-waiting'));
            return [
                'result' => 'success',
                'redirect' => add_query_arg([
                    'order_id' => $order_id,
                    'msisdn' => $msisdn,
                    'sourceReference' => $sourceReference
                ], $waiting_page)
            ];
        }
    }

    add_filter('woocommerce_payment_gateways', function($methods){
        $methods[] = 'WC_Gateway_EcoCash';
        return $methods;
    });
}

// #3 Waiting page shortcode with countdown and AJAX polling using Transaction Lookup API
add_shortcode('ecocash_waiting', function($atts){
    $order_id = intval($_GET['order_id'] ?? 0);
    $order = wc_get_order($order_id);
    if(!$order) return 'Order not found.';

    $sourceReference = get_post_meta($order_id, '_ecocash_sourceReference', true);
    $msisdn = get_post_meta($order_id, '_ecocash_msisdn', true);
    $api_key = get_option('woocommerce_ecocash_settings')['api_key'] ?? '';

    ob_start(); ?>
    <div id="ecocash-waiting" style="text-align:center; max-width:400px; margin:40px auto; font-family:sans-serif;">
  	<h3>
    Processing
    <span style="color:blue; font-weight:bold;">Eco</span><span style="color:red; font-weight:bold;">Cash</span>
	Payment...
	</h3>
        <p id="ecocash-message">Please wait up to 30 seconds.</p>
        <div style="width:100%;background:#eee;height:20px;border-radius:10px;overflow:hidden;margin-bottom:10px;">
            <div id="progress-bar" style="width:0%;height:100%;background:#4caf50;"></div>
        </div>
        <p>Time remaining: <span id="countdown">30</span>s</p>
    </div>
    <script>
    let width = 0;
    let timeLeft = 30;
    let progressBar = document.getElementById('progress-bar');
    let countdown = document.getElementById('countdown');
    let message = document.getElementById('ecocash-message');

    let progressInterval = setInterval(function(){
        if(width>=100) clearInterval(progressInterval);
        width += 4;
        progressBar.style.width = width + '%';
    }, 1000);

    let countdownInterval = setInterval(function(){
        timeLeft--;
        countdown.textContent = timeLeft;
if(timeLeft<=0) {
    clearInterval(countdownInterval);

    // Turn progress bar red and flashing
    progressBar.style.background = 'red';
    progressBar.style.animation = 'flash 1s infinite';

    // Add keyframes for flashing
    const style = document.createElement('style');
    style.innerHTML = `
        @keyframes flash {
            0% { opacity: 1; }
            50% { opacity: 0.3; }
            100% { opacity: 1; }
        }
    `;
    document.head.appendChild(style);

    // Show failed message
    const wpRoot = window.location.pathname.split('/').slice(0,2).join('/');
    const myAccountUrl = window.location.origin + wpRoot + '/my-account/orders/';
    message.innerHTML = '<p style="color:red;">Payment Failed. Please make sure Ecocash number you entered is correct/you have enough funds in your account and try again. ' +
                        'Go to <a href="' + myAccountUrl + '" style="color:blue; text-decoration:underline;">My Account</a> to complete your payment.</p>';
}

    },1000);

    function checkOrderStatus(){
        fetch('<?php echo admin_url("admin-ajax.php"); ?>?action=ecocash_check_order&order_id=<?php echo $order_id; ?>')
        .then(res=>res.json())
        .then(async data=>{
            if(data.status==='completed'){
                message.textContent = "Payment received! Redirecting...";
                progressBar.style.width = '100%';
                setTimeout(()=>{ window.location.href = '<?php echo $order->get_checkout_order_received_url(); ?>'; }, 1500);
            } else {
                // Use Transaction Lookup API
                const response = await fetch('<?php echo admin_url("admin-ajax.php"); ?>?action=ecocash_lookup&order_id=<?php echo $order_id; ?>');
                const json = await response.json();
                if(json.status==='SUCCESS'){
                    message.textContent = "Payment received! Redirecting...";
                    progressBar.style.width = '100%';
                    setTimeout(()=>{ window.location.href = '<?php echo $order->get_checkout_order_received_url(); ?>'; }, 1500);
                } else {
                    setTimeout(checkOrderStatus, 3000);
                }
            }
        });
    }
    setTimeout(checkOrderStatus,2000);
    </script>
    <?php
    return ob_get_clean();
});

// #4 AJAX: poll transaction lookup
add_action('wp_ajax_nopriv_ecocash_lookup','ecocash_lookup');
add_action('wp_ajax_ecocash_lookup','ecocash_lookup');
function ecocash_lookup(){
    $order_id = intval($_GET['order_id'] ?? 0);
    $order = wc_get_order($order_id);
    if(!$order) wp_send_json(['status'=>'NOTFOUND']);

    $sourceReference = get_post_meta($order_id, '_ecocash_sourceReference', true);
    $msisdn = get_post_meta($order_id, '_ecocash_msisdn', true);
    $api_key = get_option('woocommerce_ecocash_settings')['api_key'] ?? '';
    $sandbox = get_option('woocommerce_ecocash_settings')['sandbox'] ?? 'yes';

    $url = $sandbox==='yes'
        ? 'https://developers.ecocash.co.zw/api/ecocash_pay/api/v1/transaction/c2b/status/sandbox'
        : 'https://developers.ecocash.co.zw/api/ecocash_pay/api/v1/transaction/c2b/status';

    $body = [
        'sourceMobileNumber' => $msisdn,
        'sourceReference' => $sourceReference
    ];

    $args = [
        'headers'=>[
            'Content-Type'=>'application/json',
            'X-API-KEY'=>$api_key
        ],
        'body'=>wp_json_encode($body),
        'timeout'=>20
    ];

    $response = wp_remote_post($url, $args);
    $resp_body = wp_remote_retrieve_body($response);

    $data = json_decode($resp_body,true);
    if($data && isset($data['status']) && $data['status']==='SUCCESS'){
		// oNLY REDUCE STOCK IF ORDER IS PROCESSESED
		        wc_reduce_stock_levels($order_id);
        WC()->cart->empty_cart(); 
        $order->update_status('processing','EcoCash payment confirmed via Transaction Lookup');
    }

    wp_send_json(['status'=>$data['status'] ?? 'PENDING']);
}

// #5 Optional webhook: same as before
add_action('init', function(){
    add_rewrite_rule('^mutoko-connect-wifi/ecocash-callback/?','index.php?ecocash_callback=1','top');
    add_rewrite_tag('%ecocash_callback%','1');
});
add_action('template_redirect', function(){
    if(get_query_var('ecocash_callback')){
        ecocash_callback_handler();
        exit;
    }
});

function ecocash_callback_handler(){
    $data = json_decode(file_get_contents('php://input'), true);
    if(!$data || !isset($data['sourceReference'])) wp_send_json(['success'=>false]);

    $args = [
        'post_type' => 'shop_order',
        'post_status' => 'any',
        'meta_query' => [
            [
                'key' => '_ecocash_sourceReference',
                'value' => sanitize_text_field($data['sourceReference']),
                'compare' => '='
            ]
        ]
    ];
    $orders = get_posts($args);
    if(!$orders) wp_send_json(['success'=>false]);

    $order = wc_get_order($orders[0]->ID);
    $order->update_status('processing', 'EcoCash payment confirmed via callback');
    wp_send_json(['success'=>true]);
}
