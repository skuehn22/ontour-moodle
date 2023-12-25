<?php

require(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../lib.php');

global $DB;

$id = $_POST['id'];

$sql = "SELECT * FROM {ext_operators1} WHERE id = 4";

$operator = $DB->get_record_sql($sql);

echo $operator->price;


