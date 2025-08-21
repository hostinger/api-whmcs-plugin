<div class="card">
    <div class="card-body">
        <p class="text-center"><i class="fa fa-fw fa-2x fa-spin fa-sync-alt"></i></p>
        <p class="text-center"><span class="fa fa-fw fa-wrench mb-0"></span> Your server <strong>is busy...</strong>.</p>
        {if $action}
            <p class="text-center mt-3 mb-0">
                <span class="fa fa-fw fa-sign-in"></span> Action started: <strong>{$action.created_at|date_format:'%c'}</strong>
            </p>
            {assign var=duration value=$smarty.now-$action.created_at|date_format:"%s"}
            {assign var=h value=$duration/3600|floor}
            {assign var=m value=($duration%3600)/60|floor}
            {assign var=s value=$duration%60}

            {if $s|intval > 10}
                <p class="text-center mt-3 mb-0"><span class="fa fa-fw fa-hourglass-half"></span> Running time:
                    {if $h|intval}<strong>{$h|string_format:"%01d"} hours</strong>{/if}
                    {if $m|intval}<strong>{$m|string_format:"%01d"} minutes</strong>{/if}
                    <strong>{$s|string_format:"%01d"} seconds</strong>
                </p>
            {/if}
        {/if}
    </div>
</div>

<script>
    {literal}
    setInterval(function () {
        window.location.reload(true);
    }, 10000);
    {/literal}
</script>
