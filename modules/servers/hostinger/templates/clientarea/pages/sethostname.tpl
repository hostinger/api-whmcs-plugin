{if $error}
  <div class="alert alert-danger text-center">
      {$error}
  </div>
{/if}

<div class="card">
  <div class="card-header">
    <i class="fa fa-fw fa-edit"></i> Change Hostname
  </div>
  <div class="card-body">
    <form class="form" action="{$currentpagelinkback}" method="post" autocomplete="off">
      <div class="form-group">
        <label for="hostname" class="col-sm-12 control-label">Hostname <strong class="text-danger">*</strong></label>
        <div class="col-sm-12">
          <input type="text" id="hostname" name="hostname" class="form-control" placeholder="foo.bar.tld" value="{$hostname}" required />
        </div>
      </div>
      <div class="block text-center">
        <button class="btn btn-primary" type="submit"><i class="fa fa-fw fa-edit"></i> Set hostname</button>
      </div>
    </form>
  </div>
