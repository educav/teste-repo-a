<?php
/**
 * Classe responsável pelo controle dos módulos.
 */
class AcaoSo extends Action {

	private $objAcao;
	public function __construct(){

	}

	/**
     * Efetua o tratamento das requisições. Pesquisa as ações cadastradas.
     *
     * @return array Retorna os dados resultantes do processamento.
     */
	public function pesquisarAcao($idfAcao) {

		if($idfAcao == 0)
		{
			return $this->objAcao;
		}
		else
		{
			$objAcao = new Acao();
			$sQuery = "CALL USP_PESQUISA_ACAO($idfAcao,null,null,null,null,null,null,null,null,null,null)";
			//print $sQuery;
			$objAcao->executeSp($sQuery);

			return $objAcao;
		}
	}

	/** Efetua o tratamento das requisições. Pesquisa ações cadastradas.
     *
     * @return array Retorna os dados resultantes do processamento.
     */
	public function pesquisarAcaoMes($intMes,$exercicio) {

		$objAcao = new Acao();
		$sQuery = "CALL USP_PESQUISA_ACAOMES($intMes,$exercicio)";

		$objAcao->executeSp($sQuery);

		return $objAcao;
	}

	/**
     * Efetua o tratamento das requisições. Pesquisa as ações cadastradas.
     * @param $sPag
     * @param $areaTematica
     * @param $publicoAlvo
     * @param $orgao
     * @param $curso
     * @param $tipoAcao
     * @return array Retorna os dados resultantes do processamento.
     */
	public function pesquisar($sPag, $areaTematica, $publicoAlvo, $orgao, $curso, $tipoAcao,$dtcom = NULL) {

		$flgQuery = 0;
		//Instancia a classe que representa a tabela que usaremos no BD
		$this->objAcao = new Acao();

		$qtdPorPag	= _QUANT_PAG_;				//Quantidade de itens por pagina

		if($orgao == 0)
		{
			$orgao = "null";
		}

		if($areaTematica == 0)
		{
			$areaTematica = "null";
		}

		if($publicoAlvo == 0)
		{
			$publicoAlvo = "null";
		}

		if($curso == 0)
		{
			$curso = "null";
		}

		$sQuery = "CALL USP_PESQUISA_ACAO(null,$areaTematica,$publicoAlvo,$orgao,$curso,$tipoAcao,".$_SESSION['exercicio'].",".($sPag-1).",".$qtdPorPag.",NULL,".($dtcom?$dtcom:"NULL").")";
		//print $sQuery;exit;
		$this->objAcao->executeSp($sQuery);

		return $this->objAcao;
	}

	/**
     * Valida a entrada de dados
     * @param Objeto TipoAcao
     * @return 1 - campo obrigatório, 2 - Tipo ação já cadastrado, 3 - entrada válida 
     */
	public function validarEntrada(Acao $obj)
	{
		$errors = array();
		$this->objAcao = $obj;
		$rdbCategoria 	= $this->objAcao->getCAT_IDF();
		$rdbCompetencia = $this->objAcao->getCOM_IDF();
		$tipoAcao 		= $this->objAcao->getTAC_IDF();
		$areaTematica	= $this->objAcao->getATE_IDF();
		$publicoAlvo	= $this->objAcao->getPAL_IDF();
		$curso			= $this->objAcao->getCUR_IDF();
		$ch				= $this->objAcao->getACA_CARGA_HORARIA();
		$frequencia 	= $this->objAcao->getACA_FREQUENCIA();
		$publicado		= $this->objAcao->getACA_PUBLICADO();
		$vagas			= $this->objAcao->getACA_NUMERO_VAGAS();
		$statusAcao		= $this->objAcao->getACA_ESPECIFICA();
		if($vagas == "" || $vagas == "0")
		{
			$errors = array_merge($errors,array("Vagas"));
		}

		if($rdbCategoria == "" || $rdbCategoria == "0")
		{
			$errors = array_merge($errors,array("Categoria"));
		}

		if($rdbCompetencia == "" || $rdbCompetencia == "0")
		{
			$errors = array_merge($errors, array("Classificação das Competências"));
		}
		if($tipoAcao == "" || $tipoAcao == "0")
		{
			$errors = array_merge($errors, array("Tipo Ação de Capacitação"));
		}

		if(($areaTematica == "" || $areaTematica == "0") && $rdbCategoria != 2)
		{
			$errors = array_merge($errors, array("Área Temática"));
		}

		if($publicoAlvo == "" || $publicoAlvo == "0")
		{
			$errors = array_merge($errors, array("Público Alvo"));
		}

		if($curso == "" || $curso == "0")
		{
			$errors = array_merge($errors, array("Objetivos"));
		}

		if($ch == "")
		{
			$errors = array_merge($errors, array("CH"));
		}

		if($frequencia == "" || $frequencia == "0")
		{
			$errors = array_merge($errors, array("Freq."));
		}

		if($publicado == "")
		{
			$errors = array_merge($errors, array("Publicado"));
		}

		if($rdbCategoria == 2 && $statusAcao == "")
		{
			$errors = array_merge($errors, array("Status da Ação"));
		}

		//fazer validação verificando se os dados informados já estão cadastrados

		if(count($errors) == 0)
		{
			$objAcao2 = new Acao();

			$objAcao2->whereAdd("CUR_IDF = " . $curso);
			$objAcao2->whereAdd("ACA_CARGA_HORARIA = " . $ch);
			$objAcao2->whereAdd("EXE_IDF = ". $_SESSION["exercicio"]);

			if($this->objAcao->getACA_IDF() != "")
			{
				$objAcao2->whereAdd("ACA_IDF != ". $this->objAcao->getACA_IDF());
			}
			//print_r($objAcao2);

			$objAcao2->find();

			if($objAcao2->N > 0)
			{
				//Ação de Capacitação já cadastrada!
				$errors = array("1");
			}
		}
		return $errors;
	}

	/**
     * Salva ou atualiza Acao
     * @param novo - Flag para verificação se o método deverá inserir ou atualizar
     * @return boolean
     */
	public function salvar($novo)
	{
		if($novo == 0)
		{
			//Insert
			if($this->objAcao->getATE_IDF()== "" || $this->objAcao->getATE_IDF()== 0)
			$this->objAcao->setATE_IDF("NULL");
			$sQuery = "CALL USP_INSERE_ACAO(".$_SESSION['USU_IDF'].
			",".$this->objAcao->getCUR_IDF().
			", ". $this->objAcao->getATE_IDF().
			", ". $this->objAcao->getCAT_IDF().
			", ". $this->objAcao->getEXE_IDF().
			", ". $this->objAcao->getTAC_IDF().
			", ". $this->objAcao->getCOM_IDF().
			", ". $this->objAcao->getPAL_IDF().
			", ". $this->objAcao->getACA_CARGA_HORARIA().
			", ". $this->objAcao->getACA_FREQUENCIA().
			", ". $this->objAcao->getACA_APROVACAO().
			", ". $this->objAcao->getACA_NUMERO_VAGAS().
			", ". $this->objAcao->getACA_PUBLICADO().
			", '". $this->objAcao->getACA_OBJETIVO().
			"', '". $this->objAcao->getACA_ESPECIFICA().
			"', ". $this->objAcao->getACA_LIBERAR().
			")";
			//print_r($sQuery);exit;
			$this->objAcao->executeSp($sQuery);
			$result = $this->objAcao->toMultiArray();
			if(is_array($result))
			{
				return $result[0]['IDF'];
			}
			else
			{
				return 0;
			}
		}
		else
		{
			if($this->objAcao->getATE_IDF()== "" || $this->objAcao->getATE_IDF()== 0)
			$this->objAcao->setATE_IDF("NULL");
			$sQuery = "CALL USP_ATUALIZA_ACAO(".$_SESSION['USU_IDF'].
			", ". $this->objAcao->getACA_IDF().
			", ". $this->objAcao->getCUR_IDF().
			", ". $this->objAcao->getATE_IDF().
			", ". $this->objAcao->getCAT_IDF().
			", ". $this->objAcao->getEXE_IDF().
			", ". $this->objAcao->getTAC_IDF().
			", ". $this->objAcao->getCOM_IDF().
			", ". $this->objAcao->getPAL_IDF().
			", ". $this->objAcao->getACA_CARGA_HORARIA().
			", ". $this->objAcao->getACA_FREQUENCIA().
			", ". $this->objAcao->getACA_APROVACAO().
			", ". $this->objAcao->getACA_NUMERO_VAGAS().
			", ". $this->objAcao->getACA_PUBLICADO().
			", '". $this->objAcao->getACA_OBJETIVO().
			"', '". $this->objAcao->getACA_ESPECIFICA().
			"', ". $this->objAcao->getACA_LIBERAR().
			")";
			
			
			$this->objAcao->executeSp($sQuery);
			$result = $this->objAcao->toMultiArray();
			if(is_array($result) && $result[0]["SAIDA"] != 0){
				return $this->objAcao->getACA_IDF();
			}else {
				return 0;
			}
		}
	}

	public function atualizaLiberarOrgao(Acao $objAcao, $liberar)
	{

		$sQuery = "CALL USP_ATUALIZA_ACAO(".$_SESSION['USU_IDF'].
		", ". $objAcao->getACA_IDF().
		", ". $objAcao->getCUR_IDF().
		", ". $objAcao->getATE_IDF().
		", ". $objAcao->getCAT_IDF().
		", ". $objAcao->getEXE_IDF().
		", ". $objAcao->getTAC_IDF().
		", ". $objAcao->getCOM_IDF().
		", ". $objAcao->getPAL_IDF().
		", ". $objAcao->getACA_CARGA_HORARIA().
		", ". $objAcao->getACA_FREQUENCIA().
		", ". $objAcao->getACA_APROVACAO().
		", ". $objAcao->getACA_NUMERO_VAGAS().
		", ". $objAcao->getACA_PUBLICADO().
		", '". $objAcao->getACA_OBJETIVO().
		"', '". $objAcao->getACA_ESPECIFICA().
		"', ". $liberar.
		")";

		$objAcao->executeSp($sQuery);
		$result = $objAcao->toMultiArray();
		if(is_array($result) && $result[0]["SAIDA"] != 0){
			return $objAcao->getACA_IDF();
		}else {
			return 0;
		}

	}
	/**
     * Exclui Ação de Capacitação
     * @param $acao_idf - Chave da tabela modulo
     * @return boolean
     */
	public function excluir($acao_idf)
	{
		$objAcao = new Acao();

		$sQuery = "CALL USP_EXCLUI_ACAO(".$_SESSION['USU_IDF'].",". $acao_idf . ")";

		$result = $objAcao->executeSp($sQuery);

		if($result->message == "")
		{
			$result = $objAcao->toMultiArray();

			if($result[0]["SAIDA"] > 0)
			{
				return true;
			}
		}
		else
		{
			return false;
		}
	}

	public function retornaSemeadorWS($strCurso){

		$objDoSemeador = new InformacoesCurso();
		$objDoSemeador->intCodigoCurso = $strCurso;

		$objDoSemeador2 = new WS_SemeadorServicos();

		$resultado = $objDoSemeador2->InformacoesCurso($objDoSemeador);
		//print_r($resultado);
		return simplexml_load_string($resultado->InformacoesCursoResult->any);

	}

	/**
     * Busca os cursos vindo do WebService Semeador(DtCom)
     */
	public function retornaGradeCursoAnoWS($strAno, $strCurso){

		$objDoSemeador = new listarGradeCursoAno();
		$objDoSemeador->ano = $strAno;
		$objDoSemeador->curso = $strCurso;

		$objDoSemeador2 = new WS_SemeadorGrade();

		$resultado = $objDoSemeador2->listarGradeCursoAno($objDoSemeador);
		//print "<pre>";print_r($resultado);
		return simplexml_load_string($resultado->listarGradeCursoAnoResult->any);

	}

	/**
     * Busca as grades vindo do WebService Semeador(DtCom)
     */
	public function retornaInfoCursoWS($strCurso){

		$objDoSemeador = new InformacoesCurso();
		$objDoSemeador->intCodigoCurso	= $strCurso;

		$objDoSemeador2 = new WS_SemeadorServicos();

		$resultado = $objDoSemeador2->InformacoesCurso($objDoSemeador);
		//print "<hr>Resultado: ";print_r($resultado);
		return simplexml_load_string($resultado->InformacoesCursoResult->any);

	}

	public function atualizarCH($acao_idf, $ch) {

		$objAcao = new Acao();

		$sQuery = "CALL USP_ATUALIZA_ACAO_CH(
				".$_SESSION['USU_IDF'].",
				".$acao_idf.",
				".$ch.")";

		$result = $objAcao->executeSp($sQuery);

		if($result->message == "") {
			$result = $objAcao->toMultiArray();
			if($result[0]["SAIDA"] > 0)
			return true;
		} else
		return false;

	}

	public function validarVagasTurma($aca_idf) {

		$objAcao = new Acao();
		$sQuery = "CALL USP_PESQUISA_VALIDATURMAORGAO(".$aca_idf.", NULL)";
		//	    	print $sQuery;
		$objAcao->executeSp($sQuery);

		return $objAcao;
	}
	
	/**
     * Pesquisa codigo ACM_IDF
     */
	public function pesquisaAcmIdf($acao_idf)
	{
		$objAcaoModulo = new AcaoModulo();

		$sQuery = "CALL USP_PESQUISA_ACAOMODULOPARACONTEUDO(". $acao_idf .")";
		//echo $sQuery;
		$objAcaoModulo->executeSp($sQuery);
		
		$result = $objAcaoModulo->toMultiArray();

		return $result;
	}
	/**
	 * Atualizar a situação da assinatura padra de um derterminado certificado
	 * @return 
	 */
	public function atualizarAssinaturaPadraoCertificado($aca_idf, $status)
	{
		$objAcao = new Acao();
		
		$query = "CALL USP_ATUALIZA_ACAO_ASS_CERTIFICADO(".$aca_idf.",".($status == "" ? "NULL" : "'".$status."'").")";
		
		$objAcao->executeSp($query);
		
		return $objAcao->toMultiArray();
	}



}
?>
