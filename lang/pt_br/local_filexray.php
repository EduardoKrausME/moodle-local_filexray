<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Brazilian Portuguese language strings.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'File X-Ray';
$string['privacy:metadata'] = 'O File X-Ray armazena os IDs dos administradores nos relatórios e os metadados dos arquivos já registrados pelo Moodle.';
$string['generate'] = 'Gerar relatório';
$string['newreport'] = 'Novo relatório';
$string['reports'] = 'Relatórios anteriores';
$string['courses'] = 'Cursos';
$string['files'] = 'Referências a arquivos';
$string['progress'] = 'Progresso';
$string['pending'] = 'Pendente';
$string['queued'] = 'Na fila';
$string['running'] = 'Executando';
$string['completed'] = 'Concluído';
$string['partial'] = 'Concluído com erros';
$string['failed'] = 'Falhou';
$string['report'] = 'Relatório #{$a}';
$string['view'] = 'Visualizar';
$string['created'] = 'Criado';
$string['no_reports'] = 'Nenhum relatório foi criado.';
$string['notbackup'] = 'Esta análise não cria MBZ e não copia o conteúdo dos arquivos.';
$string['croninfo'] = 'Os cursos são processados sequencialmente pelas tarefas ad-hoc do Moodle. Mantenha o cron funcionando, pois fechar a página não interrompe o processamento.';
$string['course'] = 'Curso';
$string['status'] = 'Situação';
$string['error'] = 'Erro';
$string['started'] = 'Início';
$string['finished'] = 'Término';
$string['next'] = 'Próximo';
$string['previous'] = 'Anterior';
$string['resume'] = 'Retomar cursos pendentes';
$string['comparison'] = 'Comparar com mdl_files';
$string['comparison_note'] = 'Arquivos ausentes dos inventários dos cursos não são necessariamente órfãos. Outros contextos do Moodle e plugins sem suporte completo a backup podem utilizá-los. Nenhuma exclusão é realizada.';
$string['unmatched'] = 'Não encontrado nos inventários dos cursos';
$string['fileid'] = 'ID do arquivo';
$string['filename'] = 'Nome do arquivo';
$string['component'] = 'Componente';
$string['filearea'] = 'Área de arquivos';
$string['filesize'] = 'Tamanho do arquivo';
$string['totaldatabase'] = 'Registros de arquivos no Moodle';
$string['matched'] = 'Registros encontrados nos inventários';
$string['unmatchedcount'] = 'Registros fora dos inventários dos cursos';
$string['reportnotready'] = 'O inventário ainda não foi concluído. A comparação é provisória até que todos os cursos sejam processados com sucesso.';
$string['createdby'] = 'Criado por';
$string['id'] = 'ID';
$string['onlyread'] = 'Inventário somente de leitura. A exclusão de arquivos não é disponibilizada.';
$string['notfound'] = 'Relatório não encontrado.';
$string['staleinfo'] = 'Use Retomar para tentar novamente cursos interrompidos há mais de duas horas e agendar o trabalho pendente.';
$string['createfailed'] = 'Não foi possível criar o relatório.';
$string['resumeok'] = 'O processamento foi solicitado.';
$string['invalidcourse'] = 'O curso não está disponível.';
$string['failedbackup'] = 'Não foi possível inventariar o curso.';
$string['nothingpending'] = 'Não existem mais cursos pendentes.';
$string['readmore'] = 'Detalhes dos arquivos';
$string['matchedfiles'] = 'Arquivos encontrados neste curso';
$string['previousreports'] = 'Relatórios recentes';
$string['recordcount'] = 'Registros';
$string['starttime'] = 'Início';
$string['endtime'] = 'Término';
$string['reportcreated'] = 'Relatório criado.';
$string['coursecount'] = 'Cursos para analisar';
$string['donecount'] = 'Cursos concluídos';
$string['failedcount'] = 'Cursos com erro';
$string['administration'] = 'Administração do File X-Ray';