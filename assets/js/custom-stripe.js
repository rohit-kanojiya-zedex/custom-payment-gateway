// jQuery(function($) {
//     function initializeStripe() {
//         const stripe = Stripe('pk_test_51PuvDJRsZwJIlbd4SpX7fITdsnGFZMwnrwKlsJl6KKfo1BXXwHb0jtqb2xnAOiF9TfDBFonu0Wf7pIPcEQDNE7hl00L6TMbywa');
//         const style = {
//             base: {
//                 fontSize: '25px',
//                 color: '#32325d',
//             },
//         };
//
//         // const card = stripe.elements().create('card',{style}).mount('#stripe-card-element');
//
//         const elements = stripe.elements();
//         const card = elements.create('card',{style});
//         card.mount('#stripe-card-element');


        // $('#stripe-payment-form').on('submit', function(event) {
        // $('form.checkout').on('submit', function(event) {
        //     event.preventDefault();
        //     const $form = $('#stripe-payment-form');
        //     stripe.createToken(card).then(function(result) {
        //         console.log(result?.token?.id);
        //         if (result?.error) {
        //             $('#stripe-card-errors').text(result?.error?.message);
        //         } else {
        //             $('<input>').attr({
        //                 type: 'text',
        //                 name: 'stripeToken',
        //                 value: 'rohit'+result?.token?.id
        //             }).appendTo($form);
        //             $form.off('submit').submit();
        //         }
        //     });
        // });
//    }
//
//     $(document).ready(function() {
//         initializeStripe();
//     });
//
//     const checkInterval = setInterval(function() {
//         if ($('#stripe-card-element').children().length < 1 ) {
//             initializeStripe();
//             clearInterval(checkInterval);
//         }
//     }, 1000);
//
// });







jQuery(document).ready(function($) {
    if ($('form.checkout').length > 0) {
        var stripe = Stripe('pk_test_51PuvDJRsZwJIlbd4SpX7fITdsnGFZMwnrwKlsJl6KKfo1BXXwHb0jtqb2xnAOiF9TfDBFonu0Wf7pIPcEQDNE7hl00L6TMbywa');
        var elements = stripe.elements();
        var card = elements.create('card');
        card.mount('#stripe-card-element');

        $('form.checkout').on('submit', function(event) {
            event.preventDefault();
            $(this).find('button').prop('disabled', true); // Disable the button to prevent multiple submissions

            stripe.createToken(card).then(function(result) {
                if (result.error) {
                    $('#stripe-card-errors').text(result.error.message);
                    $('form.checkout').find('button').prop('disabled', false); // Re-enable the button
                } else {
                    stripeTokenHandler(result.token);
                }
            });
        });

        function stripeTokenHandler(token) {
            var $form = $('form.checkout');
            var $hiddenInput = $('<input>', {
                type: 'hidden',
                name: 'stripeToken',
                value: token.id
            });
            $form.append($hiddenInput);
            $form.get(0).submit(); // Submit the form after appending the token
        }
    }
});

//
// jQuery(document).ready(function($) {
//     if ($('form#stripe-payment-form').length > 0) {
//         var stripe = Stripe('your-publishable-key'); // Replace with your publishable key
//         var elements = stripe.elements();
//         var card = elements.create('card');
//         card.mount('#stripe-card-element');
//
//         $('form#stripe-payment-form').on('submit', function(event) {
//             event.preventDefault(); // Prevent default form submission
//             $(this).find('button').prop('disabled', true); // Disable the button to prevent multiple submissions
//
//             stripe.createToken(card).then(function(result) {
//                 if (result.error) {
//                     $('#stripe-card-errors').text(result.error.message);
//                     $('form#stripe-payment-form').find('button').prop('disabled', false); // Re-enable the button
//                 } else {
//                     stripeTokenHandler(result.token);
//                 }
//             });
//         });
//
//         function stripeTokenHandler(token) {
//             var $form = $('form#stripe-payment-form');
//             var $hiddenInput = $('<input>', {
//                 type: 'hidden',
//                 name: 'stripeToken',
//                 value: token.id
//             });
//             $form.append($hiddenInput);
//             $form.get(0).submit(); // Submit the form after appending the token
//         }
//     }
// });























//
//     $(document).ready(function() {
//     // Initialize Stripe
//     var stripe = Stripe('your-publishable-key-here'); // Replace with your actual Stripe publishable key
//     var elements = stripe.elements();
//
//     // Create an instance of the card Element
//     var card = elements.create('card');
//
//     // Add an instance of the card Element into the `card-element` div
//     card.mount('#stripe-card-element');
//
//     // Handle form submission
//     $('#stripe-payment-form').on('submit', function(event) {
//     event.preventDefault(); // Prevent the form from submitting the default way
//
//     // Create a token or card source with Stripe
//     stripe.createToken(card).then(function(result) {
//     if (result.error) {
//     // Display error.message in #stripe-card-errors
//     $('#stripe-card-errors').text(result.error.message);
// } else {
//     // Send the token to your server
//     $.ajax({
//     url: '/your-server-endpoint', // Replace with your server endpoint
//     method: 'POST',
//     data: {
//     token: result.token.id,
//     // Add any additional data you need to send to the server
// },
//     success: function(response) {
//     // Handle successful response
//     console.log('Payment successful:', response);
//     // Redirect or update the UI accordingly
// },
//     error: function(jqXHR, textStatus, errorThrown) {
//     // Handle errors
//     console.error('Payment error:', textStatus, errorThrown);
//     $('#stripe-card-errors').text('There was an error with your payment. Please try again.');
// }
// });
// }
// });
// });
// });


