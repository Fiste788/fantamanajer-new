﻿<div class="titolo-pagina">
	<div class="column logo-tit">
		<img align="left" src="<?php echo IMGSURL.'other-big.png'; ?>" alt="Logo Squadre" />
	</div>
	<h2 class="column">Crea squadra</h2>
</div>
<div id="nuovoTrasferimento" class="main-content">
	<form class="column last" id="acquisti" name="edit-trasferimenti" action="<?php echo $this->linksObj->getLink('nuovoTrasferimento'); ?>" method="post">
		<fieldset>
			<input type="hidden" name="squadra" value="<?php echo $this->squadra; ?>" />
			<label for="player-old">Giocatore vecchio:</label><select id="player-old" name="lascia">
				<option></option>
				<?php for($j = 0 ; $j < count($this->ruo) ; $j++): ?>
			      <optgroup label="<?php echo $this->ruo[$j] ?>">
					<?php foreach($this->giocSquadra as $key=>$val): ?>
						<?php if($val['ruolo'] == substr($this->ruo[$j],0,1)): ?>
							<option value="<?php echo $val['idGioc']; ?>"<?php if(isset($this->giocLasciato) && $this->giocLasciato == $val['idGioc']) echo '  selected="selected"'; ?>><?php echo $val['cognome'] . " " . $val['nome']; ?></option>
						<?php endif; ?>
					<?php endforeach; ?>
				</optgroup>
				<?php endfor; ?>
			</select>
			<label for="player-new">Giocatore nuovo:</label><select id="player-new" name="acquista">
				<option></option>
					<?php for($j = 0 ; $j < count($this->ruo) ; $j++): ?>
			      <optgroup label="<?php echo $this->ruo[$j] ?>">
					<?php foreach($this->freePlayer as $key=>$val): ?>
						<?php if($val['ruolo'] == substr($this->ruo[$j],0,1)): ?>
							<option value="<?php echo $val['idGioc']; ?>"<?php if(isset($this->giocAcquisto) && $this->giocAcquisto == $val['idGioc']) echo '  selected="selected"'; ?>><?php echo $val['cognome'] . " " . $val['nome']; ?></option>
						<?php endif; ?>
					<?php endforeach; ?>
				</optgroup>
				<?php endfor; ?>
			</select>
		</fieldset>
		<fieldset>
			<input class="submit dark" type="submit" name="submit" value="OK" />
			<?php if(isset($this->isset)): ?>
				<input class="submit dark" type="submit" name="submit" value="Cancella acq." />
			<?php endif; ?>
		</fieldset>
	</form>
</div>
	<?php if($_SESSION['logged'] == TRUE): ?>
	<div id="squadradett" class="column last">
	<div class="box2-top-sx column last">
	<div class="box2-top-dx column last">
	<div class="box2-bottom-sx column last">
	<div class="box2-bottom-dx column last">
	<div class="box-content column last">
		<?php if(isset($this->messaggio) && $this->messaggio[0] == 0): ?>
		<div class="messaggio good column last">
			<img alt="OK" src="<?php echo IMGSURL.'ok-big.png'; ?>" />
			<span><?php echo $this->messaggio[1]; ?></span>
		</div>
		<?php elseif(isset($this->messaggio) && $this->messaggio[0] == 1): ?>
		<div class="messaggio bad column last">
			<img alt="!" src="<?php echo IMGSURL.'attention-bad-big.png'; ?>" />
			<span><?php echo $this->messaggio[1]; ?></span>
		</div>
		<?php elseif(isset($this->messaggio) && $this->messaggio[0] == 2): ?>
		<div class="messaggio neut column last">
			<img alt="!" src="<?php echo IMGSURL.'attention-big.png'; ?>" />
			<span><?php echo $this->messaggio[1]; ?></span>
		</div>
		<?php endif; ?>
		<?php if(isset($this->messaggio)): ?>
		<script type="text/javascript">
		$(document).ready(function() {$('.messaggio').show('pulsate',{times: 3 }); });
		$(".messaggio").click(function () {
			$("div.messaggio").fadeOut("slow");
		});
		</script>
		<?php endif; ?>
		<?php if($_SESSION['logged'] == TRUE): ?>
			<?php require (TPLDIR.'operazioni.tpl.php'); ?>
		<?php endif; ?>
		<form class="column last" name="trasferimenti" action="<?php echo $this->linksObj->getLink('nuovoTrasferimento'); ?>" method="post">
		<fieldset class="no-margin fieldset max-large">
			<h3 class="no-margin">Seleziona la squadra:</h3>
			<input type="hidden" name="p" value="<?php echo $_GET['p']; ?>" />
			<select name="squad" onchange="document.trasferimenti.submit();">
				<?php foreach($this->elencosquadre as $key=>$val): ?>
					<option <?php if($this->squadra == $val[0]) echo "selected=\"selected\"" ?> value="<?php echo $val[0]?>"><?php echo $val[1]?></option>
				<?php endforeach ?>
			</select>
		</fieldset>
	</form>
	</div>
	</div>
	</div>
	</div>
	</div>
</div>
<?php endif; ?>
