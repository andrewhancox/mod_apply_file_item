<?php

// needs $req_own_data, $table, $courseid, $show_all, ....

$title_ttl  = get_string('title_title',  'apply');
$title_date = get_string('date');
$title_ver  = get_string('title_version','apply');
$title_clss = get_string('title_class',  'apply');
$title_ack  = get_string('title_ack',    'apply');
$title_exec = get_string('title_exec',   'apply');
$title_chk  = get_string('title_check',  'apply');
$title_bfr  = get_string('title_before', 'apply');

//
if ($req_own_data) {
    $title_draft   = get_string('title_draft', 'apply');

    $table_columns = array('title',    'time_modified', 'version', 'class',      'draft',      'acked');
    $table_headers = array($title_ttl, $title_date,     $title_ver, $title_clss, $title_draft, $title_ack);
    $table_widths  = array('240px',    '$120px',        '30px',    '40px',       '60px',       '60px');

    if (!$apply->only_acked_accept) {
        $table_columns = array_merge($table_columns, array('execd'));
        $table_headers = array_merge($table_headers, array($title_exec));
        $table_widths  = array_merge($table_widths,  array('60px'));
    }

    $table_columns = array_merge($table_columns, array('before',   'edit'));
    $table_headers = array_merge($table_headers, array($title_bfr, '-'));
    $table_widths  = array_merge($table_widths,  array('120x',      ''));

    if ($apply->can_discard) {
        $table_columns = array_merge($table_columns, array('discard'));
        $table_headers = array_merge($table_headers, array('-'));
        $table_widths  = array_merge($table_widths,  array(''));
    }
}
else {
    $user_pic  = get_string('user_pic', 'apply');

    $table_columns = array('userpic');
    $table_headers = array($user_pic);
    $table_widths  = array('24px');

    if ($name_pattern=='firstname') {
        $table_columns = array_merge($table_columns, array('firstname'));
        $table_headers = array_merge($table_headers, array(get_string('firstname')));
        $table_widths  = array_merge($table_widths,  array('100px'));
    }
    else if ($name_pattern=='lastname') {
        $table_columns = array_merge($table_columns, array('lastname'));
        $table_headers = array_merge($table_headers, array(get_string('lastname')));
        $table_widths  = array_merge($table_widths,  array('100px'));
    }
    else if ($name_pattern=='firstlastname') {
        $table_columns = array_merge($table_columns, array('firstname', 'lastname'));
        $table_headers = array_merge($table_headers, array(get_string('firstname'), get_string('lastname')));
        $table_widths  = array_merge($table_widths,  array('80px', '100px'));
    }
    else if ($name_pattern=='lastfirstname') {
        $table_columns = array_merge($table_columns, array('lastname', 'firstname'));
        $table_headers = array_merge($table_headers, array(get_string('lastname'), get_string('firstname')));
        $table_widths  = array_merge($table_widths,  array('80px', '100px'));
    }
    else {
        $table_columns = array_merge($table_columns, array('fullname'));
        $table_headers = array_merge($table_headers, array(get_string('fullname')));
        $table_widths  = array_merge($table_widths,  array('100px'));
    }

    $table_columns = array_merge($table_columns, array('title',    'time_modified', 'version',  'class',     'acked'));
    $table_headers = array_merge($table_headers, array($title_ttl, $title_date,     $title_ver, $title_clss, $title_ack));
    $table_widths  = array_merge($table_widths,  array('240px',    '120px',         '30px',     '40px',      '60px'));

    if (!$apply->only_acked_accept) {
        $table_columns = array_merge($table_columns, array('execd'));
        $table_headers = array_merge($table_headers, array($title_exec));
        $table_widths  = array_merge($table_widths,  array('60px'));
    }

    $table_columns = array_merge($table_columns, array('before',   'operation'));
    $table_headers = array_merge($table_headers, array($title_bfr, '-'));
    $table_widths  = array_merge($table_widths,  array('120px',    ''));

    if ($apply->enable_deletemode) {
        $table_columns = array_merge($table_columns, array('delete'));
        $table_headers = array_merge($table_headers, array('-'));
        $table_widths  = array_merge($table_widths,  array(''));
    }
}

//
$table->define_columns($table_columns);
$table->define_headers($table_headers);
$table->define_baseurl($base_url);
// set width
$num = 0;
foreach ($table_columns as $column) {
    $table->column_style[$column]['width'] = $table_widths[$num];
    $num++;
}

//
if ($req_own_data) {
    $table->sortable(true, 'time_modified', SORT_DESC);
    $table->no_sorting('lastname');
    $table->no_sorting('firstname');
    $table->no_sorting('edit');
    $table->no_sorting('draft');
    $table->no_sorting('before');
    if ($apply->can_discard) $table->no_sorting('discard');
}
else {
    $table->sortable(true, 'time_modified', SORT_DESC);
    //
    if ($name_pattern=='lastname') {
        $table->sortable(true, 'lastname', SORT_ASC);
        $table->no_sorting('firstname');
    }
    else if ($name_pattern=='firstname') {
        $table->sortable(true, 'firstname', SORT_ASC);
        $table->no_sorting('lastname');
    }
    else {
        $table->sortable(true, 'firstname', SORT_ASC);
        $table->sortable(true, 'lastname',  SORT_ASC);
    }
    $table->no_sorting('before');
    $table->no_sorting('operation');
    if ($apply->enable_deletemode) $table->no_sorting('delete');
}

//
$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'show_entrytable');
$table->set_attribute('class', 'generaltable generalbox boxaligncenter');
/*
$table->set_control_variables(array(
            TABLE_VAR_SORT  => 'ssort',
            TABLE_VAR_IFIRST=> 'sifirst',
            TABLE_VAR_ILAST => 'silast',
            TABLE_VAR_PAGE    => 'spage'
            ));
*/
$table->setup();

//
if (!$sort) {
    $sort = $table->get_sql_sort();
    if (!$sort) $sort = '';
}

list($where, $params) = $table->get_sql_where();
if ($where) $where .= ' AND ';

//
$sifirst = optional_param('sifirst', '', PARAM_ALPHA);
if ($sifirst) {
    $where .= "firstname LIKE :sifirst ESCAPE '\\\\' AND ";
    $params['sifirst'] =  $sifirst.'%';
}
$silast = optional_param('silast',  '', PARAM_ALPHA);
if ($silast) {
    $where .= "lastname LIKE :silast ESCAPE '\\\\' AND ";
    $params['silast'] =  $silast.'%';
}

//
//$table->initialbars(true);  // フィルター用イニシャルのテーブル

if ($show_all) {
    $start_page = false;
    $page_count = false;
}
else {
    $table->pagesize($perpage, $matchcount);
    $start_page = $table->get_page_start();
    $page_count = $table->get_page_size();
}

