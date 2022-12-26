
<?php
require_once('config.php');
function pushChargeArray($customer_data)
{

    $tempArray = json_decode(file_get_contents('customers.json'));

    if ($tempArray == null) {
        $tempArray = [];
    }

    array_push($tempArray, $customer_data);

    file_put_contents('customers.json', json_encode($tempArray));
}

function createCustomer($api, $customer_name, $card_data)
{
    \Stripe\Stripe::setApiKey($api);
    $token = \Stripe\Token::create($card_data);

    $user_data = [
        'name' => $customer_name,
        'source' => $token['id']
    ];

    return \Stripe\Customer::create($user_data, ['api_key' => $api]);
}

function createCharge($api, $customer_id, $amount)
{
    \Stripe\Stripe::setApiKey($api);

    return \Stripe\Charge::create([
        'amount' => $amount,
        'currency' => 'jpy',
        'statement_descriptor' => 'Wasaike Accommodation',
        'customer' => $customer_id,
    ]);
}

set_exception_handler('our_global_exception_handler');
function our_global_exception_handler($exception)
{
    $_SESSION['form_data'] = $_POST;
    $_SESSION['exception'][] = 'Exception: ' . $exception->getMessage();
    header('Location: index.php');
    exit();
}
