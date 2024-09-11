function helloMount(){
    console.log("hello after render");
    var stripe = Stripe('pk_test_51PuvDJRsZwJIlbd4SpX7fITdsnGFZMwnrwKlsJl6KKfo1BXXwHb0jtqb2xnAOiF9TfDBFonu0Wf7pIPcEQDNE7hl00L6TMbywa');
    var elements = stripe.elements();
    var card = elements.create('card');
    card.mount('#stripe-card-element');
}









