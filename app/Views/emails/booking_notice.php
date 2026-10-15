<?php

declare(strict_types=1);

/**
 * @var string $name
 * @var string $eyebrow
 * @var string $heading
 * @var list<string> $paragraphs
 * @var string|null $ctaLabel
 * @var string|null $ctaUrl
 * @var string|null $logoSource
 * @var list<array{title: string, rows: list<array{0: string, 1: string}>}> $tables
 */
$brandName  = 'EPIC Storage Solutions';
$logoSource = (string) ($logoSource ?? base_url('assets/images/epic-logo-white.png'));
$paragraphs = $paragraphs ?? [];
$tables     = $tables ?? [];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($heading) ?></title>
</head>
<body style="margin:0;background:#f6f6f6;color:#141414;font-family:Montserrat,Arial,sans-serif;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f6f6f6;padding:32px 12px;">
        <tr><td align="center">
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:620px;background:#ffffff;border:1px solid #dedede;border-radius:14px;overflow:hidden;">
                <tr><td style="background:#083150;padding:28px 32px;text-align:center;">
                    <img src="<?= esc($logoSource, 'attr') ?>" width="170" alt="<?= esc($brandName, 'attr') ?>" style="display:inline-block;max-width:170px;height:auto;">
                </td></tr>
                <tr><td style="padding:38px 36px 30px;">
                    <p style="margin:0 0 10px;color:#005aab;font-size:12px;font-weight:800;letter-spacing:2px;text-transform:uppercase;"><?= esc($eyebrow) ?></p>
                    <h1 style="margin:0 0 18px;color:#083150;font-family:Poppins,Arial,sans-serif;font-size:28px;line-height:1.2;"><?= esc($heading) ?></h1>
                    <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hi <?= esc($name) ?>,</p>
                    <?php foreach ($paragraphs as $paragraph): ?>
                        <p style="margin:0 0 16px;font-size:16px;line-height:1.7;"><?= esc($paragraph) ?></p>
                    <?php endforeach ?>
                    <?php foreach ($tables as $table): ?>
                        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 20px;border:1px solid #dedede;border-radius:8px;overflow:hidden;">
                            <tr><td colspan="2" style="background:#083150;padding:9px 16px;"><span style="color:#ffffff;font-size:12px;font-weight:800;letter-spacing:.5px;text-transform:uppercase;"><?= esc($table['title']) ?></span></td></tr>
                            <?php foreach ($table['rows'] as $row): ?>
                                <tr>
                                    <td style="padding:9px 16px;border-top:1px solid #eeeeee;color:#707070;font-size:13px;width:45%;"><?= esc($row[0]) ?></td>
                                    <td style="padding:9px 16px;border-top:1px solid #eeeeee;color:#141414;font-size:13px;font-weight:700;text-align:right;"><?= esc($row[1]) ?></td>
                                </tr>
                            <?php endforeach ?>
                        </table>
                    <?php endforeach ?>
                    <?php if (! empty($ctaLabel) && ! empty($ctaUrl)): ?>
                        <p style="margin:0 0 10px;text-align:center;"><a href="<?= esc($ctaUrl, 'attr') ?>" style="display:inline-block;background:#89c4f2;border:1px solid #89c4f2;border-radius:7px;color:#0b2036;font-family:Montserrat,Arial,sans-serif;font-size:14px;font-weight:800;letter-spacing:.5px;padding:15px 25px;text-decoration:none;text-transform:uppercase;"><?= esc($ctaLabel) ?></a></p>
                        <p style="margin:0;color:#707070;font-size:13px;line-height:1.65;">If the button does not work, copy and paste this link into your browser:</p>
                        <p style="margin:7px 0 0;word-break:break-all;"><a href="<?= esc($ctaUrl, 'attr') ?>" style="color:#005aab;font-size:13px;"><?= esc($ctaUrl) ?></a></p>
                    <?php endif ?>
                </td></tr>
                <tr><td style="background:#f0f0f0;padding:20px 36px;text-align:center;">
                    <p style="margin:0;color:#707070;font-size:12px;line-height:1.6;">Need help? Call <a href="tel:3365495216" style="color:#005aab;font-weight:700;text-decoration:none;">336.549.5216</a> or email <a href="mailto:support@epicstoragehpu.com" style="color:#005aab;font-weight:700;text-decoration:none;">Sales Support</a>.</p>
                    <p style="margin:8px 0 0;color:#707070;font-size:11px;">&copy; <?= date('Y') ?> <?= esc($brandName) ?>. All rights reserved.</p>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
