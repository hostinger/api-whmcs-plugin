{if $error}
  <div class="alert alert-danger text-center">
      {$error}
  </div>
{/if}

<div class="card">
  <div class="card-header">
    <i class="fa fa-fw fa-key"></i> Change panel password
  </div>
  <div class="card-body">
    <form class="form" action="{$currentpagelinkback}" method="post" autocomplete="off">
      <div class="form-group">
        <label for="password" class="col-sm-12 control-label">New panel password <strong class="text-danger">*</strong></label>
        <div class="col-sm-12">
          <input type="password" minlength="12" id="password" name="password" class="form-control" required />
        </div>
      </div>
      <div class="block text-center">
        <button class="btn btn-primary" type="submit"><i class="fa fa-fw fa-key"></i> Change</button>
      </div>
    </form>
  </div>
</div>