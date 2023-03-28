<?php
    require_once("../../lib/conf.php");
	require_once("../../lib/classes.php");

    setcookie("infoName", $_GET['instancia'], time() + 3600*24, "/");
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Infodental - Sistema de Migração</title>
</head>
<body>

<?php


if(1==1){
    echo "migração da rbprevencao </br>";
    echo "trabalha apenas com a tabela ident_pacientes </br>";
    echo "nenhum dado será apagado";
    die();
}

$sql = new Mysql();
$pacientes = file("arqs/lista_pacientes_modificado.csv");


//nome,???,cpf,???,Data-cadastro,Data-nascimento,endenreco,bairro ,cidade,uf,cep,telefones,e-mail,status

$id = 0;
// apaga pacientes e agendamentos
$sql->consult($_p."pacientes", "MAX(id) as id", "");
if($sql->rows){
  $tmp = mysqli_fetch_object($sql->mysqry);
  var_dump($tmp);
  $id = $tmp->id;
  if($id == NULL || $id == 0){
    $id = 0;
  }else{ 
    ++$id;
  }
}

//pegando os pacientes da lista
$telefone;
$celular;

foreach($pacientes as $linha){
    list(
        $nome, 
        $desconhecido,
        $cpf,
        $desconhecido,
        $data_cadastro,
        $data_nascimento,
        $endereco,
        $bairro,
        $cidade,
        $uf,
        $cep,
        $telefones,
        $email,
        $status
    ) = explode(',', str_replace("\"", "", $linha));


    if(!empty($bairro))
        $endereco .= ", $bairro";
    if(!empty($cidade))
        $endereco .= ", $cidade";
    if(!empty($uf))
        $endereco .= ", $uf";

    $datanascimento = invDate(utf8_encode($data_nascimento));
    $datacadastro   = invDate($data_cadastro);

    $nome = trim($nome);
    if(empty($telefones))
        $celular = $telefone = 0;
    else{
        $celulares = explode(',',str_replace(["(", ")", "-"], "", $telefones));
        for($i = 0; $i < sizeof($celulares); $i++){
            if(strlen($celulares[$i]) == 9 || strlen($celulares[$i]) == 11 ){
                $celular = $celulares[$i];
            }else{
                $telefone = $celulares[$i];
            }
        }
    }
    $index = strtolowerWLIB(str_replace(" ","",tirarAcentos($nome)));
    //verificando por nomes repetidos
    if (isset($_pacientes[$index])) {
        echo "='" . $index . "' '$nome'<BR>";
        die();
    }
    
   /*  $_pacientes[$index] = array(
        'id_paciente' => $id,
        'lixo' => (trim($status)) == 'ativo' ? 1 : 0,
        'data' => $datacadastro,
        'nome' => utf8_encode($nome),
        'telefone' => $telefone,
        'celular' => $celular,
        'email' => $email,
        'dn' => $datanascimento,
        'endereco' => utf8_encode($endereco),
        'bairro' => utf8_encode($bairro),
        'cidade' => utf8_encode($cidade),
        'uf' => $uf,
        'cep' => str_replace([".", "-"], "", $cep),
        'cpf' => str_replace([".", "-"], "", $cpf)
    );*/
  //  var_dump($pacientes);
  //  die();

    $_vsql = "lixo = '".     ((trim($status)) == 'Ativo' ? 0 : 1) ."',
              data = '".     $datacadastro . "',
              nome = '".     addslashes(utf8_encode($nome))."',
              telefone2 = '". $telefone ."',
              telefone1 = '".  $celular ."',
              email = '".    addslashes($email) ."',
              data_nascimento = '".       $datanascimento ."',
              endereco = '". addslashes(utf8_encode($endereco)) ."',
              bairro = '".   addslashes(utf8_encode($bairro)) ."',
              cidade = '".   addslashes(utf8_encode($cidade)) ."',
              estado = '".       addslashes($uf) ."',
              cep = '".      str_replace([".", "-"], "", $cep) ."',
              cpf = '".      str_replace([".", "-"], "", $cpf) ."'";

        echo $_vsql . "</br>";
    $sql->add($_p."pacientes", $_vsql);
    echo '.';
}
echo "terminado";
?>

