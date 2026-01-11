<?php
$user = Auth::user();
?>
<h2>Genel Bakış</h2>
<div class="row g-3 mt-2">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Rol</h5>
                <p class="card-text"><?= htmlspecialchars($user['role']) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Firma Erişimi</h5>
                <p class="card-text">Atanmış firmalarınızı görüntüleyin.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">CBAM Modülü</h5>
                <p class="card-text">Üretim, elektrik, yakıt ve proses emisyon verileri.</p>
            </div>
        </div>
    </div>
</div>
