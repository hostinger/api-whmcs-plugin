{if $error}
  <div class="alert alert-danger text-center">
      {$error}
  </div>
{/if}

<div class="card">
  <div class="card-header">
    <i class="fa fa-fw fa-camera-retro"></i> Snapshots
  </div>
  <div class="card-body">
    <div class="alert alert-info text-center">
      <i class="fa fa-fw fa-info-circle"></i> Create, restore, or delete snapshots that capture the state of your virtual machines at a given point, allowing you to quickly recover or test changes without affecting current operations.
    </div>
      {if $snapshot}
        <div class="alert alert-warning text-center">
          <i class="fa fa-fw fa-info-circle"></i> All data on the virtual machine will be overwritten when restoring a snapshot.
        </div>
      {/if}

    <div class="row">
      <div class="col">
        <form action="{$currentpagelinkback}" method="post" class="text-center" {if $snapshot}onsubmit="return confirm('This action will overwrite existing snapshot. Are you sure?')"{/if}>
          <input type="hidden" name="snapshot" value="create" />
          <button class="btn btn-primary" type="submit"><i class="fa fa-fw fa-camera-retro"></i> Create</button>
        </form>
      </div>
        {if $snapshot}
          <div class="col">
            <form action="{$currentpagelinkback}" method="post" class="text-center" onsubmit="return confirm('This action will overwrite all the data on the server. Are you sure?')">
              <input type="hidden" name="snapshot" value="restore" />
              <button class="btn btn-warning" type="submit"><i class="fa fa-fw fa-sync-alt"></i> Restore</button>
            </form>
          </div>
          <div class="col">
            <form action="{$currentpagelinkback}" method="post" class="text-center" onsubmit="return confirm('This action will remove created snapshot. Are you sure?')">
              <input type="hidden" name="snapshot" value="delete" />
              <button class="btn btn-danger" type="submit"><i class="fa fa-fw fa-trash"></i> Delete</button>
            </form>
          </div>
        {/if}
    </div>
  </div>
</div>