<?php

if (!defined('_root')) {
    exit;
}

function _HCM_countusers($group_id = null)
{
    if (isset($group_id)) {
        $cond = _sqlWhereColumn("group_id", $group_id);
    } else {
        $cond = "";
    }

    return DB::count(_users_table, $cond);
}
