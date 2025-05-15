<?php
// File: pelanggan/buat_pesanan.php
// Halaman untuk membuat pesanan baru dengan skema pembayaran dan kalkulasi berat otomatis

session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';
require_once '../includes/notification_helper.php'; // Tambahkan ini

// Cek apakah user adalah pelanggan
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pelanggan') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Buat Pesanan Baru";
$customer_id = $_SESSION['customer_id'];

// Ambil data layanan yang aktif
$services_query = "SELECT * FROM services WHERE is_active = 1";
$services_result = mysqli_query($koneksi, $services_query);

// Handle form submission
if(isset($_POST['buat_pesanan'])) {
    $metode_antar = cleanInput($_POST['metode_antar']);
    $alamat_jemput = ($metode_antar == 'jemput') ? cleanInput($_POST['alamat_jemput']) : '';
    $catatan = cleanInput($_POST['catatan']);
    $service_id = cleanInput($_POST['service_id']);
    $berat = cleanInput($_POST['berat']);
    $jumlah = cleanInput($_POST['jumlah']);
    $jenis_pakaian = cleanInput($_POST['jenis_pakaian']);
    $metode_pembayaran = cleanInput($_POST['metode_pembayaran']);
    
    // Hitung harga
    $service_query = "SELECT harga_per_kg FROM services WHERE id = '$service_id'";
    $service_result = mysqli_query($koneksi, $service_query);
    $service = mysqli_fetch_assoc($service_result);
    $harga_per_kg = $service['harga_per_kg'];
    
    $subtotal = $berat * $harga_per_kg;
    
    // Tambahan biaya jika metode antar adalah jemput
    $biaya_jemput = 0;
    if($metode_antar == 'jemput') {
        $biaya_jemput = 5000; // Biaya jemput Rp 5.000
    }
    
    $total_harga = $subtotal + $biaya_jemput;
    
    // Mulai transaction
    mysqli_begin_transaction($koneksi);
    
    try {
        // Insert ke tabel orders
        $order_query = "INSERT INTO orders (customer_id, total_harga, tgl_order, status, metode_antar, alamat_jemput, catatan) 
                       VALUES ('$customer_id', '$total_harga', NOW(), 'menunggu_konfirmasi', '$metode_antar', '$alamat_jemput', '$catatan')";
        mysqli_query($koneksi, $order_query);
        $order_id = mysqli_insert_id($koneksi);
        
        // Insert ke tabel order_details
        $detail_query = "INSERT INTO order_details (order_id, service_id, jenis_pakaian, jumlah, berat, harga, subtotal) 
                        VALUES ('$order_id', '$service_id', '$jenis_pakaian', '$jumlah', '$berat', '$harga_per_kg', '$subtotal')";
        mysqli_query($koneksi, $detail_query);
        
        // Insert ke tabel payments dengan status pending
        $payment_query = "INSERT INTO payments (order_id, metode_pembayaran, jumlah_bayar, status_pembayaran) 
                         VALUES ('$order_id', '$metode_pembayaran', '$total_harga', 'pending')";
        mysqli_query($koneksi, $payment_query);
        
        // Jika metode antar adalah jemput, insert ke pickup_requests
        if($metode_antar == 'jemput') {
            $pickup_query = "INSERT INTO pickup_requests (order_id, tgl_jemput, status_jemput) 
                            VALUES ('$order_id', NOW(), 'pending')";
            mysqli_query($koneksi, $pickup_query);
        }
        
        // Kirim notifikasi - GANTI BARIS sendNotification DENGAN INI
        createNotification($_SESSION['user_id'], "Pesanan #$order_id berhasil dibuat dan sedang menunggu konfirmasi.", 'order', $order_id);
        
        mysqli_commit($koneksi);
        
        $_SESSION['success'] = "Pesanan berhasil dibuat!";
        
        // Jika metode pembayaran bukan cash, redirect ke halaman pembayaran
        if($metode_pembayaran != 'cash') {
            header("Location: pembayaran.php?id=$order_id");
        } else {
            header("Location: tracking.php?id=$order_id");
        }
        exit();
        
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $error = "Gagal membuat pesanan: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<style>
    :root {
        --primary-color: #1a73e8;
        --primary-dark: #0d47a1;
        --secondary-color: #263238;
        --accent-color: #00c853;
        --light-gray: #f0f4f8;
        --mid-gray: #e1e8ed;
        --dark-gray: #546e7a;
        --danger: #d32f2f;
        --success: #00c853;
        --border-radius: 6px;
        --box-shadow: 0 6px 16px rgba(0,0,0,0.1);
        --transition: all 0.25s ease-in-out;
    }

    body {
        font-family: 'Nunito', 'Segoe UI', Arial, sans-serif;
        line-height: 1.6;
        color: #333;
        background-color: #f8f9fa;
    }

    /* Main Container */
    .form-container {
        background: white;
        padding: 30px;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        max-width: 900px;
        margin: 30px auto;
        position: relative;
        border-top: 4px solid var(--primary-color);
    }

    /* Form Groups */
    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--secondary-color);
        font-size: 0.95rem;
    }

    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: var(--border-radius);
        font-size: 1rem;
        transition: var(--transition);
    }

    .form-control:focus {
        border-color: var(--primary-color);
        outline: none;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
    }

    textarea.form-control {
        height: 120px;
        resize: vertical;
    }

    /* Buttons */
    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: var(--border-radius);
        cursor: pointer;
        font-weight: 600;
        font-size: 1rem;
        transition: var(--transition);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .btn-primary {
        background-color: var(--primary-color);
        color: white;
    }

    .btn-primary:hover {
        background-color: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .btn-secondary {
        background-color: var(--dark-gray);
        color: white;
        margin-right: 12px;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    /* Toggle Mode */
    .toggle-mode {
        display: flex;
        margin-bottom: 25px;
        background: var(--light-gray);
        padding: 6px;
        border-radius: var(--border-radius);
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .toggle-mode button {
        flex: 1;
        padding: 12px;
        border: none;
        background: transparent;
        cursor: pointer;
        font-weight: 600;
        color: var(--dark-gray);
        transition: var(--transition);
        border-radius: calc(var(--border-radius) - 2px);
    }

    .toggle-mode button.active {
        background: var(--primary-color);
        color: white;
        box-shadow: 0 2px 6px rgba(52, 152, 219, 0.3);
    }

    /* Mode Containers */
    .mode-container {
        display: none;
        animation: fadeIn 0.3s ease;
    }

    .mode-container.active {
        display: block;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Address Pickup */
    .alamat-jemput {
        display: none;
        padding: 15px;
        border-left: 4px solid var(--primary-color);
        background-color: var(--light-gray);
        border-radius: 0 var(--border-radius) var(--border-radius) 0;
        margin-top: 10px;
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Required Fields */
    .required {
        color: var(--danger);
        margin-left: 4px;
    }

    /* Price Calculation */
    .price-calculation {
        background: var(--light-gray);
        padding: 25px;
        border-radius: var(--border-radius);
        margin-top: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        border-left: 4px solid var(--accent-color);
    }

    .price-calculation h4 {
        margin-top: 0;
        color: var(--secondary-color);
        font-size: 1.2rem;
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }

    /* Payment Info */
    .payment-info {
        background: var(--light-gray);
        padding: 25px;
        border-radius: var(--border-radius);
        margin-top: 25px;
        border-left: 4px solid var(--success);
    }

    .payment-info h4 {
        color: var(--secondary-color);
        margin-top: 0;
        font-size: 1.2rem;
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }

    /* Payment Methods */
    .payment-methods {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }

    .payment-method {
        border: 2px solid #ddd;
        padding: 15px;
        border-radius: var(--border-radius);
        cursor: pointer;
        transition: var(--transition);
        text-align: center;
        position: relative;
    }

    .payment-method:hover {
        border-color: var(--primary-color);
        transform: translateY(-2px);
    }

    .payment-method.selected {
        border-color: var(--primary-color);
        background-color: rgba(52, 152, 219, 0.05);
    }

    .payment-method input[type="radio"] {
        display: none;
    }

    .payment-method img {
        max-height: 40px;
        margin-bottom: 10px;
    }

    .payment-method .label {
        font-weight: 600;
        color: var(--secondary-color);
    }

    .payment-details {
        margin-top: 15px;
        padding: 15px;
        background-color: white;
        border-radius: var(--border-radius);
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        display: none;
        animation: fadeIn 0.3s ease;
    }

    /* Clothing Items */
    .clothing-items-container {
        border: 1px solid #ddd;
        border-radius: var(--border-radius);
        padding: 20px;
        margin-bottom: 20px;
        background-color: white;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }

    .clothing-items-container h4 {
        margin-top: 0;
        color: var(--secondary-color);
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }

    .item-row {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
        align-items: center;
        padding: 10px;
        background-color: var(--light-gray);
        border-radius: var(--border-radius);
        transition: var(--transition);
    }

    .item-row:hover {
        background-color: rgba(52, 152, 219, 0.05);
    }

    .item-row select, .item-row input {
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: var(--border-radius);
        flex: 1;
        transition: var(--transition);
    }

    .item-row select:focus, .item-row input:focus {
        border-color: var(--primary-color);
        outline: none;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
    }

    .add-item {
        background-color: var(--primary-color);
        color: white;
        border: none;
        border-radius: var(--border-radius);
        padding: 10px 15px;
        cursor: pointer;
        margin-top: 10px;
        font-weight: 600;
        transition: var(--transition);
    }

    .add-item:hover {
        background-color: var(--primary-dark);
        transform: translateY(-2px);
    }

    .remove-item {
        background-color: var(--danger);
        color: white;
        border: none;
        border-radius: var(--border-radius);
        padding: 8px 12px;
        cursor: pointer;
        transition: var(--transition);
    }

    .remove-item:hover {
        background-color: #c0392b;
        transform: translateY(-2px);
    }

    /* Weight Breakdown */
    .weight-breakdown {
        margin-top: 20px;
        background-color: var(--light-gray);
        padding: 20px;
        border-radius: var(--border-radius);
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        border-left: 4px solid var(--primary-color);
    }

    .weight-breakdown h5 {
        margin-top: 0;
        color: var(--secondary-color);
        font-size: 1.1rem;
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }

    .weight-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px dashed #ddd;
    }

    .weight-item:last-child {
        border-bottom: none;
    }

    .weight-item .item-name {
        color: var(--secondary-color);
        font-weight: 500;
    }

    .weight-item .item-weight {
        color: var(--dark-gray);
    }

    .weight-total {
        font-weight: 700;
        margin-top: 15px;
        border-top: 2px solid #ddd;
        padding-top: 15px;
        display: flex;
        justify-content: space-between;
        color: var(--secondary-color);
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .form-container {
            padding: 20px;
            margin: 15px;
        }
        
        .payment-methods {
            grid-template-columns: 1fr;
        }
        
        .item-row {
            flex-direction: column;
            align-items: stretch;
        }
        
        .item-row > * {
            margin-bottom: 10px;
        }
        
        .btn {
            width: 100%;
            margin-bottom: 10px;
        }
        
        .btn-secondary {
            margin-right: 0;
        }
    }

    /* Helper Classes */
    .text-center {
        text-align: center;
    }

    .mt-3 {
        margin-top: 15px;
    }

    .mb-3 {
        margin-bottom: 15px;
    }

    .card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 20px;
        margin-bottom: 20px;
    }

    .card-header {
        border-bottom: 1px solid #ddd;
        padding-bottom: 15px;
        margin-bottom: 15px;
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--secondary-color);
    }
</style>

<h1>Buat Pesanan Baru</h1>

<?php if(isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="form-container">
    <form method="POST" action="" id="pesananForm">
        <div class="form-group">
            <label>Jenis Layanan <span class="required">*</span></label>
            <select name="service_id" id="service_id" class="form-control" required>
                <option value="">Pilih Layanan</option>
                <?php 
                mysqli_data_seek($services_result, 0);
                while($service = mysqli_fetch_assoc($services_result)): 
                ?>
                    <option value="<?php echo $service['id']; ?>" data-harga="<?php echo $service['harga_per_kg']; ?>">
                        <?php echo $service['nama_layanan']; ?> - <?php echo formatRupiah($service['harga_per_kg']); ?>/kg
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="toggle-mode">
            <button type="button" id="simpleMode" class="active">Mode Sederhana</button>
            <button type="button" id="detailMode">Mode Detail</button>
        </div>
        
        <!-- Mode Sederhana (Original) -->
        <div class="mode-container active" id="simpleModeContainer">
            <div class="form-group">
                <label>Jenis Pakaian <span class="required">*</span></label>
                <input type="text" name="jenis_pakaian" class="form-control" placeholder="Contoh: Kaos, Celana, Jaket" required>
            </div>
            
            <div class="form-group">
                <label>Jumlah (pcs) <span class="required">*</span></label>
                <input type="number" name="jumlah" class="form-control" min="1" required>
            </div>
            
            <div class="form-group">
                <label>Berat (kg) <span class="required">*</span></label>
                <input type="number" name="berat" id="berat" class="form-control" step="0.1" min="0.1" required>
                <small>Minimal 1 kg</small>
            </div>
        </div>
        
        <!-- Mode Detail (Kalkulasi Otomatis) -->
        <div class="mode-container" id="detailModeContainer">
            <div class="form-group">
                <label>Daftar Pakaian <span class="required">*</span></label>
                <input type="hidden" name="jenis_pakaian_detail" id="jenis_pakaian_detail">
                <input type="hidden" name="berat_detail" id="berat_detail">
                <input type="hidden" name="jumlah_detail" id="jumlah_detail">
                
                <div id="clothingItems" class="clothing-items-container">
                    <!-- Template item akan ditambahkan oleh JavaScript -->
                </div>
                
                <button type="button" id="addItem" class="btn btn-secondary">+ Tambah Pakaian</button>
                
                <div class="weight-breakdown">
                    <h4>Rincian Berat</h4>
                    <div id="weightBreakdown">
                        <!-- Akan diisi oleh JavaScript -->
                    </div>
                    <div class="weight-total">
                        Total Berat: <span id="totalWeight">0</span> kg
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label>Metode Antar <span class="required">*</span></label>
            <select name="metode_antar" id="metode_antar" class="form-control" required>
                <option value="">Pilih Metode</option>
                <option value="antar_sendiri">Antar Sendiri</option>
                <option value="jemput">Jemput oleh Karyawan (+Rp 5.000)</option>
            </select>
        </div>
        
        <div class="form-group alamat-jemput" id="alamatJemputDiv">
            <label>Alamat Penjemputan <span class="required">*</span></label>
            <textarea name="alamat_jemput" class="form-control" placeholder="Masukkan alamat lengkap untuk penjemputan"></textarea>
        </div>
        
        <div class="form-group">
            <label>Catatan (Opsional)</label>
            <textarea name="catatan" class="form-control" placeholder="Tambahkan catatan khusus jika ada"></textarea>
        </div>
        
        <div class="form-group">
            <label>Metode Pembayaran <span class="required">*</span></label>
            <div class="payment-methods">
                <label class="payment-method">
                    <input type="radio" name="metode_pembayaran" value="cash" required>
                    <div><b>Cash</b><br>Bayar tunai saat pengantaran/penjemputan</div>
                </label>
                <label class="payment-method">
                    <input type="radio" name="metode_pembayaran" value="transfer" required>
                    <div><b>Transfer Bank</b><br>BCA, Mandiri, BNI, BRI</div>
                </label>
                <label class="payment-method">
                    <input type="radio" name="metode_pembayaran" value="ovo" required>
                    <div><b>OVO</b><br>Pembayaran via OVO</div>
                </label>
                <label class="payment-method">
                    <input type="radio" name="metode_pembayaran" value="dana" required>
                    <div><b>DANA</b><br>Pembayaran via DANA</div>
                </label>
                <label class="payment-method">
                    <input type="radio" name="metode_pembayaran" value="gopay" required>
                    <div><b>GoPay</b><br>Pembayaran via GoPay</div>
                </label>
            </div>
        </div>
        
        <div class="payment-info" id="paymentInfo">
            <h4>Informasi Pembayaran</h4>
            <div class="payment-details" id="cashDetails">
                <p>Pembayaran akan dilakukan secara tunai saat pengantaran atau penjemputan pakaian.</p>
            </div>
            <div class="payment-details" id="transferDetails">
                <p>Anda akan diarahkan ke halaman pembayaran setelah pesanan dibuat.</p>
                <p>Silakan transfer ke rekening yang ditampilkan dan upload bukti pembayaran.</p>
            </div>
            <div class="payment-details" id="ewalletDetails">
                <p>Anda akan diarahkan ke halaman pembayaran setelah pesanan dibuat.</p>
                <p>Silakan scan QR code atau transfer ke nomor yang ditampilkan.</p>
            </div>
        </div>
        
        <div class="price-calculation">
            <h4>Estimasi Harga</h4>
            <p>Harga per kg: <span id="hargaPerKg">Rp 0</span></p>
            <p>Biaya Laundry: <span id="biayaLaundry">Rp 0</span></p>
            <p>Biaya Jemput: <span id="biayaJemput">Rp 0</span></p>
            <hr>
            <p><b>Total: <span id="totalHarga">Rp 0</span></b></p>
        </div>
        
        <button type="submit" name="buat_pesanan" class="btn btn-primary">Buat Pesanan</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const serviceSelect = document.getElementById('service_id');
    const beratInput = document.getElementById('berat');
    const metodeAntarSelect = document.getElementById('metode_antar');
    const alamatJemputDiv = document.getElementById('alamatJemputDiv');
    const hargaPerKgSpan = document.getElementById('hargaPerKg');
    const biayaLaundrySpan = document.getElementById('biayaLaundry');
    const biayaJemputSpan = document.getElementById('biayaJemput');
    const totalHargaSpan = document.getElementById('totalHarga');
    const paymentMethods = document.querySelectorAll('.payment-method');
    const paymentDetails = document.querySelectorAll('.payment-details');
    
    // Mode toggle
    const simpleMode = document.getElementById('simpleMode');
    const detailMode = document.getElementById('detailMode');
    const simpleModeContainer = document.getElementById('simpleModeContainer');
    const detailModeContainer = document.getElementById('detailModeContainer');
    const clothingItems = document.getElementById('clothingItems');
    const addItemButton = document.getElementById('addItem');
    const weightBreakdown = document.getElementById('weightBreakdown');
    const totalWeight = document.getElementById('totalWeight');
    const jenisDetailInput = document.getElementById('jenis_pakaian_detail');
    const beratDetailInput = document.getElementById('berat_detail');
    const jumlahDetailInput = document.getElementById('jumlah_detail');
    
    // Data untuk konversi jumlah ke berat
    const weightConversion = {
        'kaos': 0.1,           // 10 kaos per kg
        'kemeja': 0.125,       // 8 kemeja per kg
        'celana_pendek': 0.2,  // 5 celana pendek per kg
        'celana_panjang': 0.5, // 2 celana panjang per kg
        'dress': 0.25,         // 4 dress per kg
        'jaket': 0.33,         // 3 jaket per kg
        'sweater': 0.25,       // 4 sweater per kg
        'jas': 1.0,            // 1 jas per kg
        'rok': 0.2,            // 5 rok per kg
        'handuk': 0.33,        // 3 handuk per kg
        'sprei': 0.5,          // 2 sprei per kg
        'selimut': 1.0,        // 1 selimut per kg
        'sarung_bantal': 0.1,  // 10 sarung bantal per kg
        'jeans': 0.5,          // 2 jeans per kg
        'kaus_kaki': 0.05,     // 20 pasang kaus kaki per kg
        'pakaian_dalam': 0.05  // 20 pakaian dalam per kg
    };
    
    // Mode toggle handlers
    simpleMode.addEventListener('click', function() {
        simpleMode.classList.add('active');
        detailMode.classList.remove('active');
        simpleModeContainer.classList.add('active');
        detailModeContainer.classList.remove('active');
    });
    
    detailMode.addEventListener('click', function() {
        detailMode.classList.add('active');
        simpleMode.classList.remove('active');
        detailModeContainer.classList.add('active');
        simpleModeContainer.classList.remove('active');
        
        // Add first item if empty
        if (clothingItems.children.length === 0) {
            addClothingItem();
        }
    });
    
    // Add clothing item
    addItemButton.addEventListener('click', addClothingItem);
    
    function addClothingItem() {
        const itemRow = document.createElement('div');
        itemRow.className = 'item-row';
        
        const itemSelect = document.createElement('select');
        itemSelect.className = 'clothing-type';
        itemSelect.required = true;
        
        // Add clothing options
        const options = [
            { value: 'kaos', text: 'Kaos/T-shirt (0.1 kg/pcs)' },
            { value: 'kemeja', text: 'Kemeja (0.125 kg/pcs)' },
            { value: 'celana_pendek', text: 'Celana Pendek (0.2 kg/pcs)' },
            { value: 'celana_panjang', text: 'Celana Panjang (0.5 kg/pcs)' },
            { value: 'dress', text: 'Dress (0.25 kg/pcs)' },
            { value: 'jaket', text: 'Jaket (0.33 kg/pcs)' },
            { value: 'sweater', text: 'Sweater (0.25 kg/pcs)' },
            { value: 'jas', text: 'Jas (1.0 kg/pcs)' },
            { value: 'rok', text: 'Rok (0.2 kg/pcs)' },
            { value: 'handuk', text: 'Handuk (0.33 kg/pcs)' },
            { value: 'sprei', text: 'Sprei (0.5 kg/pcs)' },
            { value: 'selimut', text: 'Selimut (1.0 kg/pcs)' },
            { value: 'sarung_bantal', text: 'Sarung Bantal (0.1 kg/pcs)' },
            { value: 'jeans', text: 'Jeans (0.5 kg/pcs)' },
            { value: 'kaus_kaki', text: 'Kaus Kaki (0.05 kg/pasang)' },
            { value: 'pakaian_dalam', text: 'Pakaian Dalam (0.05 kg/pcs)' }
        ];
        
        // Create placeholder option
        const placeholderOption = document.createElement('option');
        placeholderOption.value = '';
        placeholderOption.text = 'Pilih jenis pakaian';
        placeholderOption.selected = true;
        placeholderOption.disabled = true;
        itemSelect.appendChild(placeholderOption);
        
        // Add all clothing options
        options.forEach(option => {
            const optionEl = document.createElement('option');
            optionEl.value = option.value;
            optionEl.text = option.text;
            itemSelect.appendChild(optionEl);
        });
        
        const quantityInput = document.createElement('input');
        quantityInput.type = 'number';
        quantityInput.className = 'clothing-quantity';
        quantityInput.min = '1';
        quantityInput.value = '1';
        quantityInput.placeholder = 'Jumlah';
        quantityInput.required = true;
        
        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'remove-item';
        removeButton.textContent = 'Hapus';
        removeButton.addEventListener('click', function() {
            clothingItems.removeChild(itemRow);
            calculateTotalWeight();
            calculatePrice();
            
            if (clothingItems.children.length === 0) {
                addClothingItem();
            }
        });
        
        itemRow.appendChild(itemSelect);
        itemRow.appendChild(quantityInput);
        itemRow.appendChild(removeButton);
        
        clothingItems.appendChild(itemRow);
        
        // Add event listeners for weight calculation
        itemSelect.addEventListener('change', function() {
            calculateTotalWeight();
            calculatePrice();
        });
        
        quantityInput.addEventListener('input', function() {
            calculateTotalWeight();
            calculatePrice();
        });
    }
    
    // Calculate total weight from clothing items
    function calculateTotalWeight() {
        let total = 0;
        const items = [];
        const typeCountMap = {};
        let totalCount = 0;
        
        // Clear current breakdown
        weightBreakdown.innerHTML = '';
        
        // Get all clothing items
        const types = document.querySelectorAll('.clothing-type');
        const quantities = document.querySelectorAll('.clothing-quantity');
        
        // Calculate weight for each item
        for (let i = 0; i < types.length; i++) {
            const type = types[i].value;
            if (!type) continue;
            
            const quantity = parseInt(quantities[i].value) || 0;
            if (quantity <= 0) continue;
            
            totalCount += quantity;
            
            const weight = weightConversion[type] * quantity;
            total += weight;
            
            // Add to items array for hidden input
            items.push({
                type: type,
                quantity: quantity,
                weight: weight.toFixed(2)
            });
            
            // Add to type count map for summary
            if (!typeCountMap[type]) {
                typeCountMap[type] = quantity;
            } else {
                typeCountMap[type] += quantity;
            }
            
            // Add to breakdown
            const itemName = types[i].options[types[i].selectedIndex].text.split(' (')[0];
            const weightItem = document.createElement('div');
            weightItem.className = 'weight-item';
            weightItem.innerHTML = `
                <span>${quantity}x ${itemName}</span>
                <span>${weight.toFixed(2)} kg</span>
            `;
            weightBreakdown.appendChild(weightItem);
        }
        
        // Update total weight display
        totalWeight.textContent = total.toFixed(2);
        
        // Update hidden inputs
        jenisDetailInput.value = JSON.stringify(Object.keys(typeCountMap).map(key => {
            const option = Array.from(document.querySelectorAll('.clothing-type option'))
                .find(opt => opt.value === key);
            const name = option ? option.text.split(' (')[0] : key;
            return `${name} (${typeCountMap[key]} pcs)`;
        }).join(', '));
        beratDetailInput.value = total.toFixed(2);
        jumlahDetailInput.value = totalCount;
        
        // Update simple mode fields too
        document.querySelector('input[name="jenis_pakaian"]').value = jenisDetailInput.value;
        document.querySelector('input[name="jumlah"]').value = totalCount;
        document.querySelector('input[name="berat"]').value = total.toFixed(2);
        
        return total;
    }
    
    // Show/hide alamat jemput
    metodeAntarSelect.addEventListener('change', function() {
        if (this.value === 'jemput') {
            alamatJemputDiv.style.display = 'block';
            alamatJemputDiv.querySelector('textarea').required = true;
        } else {
            alamatJemputDiv.style.display = 'none';
            alamatJemputDiv.querySelector('textarea').required = false;
        }
        calculatePrice();
    });
    
    // Payment method selection
    paymentMethods.forEach(method => {
        method.addEventListener('click', function() {
            paymentMethods.forEach(m => m.classList.remove('selected'));
            this.classList.add('selected');
            this.querySelector('input[type="radio"]').checked = true;
            
            paymentDetails.forEach(detail => detail.style.display = 'none');
            
            const value = this.querySelector('input[type="radio"]').value;
            if(value === 'cash') {
                document.getElementById('cashDetails').style.display = 'block';
            } else if(value === 'transfer') {
                document.getElementById('transferDetails').style.display = 'block';
            } else {
                document.getElementById('ewalletDetails').style.display = 'block';
            }
        });
    });
    
    // Calculate price
    function calculatePrice() {
        const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
        const hargaPerKg = parseFloat(selectedOption.getAttribute('data-harga')) || 0;
        
        let berat;
        if (detailModeContainer.classList.contains('active')) {
            berat = parseFloat(totalWeight.textContent) || 0;
        } else {
            berat = parseFloat(beratInput.value) || 0;
        }
        
        const biayaLaundry = hargaPerKg * berat;
        const biayaJemput = metodeAntarSelect.value === 'jemput' ? 5000 : 0;
        const total = biayaLaundry + biayaJemput;
        
        hargaPerKgSpan.textContent = formatRupiah(hargaPerKg);
        biayaLaundrySpan.textContent = formatRupiah(biayaLaundry);
        biayaJemputSpan.textContent = formatRupiah(biayaJemput);
        totalHargaSpan.textContent = formatRupiah(total);
    }
    
    // Add event listeners for price calculation
    serviceSelect.addEventListener('change', calculatePrice);
    beratInput.addEventListener('input', calculatePrice);
    
    // Form submission handling
    const pesananForm = document.getElementById('pesananForm');
    pesananForm.addEventListener('submit', function(e) {
        // If we're in detail mode, transfer the values to the main form inputs
        if (detailModeContainer.classList.contains('active')) {
            beratInput.value = beratDetailInput.value;
            document.querySelector('input[name="jenis_pakaian"]').value = jenisDetailInput.value;
            document.querySelector('input[name="jumlah"]').value = jumlahDetailInput.value;
        }
    });
    
    function formatRupiah(angka) {
        return 'Rp ' + angka.toLocaleString('id-ID');
    }
    
    // Trigger initial calculation
    calculatePrice();
    
    // Add initial clothing item in detail mode
    if (detailModeContainer.classList.contains('active')) {
        addClothingItem();
    }
});
</script>

<?php include '../includes/footer.php'; ?>