
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
        $customer = createCustomer(
            getStripeAPIKey('mandy', !$post['isTesting']),
            $post['name'],
            $card_data
        );
        $mandy_customer_id = null;
        if ($customer instanceof Stripe\Customer) {
            $_SESSION['message'][] = 'This customer @ mandy account is created';
            $mandy_customer_id = $customer->id;
        }

        //wasaike
        $customer = createCustomer(
            getStripeAPIKey('wasaike', !$post['isTesting']),
            $post['name'],
            $card_data
        );

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
            'arrive_on' => validateDate($post['arrive_on']) ? $post['arrive_on'] : null,
        ]);
        if (!$customer_db_result)
            $_SESSION['exception'][] = 'Exception: Customer insert failed.' . $db->getLastError();

        if ($post['amount'] != '' && $wasaike_customer_id != null) {
            chargeAmount(
                'new',
                !$post['isTesting'],
                $post['shop'],
                $wasaike_customer_id,
                $post['amount'],
                isset($post['capture']),
                $post['charge_percent'] / 100
            );
        }
    } elseif (isset($post['charge_customer'])) {

        $stripe_customer_id = $post[$post['shop'] . '_customer_id'];

        chargeAmount(
            'charge',
            !$post['isTesting'],
            $post['shop'],
            $stripe_customer_id,
            $post['amount'],
            isset($post['capture']),
            1,
            $post['stripe_charge_id']
        );
    }
}

function getStripeAPIKey($shop, $is_live)
{
    if ($shop == 'wasaike') {
        //wasaike
        return $is_live ? Wasaike_live : Wasaike_test;
    }
    //mandy
    return $is_live ? Mandy_live : Mandy_test;
}

function chargeAmount($mode, $is_live, $shop, $stripe_customer_id, $amount, $is_capture = true, $percent = 1, $stripe_charge_id = '')
{
    global $db;

    $amount_to_capture = intval((float) str_replace(',', '', $amount) * $percent);

    $customer = $db->where($shop . '_customer_id', $stripe_customer_id)->getOne('customer');
    $amount_authorized = $customer['amount_authorized'];
    $amount_captured = $customer['amount_captured'];

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

    //if stripe_charge_id exists and is a capture action, then start to capture authorized amount
    if ($stripe_charge_id != '' && $is_capture) {
        $stripe = new \Stripe\StripeClient(getStripeAPIKey($shop, $is_live));
        $charge = $stripe->charges->retrieve(
            $stripe_charge_id,
            []
        );
        // check the existing charge how much the authorized non captured amount is
        if ($charge->outcome->type == 'authorized' && !$charge->captured) {
            $stripe->charges->capture(
                $stripe_charge_id,
                []
            );

            $captured_authorized_amount = $charge->amount - $charge->amount_captured;

            $amount_captured += $captured_authorized_amount;
            $amount_to_capture = $customer['amount_to_capture'] - $captured_authorized_amount;

            $capture_data['amount'] = $captured_authorized_amount;

            $_SESSION['message'][] = "The amount $captured_authorized_amount JPY @ $shop is authorized. There are still $amount_to_capture JPY to capture.";

            //insert log_capture
            $log_capture_db_result = $db->insert('log_capture', $capture_data + [
                'stripe_charge_id' => $charge->id,
                'status' => 'Auth Captured'
            ]);
            if (!$log_capture_db_result)
                $_SESSION['exception'][] = 'Exception: log_capture insert failed.' . $db->getLastError();

            //update customer
            $customer_db_result = $db->where('id', $customer['id'])->update('customer', [
                'amount_captured' => $amount_captured,
                'amount_to_capture' => $amount_to_capture,
                'status' => $amount_to_capture > 0 ? 'Partially Captured' : 'Captured'
            ]);
            if (!$customer_db_result)
                $_SESSION['exception'][] = 'Exception: Customer update failed.' . $db->getLastError();
        }
    }

    // if the captured authorized amount is still not enough to cover the amount to capture, create a new charge and authorize / capture the rest amount right away
    if ($amount_to_capture > 0) {

        $charge = createCharge(getStripeAPIKey($shop, $is_live), $stripe_customer_id, $amount_to_capture, $is_capture);

        if ($charge instanceof Stripe\Charge) {
            if ($mode == 'new') {
                if ($is_capture) {
                    $amount_captured = $amount_to_capture;
                    $amount_to_capture = $amount - $amount_captured;
                } else {
                    $amount_authorized += $amount_to_capture;
                    $amount_to_capture = $amount;
                }
            } else if ($mode == 'charge') {
                if ($is_capture) {
                    $amount_captured += $amount_to_capture;
                    $amount_to_capture = $customer['amount_to_capture'] - $amount_to_capture;
                } else {
                    $amount_authorized += $amount_to_capture;
                    $amount_to_capture = $customer['amount_to_capture'];
                }
            }
            $_SESSION['message'][] = "$amount_captured JPY @ $shop is captured. There are still $amount_to_capture JPY to capture. Total is $amount.";

            //insert log_capture
            $log_capture_db_result = $db->insert('log_capture', $capture_data + [
                'stripe_charge_id' => $charge->id,
                'status' => $is_capture ? 'Success' : 'Authorized'
            ]);
            if (!$log_capture_db_result)
                $_SESSION['exception'][] = 'Exception: log_capture insert failed.' . $db->getLastError();

            //update customer
            $customer_db_result = $db->where('id', $customer['id'])->update('customer', [
                'amount_authorized' => $amount_authorized,
                'amount_captured' => $amount_captured,
                'amount_to_capture' => $amount_to_capture,
                'status' => $is_capture ? ($amount_to_capture > 0 ? 'Partially Captured' : 'Captured') : 'Authorized'
            ]);
            if (!$customer_db_result)
                $_SESSION['exception'][] = 'Exception: Customer update failed.' . $db->getLastError();

            return true;
        }
    } else {
        return true;
    }


    //insert log_capture
    $log_capture_db_result = $db->insert('log_capture', $capture_data + [
        'status' => $is_capture ? 'Failed' : 'Auth Failed'
    ]);
    if (!$log_capture_db_result)
        $_SESSION['exception'][] = 'Exception: log_capture insert failed.' . $db->getLastError();


    if ($mode == 'new') {
        $amount_to_capture = $amount;
    }
    //update customer
    $customer_db_result = $db->where('id', $customer['id'])->update('customer', [
        'amount_to_capture' => $amount_to_capture,
        'status' => $is_capture ? 'Capture Failed' : 'Auth Failed'
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

function createCharge($api, $customer_id, $amount, $is_capture = true)
{
    \Stripe\Stripe::setApiKey($api);

    return \Stripe\Charge::create([
        'amount' => $amount,
        'currency' => 'jpy',
        'statement_descriptor' => 'Wasaike Accommodation',
        'customer' => $customer_id,
        'capture' => $is_capture,
    ]);
}

function validateDate($date, $format = 'Y/m/d')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

set_exception_handler('our_global_exception_handler');
function our_global_exception_handler($exception)
{
    $_SESSION['form_data'] = $_POST;
    $_SESSION['exception'][] = 'Exception: ' . $exception->getMessage();
    header('Location: index.php');
    exit();
}
