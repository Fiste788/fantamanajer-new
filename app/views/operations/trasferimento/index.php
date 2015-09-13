<form class="form-inline" action="<?php echo $this->router->generate('trasferimento_index'); ?>" method="get">
    <fieldset>
        <div class="form-group">
            <label for="id" class="no-margin">Seleziona la squadra:</label>
            <select class="form-control" name="id">
                <?php foreach ($this->elencoSquadre as $val): ?>
                    <option data-url="<?php echo $this->router->generate('trasferimento_index',array('squadra'=>$val->id)) ?>" <?php if ($this->filterId == $val->id) echo ' selected="selected"'; ?> value="<?php echo $val->id; ?>"><?php echo $val->nomeSquadra; ?></option>
                <?php endforeach ?>
            </select>
        </div>
    </fieldset>
</form>