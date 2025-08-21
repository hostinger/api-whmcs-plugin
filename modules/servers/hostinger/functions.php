<?php
/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpUndefinedFunctionInspection */

declare(strict_types=1);

use GuzzleHttp\Exception\GuzzleException;
use Hostinger\Api\VPSVirtualMachineApi;
use Hostinger\ApiException;
use Hostinger\Configuration;
use Hostinger\Model\VPSV1VirtualMachineVirtualMachineResource;
use WHMCS\Database\Capsule;
use WHMCS\Service\Status;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/include/constants.php';
require_once __DIR__ . '/include/adminarea.php';
require_once __DIR__ . '/include/clientarea.php';
require_once __DIR__ . '/include/loaders.php';

/**
 * @throws Exception
 */
function getHostingerApiConfig(array $params): Configuration
{
    $server = Capsule::table('tblservers')->find($params['serverid']);
    if (!$server) {
        throw new Exception('Server credentials not found');
    }

    $decrypted = localAPI('DecryptPassword', ['password2' => $server->password]);

    return Configuration::getDefaultConfiguration()
        ->setAccessToken($decrypted['password'])
        ->setHost("https://$server->hostname");
}

function getHostingerVirtualMachineId(array $params): int
{
    return (int) $params['model']->subscriptionid;
}

function getFormattedHostingerPeriod(int $period, string $unit): string
{
    $unit = strtolower($unit);
    switch ($unit) {
        case 'month':
            return $period == 1 ? "1 Month" : "{$period} Months";
        case 'year':
            return $period == 1 ? "1 Year" : "{$period} Years";
        case 'week':
            return $period == 1 ? "1 Week" : "{$period} Weeks";
        case 'day':
            return $period == 1 ? "1 Day" : "{$period} Days";
        default:
            return "{$period} " . ucfirst($unit);
    }
}

function getHostingerDomainStatus(string $state): ?string
{
    return match ($state) {
        VPSV1VirtualMachineVirtualMachineResource::STATE_INITIAL => Status::PENDING,
        VPSV1VirtualMachineVirtualMachineResource::STATE_DESTROYED,
        VPSV1VirtualMachineVirtualMachineResource::STATE_SUSPENDED => Status::TERMINATED,
        VPSV1VirtualMachineVirtualMachineResource::STATE_RUNNING => Status::ACTIVE,
        default => null,
    };
}

function saveHostingerServerParameters(array $params, VPSV1VirtualMachineVirtualMachineResource $resource): void
{
    $data = [
        HOSTINGER_PARAMETER_SUBSCRIPTION_ID => $resource->getSubscriptionId(),
        HOSTINGER_PARAMETER_ACTIONS_LOCK => $resource->getActionsLock(),
        HOSTINGER_PARAMETER_LAST_UPDATE => time(),
        HOSTINGER_PARAMETER_DATA_CENTER_ID => $resource->getDataCenterId(),
    ];

    $template = $resource->getTemplate();
    if ($template) {
        $data[HOSTINGER_PARAMETER_TEMPLATE_ID] = sprintf('%d|%s', $template->getId(), $template->getName());
    }

    $params['model']->serviceProperties->save($data);
    $params['model']->refresh();
}

function getHostingerServerParameter(array $params, string $key): mixed
{
    return $params['model']->serviceProperties->get($key);
}

/**
 * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
 * @throws GuzzleException
 * @throws ApiException
 */
function getHostingerServerDetails(array $params, $force = false): array
{
    $actionsLock = getHostingerServerParameter($params, HOSTINGER_PARAMETER_ACTIONS_LOCK);
    $lastUpdate = (int) getHostingerServerParameter($params, HOSTINGER_PARAMETER_LAST_UPDATE);

    if ($force || $actionsLock === 'locked' || time() - $lastUpdate > 5 * 60) {
        $apiClient = new VPSVirtualMachineApi(getHostingerApiConfig($params));
        $virtualMachine = $apiClient->getVirtualMachineDetailsV1(getHostingerVirtualMachineId($params));

        $dedicatedIp = $virtualMachine->getIpv4()[0]?->getAddress();
        $assignedIps = [];
        foreach ($virtualMachine->getIpv4() as $ipv4) {
            if ($dedicatedIp === $ipv4->getAddress()) {
                continue;
            }

            $assignedIps[] = $ipv4->getAddress();
        }
        foreach ($virtualMachine->getIpv6() as $ipv6) {
            $assignedIps[] = $ipv6->getAddress();
        }

        $update = [
            'domain' => $virtualMachine->getHostname(),
            'disklimit' => $virtualMachine->getDisk(),
            'bwlimit' => $virtualMachine->getBandwidth(),
            'dedicatedip' => $dedicatedIp,
            'assignedips' => implode(',', $assignedIps),
            'ns1' => $virtualMachine->getNs1(),
            'ns2' => $virtualMachine->getNs2(),
            'username' => 'root',
            'lastupdate' => Capsule::raw('now()'),
        ];

        $status = getHostingerDomainStatus($virtualMachine->getState());
        if ($status !== null && $status !== $params['domainstatus']) {
            $update['domainstatus'] = $status;
        }

        Capsule::table('tblhosting')
            ->where('id', $params['serviceid'])
            ->update($update);

        saveHostingerServerParameters($params, $virtualMachine);

        $actionsLock = $virtualMachine->getActionsLock();
    }

    return [
        'server' => $params['model'],
        'actions_lock' => $actionsLock,
    ];
}

function markHostingerServerDetailsObsolete(array $params): void
{
    $params['model']->serviceProperties->save([
        HOSTINGER_PARAMETER_LAST_UPDATE => null,
    ]);
}

function makeRedirect(string $url): never
{
    header("Location: {$url}");
    exit();
}

function makeActionLink(array $params, string $action): string
{
    return "clientarea.php?action=productdetails&id={$params['serviceid']}&act={$action}";
}

function getConfigOption(array $params, string $key): mixed
{
    $config = hostinger_ConfigOptions($params);
    $keys = array_keys($config);
    $index = array_search($key, $keys);
    if ($index === false) {
        return null;
    }
    $configKey = sprintf('configoption%d', $index + 1);

    return $params[$configKey] ?? null;
}

function extractErrorFromThrowable(Throwable $e): string
{
    if ($e instanceof ApiException) {
        $response = $e->getResponse();
        if ($response) {
            $body = $response->getBody()->getContents();
            $json = json_decode($body, true);
            return $json['message'] ?? $e->getMessage();
        }
    }

    return $e->getMessage();
}


function debug(mixed $data): never
{
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}