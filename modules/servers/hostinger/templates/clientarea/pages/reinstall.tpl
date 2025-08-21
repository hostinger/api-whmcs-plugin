{if $error}
  <div class="alert alert-danger text-center">
      {$error}
  </div>
{/if}

<div class="card">
  <div class="card-header">
    <i class="fa fa-fw fa-repeat"></i> Reinstall server
  </div>
  <div class="card-body">
      {if $last_result.completed}
        <p>Your server was reinstalled at <strong>{$last_result.completed|date_format:'%c'}</strong>:</p>
        <pre>{$last_result.results}</pre>
        <hr />
      {/if}

    <form class="form" action="{$currentpagelinkback}" method="post" autocomplete="off">
      <div class="form-group">
        <label for="template" class="col-sm-12 control-label">Select operating system <strong class="text-danger">*</strong></label>
        <div class="col-sm-12">
          <select id="template" name="template_id" class="form-control" required>
              {foreach from=$templates item=template key=id}
                <option value="{$id}">{$template}</option>
              {/foreach}
          </select>
        </div>
      </div>
      <div class="form-group">
        <label for="password" class="col-sm-12 control-label">Provide root password</label>
        <div class="col-sm-12">
          <input type="password" id="password" name="password" class="form-control" minlength="12" placeholder="Random password will be generated if left empty" />
        </div>
      </div>
      <div class="form-group">
        <div class="col-sm-12 text-center">
          <label for="confirm" class="control-label">
            <input type="checkbox" name="confirm" id="confirm" required />
            I understand, that reinstalling OS will <strong class="text-danger">ERASE ALL DATA STORED ON SERVER</strong>.
          </label>
        </div>
      </div>
      <div class="block text-center">
        <button class="btn btn-danger" type="submit"><i class="fa fa-fw fa-repeat"></i> Reinstall Server</button>
      </div>
    </form>
  </div>
</div>