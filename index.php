<?php
error_reporting(E_ALL | E_WARNING | E_NOTICE);
ini_set('display_errors', TRUE);
set_exception_handler('our_global_exception_handler');
session_start();

if (!isset($_SESSION['message'])) $_SESSION['message'] = array();
if (!isset($_SESSION['exception'])) $_SESSION['exception'] = array();

require_once('config.php');

if (isset($_POST['name'])) {
    require_once('vendor/autoload.php');
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

function our_global_exception_handler($exception)
{
    $_SESSION['exception'][] = 'Exception: ' . $exception->getMessage();
    header('Location: index.php');
    exit();
}


if (isset($_POST['charge'])) {
    require_once('vendor/autoload.php');

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
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/tabulator-tables@4.9.3/dist/css/tabulator.min.css">

    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">

    <style type="text/css" media="screen">
        .header {
            display: flex;
            justify-content: space-between;
        }

        input.invalid {
            border: 2px solid red;
        }

        .validation.failed {
            display: block;
        }

        .validation.failed:after {
            color: red;
            content: 'Validation failed';
        }

        .validation.passed {
            display: none;
        }

        .validation.passed:after {
            color: green;
            content: 'Validation passed';
        }
    </style>

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
                <button class="nav-link active" id="charge-tab" data-bs-toggle="tab" data-bs-target="#charge" type="button" role="tab" aria-controls="charge" aria-selected="true">Charge Existing Customer</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="add-charge-tab" data-bs-toggle="tab" data-bs-target="#add-charge" type="button" role="tab" aria-controls="add-charge" aria-selected="false">Add new customer</button>
            </li>
        </ul>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="flexSwitchCheckChecked" checked>
            <label class="form-check-label" for="flexSwitchCheckChecked">Live</label>
        </div>
    </div>
    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="charge" role="tabpanel" aria-labelledby="charge-tab" tabindex="0">
            <form action="" method="post" autocomplete="on" id="charge">
                <input type="hidden" name="isTesting" value="<?php echo isset($_POST['isTesting']) ? $_POST['isTesting'] : 0; ?>" />
                <div class="form-check mt-3">
                    <input class="form-check-input" type="radio" name="shop" value="mandy" id="shop-mandy" checked>
                    <label class="form-check-label" for="shop-mandy">
                        Mandy
                    </label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="shop" value="wasaike" id="shop-wasaike">
                    <label class="form-check-label" for="shop-wasaike">
                        Wasaike
                    </label>
                </div>

                <div id="example-table"></div>

                <input type="hidden" name="mandy_customer_id" value="" required>

                <input type="hidden" name="wasaike_customer_id" value="" required>

                <input type="hidden" name="customer_name" value="" required>

                <input type="hidden" name="last4" value="" required>

                <div class="row mb-3 mt-3">
                    <div class="form-floating col">
                        <input type="text" class="amount form-control" name="amount" value="<?php echo isset($_POST['amount']) ? $_POST['amount'] : '' ?>" autocomplete="off" pattern="\d*" required placeholder="Total">
                        <label for="amount">Total</label>
                    </div>

                    <div class="col-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="charge_percent" value="100" id="charge_percent-100" checked>
                            <label class="form-check-label" for="charge_percent-100">
                                100%
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="charge_percent" value="50" id="charge_percent-50">
                            <label class="form-check-label" for="charge_percent-50">
                                50%
                            </label>
                        </div>
                    </div>
                </div>

                <div class="float-end">
                    <button class="btn btn-primary" type="submit" name="charge" value="1">Submit</button>
                </div>
            </form>
        </div>
        <div class="tab-pane fade" id="add-charge" role="tabpanel" aria-labelledby="add-charge-tab" tabindex="0">
            <form action="" method="post" autocomplete="on" id="add_card">
                <input type="hidden" name="isTesting" value="<?php echo isset($_POST['isTesting']) ? $_POST['isTesting'] : 0; ?>" required />
                <div class="form-floating mb-3 mt-3">
                    <input type="text" class="name form-control" name="name" placeholder="Name" value="<?php echo isset($_POST['name']) ? $_POST['name'] : '' ?>" required>
                    <label for="name">Name</label>
                </div>

                <div class="form-floating mb-3 input-group">
                    <input type="text" class="cc-number form-control" name="cc-number" value="<?php echo isset($_POST['cc-number']) ? $_POST['cc-number'] : '' ?>" pattern="\d*" x-autocompletetype="cc-number" placeholder="Card number" required>
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Use Test Card...</button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item visa" href="#">Visa</a></li>
                        <li><a class="dropdown-item visa-debit" href="#">Visa (debit)</a></li>
                    </ul>
                    <label for="cc-number">Card number</label>
                </div>

                <div class="row g-3">
                    <div class="form-floating col">
                        <input type="text" class="cc-exp form-control" name="cc-exp" value="<?php echo isset($_POST['cc-exp']) ? $_POST['cc-exp'] : '' ?>" x-autocompletetype="cc-exp" placeholder="MM/YY Expires" required maxlength="9">
                        <label for="cc-exp">MM/YY</label>
                    </div>
                    <div class="form-floating col-3">
                        <input type="text" class="cc-cvc form-control" name="cc-cvc" value="<?php echo isset($_POST['cc-cvc']) ? $_POST['cc-cvc'] : '' ?>" pattern="\d*" x-autocompletetype="cc-csc" placeholder="CVC" autocomplete="off">
                        <label for="cc-cvc">CVC</label>
                    </div>
                    <div class="form-floating col">
                        <input type="text" class="amount form-control" name="amount" value="<?php echo isset($_POST['amount']) ? $_POST['amount'] : '' ?>" autocomplete="off" pattern="\d*" required placeholder="Total">
                        <label for="amount">Total</label>
                    </div>
                </div>
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <svg style="width: 1em; height: 1em;" class="bi flex-shrink-0 me-2" role="img" aria-label="Warning:">
                        <use xlink:href="#exclamation-triangle-fill" />
                    </svg>
                    <div>
                        Please be sure to enter the total amount! This amount will first be charged by 50% for Wasaike...
                    </div>
                </div>

                <div class="alert alert-danger validation passed" role="alert"></div>

                <div class="float-end">
                    <button class="btn btn-primary" type="submit" id="add_customer" name="add_customer" value="1">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <script type="text/javascript" src="lib/payment.js"></script>
    <script>
        var J = Payment.J,
            //numeric = document.querySelector('[data-numeric]'),
            number = document.querySelector('.cc-number'),
            exp = document.querySelector('.cc-exp'),
            cvc = document.querySelector('.cc-cvc'),
            validation = document.querySelector('.validation');

        //Payment.restrictNumeric(numeric);
        Payment.formatCardNumber(number);
        Payment.formatCardExpiry(exp);
        Payment.formatCardCVC(cvc);

        document.querySelector('#add_customer').onclick = function(e) {
            J.toggleClass(document.querySelectorAll('input'), 'invalid');
            J.removeClass(validation, 'passed failed');

            var cardType = Payment.fns.cardType(J.val(number));

            J.toggleClass(number, 'invalid', !Payment.fns.validateCardNumber(J.val(number)));
            J.toggleClass(exp, 'invalid', !Payment.fns.validateCardExpiry(Payment.cardExpiryVal(exp)));

            J.toggleClass(cvc, 'invalid', !Payment.fns.validateCardCVC(J.val(cvc), cardType));

            if (document.querySelectorAll('.invalid').length) {
                J.addClass(validation, 'failed');
                return false;
            } else {
                J.addClass(validation, 'passed');
            }
        }
    </script>

    <!-- <script type="text/javascript" src="jquery-3.4.1.min.js"></script> -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/jquery@3.6.2/dist/jquery.min.js"></script>

    <!-- <script type="text/javascript" src="/tabulator/dist/js/tabulator.min.js"></script> -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/tabulator-tables@4.9.3/dist/js/tabulator.min.js"></script>

    <!--<script type="text/javascript" src="/tabulator/moment.js"></script> -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>


    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js" integrity="sha384-7+zCNj/IqJ95wo16oMtfsKbZ9ccEh31eOz1HGyDuCQ6wgnyJNSYdrPa03rtR1zdB" crossorigin="anonymous"></script>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js"></script>

    <script>
        function changetimestamp2date(value, data, type, cell) {
            return new Date(value * 1000);
        }
        //Build Tabulator
        var table = new Tabulator("#example-table", {
            height: false,
            layout: "fitColumns",
            selectable: 1,
            pagination: "local", //enable local pagination.
            paginationSize: 5, // this option can take any positive integer value (default = 10)
            clipboard: true,
            clipboardCopyStyled: false,
            placeholder: "No Data Set",
            columns: [
                //{title:"ID@Wasaike", field:"wasaike_customer_id", sorter:"string", width:150},
                //{title:"ID@Mandy", field:"mandy_customer_id", sorter:"string", width:150},
                {
                    title: "Live?",
                    field: "is_live",
                    formatter: "tick",
                    width: 30
                },
                {
                    title: "Charger",
                    field: "charger",
                    sorter: "string",
                    width: 50
                },
                {
                    title: "Name",
                    field: "name",
                    sorter: "string",
                    width: 120
                },
                {
                    title: "Created",
                    field: "created",
                    sorter: "date",
                    mutator: changetimestamp2date,
                    align: "center"
                },
                {
                    title: "Amount",
                    field: "amount",
                    formatter: "money",
                    formatterParams: {
                        symbol: "円",
                        precision: false
                    },
                    width: 80
                },
                {
                    title: "Last 4 digit",
                    field: "last4",
                    width: 50
                },
                {
                    title: "Brand",
                    field: "brand",
                    sorter: "string",
                    width: 80
                },
                {
                    title: "Country",
                    field: "country",
                    sorter: "string",
                    width: 40
                }
            ],

            ajaxResponse: function(url, params, response) {
                return response.reverse();
            },
            rowDeselected: function(row) {
                row_selected = false;
            },
            rowSelected: function(row) {
                $('form#charge input[name=mandy_customer_id]').val(row._row.data.mandy_customer_id);
                $('form#charge input[name=wasaike_customer_id]').val(row._row.data.wasaike_customer_id);
                $('form#charge input[name=customer_name]').val(row._row.data.name);
                $('form#charge input[name=last4]').val(row._row.data.last4);
                $('form#charge input[name=amount]').val(row._row.data.amount);
                $('#flexSwitchCheckChecked').prop('checked', row._row.data.is_live == 1 ? true : false).trigger('change');
                row_selected = true;
            }
        });
        var ajaxConfig = {
            method: "post", //set request type to Position
            headers: {
                "Content-type": 'application/json; charset=utf-8', //set specific content type
            },
        };

        table.setData("customers.json", {}, ajaxConfig); //make ajax request with advanced config options
    </script>

    <script>
        var row_selected = false;
        jQuery(function($) {
            $('body').on('change', '#flexSwitchCheckChecked', function(e) {
                if ($('#flexSwitchCheckChecked').is(':checked')) {
                    $('[name="isTesting"]').val(0);
                } else {
                    $('[name="isTesting"]').val(1);
                }
            }).on('click', '.dropdown-menu .visa', function(e) {
                e.preventDefault();
                var year = new Date().getFullYear() + 10;
                year = year.toString().substr(-2);
                $('#flexSwitchCheckChecked').prop('checked', false).trigger('change');
                $(this).parents('form').find('[name="cc-number"]').val('4242424242424242');
                $(this).parents('form').find('[name="cc-exp"]').val('12/' + year.toString().substr(-2));
                $(this).parents('form').find('[name="cc-cvc"]').val('123');
            }).on('click', '.dropdown-menu .visa-debit', function(e) {
                e.preventDefault();
                var year = new Date().getFullYear() + 10;
                year = year.toString().substr(-2);
                $('#flexSwitchCheckChecked').prop('checked', false).trigger('change');
                $(this).parents('form').find('[name="cc-number"]').val('4000056655665556');
                $(this).parents('form').find('[name="cc-exp"]').val('12/' + year.toString().substr(-2));
                $(this).parents('form').find('[name="cc-cvc"]').val('123');
            }).on('click', 'form#charge [type="submit"]', function(e) {
                $('.messages').html('');
                if (($('form#charge').find('[name="amount"]').val() % 2 != 0 && $('form#charge').find('#charge_percent-50').is(':checked')) || $('form#add_card').find('[name="amount"]') <= 0) {
                    $('.html_template .alert .text').text('Please ensure the amount is an even positive value.');
                    $('.html_template .alert').clone().appendTo('.messages');
                    return false;
                }

                if (!row_selected) {
                    $('.html_template .alert .text').text('Please select a row on the table.');
                    $('.html_template .alert').clone().appendTo('.messages');
                    return false;
                }
            }).on('click', 'form#add_card [type="submit"]', function(e) {
                $('.messages').html('');
                if ($('form#add_card').find('[name="amount"]').val() % 2 != 0 || $('form#add_card').find('[name="amount"]') <= 0) {
                    $('.html_template .alert .text').text('Please ensure the amount is an even positive value.');
                    $('.html_template .alert').clone().appendTo('.messages');
                    return false;
                }
            })
        })
    </script>

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
