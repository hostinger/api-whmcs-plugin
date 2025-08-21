<?php
/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

use Hostinger\Api\BillingCatalogApi;
use Hostinger\Api\VPSActionsApi;
use Hostinger\Api\VPSBackupsApi;
use Hostinger\Api\VPSDataCentersApi;
use Hostinger\Api\VPSOSTemplatesApi;
use Hostinger\Api\VPSSnapshotsApi;
use Hostinger\Api\VPSVirtualMachineApi;

/**
 * @throws Throwable
 */
function hostinger_DataCenterLoader(array $params): array
{
    $apiClient = new VPSDataCentersApi(config: getHostingerApiConfig($params));
    $locations = $apiClient->getDataCenterListV1();
    $options = [];

    foreach ($locations as $dc) {
        $city    = $dc->getCity() ?: $dc->getName();
        $country = strtoupper((string) $dc->getLocation());
        $label   = $country ? "{$city} ({$country})" : $city;

        $options[$dc->getId()] = $label;
    }

    asort($options);

    return $options;
}

/**
 * @throws Throwable
 */
function hostinger_OSTemplateLoader(array $params, bool $withEmpty = true): array
{
    $apiClient = new VPSOSTemplatesApi(config: getHostingerApiConfig($params));
    $templates = $apiClient->getTemplatesV1();

    $options = [null => ''];

    foreach ($templates as $tpl) {
        $options[$tpl->getId()] = $tpl->getName();
    }

    if (!$withEmpty) {
        $options = array_filter($options);
    }

    asort($options);

    return $options;
}

/**
 * @throws Throwable
 */
function hostinger_VPSPlanLoader(array $params): array
{
    $apiClient = new BillingCatalogApi(config: getHostingerApiConfig($params));
    $response = $apiClient->getCatalogItemListV1(category: 'VPS');
    $options = [];

    foreach ($response as $item) {
        $planName = $item->getName();
        foreach ($item->getPrices() as $priceItem) {
            $termLabel = getFormattedHostingerPeriod($priceItem->getPeriod() ?? 1, $priceItem->getPeriodUnit() ?? '');
            $options[$priceItem->getId()] = "$planName - $termLabel";
        }
    }

    asort($options);

    return $options;
}

/**
 * @throws Throwable
 */
function hostinger_BackupsLoader(array $params): array
{
    $apiClient = new VPSBackupsApi(config: getHostingerApiConfig($params));
    $response = $apiClient->getBackupsV1(getHostingerVirtualMachineId($params));
    $backups = [];

    foreach ($response->getData() ?? [] as $backup) {
        $backups[$backup->getId()] = $backup->getCreatedAt();
    }

    return $backups;
}

/**
 * @throws Throwable
 */
function hostinger_SnapshotsLoader(array $params): ?int
{
    $apiClient = new VPSSnapshotsApi(config: getHostingerApiConfig($params));
    $response = $apiClient->getSnapshotV1(getHostingerVirtualMachineId($params));

    return $response->getId();
}

/**
 * @throws Throwable
 */
function hostinger_ActionsLoader(array $params): array
{
    $apiClient = new VPSActionsApi(config: getHostingerApiConfig($params));
    $response = $apiClient->getActionsV1(getHostingerVirtualMachineId($params));

    return $response->getData();
}

/**
 * @throws Throwable
 */
function hostinger_IPAddressLoader(array $params): array
{
    $apiClient = new VPSVirtualMachineApi(config: getHostingerApiConfig($params));
    $response = $apiClient->getVirtualMachineDetailsV1(getHostingerVirtualMachineId($params));

    $ips = [];
    foreach ($response->getIpv4() as $ip) {
        $ips[$ip->getId()] = $ip->getAddress();
    }

    foreach ($response->getIpv6() as $ip) {
        $ips[$ip->getId()] = $ip->getAddress();
    }

    return $ips;
}