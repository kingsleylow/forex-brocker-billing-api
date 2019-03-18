<h1 class="page-header">Настройки</h1>

<form class="form-horizontal" role="form">
    <div class="form-group">
        <label class="col-sm-2 control-label">Ваш ID</label>
        <div class="col-sm-10">
            <p class="form-control-static"><?php echo $id; ?></p>
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">Название</label>
        <div class="col-sm-10">
            <p class="form-control-static"><?php echo $name; ?></p>
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">Транзитный МТ-счет</label>
        <div class="col-sm-10">
            <p class="form-control-static"><?php echo $trans_mt_login; ?></p>
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">Агентский МТ-счет</label>
        <div class="col-sm-10">
            <p class="form-control-static"><?php echo $agent_mt_login; ?></p>
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">МТ-диапазон</label>
        <div class="col-sm-10">
            <p class="form-control-static"><?php echo $mt_range; ?></p>
        </div>
    </div>
    <div class="form-group">
        <label for="inputPassword" class="col-sm-2 control-label">URL для передачи статусов</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" name="status_url" id="inputStatusURL" placeholder="URL для передачи статусов" value="<?php echo $status_url; ?>">
        </div>
    </div>
    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
            <button type="submit" class="btn btn-default">Сохранить</button>
        </div>
    </div>
</form>
