<?php
// Menghubungkan dengan database
require 'connection.php';

// Proses Tambah Pemasukan
if (isset($_POST['btnAddPemasukan'])) {
    $jumlah_pemasukan = htmlspecialchars($_POST['jumlah_pemasukan']);
    $keterangan = htmlspecialchars($_POST['keterangan']);
    
    // Query untuk memasukkan data ke tabel pemasukan
    $query = "INSERT INTO pemasukan (jumlah_pemasukan, keterangan) 
              VALUES ('$jumlah_pemasukan', '$keterangan')";
    
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Pemasukan berhasil ditambahkan'); window.location = 'pemasukan.php';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan pemasukan: " . mysqli_error($conn) . "');</script>";
    }
}

// Proses Hapus Pemasukan
if (isset($_GET['delete_id'])) {
    $id_pemasukan = $_GET['delete_id'];
    
    // Query untuk menghapus data berdasarkan ID
    $query = "DELETE FROM pemasukan WHERE id_pemasukan = $id_pemasukan";
    
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Pemasukan berhasil dihapus'); window.location = 'pemasukan.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus pemasukan: " . mysqli_error($conn) . "');</script>";
    }
}

// Pencarian Pemasukan berdasarkan tanggal
$search_query = "";
if (isset($_POST['btnSearch'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    // Validasi format tanggal
    if ($start_date && $end_date) {
        $search_query = "WHERE tanggal_pemasukan BETWEEN '$start_date' AND '$end_date'";
    }
}

// Fungsi untuk mengunduh CSV
// Fungsi untuk mengunduh CSV
if (isset($_GET['download_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="pemasukan.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['No.', 'Keterangan', 'Tanggal Pemasukan', 'Jumlah Pemasukan']);
    
    $result = mysqli_query($conn, "SELECT * FROM pemasukan $search_query ORDER BY tanggal_pemasukan DESC");
    $i = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        // Escape special characters like newline and commas for CSV
        $keterangan = '"' . str_replace('"', '""', $row['keterangan']) . '"'; // Escape double quotes by doubling them
        $tanggal_pemasukan = date("d-m-Y, H:i:s", strtotime($row['tanggal_pemasukan']));
        $jumlah_pemasukan = 'Rp. ' . number_format($row['jumlah_pemasukan']);
        
        fputcsv($output, [
            $i++, 
            $keterangan, 
            $tanggal_pemasukan, 
            $jumlah_pemasukan
        ]);
    }
    fclose($output);
    exit();
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <?php include 'include/css.php'; ?>
    <title>Tambah Pemasukan</title>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    
    <?php include 'include/navbar.php'; ?>
    <?php include 'include/sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm">
                        <h1 class="m-0 text-dark">Tambah Pemasukan</h1>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-6">
                        <!-- Form Pencarian Pemasukan Berdasarkan Tanggal -->
                        <form method="post">
                            <div class="form-group">
                                <label for="start_date">Tanggal Mulai</label>
                                <input type="date" name="start_date" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="end_date">Tanggal Selesai</label>
                                <input type="date" name="end_date" class="form-control">
                            </div>
                            <button type="submit" name="btnSearch" class="btn btn-primary">Cari</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <!-- Form tambah pemasukan jika tombol ditekan -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-6">
                        <button id="toggleFormBtn" class="btn btn-success mb-3">Tambah Pemasukan</button>

                        <div id="formTambahPemasukan" style="display:none;">
                            <div class="card">
                                <div class="card-body">
                                    <form method="post">
                                        <div class="form-group">
                                            <label for="jumlah_pemasukan">Jumlah Pemasukan</label>
                                            <input type="number" name="jumlah_pemasukan" id="jumlah_pemasukan" required class="form-control" placeholder="Rp.">
                                        </div>
                                        <div class="form-group">
                                            <label for="keterangan">Keterangan</label>
                                            <textarea name="keterangan" id="keterangan" required class="form-control"></textarea>
                                        </div>
                                        <button type="submit" name="btnAddPemasukan" class="btn btn-primary">Simpan</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tabel Pemasukan -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="mb-3">
                            <!-- Tombol Print dan Unduh CSV dalam satu baris -->
                            <button onclick="printTable()" class="btn btn-info">Print Tabel</button>
                            <a href="?download_csv=true" class="btn btn-success">Unduh CSV</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-striped" id="pemasukanTable">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Keterangan</th>
                                        <th>Tanggal Pemasukan</th>
                                        <th>Jumlah Pemasukan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Ambil data pemasukan dari database dengan filter pencarian berdasarkan tanggal
                                    $result = mysqli_query($conn, "SELECT * FROM pemasukan $search_query ORDER BY tanggal_pemasukan DESC");
                                    $i = 1;
                                    while ($row = mysqli_fetch_assoc($result)) {
                                    ?>
                                    <tr>
                                        <td><?= $i++; ?></td>
                                        <td><?= $row['keterangan']; ?></td>
                                        <td><?= date("d-m-Y, H:i:s", strtotime($row['tanggal_pemasukan'])); ?></td>
                                        <td>Rp. <?= number_format($row['jumlah_pemasukan']); ?></td>
                                        <td>
                                            <!-- Tombol Hapus -->
                                            <a href="pemasukan.php?delete_id=<?= $row['id_pemasukan']; ?>" 
                                               class="btn btn-danger btn-sm" 
                                               onclick="return confirm('Yakin ingin menghapus pemasukan ini?');">
                                               Hapus
                                            </a>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <strong>&copy; 2025 Bendahara BEM</strong>
        All rights reserved.
    </footer>
</div>

<script>
    document.getElementById('toggleFormBtn').addEventListener('click', function() {
        var form = document.getElementById('formTambahPemasukan');
        if (form.style.display === "none") {
            form.style.display = "block";
        } else {
            form.style.display = "none";
        }
    });

    // Fungsi untuk mencetak tabel
    function printTable() {
        var printContents = document.getElementById('pemasukanTable').outerHTML;
        var originalContents = document.body.innerHTML;

        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
    }
</script>

</body>
</html>
