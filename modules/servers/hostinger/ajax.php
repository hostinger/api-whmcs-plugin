<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/functions.php';

use WHMCS\Database\Capsule;

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verify the request - check CSRF token
$token = $_POST['token'] ?? '';
$sessionToken = $_SESSION['tkval'] ?? '';

if (!$token || !$sessionToken || $token !== $sessionToken) {
    die(json_encode(['success' => false, 'error' => 'Invalid token']));
}

$action = $_POST['action'] ?? '';
$productId = intval($_POST['product_id'] ?? 0);
$params = resolveServerFromProductId($productId);

function resolveServerFromProductId(int $productId): array
{
    $product = Capsule::table('tblproducts')
        ->where('id', $productId)
        ->first();

    if ($product && $product->servergroup) {
        // Get first active server from the server group
        $server = Capsule::table('tblservers')
            ->where('servergroup', $product->servergroup)
            ->where('active', '1')
            ->where('type', 'hostinger')
            ->first();
    }

    if (!isset($server) || !$server) {
        $server = Capsule::table('tblservers')
            ->where('type', 'hostinger')
            ->where('active', '1')
            ->first();
    }

    return [
        'serverid' => $server->id,
    ];
}

switch ($action) {
    case 'getDataCenters':
        handleGetDataCenters($params);
        break;

    case 'getTemplates':
        handleGetTemplates($params);
        break;

    default:
        die(json_encode(['success' => false, 'error' => 'Invalid action']));
}

function handleGetDataCenters(array $params): void
{
    try {
        $data = hostinger_DataCenterLoader($params);

        echo json_encode([
            'success' => true,
            'data' => $data
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function handleGetTemplates(array $params): void
{
    try {
        $data = hostinger_OSTemplateLoader($params);

        echo json_encode([
            'success' => true,
            'data' => $data
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}