<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['users'])) {
    header("Location: ../admin/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Contact</title>

  <!-- Global styles -->
  <link rel="stylesheet" href="/smart-printing-system/assets/css/style.css" />

  <!-- Inline Form and Page Styles -->
  <style>
    /* Body layout for full-height page */
   body {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    main.main-content {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
      background-image: url('/smart-printing-system/assets/images/Background.jpeg');
      background-repeat: no-repeat;
      background-position: center;
      background-size: cover;
      position: relative;
    }

    .auth-container {
      background: #ffffff;
      padding: 30px 25px;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 450px;
      text-align: left;
    }

    .auth-container h2 {
      margin-bottom: 20px;
      color: #0a3d62;
      text-align: center;
      font-size: 1.6rem;
    }

    .auth-container img {
      width: 100%;
      max-height: 200px;
      object-fit: cover;
      border-radius: 10px;
      margin-bottom: 20px;
    }

    .auth-container label {
      font-weight: 600;
      display: block;
      margin-bottom: 6px;
      margin-top: 12px;
    }

    .auth-container input,
    .auth-container textarea {
      width: 100%;
      padding: 12px 15px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 1rem;
      font-family: inherit;
      color: #333;
      resize: vertical;
      box-sizing: border-box;
      margin-bottom: 8px;
    }

    .auth-container input:focus,
    .auth-container textarea:focus {
      border-color: #0a3d62;
      outline: none;
      box-shadow: 0 0 0 2px rgba(10, 61, 98, 0.2);
    }

    .auth-container button {
      width: 100%;
      padding: 12px;
      background-color: #0a3d62;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      margin-top: 10px;
      transition: background 0.3s ease;
    }

    .auth-container button:hover {
      background-color: #064173;
    }

    @media (max-width: 480px) {
      .auth-container {
        padding: 20px 15px;
      }

      .auth-container img {
        max-height: 150px;
      }
    }

    .services-section {
      background-image: url('/smart-printing-system/assets/images/Background.jpeg');
      background-repeat: no-repeat;
      background-position: center;
      background-size: cover;
      height: 100vh;
      position: relative;
    }

    .overlay {
      height: 100%;
      width: 100%;
      padding: 60px 0;
    } 
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="main-content">
  <div class="auth-container">
    <h2>Contact Us</h2>

    <form action="process_contact.php" method="POST">
      <p><strong>Name:</strong> <?= htmlspecialchars($_SESSION['users']['name']) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($_SESSION['users']['email']) ?></p>

      <label for="subject">Subject:</label>
      <input type="text" name="subject" id="subject" required placeholder="Subject of your message" />

      <label for="message">Message:</label>
      <textarea name="message" id="message" rows="5" required placeholder="Write your message here..."></textarea>

      <button type="submit">Send Message</button>
    </form>
  </div>
</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>
