<?php
error_reporting(E_ALL | E_WARNING | E_NOTICE);
ini_set('display_errors', TRUE);
session_start();

require_once('vendor/autoload.php');

if (!isset($_SESSION['message'])) $_SESSION['message'] = array();
if (!isset($_SESSION['exception'])) $_SESSION['exception'] = array();

include('partials/common/functions.php');
if (isset($_POST['charge_customer'])) {

    if ($_POST['shop'] == 'mandy') {
        //mandy
        $api = $_POST['isTesting'] == 1 ? Mandy_test : Mandy_live;
        $customer_id = $_POST['mandy_customer_id'];
    } else {
        //wasaike
        $api = $_POST['isTesting'] == 1 ? Wasaike_test : Wasaike_live;
        $customer_id = $_POST['wasaike_customer_id'];
    }

    $amount = '';
    if ($_POST['amount'] != '') {
        //echo $customer->id;
        $amount = (float) str_replace(',', '', $_POST['amount']) * $_POST['charge_percent'] / 100;
        $charge = createCharge($api, $customer_id, $amount);
        if ($charge instanceof Stripe\Charge) {
            $_SESSION['message'][] = 'The charge ' . $amount . 'yen @ ' . $_POST['shop'] . ' account is executed';
        }
    }
    $customer_data = array(
        'wasaike_customer_id' => $_POST['wasaike_customer_id'],
        'mandy_customer_id' => $_POST['mandy_customer_id'],
        'name' => $_POST['customer_name'],
        'last4' => $_POST['last4'],
        'created' => time(),
        'amount' => $amount,
        'is_live' => $_POST['isTesting'] == 0 ? 1 : 0,
        'charger' => $_POST['shop'] == 'mandy' ? 'M' : 'W'
    );
    pushChargeArray($customer_data);
    header('Location: index.php');
    exit();
} elseif (isset($_POST['new_customer'])) {
    $card_data = [
        'card' => [
            'number' => trim($_POST['cc-number']),
            'exp_month' => trim(explode('/', $_POST['cc-exp'])[0]),
            'exp_year' => trim(explode('/', $_POST['cc-exp'])[1]),
            'currency' => 'JPY'
        ]
    ];

    if (trim($_POST['cc-cvc']) != '000') $card_data['card']['cvc'] = trim($_POST['cc-cvc']);


    //wasaike
    $api = $_POST['isTesting'] == 1 ? Wasaike_test : Wasaike_live;
    $customer = createCustomer($api, $_POST['name'], $card_data);

    if ($customer instanceof Stripe\Customer) {
        $_SESSION['message'][] = 'This customer @ wasaike account is created';

        $amount = '';
        if ($_POST['amount'] != '') {
            //echo $customer->id;
            $amount = (float) str_replace(',', '', $_POST['amount']) / 2;
            $charge = createCharge($api, $customer->id, $amount);
            if ($charge instanceof Stripe\Charge) {
                $_SESSION['message'][] = 'The charge ' . $amount . 'yen @ wasaike account is executed';
            } else {
                $amount = '';
            }
        }

        $customer_data = array(
            'wasaike_customer_id' => $customer->id,
            'name' => $customer->name,
            'created' => $customer->created,
            'amount' => $amount,
            'brand' => $customer->sources->data[0]->brand,
            'country' => $customer->sources->data[0]->country,
            'last4' => $customer->sources->data[0]->last4,
            'is_live' => $_POST['isTesting'] == 0 ? 1 : 0,
            'charger' => 'W'
        );
    }

    //mandy
    $api = $_POST['isTesting'] == 1 ? Mandy_test : Mandy_live;
    $customer = createCustomer($api, $_POST['name'], $card_data);

    if ($customer instanceof Stripe\Customer) {
        $_SESSION['message'][] = 'This customer @ mandy account is created';
        $customer_data['mandy_customer_id'] = $customer->id;
    }

    pushChargeArray($customer_data);

    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php require 'partials/common/style.php' ?>

</head>

<body style="margin: 15px; position:relative;">
    <div class="messages">
        <?php
        if (isset($_SESSION['exception'])) {
            foreach ($_SESSION['exception'] as $exception) {
        ?>
                <div class="alert alert-warning alert-dismissible" role="alert">
                    <?php echo $exception ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
        <?php
            }
            $_SESSION['exception'] = array();
        }
        ?>
        <?php
        if (isset($_SESSION['message'])) {
            foreach ($_SESSION['message'] as $message) {
        ?>
                <div class="alert alert-primary alert-dismissible" role="alert">
                    <?php echo $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
        <?php
            }
            $_SESSION['message'] = array();
        }
        ?>
    </div>

    <div class="header">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php if (!isset($_SESSION['form_data']['new_customer'])) { ?>active<?php } ?>" id="charge-tab" data-bs-toggle="tab" data-bs-target="#charge" type="button" role="tab" aria-controls="charge" aria-selected="true">Successful Captures</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php if (isset($_SESSION['form_data']['new_customer'])) { ?>active<?php } ?>" id="add-charge-tab" data-bs-toggle="tab" data-bs-target="#add-charge" type="button" role="tab" aria-controls="add-charge" aria-selected="false">Add New Customer</button>
            </li>
        </ul>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="flexSwitchCheckChecked" checked>
            <label class="form-check-label" for="flexSwitchCheckChecked">Live</label>
        </div>
    </div>
    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade <?php if (!isset($_SESSION['form_data']['new_customer'])) { ?>show active<?php } ?>" id="charge" role="tabpanel" aria-labelledby="charge-tab" tabindex="0">
            <?php require 'partials/charge_customer.php'; ?>
        </div>
        <div class="tab-pane fade <?php if (isset($_SESSION['form_data']['new_customer'])) { ?>show active<?php } ?>" id="add-charge" role="tabpanel" aria-labelledby="add-charge-tab" tabindex="0">
            <?php require 'partials/new_customer.php'; ?>
        </div>
    </div>
    <?php require 'partials/common/script.php'; ?>

    <div class="html_template" style="display: none;">
        <svg xmlns="http://www.w3.org/2000/svg">
            <symbol id="check-circle-fill" viewBox="0 0 16 16">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
            </symbol>
            <symbol id="info-fill" viewBox="0 0 16 16">
                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z" />
            </symbol>
            <symbol id="exclamation-triangle-fill" viewBox="0 0 16 16">
                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
            </symbol>
        </svg>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <div class="text"></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
</body>

</html>


<?php
