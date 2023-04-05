<?php
	require_once("../lib/conf.php");
	require_once("../lib/classes.php");
	$sql = new Mysql();

	if(isset($_POST['ajax'])) {
		$rtn = [];

		if($_POST['ajax']=="persistir") {

			$evolucao=$paciente=$resposta='';
			if(isset($_POST['id_evolucao']) and !empty($_POST['id_evolucao'])) {
				$sql->consult($_p."pacientes_evolucoes","*","where md5(id) = '".addslashes($_GET['id_evolucao'])."' and lixo=0");
				if($sql->rows) {
					$evolucao=mysqli_fetch_object($sql->mysqry);


					$sql->consult($_p."pacientes","*","where id=$evolucao->id_paciente and lixo=0");
					if($sql->rows) $paciente=mysqli_fetch_object($sql->mysqry);

					if(isset($_POST['id_resposta']) and is_numeric($_POST['id_resposta'])) {
						$sql->consult($_p."pacientes_evolucoes_anamnese","*","where id_evolucao=$evolucao->id and id=".$_POST['id_resposta']);
						if($sql->rows) $resposta=mysqli_fetch_object($sql->mysqry);
					}

				}
			}

			$val = isset($_POST['val']) ? $_POST['val'] : '';
			$tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';

			$erro='';
			if(empty($evolucao)) $erro='Evolução não encontrada!';
			else if(empty($paciente)) $erro='Paciente não encontrado!';
			else if(empty($resposta)) $erro='Pergunta não encontrada!';
			else if(empty($tipo)) $erro='Tipo de resposta inválida!';

			if(empty($erro)) {

				if($tipo=="texto") {
					$sql->update($_p."pacientes_evolucoes_anamnese","resposta_texto='".addslashes(utf8_decode($val))."'","where id=$resposta->id");
				} else {
					$sql->update($_p."pacientes_evolucoes_anamnese","resposta='".addslashes(utf8_decode($val))."'","where id=$resposta->id");
				}

				$rtn=array('success'=>true);


			} else {
				$rtn=array('success'=>false,'error'=>$erro);
			}

		} else {
			$rtn=array('success'=>false,'error'=>'Nenhum método definido!');
		}

		header("Content-type: application/json");
		echo json_encode($rtn);
		die();
	}

	# dados da clinica
		$clinica = $logo = '';
		$sql->consult($_p."clinica","*","");
		$clinica=mysqli_fetch_object($sql->mysqry);
		if(!empty($clinica->cn_logo)) $logo=$_cloudinaryURL.'c_thumb,w_600/'.$clinica->cn_logo;
		$title=utf8_encode($clinica->clinica_nome)." | Info Dental";
		$endereco = utf8_encode($clinica->endereco);

	# dados evolucao
	$evolucao=$paciente='';
	if(isset($_GET['id_evolucao']) and !empty($_GET['id_evolucao'])) {
		$sql->consult($_p."pacientes_evolucoes","*","where md5(id) = '".addslashes($_GET['id_evolucao'])."' and lixo=0");
		if($sql->rows) {
			$evolucao=mysqli_fetch_object($sql->mysqry);
			
			if($evolucao->id_tipo==1) {
				$title.=" | Anamnese";
			} 

			$sql->consult($_p."colaboradores","id,nome","where id=$evolucao->id_usuario");
			if($sql->rows) {
				$solicitante=mysqli_fetch_object($sql->mysqry);
			}

			$sql->consult($_p."pacientes","*","where id=$evolucao->id_paciente");
			if($sql->rows) {
				$paciente=mysqli_fetch_object($sql->mysqry);
				if($paciente->data_nascimento !="0000-00-00"){
					$idade=idade($paciente->data_nascimento);	
				}else{
					$idade = "";
				}
			}

			$_anamnesePerguntas=array();
			$sql->consult($_p."pacientes_evolucoes_anamnese","*","where id_evolucao=$evolucao->id and lixo=0");
			if($sql->rows) {
				while($x=mysqli_fetch_object($sql->mysqry)) {
					$_anamnesePerguntas[]=$x;
				}
			}
		}
	}

	require_once("../includes/assinaturas/assinatura-head.php");
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml"
      xmlns:og="http://ogp.me/ns#"
      xmlns:fb="http://www.facebook.com/2008/fbml">

	<head>
		<meta charset="utf-8">
		<title><?php echo $title;?></title>
		<link rel="stylesheet" type="text/css" href="../css/evolucoes.css" />
		<script defer src="https://code.iconify.design/1/1.0.3/iconify.min.js"></script>
		<script src="../js/jquery.js"></script>
	</head>
	
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	<script defer type="text/javascript" src="../js/jquery.slick.js"></script>
	<script defer type="text/javascript" src="../js/jquery.datetimepicker.js"></script>
	<script defer type="text/javascript" src="../js/jquery.chosen.js"></script>
	<script defer type="text/javascript" src="../js/jquery.fancybox.js"></script>
	<script defer type="text/javascript" src="../js/jquery.inputmask.js"></script>
	<script defer type="text/javascript" src="../js/jquery.tablesorter.js"></script>
	<script defer type="text/javascript" src="../js/jquery.chart.js"></script>
	<script defer type="text/javascript" src="../js/jquery.chart-utils.js"></script>
	<script type="text/javascript" src="../js/jquery.sweetalert.js"></script>
	<script type="text/javascript" src="../js/jquery.validacao.js"></script>
	<script type="text/javascript" src="../js/jquery.funcoes.js"></script>
	<script defer src="https://code.iconify.design/1/1.0.3/iconify.min.js"></script>

	<body>

		<div class="print-header" style="padding-top: 20px;">
			<?php
			if(!empty($logo)) {
			?>
			<img src="<?php echo $logo;?>" class="print-header__logo" style="width: auto;height: 30px;" />
			<?php
			} else {
			?>
			<img src="../img/logo-info.svg"  class="print-header__logo" style="width: auto;height: 25px;" />
			<?php
			}
			?>
		</div>

		<script type="text/javascript">
			var id_evolucao = '<?php echo md5($evolucao->id);?>'
			$(function(){
				$('.js-resposta').change(function(){
					let id_reposta = $(this).attr('data-id_resposta');
					let tipo = $(this).attr('data-tipo');
					let val = $(this).val();

					let data = `ajax=persistir&id_resposta=${id_reposta}&tipo=${tipo}&val=${val}&id_evolucao=${id_evolucao}`

					$.ajax({
						type:"POST",
						data:data,
						success:function(rtn) {

						}
					})
				});
			})
		</script>

		<table class="print-table">
			<thead><tr><td><div class="print-table-header">&nbsp;</div></td></tr></thead>
			<tbody>
				<tr>
					<td>
						<section class="print-content">

							<header class="titulo1">
								<h1>Ficha do Paciente</h1>
								<p><?php echo date('d/m/Y',strtotime($evolucao->data));?></p>
							</header>

							<div class="ficha">
								<table border="0">
									<tr>
										<td colspan="3"><strong><?php echo utf8_encode($paciente->nome);?></strong></td>
									</tr>
									<tr>
										<td><?php echo $idade>1?"$idade anos":"$idade";?></td>
										<td><?php echo $paciente->sexo=="M"?"Masculino":$paciente->sexo=="F"?"Feminino":'';?></td>
										<td style="text-align:right;"><span class="iconify" data-icon="bxs:phone" data-inline="true"></span> <?php echo maskTelefone($paciente->telefone1);?></td>
									</tr>
								</table>
							</div>

							<header class="titulo2">
								<span>
									<h1>Formulário da Anamnese</h1>
									<h2><?php echo utf8_encode($solicitante->nome);?></h2>
								</span>
							</header>

							<form method="post">

								<div class="box">
									<table>
										<?php
										foreach($_anamnesePerguntas as $p) {
											$pergunta=json_decode($p->json_pergunta);
										?>
										<tr>
											<td>
												<p><strong><?php echo utf8_encode($p->pergunta);?></strong></p>
												<p>
													<dl>
														<dd>
													<?php  
													if($pergunta->tipo=="simnao") { 
														?>
														<label>
															<input type="radio" name="resposta_<?php echo $p->id;?>" value="SIM" class="js-resposta" data-tipo="simnao_texto" data-id_resposta="<?php echo $p->id;?>"<?php echo $p->resposta=="SIM"?" checked":"";?> /> Sim
														</label>
														<label>
															<input type="radio" name="resposta_<?php echo $p->id;?>" value="NAO" class="js-resposta" data-tipo="simnao_texto" data-id_resposta="<?php echo $p->id;?>"<?php echo $p->resposta=="NAO"?" checked":"";?> /> Não
														</label>

														<?php

													}
													else if($pergunta->tipo=="simnaotexto") {
														?>
															<div>
																<label><input type="radio" name="resposta_<?php echo $p->id;?>" value="SIM" class="js-resposta" data-tipo="simnao" data-id_resposta="<?php echo $p->id;?>"<?php echo $p->resposta=="SIM"?" checked":"";?> /> Sim</label>
																<label><input type="radio" name="resposta_<?php echo $p->id;?>" value="NAO" class="js-resposta" data-tipo="simnao" data-id_resposta="<?php echo $p->id;?>"<?php echo $p->resposta=="NAO"?" checked":"";?> /> Não</label>
															</div>
															<div>
																<textarea name="resposta_<?php echo $p->id;?>" class="js-resposta" data-tipo="texto" data-id_resposta="<?php echo $p->id;?>"><?php echo utf8_encode($p->resposta_texto);?></textarea>
															</div>	
														<?php
													} else if($pergunta->tipo=="nota") {
														for($i=1;$i<=10;$i++) {
														?>
														<label>
															<input type="radio" name="resposta_<?php echo $p->id;?>" value="<?php echo $i;?>" class="js-resposta" data-tipo="nota" data-id_resposta="<?php echo $p->id;?>"<?php echo $p->resposta==$i?" checked":"";?> /> <?php echo $i;?>
														</label>
														<?php
														}
													} else {
														?>
														<textarea name="resposta_<?php echo $p->id;?>" class="js-resposta" data-tipo="texto" data-id_resposta="<?php echo $p->id;?>"><?php echo utf8_encode($p->resposta_texto);?></textarea>
														<?php
													}
													?>
														</dd>
													</dl>	
												</p>
											</td>
										</tr>
										<?php
										}
										?>
										
									</table>
								</div>
									<?php 
										require_once("../includes/assinaturas/assinatura-canvas.php");
									?>
								</form>
						</section>
					</td>
				</tr>
			</tbody>
		</table>

		<div class="print-footer">
			<p><span class="iconify" data-icon="bx:bxs-phone" data-inline="true"></span><span><?php echo maskTelefone($clinica->telefone);?></span><span class="iconify" data-icon="ri:whatsapp-fill" data-inline="true"></span><span><?php echo maskTelefone($clinica->whatsapp);?></span></p>
			<p><?php echo $endereco;?></p>
			<p>
				<span><i class="iconify" data-icon="ph-globe-simple"></i> <a href="https://<?php echo $clinica->site;?>"><?php echo $clinica->site;?></a></span>
				<span><i class="iconify" data-icon="ph-instagram-logo"></i> <a href="https://instagram.com/<?php echo str_replace("@","",$clinica->instagram);?>"><?php echo $clinica->instagram;?></a></span>
			</p>
		</div>

	</body>
</html>