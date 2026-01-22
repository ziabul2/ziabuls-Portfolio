<?php
function loadPortfolioData() {
    $json_data = file_get_contents(__DIR__ . '/../data/portfolio.json');
    return json_decode($json_data, true);
}
?>
