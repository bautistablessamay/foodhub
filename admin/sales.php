<?php
require_once '../includes/auth.php'; require_role('admin'); require_once '../includes/sales.php';
$pageTitle = 'Sales & earnings'; include '../includes/header.php';
sales_report();
include '../includes/footer.php';
