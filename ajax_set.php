<?php
require_once(__DIR__ . '/partials/common/config.php');
if ($_GET['type'] == 'resume_auto_auth') {
    header('Content-Type: application/json; charset=utf-8');

    $db->where('id', $_GET['id']);
    $data = ['is_auto_auth' => $_GET['value'] == 'true' ? 1 : 0];
    if ($data['is_auto_auth'] == 0) {
        $data['auto_auth_pauses_at'] = date('Y-m-d H:i:s');
    }

    if ($db->update('customer', $data)) {
        echo json_encode(
            [
                'success' => isset($data['auto_auth_pauses_at']) ? strtotime($data['auto_auth_pauses_at']) : null,
            ]
        );
    } else {
        echo json_encode(
            [
                'error' => $db->getLastError(),
            ]
        );
    }
} else if ($_GET['type'] == 'update_cell') {
    header('Content-Type: application/json; charset=utf-8');
    $db->where('id', $_GET['id']);
    $get = $_GET;
    unset($get['id']);
    unset($get['type']);
    if ($db->update('customer', $get)) {
        echo json_encode(
            [
                'success' => true,
            ]
        );
    } else {
        echo json_encode(
            [
                'error' => 'DB Update Error',
            ]
        );
    }
}

exit();
