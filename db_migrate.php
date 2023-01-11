<?php
require_once('vendor/autoload.php');
include('partials/common/config.php');

$data = file_get_contents('customers.json');

$array = json_decode($data, true);

$db->rawQuery('SET FOREIGN_KEY_CHECKS = 0;');
$db->rawQuery('truncate table stripe_booking.customer');
$db->rawQuery('truncate table stripe_booking.log_capture;;');
$db->rawQuery('SET FOREIGN_KEY_CHECKS = 1;');

foreach ($array as $set) {
    $customer_data = $set;
    $customer_data['created_at'] = date('Y-m-d H:i:s', $customer_data['created']);
    $customer_data['customer_name'] = $customer_data['name'] ?? null;
    $customer_data['card_number'] = $customer_data['last4'] ?? null;
    $customer_data['card_brand'] = $customer_data['brand'] ?? null;
    $customer_data['card_country'] = $customer_data['country'] ?? null;
    $customer_data['amount_captured'] = $customer_data['amount'] ?? null;
    $customer_data['status'] = 'Captured';

    unset($customer_data['created']);
    unset($customer_data['name']);
    unset($customer_data['last4']);
    unset($customer_data['brand']);
    unset($customer_data['country']);
    unset($customer_data['amount']);
    unset($customer_data['charger']);

    $result = $db->where('wasaike_customer_id', $customer_data['wasaike_customer_id'])->getOne('customer');
    if ($db->count == 0) {
        $db->insert('customer', $customer_data);
    } else {
        $db->where('id', $result['id'])->update('customer', ['amount_captured' => $db->inc($customer_data['amount_captured'])]);
    }

    $capture_data = $set;
    $capture_data['created_at'] = date('Y-m-d H:i:s', $capture_data['created']);
    $capture_data['customer_name'] = $capture_data['name'] ?? null;
    $capture_data['card_number'] = $capture_data['last4'] ?? null;
    $capture_data['card_brand'] = $capture_data['brand'] ?? null;
    $capture_data['card_country'] = $capture_data['country'] ?? null;
    $capture_data['status'] = 'Success';
    $capture_data['customer_id'] = $db->where('wasaike_customer_id', $capture_data['wasaike_customer_id'])->getOne("customer", "id")['id'] ?? null;
    $capture_data['shop'] = $capture_data['charger'] ?? null;

    unset($capture_data['created']);
    unset($capture_data['name']);
    unset($capture_data['last4']);
    unset($capture_data['brand']);
    unset($capture_data['country']);
    unset($capture_data['wasaike_customer_id']);
    unset($capture_data['mandy_customer_id']);
    unset($capture_data['charger']);

    $db->insert('log_capture', $capture_data);
}

echo '<pre>';
var_dump($array);
echo '</pre>';
