<?php
// Closes <main> from header.php. HQ phone/email here for FR3 as well as contact.php.
// main.js is loaded last so the hamburger and form checks can find the DOM.
?>
    </main>

    <footer class="site-footer">
        <div class="footer-inner">
            <div>
                <p class="footer-brand">Book &amp; Board</p>
                <p>UK high-street travel since 1975. Four regional branches and a London headquarters.</p>
            </div>
            <div>
                <p><strong>Head office</strong></p>
                <p>14 Strand, London WC2N 5HY</p>
                <p><a href="tel:+442079461975">020 7946 1975</a></p>
                <p><a href="mailto:hq@bookandboard.co.uk">hq@bookandboard.co.uk</a></p>
            </div>
            <div>
                <p><strong>Visit us</strong></p>
                <p><a href="<?php echo escape(app_url('branches.php')); ?>">Branch locations</a></p>
                <p><a href="<?php echo escape(app_url('contact.php')); ?>">Contact the team</a></p>
                <p><a href="<?php echo escape(app_url('admin/admin-login.php')); ?>">Staff login</a></p>
            </div>
        </div>
        <p class="footer-legal">&copy; <?php echo date('Y'); ?> Book &amp; Board Ltd. All rights reserved.</p>
    </footer>

    <script src="<?php echo escape(app_url('assets/js/main.js')); ?>"></script>
</body>
</html>
