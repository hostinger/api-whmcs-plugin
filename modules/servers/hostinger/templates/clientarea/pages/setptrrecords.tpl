{if $error}
  <div class="alert alert-danger text-center">
      {$error}
  </div>
{/if}

<div class="card">
  <div class="card-header">
    <i class="fa fa-fw fa-search"></i> Set PTR records
  </div>
  <div class="card-body">
    <form class="form" action="{$currentpagelinkback}" method="post" autocomplete="off">
      <div class="form-group">
        <label for="ip" class="col-sm-12 control-label">IP Address <strong class="text-danger">*</strong></label>
        <div class="col-sm-12">
          <select name="ip" class="form-control" id="ip" required>
            {foreach from=$ips item=$ip key=$id}
              <option value="{$id}">{$ip}</option>
            {/foreach}
          </select>
        </div>
      </div>
      <div class="form-group">
        <label for="ptr" class="col-sm-12 control-label">PTR Record <strong class="text-danger">*</strong></label>
        <div class="col-sm-12">
          <input type="text" name="ptr" class="form-control" id="ptr" required placeholder="foo.bar.tld" />
        </div>
      </div>
      <div class="block text-center">
        <button class="btn btn-primary" type="submit"><i class="fa fa-fw fa-search"></i> Set PTR Records</button>
      </div>
    </form>
  </div>
</div>
