<?php
session_start(); // Pornim sesiunea pentru gestionarea autentificării

// Include fișierul pentru conectarea la baza de date
include "conectaredb.php";

// Variabile pentru mesaje de eroare
$error_message = "";

// Verificăm dacă formularul a fost trimis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        // Interogăm baza de date pentru utilizator
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Verificăm parola (se presupune că este criptată)
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                // Redirectare la pagina principală
                header("Location: admin.php");
                exit;
            } else {
                $error_message = "Parolă incorectă.";
            }
        } else {
            $error_message = "Utilizatorul nu există.";
        }

        $stmt->close();
    } else {
        $error_message = "Toate câmpurile sunt obligatorii.";
    }
}

$conn->close();
include 'header.php';
?>


<body class="bg-light">
<div class="container d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow p-4" style="width: 100%; max-width: 400px;">
        <h2 class="text-center mb-4">Agenda Episcopului</h2>
        <?php if ($error_message): ?>
            <div class="alert alert-danger text-center" role="alert">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <div class="mb-3">
                <label for="username" class="form-label">Utilizator</label>
                <input type="text" name="username" id="username" class="form-control" placeholder="Introduceți utilizatorul" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Parolă</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Introduceți parola" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>
</div>
<?php include 'footer.php';?>