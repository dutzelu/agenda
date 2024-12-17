<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>AGENDĂ Episcopului</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <style>

    .agenda ul {
    
        margin:0;
        padding:10px 18px;
        border:1px solid #CCC;
        border-right:1px solid #CCC;
        border-bottom:none;
        list-style:none;
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



    .navig_agenda a.btn-primary, a.btn-secondary.active {
       background:#c20000;
       border:none;
    }

    .zi_sapt { 
        color: #a94c50; 
        font-size: 18px; 
        background: rgba(208, 208, 208, 0.3); 
        padding: 4px 15px; 
        font-weight: bold; 
        border:1px solid #CCC;
        border-bottom:none;
        margin:0;
        text-transform:capitalize;
        }

    .continut-agenda {
        border-bottom:1px solid #CCC;
    }

    .agenda ul li {margin:5px 0;}

    .agenda .titlu_eveniment {
        margin-left:25px;
    }
    .afis-sapt-luna {
        font-weight:normal!important;
        font-size:22px;
        color:#000;
    }

    a.btn-secondary.active , a.btn-secondary.active   {
        background: #c20000!important;
    }

    .btn-secondary, .btn-secondary:hover {
        background-color: #fa6060;
        color: white;
    }

    .agenda a.btn-secondary:nth-child(2)  {
        margin-left: -6px!important;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        border: none;
    }

    .agenda a.btn-secondary:nth-child(1){
        border: none;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }



    .agenda a.btn-primary:nth-child(2){
        margin-left: -6px;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        border-left: 1px solid #fa6060;
    }

    .agenda a.btn-primary:nth-child(1){
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .navig_agenda .col-md-6 {text-align:center!important;}
    .navig_agenda div.col-md-3:nth-child(3) {text-align:right;}

   ul.zile-suplimentare {
            column-count: 2; /* Divide the list into 2 columns */
            column-gap: 20px; /* Optional: spacing between columns */
            list-style: none;
    }


    /* Calendar afisat gen Google */

        .luna .day-number { font-weight: bold; margin-bottom: 5px; }
        .luna .event {
            font-size: 14px;
        }
        .luna .form-inline { justify-content: center; gap: 10px; }
        .luna table.table td {
            height: 100px;
            width: 14.28%;
            vertical-align: top;
        }

        .luna .event-even {
            background-color:antiquewhite; /* Gri deschis */
            color: #000;
            padding: 2px 5px;
            margin: 10px 0;
            border-radius: 3px;
        }

        .luna .event-odd {
            background-color: #e9ecef; /* Gri foarte deschis */
            color: #333;
            padding: 2px 5px;
            margin: 2px 0;
            border-radius: 3px;
        }

        .luna .bg-info.text-white .day-number {
            font-weight: bold;
            font-size: 1.1em;
        }

        .luna .form-inline .form-group {
            margin: 0 5px;
        }

    /* Mobil */

    @media only screen and (max-width: 600px) {

        .navig_agenda div.col-md-3:nth-child(1), .navig_agenda .col-md-6, .navig_agenda div.col-md-3:nth-child(3) {text-align:center!important;}

        
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