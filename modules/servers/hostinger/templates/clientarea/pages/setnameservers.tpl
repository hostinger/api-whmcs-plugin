{if $error}
  <div class="alert alert-danger text-center">
      {$error}
  </div>
{/if}

<div class="card">
  <div class="card-header">
    <i class="fa fa-fw fa-tags"></i> Change name servers
  </div>
  <div class="card-body">
    <div class="alert alert-warning text-center">
      <i class="fa fa-fw fa-info-circle"></i> Improper nameserver configuration can lead to the virtual machine being unable to resolve domain names which can result in a loss of connectivity.
    </div>

    <form class="form" action="{$currentpagelinkback}" method="post" autocomplete="off">
      <div class="form-group">
        <label for="ns1" class="col-sm-12 control-label">Name server 1 <strong class="text-danger">*</strong></label>
        <div class="col-sm-12">
          <input type="text" name="ns1" class="form-control" id="ns1" placeholder="IP Address" required value="{$ns1}" />
        </div>
      </div>
      <div class="form-group">
        <label for="ns2" class="col-sm-12 control-label">Name server 2</label>
        <div class="col-sm-12">
          <input type="text" name="ns2" class="form-control" id="ns2" placeholder="IP Address" value="{$ns2}" />
        </div>
      </div>
      <div class="block text-center">
        <button class="btn btn-primary" type="submit"><i class="fa fa-fw fa-tags"></i> Set name servers</button>
      </div>
    </form>
  </div>
</div>