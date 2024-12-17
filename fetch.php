<?php
// Conectare la baza de date
$host = "localhost";
$user = "root";
$pass = "";
$db = "agenda";

$conn = new mysqli($host, $user, $pass, $db);

// Verificare conexiune
if ($conn->connect_error) {
    die("Conexiunea a eșuat: " . $conn->connect_error);
}

// Textul sursă (text HTML)
$htmlText = <<<EOT
<p style="text-align: center;"><strong><span style="color: red;"><a id="ianuarie"></a>IANUARIE - GERAR</span></strong></p>
<p style="text-align: center;"><span style=";color: red;">Această lună are 31 de zile
Ziua are 10 ceasuri și noaptea 14 ceasuri</span></p>
&nbsp;
<p style="text-align: justify;"><span style="color: red;">1.</span><strong><span style=";color: red;">(†) Tăierea-împrejur cea după trup a Domnului</span></strong><span style=";color: red;">; †) Sf. Ier. Vasile cel Mare, arhiepiscopul Cezareei Capadociei, şi mama sa, Sf. Emilia <em>(Anul Nou. Tedeum. Harți) </em></span></p>
<p style="text-align: justify;">2. <em>Înainte-prăznuirea Botezului Domnului</em>; Sf. Ier. Silvestru, episcopul Romei; Sf. Cuv. Serafim de Sarov</p>
<p style="text-align: justify;">3. Sf. Proroc Maleahi; Sf. Mc. Gordie <em>(Zi aliturgică. Harți)</em></p>
<p style="text-align: justify;">4. Soborul Sf. 70 de Apostoli; Sf. Cuv. Teoctist din Sicilia, Nichifor cel Lepros și Apolinaria</p>
<p style="text-align: justify;">5. Sf. Mc. Teopempt şi Teonas; Sf. Cuv. Sinclitichia (<em>Ajunul Botezului Domnului. Post</em>)</p>
<p style="text-align: justify;"><span style=";color: red;">6. <strong>(†) Botezul Domnului</strong> (<em>Dumnezeiasca Arătare - Boboteaza</em>) </span></p>
<p style="text-align: justify;"><span style=";color: red;">7. <strong>† Soborul Sf. Proroc Ioan Botezătorul</strong></span></p>
<p style="text-align: justify;">8. Sf. Cuv. Gheorghe Hozevitul; Sf. Cuv. Domnica</p>
<p style="text-align: justify;">9. Sf. Mc. Polieuct; Sf. Ier. Petru, episcopul Sevastiei</p>
<p style="text-align: justify;">10. Sf. Ier. Grigorie, episcopul Nyssei; <strong><span style="color: #0070c0;">†) Sf. Cuv. Antipa de la Calapodeşti;</span></strong> Sf. Ier. Dometian, episcopul Melitinei</p>
<p style="text-align: justify;">11. † Sf. Cuv. Teodosie, începătorul vieţii călugăreşti de obşte din Palestina; Sf. Cuv. Vitalie</p>
<p style="text-align: justify;">12. Sf. Mc. Tatiana diaconiţa şi Eutasia</p>
<p style="text-align: justify;">13. <strong><span style="color: #0070c0;">† Sf. Mc. Ermil şi Stratonic</span></strong>; Sf. Ier. Iacob, episcop de Nisibe</p>
<p style="text-align: justify;">14. <em>Odovania praznicului Botezului Domnului</em>; Sf. Cuv. Mucenici din Sinai şi Rait; Sf. Nina, luminătoarea Georgiei</p>
<p style="text-align: justify;">15. Sf. Cuv. Pavel Tebeul şi Ioan Colibaşul</p>
<p style="text-align: justify;">16. Cinstirea lanţului Sf. Ap. Petru; Sf. Mc. Danact citeţul</p>
<p style="text-align: justify;">17. †) Sf. Cuv. Antonie cel Mare; Sf. Cuv. Antonie cel Nou din Veria</p>
<p style="text-align: justify;">18. † Sf. Ier. Atanasie şi Chiril, arhiepiscopii Alexandriei</p>
<p style="text-align: justify;">19. Sf. Cuv. Macarie cel Mare şi Macarie Alexandrinul; Sf. Ier. Marcu, mitropolitul Efesului; Sf. Mc. Eufrasia</p>
<p style="text-align: justify;">20. †) Sf. Cuv. Eftimie cel Mare; <strong><span style="color: #0070c0;">Sf. Mc. In, Pin şi Rim</span></strong>; Sf. Mc. Eusebiu</p>
<p style="text-align: justify;">21. Sf. Cuv. Maxim Mărturisitorul; Sf. Mc. Neofit; Sf. Mc. Agnia din Roma</p>
<p style="text-align: justify;">22. Sf. Ap. Timotei; Sf. Cuv. Mc. Anastasie Persul</p>
<p style="text-align: justify;">23. Sf. Sfinţit Mc. Clement, episcopul Ancirei; Sf. Mc. Agatanghel; Sf. Părinţi de la Sinodul al VI-lea Ecumenic</p>
<p style="text-align: justify;">24. Sf. Cuv. Xenia din Roma; Sf. Xenia din Petersburg (<em>Tedeum</em>)</p>
<p style="text-align: justify;">25. †) Sf. Ier. Grigorie Teologul, arhiepiscopul Constantinopolului; <strong><span style="color: #0070c0;">†) Sf. Ier. Bretanion, episcopul Tomisului</span></strong></p>
<p style="text-align: justify;">26. <strong><span style="color: #0070c0;">†) Sf. Ier. Iosif cel Milostiv, mitropolitul Moldovei</span></strong>; Sf. Cuv. Xenofont, Maria, Arcadie şi Ioan</p>
<p style="text-align: justify;">27. † Aducerea moaştelor Sf. Ier. Ioan Gură de Aur</p>
<p style="text-align: justify;">28. Sf. Cuv. Efrem Sirul, Isaac Sirul, Paladie şi Iacob Sihastrul</p>
<p style="text-align: justify;">29. Aducerea moaştelor Sf. Sfinţit Mc. Ignatie Teoforul; Sf. Mc. Filotei</p>
<p style="text-align: justify;"><span style="color: red;">30. <strong>†) Sfinţii Trei Ierarhi: Vasile cel Mare, Grigorie Teologul şi Ioan Gură de Aur</strong>; Sf. Sfinţit Mc. Ipolit, episcopul Romei</span></p>
<p style="text-align: justify;">31. Sf. Mc. doctori fără de arginţi Chir şi Ioan</p>
EOT;

// Pregătim query-ul SQL pentru inserare
$stmt = $conn->prepare("INSERT INTO calendar_date_fixe (luna, zi, sfinti, icoana) VALUES (?, ?, ?, '')");

// Luna ianuarie
$luna = 1;

// Regex pentru a extrage ziua și textul asociat
$pattern = '/<span style="color: red;">(\d{1,2})\.<\/span>|(\d{1,2})\.\s(.*?)<\/p>/s';
preg_match_all($pattern, $htmlText, $matches, PREG_SET_ORDER);

// Iterăm și introducem în baza de date
foreach ($matches as $match) {
    $zi = isset($match[1]) && $match[1] !== "" ? $match[1] : $match[2];
    $sfinti = trim($match[3]);

    // Protejează textul pentru inserare
    $stmt->bind_param("iis", $luna, $zi, $sfinti);
    $stmt->execute();
}

echo "Datele au fost extrase și inserate cu succes!";

// Închidem conexiunea
$stmt->close();
$conn->close();
?>
