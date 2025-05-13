<?php
/**
 * Plugin Name: BitBirr Cryptocurrency Exchange
 * Description: A WordPress plugin for BitBirr Cryptocurrency exchange form with real-time exchange rates, Chapa payments, and email notifications.
 * Version: 1.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Create a shortcode to display the BitBirr Form
function bitbirr_form_shortcode() {
    ob_start();
    bitbirr_form_html();
    return ob_get_clean();
}

add_shortcode('bitbirr_form', 'bitbirr_form_shortcode');

// Enqueue scripts and styles
function bitbirr_enqueue_scripts() {
    wp_enqueue_style('bitbirr-style', plugin_dir_url(__FILE__) . 'assets/css/style.css');
    wp_enqueue_script('bitbirr-script', plugin_dir_url(__FILE__) . 'assets/js/script.js', ['jquery'], null, true);
}
add_action('wp_enqueue_scripts', 'bitbirr_enqueue_scripts');

// Display the HTML form
function bitbirr_form_html() {
    ?>
    <div id="bitbirr-exchange-form" class="bitbirr-exchange-form">
        <img src="<?php echo plugin_dir_url(__FILE__) . 'assets/bitbirr-logo.png'; ?>" alt="BitBirr Logo" class="w-32 mx-auto mb-6" />
        <div id="loading" class="loading hidden"><span>Loading...</span></div>

        <div class="flex space-x-4 mb-6">
            <div class="transaction-type buy active" onclick="handleTransactionType('buy')">Buy</div>
            <div class="transaction-type sell" onclick="handleTransactionType('sell')">Sell</div>
        </div>

        <form id="exchangeForm">
            <label for="coin">Select Coin:</label>
            <select id="coin" name="coin">
                <option value="tether" data-commission="3">Tether (USDT)</option>
                <option value="bitcoin" data-commission="10">Bitcoin (BTC)</option>
                <option value="ethereum" data-commission="5">Ethereum (ETH)</option>
            </select>

            <label for="amount">Amount:</label>
            <input type="number" id="amount" name="amount" min="1" required />

            <p>Total Cost (USD): <span id="totalCost">$0.00</span></p>
            <p>Total Cost (ETB): <span id="totalCostETB">0.00 ETB</span></p>

            <button type="submit" class="submit-button">Proceed to Payment</button>
        </form>
    </div>
    <script>
        (function($) {
            let exchangeRates = {};
            let usdToETB = 0;
            const selectedCoinElement = $('#coin');
            const amountElement = $('#amount');

            // Function to fetch exchange rates from the API
            function fetchExchangeRates() {
                $.get('https://api.coingecko.com/api/v3/simple/price?ids=tether,bitcoin,ethereum&vs_currencies=usd', function(data) {
                    exchangeRates = data;
                    calculateTotal();
                });
            }

            // Function to fetch USD to ETB conversion rate
            function fetchUsdToETB() {
                $.get('https://api.coingecko.com/api/v3/simple/price?ids=usd&vs_currencies=etb', function(data) {
                    usdToETB = data.usd.etb;
                    calculateTotal();
                });
            }

            // Function to calculate total cost
            function calculateTotal() {
                const selectedCoin = selectedCoinElement.find(':selected');
                const rate = exchangeRates[selectedCoin.val()]?.usd || 0;
                const commission = parseFloat(selectedCoin.data('commission')) || 0;
                const amount = parseFloat(amountElement.val()) || 0;
                const totalCost = amount * rate + commission;
                const totalCostETB = totalCost * usdToETB;

                $('#totalCost').text(`$${totalCost.toFixed(2)}`);
                $('#totalCostETB').text(`${totalCostETB.toFixed(2)} ETB`);
            }

            // Handle transaction type toggle
            function handleTransactionType(type) {
                $('.transaction-type').removeClass('active');
                $('.transaction-type.' + type).addClass('active');
                calculateTotal(); // Recalculate total on type change
            }

            selectedCoinElement.change(calculateTotal);
            amountElement.on('input', calculateTotal);

            $('#exchangeForm').submit(function(e) {
                e.preventDefault();

                const txRef = `BBTX-${Date.now()}`;
                const selectedCoin = selectedCoinElement.find(':selected').text();
                const userAmount = amountElement.val();
                const totalCostETB = parseFloat($('#totalCostETB').text());

                const paymentData = {
                    amount: totalCostETB.toFixed(2),
                    currency: 'ETB',
                    email: 'customer@example.com',  // This should be replaced with an actual email input
                    first_name: 'Customer',          // Replace with dynamic user data if available
                    last_name: 'BitBirr',            // Replace with dynamic user data if available
                    tx_ref: txRef,
                    callback_url: 'https://bitbirr.net/thank-you',
                    return_url: 'https://bitbirr.net',
                    customization: {
                        title: 'BitBirr Transaction',
                        description: `Purchase of ${userAmount} ${selectedCoin}`
                    }
                };

                const queryParams = $.param(paymentData);
                window.location.href = `https://api.chapa.co/v1/hosted/pay?${queryParams}`;
            });

            // Initialize functions
            fetchExchangeRates();
            fetchUsdToETB();
        })(jQuery);
    </script>
    <?php
}