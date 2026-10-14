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
$string['comparison'] = 'Comparar com mdl_files';
$string['comparison_note'] = 'Arquivos ausentes dos inventários dos cursos não são necessariamente órfãos. Outros contextos do Moodle e plugins sem suporte completo a backup podem utilizá-los. Nenhuma exclusão é realizada.';
$string['completed'] = 'Concluído';
$string['component'] = 'Componente';
$string['course'] = 'Curso';
$string['coursecount'] = 'Cursos para analisar';
$string['courses'] = 'Cursos';
$string['created'] = 'Criado';
$string['createdby'] = 'Criado por';
$string['createfailed'] = 'Não foi possível criar o relatório.';
$string['croninfo'] = 'Os cursos são processados sequencialmente pelas tarefas ad-hoc do Moodle. Mantenha o cron funcionando, pois fechar a página não interrompe o processamento.';
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
$string['finished'] = 'Término';
$string['generate'] = 'Gerar relatório';
$string['id'] = 'ID';
$string['invalidcourse'] = 'O curso não está disponível.';
$string['matched'] = 'Registros encontrados nos inventários';
$string['matchedfiles'] = 'Arquivos encontrados neste curso';
$string['newreport'] = 'Novo relatório';
$string['next'] = 'Próximo';
$string['no_reports'] = 'Nenhum relatório foi criado.';
$string['notbackup'] = 'Esta análise não cria MBZ e não copia o conteúdo dos arquivos.';
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
$string['reportcreated'] = 'Relatório criado.';
$string['reportnotready'] = 'O inventário ainda não foi concluído. A comparação é provisória até que todos os cursos sejam processados com sucesso.';
$string['reports'] = 'Relatórios anteriores';
$string['resume'] = 'Retomar cursos pendentes';
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
