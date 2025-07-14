<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Smart Printing | Home</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/assets/css/style.css" />
</head>

<body>

<?php include 'includes/header.php'; ?>

<div class="hero-grid">
  <!-- LEFT SECTION -->
  <div class="hero-text">
    <h1>Welcome to Smart Printing</h1>

    <?php if (isset($_SESSION['users'])): ?>
      <p style="font-weight: bold;">👋 Hello, <?= htmlspecialchars($_SESSION['users']['name']) ?>!</p>
    <?php else: ?>
      <p><a href="/smart-printing-system/login.php">🔐 Login</a> to access more features.</p>
    <?php endif; ?>

    <ul class="hero-features">
      <li>
        <div class="icon-box"><i class="fas fa-upload"></i></div>
        <div><strong>Upload Files:</strong> Upload documents to print.</div>
      </li>
      <li>
        <div class="icon-box"><i class="fas fa-credit-card"></i></div>
        <div><strong>Make Payment:</strong> Complete transactions quickly.</div>
      </li>
      <li>
        <div class="icon-box"><i class="fas fa-chart-line"></i></div>
        <div><strong>Track Progress:</strong> Know the status of your print jobs.</div>
      </li>
      <li>
        <div class="icon-box"><i class="fas fa-book"></i></div>
        <div><strong>Enroll in Lessons:</strong> Register for computer classes easily.</div>
      </li>
      <li>
        <div class="icon-box"><i class="fab fa-whatsapp"></i></div>
        <div><strong>WhatsApp Support:</strong> Chat with us directly or email us.</div>
      </li>
    </ul>

    <!-- 🔗 Action Buttons -->
    <div class="hero-buttons">
      <a href="upload.php">📤 Upload Document</a>
      <a href="status.php">📈 Track Status</a>
      <a href="lessons/lessons.php">📚 Enroll in Lesson</a>
      <a href="https://wa.me/265984487611?text=Hi%20Smart%20Printing%2C%20I%20need%20help%20with%20printing" target="_blank" rel="noopener noreferrer">
        💬 WhatsApp Us
      </a>
     </div>
  </div>

  <!-- RIGHT SECTION -->
  <div class="hero-image">
    <div class="caption">
      <h2>Print Smart, Print Fast</h2>
      <p>Submit school projects, business documents, or ID designs — we've got you covered!</p>
    </div>
  </div>
</div>

<!-- ===== FEATURE SECTIONS IN CARD FORMAT ===== -->
<section class="extra-sections container">
  <div class="card-grid">

    <!-- About Us Card -->
    <div class="card">
      <h2>About Us</h2>
      <p>
        At Smart Printing, we believe in automating the boring stuff. From document uploads to professional prints,
        we provide a seamless and secure experience for students, businesses, and individuals alike.
      </p>
    </div>

    <!-- How It Works Card -->
    <div class="card">
      <h2>How It Works</h2>
      <div class="steps-grid">
        <div class="step">
          <i class="fas fa-upload"></i>
          <h3>Upload</h3>
          <p>Send your document to our system.</p>
        </div>
        <div class="step">
          <i class="fas fa-credit-card"></i>
          <h3>Pay</h3>
          <p>Complete payment securely.</p>
        </div>
        <div class="step">
          <i class="fas fa-print"></i>
          <h3>Print</h3>
          <p>Your document is printed instantly.</p>
        </div>
      </div>
    </div>

    <!-- Contact Us Card -->
    <div class="card">
      <h2>Contact Us</h2>
      <p>Need help or have questions? Reach us here:</p>
      <ul class="contact-list">
        <li>
          <i class="fas fa-envelope"></i>
          <a href="https://mail.google.com/mail/?view=cm&fs=1&to=leonardponjemlungu@gmail.com&su=Smart%20Printing%20Inquiry&body=Hi%20Smart%20Printing%2C%20I%20have%20a%20question%20about..." 
             target="_blank" rel="noopener noreferrer">
            leonardponjemlungu@gmail.com
          </a>
        </li>
        <li><i class="fas fa-phone"></i> +265 984 487 611</li>
        <li><i class="fab fa-whatsapp"></i> <a href="https://wa.me/265984487611" target="_blank" rel="noopener noreferrer">WhatsApp Us</a></li>
      </ul>
    </div>

  </div>
</section>
<?php include 'includes/footer.php'; ?>

</body>
</html>
