<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Prueba de Librerías QR</title>
<style>
    body { font-family: Arial; text-align: center; margin: 20px; }
    .qr-container { margin: 30px auto; width: 220px; border: 2px solid #333; padding: 10px; }
    h2 { font-size: 18px; }
</style>
</head>
<body>
<h1>Prueba de Librerías QR</h1>

<!-- ========================= QRCode.js ========================= -->
<div class="qr-container">
    <h2>QRCode.js</h2>
    <div id="qrcode-js"></div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById("qrcode-js"), {
    text: "https://albumdigital.online",
    width: 200,
    height: 200,
    colorDark : "#000000",
    colorLight : "#ffffff",
    correctLevel : QRCode.CorrectLevel.H
});
</script>

<!-- ========================= kjua ========================= -->
<div class="qr-container">
    <h2>kjua</h2>
    <div id="qrcode-kjua"></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/kjua@0.1.1/dist/kjua.min.js"></script>
<script>
var qrKjua = kjua({
    text: 'https://albumdigital.online',
    size: 200,
    fill: '#000000',
    back: '#ffffff'
});
document.getElementById('qrcode-kjua').appendChild(qrKjua);
</script>

<!-- ========================= qrious ========================= -->
<div class="qr-container">
    <h2>qrious</h2>
    <canvas id="qrcode-qrious"></canvas>
</div>
<script src="https://cdn.jsdelivr.net/npm/qrious/dist/qrious.min.js"></script>
<script>
var qrQrious = new QRious({
    element: document.getElementById('qrcode-qrious'),
    value: 'https://albumdigital.online',
    size: 200,
    level: 'H',
    background: '#ffffff',
    foreground: '#000000'
});
</script>

</body>
</html>
