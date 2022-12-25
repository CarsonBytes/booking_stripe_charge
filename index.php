<?php
error_reporting(E_ALL | E_WARNING | E_NOTICE);
ini_set('display_errors', TRUE);
set_exception_handler('our_global_exception_handler');
session_start();

if (!isset($_SESSION['message'])) $_SESSION['message'] = array();

//var_dump($_POST);

if (isset($_SESSION['message'])) {

	foreach ($_SESSION['message'] as $message) {
		echo  $message;
		echo '<br>';
	}
	$_SESSION['message'] = array();
}

require_once('config.php');

/* echo '<pre>';
	var_dump($_POST);
	echo '</pre>';die();
	echo '<pre>';
	var_dump([
		'number' => $_POST['cc-number'],
		'exp_month' => trim(explode('/',$_POST['cc-exp'])[0]),
		'exp_year' => trim(explode('/',$_POST['cc-exp'])[1]),
		'cvc' => $_POST['cc-cvc']
	  ]);
	echo '</pre>'; */

if (isset($_POST['name'])) {

	$card_data = [
		'card' => [
			'number' => trim($_POST['cc-number']),
			'exp_month' => trim(explode('/', $_POST['cc-exp'])[0]),
			'exp_year' => trim(explode('/', $_POST['cc-exp'])[1]),
			'currency' => 'JPY'
		]
	];

	if (trim($_POST['cc-cvc']) != '000') $card_data['card']['cvc'] = trim($_POST['cc-cvc']);

	//require_once('init.php');
    require_once('vendor/autoload.php');

	//wasaike
	$api = isset($_POST['isTesting']) ? Wasaike_test : Wasaike_live;
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
			'is_live' => isset($_POST['isTesting']) ? 0 : 1,
			'charger' => 'W'
		);
	}

	//mandy
	$api = isset($_POST['isTesting']) ? Mandy_test : Mandy_live;
	$customer = createCustomer($api, $_POST['name'], $card_data);

	if ($customer instanceof Stripe\Customer) {
		$_SESSION['message'][] = 'This customer @ mandy account is created';
		$customer_data['mandy_customer_id'] = $customer->id;
	}

	pushChargeArray($customer_data);

	header('Location: index.php');
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
	$_SESSION['message'][] = 'Exception: ' . $exception->getMessage();
	header('Location: index.php');
}


if (isset($_POST['charge'])) {
	require_once('init.php');

	if ($_POST['shop'] == 'mandy') {
		//mandy
		$api = isset($_POST['isTesting']) ? Mandy_test : Mandy_live;
		$customer_id = $_POST['mandy_customer_id'];
	} else {
		//wasaike
		$api = isset($_POST['isTesting']) ? Wasaike_test : Wasaike_live;
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
		'is_live' => isset($_POST['isTesting']) ? 0 : 1,
		'charger' => $_POST['shop'] == 'mandy' ? 'M' : 'W'
	);
	pushChargeArray($customer_data);
	header('Location: index.php');
}

?>

<!DOCTYPE html>
<html>

<head>
	<!-- <link rel="stylesheet" type="text/css" href="/tabulator/dist/css/tabulator.min.css"> -->
	<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/tabulator-tables@4.9.3/dist/css/tabulator.min.css">

    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">

	<style type="text/css" media="screen">
		input.invalid {
			border: 2px solid red;
		}

		.validation.failed:after {
			color: red;
			content: 'Validation failed';
		}

		.validation.passed:after {
			color: green;
			content: 'Validation passed';
		}
	</style>

</head>

<body style="margin: 15px; position:relative;">

	<?php //<a target="_blank" href="http://stripe.wasaike.com/customer_table.php">Customer table</a> <br />
	?>

	<div style="/* float:right; */">
		<h1>Charge Existing Customer</h1>
		<form action="" method="post" novalidate autocomplete="on" id="charge">
			<label><input type="checkbox" name="isTesting" value="isTesting" <?php echo isset($_POST['isTesting']) ? 'checked' : '' ?> /> is Testing?</label> <br /><br />

			<label><input type="radio" name="shop" value="mandy" checked>Mandy</label> <br />
			<label><input type="radio" name="shop" value="wasaike">Wasaike</label><br /><br />

			<div id="example-table"></div>


			<input type="hidden" name="mandy_customer_id" value="" required>

			<input type="hidden" name="wasaike_customer_id" value="" required>

			<input type="hidden" name="customer_name" value="" required>

			<input type="hidden" name="last4" value="" required>

			<h2>Total Amount</h2>
			<input type="text" class="amount" name="amount" value="<?php echo isset($_POST['amount']) ? $_POST['amount'] : '' ?>" pattern="\d*" placeholder="Amount" required><br /><br />

			<label><input type="radio" name="charge_percent" value="100" checked>100%</label>
			<label><input type="radio" name="charge_percent" value="50">50%</label>

			<br /><br />

			<button type="submit" name="charge" value="1">Submit</button>
		</form>

	</div>

	<h1>Add new customer</h1>
	<form action="" method="post" novalidate autocomplete="on" id="add_card">
		<label><input type="checkbox" name="isTesting" value="isTesting" <?php echo isset($_POST['isTesting']) ? 'checked' : '' ?> /> is Testing?</label> <br /><br />

		<input type="text" class="name" name="name" placeholder="Name" value="<?php echo isset($_POST['name']) ? $_POST['name'] : '' ?>" required>

		<h2>Card number formatting</h2>

		<input type="text" class="cc-number" name="cc-number" value="<?php echo isset($_POST['cc-number']) ? $_POST['cc-number'] : '' ?>" pattern="\d*" x-autocompletetype="cc-number" placeholder="Card number" required>

		<br />
		Testing cards:<br />
		4242424242424242 Visa <br />
		4000056655665556 Visa (debit) <br />
		<br />

		<h2>Expiry formatting</h2>

		<input type="text" class="cc-exp" name="cc-exp" value="<?php echo isset($_POST['cc-exp']) ? $_POST['cc-exp'] : '' ?>" pattern="\d*" x-autocompletetype="cc-exp" placeholder="Expires MM/YY" required maxlength="9">

		<h2>CVC formatting</h2>
		<input type="text" class="cc-cvc" name="cc-cvc" value="<?php echo isset($_POST['cc-cvc']) ? $_POST['cc-cvc'] : '' ?>" pattern="\d*" x-autocompletetype="cc-csc" placeholder="Security code" autocomplete="off">

		<h2>Total Amount</h2>
		<input type="text" class="amount" name="amount" value="<?php echo isset($_POST['amount']) ? $_POST['amount'] : '' ?>" pattern="\d*" placeholder="Amount">
		<br />
		(will charge by 50% only! <br />Please be sure to enter total amount)

		<h2 class="validation"></h2>

		<button type="submit" name="add_customer" value="1">Submit</button>
	</form>


	<script type="text/javascript" src="payment.js"></script>
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

		document.querySelector('#add_card').onsubmit = function(e) {
			e.preventDefault();
			J.toggleClass(document.querySelectorAll('input'), 'invalid');
			J.removeClass(validation, 'passed failed');

			var cardType = Payment.fns.cardType(J.val(number));

			J.toggleClass(number, 'invalid', !Payment.fns.validateCardNumber(J.val(number)));
			J.toggleClass(exp, 'invalid', !Payment.fns.validateCardExpiry(Payment.cardExpiryVal(exp)));

			J.toggleClass(cvc, 'invalid', !Payment.fns.validateCardCVC(J.val(cvc), cardType));

			if (document.querySelectorAll('.invalid').length) {
				J.addClass(validation, 'failed');
			} else {
				J.addClass(validation, 'passed');
				document.getElementById('add_card').submit();
			}
		}
	</script>

	<!-- <script type="text/javascript" src="jquery-3.4.1.min.js"></script> -->
	<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/jquery@3.6.2/dist/jquery.min.js"></script>

    <!-- <script type="text/javascript" src="/tabulator/dist/js/tabulator.min.js"></script> -->
	<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/tabulator-tables@4.9.3/dist/js/tabulator.min.js"></script>

	<!--<script type="text/javascript" src="/tabulator/moment.js"></script> -->
	<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>

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
			rowSelectionChanged: function(data, rows) {
				if (data.length) {
					$('form#charge input[name=mandy_customer_id]').val(data[0].mandy_customer_id);
					$('form#charge input[name=wasaike_customer_id]').val(data[0].wasaike_customer_id);
					$('form#charge input[name=customer_name]').val(data[0].name);
					$('form#charge input[name=last4]').val(data[0].last4);
					$('form#charge input[name=amount]').val(data[0].amount);
				}
			},
		});
		var ajaxConfig = {
			method: "post", //set request type to Position
			headers: {
				"Content-type": 'application/json; charset=utf-8', //set specific content type
			},
		};

		table.setData("customers.json", {}, ajaxConfig); //make ajax request with advanced config options
	</script>

	<!-- <script>
	jQuery(function($){
		$('body').on('submit','#charge2',function(e){
			e.preventDefault();
			header('Location: https://stripe.wasaike.com/');
		})
	})
	</script> -->
</body>

</html>


<?php
