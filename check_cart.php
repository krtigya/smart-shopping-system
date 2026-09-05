<?php
require 'config/connection.php';
$uid = 6;
$res = $conn->query("SELECT p.name, COALESCE(v.price, p.price) AS price, c.quantity AS qty FROM cart c JOIN products p ON c.product_id=p.id LEFT JOIN product_variants v ON c.variant_id=v.id AND c.variant_id=v.id WHERE c.user_id=$uid");
while ($r = $res->fetch_assoc()) {
    echo $r['name'] . ' price=' . $r['price'] . ' qty=' . $r['qty'] . PHP_EOL;
}