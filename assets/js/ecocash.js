jQuery(function($){
    var count = 25;
    var interval = setInterval(function(){
        count--;
        if(count <= 0){
            clearInterval(interval);
            $('#ecocash-loader').html('<h2>✅ Payment Completed</h2><p>Redirecting...</p>');
            window.location.href = '/checkout/order-received/' + ecocashOrderID;
        }
    }, 1000);
});
