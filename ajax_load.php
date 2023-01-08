<?php
require_once(__DIR__ . '/partials/common/config.php');

$page = $_GET['page'];
// set page limit to 5 results per page. 20 by default
$items_per_page = $_GET['size'];

if ($_GET['type'] == 'past_captures') {
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
} else if ($_GET['type'] == 'scheduled_captures') {

    $db->orderBy("COALESCE(updated_at, created_at)", "desc");
    $db->orderBy("id", "desc");

    $result = $db->get(
        'customer c',
        [($page - 1) * $items_per_page, $items_per_page],
        [
            'c.id',
            'c.customer_name as name',
            'UNIX_TIMESTAMP(c.arrive_at) as arrive_at',
            'c.status',
            'c.amount_authorized',
            'c.amount_captured',
            'c.amount_to_capture',
            'SUBSTRING(c.card_number, -4, 4) as last4',
            'c.card_brand as brand',
            'c.card_country as country',
            'is_auto_auth',
            'UNIX_TIMESTAMP(auto_auth_starts_at) as auto_auth_starts_at',
            'UNIX_TIMESTAMP(auto_auth_pauses_at) as auto_auth_pauses_at',
            'UNIX_TIMESTAMP(last_retry_at) as last_retry_at',
            'retry_count',
            'UNIX_TIMESTAMP(c.updated_at) as updated_at',
            'UNIX_TIMESTAMP(c.created_at) as created_at'
        ]
    );

    $count = $db->getValue("customer", "count(*)");
}
/* echo '<pre>';
var_dump(
    [
        'data' => $result,
        'last_page' => intdiv($count, $items_per_page) + 1,
        'total' => $count
    ]
);
echo '</pre>';
die(); */

header('Content-Type: application/json; charset=utf-8');
echo json_encode(
    [
        'data' => $result,
        'last_page' => intdiv($count, $items_per_page) + 1,
        'total' => $count
    ]
);
exit();
