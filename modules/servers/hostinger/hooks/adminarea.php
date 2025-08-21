<?php

declare(strict_types=1);

use WHMCS\Database\Capsule;

add_hook('ProductEdit', 1, function($vars) {
    $productId = $vars['pid'];

    // Check if product uses hostinger module
    $product = Capsule::table('tblproducts')
        ->where('id', $productId)
        ->where('servertype', 'hostinger')
        ->first();

    if (!$product) {
        return;
    }

    hostinger_upsertProductCustomFields($productId);
});

function hostinger_upsertProductCustomFields($productId)
{
    $customFields = [
        [
            'type' => 'product',
            'relid' => $productId,
            'fieldname' => 'actions_lock|Actions Lock',
            'fieldtype' => 'dropdown',
            'description' => 'Virtual machine action lock status',
            'fieldoptions' => 'locked,unlocked',
            'adminonly' => 'on',
        ],
        [
            'type' => 'product',
            'relid' => $productId,
            'fieldname' => 'last_update|Last update',
            'fieldtype' => 'text',
            'description' => 'Timestamp of last update of the server',
            'adminonly' => 'on',
        ],
        [
            'type' => 'product',
            'relid' => $productId,
            'fieldname' => 'subscription_id|Hostinger Subscription ID',
            'fieldtype' => 'text',
            'description' => 'Subscription ID of the server on Hostinger system',
            'adminonly' => 'on',
        ],
        [
            'type' => 'product',
            'relid' => $productId,
            'fieldname' => 'vps_template_id|Operating system',
            'fieldtype' => 'text',
            'description' => 'Select operating system of the server',
            'required' => 'on',
            'showorder' => 'on',
            'sortorder' => 10,
        ],
        [
            'type' => 'product',
            'relid' => $productId,
            'fieldname' => 'vps_data_center_id|Data Center',
            'fieldtype' => 'text',
            'description' => 'Select location for the server',
            'required' => 'on',
            'showorder' => 'on',
            'sortorder' => 20,
        ],
    ];

    foreach ($customFields as $field) {
        $code = explode('|', $field['fieldname'])[0];

        if (!Capsule::table('tblcustomfields')->where('fieldname', 'LIKE', "$code|%")->exists()) {
            Capsule::table('tblcustomfields')->insert($field);
        }
    }
}