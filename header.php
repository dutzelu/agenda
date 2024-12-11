
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>AGENDĂ Episcopului</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <style>
    .btn.active {
        background-color: #007bff;
        color: white;
    }
    .d-flex span {
        font-weight: bold;
        margin: 0 10px;
    }
    .d-flex a {
        margin: 0 5px;
    }

    .clickable-row {
            cursor: pointer;
        }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var rows = document.querySelectorAll(".clickable-row");
        rows.forEach(function(row) {
            row.addEventListener("click", function() {
                window.location.href = row.dataset.href;
            });
        });
    });
</script>

</head>