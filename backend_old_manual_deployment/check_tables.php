<?php
$pdo = new PDO('pgsql:host=localhost;port=5432;dbname=opf_cd', 'postgres', 'password123');
$stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE' ORDER BY table_name");
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $table) {
    echo $table . PHP_EOL;
}
