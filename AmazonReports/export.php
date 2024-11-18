<?php
session_start();

if (!isset($_SESSION['filter_type'])) {
    header('Location: index.php'); // Redirect if session filter type is not set
    exit()