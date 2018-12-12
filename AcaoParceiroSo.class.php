<?php

/**
 * Classe responsável pelo controle dos módulos.
 */
class AcaoParceiroSo extends Action {
	
	//teste 00022
	
	private $objAcaoParceiro;
	public function __construct(){
	}

	/**
     * Efetua o tratamento das requisições. Pesquisa os parceiros cadastrados.
     *
     * @return array Retorna os dados resultantes do processamento.
     */
    public function pesquisarAcaoParceiro($idfAcao) {
    	
    	if($idfAcao == 0)
    	{
    		return $this->objAcao;
    	}
    	else
    	{
	    	$objAcao = new Acao();
	    	$objAcao->get("idfAcao", $idfAcao);
	    	$objAcao->find();
    	}    	
    	 
		return $objAcao;
    }
	
	/**
     * Efetua o tratamento das requisições. Pesquisa as ações cadastradas.
     *
     * @return array Retorna os dados resultantes do processamento.
     */
    public function pesquisar($sPag, $idfAcao) {
       	//Instancia a classe que representa a tabela que usaremos no BD           
        $this->objAcaoParceiro = new AcaoParceiro();

    	$qtdPorPag	= _QUANT_PAG_;	//Quantidade de itens por pagina
		
		$sQuery = "CALL USP_PESQUISA_ACAOPARCEIRO (".$idfAcao.",NULL, NULL, ".($sPag-1).",".$qtdPorPag.",NULL,NULL)";
        $this->objAcaoParceiro->executeSp($sQuery);
        
        $arrayAcaoParceiro = $this->objAcaoParceiro->toMultiArray();
        
        if($arrayAcaoParceiro == "")
        	$arrayAcaoParceiro = array();

    	return $arrayAcaoParceiro;
    }
    
    /**
     * Valida a entrada de dados
     * @param Objeto AcaoParceiro
     * @param código do parceiro antes da atualização
     * @return 1 - campo obrigatório, 2 - ação parceiro já cadastrado, 3 - entrada válida 
     */
    public function validarEntrada(AcaoParceiro $obj, $par_idf_ant = "null", $orden_ant = "null")
    {
    	$errors = array();
    	$this->objAcaoParceiro = $obj;

		$parceiro 	= $this->objAcaoParceiro->getPAR_IDF();
		$acao 		= $this->objAcaoParceiro->getACA_IDF();
		$logomarca 	= $this->objAcaoParceiro->getACP_LOGO();
		$assinatura = $this->objAcaoParceiro->getACP_ASS();
		$ordem		= $this->objAcaoParceiro->getACP_ORDEM();

		if($parceiro == 0)
		{
			$errors = array("Parceiro");
		}
		
		if($ordem == "" || !is_numeric($ordem))
		{
			$errors = array_merge($errors, array("Ordem"));
		}
		
		//fazer validação verificando se os dados informados já estão cadastrados
		if(count($errors) == 0)
		{
			if($parceiro != $par_idf_ant)
			{
				$objAcao2 = new AcaoParceiro();
	
				$sQuery = "CALL USP_PESQUISA_ACAOPARCEIRO (".$acao.",".$parceiro.",NULL,0,0,".$par_idf_ant.",NULL)";

	       		$objAcao2->executeSp($sQuery);
	
				if($objAcao2->N > 0)
				{
					//Ação Parceiro já cadastrado!
					$errors = array("1");
				}
			}
			
			if(count($errors) == 0 && $orden_ant != $ordem)
			{
				unset($objAcao2);
				$objAcao2 = new AcaoParceiro();
				$sQuery = "CALL USP_PESQUISA_ACAOPARCEIRO (".$acao.",NULL,".$ordem.",0,0,null,".$orden_ant.")";
       			$objAcao2->executeSp($sQuery);
				//print_r($sQuery);
				if($objAcao2->N > 0)
				{
					//A Ordem de exibição informada já está sendo utilizada!
					$errors = array("2");
				}
			}
		}
		//print_r($errors);
		return $errors;
    }
    
    /**
     * Salva ou atualiza AcaoParceiro
     * @param idfPar - Flag para verificação se o método deverá inserir ou atualizar
     * @return boolean
     */
    public function salvar($idfPar)
    {
    	if($idfPar == 0)
    	{
			if($this->objAcaoParceiro->getACP_LOGO() == "")
			{
				$this->objAcaoParceiro->setACP_LOGO(0);
			}
			
			if($this->objAcaoParceiro->getACP_ASS() == "")
			{
				$this->objAcaoParceiro->setACP_ASS(0);
			}

			$sQuery = "CALL USP_INSERE_ACAOPARCEIRO(".$_SESSION['USU_IDF']." ,".
						$this->objAcaoParceiro->getACA_IDF()." ,".
						$this->objAcaoParceiro->getPAR_IDF()." ,".
						$this->objAcaoParceiro->getACP_LOGO()." ,".
						$this->objAcaoParceiro->getACP_ASS()." ,".
						$this->objAcaoParceiro->getACP_ORDEM().")";

			$result = $this->objAcaoParceiro->executeSp($sQuery);

			if($result->message == "")
			{
				$result = $this->objAcaoParceiro->toMultiArray();
				if($result[0]['IDF'] != "")
 				{
					return true;
 				}
	 			else
	 			{
	 				return false;
	 			}   
			}
 			else
 			{
 				return false;
 			}
    	}
    	else
    	{
		//Update
	
		$sQuery = "CALL USP_ATUALIZA_ACAOPARCEIRO (".$this->objAcaoParceiro->getACA_IDF().
					", ".$this->objAcaoParceiro->getPAR_IDF().
					", ".$idfPar.
					", ".$this->objAcaoParceiro->getACP_LOGO().
					", ".$this->objAcaoParceiro->getACP_ASS().
					", ".$this->objAcaoParceiro->getACP_ORDEM().
					")";
		$result = $this->objAcaoParceiro->executeSp($sQuery);
	
		if($result->message == "")
		{
			$result = $this->objAcaoParceiro->toMultiArray();

			if($result[0]["SAIDA"] > 0)
			{
				return true;
			}
			else
			{
				return false;
			}
 		}
 		else
 		{
 			return false;
 		}
    	}
	}
	
	/**
     * Exclui Ação de Capacitação
     * @param $acao_idf - Chave da tabela ACAO
     * @param $par_idf  - Chave da tabela PARCEIRO
     * @return boolean
     */
    public function excluir($acao_idf, $par_idf)
    {
    	$objAcaoParceiro = new AcaoParceiro();  	
   
		$sQuery = "CALL USP_EXCLUI_ACAOPARCEIRO (".$acao_idf.", ".$par_idf.")";

		$objAcaoParceiro->executeSp($sQuery);
		$result = $objAcaoParceiro->toMultiArray();
		if($result[0]['SAIDA'] != 0 )
	 		return true;
	 	else
 			return false;	 	 
    }
}
?>
