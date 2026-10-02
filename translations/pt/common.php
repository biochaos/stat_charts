<?php
/**
 * Stat Charts extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

// STATCHARTS_JS_* go to the page script; {name} placeholders are filled there.
// Translation: a first draft, not yet reviewed by a native speaker.
$lang = array_merge($lang, [
	'STATCHARTS_TITLE'                   => 'Estatísticas',
	'STATCHARTS_LOGIN'                   => 'Tem de iniciar sessão para ver as estatísticas.',
	'STATCHARTS_NOT_BUILT'               => 'As estatísticas ainda não foram calculadas.',
	'STATCHARTS_LINK_ELIDED'             => '[ligação]',
	'STATCHARTS_NOSCRIPT'                => 'As estatísticas precisam de JavaScript.',
	'STATCHARTS_JS_TAB_GENERAL'          => 'Geral',
	'STATCHARTS_JS_TAB_FORUM'            => 'Fórum',
	'STATCHARTS_JS_TAB_TOPIC'            => 'Tópico',
	'STATCHARTS_JS_TAB_USER'             => 'Membro',
	'STATCHARTS_JS_AS_OF'                => 'Dados de {time}',
	'STATCHARTS_JS_NOT_BUILT'            => 'As estatísticas ainda não foram calculadas. Aparecerão após o próximo recálculo agendado.',
	'STATCHARTS_JS_LOADING'              => 'A carregar…',
	'STATCHARTS_JS_ERROR'                => 'Não foi possível carregar as estatísticas.',
	'STATCHARTS_JS_NO_ACTIVITY'          => 'Sem atividade neste período',
	'STATCHARTS_JS_NO_ROWS'              => 'Nada a mostrar',
	'STATCHARTS_JS_RANGE_ALL'            => 'Desde sempre',
	'STATCHARTS_JS_RANGE_YEAR'           => 'Último ano',
	'STATCHARTS_JS_RANGE_MONTH'          => 'Último mês',
	'STATCHARTS_JS_RANGE_WEEK'           => 'Última semana',
	'STATCHARTS_JS_RANGE_TODAY'          => 'Hoje',
	'STATCHARTS_JS_RANGE_CUSTOM'         => 'Período personalizado',
	'STATCHARTS_JS_RANGE_FROM'           => 'De',
	'STATCHARTS_JS_RANGE_TO'             => 'Até',
	'STATCHARTS_JS_TODAY_CAPTION'        => 'Desde a meia-noite, até {time}',
	'STATCHARTS_JS_PAGE_RANGE'           => 'Período de todos os gráficos e tabelas',
	'STATCHARTS_JS_PAGE_RANGE_HINT'      => 'Aplica-se a todos os gráficos e tabelas da página; depois, cada um pode ser alterado individualmente.',
	'STATCHARTS_JS_RANGE_OVERRIDDEN'     => 'Diferente do período da página',
	'STATCHARTS_JS_MODE_AVG'             => 'Média de 7 dias',
	'STATCHARTS_JS_MODE_DAY'             => 'Por dia',
	'STATCHARTS_JS_MODE_CUM'             => 'Acumulado',
	'STATCHARTS_JS_SHOW_TABLE'           => 'Tabela',
	'STATCHARTS_JS_SHOW_CHART'           => 'Gráfico',
	'STATCHARTS_JS_TOTAL'                => 'Total',
	'STATCHARTS_JS_POSTS_OVER_TIME'      => 'Mensagens ao longo do tempo',
	'STATCHARTS_JS_POSTS_BY_FORUM'       => 'Mensagens por fórum',
	'STATCHARTS_JS_TOPICS_BY_FORUM'      => 'Novos tópicos por fórum',
	'STATCHARTS_JS_LIKES_BY_FORUM'       => 'Agradecimentos por fórum',
	'STATCHARTS_JS_LIKES_EXPLAIN'        => 'Agradecimentos dados no período, incluindo os de mensagens mais antigas.',
	'STATCHARTS_JS_VIEWS_BY_FORUM'       => 'Visualizações por fórum',
	'STATCHARTS_JS_VIEWS_BY_FORUM_RANGE' => 'Visualizações por fórum, dos tópicos criados: {range}',
	'STATCHARTS_JS_VIEWS_NOTE'           => 'O phpBB conta as visualizações ao longo de toda a vida de um tópico, por isso não podem ser datadas. Um período seleciona os tópicos criados nele.',
	'STATCHARTS_JS_USERS'                => 'Membros',
	'STATCHARTS_JS_USERS_REGISTERED'     => 'Registados',
	'STATCHARTS_JS_USERS_POSTED'         => 'Registados que escreveram',
	'STATCHARTS_JS_ACTIVE_7D'            => 'Membros ativos (7 dias)',
	'STATCHARTS_JS_ACTIVE_7D_EXPLAIN'    => 'Para cada dia, os membros que escreveram nos sete dias até esse dia.',
	'STATCHARTS_JS_MOST_VIEWED'          => 'Tópicos mais vistos',
	'STATCHARTS_JS_MOST_VIEWED_RANGE'    => 'Os mais vistos entre os tópicos criados: {range}',
	'STATCHARTS_JS_MOST_ACTIVE'          => 'Tópicos mais ativos',
	'STATCHARTS_JS_MOST_LIKED'           => 'Mensagens mais agradecidas',
	'STATCHARTS_JS_COL_TOPIC'            => 'Tópico',
	'STATCHARTS_JS_COL_TOPICS'           => 'Tópicos',
	'STATCHARTS_JS_COL_FORUM'            => 'Fórum',
	'STATCHARTS_JS_COL_USER'             => 'Membro',
	'STATCHARTS_JS_COL_POST'             => 'Mensagem',
	'STATCHARTS_JS_COL_VIEWS'            => 'Visualizações',
	'STATCHARTS_JS_COL_POSTS'            => 'Mensagens',
	'STATCHARTS_JS_COL_LIKES'            => 'Agradecimentos',
	'STATCHARTS_JS_COL_SHARE'            => 'Proporção',
	'STATCHARTS_JS_COL_MONTH'            => 'Mês',
	'STATCHARTS_JS_FORUM_SELECT'         => 'Fórum',
	'STATCHARTS_JS_FORUM_NOT_IN_STATS'   => 'Este fórum não está incluído nas estatísticas. Escolha outro acima.',
	'STATCHARTS_JS_FORUM_HEADER'         => '{posts} mensagens em {topics} tópicos',
	'STATCHARTS_JS_USER_SEARCH'          => 'Procurar membros',
	'STATCHARTS_JS_USER_PICK'            => 'Escolha um membro para ver as suas estatísticas.',
	'STATCHARTS_JS_USER_NOT_FOUND'       => 'Membro inexistente.',
	'STATCHARTS_JS_USER_HEADER'          => '{posts} mensagens, {likes} agradecimentos recebidos',
	'STATCHARTS_JS_USER_TOP_TOPICS'      => 'Tópicos com mais mensagens',
	'STATCHARTS_JS_NO_PROFILES'          => 'Não tem permissão para ver as estatísticas dos membros.',
	'STATCHARTS_JS_TOPIC_SEARCH'         => 'Procurar tópicos',
	'STATCHARTS_JS_TOPIC_ALL_FORUMS'     => 'Todos os fóruns',
	'STATCHARTS_JS_SORT_TITLE'           => 'Por título',
	'STATCHARTS_JS_SORT_REPLIES'         => 'Por respostas',
	'STATCHARTS_JS_TOPIC_NOT_FOUND'      => 'Tópico inexistente.',
	'STATCHARTS_JS_TOPIC_HEADER'         => 'Criado por {poster} em {date} em {forum}',
	'STATCHARTS_JS_TOPIC_ACTIVITY'       => 'Atividade do tópico',
	'STATCHARTS_JS_TOPIC_ACTIVITY_ZOOM'  => 'Atividade do tópico, ampliada',
	'STATCHARTS_JS_TOPIC_TOP_USERS'      => 'Membros mais ativos no tópico',
	'STATCHARTS_JS_FORUM_TOP_USERS'      => 'Membros mais ativos no fórum',
	'STATCHARTS_JS_FORUM_TOP_LIKED'      => 'Membros mais agradecidos no fórum',
	'STATCHARTS_JS_GO'                   => 'Ir',
	'STATCHARTS_JS_OTHER'                => 'Outros',
	'STATCHARTS_JS_HIDDEN'               => 'Fóruns que não pode ver',
	'STATCHARTS_JS_OPEN_ON_FORUM'        => 'Abrir no fórum',
	'STATCHARTS_JS_SESSION_EXPIRED'      => 'A sua sessão expirou.',
	'STATCHARTS_JS_RELOAD'               => 'Recarregar a página',
]);
