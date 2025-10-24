<?php
// Destroy session securely
session_unset();
session_destroy();

// Start new session for flash message
session_start();
setFlash('success', 'You have been logged out successfully.');

redirect('/login');