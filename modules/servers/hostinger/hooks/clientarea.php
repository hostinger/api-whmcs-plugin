<?php
/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpUndefinedFunctionInspection */

declare(strict_types=1);

require_once __DIR__ . '/../functions.php';

use WHMCS\Database\Capsule;
use WHMCS\Service\Service;

add_hook('ClientAreaPrimarySidebar', 1, function ($sidebar) {

    $service = Menu::context("service");
    if ($service instanceof Service && $service->product->module !== "hostinger") {
        return null;
    }

    $overviewSidebar = $sidebar->getChild('Service Details Overview');
    if ($overviewSidebar) {
        $overviewSidebar->removeChild('Information');
        $overviewSidebar->addChild(
            'actionsMenuInformationItem', [
                'name' => 'Information',
                'label' => Lang::trans('Information'),
                'uri' => "clientarea.php?action=productdetails&id={$service->id}&modop=custom&act=Information",
                'order' => 1,
                'icon' => 'fa-fw fa-info'
            ]
        );
    }

    $actionsSidebar = $sidebar->getChild('Service Details Actions');
    if (!$actionsSidebar) {
        return null;
    }

    $actionsSidebar->addChild(
        'actionsMenuChangeRootPasswordItem', [
            'disabled' => $service->status != "Active",
            'name' => 'Change OS password',
            'label' => Lang::trans('Change OS password'),
            'uri' => "clientarea.php?action=productdetails&id={$service->id}&modop=custom&act=ChangeRootPassword",
            'order' => 10,
            'icon' => 'fa-fw fa-key'
        ]
    );

    $actionsSidebar->addChild(
        'actionsMenuChangePanelPasswordItem', [
            'disabled' => $service->status != "Active",
            'name' => 'Change panel password',
            'label' => Lang::trans('Change panel password'),
            'uri' => "clientarea.php?action=productdetails&id={$service->id}&modop=custom&act=ChangePanelPassword",
            'order' => 20,
            'icon' => 'fa-fw fa-key'
        ]
    );

    $actionsSidebar->addChild(
        'actionsMenuRebootItem', [
            'disabled' => $service->status != "Active",
            'name' => 'Reboot',
            'label' => Lang::trans('Reboot'),
            'uri' => "clientarea.php?action=productdetails&id={$service->id}&modop=custom&act=Reboot",
            'order' => 30,
            'icon' => 'fa-fw fa-sync-alt'
        ]
    );

    $actionsSidebar->addChild(
        'actionsMenuSetHostnameItem', [
            'disabled' => $service->status != "Active",
            'name' => 'Set hostname',
            'label' => Lang::trans('Set hostname'),
            'uri' => "clientarea.php?action=productdetails&id={$service->id}&modop=custom&act=SetHostname",
            'order' => 40,
            'icon' => 'fa-fw fa-edit'
        ]
    );

    $actionsSidebar->addChild(
        'actionsMenuSetPTRRecordsItem', [
            'disabled' => $service->status != "Active",
            'name' => 'Set PTR records',
            'label' => Lang::trans('Set PTR records'),
            'uri' => "clientarea.php?action=productdetails&id={$service->id}&modop=custom&act=SetPTRRecords",
            'order' => 50,
            'icon' => 'fa-fw fa-search'
        ]
    );

    $actionsSidebar->addChild(
        'actionsMenuSetNameserversItem', [
            'disabled' => $service->status != "Active",
            'name' => 'Set nameservers',
            'label' => Lang::trans('Set nameservers'),
            'uri' => "clientarea.php?action=productdetails&id={$service->id}&modop=custom&act=SetNameservers",
            'order' => 60,
            'icon' => 'fa-fw fa-tags'
        ]
    );

    $actionsSidebar->addChild(
        'actionsMenuSnapshotsItem', [
            'disabled' => $service->status != "Active",
            'name' => 'Snapshots',
            'label' => Lang::trans('Snapshots'),
            'uri' => "clientarea.php?action=productdetails&id={$service->id}&modop=custom&act=Snapshots",
            'order' => 70,
            'icon' => 'fa-fw fa-camera-retro'
        ]
    );

    $actionsSidebar->addChild(
        'actionsMenuBackupsItem', [
            'disabled' => $service->status != "Active",
            'name' => 'Backups',
            'label' => Lang::trans('Backups'),
            'uri' => "clientarea.php?action=productdetails&id={$service->id}&modop=custom&act=Backups",
            'order' => 80,
            'icon' => 'fa-fw fa-archive'
        ]
    );

    $actionsSidebar->addChild(
        'actionsMenuReinstallItem', [
            'disabled' => $service->status != "Active",
            'name' => 'Reinstall',
            'label' => Lang::trans('Reinstall'),
            'uri' => "clientarea.php?action=productdetails&id={$service->id}&modop=custom&act=Reinstall",
            'order' => 90,
            'icon' => 'fa-fw fa-repeat'
        ]
    );

    // set highlight
    if ($_GET['modop'] ?? '' === 'custom') {
        try {
            $actionsSidebar->getChild(sprintf('actionsMenu%sItem', $_GET['act'] ?? ''))?->setClass('active');
            $overviewSidebar->getChild(sprintf('actionsMenu%sItem', $_GET['act'] ?? ''))?->setClass('active');
        } catch (Exception $e) {}
    }
});

add_hook('ClientAreaFooterOutput', 1, function ($params) {
    $output = [];
    $output[] = hostinger_addClientAreaAjaxLoaders($params);

    return implode("\n", array_filter($output));
});

function hostinger_addClientAreaAjaxLoaders(array $params): ?string
{
    if ($params['filename'] != 'cart' || $_GET['a'] != 'confproduct') {
        return null;
    }

    $productId = $params['productinfo']['pid'] ?? $params['pid'] ?? 0;
    if (!$productId) {
        return null;
    }

    $token = $_SESSION['tkval'];
    $product = Capsule::table('tblproducts')
        ->where('id', $productId)
        ->where('servertype', 'hostinger')
        ->first();

    if (!$product) {
        return null;
    }

    $templateField = Capsule::table('tblcustomfields')
        ->where('fieldname', 'LIKE', "vps_template_id|%")
        ->where('type', 'product')
        ->where('relid', $product->id)
        ->first();

    $dataCenterField = Capsule::table('tblcustomfields')
        ->where('fieldname', 'LIKE', "vps_data_center_id|%")
        ->where('type', 'product')
        ->where('relid', $product->id)
        ->first();

    return <<<EOT
    <script>
        jQuery(document).ready(function($) {
            
        const selectedDataCenter = '$product->configoption2';
        const selectedOperatingSystem = '$product->configoption3';
        
        $('input[name="customfield[$dataCenterField->id]"]').replaceWith('<select name="customfield[$dataCenterField->id]" id="customfield$dataCenterField->id" class="form-control custom-select"></select>');
        $('input[name="customfield[$templateField->id]').replaceWith('<select name="customfield[$templateField->id]" id="customfield$templateField->id" class="form-control custom-select"></select>');
        
        function loadDataCenters() {
            const datacenterField = $('select[name="customfield[$dataCenterField->id]"]');
            datacenterField.empty();
            datacenterField.append('<option value="" disabled selected hidden>Loading data centers...</option>');
            
            $.ajax({
                url: 'modules/servers/hostinger/ajax.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'getDataCenters',
                    product_id: '{$productId}',
                    token: '{$token}'
                },
                success: function(response) {
                    datacenterField.empty()
                    datacenterField.append('<option value="" disabled selected hidden>-- Select Data Center --</option>');
                    
                    if (response.success && response.data) {
                        $.each(response.data, function(id, datacenter) {
                            const selected = (selectedDataCenter === id + '') ? 'selected' : '';
                            datacenterField.append('<option value="' + id + '" ' + selected + '>' + datacenter + '</option>');
                        });
                    } else {
                        datacenterField.empty()
                        datacenterField.append(`<option value="" disabled selected hidden>\${response.error}</option>`);
                    }
                    
                    if (selectedDataCenter) {
                        datacenterField.trigger('change');
                    }
                },
                error: function() {
                    datacenterField.empty()
                    datacenterField.append('<option value="" disabled selected hidden>Error loading data centers</option>');
                }
            });
        }
        
        // Function to load OS templates
        function loadTemplates() {
            const templateField = $('select[name="customfield[$templateField->id]"]');
            templateField.empty();
            templateField.append('<option value="" disabled selected hidden>Loading operating systems...</option>');
            
            $.ajax({
                url: 'modules/servers/hostinger/ajax.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'getTemplates',
                    product_id: '{$productId}',
                    token: '{$token}'
                },
                success: function(response) {
                    templateField.empty();
                    templateField.append('<option value="" disabled selected hidden>-- Select Operating System --</option>');
                    
                    if (response.success && response.data) {
                        $.each(response.data, function(id, template) {
                            const selected = (selectedOperatingSystem === id + '') ? 'selected' : '';
                            templateField.append('<option value="' + id + '" ' + selected + '>' + template + '</option>');
                        });
                    } else {
                        templateField.empty()
                        templateField.append(`<option value="" disabled selected hidden>\${response.error}</option>`);
                    }
                },
                error: function() {
                    templateField.empty()
                    templateField.append('<option value="" disabled selected hidden>Error loading operating systems</option>');
                }
            });
        }
        
        loadDataCenters();
        loadTemplates();
    });
    </script>
    EOT;
}