{if $error}
    <div class="alert alert-danger text-center">
        {$error}
    </div>
{/if}

<div class="card">
    <div class="card-header">
        <i class="fa fa-fw fa-sync-alt"></i> Reboot Server
    </div>
    <div class="card-body">
        <div class="alert alert-info text-center">
            <i class="fa fa-fw fa-question-circle"></i> Are you sure you want to reboot the server?
        </div>

        <form action="{$currentpagelinkback}" method="post" class="text-center">
            <input type="hidden" name="confirm" value="1" />
            <button class="btn btn-primary" type="submit"><i class="fa fa-fw fa-sync-alt"></i> Reboot</button>
        </form>
    </div>
</div>
