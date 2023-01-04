<?php
require_once(__DIR__ . '/partials/common/config.php');

$page = $_GET['page'];

// set page limit to 5 results per page. 20 by default
$items_per_page = $_GET['size'];

$db->orderBy("lc.id", "desc")
    ->join("customer c", "c.id=lc.customer_id", "LEFT");

if (isset($_GET['filter'])) {
    $db->where('c.customer_name', '%' . $_GET['filter'][0]['value'] . '%', 'like');
}
$result = $db->get(
    'log_capture lc',
    [($page - 1) * $items_per_page, $items_per_page],
    [
        'lc.shop',
        'c.wasaike_customer_id',
        'c.mandy_customer_id',
        'lc.stripe_charge_id',
        'lc.customer_name as name',
        'UNIX_TIMESTAMP(c.arrive_at) as arrive_at',
        'UNIX_TIMESTAMP(lc.created_at) as created',
        'lc.status',
        'lc.amount',
        'c.amount_to_capture',
        'SUBSTRING(lc.card_number, -4, 4) as last4',
        'lc.card_brand as brand',
        'lc.card_country as country'
    ]
);

if (isset($_GET['filter'])) {
    $db->where('customer_name', '%' . $_GET['filter'][0]['value'] . '%', 'like');
}
$count = $db->getValue("log_capture", "count(*)");

header('Content-Type: application/json; charset=utf-8');
echo json_encode(
    [
        'data' => $result,
        'last_page' => intdiv($count, $items_per_page) + 1,
        'total' => $count
    ]
);
exit();
