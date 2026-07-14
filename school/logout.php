<?php
require_once 'functions.php';
log_activity('Logged out');
session_unset();
session_destroy();
redirect('main_dashboard.php');
