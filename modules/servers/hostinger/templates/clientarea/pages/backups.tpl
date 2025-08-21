{if $error}
  <div class="alert alert-danger text-center">
      {$error}
  </div>
{/if}

<div class="card">
  <div class="card-header">
    <i class="fa fa-fw fa-archive"></i> Backups
  </div>
  <div class="card-body">
      {if $backups}
        <div class="alert alert-warning text-center">
          <i class="fa fa-fw fa-info-circle"></i> All data on the virtual machine will be overwritten with the data from the backup.
        </div>
        <table class="table table-bordered table-striped mb-0">
          <thead>
          <tr>
            <th>Backup date</th>
            <th class="text-center" style="width: 25%">Actions</th>
          </tr>
          </thead>
            {foreach from=$backups item=backup key=id}
              <tr>
                <td>{$backup->format('Y-m-d H:i:s')}</td>
                <td>
                  <form action="{$currentpagelinkback}" method="post" class="text-center" onsubmit="return confirm('This action will overwrite all the data on the server. Are you sure?')">
                    <input type="hidden" name="backup_id" value="{$id}" />
                    <button class="btn btn-xs btn-primary" type="submit"><i class="fa fa-sync-alt"></i> Restore</button>
                  </form>
                </td>
              </tr>
            {/foreach}
        </table>
      {else}
        <div class="alert alert-info text-center mb-0">
          <i class="fa fa-fw fa-ban"></i> Server does not have any backups yet.
        </div>
      {/if}
  </div>
</div>