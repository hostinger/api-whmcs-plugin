<?php
/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

use Hostinger\Api\BillingSubscriptionsApi;
use Hostinger\Api\VPSBackupsApi;
use Hostinger\Api\VPSPTRRecordsApi;
use Hostinger\Api\VPSSnapshotsApi;
use Hostinger\Api\VPSVirtualMachineApi;
use Hostinger\Configuration;
use Hostinger\Model\BillingV1SubscriptionCancelRequest;
use Hostinger\Model\VPSV1VirtualMachineHostnameUpdateRequest;
use Hostinger\Model\VPSV1VirtualMachineNameserversUpdateRequest;
use Hostinger\Model\VPSV1VirtualMachinePanelPasswordUpdateRequest;
use Hostinger\Model\VPSV1VirtualMachinePTRStoreRequest;
use Hostinger\Model\VPSV1VirtualMachinePurchaseRequest;
use Hostinger\Model\VPSV1VirtualMachineRecreateRequest;
use Hostinger\Model\VPSV1VirtualMachineRootPasswordUpdateRequest;
use Hostinger\Model\VPSV1VirtualMachineSetupRequest;
use WHMCS\Database\Capsule;
use WHMCS\Module\Server\CustomAction;
use WHMCS\Module\Server\CustomActionCollection;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once 'functions.php';

function hostinger_MetaData(): array
{
    return [
        'DisplayName' => 'Hostinger',
        'APIVersion'  => '1.0',
        'RequiresServer' => true,
    ];
}

function hostinger_TestConnection(array $params): array
{
    $config = Configuration::getDefaultConfiguration()
        ->setAccessToken($params['serverpassword'])
        ->setHost('https://' . $params['serverhostname']);

    try {
        (new VPSVirtualMachineApi(config: $config))->getVirtualMachinesV1();

        $success = true;
        $errorMsg = '';
    } catch (Throwable $e) {
        $success = false;
        $errorMsg = $e->getMessage();
    }

    return [
        'success' => $success,
        'error' => $errorMsg,
    ];
}

function hostinger_ConfigOptions(array $params): array
{
    return [
        HOSTINGER_CONFIG_LABEL_PLAN => [
            'Type'       => 'dropdown',
            'Loader'     => 'hostinger_VPSPlanLoader',
            'SimpleMode' => true,
        ],
        HOSTINGER_CONFIG_LABEL_DATACENTER => [
            'Type'       => 'dropdown',
            'Loader'     => 'hostinger_DataCenterLoader',
            'SimpleMode' => true,
            'Description' => 'Optional default',
        ],
        HOSTINGER_CONFIG_LABEL_OS => [
            'Type'       => 'dropdown',
            'Loader'     => 'hostinger_OSTemplateLoader',
            'SimpleMode' => true,
            'Description' => 'Optional default',
        ],
    ];
}

/**
 * Templating
 */
function hostinger_ClientArea(array $params): array
{
    return hostinger_ParseClientAreaRequest($params);
}

function hostinger_AdminCustomButtonArray(): array
{
    return [
        'Restart' => 'Restart',
        'Update Details' => 'UpdateServerDetails',
    ];
}

/**
 * Built-in functions
 */
function hostinger_UsageUpdate(array $params): void
{
    $apiClient = new VPSVirtualMachineApi(config: getHostingerApiConfig($params));

    $servers = Capsule::table('tblhosting')
        ->where('server', $params['serverid'])
        ->get();

    foreach ($servers as $server) {
        $serverId = (int) $server->subscriptionid ?? null;
        if (!$serverId) {
            continue;
        }

        try {
            $response = $apiClient->getMetricsV1(
                virtualMachineId: $serverId,
                dateFrom: new DateTime('-1 day'),
                dateTo: new DateTime(),
            );

            $diskUsageArray = (array)$response->getDiskSpace()->getUsage();
            $incomingTrafficArray = (array)$response->getIncomingTraffic()->getUsage();
            $outgoingTrafficArray = (array)$response->getOutgoingTraffic()->getUsage();
            $latestDiskUsage = array_pop($diskUsageArray);
            $latestIncomingTraffic = array_pop($incomingTrafficArray);
            $latestOutgoingTraffic = array_pop($outgoingTrafficArray);

            Capsule::table('tblhosting')
                ->where('server', $params['serverid'])
                ->update([
                    'diskusage'  => $latestDiskUsage / 1024 / 1024 ?? $params['diskusage'],
                    'bwusage'    => ($latestIncomingTraffic + $latestOutgoingTraffic) / 1024 / 1024 ?? $params['bwusage'],
                    'lastupdate' => Capsule::raw('now()'),
                ]);
        } catch (Throwable $e) {
            logModuleCall('hostinger', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        }
    }
}

function hostinger_CreateAccount(array $params): string
{
    if ($params['model']->subscriptionid) {
        return 'Virtual Machine already exists';
    }

    try {
        $planPriceId  = $params['configoption1'];
        $datacenterId = (int) $params['customfields'][HOSTINGER_PARAMETER_DATA_CENTER_ID] ?: $params['configoption2'];
        $templateId   = (int) $params['customfields'][HOSTINGER_PARAMETER_TEMPLATE_ID] ?: $params['configoption3'];
        $password     = $params['password'];
        $hostname     = $params['domain'] ?: null;

        $apiClient = new VPSVirtualMachineApi(config: getHostingerApiConfig($params));
        $response = $apiClient->purchaseNewVirtualMachineV1(
            (new VPSV1VirtualMachinePurchaseRequest())
                ->setItemId($planPriceId)
                ->setSetup(
                    (new VPSV1VirtualMachineSetupRequest())
                        ->setTemplateId($templateId)
                        ->setHostname($hostname)
                        ->setDataCenterId($datacenterId)
                        ->setPassword($password)
                )
        );
        $params['model']->customfields[HOSTINGER_PARAMETER_SUBSCRIPTION_ID] = $response->getOrder()->getSubscriptionId();
        $params['model']->subscriptionid = $response->getVirtualMachine()->getId();
        $params['model']->save();

        saveHostingerServerParameters($params, $response->getVirtualMachine());
    } catch (Throwable $e) {
        logModuleCall('hostinger', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());

        return extractErrorFromThrowable($e);
    }

    return 'success';
}

function hostinger_SuspendAccount(array $params): string
{
    try {
        $apiClient = new VPSVirtualMachineApi(config: getHostingerApiConfig($params));
        $apiClient->stopVirtualMachineV1(getHostingerVirtualMachineId($params));
    } catch (Throwable $e) {
        logModuleCall('hostinger', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());

        return extractErrorFromThrowable($e);
    }

    return 'success';
}

function hostinger_UnsuspendAccount(array $params): string
{
    try {
        $apiClient = new VPSVirtualMachineApi(config: getHostingerApiConfig($params));
        $apiClient->startVirtualMachineV1(getHostingerVirtualMachineId($params));
    } catch (Throwable $e) {
        logModuleCall('hostinger', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());

        return extractErrorFromThrowable($e);
    }

    return 'success';
}

function hostinger_TerminateAccount(array $params): string
{
    $subscriptionId = getHostingerServerParameter($params, HOSTINGER_PARAMETER_SUBSCRIPTION_ID);
    if (!$subscriptionId) {
        return 'Subscription ID not found';
    }

    try {
        $apiClient = new BillingSubscriptionsApi(config: getHostingerApiConfig($params));
        $apiClient->cancelSubscriptionV1(
            subscriptionId: $subscriptionId,
            billingV1SubscriptionCancelRequest: (new BillingV1SubscriptionCancelRequest())
        );
    } catch (Throwable $e) {
        logModuleCall('hostinger', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());

        return extractErrorFromThrowable($e);
    }

    return 'success';
}

function hostinger_ChangeRootPassword(array $params, string $password): string
{
    try {
        $apiClient = new VPSVirtualMachineApi(config: getHostingerApiConfig($params));
        $apiClient->setRootPasswordV1(
            virtualMachineId: getHostingerVirtualMachineId($params),
            vPSV1VirtualMachineRootPasswordUpdateRequest: (new VPSV1VirtualMachineRootPasswordUpdateRequest())
                ->setPassword($password),
        );

        $result = localAPI('EncryptPassword', ['password2' => '--- ENCRYPTED ---']);

        $params['model']->password = $result['password'];
        $params['model']->save();
    } catch (Throwable $e) {
        logModuleCall('hostinger', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());

        return extractErrorFromThrowable($e);
    }

    return 'success';
}

function hostinger_ChangePanelPassword(array $params, string $password): string
{
    try {
        $apiClient = new VPSVirtualMachineApi(config: getHostingerApiConfig($params));
        $apiClient->setPanelPasswordV1(
            virtualMachineId: getHostingerVirtualMachineId($params),
            vPSV1VirtualMachinePanelPasswordUpdateRequest: (new VPSV1VirtualMachinePanelPasswordUpdateRequest())
                ->setPassword($password),
        );
    } catch (Throwable $e) {
        logModuleCall('hostinger', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());

        return extractErrorFromThrowable($e);
    }

    return 'success';
}

function hostinger_Restart(array $params): string
{
    try {
        $apiClient = new VPSVirtualMachineApi(config: getHostingerApiConfig($params));
        $apiClient->restartVirtualMachineV1(getHostingerVirtualMachineId($params));
    } catch (Throwable $e) {
        logModuleCall('hostinger', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());

        return extractErrorFromThrowable($e);
    }

    return 'success';
}

function hostinger_SetHostname(array $params, string $hostname): string
{
    try {
        $apiClient = new VPSVirtualMachineApi(config: getHostingerApiConfig($params));
        $apiClient->setHostnameV1(
            virtualMachineId: getHostingerVirtualMachineId($params),
            vPSV1VirtualMachineHostnameUpdateRequest: (new VPSV1VirtualMachineHostnameUpdateRequest())
                ->setHostname($hostname),
        );
    } catch (Throwable $e) {
        logModuleCall('hostinger', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());

        return extractErrorFromThrowable($e);
    }

    return 'success';
}

function hostinger_Reinstall(array $params, int $templateId, ?string $password = null, ?int $postInstallScript = null): string
{
    try {
        $apiClient = new VPSVirtualMachineApi(config: getHostingerApiConfig($params));
        $apiClient->recreateVirtualMachineV1(
            virtualMachineId: getHostingerVirtualMachineId($params),
            vPSV1VirtualMachineRecreateRequest: (new VPSV1VirtualMachineRecreateRequest())
                ->setTemplateId($templateId)
                ->setPassword($password)
                ->setPostInstallScriptId($postInstallScript),
        );
    } catch (Throwable $e) {
        logModuleCall('hostinger', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());

        return extractErrorFromThrowable($e);
    }

    return 'success';
}

function hostinger_SetNameservers(array $params, string $ns1, ?string $ns2 = null): string
{
    try {
        $apiClient = new VPSVirtualMachineApi(config: getHostingerApiConfig($params));
        $apiClient->setNameserversV1(
            virtualMachineId: getHostingerVirtualMachineId($params),
            vPSV1VirtualMachineNameserversUpdateRequest: (new VPSV1VirtualMachineNameserversUpdateRequest())
                ->setNs1($ns1)
                ->setNs2($ns2),
        );
    } catch (Throwable $e) {
        logModuleCall('hostinger', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());

        return extractErrorFromThrowable($e);
    }

    return 'success';
}

function hostinger_BackupRestore(array $params, int $backupId): string
{
    try {
        $apiClient = new VPSBackupsApi(config: getHostingerApiConfig($params));
        $apiClient->restoreBackupV1(
            getHostingerVirtualMachineId($params),
            $backupId,
        );
    } catch (Throwable $e) {
        logModuleCall('hostinger', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());

        return extractErrorFromThrowable($e);
    }

    return 'success';
}

function hostinger_SnapshotCreate(array $params): string
{
    try {
        $apiClient = new VPSSnapshotsApi(config: getHostingerApiConfig($params));
        $apiClient->createSnapshotV1(getHostingerVirtualMachineId($params));
    } catch (Throwable $e) {
        logModuleCall('hostinger', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());

        return extractErrorFromThrowable($e);
    }

    return 'success';
}

function hostinger_SnapshotRestore(array $params): string
{
    try {
        $apiClient = new VPSSnapshotsApi(config: getHostingerApiConfig($params));
        $apiClient->restoreSnapshotV1(getHostingerVirtualMachineId($params));
    } catch (Throwable $e) {
        logModuleCall('hostinger', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());

        return extractErrorFromThrowable($e);
    }

    return 'success';
}

function hostinger_SnapshotDelete(array $params): string
{
    try {
        $apiClient = new VPSSnapshotsApi(config: getHostingerApiConfig($params));
        $apiClient->deleteSnapshotV1(getHostingerVirtualMachineId($params));
    } catch (Throwable $e) {
        logModuleCall('hostinger', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());

        return extractErrorFromThrowable($e);
    }

    return 'success';
}

function hostinger_SetPTRRecords(array $params, int $id, string $domain): string
{
    try {
        $apiClient = new VPSPTRRecordsApi(config: getHostingerApiConfig($params));
        $apiClient->createPTRRecordV1(
            virtualMachineId: getHostingerVirtualMachineId($params),
            ipAddressId: $id,
            vPSV1VirtualMachinePTRStoreRequest: (new VPSV1VirtualMachinePTRStoreRequest())
                ->setDomain($domain),
        );
    } catch (Throwable $e) {
        logModuleCall('hostinger', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());

        return extractErrorFromThrowable($e);
    }

    return 'success';
}

function hostinger_AdminLink(array $params): string
{
    return '<a href="https://hpanel.hostinger.com/vps" target="_blank">hPanel</a>';
}