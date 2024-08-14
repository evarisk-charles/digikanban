<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    lib/digikanban.lib.php
 * \ingroup digikanban
 * \brief   Library files with common functions for Admin conf.
 */

/**
 * Prepare array of tabs for admin.
 *
 * @return array Array of tabs.
 */

function digikanban_admin_prepare_head($active, $linkback, $picto)
{
    // Global variables definitions.
    global $langs;

    // Load translation files required by the page.
    saturne_load_langs();

    // Initialize values.
    $h    = 0;
    $head = [];

    $head[$h][0] = dol_buildpath("/digikanban/admin/setup.php", 1);
    $head[$h][1] = $langs->trans("General");
    $head[$h][2] = 'general';
    $h++;

    $head[$h][0] = dol_buildpath("/digikanban/columns/list.php?mainmenu=project", 1);
    $head[$h][1] = $langs->trans('columns');
    $head[$h][2] = 'columns';
    $h++;



    print load_fiche_titre($langs->trans("config_vue_kanban"), $linkback, $picto);

    dol_get_fiche_head($head, $active, $langs->trans('config_vue_kanban'), -1,  'list');

}