{if !$details}
    <div class="alert alert-warning">
        <i class="fa fa-fw fa-spin fa-sync-alt"></i> Your server is being provisioned. Please wait...
    </div>
    <script>
        {literal}
        setInterval(function () {
            window.location.reload(true);
        }, 10000);
        {/literal}
    </script>
{/if}