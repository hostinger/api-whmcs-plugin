<?php
/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpUnused */
/** @noinspection PhpUndefinedFunctionInspection */

declare(strict_types=1);

/**
 * Client area output logic handling.
 *
 * This function is used to define module specific client area output. It should
 * return an array consisting of a template file and optional additional
 * template variables to make available to that template.
 *
 * The template file you return can be one of two types:
 *
 * * tabOverviewModuleOutputTemplate - The output of the template provided here
 *   will be displayed as part of the default product/service client area
 *   product overview page.
 *
 * * tabOverviewReplacementTemplate - Alternatively using this option allows you
 *   to entirely take control of the product/service overview page within the
 *   client area.
 *
 * Whichever option you choose, extra template variables are defined in the same
 * way. This demonstrates the use of the full replacement.
 *
 * Please Note: Using tabOverviewReplacementTemplate means you should display
 * the standard information such as pricing and billing details in your custom
 * template or they will not be visible to the end user.
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 */
function hostinger_ParseClientAreaRequest(array $params): array
{
    $action = !empty($_REQUEST['act']) ? $_REQUEST['act'] : null;

    if ($params['status'] !== 'Active') {
        return hostinger_ClientAreaInformation($params);
    }

    try {
        $details = getHostingerServerDetails($params);
    } catch (Throwable $e) {
        logModuleCall('hostinger', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());

        return hostinger_ClientAreaError($params);
    }

    if ($details['actions_lock'] === 'locked') {
        return hostinger_ClientAreaServerBusy($params, $details);
    }

    if ($action) {
        return call_user_func(sprintf('hostinger_ClientArea%s', $action), $params, $details);
    }

    return hostinger_ClientAreaInformation($params, $details);
}

function hostinger_ClientAreaError(array $params): array
{
    return [
        'tabOverviewReplacementTemplate' => 'templates/clientarea/error.tpl',
    ];
}

function hostinger_ClientAreaInformation(array $params, array $details = []): array
{
    return [
        'tabOverviewModuleOutputTemplate' => 'templates/clientarea/clientarea.tpl',
        'templateVariables' => [
            'details' => $details,
        ],
    ];
}

function hostinger_ClientAreaReboot(array $params, array $details = []): array
{
    $error = null;

    if (!empty($_POST['confirm'])) {
        $error = hostinger_Restart($params);
        if ($error === 'success') {
            markHostingerServerDetailsObsolete($params);
            makeRedirect(makeActionLink($params, 'Reboot'));
        }
    }

    return [
        'tabOverviewReplacementTemplate' => 'templates/clientarea/pages/reboot.tpl',
        'templateVariables' => [
            'error' => $error
        ]
    ];
}

function hostinger_ClientAreaServerBusy(array $params, array $details = []): array
{
    $action = null;
    try {
        $actions = hostinger_ActionsLoader($params);
        foreach ($actions as $a) {
            if ($a->getState() === 'started') {
                $action = [
                    'name' => $a->getName(),
                    'state' => $a->getState(),
                    'created_at' => $a->getCreatedAt(),
                    'updated_at' => $a->getUpdatedAt(),
                ];

                break;
            }
        }
    } catch (Throwable $e) {
        logModuleCall('hostinger', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
    }

    return [
        'tabOverviewReplacementTemplate' => 'templates/clientarea/server_busy.tpl',
        'templateVariables' => [
            'action' => $action,
        ]
    ];
}

function hostinger_ClientAreaSetHostname(array $params, array $details = []): array
{
    $error = null;

    if (!empty($_POST['hostname'])) {
        $error = hostinger_SetHostname($params, $_POST['hostname']);
        if ($error === 'success') {
            markHostingerServerDetailsObsolete($params);
            makeRedirect(makeActionLink($params, 'SetHostname'));
        }
    }

    return [
        'tabOverviewReplacementTemplate' => 'templates/clientarea/pages/sethostname.tpl',
        'templateVariables' => [
            'hostname' => $details['server']->domain,
            'error' => $error
        ]
    ];
}

function hostinger_ClientAreaSetNameservers(array $params, array $details = []): array
{
    $error = null;

    if (!empty($_POST['ns1'])) {
        $error = hostinger_SetNameservers($params, $_POST['ns1'], $_POST['ns2'] ?? null);
        if ($error === 'success') {
            markHostingerServerDetailsObsolete($params);
            makeRedirect(makeActionLink($params, 'SetNameservers'));
        }
    }

    return [
        'tabOverviewReplacementTemplate' => 'templates/clientarea/pages/setnameservers.tpl',
        'templateVariables' => [
            'error' => $error,
            'ns1' => $details['server']->ns1,
            'ns2' => $details['server']->ns2,
        ]
    ];
}

function hostinger_ClientAreaChangeRootPassword(array $params, array $details = []): array
{
    $error = null;

    if (!empty($_POST['password'])) {
        $error = hostinger_ChangeRootPassword($params, $_POST['password']);
        if ($error === 'success') {
            markHostingerServerDetailsObsolete($params);
            makeRedirect(makeActionLink($params, 'ChangeRootPassword'));
        }
    }

    return [
        'tabOverviewReplacementTemplate' => 'templates/clientarea/pages/changerootpassword.tpl',
        'templateVariables' => [
            'error' => $error,
        ]
    ];
}

function hostinger_ClientAreaChangePanelPassword(array $params, array $details = []): array
{
    $error = null;

    if (!empty($_POST['password'])) {
        $error = hostinger_ChangePanelPassword($params, $_POST['password']);
        if ($error === 'success') {
            markHostingerServerDetailsObsolete($params);
            makeRedirect(makeActionLink($params, 'ChangePanelPassword'));
        }
    }

    return [
        'tabOverviewReplacementTemplate' => 'templates/clientarea/pages/changepanelpassword.tpl',
        'templateVariables' => [
            'error' => $error,
        ]
    ];
}

function hostinger_ClientAreaSetPTRRecords(array $params, array $details = []): array
{
    $error = null;

    if (!empty($_POST['ip'])) {
        $error = hostinger_SetPTRRecords($params, (int) $_POST['ip'], $_POST['ptr']);
        if ($error === 'success') {
            markHostingerServerDetailsObsolete($params);
            makeRedirect(makeActionLink($params, 'SetPTRRecords'));
        }
    }

    try {
        $ips = hostinger_IPAddressLoader($params);
    } catch (Throwable $e) {
        $ips = [];
        $error = extractErrorFromThrowable($e);
    }

    return [
        'tabOverviewReplacementTemplate' => 'templates/clientarea/pages/setptrrecords.tpl',
        'templateVariables' => [
            'error' => $error,
            'ips' => $ips,
        ]
    ];
}

function hostinger_ClientAreaSnapshots(array $params, array $details = []): array
{
    $error = null;
    $action = $_POST['snapshot'] ?? null;

    switch ($action) {
        case 'create':
            $error = hostinger_SnapshotCreate($params);
            if ($error === 'success') {
                markHostingerServerDetailsObsolete($params);
                makeRedirect(makeActionLink($params, 'Snapshots'));
            }
            break;
        case 'delete':
            $error = hostinger_SnapshotDelete($params);
            if ($error === 'success') {
                markHostingerServerDetailsObsolete($params);
                makeRedirect(makeActionLink($params, 'Snapshots'));
            }
            break;
        case 'restore':
            $error = hostinger_SnapshotRestore($params);
            if ($error === 'success') {
                markHostingerServerDetailsObsolete($params);
                makeRedirect(makeActionLink($params, 'Snapshots'));
            }
            break;
    }

    try {
        $snapshot = hostinger_SnapshotsLoader($params);
    } catch (Throwable $e) {
        $snapshot = null;
        $error = extractErrorFromThrowable($e);
    }

    return [
        'tabOverviewReplacementTemplate' => 'templates/clientarea/pages/snapshots.tpl',
        'templateVariables' => [
            'error' => $error,
            'snapshot' => $snapshot,
        ]
    ];
}

function hostinger_ClientAreaBackups(array $params, array $details = []): array
{
    $error = null;

    if (!empty($_POST['backup_id'])) {
        $error = hostinger_BackupRestore($params, (int) $_POST['backup_id']);
        if ($error === 'success') {
            markHostingerServerDetailsObsolete($params);
            makeRedirect(makeActionLink($params, 'Backups'));
        }
    }

    try {
        $backups = hostinger_BackupsLoader($params);
    } catch (Throwable $e) {
        $backups = [];
        $error = extractErrorFromThrowable($e);
    }

    return [
        'tabOverviewReplacementTemplate' => 'templates/clientarea/pages/backups.tpl',
        'templateVariables' => [
            'error' => $error,
            'backups' => $backups,
        ]
    ];
}

function hostinger_ClientAreaReinstall(array $params, array $details = []): array
{
    $error = null;

    if (!empty($_POST['template_id'])) {
        $error = hostinger_Reinstall(
            params: $params,
            templateId: (int) $_POST['template_id'],
            password: $_POST['password'] ?: null,
        );

        if ($error === 'success') {
            markHostingerServerDetailsObsolete($params);
            makeRedirect(makeActionLink($params, 'Reinstall'));
        }
    }

    try {
        $templates = hostinger_OSTemplateLoader($params, withEmpty: false);
    } catch (Throwable $e) {
        $templates = [];
        $error = extractErrorFromThrowable($e);
    }

    return [
        'tabOverviewReplacementTemplate' => 'templates/clientarea/pages/reinstall.tpl',
        'templateVariables' => [
            'error' => $error,
            'templates' => $templates,
        ]
    ];
}
