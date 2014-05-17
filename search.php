<?php
  
$keyword = filter_input(INPUT_POST, 'keyword');
$case    = filter_input(INPUT_POST, 'case');
$regx    = filter_input(INPUT_POST, 'regx');

if (!empty($keyword)) { $urlGets['keyw'] = urlencode($keyword); }
if (!empty($case))    { $urlGets['case'] = 'case=true'; }
if (!empty($regx))    { $urlGets['regx'] = 'regx=true'; }

$urlGet = implode('&', $urlGets);

$urlRedirect = $_SERVER['HTTP_HOST'] . "../$urlGet";

header("Location: http://$urlRedirect");
// echo $urlRedirect;
die();
