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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

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
$string['areadraft'] = 'Rascunhos dos usuários';
$string['areaname'] = 'Categoria';
$string['areaoutsidebytes'] = 'Bytes fora do inventário';
$string['areaoutsidefiles'] = 'Fora do inventário';
$string['areaprivate'] = 'Arquivos privados dos usuários';
$string['areaprofile'] = 'Arquivos de perfil dos usuários';
$string['areasummary'] = 'Outras áreas de arquivos em mdl_files';
$string['areasummary_note'] = 'Os registros são agrupados por component e filearea. O total de bytes soma os registros atuais de mdl_files naquela área; user/draft corresponde à soma de filesize dos rascunhos. As colunas fora do inventário mostram apenas IDs ausentes do backup virtual deste relatório. A ausência no backup não significa que o arquivo esteja sem uso ou não pertença a um curso.';
$string['areatotalbytes'] = 'Total de bytes';
$string['areatotalfiles'] = 'Total de registros';
$string['bytes'] = 'Bytes';
$string['comparison'] = 'Comparar com mdl_files';
$string['comparison_note'] = 'Arquivos ausentes dos inventários dos cursos não são necessariamente órfãos. Outros contextos do Moodle e plugins sem suporte completo a backup podem utilizá-los. Nenhuma exclusão é realizada.';
$string['completed'] = 'Concluído';
$string['component'] = 'Componente';
$string['course'] = 'Curso';
$string['coursecount'] = 'Cursos para analisar';
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
$string['endtime'] = 'Término';
$string['error'] = 'Erro';
$string['failed'] = 'Falhou';
$string['failedbackup'] = 'Não foi possível inventariar o curso.';
$string['failedcount'] = 'Cursos com erro';
$string['filearea'] = 'Área de arquivos';
$string['fileid'] = 'ID do arquivo';
$string['filename'] = 'Nome do arquivo';
$string['files'] = 'Referências a arquivos';
$string['filesize'] = 'Tamanho do arquivo';
$string['filexray:run'] = 'Gerar e retomar relatórios do File X-Ray';
$string['filexray:view'] = 'Visualizar relatórios do File X-Ray';
$string['finished'] = 'Término';
$string['generate'] = 'Gerar relatório';
$string['id'] = 'ID';
$string['invalidcourse'] = 'O curso não está disponível.';
$string['matched'] = 'Registros encontrados nos inventários';
$string['matchedfiles'] = 'Arquivos encontrados neste curso';
$string['maxreports'] = 'Limite de relatórios para manter';
$string['maxreports_desc'] = 'Quantidade máxima de relatórios armazenados, inclusive os não concluídos. Ao gerar um novo relatório quando o limite for atingido, o mais antigo e seu inventário serão excluídos primeiro. Mínimo de 1. Se o mais antigo estiver sendo processado, a criação será bloqueada até terminar o curso atual.';
$string['newreport'] = 'Novo relatório';
$string['next'] = 'Próximo';
$string['no_reports'] = 'Nenhum relatório foi criado.';
$string['notbackup'] = 'O File X-Ray executa o processo nativo de backup de cada curso até identificar e consolidar a lista de arquivos que entrariam no MBZ. Nesse ponto, registra as referências encontradas e interrompe o backup antes de copiar o conteúdo físico dos arquivos e antes de compactar ou criar o arquivo ZIP/MBZ. Assim, é possível analisar os arquivos do curso sem gerar o backup.';
$string['notfound'] = 'Relatório não encontrado.';
$string['nothingpending'] = 'Não existem mais cursos pendentes.';
$string['onlyread'] = 'Inventário somente de leitura. A exclusão de arquivos não é disponibilizada.';
$string['partial'] = 'Concluído com erros';
$string['pending'] = 'Pendente';
$string['pluginname'] = 'File X-Ray';
$string['previous'] = 'Anterior';
$string['previousreports'] = 'Relatórios recentes';
$string['privacy:metadata'] = 'O File X-Ray armazena os IDs dos administradores nos relatórios e os metadados dos arquivos já registrados pelo Moodle.';
$string['progress'] = 'Progresso';
$string['queued'] = 'Na fila';
$string['readmore'] = 'Detalhes dos arquivos';
$string['recordcount'] = 'Registros';
$string['report'] = 'Relatório #{$a}';
$string['reportbusy'] = 'Este relatório está em processamento. Tente novamente após a conclusão do curso atual.';
$string['reportcreated'] = 'Relatório criado.';
$string['reportnotready'] = 'O inventário ainda não foi concluído. A comparação é provisória até que todos os cursos sejam processados com sucesso.';
$string['reports'] = 'Relatórios anteriores';
$string['reportsbusy'] = 'Outra operação de relatório está em andamento. Tente novamente.';
$string['resume'] = 'Retomar e repetir cursos com erro';
$string['resumeok'] = 'O processamento foi solicitado.';
$string['running'] = 'Executando';
$string['staleinfo'] = 'Use Retomar para tentar novamente cursos interrompidos há mais de duas horas e agendar o trabalho pendente.';
$string['started'] = 'Início';
$string['starttime'] = 'Início';
$string['status'] = 'Situação';
$string['totaldatabase'] = 'Registros de arquivos no Moodle';
$string['unmatched'] = 'Não encontrado nos inventários dos cursos';
$string['unmatchedcount'] = 'Registros fora dos inventários dos cursos';
$string['view'] = 'Visualizar';
