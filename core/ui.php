<?php

function uiHead($title = "BitW") {
    echo '
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>'.$title.'</title>

    <!-- Bootstrap (stable production CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- Boxicons -->
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <!-- BITW Custom CSS -->
    <link rel="stylesheet" href="/assets/css/bitw.css">
    ';
}