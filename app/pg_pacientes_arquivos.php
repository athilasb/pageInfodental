<?php
	if(isset($_POST['ajax'])) {
		require_once("lib/conf.php");	
		require_once("usuarios/checa.php");


		$paciente = '';
		if(isset($_POST['id_paciente']) and is_numeric($_POST['id_paciente'])) {
			$sql->consult($_p."pacientes","id,nome","where id=".$_POST['id_paciente']);
			if($sql->rows) {
				$paciente=mysqli_fetch_object($sql->mysqry);
			}
		}

		$evolucao = '';
		if(is_object($paciente) and isset($_POST['id_evolucao']) and is_numeric($_POST['id_evolucao'])) {
			$sql->consult($_p."pacientes_evolucoes","id,data","where id=".$_POST['id_evolucao']." and id_paciente=$paciente->id and lixo=0");
			if($sql->rows) {
				$evolucao=mysqli_fetch_object($sql->mysqry);
			}
		}


		$rtn = array();

		if($_POST['ajax']=="evolucaoErrataPersisitr") {

			$texto = (isset($_POST['texto']) and !empty($_POST['texto']))?$_POST['texto']:'';

			$erro='';
			if(empty($paciente)) $erro='Paciente não encontrado!';
			else if(empty($evolucao)) $erro='Evolução não encontrada!';
			else if(empty($texto)) $erro='Preencha o campo da errata!';

			if(empty($erro)) {

				$vsql="data=now(),
						id_usuario=$usr->id,
						id_evolucao=$evolucao->id,
						id_paciente=$paciente->id,
						texto='".addslashes(utf8_decode($texto))."'";

				$sql->add($_p."pacientes_evolucoes_erratas",$vsql);

				$rtn=array('success'=>true);

			} else {
				$rtn=array('success'=>false,'error'=>$erro);
			}

		}
		else if($_POST['ajax']=="evolucaoErrataListar") {

			$_profissionais=array();
			$sql->consult($_p."colaboradores","id,nome","");
			while($x=mysqli_fetch_object($sql->mysqry)) {
				$_profissionais[$x->id]=$x;
			}

			$erratas=array();
			if(is_object($paciente) and is_object($evolucao)) {
				$sql->consult($_p."pacientes_evolucoes_erratas","*","where id_evolucao=$evolucao->id and id_paciente=$paciente->id and lixo=0 order by data desc");
				if($sql->rows){
					while($x=mysqli_fetch_object($sql->mysqry)) {
						$erratas[]=array('id'=>$x->id,
										'id_evolucao'=>$x->id_evolucao,
										'data'=>date('d/m/Y H:i',strtotime($x->data)),
										'profissional'=>isset($_profissionais[$x->id_usuario])?utf8_encode($_profissionais[$x->id_usuario]->nome):'',
										'texto'=>utf8_encode($x->texto));
					}
				}
			}

			$rtn=array('success'=>true,'erratas'=>$erratas,'id_evolucao'=>is_object($evolucao)?$evolucao->id:0);
		}

		else if($_POST['ajax']=="evolucaoProcedimentosHistorico") {


			$procedimentoAEvoluir = $procedimento = '';
			if(isset($_POST['id_procedimento_aevoluir']) and is_numeric($_POST['id_procedimento_aevoluir'])) {
				$sql->consult($_p."pacientes_tratamentos_procedimentos_evolucao","*","where id=".$_POST['id_procedimento_aevoluir']);
				if($sql->rows) {
					$procedimentoEvoluir=mysqli_fetch_object($sql->mysqry);

					$sql->consult($_p."parametros_procedimentos","*","where id=$procedimentoEvoluir->id_procedimento");
					if($sql->rows) $procedimento=mysqli_fetch_object($sql->mysqry);
					
				}
			}

			$erro='';
			if(empty($evolucao)) $erro='Evolução não encontrada!';
			else if(empty($procedimentoEvoluir)) $erro='Procedimento evoluído não encontrada!';

			if(empty($erro)) {

				$historico=array();
				$sql->consult($_p."pacientes_tratamentos_procedimentos_evolucao_historico","*","where id_evolucao=$evolucao->id and id_procedimento_aevoluir=$procedimentoEvoluir->id");
				while($x=mysqli_fetch_object($sql->mysqry)) {
					$historico[]=array('id'=>$x->id,
										'data'=>date('d/m/Y H:i',strtotime($x->data)),
										'usuario'=>encodingToJson($x->usuario),
										'obs'=>encodingToJson($x->obs));
				}

				$rtn=array('success'=>true,
							'dataEvolucao'=>date('d/m/Y H:i',strtotime($evolucao->data)),
							'status'=>$procedimentoEvoluir->status_evolucao,
							'procedimento'=>encodingToJson($procedimento->titulo),
							'historico'=>$historico);

			} else {
				$rtn=array('success'=>false,'error'=>$erro);
			}
		}

		header("Content-type: application/json");
		echo json_encode($rtn);
		die();
	}
	include "includes/header.php";
	include "includes/nav.php";

	$_table=$_p."pacientes_prontuarios";
	require_once("includes/header/headerPacientes.php");
?>



	<main class="main">
		<div class="main__content content">

			<section class="filter">
				<div class="filter-group">
					<div class="filter-title">
						<h1>Arquivos</h1>
					</div>
				</div>
			</section>

			<section class="grid">

				<div class="box box-col">

					<?php 
					require_once("includes/submenus/subPacientesArquivos.php");
					?>

					<div class="box-col__inner1">
				
						<section class="filter">
							
							<div class="filter-group">
								<div class="filter-form form">
									<dl>
										<dd>
											<a href="javascript:;" class="button button_main js-btn-asideArquivo">
												<i class="iconify" data-icon="fluent:add-circle-24-regular"></i><span>Enviar Arquivo(s)</span>
											</a>
										</dd>
									</dl>
								</div>
							</div>

							<form method="get" class="js-filtro">
								<input type="hidden" name="id_paciente" value="<?php echo $paciente->id;?>">
								<div class="filter-group">
									<div class="filter-form form">
										
										<script type="text/javascript">
											$(function(){
												$('input[name=busca]').keydown(function(e){
													if(e.which==13) {
														$('.js-btn-buscar').click();
													}
												});
											})
										</script>
										<dl>
											<dd class="form-comp form-comp_pos"><input type="text" name="busca" placeholder="Buscar..." value="<?php echo isset($_GET['busca'])?($_GET['busca']):"";?>" /><a href="javascript:;" class="js-btn-buscar" onclick="$('form.js-filtro').submit();"><i class="iconify" data-icon="fluent:search-12-filled"></i></a></dd>
										</dl>
									</div>					
								</div>
							</form>						
						</section>

						<div class="list1">

							<?php
							$_dir="arqs/pacientes/arquivos/";
							$registros=[];
							$where="where id_paciente=$paciente->id and lixo=0 order by data desc";
							$sql->consult($_p."pacientes_arquivos","*",$where);
							while($x=mysqli_fetch_object($sql->mysqry)) {
								$registros[]=$x;
							}

							if(count($registros)==0) echo "<center>Este paciente não possui nenhum arquivo.</center>";
							else {
							?>
							<table class="js-table-pagamentos">
								<?php
								foreach($registros as $x) {
								?>
								<tr>
									<td style="width: 100px;"><?php echo date('d/m/Y\<\b\r\ \/\>H:i',strtotime($x->data));?></td>
									<td><strong><?php echo utf8_encode($x->titulo.".".$x->extensao);?></strong></td>
									<td style="width: 50px;"><a href="<?php echo $_scalewayS3endpoint."/".$infoConta->instancia."/".$_dir.md5($x->id).".".$x->extensao;?>" target="_blank" class="button button_main"><span class="iconify" data-icon="ic:baseline-file-download"></span></a></td>
								</tr>
								<?php
								}
								?>
							</table>
							<?php
							}
							?>

						</div>	
					</div>
					
				</div>

			</section>


			
		</div>
	</main>

<?php 

	require_once("includes/api/apiAsideArquivos.php");

	include "includes/footer.php";
?>	