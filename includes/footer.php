<footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>About Us</h5>
                    <p>Making a positive impact in communities worldwide.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="/" class="text-white">Home</a></li>
                        <li><a href="/donate" class="text-white">Donate</a></li>
                        <li><a href="/volunteer" class="text-white">Volunteer</a></li>
                        <li><a href="/gallery" class="text-white">Gallery</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact</h5>
                    <p><?= e(getSetting('office_address', 'NGO Office Address')) ?></p>
                </div>
            </div>
            <hr class="bg-light">
            <div class="text-center">
                <p class="mb-0">&copy; <?= date('Y') ?> NGO. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>