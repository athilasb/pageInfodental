<?php
	include "includes/header.php";
	include "includes/nav.php";
	require_once("includes/header/headerPacientes.php");

	// configuracao da pagina
		$_table=$_p."pacientes_tratamentos";
		$_page=basename($_SERVER['PHP_SELF']);


	// dados
		// clinica
			$clinica='';
			$sql->consult($_p."clinica","*","");
			$clinica=mysqli_fetch_object($sql->mysqry);

		// profissionais
			$_profissionais=array();
			$sql->consult($_p."colaboradores","id,nome,calendario_iniciais,foto,calendario_cor,check_agendamento,contratacaoAtiva","where tipo_cro<>'' and lixo=0 order by nome asc");
			while($x=mysqli_fetch_object($sql->mysqry)) $_profissionais[$x->id]=$x;

		// procedimentos
			$_procedimentos=array();
			$sql->consult($_p."parametros_procedimentos","*","where lixo=0");
			while($x=mysqli_fetch_object($sql->mysqry)) $_procedimentos[$x->id]=$x;

		// regioes
			$_regioesOpcoes=array();
			$sql->consult($_p."parametros_procedimentos_regioes_opcoes","*","order by titulo asc");
			while($x=mysqli_fetch_object($sql->mysqry)) $_regioesOpcoes[$x->id_regiao][]=$x;

			$_regioes=array();
			$sql->consult($_p."parametros_procedimentos_regioes","*","");
			while($x=mysqli_fetch_object($sql->mysqry)) $_regioes[$x->id]=$x;

			$_regioesFaces=array();
			$_regioesFacesOptions='';
			$_regioesInfos=array();
			$sql->consult($_p."parametros_procedimentos_regioes_faces","*"," order by titulo asc");
			while($x=mysqli_fetch_object($sql->mysqry)) {
				$_regioesFaces[$x->id]=$x;
				$_regioesFacesOptions.='<option value="'.$x->id.'">'.utf8_encode($x->titulo).'</option>';
				$_regioesInfos[$x->id]=array('abreviacao'=>$x->abreviacao,'titulo'=>utf8_encode($x->titulo));
			}

		// situacao
			$_selectSituacaoOptions=array('aprovado'=>array('titulo'=>'APROVADO','cor'=>'green'),
											'naoAprovado'=>array('titulo'=>'REPROVADO','cor'=>'red'));

			$selectSituacaoOptions='';
			foreach($_selectSituacaoOptions as $key=>$value) {
				$selectSituacaoOptions.='<option value="'.$key.'">'.$value['titulo'].'</option>';
			}

		// formas de pagamento
			$_formasDePagamento=array();
			$optionFormasDePagamento='';
			$sql->consult($_p."parametros_formasdepagamento","*","where lixo=0 order by titulo asc");
			while($x=mysqli_fetch_object($sql->mysqry)) {
				$_formasDePagamento[$x->id]=$x;
				$optionFormasDePagamento.='<option value="'.$x->id.'" data-tipo="'.$x->tipo.'">'.utf8_encode($x->titulo).'</option>';
			}

		// credito, debito, bandeiras
			$_bandeiras=array();
			$sql->consult($_p."parametros_cartoes_bandeiras","*","where lixo=0");
			while($x=mysqli_fetch_object($sql->mysqry)) {
				$_bandeiras[$x->id]=$x;
			}


			$creditoBandeiras=array();
			$debitoBandeiras=array();
			$_operadoras=array();
	
			$sql->consult($_p."parametros_cartoes_operadoras","*","where lixo=0 order by titulo");
			while($x=mysqli_fetch_object($sql->mysqry)) {
				$_operadoras[$x->id]=$x;
				$creditoBandeiras[$x->id]=array('titulo'=>utf8_encode($x->titulo),'bandeiras'=>array());
				$debitoBandeiras[$x->id]=array('titulo'=>utf8_encode($x->titulo),'bandeiras'=>array());
			}

			$sql->consult($_p."parametros_cartoes_operadoras_bandeiras","*","where lixo=0");
			while($x=mysqli_fetch_object($sql->mysqry)) {
				if(isset($_bandeiras[$x->id_bandeira]) and isset($_operadoras[$x->id_operadora])) {
					$bandeira=$_bandeiras[$x->id_bandeira];
					
					$txJson = json_decode($x->taxas);


					if($x->check_debito==1) {
						$debitoTaxa=isset($txJson->debitoTaxas->taxa)?$txJson->debitoTaxas->taxa:0;
						$debitoDias=isset($txJson->debitoTaxas->dias)?$txJson->debitoTaxas->dias:0;
						$debitoBandeiras[$x->id_operadora]['bandeiras'][$x->id_bandeira]=array('id_bandeira'=>$x->id_bandeira,
																								'titulo'=>utf8_encode($bandeira->titulo),
																							 	'taxa'=>$debitoTaxa,
																							 	'dias'=>$debitoDias);
					}
					if($x->check_credito==1) {
						$creditoBandeiras[$x->id_operadora]['bandeiras'][$x->id_bandeira]=array('id_bandeira'=>$x->id_bandeira,
																								'titulo'=>utf8_encode($bandeira->titulo),
																								'parcelas'=>$x->credito_parcelas,
																								'semJuros'=>$x->credito_parcelas_semjuros);
					}
				}
			}



			/*$_semJuros=array();
			$sql->consult($_p."parametros_cartoes_taxas_semjuros","*","where lixo=0");
			while($x=mysqli_fetch_object($sql->mysqry)) {
				$_semJuros[$x->id_operadora][$x->id_bandeira]=$x->semjuros;
			}


			$sql->consult($_p."parametros_cartoes_taxas","*","where lixo=0");
			$_taxasCredito=$_taxasCreditoSemJuros=array();
			while($x=mysqli_fetch_object($sql->mysqry)) {
				if(isset($_bandeiras[$x->id_bandeira])) {
					$bandeira=$_bandeiras[$x->id_bandeira];
					if($x->operacao=="credito") {
						if(isset($creditoBandeiras[$x->id_operadora])) {
							$semJurosTexto="";
							if($bandeira->parcelasAte>0) {
								$semJurosTexto.=" - em ate ".$bandeira->parcelasAte."x";
							}
							if(!isset($_taxasCredito[$x->id_operadora][$bandeira->id][$x->parcela])) {
								$_taxasCredito[$x->id_operadora][$bandeira->id][$x->parcela]=$x->taxa;
							}

							$creditoBandeiras[$x->id_operadora]['bandeiras'][$bandeira->id]=array('id_bandeira'=>$bandeira->id,
																								//	'semJuros'=>$semJuros,
																									'parcelas'=>$bandeira->parcelasAte,
																									'taxa'=>$x->taxa,	
																									'titulo'=>utf8_encode($bandeira->titulo).$semJurosTexto);
						}
					} else {

						$debitoBandeiras[$x->id_operadora]['bandeiras'][$bandeira->id]=array('id_bandeira'=>$bandeira->id,
																								'titulo'=>utf8_encode($bandeira->titulo),
																								'taxa'=>$x->taxa);
					}

				}
			}*/


	// formulario
		$cnt='';
		$campos=explode(",","titulo,id_profissional,tempo_estimado");
			
		foreach($campos as $v) $values[$v]='';
		$values['procedimentos']="[]";
		$values['pagamentos']="[]";

		if(isset($_GET['edita']) and is_numeric($_GET['edita'])) {
			$sql->consult($_table,"*","where id=".$_GET['edita']);
			if($sql->rows) {
				$cnt=mysqli_fetch_object($sql->mysqry);
				$values=$adm->values($campos,$cnt);

				// Procedimentos
					$procedimentosRegs=$usuariosIds=array();
					$where="where id_tratamento=$cnt->id and id_paciente=$paciente->id and lixo=0";
					$sql->consult($_table."_procedimentos","*",$where);
					while($x=mysqli_fetch_object($sql->mysqry)) {
						$usuariosIds[$x->id_usuario]=$x->id_usuario;
						$procedimentosRegs[]=$x;
					}

					$_usuarios=array();
					if(count($usuariosIds)>0) {
						$sql->consult($_p."colaboradores","id,nome","where id IN (".implode(",",$usuariosIds).")");
						while ($x=mysqli_fetch_object($sql->mysqry)) {
							$_usuarios[$x->id]=$x;
						}
					}


					$procedimentos=array();
					foreach($procedimentosRegs as $x) {


						/*$profissional=isset($_profissionais[$x->id_profissional])?$_profissionais[$x->id_profissional]:'';
						$iniciaisCor='';
						$iniciais='?';
						if(is_object($profissional)) {
							$iniciais=$profissional->calendario_iniciais;

							$iniciaisCor=$profissional->calendario_cor;
						}*/

						$valor=$x->valorSemDesconto;
						//if($x->quantitativo==1) $valor*=$x->quantidade;

						$autor = isset($_usuarios[$x->id_usuario]) ? utf8_encode($_usuarios[$x->id_usuario]->nome) : 'Desconhecido';

						$facesArray=[];
						$aux=explode(",",$x->faces);
						foreach($aux as $f) {
							if(!empty($f) and is_numeric($f)) $facesArray[]=$f;
						}

						$valorCorrigido=$x->valor;

						if($x->quantitativo==1) $valorCorrigido*=$x->quantidade;
						else if($x->face==1)  $valorCorrigido*=count($facesArray);
						else if($x->id_regiao==5) $valorCorrigido*=$x->hof;

						$procedimentos[]=array('id'=>$x->id,
												'autor'=>$autor,
												'data'=>date('d/m/Y H:i',strtotime($x->data)),
												'id_procedimento'=>(int)$x->id_procedimento,
												'procedimento'=>utf8_encode($x->procedimento),
												//'id_profissional'=>(int)$x->id_profissional,
												'profissional'=>utf8_encode($x->profissional),
												'id_plano'=>(int)$x->id_plano,
												'plano'=>utf8_encode($x->plano),
												'quantitativo'=>(int)$x->quantitativo,
												'quantidade'=>(int)$x->quantidade,
												'id_opcao'=>(int)$x->id_opcao,
												'opcao'=>utf8_encode($x->opcao),
												'valorCorrigido'=>(float)$valorCorrigido,
												'valor'=>(float)$valor,
												'desconto'=>(float)$x->desconto,
												'obs'=>utf8_encode($x->obs),
												'situacao'=>$x->situacao,
												'id_regiao'=>$x->id_regiao,
												'face'=>$x->face,
												'faces'=>$facesArray,
												'hof'=>$x->hof);
												/*'iniciais'=>$iniciais,
												'iniciaisCor'=>$iniciaisCor);*/
					}

					if($cnt->status=="APROVADO") {
						$values['procedimentos']=json_encode($procedimentos);
					} else {
						$values['procedimentos']=empty($cnt->procedimentos)?"[]":utf8_encode($cnt->procedimentos);
					}

				// Pagamentos
					$pagamentos=array();
					$where="where id_tratamento=$cnt->id and id_paciente=$paciente->id and lixo=0";
					$sql->consult($_table."_pagamentos","*",$where);
					while($x=mysqli_fetch_object($sql->mysqry)) {
						
						$pagamentos[]=array('id'=>$x->id,
												'vencimento'=>date('d/m/Y',strtotime($x->data_vencimento)),
												'valor'=>(float)$x->valor);
					}

					if($cnt->status=="APROVADO") {
						$values['pagamentos']=json_encode($pagamentos);
					} else {
						$values['pagamentos']=empty($cnt->pagamentos)?"[]":utf8_encode($cnt->pagamentos);
					}

					$values['pagamentos']=empty($cnt->pagamentos)?"[]":utf8_encode($cnt->pagamentos);


			}
		}

		if(is_object($cnt)) {
			if(isset($_GET['deletaTratamento']) and $_GET['deletaTratamento']==$cnt->id) {

				$vsql="lixo=1";
				$vwhere="where id='".addslashes($_GET['deletaTratamento'])."'";
				$sql->consult($_table,"*",$vwhere);
				if($sql->rows) {
					$x=mysqli_fetch_object($sql->mysqry);

					if($x->status=="APROVADO") {
						$jsc->jAlert("Não é possível excluir tratamentos aprovados!","erro","");

					} else {
						$sql->update($_table,$vsql,$vwhere);
						$sql->add($_p."log","data=now(),id_usuario='".$usr->id."',tipo='update',vsql='".addslashes($vsql)."',vwhere='".addslashes($vwhere)."',tabela='".$_table."',id_reg='".$x->id."'");

						$sql->update($_table."_pagamentos","lixo=1,lixo_obs=6,lixo_data=now(),lixo_id_usuario=$usr->id","where id_tratamento=$x->id and id_paciente=$paciente->id");

						$adm->biCategorizacao();
						$jsc->jAlert("Tratamento excluído com sucesso!","sucesso","document.location.href='pg_pacientes_planosdetratamento.php?$url'");
					}
				}

			}
		} else {
			$sql->consult($_table,"id","where id_paciente=$paciente->id");
			$values['titulo']="Plano de tratamento ".($sql->rows+1);
		}


		$tratamentoAprovado=(is_object($cnt) and $cnt->status=="APROVADO")?true:false;
	

	
?>
	<script type="text/javascript">
		var procedimentos = [];
		var pagamentos = JSON.parse(`<?php echo ($values['pagamentos']);;?>`);
		var usuario = '<?php echo utf8_encode($usr->nome);?>';
		var id_usuario = <?php echo $usr->id;?>;
		var tratamentoAprovado = <?php echo ($tratamentoAprovado===true)?1:0;?>;

		const desativarCampos = () => {
			if(tratamentoAprovado===1) { 
				$('.js-pagamento-item').find('select,input').prop('disabled',true);
				$('#cal-popup').find('select:not(.js-profissional),input').prop('disabled',true);
				$('#cal-popup').find('.js-btn-excluir,.js-btn-descontoAplicarEmTodos').hide();
			}
		}


		$(function(){

			
			$('.js-btn-salvar').click(function(){
				let erro = ``;

				if($('input[name=titulo]').val().length==0) {
					erro='Digite o título do <b>Tratamento</b>';
					$('input[name=titulo]').addClass('erro');
				} /*else if($('select[name=id_profissional]').val().length==0) {
					erro='Selecione o <b>Profissional</b>';
					$('select[name=id_profissional]').addClass('erro');
				}*/else if(procedimentos.length==0) {
					erro='Adicione pelo menos um procedimento para iniciar um Plano de Tratamento';
				}

				if(erro.length==0) {
					$('.js-pagamento-item').each(function(index,elem) {
						if($(elem).find('.js-vencimento').val().length==0) {
							$(elem).find('.js-vencimento').addClass('erro');
							erro='Defina a(s) <b>Data(s) de Vencimento</b> do(s) pagamento(s)';
						}
					})
				}

				if(erro.length>0) {
					swal({title:"Erro", text: erro, html:true, type:"error", confirmButtonColor: "#424242"});
				} else {

					$('.js-form-plano').submit();

				}
			});

			$('.js-btn-status').click(function(){
				let status = $(this).attr('data-status');
				if(status=="PENDENTE") {
					$('input[name=status]').val('PENDENTE');
				} else if(status=="APROVADO") {
					$('input[name=status]').val('APROVADO');

				} else if(status=="CANCELADO") {
					$('input[name=status]').val('CANCELADO');

				} else  {

					$('input[name=status]').val('');
				}

				$('form.js-form-plano').submit();
			});

			$('.js-btn-adicionarProcedimento').click(function(){
				$(".aside-plano-procedimento-adicionar").fadeIn(100,function() {
					$(".aside-plano-procedimento-adicionar .aside__inner1").addClass("active");
				});

				$('.aside-plano-procedimento-adicionar .js-asidePlano-id_procedimento').chosen('destroy');
				$('.aside-plano-procedimento-adicionar .js-asidePlano-id_procedimento').chosen();
				
			})
		});

	</script>

	<main class="main">
		<div class="main__content content">

			<section class="filter">
				
				<div class="filter-group">				
				</div>

				<div class="filter-group">
					<div class="filter-form form">
						<dl>
							<dd><a href="pg_pacientes_planosdetratamento.php?<?php echo $url;?>" class="button"><i class="iconify" data-icon="fluent:arrow-left-24-regular"></i></a></dd>
						</dl>
						<?php
						if(is_object($cnt)) {

						?>
						<dl>
							<dd>
								<?php
								if($cnt->status=="APROVADO") {
								?>
								<a href="javascript:;" class="button" style="opacity: 0.3;"><i class="iconify" data-icon="fluent:delete-24-regular"></i></a>
								<?php
								} else {
								?>
								<a href="pg_pacientes_planosdetratamento_form.php?<?php echo $url;?>edita=<?php echo $cnt->id;?>&deletaTratamento=<?php echo $cnt->id;?>" class="button js-confirmarDeletar" data-msg="Deseja realmente remover este plano de tratamento?"><i class="iconify" data-icon="fluent:delete-24-regular"></i></a>
								<?php
								}
								?>
							</dd>
						</dl>
						<dl>
							<dd><a href="impressao/planodetratamento.php?id=<?php echo md5($cnt->id);?>" class="button" target="_blank"><i class="iconify" data-icon="fluent:print-24-regular"></i></a></dd>
						</dl>
						<?php
						}
						if($tratamentoAprovado===false) {
						?>
						<dl>
							<dd><a href="javascript:;" class="button button_main js-btn-salvar"><i class="iconify" data-icon="fluent:checkmark-12-filled"></i><span>Salvar</span></a></dd>
						</dl>
						<?php
						}
						?>
					</div>
				</div>
				
			</section>

			<?php
			// submit
			if(isset($_POST['acao'])) {


				if($_POST['acao']=="wlib") {

					$processa=true;
					if(empty($cnt)) {
						$sql->consult($_p."pacientes_tratamentos","*","where id_paciente=$paciente->id and titulo='".addslashes($_POST['titulo'])."' and lixo=0");
						if($sql->rows) {
							$x=mysqli_fetch_object($sql->mysqry);	
							//$processa=false;
							//$jsc->go("?form=1&id_paciente=$paciente->id&edita=$x->id");
							//die();
						}
					}


					
					if($processa===true) {	


						// persiste as informacoes do tratamento
						if($_POST['acao']=="wlib") {
							$vSQL=$adm->vSQL($campos,$_POST);
							$values=$adm->values;

							if($tratamentoAprovado===false) {
								$vSQL.="procedimentos='".addslashes(utf8_decode($_POST['procedimentos']))."',";
								$vSQL.="pagamentos='".addslashes(utf8_decode($_POST['pagamentos']))."',";
							}

							$idProfissional=(isset($_POST['id_profissional']) and is_numeric($_POST['id_profissional']))?$_POST['id_profissional']:0;

							if(isset($_POST['parcelas']) and is_numeric($_POST['parcelas'])) $vSQL.="parcelas='".$_POST['parcelas']."',";
							if(isset($_POST['pagamento'])) $vSQL.="pagamento='".$_POST['pagamento']."',";

							if(is_object($cnt)) {

								if(!empty($vSQL)) {
									$vSQL=substr($vSQL,0,strlen($vSQL)-1);
									$vWHERE="where id='".$cnt->id."'";
									$sql->update($_table,$vSQL,$vWHERE);
									$sql->add($_p."log","data=now(),id_usuario='".$usr->id."',tipo='update',vsql='".addslashes($vSQL)."',vwhere='".addslashes($vWHERE)."',tabela='".$_table."',id_reg='".$cnt->id."'");
									$id_tratamento=$cnt->id;

									if($tratamentoAprovado===false) {
										$sql->update($_table."_procedimentos","lixo=1","where id_tratamento=$id_tratamento and id_paciente=$paciente->id");
										$sql->update($_table."_pagamentos","lixo=1,lixo_obs=1,lixo_data=now(),lixo_id_usuario=$usr->id","where id_tratamento=$id_tratamento and id_paciente=$paciente->id");
									}
								} else {

									$id_tratamento=$cnt->id;
								}
							} else {
								$vSQL.="data=now(),id_paciente=$paciente->id";
								//echo $_table." ".$vSQL;die();
								$sql->add($_table,$vSQL);
								$id_tratamento=$sql->ulid;
								$sql->add($_p."log","data=now(),id_usuario='".$usr->id."',tipo='insert',vsql='".addslashes($vSQL)."',tabela='".$_table."',id_reg='".$id_tratamento."'");
							}

							$sql->update($_table."_procedimentos","id_profissional=$idProfissional","where id_tratamento=$id_tratamento");
						}
						if(isset($_POST['status']) and !empty($_POST['status'])) {

							if(is_object($cnt)) {
								$persistir=true;
								$msgOk='';
								$erro='';


								// Baixas de pagamento
								$pagamentosUnidosIds=array(-1);
								$pagamentosBaixas=0;
								$sql->consult($_table."_pagamentos","*","where id_tratamento=$cnt->id and lixo=0");
								if($sql->rows) {
									$pagamentosIds=array(-1);
									while($x=mysqli_fetch_object($sql->mysqry)) {
										$pagamentosIds[]=$x->id;

										// se for pagamento de fusao/uniao
										if($x->id_fusao>0) {
											$pagamentosUnidosIds[$x->id_fusao]=$x->id_fusao;
										}
									}

									// retorna pagamentos unidos
									$sql->consult($_table."_pagamentos","*","where id IN (".implode(",",$pagamentosUnidosIds).") and fusao=1 and lixo=0");
									if($sql->rows) {
										while($x=mysqli_fetch_object($sql->mysqry)) {
											$pagamentosIds[]=$x->id;
										}
									}

									
									$sql->consult($_table."_pagamentos_baixas","*","where id_pagamento IN (".implode(",",$pagamentosIds).") and lixo=0");
									$pagamentosBaixas=$sql->rows;

								}


								// APROVACAO
									if($_POST['status']=="APROVADO") { 

										// verifica se todos procedimentos estao com situacao/status APROVADO, OBSERVADO e/ou REPROVADO
											$erro="";
											if(isset($_POST['procedimentos'])  and !empty($_POST['procedimentos'])) {
										
												$procedimetosJSON=!empty($_POST['procedimentos'])?json_decode($_POST['procedimentos']):array();

												if(is_array($procedimetosJSON)){ 
													foreach($procedimetosJSON as $x) {
														$x=(object)$x;
														if($x->situacao=='aguardandoAprovacao') {
															$erro='Para aprovar o tratamento, é necessário aprovar/reprovar todos os procedimentos';
															$persistir=false;
															break;
														}
														/*if($x->situacao=="aprovado" and ($x->id_profissional==0 or empty($x->id_profissional))) {
															$erro='Para aprovar o tratamento, é necessário selecionar o Profissional para todos os procedimentos aprovados';
															$persistir=false;
															break;
														}*/
													}
												}
											};

										// verifica se financeiro bate
											if(empty($erro)) {
												$valorProcedimento=0;
												if(isset($_POST['procedimentos'])  and !empty($_POST['procedimentos'])) {
											
													$procedimetosJSON=!empty($_POST['procedimentos'])?json_decode($_POST['procedimentos']):array();

													if(is_array($procedimetosJSON)){ 
														foreach($procedimetosJSON as $x) {
															$x=(object)$x;
															if($x->situacao=='aprovado') {


																$qtd=1;
																if($x->quantitativo==1) $qtd=$x->quantidade;
																else if($x->face==1) $qtd=count($x->faces);
																else if($x->id_regiao==5) $qtd=$x->hof;

																$valorProcedimento+=number_format($x->valor*$qtd,2,".","");
																$valorProcedimento-=$x->desconto;

															}
														}
													}
												};

												$valorPagamento=0;
												
												if(isset($_POST['pagamentos'])  and !empty($_POST['pagamentos'])) {
													$pagamentosJSON=json_decode($_POST['pagamentos']);
													if(is_array($pagamentosJSON)) {
														foreach($pagamentosJSON as $x) {
															$x=(object)$x;
															$valorPagamento+=$x->valor;
														} 
													}
												}
												$valorPagamento=number_format($valorPagamento,2,".","");
												$valorProcedimento=number_format($valorProcedimento,2,".","");

												//echo $valorPagamento." ".$valorProcedimento." = ".abs($valorPagamento - $valorProcedimento);die();
												if(!(abs($valorPagamento - $valorProcedimento) < 0.50000001)) {
													$erro="Defina as parcelas de pagamento!";
												} 
											}

										


										if(empty($erro)) {
											if($cnt->status=="PENDENTE" or $cnt->status=="CANCELADO") {
												$sql->update($_table,"status='APROVADO',id_aprovado=$usr->id,data_aprovado=now()","where id=$cnt->id");
												$msgOk="Plano de Tratamento foi <b>APROVADO</b> com sucesso!";
											} else {
												$erro="Este tratamento já está APROVADO";
												$persistir=false;
											}
										}
									}

								// PENDENTE
									else if($_POST['status']=="PENDENTE") {
										if($pagamentosBaixas==0) {
											if($cnt->status=="APROVADO" || $cnt->status=="CANCELADO") {


												if(empty($erro)) {

													$sql->update($_table,"status='PENDENTE',id_aprovado=0,data_aprovado='0000-00-00 00:00:00'","where id=$cnt->id");
													$msgOk="Plano de Tratamento foi <b>ABERTO</b> com sucesso!";
													$persistir=false;


													// remove os pagamentos com fusao
													$sql->consult($_table."_pagamentos","*","where id_tratamento=$cnt->id and id_fusao>0");
													$pagamentosUnidosIds=array(-1);
													while($x=mysqli_fetch_object($sql->mysqry)) {
														$pagamentosUnidosIds[$x->id_fusao]=$x->id_fusao;
													}

													// retorna pagamentos de uniao
													$pagamentosFusaoIds=array(-1);
													$sql->consult($_table."_pagamentos","*","where id IN (".implode(",",$pagamentosUnidosIds).") and fusao=1");
													while($x=mysqli_fetch_object($sql->mysqry)) {
														$pagamentosFusaoIds[$x->id_fusao]=$x->id_fusao;
													}

													// retorna procedimentos de evolucao
													$tratamentosProdecimentosIds=array(0);
													$sql->consult($_table."_procedimentos","id","where id_tratamento=$cnt->id");
													while($x=mysqli_fetch_object($sql->mysqry)) $tratamentosProdecimentosIds[]=$x->id;

													$sql->update($_table."_procedimentos_evolucao","lixo=1","where id_tratamento_procedimento IN (".implode(",",$tratamentosProdecimentosIds).")");

													$sql->update($_table."_procedimentos","lixo=1","where id_tratamento=$cnt->id");
													$sql->update($_table."_pagamentos","lixo=1,lixo_obs='2 $cnt->id or id_fusao IN (".implode(",", $pagamentosFusaoIds).")',lixo_data=now(),lixo_id_usuario=$usr->id","where id_tratamento=$cnt->id");// or id_fusao IN (".implode(",", $pagamentosFusaoIds).")");
													//$sql->update($_table,"pagamentos=''","where id=$cnt->id");

												}



											} else {
												$erro="Este tratamento já está PENDENTE";
												$persistir=false;
											}
										} else {
											$erro="Para <b>REABRIR</b> este tratamento, estorne todas as suas baixas de pagamento!";
											$persistir=false;
										}
									}

								// CANCELADO
									else if($_POST['status']=="CANCELADO") {

										

										if($pagamentosBaixas==0) {

											if($cnt->status=="APROVADO" || $cnt->status=="PENDENTE") {
												$sql->update($_table,"status='CANCELADO',id_aprovado=0,data_aprovado='0000-00-00 00:00:00'","where id=$cnt->id");
												$msgOk="Plano de Tratamento foi <b>REPROVADO</b> com sucesso!";
												$persistir=false;

												// remove os pagamentos com fusao
												$sql->consult($_table."_pagamentos","*","where id_tratamento=$cnt->id and id_fusao>0");
												$pagamentosUnidosIds=array(-1);
												while($x=mysqli_fetch_object($sql->mysqry)) {
													$pagamentosUnidosIds[$x->id_fusao]=$x->id_fusao;
												}

												// retorna pagamentos de uniao
												$pagamentosFusaoIds=array(-1);
												$sql->consult($_table."_pagamentos","*","where id IN (".implode(",",$pagamentosUnidosIds).") and fusao=1");
												while($x=mysqli_fetch_object($sql->mysqry)) {
													$pagamentosFusaoIds[$x->id_fusao]=$x->id_fusao;
												}


												// retorna procedimentos de evolucao
												$tratamentosProcedimentosIds=array(-1);
												$sql->consult($_table."_procedimentos","id","where id_tratamento=$cnt->id");
												while($x=mysqli_fetch_object($sql->mysqry)) $tratamentosProcedimentosIds[]=$x->id;

												$sql->update($_table."_procedimentos_evolucao","lixo=1","where id_tratamento_procedimento IN (".implode(",",$tratamentosProcedimentosIds).")");

												$sql->update($_table."_procedimentos","lixo=1","where id_tratamento=$cnt->id");
												$sql->update($_table."_pagamentos","lixo=1,lixo_obs=3,lixo_data=now(),lixo_id_usuario=$usr->id","where id_tratamento=$cnt->id or id_fusao IN (".implode(",", $pagamentosFusaoIds).")");
												//$sql->update($_table,"pagamentos=''","where id=$cnt->id");
											} else {
												$erro="Este tratamento já está REPROVADO";
												$persistir=false;
											}
										} else {
											$erro="Não é possível REPROVAR este tratamento, pois ele já teve baixas de pagamentos. Estorne as baixas para poder REPROVÁ-LO!";
											$persistir=false;
										}
									}



								// Persiste informações
								if($persistir===true) {

									// Pagamentos
										if(isset($_POST['pagamentos'])  and !empty($_POST['pagamentos'])) {
											$pagamentosJSON=json_decode($_POST['pagamentos']);
											if(is_array($pagamentosJSON)) {
												$vSQLBaixa=array();
												foreach($pagamentosJSON as $x) {

													$taxasPrazos=array();

													// se for credito/debito
													if(isset($x->id_formapagamento)) {

														// se for credito
														if($x->id_formapagamento==2 and isset($x->creditoBandeira) and isset($x->id_operadora) and isset($x->qtdParcelas)) {
															/*$where="where id_bandeira='".$x->creditoBandeira."' and id_operadora='".$x->id_operadora."' and vezes='".$x->qtdParcelas."' and operacao='credito' and lixo=0";
															$sql->consult($_p."parametros_cartoes_taxas","parcela,taxa,prazo",$where);
															
															if($sql->rows) {
																while($t=mysqli_fetch_object($sql->mysqry)) {
																	$taxasPrazos[$t->parcela]=$t;
																}
															}*/

															$where="where id_bandeira='".$x->creditoBandeira."' and id_operadora='".$x->id_operadora."' and check_credito=1 and lixo=0";
															$sql->consult($_p."parametros_cartoes_operadoras_bandeiras","*",$where);
															if($sql->rows) {
																$tx=mysqli_fetch_object($sql->mysqry);
																$taxasPrazos = json_decode($tx->taxas,true);
															}
														}
														// se for debito
														else if($x->id_formapagamento==3 and isset($x->debitoBandeira) and isset($x->id_operadora)) {
															/*$where="where id_bandeira='".$x->debitoBandeira."' and id_operadora='".$x->id_operadora."' and operacao='debito' and lixo=0";
															$sql->consult($_p."parametros_cartoes_taxas","parcela,taxa,prazo",$where);
															
															if($sql->rows) {
																while($t=mysqli_fetch_object($sql->mysqry)) {
																	$taxasPrazos=$t;
																}
															}*/

															$where="where id_bandeira='".$x->debitoBandeira."' and id_operadora='".$x->id_operadora."' and check_debito=1 and lixo=0";
															$sql->consult($_p."parametros_cartoes_operadoras_bandeiras","*",$where);
															if($sql->rows) {
																$tx=mysqli_fetch_object($sql->mysqry);
																$taxasPrazos = json_decode($tx->taxas,true);
															}
														}
													}

													
													$vSQLPagamento="lixo=0,
																	id_paciente=$paciente->id,
																	id_tratamento=$id_tratamento,
																	id_formapagamento='".addslashes(isset($x->id_formapagamento)?$x->id_formapagamento:0)."',
																	qtdParcelas='".addslashes(isset($x->qtdParcelas)?$x->qtdParcelas:0)."',
																	data_vencimento='".addslashes(invDate($x->vencimento))."',
																	valor='".addslashes(($x->valor))."',";

													$pagamento='';
													if(isset($x->id) and is_numeric($x->id)) {
														$sql->consult($_table."_pagamentos","*","where id_tratamento=$id_tratamento and id=$x->id");
														if($sql->rows) {
															$pagamento=mysqli_fetch_object($sql->mysqry);
														}
													}

													if(is_object($pagamento)) {
														$vSQLPagamento.="data_alteracao=now(),id_usuario_alteracao=$usr->id";
														$vWHERE="WHERE id=$pagamento->id";
														$sql->update($_table."_pagamentos",$vSQLPagamento,$vWHERE);
														$id_tratamento_pagamento=$sql->ulid;
														$sql->add($_p."log","data=now(),id_usuario='".$usr->id."',tipo='update',vsql='".addslashes($vSQLPagamento)."',vwhere='".addslashes($vWHERE)."',tabela='".$_table."_pagamentos',id_reg='".$id_tratamento_pagamento."'");

													} else {
														$vSQLPagamento.="data=now(),id_usuario=$usr->id";
														$sql->add($_table."_pagamentos",$vSQLPagamento);
														$id_tratamento_pagamento=$sql->ulid;
														$sql->add($_p."log","data=now(),id_usuario='".$usr->id."',tipo='insert',vsql='".addslashes($vSQLPagamento)."',tabela='".$_table."_pagamentos',id_reg='".$id_tratamento_pagamento."'");
													}


													if(isset($x->id_formapagamento) and is_numeric($x->id_formapagamento) and isset($_formasDePagamento[$x->id_formapagamento])) {
														$f=$_formasDePagamento[$x->id_formapagamento];
														if($f->tipo=="credito") {

															if(isset($x->creditoBandeira) and is_numeric($x->creditoBandeira) and isset($_bandeiras[$x->creditoBandeira])) {

																$b = $_bandeiras[$x->creditoBandeira];

																$id_bandeira=$b->id;
																$id_operadora=$x->id_operadora;

																if(isset($x->qtdParcelas) and is_numeric($x->qtdParcelas)) {
																	$valorParcela=$x->valor/$x->qtdParcelas;
																	for($i=1;$i<=$x->qtdParcelas;$i++) {

																		$prazo=$taxa=0;
																		/*if(isset($taxasPrazos[$i])) {
																			$prazo=$taxasPrazos[$i]->prazo;
																			$taxa=$taxasPrazos[$i]->taxa;
																		}*/

																		if(isset($taxasPrazos['creditoTaxas'][$x->qtdParcelas][$i])) {
																			$tx=$taxasPrazos['creditoTaxas'][$x->qtdParcelas][$i];

																			$taxa=valor($tx['taxa']);
																			$prazo=$tx['dias'];
																			//var_dump($taxasPrazos['creditoTaxas'][$x->qtdParcelas][$i]);
																		} else echo "n";
																		

																		$dtVencimento=date('Y-m-d',strtotime(invDate($x->vencimento)." + $prazo days"));


																		$vSQLBaixa[]=array("id_pagamento"=>$id_tratamento_pagamento,
																							"data_vencimento"=>$dtVencimento,
																							"valor"=>$valorParcela,
																							"id_formadepagamento"=>$f->id,
																							"parcela"=>$i,
																							"taxa"=>$taxa,
																							"dias"=>$prazo,
																							"parcelas"=>$x->qtdParcelas,
																							"id_bandeira"=>$id_bandeira,
																							"id_operadora"=>$id_operadora,
																							"tipo"=>"credito");
																	}
																}
															}

															//echo json_encode($vSQLBaixa);die();
														} else if($f->tipo=="debito") {
															if(isset($x->debitoBandeira) and is_numeric($x->debitoBandeira) and isset($_bandeiras[$x->debitoBandeira])) {

																$b = $_bandeiras[$x->debitoBandeira];

																$id_bandeira=$b->id;
																$id_operadora=$x->id_operadora;

																$prazo=$taxa=0;
																if(is_object($taxasPrazos)) {
																	$prazo=$taxasPrazos->prazo;
																	$taxa=$taxasPrazos->taxa;
																}

																$dtVencimento=date('Y-m-d',strtotime(invDate($x->vencimento)." + $prazo days"));
																

																$vSQLBaixa[]=array("id_pagamento"=>$id_tratamento_pagamento,
																				"data_vencimento"=>$dtVencimento,
																				"valor"=>$x->valor,
																				"id_formadepagamento"=>$f->id,
																				"taxa"=>$taxa,
																				"id_bandeira"=>$id_bandeira,
																				"id_operadora"=>$id_operadora,
																				"tipo"=>"debito");
																	
																
															}
														} else {
															$vSQLBaixa[]=array("id_pagamento"=>$id_tratamento_pagamento,
																				"data_vencimento"=>invDate($x->vencimento),
																				"valor"=>$x->valor,
																				"id_formadepagamento"=>$f->id,
																				"tipo"=>"outros");

														}
													}
												} 

												foreach($vSQLBaixa as $x) {
													$x=(object)$x;
													$vsql="";
													$where="where id_pagamento=$x->id_pagamento";

													if($x->tipo=="credito") $where.=" and id_operadora='".$x->id_operadora."'
																						and id_bandeira='".$x->id_bandeira."' 
																						and parcela='".$x->parcelas."' 
																						and parcela='".$x->parcela."'";
													$baixa='';
													$sql->consult($_p."pacientes_tratamentos_pagamentos_baixas","*",$where);
													if($sql->rows) {
														$baixa=mysqli_fetch_object($sql->mysqry);
													} 

													if(!isset($x->id_operadora)) $x->id_operadora=0;
													if(!isset($x->id_bandeira)) $x->id_bandeira=0;
													if(!isset($x->parcelas)) $x->parcelas=0;
													if(!isset($x->parcela)) $x->parcela=0;

													$vsql="id_pagamento='$x->id_pagamento',
															id_usuario=$usr->id,
															tipoBaixa='pagamento',
															valor='$x->valor',
															taxa='".(isset($x->taxa)?$x->taxa:0)."',
															dias='".(isset($x->dias)?$x->dias:0)."',
															id_formadepagamento='$x->id_formadepagamento',
															data_vencimento='".($x->data_vencimento)."',
															parcelas='$x->parcelas',
															parcela='$x->parcela',
															id_operadora='$x->id_operadora',
															id_bandeira='$x->id_bandeira'";
															//echo $vsql."<BR>";die();
													if(is_object($baixa)) {
														$sql->update($_p."pacientes_tratamentos_pagamentos_baixas",$vsql,"where id=$baixa->id");
													} else {
														$sql->add($_p."pacientes_tratamentos_pagamentos_baixas","data=now(),".$vsql);

													}

												}
											}
										}

									// Procedimentos
										if(isset($_POST['procedimentos'])  and !empty($_POST['procedimentos'])) {
											
											$procedimetosJSON=!empty($_POST['procedimentos'])?json_decode($_POST['procedimentos']):array();
											//echo json_encode($procedimetosJSON);die();
											if(is_array($procedimetosJSON)){ 


												$procedimentosEvolucao=array();
												foreach($procedimetosJSON as $x) {

													if(!isset($x->quantidade)) $x->quantidade=1;

													$vSQLProcedimento="lixo=0,
																		id_paciente=$paciente->id,
																		id_tratamento=$id_tratamento,
																		id_procedimento='".addslashes($x->id_procedimento)."',
																		procedimento='".addslashes(utf8_decode($x->procedimento))."',
																		id_plano='".addslashes($x->id_plano)."',
																		plano='".addslashes(utf8_decode($x->plano))."',
																		profissional='".addslashes(utf8_decode($x->profissional))."',
																		situacao='".addslashes($x->situacao)."',
																		id_profissional='".$idProfissional."',
																		valor='".addslashes($x->valor)."',
																		desconto='".addslashes($x->desconto)."',
																		valorSemDesconto='".addslashes($x->valor)."',
																		quantitativo='".addslashes($x->quantitativo)."',
																		quantidade='".addslashes($x->quantidade)."',
																		id_opcao='".addslashes($x->id_opcao)."',
																		obs='".addslashes(utf8_decode($x->obs))."',
																		opcao='".addslashes(utf8_decode($x->opcao))."',
																		id_regiao='".addslashes($x->id_regiao)."',
																		face='".addslashes(utf8_decode($x->face))."',
																		faces='".(implode(",",$x->faces))."',
																		hof='".addslashes($x->hof)."',";

																		//var_dump($x->faces);die();
																		//id_profissional='".addslashes($x->id_profissional)."',
												
													$procedimento='';
													if(isset($x->id) and is_numeric($x->id)) {
														$sql->consult($_table."_procedimentos","*","where id_tratamento=$id_tratamento and id=$x->id");
														if($sql->rows) {
															$procedimento=mysqli_fetch_object($sql->mysqry);
														}
													}

													if(is_object($procedimento)) {
														$vSQLProcedimento.="data_alteracao=now(),id_usuario_alteracao=$usr->id";
														$vWHERE="WHERE id=$procedimento->id";
														$sql->update($_table."_procedimentos",$vSQLProcedimento,$vWHERE);
														$id_tratamento_procedimento=$procedimento->id;
														$sql->add($_p."log","data=now(),id_usuario='".$usr->id."',tipo='update',vsql='".addslashes($vSQLProcedimento)."',vwhere='".addslashes($vWHERE)."',tabela='".$_table."_procedimentos',id_reg='".$id_tratamento_procedimento."'");

													} else {
														$vSQLProcedimento.="data=now(),id_usuario=$usr->id";
														$sql->add($_table."_procedimentos",$vSQLProcedimento);
														$id_tratamento_procedimento=$sql->ulid;
														$sql->add($_p."log","data=now(),id_usuario='".$usr->id."',tipo='insert',vsql='".addslashes($vSQLProcedimento)."',tabela='".$_table."_procedimentos',id_reg='".$id_tratamento_procedimento."'");
													}

													if($id_tratamento_procedimento>0) {

														for($i=1;$i<=$x->quantidade;$i++) {
															$procedimentosEvolucao[]=array('id_tratamento_procedimento'=>$id_tratamento_procedimento,
																							'id_paciente'=>$paciente->id,
																							'id_procedimento'=>$x->id_procedimento,
																							'id_profissional'=>$idProfissional,
																							'status_evolucao'=>'iniciar',
																							'numeroTotal'=>$x->quantidade,
																							'numero'=>$i);
														}
													}

												}

												// cria os procedimentos de evolucao
												foreach($procedimentosEvolucao as $x) {
													$x=(object)$x;

													$vSQL="id_tratamento_procedimento=$x->id_tratamento_procedimento,
															id_paciente=$x->id_paciente,
															id_procedimento=$x->id_procedimento,
															id_profissional=$idProfissional,
															status_evolucao='$x->status_evolucao',
															numeroTotal='$x->numeroTotal',
															numero='$x->numero'";
													//echo $vSQL;die();

													$sql->consult($_p."pacientes_tratamentos_procedimentos_evolucao","*","where id_tratamento_procedimento='$x->id_tratamento_procedimento' and numero='$x->numero' and numeroTotal='$x->numeroTotal' and lixo=0");
													if($sql->rows) {
														$reg=mysqli_fetch_object($sql->mysqry);
														
														$sql->add($_p."pacientes_tratamentos_procedimentos_evolucao",$vSQL,"where id=$reg->id");
													} else {
														$sql->add($_p."pacientes_tratamentos_procedimentos_evolucao",$vSQL);
													}
												}
											}
										}
								}

								$adm->biCategorizacao();
								if(empty($erro)) {
									$jsc->jAlert($msgOk,"sucesso","document.location.href='$_page?form=1&edita=$cnt->id&$url'");
									die();
								} else {
									$jsc->jAlert($erro,"erro","document.location.href='$_page?form=1&edita=$cnt->id&$url'");
									die();
								}

							} else {
								$jsc->jAlert("Tratamento não encontrado!","erro","document.location.href='$_page?$url'");
								die();
							}
						} else {
							//$adm->biCategorizacao();e
							$jsc->jAlert("Informações salvas com sucesso!","sucesso","document.location.href='".$_page."?form=1&edita=$id_tratamento&id_paciente=$paciente->id'");
							die();
						}
					}

				}

			}
			?>

			<form method="post" class="form js-form-plano">
				<input type="hidden" name="acao" value="wlib" />
				<input type="hidden" name="status" />
				
				<div class="grid grid_2">

					<!-- Identificacao -->
					<fieldset>
						<legend>Identificação</legend>
						<dl>
							<dd>
								<?php
								if(is_object($cnt)) {
								?>
								<div class="button-group">
									<a href="javascript:;" data-status="PENDENTE" class="button js-btn-status<?php echo $cnt->status=="PENDENTE"?" active":"";?>"><i class="iconify" data-icon="fluent:timer-24-regular"></i><span>Aguard. Aprovação</span></a>
									<a href="javascript:;" data-status="APROVADO" class="button js-btn-status<?php echo $cnt->status=="APROVADO"?" active":"";?>"><i class="iconify" data-icon="fluent:checkbox-checked-24-filled"></i><span>Aprovado</span></a>
									<a href="javascript:;" data-status="CANCELADO" class="button js-btn-status<?php echo $cnt->status=="CANCELADO"?" active":"";?>"><i class="iconify" data-icon="fluent:dismiss-square-24-regular"></i><span>Reprovado</span></a>
								</div>
								<?php
								} else {
								?>
								<div class="button-group tooltip" style="opacity:0.4" title="Salve o tratamento para poder alterar o status">
									<a href="javascript:;" class="button"><i class="iconify" data-icon="fluent:timer-24-regular"></i><span>Aguard. Aprovação</span></a>
									<a href="javascript:;" class="button"><i class="iconify" data-icon="fluent:checkbox-checked-24-filled"></i><span>Aprovado</span></a>
									<a href="javascript:;" class="button"><i class="iconify" data-icon="fluent:dismiss-square-24-regular"></i><span>Reprovado</span></a>
								</div>
								<?php
								}
								?>
							</dd>
						</dl>
						<div class="colunas">
							<dl>
								<dt>Título</dt>
								<dd><input type="text" name="titulo" value="<?php echo $values['titulo'];?>"<?php echo $tratamentoAprovado==true?" disabled":"";?> /></dd>
							</dl>
							<dl>
								<dt>Profissional</dt>
								<dd>
									<select name="id_profissional" class="js-id_profissional"<?php echo $tratamentoAprovado==true?" disabled":"";?>>
										<option value=""><?php echo utf8_encode($clinica->clinica_nome);?></option>
										<?php
										foreach($_profissionais as $x) {
											if($x->check_agendamento==0 or $x->contratacaoAtiva==0) continue;
											$iniciais=$x->calendario_iniciais;
											echo '<option value="'.$x->id.'" data-iniciais="'.$iniciais.'" data-iniciaisCor="'.$x->calendario_cor.'"'.($values['id_profissional']==$x->id?" selected":"").'>'.utf8_encode($x->nome).'</option>';
										}
										?>
									</select>
								</dd>
							</dl>					
						</div>

						<div class="colunas">
							<dl>
								<dt>Tempo Estimado</dt>
								<dd class="form-comp form-comp_pos"><input type="number" name="tempo_estimado" value="<?php echo $values['tempo_estimado'];?>"<?php echo $tratamentoAprovado==true?" disabled":"";?> /><span>dias</span></dd>
							</dl>
						</div>
					</fieldset>

					<!-- Financeiro -->
					<fieldset style="grid-row:span 2">
						<legend>Financeiro</legend>
						<textarea name="pagamentos" id="js-textarea-pagamentos" style="display:none;"><?php echo $values['pagamentos'];?></textarea>
						<?php
						if($tratamentoAprovado===false) {
						?>
						<dl>
							<dd>
								<a href="javascript:;" class="button button_main js-btn-desconto"><i class="iconify" data-icon="fluent:money-calculator-24-filled"></i><span>Descontos</span></a>
							</dd>
						</dl>
						<?php
						}
						?>

						<div class="colunas3">
							<dl>
								<dt>Valor Total (R$)</dt>
								<dd style="font-size:1.75em; font-weight:bold;" class="js-valorTotal">0,00</dd>
							</dl>
							<?php
							if($tratamentoAprovado===false) {
							?>
							<dl class="dl2">
								<dt>Forma de Pagamento</dt>
								<dd>
									<label><input type="radio" name="pagamento" value="avista"<?php echo (is_object($cnt) and $cnt->pagamento=="avista")?" checked":"";?> disabled />A Vista</label>
									<label><input type="radio" name="pagamento" value="parcelado"<?php echo (is_object($cnt) and $cnt->pagamento=="parcelado")?" checked":"";?> disabled />Parcelado em</label>
									<label><input type="number" name="parcelas" class="js-pagamentos-quantidade" value="<?php echo (is_object($cnt) and $cnt->pagamento=="parcelado")?$cnt->parcelas:"2";?>" style="width:50px;<?php echo (is_object($cnt) and $cnt->pagamento=="parcelado")?"":"display:none;";?>" /></label>
								</dd>
							</dl>	
							<?php
							}
							?>						
						</div>

						<div class="fpag js-pagamentos" style="margin-top:2rem;">
							<?php

							/*
							?>
							<div class="fpag-item">
								<aside>1</aside>
								<article>
									<div class="colunas3">
										<dl>
											<dd class="form-comp"><span><i class="iconify" data-icon="fluent:calendar-ltr-24-regular"></i></span><input type="tel" name="" class="data" value="07/09/2022" /></dd>
										</dl>
										<dl>
											<dd class="form-comp"><span>R$</i></span><input type="tel" name="" class="valor" value="" /></dd>
										</dl>
										<dl>
											<dd>
												<select class="js-id_formadepagamento js-tipoPagamento">
													<option value="9" data-tipo="boleto">BOLETO</option>
												</select>
											</dd>
										</dl>
									</div>
									<div class="colunas3">
										<dl>
											<dt>Identificador</dt>
											<dd><input type="text" name="" /></dd>
										</dl>
									</div>
								</article>
							</div>

							<div class="fpag-item">
								<aside>2</aside>
								<article>
									<div class="colunas3">
										<dl>
											<dd class="form-comp"><span><i class="iconify" data-icon="fluent:calendar-ltr-24-regular"></i></span><input type="tel" name="" class="data" value="07/09/2022" /></dd>
										</dl>
										<dl>
											<dd class="form-comp"><span>R$</i></span><input type="tel" name="" class="valor" value="" /></dd>
										</dl>
										<dl>
											<dd>
												<select class="js-id_formadepagamento js-tipoPagamento">
													<option value="9" data-tipo="boleto">CARTÃO DE CRÉDITO</option>
												</select>
											</dd>
										</dl>
									</div>
									<div class="colunas3">
										<dl>
											<dt>Bandeira</dt>
											<dd><select name=""><option value=""></option></select></dd>
										</dl>
										<dl>
											<dt>Parcelas</dt>
											<dd><select name=""><option value="">1x</option></select></dd>
										</dl>
										<dl>
											<dt>Identificador</dt>
											<dd><input type="text" name="" /></dd>
										</dl>
									</div>
								</article>
							</div>
							*/?>
						</div>
					</fieldset>

					<!-- Procedimentos --> 
					<fieldset>
						<legend>Procedimentos</legend>
						<textarea name="procedimentos" id="js-textarea-procedimentos" style="display:none"><?php echo $values['procedimentos'];?></textarea>
						<?php
						if($tratamentoAprovado===false) {
						?>
						<dl>
							<dd>
								<a href="javascript:;" ata-aside="plano-procedimento-adicionar" class="button button_main js-btn-adicionarProcedimento"><i class="iconify" data-icon="fluent:add-circle-24-regular"></i><span>Adicionar Procedimento</span></a>
							</dd>
						</dl>
						<?php
						}
						?>
						
						<div class="list1">
							<table id="js-table-procedimentos">
								
							</table>
						</div>
					</fieldset>


				</div>


			</form>

		</div>
	</main>

<?php 
	
	require_once("includes/api/apiAsidePlanoDeTratamento.php");

	include "includes/footer.php";
?>	