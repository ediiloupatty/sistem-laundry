<?php
// File: includes/footer.php
// Footer untuk semua halaman
?>

    </div> <!-- End of main-content -->
    
    <footer style="background: #333; color: white; text-align: center; padding: 15px; margin-top: 50px;">
        <p>&copy; <?php echo date('Y'); ?> Sistem Laundry. All rights reserved.</p>
    </footer>
    
    <script>
        // Script umum untuk semua halaman
        
        // Auto-hide alerts setelah 5 detik
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 5000);
            });
        });
    </script>
</body>
</html>