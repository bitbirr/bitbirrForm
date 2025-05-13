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
    window.handleTransactionType = function(type) {
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