<div class="titolo-pagina">
	<div class="column logo-tit">
		<img align="left" src="<?php echo IMGSURL.'premi-big.png'; ?>" alt="->" />
	</div>
	<h2 class="column">Premi</h2>
</div>
<div id="premi" class="main-content">
<p>Per poter partecipare ad ogni giornata ogni partecipante deve versare la quota di 1€. Siccome i partecipanti sono 8 avremo 1€ a gionata per 8 partecipanti = 8€ a giornata.<br />Le giornate del campionato sono 38 quindi facendo un pò ci calcoli avremo 8€ a giornata per 38 giornate = 304€.<br/>Da questi 304€ vengono detratti 10€ utilizzati per l'acquisto del dominio quindi il montepremi finale è di 294€ che viene suddiviso secondo la seguente tabella:</p>
<table id="rosa" cellpadding="0" cellspacing="0">
	<tbody>
		<tr>
			<th>&nbsp;</th>
			<th>Posizione</th>
			<th>Premio</th>
		</tr>
		<tr>
			<td class="tableimg"><img alt="oro" src="<?php echo IMGSURL."cup-oro.png"; ?>" /></td>
			<td>1° classificato</td>
			<td class="ruolo">130€</td>
		</tr>
		<tr>
			<td class="tableimg"><img alt="argento" src="<?php echo IMGSURL."cup-argento.png"; ?>" /></td>
			<td>2° classificato</td>
			<td>76€</td>
		</tr>
		<tr>
			<td class="tableimg"><img alt="bronzo" src="<?php echo IMGSURL."cup-bronzo.png"; ?>" /></td>
			<td>3° classificato</td>
			<td>50€</td>
		</tr>
		<tr>
			<td class="tableimg"><img alt="legno" src="<?php echo IMGSURL."cup-legno.png"; ?>" /></td>
			<td>4° classificato</td>
			<td>38€</td>
		</tr>
	</tbody>
</table>
</div>
<?php if($_SESSION['logged'] == TRUE): ?>
	<div id="squadradett" class="column last">
		<div class="box2-top-sx column last">
		<div class="box2-top-dx column last">
		<div class="box2-bottom-sx column last">
		<div class="box2-bottom-dx column last">
		<div class="box-content column last">
			<?php require (TPLDIR.'operazioni.tpl.php'); ?>
		</div>
		</div>
		</div>
		</div>
		</div>
	</div>
<?php else: ?>
	<div class="right">&nbsp;</div>
<?php endif; ?>
