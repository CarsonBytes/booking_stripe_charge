
<?php
require_once('config.php');
function handleFormSubmit($post)
{
    global $db;
    if (isset($post['new_customer'])) {
        $card_data = [
            'card' => [
                'number' => trim($post['cc-number']),
                'exp_month' => trim(explode('/', $post['cc-exp'])[0]),
                'exp_year' => trim(explode('/', $post['cc-exp'])[1]),
                'currency' => 'JPY'
            ]
        ];

        if (trim($post['cc-cvc']) != '000') $card_data['card']['cvc'] = trim($post['cc-cvc']);

        //mandy
        $api = $post['isTesting'] == 1 ? Mandy_test : Mandy_live;

        $customer = createCustomer($api, $post['name'], $card_data);
        $mandy_customer_id = null;
        if ($customer instanceof Stripe\Customer) {
            $_SESSION['message'][] = 'This customer @ mandy account is created';
            $mandy_customer_id = $customer->id;
        }

        //wasaike
        $api = $post['isTesting'] == 1 ? Wasaike_test : Wasaike_live;

        $customer = createCustomer($api, $post['name'], $card_data);

        $wasaike_customer_id = null;
        if ($customer instanceof Stripe\Customer) {
            $_SESSION['message'][] = 'This customer @ wasaike account is created';
            $wasaike_customer_id = $customer->id;
        }

        $customer_db_result = $db->insert('customer', [
            'wasaike_customer_id' => $wasaike_customer_id,
            'mandy_customer_id' => $mandy_customer_id,
            'customer_name' => $post['name'] ?? null,
            'card_number' => $card_data['card']['number'] ?? null,
            'card_exp_month' => $card_data['card']['exp_month'] ?? null,
            'card_exp_year' => $card_data['card']['exp_year'] ?? null,
            'card_cvc' => $card_data['card']['cvc'] ?? null,
            'card_brand' => $customer->sources->data[0]->brand,
            'card_country' => $customer->sources->data[0]->country,
            'amount_to_capture' => $post['amount'] ?? null,
        ]);
        if (!$customer_db_result)
            $_SESSION['exception'][] = 'Exception: Customer insert failed.' . $db->getLastError();

        if ($post['amount'] != '' && $wasaike_customer_id != null) {
            captureAmount($post['isTesting'] == 0, 'wasaike', $api, $wasaike_customer_id, $post['amount'], 0.5);
        }
    } else {
    }
}

function captureAmount($is_live, $shop, $api, $stripe_customer_id, $amount, $percent)
{
    global $db;

    $amount_to_capture = intval((float) str_replace(',', '', $amount) * $percent);

    $customer = $db->where($shop . '_customer_id', $stripe_customer_id)->getOne('customer');

    $capture_data = [
        'is_live' => $is_live,
        'shop' => $shop == 'mandy' ? 'M' : 'W',
        'stripe_customer_id' => $stripe_customer_id,
        'customer_id' => $customer['id'] ?? null,
        'customer_name' =>  $customer['customer_name'] ?? null,
        'card_number' =>  $customer['card_number'] ?? null,
        'card_exp_month' =>  $customer['card_exp_month'] ?? null,
        'card_exp_year' =>  $customer['card_exp_year'] ?? null,
        'card_cvc' =>  $customer['card_cvc'] ?? null,
        'card_brand' =>  $customer['card_brand'] ?? null,
        'card_country' =>  $customer['card_country'] ?? null,
        'amount' => $amount_to_capture,
    ];

    $charge = createCharge($api, $stripe_customer_id, $amount_to_capture);

    if ($charge instanceof Stripe\Charge) {
        $amount_captured = $amount_to_capture;
        $amount_to_capture = $amount - $amount_captured;
        $_SESSION['message'][] = "$amount_captured JPY @ $shop is captured. There are still $amount_to_capture JPY to capture. Total is $amount.";

        //insert log_capture
        $log_capture_db_result = $db->insert('log_capture', $capture_data + [
            'status' => 'success'
        ]);
        if (!$log_capture_db_result)
            $_SESSION['exception'][] = 'Exception: log_capture insert failed.' . $db->getLastError();

        //update customer
        $customer_db_result = $db->where('id', $customer['id'])->update('customer', [
            'amount_captured' => $amount_captured,
            'amount_to_capture' => $amount_to_capture,
            'status' => 'Deposited'
        ]);
        if (!$customer_db_result)
            $_SESSION['exception'][] = 'Exception: Customer update failed.' . $db->getLastError();

        return true;
    }

    //insert log_capture
    $log_capture_db_result = $db->insert('log_capture', $capture_data + [
        'status' => 'failed'
    ]);
    if (!$log_capture_db_result)
        $_SESSION['exception'][] = 'Exception: log_capture insert failed.' . $db->getLastError();

    //update customer
    $customer_db_result = $db->where('id', $customer['id'])->update('customer', [
        'status' => 'Deposit Failed'
    ]);
    if (!$customer_db_result)
        $_SESSION['exception'][] = 'Exception: Customer update failed.' . $db->getLastError();

    return false;
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
    $_SESSION['form_data'] = $post;
    $_SESSION['exception'][] = 'Exception: ' . $exception->getMessage();
    header('Location: index.php');
    exit();
}
