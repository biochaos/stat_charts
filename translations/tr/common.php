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
	'STATCHARTS_TITLE'                   => 'İstatistikler',
	'STATCHARTS_LOGIN'                   => 'İstatistikleri görmek için giriş yapmalısınız.',
	'STATCHARTS_NOT_BUILT'               => 'İstatistikler henüz hesaplanmadı.',
	'STATCHARTS_LINK_ELIDED'             => '[bağlantı]',
	'STATCHARTS_NOSCRIPT'                => 'İstatistikler için JavaScript gereklidir.',
	'STATCHARTS_JS_TAB_GENERAL'          => 'Genel',
	'STATCHARTS_JS_TAB_FORUM'            => 'Forum',
	'STATCHARTS_JS_TAB_TOPIC'            => 'Konu',
	'STATCHARTS_JS_TAB_USER'             => 'Üye',
	'STATCHARTS_JS_AS_OF'                => 'Veriler: {time} itibarıyla',
	'STATCHARTS_JS_NOT_BUILT'            => 'İstatistikler henüz hesaplanmadı. Bir sonraki planlı hesaplamadan sonra görünecekler.',
	'STATCHARTS_JS_LOADING'              => 'Yükleniyor…',
	'STATCHARTS_JS_ERROR'                => 'İstatistikler yüklenemedi.',
	'STATCHARTS_JS_NO_ACTIVITY'          => 'Bu dönemde etkinlik yok',
	'STATCHARTS_JS_NO_ROWS'              => 'Gösterilecek bir şey yok',
	'STATCHARTS_JS_RANGE_ALL'            => 'Tüm zamanlar',
	'STATCHARTS_JS_RANGE_YEAR'           => 'Son bir yıl',
	'STATCHARTS_JS_RANGE_MONTH'          => 'Son bir ay',
	'STATCHARTS_JS_RANGE_WEEK'           => 'Son bir hafta',
	'STATCHARTS_JS_RANGE_TODAY'          => 'Bugün',
	'STATCHARTS_JS_RANGE_CUSTOM'         => 'Özel aralık',
	'STATCHARTS_JS_RANGE_FROM'           => 'Başlangıç',
	'STATCHARTS_JS_RANGE_TO'             => 'Bitiş',
	'STATCHARTS_JS_TODAY_CAPTION'        => 'Gece yarısından beri, {time} itibarıyla',
	'STATCHARTS_JS_PAGE_RANGE'           => 'Tüm grafik ve tabloların aralığı',
	'STATCHARTS_JS_PAGE_RANGE_HINT'      => 'Sayfadaki tüm grafik ve tabloları ayarlar; her biri daha sonra ayrıca değiştirilebilir.',
	'STATCHARTS_JS_RANGE_OVERRIDDEN'     => 'Sayfanın aralığından farklı',
	'STATCHARTS_JS_MODE_AVG'             => '7 günlük ortalama',
	'STATCHARTS_JS_MODE_DAY'             => 'Günlük',
	'STATCHARTS_JS_MODE_CUM'             => 'Kümülatif',
	'STATCHARTS_JS_SHOW_TABLE'           => 'Tablo',
	'STATCHARTS_JS_SHOW_CHART'           => 'Grafik',
	'STATCHARTS_JS_TOTAL'                => 'Toplam',
	'STATCHARTS_JS_POSTS_OVER_TIME'      => 'Zaman içinde mesajlar',
	'STATCHARTS_JS_POSTS_BY_FORUM'       => 'Foruma göre mesajlar',
	'STATCHARTS_JS_TOPICS_BY_FORUM'      => 'Foruma göre yeni konular',
	'STATCHARTS_JS_LIKES_BY_FORUM'       => 'Foruma göre teşekkürler',
	'STATCHARTS_JS_LIKES_EXPLAIN'        => 'Dönem içinde verilen teşekkürler, eski mesajlara verilenler dahil.',
	'STATCHARTS_JS_VIEWS_BY_FORUM'       => 'Foruma göre görüntülenmeler',
	'STATCHARTS_JS_VIEWS_BY_FORUM_RANGE' => 'Foruma göre görüntülenmeler, açılan konular: {range}',
	'STATCHARTS_JS_VIEWS_NOTE'           => 'phpBB görüntülenmeleri bir konunun tüm ömrü boyunca sayar, bu yüzden tarihlendirilemezler. Bir aralık, o aralıkta açılan konuları seçer.',
	'STATCHARTS_JS_USERS'                => 'Üyeler',
	'STATCHARTS_JS_USERS_REGISTERED'     => 'Kayıtlı',
	'STATCHARTS_JS_USERS_POSTED'         => 'Kayıtlı ve mesaj yazmış',
	'STATCHARTS_JS_ACTIVE_7D'            => 'Aktif üyeler (7 gün)',
	'STATCHARTS_JS_ACTIVE_7D_EXPLAIN'    => 'Her gün için, o güne kadarki yedi günde mesaj yazan üyeler.',
	'STATCHARTS_JS_MOST_VIEWED'          => 'En çok görüntülenen konular',
	'STATCHARTS_JS_MOST_VIEWED_RANGE'    => 'Açılan konular arasında en çok görüntülenenler: {range}',
	'STATCHARTS_JS_MOST_ACTIVE'          => 'En aktif konular',
	'STATCHARTS_JS_MOST_LIKED'           => 'En çok teşekkür alan mesajlar',
	'STATCHARTS_JS_COL_TOPIC'            => 'Konu',
	'STATCHARTS_JS_COL_TOPICS'           => 'Konular',
	'STATCHARTS_JS_COL_FORUM'            => 'Forum',
	'STATCHARTS_JS_COL_USER'             => 'Üye',
	'STATCHARTS_JS_COL_POST'             => 'Mesaj',
	'STATCHARTS_JS_COL_VIEWS'            => 'Görüntülenme',
	'STATCHARTS_JS_COL_POSTS'            => 'Mesajlar',
	'STATCHARTS_JS_COL_LIKES'            => 'Teşekkürler',
	'STATCHARTS_JS_COL_SHARE'            => 'Pay',
	'STATCHARTS_JS_COL_MONTH'            => 'Ay',
	'STATCHARTS_JS_FORUM_SELECT'         => 'Forum',
	'STATCHARTS_JS_FORUM_NOT_IN_STATS'   => 'Bu forum istatistiklere dahil değil. Yukarıdan başka birini seçin.',
	'STATCHARTS_JS_FORUM_HEADER'         => '{topics} konuda {posts} mesaj',
	'STATCHARTS_JS_USER_SEARCH'          => 'Üye ara',
	'STATCHARTS_JS_USER_PICK'            => 'İstatistiklerini görmek için bir üye seçin.',
	'STATCHARTS_JS_USER_NOT_FOUND'       => 'Böyle bir üye yok.',
	'STATCHARTS_JS_USER_HEADER'          => '{posts} mesaj, {likes} teşekkür aldı',
	'STATCHARTS_JS_USER_TOP_TOPICS'      => 'En çok mesaj yazdığı konular',
	'STATCHARTS_JS_NO_PROFILES'          => 'Üyelerin istatistiklerini görme izniniz yok.',
	'STATCHARTS_JS_TOPIC_SEARCH'         => 'Konu ara',
	'STATCHARTS_JS_TOPIC_ALL_FORUMS'     => 'Tüm forumlar',
	'STATCHARTS_JS_SORT_TITLE'           => 'Başlığa göre',
	'STATCHARTS_JS_SORT_REPLIES'         => 'Cevaplara göre',
	'STATCHARTS_JS_TOPIC_NOT_FOUND'      => 'Böyle bir konu yok.',
	'STATCHARTS_JS_TOPIC_HEADER'         => '{poster} tarafından {date} tarihinde {forum} forumunda açıldı',
	'STATCHARTS_JS_TOPIC_ACTIVITY'       => 'Konu etkinliği',
	'STATCHARTS_JS_TOPIC_ACTIVITY_ZOOM'  => 'Konu etkinliği, yakınlaştırılmış',
	'STATCHARTS_JS_TOPIC_TOP_USERS'      => 'Konudaki en aktif üyeler',
	'STATCHARTS_JS_FORUM_TOP_USERS'      => 'Forumdaki en aktif üyeler',
	'STATCHARTS_JS_FORUM_TOP_LIKED'      => 'Forumda en çok teşekkür alan üyeler',
	'STATCHARTS_JS_GO'                   => 'Git',
	'STATCHARTS_JS_OTHER'                => 'Diğer',
	'STATCHARTS_JS_HIDDEN'               => 'Göremediğiniz forumlar',
	'STATCHARTS_JS_OPEN_ON_FORUM'        => 'Forumda aç',
	'STATCHARTS_JS_SESSION_EXPIRED'      => 'Oturumunuz sona erdi.',
	'STATCHARTS_JS_RELOAD'               => 'Sayfayı yeniden yükle',
]);
