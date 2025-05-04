<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Deezhole</title>
  <style>
    body { margin: 0; overflow: hidden; background: #000; }
    canvas { display: block; }
    #hud {
      position: absolute;
      top: 10px;
      left: 10px;
      color: white;
      font-family: Arial, sans-serif;
      font-size: 18px;
      background: rgba(0, 0, 0, 0.5);
      padding: 8px 12px;
      border-radius: 10px;
    }
    a{
        position: fixed;
        bottom: 30px;
        left: 50%;
        transform: translate(-50%);
        background: #fff;
        color: #000;
        padding: 5px 30px;
        border-radius: 100px;
        font-family: 'Poppins', sans-serif;
        text-decoration: none;
        font-weight: bold
    }
  </style>
</head>
<body>
    <a href="/">Voltar</a>
  <div id="hud">Massa: 0</div>
  <canvas id="game"></canvas>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    const canvas = $('#game')[0];
    const ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;

    const mapSize = 5000;
    const gridSize = 50;
    const maxSize = 300; // Tamanho máximo do buraco negro

    let player = {
      x: mapSize / 2,
      y: mapSize / 2,
      r: 30,
      color: "black",
      vx: 0,
      vy: 0,
      mass: 0
    };

    let mouse = { x: canvas.width / 2, y: canvas.height / 2 };
    let foods = [];

    function spawnFood(count) {
      for (let i = 0; i < count; i++) {
        const mass = Math.random() * 5 + 1;
        foods.push({
          x: Math.random() * mapSize,
          y: Math.random() * mapSize,
          r: mass * 2,
          mass: mass,
          color: `hsl(${Math.random() * 360}, 100%, 80%)`
        });
      }
    }

    spawnFood(100);

    $(document).on('mousemove', function(e) {
      mouse.x = e.clientX;
      mouse.y = e.clientY;
    });

    function drawStar(x, y, r, color) {
      const gradient = ctx.createRadialGradient(x, y, r * 0.2, x, y, r);
      gradient.addColorStop(0, "#fff");
      gradient.addColorStop(1, color);
      ctx.beginPath();
      ctx.arc(x, y, r, 0, Math.PI * 2);
      ctx.fillStyle = gradient;
      ctx.fill();
    }

    function drawBlackHole(x, y, r) {
      // Núcleo
      const coreGradient = ctx.createRadialGradient(x, y, 0, x, y, r);
      coreGradient.addColorStop(0, '#000');
      coreGradient.addColorStop(1, '#222');
      ctx.beginPath();
      ctx.arc(x, y, r, 0, Math.PI * 2);
      ctx.fillStyle = coreGradient;
      ctx.fill();

      // Disco de acreção
      const diskRadius = r * 2.2;
      const diskGradient = ctx.createRadialGradient(x, y, r * 1.2, x, y, diskRadius);
      diskGradient.addColorStop(0, 'rgba(0, 0, 0, 0.6)');
      diskGradient.addColorStop(1, 'rgba(255, 255, 255, 0)');
      ctx.beginPath();
      ctx.arc(x, y, diskRadius, 0, Math.PI * 2);
      ctx.fillStyle = diskGradient;
      ctx.fill();
    }

    function drawGrid() {
      ctx.save();
      ctx.translate(-player.x + canvas.width / 2, -player.y + canvas.height / 2);
      ctx.strokeStyle = '#111';
      ctx.lineWidth = 1;

      const gravityFactor = 0.01; // Ajuste a força da distorção

      for (let x = 0; x <= mapSize; x += gridSize) {
        let distX = Math.abs(x - player.x);
        let distY = Math.abs(x - player.y);
        let dist = Math.sqrt(distX * distX + distY * distY);
        let curve = dist * gravityFactor;
        ctx.beginPath();
        ctx.moveTo(x + curve, 0);
        ctx.lineTo(x + curve, mapSize);
        ctx.stroke();
      }

      for (let y = 0; y <= mapSize; y += gridSize) {
        let distX = Math.abs(y - player.x);
        let distY = Math.abs(y - player.y);
        let dist = Math.sqrt(distX * distX + distY * distY);
        let curve = dist * gravityFactor;
        ctx.beginPath();
        ctx.moveTo(0, y + curve);
        ctx.lineTo(mapSize, y + curve);
        ctx.stroke();
      }

      ctx.restore();
    }

    function applyGravity() {
      for (let f of foods) {
        let dx = player.x - f.x;
        let dy = player.y - f.y;
        let distance = Math.hypot(dx, dy);
        if (distance < 1) continue; // Evita divisões por 0

        // A força gravitacional é inversamente proporcional ao quadrado da distância
        let force = player.mass / (distance * distance); // Fórmula simplificada de atração gravitacional
        let angle = Math.atan2(dy, dx);

        // Atualiza a posição da estrela com a gravidade
        f.x += Math.cos(angle) * force;
        f.y += Math.sin(angle) * force;
      }
    }

    function update() {
      let centerX = canvas.width / 2;
      let centerY = canvas.height / 2;
      let dx = mouse.x - centerX;
      let dy = mouse.y - centerY;
      let dist = Math.sqrt(dx * dx + dy * dy);

      if (dist > 1) {
        let angle = Math.atan2(dy, dx);
        let speed = 5 / Math.sqrt(player.r);
        player.vx = Math.cos(angle) * speed;
        player.vy = Math.sin(angle) * speed;
        player.x += player.vx;
        player.y += player.vy;

        player.x = Math.max(player.r, Math.min(mapSize - player.r, player.x));
        player.y = Math.max(player.r, Math.min(mapSize - player.r, player.y));
      }

      // Aumentar o raio de captura baseado na massa do buraco negro
      let captureRadius = player.r * 2; // O raio de captura é proporcional ao raio do buraco negro

      for (let i = foods.length - 1; i >= 0; i--) {
        let f = foods[i];
        let d = Math.hypot(player.x - f.x, player.y - f.y);
        if (d < captureRadius) { // O buraco negro engole as comidas dentro do raio de captura
          player.mass += f.mass;  // Aumenta a massa
          if (player.r < maxSize) {  // O buraco negro cresce até o tamanho máximo
            player.r = Math.min(maxSize, player.r + f.mass * 0.1); // Cresce a massa até o limite
          }
          foods.splice(i, 1);
        }
      }

      if (foods.length < 50) spawnFood(20);

      $('#hud').text('Massa: ' + player.mass.toFixed(1));
    }

    function render() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      drawGrid();

      const offsetX = canvas.width / 2 - player.x;
      const offsetY = canvas.height / 2 - player.y;

      for (let f of foods) {
        drawStar(f.x + offsetX, f.y + offsetY, f.r, f.color);
      }

      drawBlackHole(canvas.width / 2, canvas.height / 2, player.r);
    }

    function gameLoop() {
      applyGravity(); // Aplica a gravidade nas estrelas
      update();
      render();
      requestAnimationFrame(gameLoop);
    }

    gameLoop();
  </script>
</body>
</html>
