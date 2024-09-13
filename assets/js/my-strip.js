function mountAndGenerateToken() {
    stripe = Stripe(stripeObj.publishable_key);
    const elements = stripe.elements();
    card = elements.create('card');
    card.mount('#stripe-card-element');
    jQuery(function($) {
        $('form.checkout').on('submit', function (event) {
            event.preventDefault();
            stripe.createToken(card).then(function (result) {
                if (result?.error) {
                    $('#stripe-card-errors').text(result?.error?.message);
                } else {
                    $.post(stripeObj.ajaxurl, {
                        action: 'stripAction',
                        data : {token: result?.token?.id},
                    }, function (response) {
                        if (response?.status) {
                            $('form.checkout').off('submit').submit();
                        }else{
                            $('#stripe-card-errors').text('An error occurred while processing the payment.');
                        }
                    }, 'json')
                }
            });
        });
    });
}