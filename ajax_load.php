<?php
require_once(__DIR__ . '/partials/common/config.php');

$page = $_GET['page'];
// set page limit to 5 results per page. 20 by default
$items_per_page = $_GET['size'];

if ($_GET['type'] == 'past_captures') {
    $db->orderBy("lc.id", "desc")
        ->join("customer c", "c.id=lc.customer_id", "LEFT");

    if (isset($_GET['is_testing'])) {
        $db->where('lc.is_live', $_GET['is_testing'] == 0 ? 1 : 0);
    }

    if (isset($_GET['filter'])) {
        $db->where('c.customer_name', '%' . $_GET['filter'][0]['value'] . '%', 'like');
    }
    $result = $db->get(
        'log_capture lc',
        [($page - 1) * $items_per_page, $items_per_page],
        [
            'lc.is_live',
            'lc.shop',
            'c.wasaike_customer_id',
            'c.mandy_customer_id',
            'lc.stripe_charge_id',
            'lc.customer_name as name',
            "UNIX_TIMESTAMP(CONVERT_TZ(arrive_on, '+08:00', @@session.time_zone)) as arrive_on",
            "UNIX_TIMESTAMP(CONVERT_TZ(lc.created_at, '+08:00', @@session.time_zone)) as created_at",
            'lc.status',
            'lc.amount',
            'c.amount_to_capture',
            'SUBSTRING(lc.card_number, -4, 4) as last4',
            'lc.card_brand as brand',
            'lc.card_country as country'
        ]
    );

    if (isset($_GET['is_testing'])) {
        $db->where('is_live', $_GET['is_testing'] == 0 ? 1 : 0);
    }
    if (isset($_GET['filter'])) {
        $db->where('customer_name', '%' . $_GET['filter'][0]['value'] . '%', 'like');
    }
    $count = $db->getValue("log_capture", "count(*)");
} else if ($_GET['type'] == 'scheduled_captures') {

    $db->orderBy("COALESCE(updated_at, created_at)", "desc");
    $db->orderBy("id", "desc");

    if (isset($_GET['is_testing'])) {
        $db->where('is_live', $_GET['is_testing'] == 0 ? 1 : 0);
    }

    $result = $db->get(
        'customer c',
        [($page - 1) * $items_per_page, $items_per_page],
        [
            'c.id',
            'c.customer_name as name',
            "UNIX_TIMESTAMP(CONVERT_TZ(arrive_on, '+08:00', @@session.time_zone)) as arrive_on",
            'c.status',
            'c.amount_authorized',
            'c.amount_captured',
            'c.amount_to_capture',
            'SUBSTRING(c.card_number, -4, 4) as last4',
            'c.card_brand as brand',
            'c.card_country as country',
            'is_auto_auth',
            "UNIX_TIMESTAMP(CONVERT_TZ(auto_auth_starts_at, '+08:00', @@session.time_zone)) as auto_auth_starts_at",
            "UNIX_TIMESTAMP(CONVERT_TZ(auto_auth_pauses_at, '+08:00', @@session.time_zone)) as auto_auth_pauses_at",
            "UNIX_TIMESTAMP(CONVERT_TZ(last_retry_at, '+08:00', @@session.time_zone)) as last_retry_at",
            'retry_count',
            "UNIX_TIMESTAMP(CONVERT_TZ(c.updated_at, '+08:00', @@session.time_zone)) as updated_at",
            "UNIX_TIMESTAMP(CONVERT_TZ(c.created_at, '+08:00', @@session.time_zone)) as created_at"
        ]
    );

    if (isset($_GET['is_testing'])) {
        $db->where('is_live', $_GET['is_testing'] == 0 ? 1 : 0);
    }
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
