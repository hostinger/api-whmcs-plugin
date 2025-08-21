<?php
/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpUnused */
/** @noinspection PhpUndefinedFunctionInspection */

declare(strict_types=1);

use WHMCS\Database\Capsule;

function hostinger_UpdateServerDetails(array $params): string
{
    getHostingerServerDetails($params, force: true);

    return 'success';
}