<?php
/**
 * Classe responsável pelo controle dos módulos.
 */
class AcaoModuloSo extends Action {
	
	//TESTE GIT 001
	
	private $objAcaoModulo;
	private $modIdfAnt;
	public function __construct($modIdf="")
	{
		$this->modIdfAnt = $modIdf;
		//new data 16012019 16:08
	}

	public function pesquisar($sPag, $idfAcao, $iTodos="NULL")
	{
		if($idfAcao == 0)
		{
			return $this->objAcaoModulo;
		}
		else
		{

			if($iTodos=="NULL")
			$qtdPorPag = "NULL";
			else
			$qtdPorPag	= _QUANT_PAG_;				//Quantidade de itens por pagina

			$limiteSup = $sPag * $qtdPorPag;
			$limiteInf = $limiteSup - $qtdPorPag;

			$objAcaoModulo = new AcaoModulo();

			$sQuery = "CALL USP_PESQUISA_ACAOMODULO($idfAcao,null,".($sPag-1).",$qtdPorPag)";
			//print $sQuery;exit;
			$objAcaoModulo->executeSp($sQuery);

			return $objAcaoModulo;
		}
	}

	public function pesquisarAcaoModulo($idfAcao, $idfModulo)
	{
		if($idfAcao == 0)
		{
			return $this->objAcaoModulo;
		}
		else
		{
			$qtdPorPag	= _QUANT_PAG_;				//Quantidade de itens por pagina

			$objAcaoModulo = new AcaoModulo();

			$sQuery = "CALL USP_PESQUISA_ACAOMODULO($idfAcao,$idfModulo,null,null)";
			//print $sQuery;exit;
			$objAcaoModulo->executeSp($sQuery);

			return $objAcaoModulo;
		}
	}

	public function validaEntrada(AcaoModulo $obj)
	{
		$errors = array();
		$this->objAcaoModulo = $obj;
		$idfAcao 	  = $this->objAcaoModulo->getACA_IDF();
		$idfModulo 	  = $this->objAcaoModulo->getMOD_IDF();
		$cargaHoraria = $this->objAcaoModulo->getACM_CARGA_HORARIA();
		$metodologia  = $this->objAcaoModulo->getACM_METODOLOGIA();

		if($idfModulo == "0" or $idfModulo == "")
		{
			$errors = array("Módulo");
		}

		if($cargaHoraria == "")
		{
			$errors = array_merge($errors,array("Carga Horária"));
		}

		if($metodologia == "")
		{
			$errors = array_merge($errors,array("Metodologia"));
		}

		if(count($errors) == 0)
		{
			$objAcao = new AcaoSo();
			$objAcao = $objAcao->pesquisarAcao($idfAcao);

			$arrayAcao = $objAcao->toMultiArray();

			$cargaHorariaA = $arrayAcao[0]['CARGA_HORARIA'];
			$result = $this->pesquisarAcaoModulo($idfAcao,$idfModulo);

			//Verifica se já existe o módulo para ação cadastrado
			if($this->modIdfAnt != $idfModulo)
			{

				if($result->N > 0)
				{
					$errors =  array("1");
				}
			}

			$arrayAcaoModAnt = $result->toMultiArray();

			$objAcaoModulo = new AcaoModulo();

			$objAcaoModulo->executeSp("CALL USP_PESQUISA_ACAOMODULO($idfAcao,null,0,0)");

			$arrayModulo = $objAcaoModulo->toMultiArray();

			$totalCH = 0;
			for($i = 0; $i < $objAcaoModulo->N; $i++)
			{
				$totalCH = $totalCH + $arrayModulo[$i][ACAOMODULO_CARGAHORARIA];
			}
			$totalCH = $totalCH + $cargaHoraria - $arrayAcaoModAnt[0]['ACAOMODULO_CARGAHORARIA'];

			if($totalCH > $cargaHorariaA)
			{
				$errors = array_merge($errors,array("2"));
			}
		}
		return $errors;
	}

	public function salvar($iRecIdf,$ModuloIdf="") {
		if($iRecIdf == 0){
			//Insert
			$sQuery = "CALL USP_INSERE_ACAOMODULO(".$_SESSION['USU_IDF'].
			", ".$this->objAcaoModulo->getACA_IDF().
			", ". $this->objAcaoModulo->getMOD_IDF().
			", ". $this->objAcaoModulo->getACM_CARGA_HORARIA().
			", '". $this->objAcaoModulo->getACM_METODOLOGIA().
			"')";
			//print "->>>>> ".$sQuery;
			
		} else if($iRecIdf == 1) {
			//Update
			$sQuery = "CALL USP_ATUALIZA_ACAOMODULO( ".//$_SESSION['USU_IDF'].
			$this->objAcaoModulo->getACA_IDF().
			", ". $this->objAcaoModulo->getMOD_IDF().
			", ". $this->objAcaoModulo->getACM_CARGA_HORARIA().
			", '". $this->objAcaoModulo->getACM_METODOLOGIA().
			"', ". $ModuloIdf.
			")";
		}

		//print "->>>>> ".$sQuery;exit;

		$this->objAcaoModulo->executeSp($sQuery);
		$result = $this->objAcaoModulo->toMultiArray();

		if(is_array($result))
		{
			if($iRecIdf == 0 && $result[0]["IDF"] != 0){
				return $result[0]["IDF"];
			} elseif($iRecIdf == 1 && $result[0]["SAIDA"] != 0) {
				return $result[0]["SAIDA"];
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
     * Exclui Ação Módulo
     * @param $mod_idf - Chave ta tabela 
     * @param $acao_idf - - Chave ta tabela Ação
     * @return boolean
     */
	public function excluir($mod_idf, $acao_idf)
	{
		$objAcaoModulo = new AcaoModulo();

		$sQuery = "CALL USP_EXCLUI_ACAOMODULO(". $acao_idf . ", " . $mod_idf . ")";
		//	  	print $sQuery;

		$result = $objAcaoModulo->executeSp($sQuery);

		if($result->message == "")
		{
			$result = $objAcaoModulo->toMultiArray();

			if($result[0]["SAIDA"] > 0)
			{
				return true;
			}
		} else {
			return false;
		}
	}
}
?>
