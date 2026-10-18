<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Brazilian Portuguese language strings.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['administration'] = 'Administração do File X-Ray';
$string['areaavatar'] = 'Imagens de perfil dos usuários';
$string['areaavatar_desc'] = 'Fotos de perfil e variações de tamanho geradas para as contas dos usuários do Moodle.';
$string['areabackup'] = 'Componente backup (todas as áreas)';
$string['areabackup_desc'] = 'Soma de todas as áreas do componente backup, sem filtrar filearea. Pode incluir arquivos de cópias de segurança e recursos do sistema de backup.';
$string['areabadgeimage'] = 'Imagens de emblemas';
$string['areabadgeimage_desc'] = 'Imagens de badges e conquistas cadastradas no Moodle, incluindo as utilizadas na apresentação de emblemas concedidos.';
$string['areacoreh5p'] = 'Componente core_h5p (todas as áreas)';
$string['areacoreh5p_desc'] = 'Todas as áreas do componente core_h5p reunidas em uma linha, com os nomes existentes. Bibliotecas, exportações e conteúdos podem ser necessários às atividades H5P.';
$string['areadescription'] = 'O que são esses arquivos';
$string['areadoclink'] = 'O que são esses arquivos';
$string['areadraft'] = 'Rascunhos dos usuários';
$string['areadraft_desc'] = 'Arquivos temporários usados durante a edição ou seleção de anexos. Alguns rascunhos ainda podem estar em uso.';
$string['areaname'] = 'Categoria';
$string['areaother_desc'] = 'Arquivos registrados em outras áreas do Moodle ou de plugins. Não aparecer no backup dos cursos não prova que estejam sem uso.';
$string['areaoutsidebytes'] = 'Bytes fora do inventário';
$string['areaoutsidefiles'] = 'Fora do inventário';
$string['areaprivate'] = 'Arquivos privados dos usuários';
$string['areaprivate_desc'] = 'Arquivos pessoais privados, que não precisam fazer parte de um backup de curso.';
$string['areaprofile'] = 'Arquivos de perfil dos usuários';
$string['areaprofile_desc'] = 'Outros arquivos associados às áreas de perfil dos usuários.';
$string['areasummary'] = 'Outras áreas de arquivos em mdl_files';
$string['areasummary_note'] = 'Esta tabela classifica arquivos de usuários, distintivos, backups, H5P, temas, identidade visual, prévias, pastas, lixeira e outras áreas documentadas, exibindo o tamanho lógico e os registros ausentes dos backups dos cursos. H5P e temas aparecem consolidados em uma linha cada. Para temas, são mostradas até cinco áreas, seguidas de etc.... Totais por componente podem se sobrepor às linhas por área e não devem ser somados entre si.';
$string['areathemes'] = 'Temas';
$string['areathemes_desc'] = 'Arquivos dos componentes theme_* reunidos em uma única linha. A coluna Área de arquivos apresenta até cinco áreas distintas e acrescenta etc... quando houver mais.';
$string['areatotalbytes'] = 'Total de bytes';
$string['areatotalfiles'] = 'Total de registros';
$string['areauserbackup'] = 'Backups dos usuários';
$string['areauserbackup_desc'] = 'Cópias de segurança registradas na área de backup do usuário, que podem ser necessárias para restaurar cursos ou conteúdos.';
$string['bytes'] = 'Bytes';
$string["chartcoverage"] = "Cobertura dos inventários";
$string["chartcoveragefiles"] = "Arquivos";
$string["chartcoveragebytes"] = "Bytes (tamanho lógico)";
$string["chartcoverage_note"] = "Distribuição dos arquivos atuais do Moodle por quantidade de registros e bytes lógicos, em grupos que não se sobrepõem.";
$string["chartininventory"] = "Nos inventários dos cursos";
$string["chartnodata"] = "Não há registros para apresentar.";
$string["chartotherareas"] = "Fora dos inventários, em áreas classificadas";
$string["chartresidual"] = "Fora dos inventários e das áreas classificadas";
$string["charttopcourses"] = "Cursos com mais bytes inventariados nesta página";
$string["charttopcourses_note"] = "Os tamanhos lógicos são somados por curso; arquivos compartilhados podem aparecer no total de mais de um curso.";
$string["coursechartfiles"] = "Arquivos por componente";
$string["coursechartbytes"] = "Bytes por componente";
$string["coursechartnote"] = "Clique na linha de um componente para ver apenas seus arquivos. Os totais consideram todo o inventário salvo do curso.";
$string["coursecomponentfilter"] = "Exibindo somente arquivos do componente:";
$string["courseallcomponents"] = "Todos os componentes";
$string['comparison'] = 'Comparar com mdl_files';
$string['comparison_note'] = 'Arquivos ausentes dos inventários dos cursos não são necessariamente órfãos. Outros contextos do Moodle e plugins sem suporte completo a backup podem utilizá-los. Nenhuma exclusão é realizada.';
$string['completed'] = 'Concluído';
$string['component'] = 'Componente';
$string['course'] = 'Curso';
$string["coursecount"] = "Cursos analisados";
$string['courses'] = 'Cursos';
$string['coursesummary'] = 'Arquivos por curso';
$string['coursesummary_note'] = 'Esta tabela utiliza o inventário salvo de cada curso, sem contabilizar diretórios. Os bytes são tamanhos lógicos e arquivos compartilhados aparecem em cada curso. Não representam bytes físicos exclusivos no filedir.';
$string['created'] = 'Criado';
$string['createdby'] = 'Criado por';
$string['createfailed'] = 'Não foi possível criar o relatório.';
$string['croninfo'] = 'Os cursos são processados sequencialmente pelas tarefas ad-hoc do Moodle. Mantenha o cron funcionando, pois fechar a página não interrompe o processamento.';
$string['deletereport'] = 'Excluir relatório';
$string['deletereportconfirm'] = 'Excluir definitivamente este relatório, o histórico de processamento dos cursos e o inventário de arquivos? Os arquivos reais e os dados dos cursos no Moodle não serão excluídos.';
$string['deletereporttitle'] = 'Excluir relatório #{$a}?';
$string['donecount'] = 'Cursos concluídos';
$string['downloadfile'] = 'Baixar arquivo';
$string["editsettings"] = "Editar configuração";
$string['endtime'] = 'Término';
$string['error'] = 'Erro';
$string['expiredpreview'] = 'O link externo de pré-visualização expirou ou é inválido.';
$string['failed'] = 'Falhou';
$string['failedbackup'] = 'Não foi possível inventariar o curso.';
$string['failedcount'] = 'Cursos com erro';
$string['filearea'] = 'Área de arquivos';
$string['fileid'] = 'ID do arquivo';
$string['filename'] = 'Nome do arquivo';
$string['filenotavailable'] = 'O arquivo não está mais disponível ou não corresponde ao registro salvo no relatório.';
$string['files'] = 'Referências a arquivos';
$string['filesize'] = 'Tamanho do arquivo';
$string['filexray:run'] = 'Gerar e retomar relatórios do File X-Ray';
$string['filexray:view'] = 'Visualizar relatórios do File X-Ray';
$string['finished'] = 'Término';
$string['generate'] = 'Gerar relatório';
$string['id'] = 'ID';
$string['invalidcourse'] = 'O curso não está disponível.';
$string['invalidpreview'] = 'Este tipo de arquivo não admite essa forma de pré-visualização.';
$string['matched'] = 'Registros encontrados nos inventários';
$string['matchedfiles'] = 'Arquivos encontrados neste curso';
$string['maxreports'] = 'Limite de relatórios para manter';
$string['maxreports_desc'] = 'Quantidade máxima de relatórios armazenados, inclusive os não concluídos. Ao gerar um novo relatório quando o limite for atingido, o mais antigo e seu inventário serão excluídos primeiro. Mínimo de 1. Se o mais antigo estiver sendo processado, a criação será bloqueada até terminar o curso atual.';
$string["mcp_banner_badge"] = "INTEGRAÇÃO COM IA";
$string["mcp_banner_benefit_control"] = "Confirmação obrigatória para alterações";
$string["mcp_banner_benefit_discovery"] = "Descoberta automática de ferramentas";
$string["mcp_banner_benefit_security"] = "Permissões do Moodle respeitadas";
$string["mcp_banner_cta"] = "Conhecer o Moodle MCP";
$string["mcp_banner_description"] = "Instale o local_mcp para disponibilizar os recursos do File X-Ray ao ChatGPT, respeitando as permissões do Moodle.";
$string["mcp_banner_dismiss"] = "Não mostrar novamente";
$string["mcp_banner_title"] = "Use o File X-Ray com o ChatGPT";
$string['newreport'] = 'Novo relatório';
$string['next'] = 'Próximo';
$string['no_reports'] = 'Nenhum relatório foi criado.';
$string['notbackup'] = 'O File X-Ray executa o processo nativo de backup de cada curso até identificar e consolidar a lista de arquivos que entrariam no MBZ. Nesse ponto, registra as referências encontradas e interrompe o backup antes de copiar o conteúdo físico dos arquivos e antes de compactar ou criar o arquivo ZIP/MBZ. Assim, é possível analisar os arquivos do curso sem gerar o backup.';
$string['notfound'] = 'Relatório não encontrado.';
$string['nothingpending'] = 'Não existem mais cursos pendentes.';
$string['officeapprove'] = 'Autorizar prévia temporária pelo Google';
$string['officeconsent'] = 'Este documento está protegido na instalação Moodle. O visualizador do Google só consegue abri-lo mediante autorização de acesso externo temporário. Ao autorizar, um link assinado será compartilhado com o Google e qualquer pessoa que possuir esse link poderá consultar o arquivo por até 10 minutos. O Google poderá processar o documento conforme suas próprias regras de privacidade. Não autorize arquivos confidenciais sem aprovação da instituição.';
$string['officehttpsrequired'] = 'A prévia pelo Google não está disponível porque este Moodle não utiliza HTTPS. Baixe o arquivo original.';
$string['officeopenexternal'] = 'Abrir o visualizador do Google em outra aba';
$string['officeviewernote'] = 'O visualizador do Google tentará carregar o arquivo por um link temporário que expira em até 10 minutos. O servidor Moodle precisa estar acessível publicamente por HTTPS. Se a visualização não funcionar, utilize o download do arquivo original.';
$string['onlyread'] = 'Inventário somente de leitura. A exclusão de arquivos não é disponibilizada.';
$string['partial'] = 'Concluído com erros';
$string['pending'] = 'Pendente';
$string['pluginname'] = 'File X-Ray';
$string['preview'] = 'Pré-visualizar';
$string['previewnotconfigured'] = 'A chave de assinatura da prévia externa não foi instalada. Execute a atualização do plugin Moodle.';
$string['previous'] = 'Anterior';
$string['previousreports'] = 'Relatórios recentes';
$string['privacy:metadata'] = 'O File X-Ray armazena os IDs dos administradores nos relatórios e os metadados dos arquivos já registrados pelo Moodle.';
$string['progress'] = 'Progresso';
$string['queued'] = 'Na fila';
$string['readmore'] = 'Detalhes dos arquivos';
$string['recordcount'] = 'Registros';
$string['report'] = 'Relatório #{$a}';
$string['reportbusy'] = 'O File X-Ray está analisando um curso deste relatório neste momento e não pode alterá-lo até essa etapa terminar. Aguarde a conclusão do curso atual e tente novamente. Nenhum dado do relatório foi excluído.';
$string['reportcreated'] = 'Relatório criado.';
$string['reportnotready'] = 'O inventário ainda não foi concluído. A comparação é provisória até que todos os cursos sejam processados com sucesso.';
$string['reports'] = 'Relatórios anteriores';
$string['reportsbusy'] = 'Outra operação com relatórios está em andamento. Aguarde a conclusão e tente novamente.';
$string['residual_note'] = 'Aqui aparecem apenas arquivos ausentes dos backups virtuais dos cursos que não estejam nas categorias classificadas acima. Eles ainda precisam ser investigados, pois não aparecer em um backup não comprova que sejam órfãos.';
$string['resume'] = 'Retomar e repetir cursos com erro';
$string['resumeok'] = 'O processamento foi solicitado.';
$string['running'] = 'Executando';
$string['skipuserdata'] = 'Ignorar dados dos alunos no inventário (mais rápido)';
$string['skipuserdata_desc'] = 'Quando marcada, esta opção desativa \'Incluir usuários inscritos\' no backup nativo usado pelo File X-Ray para analisar cada curso. Em cursos com muitos alunos, isso pode reduzir bastante o tempo de processamento porque o backup deixa de incluir inscrições, entregas de tarefas, postagens em fóruns e outros dados de participação. <strong>Atenção:</strong> arquivos associados apenas às atividades dos alunos podem ficar fora do inventário e aparecer como \'não encontrados\' na comparação, mesmo estando em uso. Nenhum aluno ou arquivo será excluído. Quando desmarcada (padrão), a análise segue as configurações normais de backup do Moodle. A mudança vale para cursos processados depois da alteração, inclusive os ainda pendentes em relatórios existentes; evite alterá-la durante um relatório em andamento. Se o Moodle bloquear a inclusão de usuários como obrigatória, o processamento do curso falhará em vez de ignorar silenciosamente a escolha.';
$string["skipuserdatawarning"] = "A opção de ignorar dados dos alunos está ativada. Os inventários processados com ela não incluíram os dados de usuários inscritos, portanto arquivos das atividades dos alunos podem aparecer como ausentes. Alterar a configuração não refaz os inventários concluídos.";
$string['staleinfo'] = 'Use Retomar para tentar novamente cursos interrompidos há mais de duas horas e agendar o trabalho pendente.';
$string['started'] = 'Início';
$string['starttime'] = 'Início';
$string['status'] = 'Situação';
$string['totaldatabase'] = 'Registros de arquivos no Moodle';
$string['unmatched'] = 'Não encontrado nos inventários dos cursos';
$string['unmatchedcount'] = 'Registros fora dos inventários e das categorias já classificadas';
$string['unmatchedview'] = 'Ver arquivos não encontrados nos inventários dos cursos';
$string['userdatalocked'] = 'O Moodle obriga a inclusão de usuários nas configurações de backup. Desative o bloqueio nativo antes de usar o modo rápido do File X-Ray, ou desmarque a opção de ignorar dados dos alunos.';
$string['view'] = 'Visualizar';
