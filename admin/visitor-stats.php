<?php
require_once __DIR__ . '/includes/auth.php';
ensure_visit_stats();

$ranges = [7 => '7 วัน', 30 => '30 วัน', 90 => '90 วัน', 365 => '12 เดือน'];
$days = (int) ($_GET['days'] ?? 30);
if (!isset($ranges[$days])) {
    $days = 30;
}
$byMonth = $days === 365;
$from = $byMonth ? (new DateTime(visit_today('-11 months'), new DateTimeZone(VISIT_TZ)))->modify('first day of this month')->format('Y-m-d') : visit_today('-' . ($days - 1) . ' days');

$today = visit_today();
$tiles = [
    ['วันนี้', visit_totals($today, $today)],
    ['เมื่อวาน', visit_totals(visit_today('-1 day'), visit_today('-1 day'))],
    ['7 วันล่าสุด', visit_totals(visit_today('-6 days'), $today)],
    ['เดือนนี้', visit_totals(substr($today, 0, 8) . '01', $today)],
    ['ทั้งหมด', visit_totals('2000-01-01', $today)],
];
$series = visit_series($from, $byMonth);
$range = visit_totals($from, $today);
$top = visit_top_pages($from);
$devices = visit_devices($from);
$deviceTotal = max(1, array_sum($devices));
$since = get_setting('visit_stats_since', $today);

$maxVisitors = max(1, max(array_column($series, 'visitors') ?: [0]));
// Round the axis top up to a "nice" number so gridlines land on whole values.
$step = 10 ** max(0, floor(log10($maxVisitors)));
foreach ([1, 2, 5, 10] as $m) {
    if ($m * $step * 4 >= $maxVisitors) { $step *= $m; break; }
}
$axisTop = max(4, $step * 4);
$axisStep = $axisTop / 4;
$labelEvery = max(1, (int) ceil(count($series) / 10));
$topMax = max(1, (int) ($top[0]['views'] ?? 1));
$fmt = fn($n) => number_format((int) $n);
$thDate = function ($ymd) {
    $m = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    [$y, $mo, $d] = array_map('intval', explode('-', $ymd));
    return $d . ' ' . $m[$mo] . ' ' . ($y + 543);
};

$adminPageTitle = 'สถิติผู้เข้าชมเว็บไซต์';
require_once __DIR__ . '/includes/layout-top.php';
?>
<style>
.vs-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 18px; }
.vs-tile { margin: 0; }
.vs-tile h4 { margin: 0 0 6px; font-size: .85rem; color: #777; font-weight: 600; }
.vs-tile .vs-num { font-size: 2rem; font-weight: 700; line-height: 1.1; color: #1a1a1a; font-variant-numeric: tabular-nums; }
.vs-tile .vs-sub { font-size: .82rem; color: #888; margin-top: 4px; }
.vs-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 14px; }
.vs-head h3 { margin: 0; }
.vs-range { display: inline-flex; border: 1px solid #ddd; border-radius: 999px; overflow: hidden; }
.vs-range a { padding: 6px 14px; font-size: .85rem; color: #555; text-decoration: none; }
.vs-range a + a { border-left: 1px solid #ddd; }
.vs-range a.on { background: var(--red, #a3222c); color: #fff; }
.vs-chart { position: relative; height: 260px; margin: 8px 0 0 44px; }
.vs-grid { position: absolute; left: 0; right: 0; border-top: 1px solid #eee; }
.vs-grid span { position: absolute; left: -44px; width: 38px; top: -9px; text-align: right; font-size: .72rem; color: #999; font-variant-numeric: tabular-nums; }
.vs-grid.base { border-top-color: #ccc; }
.vs-bars { position: absolute; inset: 0; display: flex; align-items: flex-end; gap: 2px; }
.vs-col { flex: 1 1 0; height: 100%; display: flex; align-items: flex-end; justify-content: center; cursor: default; position: relative; }
.vs-col i { display: block; width: 100%; max-width: 28px; min-height: 0; background: var(--red, #a3222c); border-radius: 4px 4px 0 0; transition: opacity .15s; }
.vs-col.zero i { height: 2px !important; background: #e6dcdc; }
.vs-bars:hover .vs-col i { opacity: .45; }
.vs-bars .vs-col:hover i { opacity: 1; }
.vs-xlabels { display: flex; gap: 2px; margin: 6px 0 0 44px; }
.vs-xlabels span { flex: 1 1 0; font-size: .7rem; color: #999; text-align: center; white-space: nowrap; overflow: visible; }
.vs-tip { position: absolute; pointer-events: none; background: #1a1a1a; color: #fff; padding: 7px 10px; border-radius: 8px; font-size: .8rem; line-height: 1.45; white-space: nowrap; transform: translate(-50%, -100%); margin-top: -8px; display: none; z-index: 5; }
.vs-tip strong { font-size: .95rem; }
.vs-split { display: grid; grid-template-columns: 2fr 1fr; gap: 18px; }
@media (max-width: 900px) { .vs-split { grid-template-columns: 1fr; } }
.vs-page { display: grid; grid-template-columns: 1fr auto; gap: 2px 12px; padding: 9px 0; border-top: 1px solid #f0f0f0; font-size: .9rem; }
.vs-page:first-of-type { border-top: 0; }
.vs-page a { color: inherit; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.vs-page a:hover { color: var(--red, #a3222c); }
.vs-page small { grid-column: 1; color: #999; font-size: .75rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.vs-page b { grid-row: 1 / span 2; grid-column: 2; align-self: center; font-variant-numeric: tabular-nums; }
.vs-meter { grid-column: 1 / -1; height: 6px; background: #f3eded; border-radius: 3px; overflow: hidden; }
.vs-meter i { display: block; height: 100%; background: var(--red, #a3222c); border-radius: 3px; }
.vs-dev { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-top: 1px solid #f0f0f0; }
.vs-dev:first-of-type { border-top: 0; }
.vs-dev span:first-child { font-size: 1.4rem; width: 32px; text-align: center; }
.vs-dev div { flex: 1; }
.vs-dev b { font-variant-numeric: tabular-nums; }
.vs-table td, .vs-table th { text-align: right; }
.vs-table td:first-child, .vs-table th:first-child { text-align: left; }
</style>

<div class="admin-card">
    <p style="margin:0;">นับเฉพาะผู้เข้าชมหน้าเว็บจริง <strong>ไม่นับ</strong> บอต/เครื่องค้นหา และผู้ดูแลระบบที่ล็อกอินอยู่ · เวลาประเทศไทย · เริ่มนับตั้งแต่ <strong><?= h($thDate($since)) ?></strong>
        <br><small style="color:#888;">"ผู้เข้าชม" = จำนวนคน (เครื่อง/เบราว์เซอร์ นับไม่ซ้ำ) · "เปิดหน้า" = จำนวนครั้งที่เปิดดูหน้าเว็บทั้งหมด</small></p>
</div>

<div class="vs-tiles">
    <?php foreach ($tiles as [$label, $t]): ?>
    <div class="admin-card vs-tile">
        <h4><?= h($label) ?></h4>
        <div class="vs-num"><?= $fmt($t['visitors']) ?></div>
        <div class="vs-sub">ผู้เข้าชม · เปิดหน้า <?= $fmt($t['views']) ?> ครั้ง</div>
    </div>
    <?php endforeach; ?>
</div>

<div class="admin-card">
    <div class="vs-head">
        <h3>ผู้เข้าชม<?= $byMonth ? 'รายเดือน' : 'รายวัน' ?> · <?= h($ranges[$days]) ?>ล่าสุด
            <small style="font-weight:400;color:#888;font-size:.85rem;">(รวม <?= $fmt($range['visitors']) ?> คน · เปิดหน้า <?= $fmt($range['views']) ?> ครั้ง)</small></h3>
        <nav class="vs-range" aria-label="ช่วงเวลา">
            <?php foreach ($ranges as $d => $label): ?>
            <a href="?days=<?= $d ?>" class="<?= $d === $days ? 'on' : '' ?>"><?= h($label) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
    <div class="vs-chart" id="vs-chart" role="img" aria-label="กราฟจำนวนผู้เข้าชม<?= $byMonth ? 'รายเดือน' : 'รายวัน' ?>">
        <?php for ($g = 0; $g <= 4; $g++): ?>
        <div class="vs-grid<?= $g === 0 ? ' base' : '' ?>" style="bottom:<?= $g * 25 ?>%"><span><?= $fmt($g * $axisStep) ?></span></div>
        <?php endfor; ?>
        <div class="vs-bars">
            <?php foreach ($series as $p): ?>
            <div class="vs-col<?= $p['visitors'] ? '' : ' zero' ?>" data-label="<?= h($p['label']) ?>" data-v="<?= (int) $p['visitors'] ?>" data-p="<?= (int) $p['views'] ?>">
                <i style="height:<?= round($p['visitors'] / $axisTop * 100, 2) ?>%"></i>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="vs-tip" id="vs-tip"></div>
    </div>
    <div class="vs-xlabels" aria-hidden="true">
        <?php foreach ($series as $i => $p): ?>
        <span><?= ($i % $labelEvery === 0 || $i === count($series) - 1) && (count($series) - 1 - $i >= $labelEvery || $i === count($series) - 1) ? h($p['label']) : '' ?></span>
        <?php endforeach; ?>
    </div>
    <details style="margin-top:14px;">
        <summary style="cursor:pointer;color:#666;font-size:.88rem;">ดูเป็นตาราง</summary>
        <table class="admin-table vs-table" style="margin-top:8px;">
            <thead><tr><th><?= $byMonth ? 'เดือน' : 'วันที่' ?></th><th>ผู้เข้าชม</th><th>เปิดหน้า</th></tr></thead>
            <tbody>
                <?php foreach (array_reverse($series) as $p): ?>
                <tr><td><?= h($p['label']) ?></td><td><?= $fmt($p['visitors']) ?></td><td><?= $fmt($p['views']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </details>
</div>

<div class="vs-split">
    <div class="admin-card">
        <h3 style="margin-top:0;">หน้าที่มีคนเปิดดูมากที่สุด · <?= h($ranges[$days]) ?>ล่าสุด</h3>
        <?php if (!$top): ?>
        <p style="color:#888;">ยังไม่มีข้อมูลในช่วงนี้</p>
        <?php endif; ?>
        <?php foreach ($top as $row): ?>
        <div class="vs-page">
            <a href="<?= h($row['path']) ?>" target="_blank" rel="noopener" title="<?= h($row['title'] ?: $row['path']) ?>"><?= h($row['title'] ?: $row['path']) ?></a>
            <b><?= $fmt($row['views']) ?></b>
            <small><?= h(urldecode($row['path'])) ?></small>
            <div class="vs-meter"><i style="width:<?= round($row['views'] / $topMax * 100, 1) ?>%"></i></div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="admin-card">
        <h3 style="margin-top:0;">อุปกรณ์ที่ใช้เข้าชม</h3>
        <?php foreach (['mobile' => ['📱', 'มือถือ'], 'desktop' => ['💻', 'คอมพิวเตอร์'], 'tablet' => ['📟', 'แท็บเล็ต']] as $key => [$icon, $label]): $pct = round($devices[$key] / $deviceTotal * 100); ?>
        <div class="vs-dev">
            <span><?= $icon ?></span>
            <div><?= h($label) ?><div class="vs-meter" style="margin-top:5px;"><i style="width:<?= $pct ?>%"></i></div></div>
            <b><?= $pct ?>%</b>
            <small style="color:#999;width:56px;text-align:right;"><?= $fmt($devices[$key]) ?> คน</small>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
(function () {
    var chart = document.getElementById('vs-chart');
    var tip = document.getElementById('vs-tip');
    if (!chart) return;
    chart.querySelectorAll('.vs-col').forEach(function (col) {
        col.addEventListener('mouseenter', function () {
            tip.innerHTML = '<div>' + col.dataset.label + '</div><strong>' + Number(col.dataset.v).toLocaleString() + '</strong> ผู้เข้าชม<br>เปิดหน้า ' + Number(col.dataset.p).toLocaleString() + ' ครั้ง';
            var bar = col.querySelector('i');
            var c = chart.getBoundingClientRect(), b = bar.getBoundingClientRect(), k = col.getBoundingClientRect();
            var x = k.left + k.width / 2 - c.left;
            tip.style.left = Math.min(Math.max(x, 70), c.width - 70) + 'px';
            tip.style.top = (b.top - c.top) + 'px';
            tip.style.display = 'block';
        });
        col.addEventListener('mouseleave', function () { tip.style.display = 'none'; });
    });
})();
</script>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
