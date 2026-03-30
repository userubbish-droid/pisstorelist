<?php
declare(strict_types=1);

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function fmt_num(float $v): string {
    // 统一数字显示：最多2位小数，去掉尾随0
    return rtrim(rtrim(sprintf('%.2f', $v), '0'), '.');
}

