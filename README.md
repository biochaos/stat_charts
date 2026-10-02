# Stat Charts

Statistics for phpBB boards: posts, topics, thanks and members over time, by forum,
member and topic, on a page of its own at `app.php/stats`.

- **Four tabs.** The whole board, one forum, one topic, one member. Each has time
  series and top-10 tables linking into the board.
- **Time ranges** for every chart and table: all time, the past year, month or
  week, today, or custom dates. One page-wide range sets them all, each card can
  then be changed on its own, and the address keeps the ranges so a view can be
  shared.
- **Per day, a 7-day average, or cumulative**, and a table view of every chart.
- **Nothing is computed on page view.** A scheduled task rolls the forum up into
  statistics tables, so the page stays fast on large boards.
- **Permissions respected.** A forum a viewer cannot read is never named: its
  share appears only as "Forums you cannot see".
- **Colours that stay put.** Every forum keeps its colour wherever it appears.
  The built-in palettes are checked for colour-blind readers, and each style can
  have its own palette.
- **Right-to-left** languages supported. English and Hebrew included; draft
  translations of the statistics page into 17 more languages are available
  separately (see *Translations* below).
- **Thanks**, when the *Thanks for posts* extension (`naguissa/thanksforposts`) is
  installed. Without it, the thanks charts are simply not shown. The English
  strings say "thanks", as that extension does. A board that calls them likes
  can change the wording in `language/en/common.php` (the Hebrew already says
  לייקים).

## Requirements

- phpBB 3.3
- PHP 7.2 or newer (tested on 7.2, 8.3 and 8.5)
- MySQL, MariaDB, PostgreSQL or SQLite (tested on MySQL 8.0, MariaDB 10.11,
  PostgreSQL 16 and SQLite 3). MS SQL Server and Oracle have not been tested, so the
  extension will not enable on them. It says so, rather than risk wrong numbers.

## Install

1. Download the release zip (from the phpBB extensions database, or the
   [releases](https://github.com/biochaos/stat_charts/releases) on GitHub) and
   unpack it into `ext/`, so the files land in `ext/biochaos/statcharts`.
2. In the ACP: *Customise → Manage extensions → Stat Charts → Enable*.
3. Set it up in the ACP under *Extensions → Statistics → Settings*. The page
   lists the steps in order.
4. Build the statistics with *Build now* on the settings page. Otherwise the
   scheduled task builds them within the hour. On a large board the first build
   takes a few minutes (half a million posts: about 45 seconds from the command
   line).

The statistics page is linked from the Quick links menu, from each forum and
topic page (below the Information panel), and from members' profiles.

## Translations

The extension ships in English and Hebrew. The repository's `translations`
folder has **draft** translations of what members see (the statistics page, its
links, and the permission's name) into:

- Catalan (`ca`), Croatian (`hr`), Czech (`cs`), Dutch (`nl`), French (`fr`),
  Italian (`it`), Polish (`pl`), Russian (`ru`), Slovak (`sk`), Turkish (`tr`) and
  Ukrainian (`uk`)
- German (`de` and, addressing the reader as Sie, `de_x_sie`), Portuguese
  (`pt` and Brazilian `pt_br`) and Spanish (`es` and, addressing the reader as tú,
  `es_x_tu`)

Each uses the word the Thanks for posts extension uses in that language. The
admin pages stay in English.

**They are first drafts, not yet reviewed by native speakers,** which is why they
are not part of the release. Check the wording before using one on your board.

To install one, copy its folder into the extension's `language` folder, e.g.
`translations/de` to `ext/biochaos/statcharts/language/de`, then purge the cache
(*ACP → General → Purge the cache*). phpBB uses it for members whose board
language is German, and English wherever a file is missing.

**Found a mistake, or can review a language?** Please report it: [open an issue](https://github.com/biochaos/stat_charts/issues)
or post in the extension's support topic on phpBB.com.

## Permissions

*Can view the forum statistics* (`u_statcharts_view`) is granted to the
Registered users group on install. Grant it to Guests to make the statistics
public. Guests still see only the forums they may read. The member tab also
needs *Can view profiles*.

## Settings

The settings page, in the order you set it up:

1. **How forums are grouped.** Every forum separately, sub-forums counted in
   their top-level forum, or one group per category.
2. **Palettes.** One per style. You can duplicate a built-in palette to match
   your theme; the page checks it and warns about colours that are hard to tell
   apart.
3. **What the statistics include.**
   - Forums to leave out entirely.
   - Whether posts without an account (guests, and deleted members whose posts
     were kept) count as active members.

   These two take effect at the next rebuild.
4. **Order and colours.** The order of the groups everywhere, and which colour
   each gets.

Only approved posts and topics are counted. Bots and the anonymous account are
not members.

## Keeping the statistics fresh

The statistics are rebuilt every hour by phpBB's scheduled tasks, which run when
someone visits the board. On a quiet board that can be much later than an hour.
The settings page warns when the statistics are overdue. For a dependable
schedule, use either option below.

**phpBB's system cron.** Set *ACP → General → Server settings → Run periodic
tasks from system cron* to *Yes*, and run phpBB's tasks every few minutes:

```cron
*/5 * * * * www-data /usr/bin/php /path/to/phpBB/bin/phpbbcli.php cron:run
```

**Or rebuild on a timer** with the extension's own command. It builds to
completion, and if a build is already running it stops with a message and exit
code 1:

```cron
15 * * * * www-data /usr/bin/php /path/to/phpBB/bin/phpbbcli.php statcharts:rebuild --quiet
```

The same with systemd, in `/etc/systemd/system/statcharts-rebuild.service`:

```ini
[Unit]
Description=Rebuild the phpBB Stat Charts statistics

[Service]
Type=oneshot
User=www-data
ExecStart=/usr/bin/php /path/to/phpBB/bin/phpbbcli.php statcharts:rebuild --quiet
```

and `/etc/systemd/system/statcharts-rebuild.timer`:

```ini
[Unit]
Description=Rebuild the phpBB Stat Charts statistics hourly

[Timer]
OnCalendar=hourly
RandomizedDelaySec=300
Persistent=true

[Install]
WantedBy=timers.target
```

Then `systemctl enable --now statcharts-rebuild.timer`. Run the command as the
web server's user, so the cache files it writes stay writable by phpBB.

## Faster pages on big boards

The page loads its data as JSON. Many web servers do not compress JSON by
default, and compressing it makes it about three times smaller: the topic list
of a board with 4,000 topics goes from 214 KB to 66 KB. For Apache
(mod_deflate):

```apache
AddOutputFilterByType DEFLATE application/json
```

## Uninstall

*Disable* keeps the statistics and the settings. *Delete data* removes the
statistics tables and every setting, custom palettes included. The statistics
can always be rebuilt from the forum; the settings cannot.

## Licence

[GNU General Public License v2](license.txt) (GPL-2.0-only).

The charts are drawn with [Chart.js](https://www.chartjs.org/) 4.5.1 (MIT), the
official, unmodified `chart.umd.min.js`. Its licence, and that of the colour
library it includes, are in `styles/all/template/js/chart.LICENSE.txt`.
