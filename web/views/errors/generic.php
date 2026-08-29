<?php

declare(strict_types=1);

/** @var string $message */
/** @var int $status */

$status = $status ?? 400;
$message = $message ?? 'Възникна грешка.';
?>
<p class="m-0 text-2xl leading-snug font-semibold"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
<p class="mt-3 text-muted">Код <?= (int) $status ?></p>
