<?php
// LOGIN : sequoia
// PWD : mdg69130
?>
<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" crossorigin="" href="main.css">
</head>
<body>
  <div id="depart">
    <p>Bienvenue sur l'outil de présentation de module Sequoia.<br /><br />
      Une fois lancé vous pouvez appuyer sur la touche "echap" ou sur le bouton de déconnexion pour sortir du module.<br /><br />
      Pour déposer un commentaire cliquer sur l'icône en haut à gauche et postez ce que vous désirez. :)</p>
    <img src="comment.png" class="icon" />
    <input type="text" id="login" placeholder="Login" />
    <input type="password" id="password" placeholder="password" />

    <button id="fullscreen-btn" onclick="checkInfo()">Commencer</button>

    <div id="listeProject">
      <div class="listMyProjects">
      </div>
      <!-- <button id="fullscreen-btn1" onclick="toggleFullscreen()">Commencer</button> -->
      <button id="fullscreen-btn2" onclick="logout()">Déconnexion</button>
    </div>
    <p id="error-message" style="color: red;"></p>
  </div>

  <div id="addComment">
    <img src="comment.png" />

    <div class="formulaire">

      <!-- LOAD COMMENTS -->
      <div class="wrapperComments">
        <div class="comment">
          <label>nom</label>
          <div class="description">description</div>
        </div>  
        ...
        <div class="answers">
          <textarea></textarea>
          <button>Envoyer</button>
        </div>
      </div>

      <!-- DISPLAY POPUP -->
      <div class="popupDrawing">
        <button id="closePop">x</button>
        <canvas id="myCanvas"></canvas>
        <button id="save">Enregistrer</button>
      </div>
    </div>
  </div>

  <iframe width="100%" height="100%" src="pleasewait.html" id="testIframe"></iframe>
  <script src="jquery-1.12.4.min.js" type="text/javascript"></script>
  <script src="api.js" type="text/javascript"></script>
  <script src="scorm.js" type="text/javascript"></script>
  <script>
	  var iframeload = document.getElementById('testIframe');
	  var depart = document.getElementById('depart');
    var errorMessage = document.getElementById('error-message');
	  var login = document.getElementById('login');
    var password = document.getElementById('password');
	  var fullscreenbtn = document.getElementById('fullscreen-btn');
    var fullscreenbtn1 = document.getElementById('fullscreen-btn1');
    var listeProject = document.getElementById('listeProject');

    const TOKEN_KEY = 'sequoia_auth_token';
    const EXPIRY_KEY = 'sequoia_auth_expiry';
    const SEQ_LOGIN_KEY = 'sequoia_login_key';
    let myLocation = '';
    let myLocationInfo;
    let myPage = '';

    // Sélectionne l'image et le formulaire
    const commentImg = document.querySelector('#addComment img');
    const formulaire = document.querySelector('.formulaire');

    // Ajoute un écouteur de clic sur l'image
    commentImg.addEventListener('click', function() {
      formulaire.classList.toggle('on');
    });

    // Vérifier au chargement si l'utilisateur est déjà authentifié
    window.addEventListener('DOMContentLoaded', () => {
      const token = localStorage.getItem(TOKEN_KEY);
      const expiry = localStorage.getItem(EXPIRY_KEY);

      if (token && expiry) {
        // Vérifier si le token est toujours valide côté serveur
        fetch('check-token.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `token=${encodeURIComponent(token)}&expiry=${encodeURIComponent(expiry)}`
        })
        .then(response => response.json())
        .then(data => {
          if (data.valid) {
            // Token valide : afficher directement l'iframe
            password.style.display = "none";
            login.style.display = "none";
            fullscreenbtn.style.display = "none";

            
            
            const userNom = localStorage.getItem(SEQ_LOGIN_KEY) || 'Anonyme';
            $.post( "getcomment.php", { func: "getMyProjects", client: userNom }, function( data ) {
              // listMyProjects
              console.log(data);
              displayProjects(data.projects);
              listeProject.style.display = "block";
            }, "json");

            
          } else {
            // Token invalide : supprimer et afficher le login
            localStorage.removeItem(TOKEN_KEY);
            localStorage.removeItem(EXPIRY_KEY);
          }
        })
        .catch(() => {
          // Erreur réseau : afficher le login
          localStorage.removeItem(TOKEN_KEY);
          localStorage.removeItem(EXPIRY_KEY);
        });
      }
    });

    function getModule(nom) {
      myLocation = nom;  
      // myLocationInfo = getModuleInfo(myLocation+':cmi.location');  
      myLocationInfo = getModuleInfo('cmi.location');  
      setInterval(() => {
        // myLocationInfo = getModuleInfo(myLocation+':cmi.location'); 
        myLocationInfo = getModuleInfo('cmi.location'); 
        // console.log(myLocationInfo);
        if(myPage != myLocationInfo.pageId) {
          formulaire.classList.remove('on');
          myPage = myLocationInfo.pageId;
          getMyForm();
        }
      }, 500); 
    }

    // intro_1
    function getMyForm() {
      // Construction de l'URL et des paramètres
      const url = "getcomment.php";
      const params = {
        func: "allcomment",
        id_module: myLocation,
        id_page: myPage
      };

      // Requête POST avec fetch
      fetch(url, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams(params).toString() 
      })
      .then(response => {
        if (!response.ok) {
          throw new Error(`Erreur HTTP: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          // Organiser les commentaires en arbre
          const commentsTree = buildCommentsTree(data.comments);
          // Afficher les commentaires
          displayComments(commentsTree);
        } else {
          console.log(data.message);
        }
      })
      .catch(error => {
        console.error("Erreur lors de la requête:", error);
      });
    }

    function getModuleInfo(nom) {
      // 1. Récupérer la valeur depuis localStorage
      const localInfoString = localStorage.getItem(nom);

      // 2. Vérifier si la valeur existe
      if (localInfoString === null) {
        console.warn(`Aucune donnée trouvée dans localStorage pour la clé "${nom}"`);
        return null; // ou une valeur par défaut
      }

      // 3. Parser la chaîne JSON en objet JavaScript
      try {
        const localInfo = JSON.parse(localInfoString);
        return localInfo;
      } catch (error) {
        console.error(`Erreur lors du parsing JSON pour la clé "${nom}":`, error);
        return null; // ou une valeur par défaut
      }
    }

    function checkInfo() {
      const login = document.getElementById('login').value;
      const password = document.getElementById('password').value;

      if (!login || !password) {
        errorMessage.textContent = 'Veuillez remplir tous les champs.';
        return;
      }

      // Appel à l'API d'authentification
      fetch('auth.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `login=${encodeURIComponent(login)}&password=${encodeURIComponent(password)}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          errorMessage.textContent = '';
          localStorage.setItem(SEQ_LOGIN_KEY, login);
          localStorage.setItem(TOKEN_KEY, data.token);
          localStorage.setItem(EXPIRY_KEY, data.expiry);
          location.reload();
        } else {
          errorMessage.textContent = data.message || 'Erreur d\'authentification';
        }
      })
      .catch(error => {
        errorMessage.textContent = 'Erreur de connexion au serveur.';
        console.error('Erreur:', error);
      });
    }
    
    function toggleFullscreen() {
      depart.style.display = "none";
      if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(err => {
          alert("Erreur : " + err.message);
        });
      } else {
        document.exitFullscreen();
      }
    }

    function buildCommentsTree(comments) {
      if (!comments || comments.length === 0) {
        return [];
      }

      // 1. Séparer les commentaires de base (reply = 0) et les réponses
      const baseComments = comments.filter(c => c.reply === 0 || c.reply === null || c.reply === "0");
      const replies = comments.filter(c => c.reply !== 0 && c.reply !== null && c.reply !== "0");

      // 2. Trier les commentaires de base par time (du plus ancien au plus récent)
      baseComments.sort((a, b) => new Date(a.time) - new Date(b.time));

      // 3. Associer les réponses à leurs parents
      const commentsMap = new Map();

      // Ajouter les commentaires de base à la map
      baseComments.forEach(comment => {
        commentsMap.set(comment.ID, { ...comment, children: [] });
      });

      // Ajouter les réponses comme enfants de leurs parents
      replies.forEach(reply => {
        const parentId = reply.reply;
        if (commentsMap.has(parentId)) {
          commentsMap.get(parentId).children.push(reply);
          // Trier les enfants par time (du plus ancien au plus récent)
          commentsMap.get(parentId).children.sort((a, b) => new Date(a.time) - new Date(b.time));
        }
      });

      // 4. Retourner les commentaires de base avec leurs enfants
      return Array.from(commentsMap.values());
    }

    function displayComments(commentsTree) {
      const wrapperComments = document.querySelector('.wrapperComments');

      // Vider le conteneur avant de réafficher
      wrapperComments.innerHTML = '';

      if (commentsTree.length === 0) {
        // wrapperComments.innerHTML = '<p>Aucun commentaire pour cette page.</p>';

        const answerForm = document.createElement('div');
        wrapperComments.innerHTML = `
          <div class="answer-form"><textarea placeholder="Ajouter un commentaire..."></textarea>
          <button onclick="postAnswer(0)">Envoyer</button></div>
        `;
        commentDiv.appendChild(answerForm);

        // Bouton "fermer" : visible pour l'auteur du commentaire ou le super admin
        if (userNom == comment.nom || userNom == 'sequoia') {
            const closeBtn = document.createElement('button');
            closeBtn.textContent = 'Fermer ce commentaire';
            closeBtn.className = 'closeComment';
            closeBtn.addEventListener('click', () => closeComment(comment.ID));
            commentDiv.appendChild(closeBtn);
        }
        // Ajouter le commentaire au conteneur
        wrapperComments.appendChild(commentDiv);
        return;
      }

      // Parcourir chaque commentaire de base et ses enfants
      commentsTree.forEach(comment => {
        // Créer le conteneur du commentaire
        const commentDiv = document.createElement('div');
        commentDiv.className = 'comment';
        commentDiv.dataset.id = comment.ID; // Stocker l'ID pour les réponses

        const userNom = localStorage.getItem(SEQ_LOGIN_KEY) || 'Anonyme';

        let classMine = "notmine";
        if(userNom == comment.nom) {
          classMine = "mine";
        }

        const myNameFile = comment.screenshot.slice(0, -4);
        // Ajouter le nom et la description
        commentDiv.innerHTML = `
          <div class="first ${classMine}"><div class="textes"><label>${comment.nom || 'Anonyme'}</label>
          <div class="description">${comment.description}</div></div>
          <div class="screenshot"><img src="./screenshot/${myfolder}/${myNameFile}_thumb.jpg" class="popImg" data-img="./screenshot/${myfolder}/${comment.screenshot}" /></div>
          <div class="time">${new Date(comment.time).toLocaleString('fr-FR')}</div></div>
        `;
        

        // Ajouter les réponses (children) si elles existent
        if (comment.children && comment.children.length > 0) {
          const answersDiv = document.createElement('div');
          answersDiv.className = 'answers';

          comment.children.forEach(child => {
            const childDiv = document.createElement('div');
            if(userNom == child.nom) {
              classMine = "mine";
            } else {
              classMine = "notmine";
            }
            childDiv.className = 'comment child '+classMine;
            childDiv.innerHTML = `
              <label>${child.nom || 'Anonyme'}</label>
              <div class="description">${child.description}</div>
              <div class="time">${new Date(child.time).toLocaleString('fr-FR')}</div>
            `;
            answersDiv.appendChild(childDiv);
          });

          commentDiv.appendChild(answersDiv);
        }

        // Ajouter le formulaire de réponse (à développer plus tard)
        const answerForm = document.createElement('div');
        answerForm.className = 'answer-form';
        answerForm.innerHTML = `
          <textarea placeholder="Répondre à ce commentaire..."></textarea>
          <button onclick="postAnswer(${comment.ID})">Envoyer</button>
        `;
        commentDiv.appendChild(answerForm);

        // Bouton "fermer" : visible pour l'auteur du commentaire ou le super admin
        if (userNom == comment.nom || userNom == 'sequoia') {
            const closeBtn = document.createElement('button');
            closeBtn.textContent = 'Fermer ce commentaire';
            closeBtn.className = 'closeComment';
            closeBtn.addEventListener('click', () => closeComment(comment.ID));
            commentDiv.appendChild(closeBtn);
        }
        // Ajouter le commentaire au conteneur
        wrapperComments.appendChild(commentDiv);

        $('.popImg').off().on('click', function(){
          $('.popupDrawing').addClass('on');
          openPopupAndStartDraw($(this).data('img'));
        });
      });
    }

    let myfolder = '';
    function displayProjects(allprojects) {
      allprojects.forEach(function(project){
        $('.listMyProjects').append('<button class="proj" data-proj="'+project.folder+'">'+project.nom+'</button>');
      });
      bindProjectList();
    }

    function bindProjectList() {
      $('.proj').off().on('click', function(){
        var myProj = $(this);
        myinter = setInterval(function(){
          // if(learner_id != 0){
            clearInterval(myinter);
            // var url = "en/index.html?StandAlone=yes";
            var projectFolder = myProj.attr('data-proj');
            myfolder = projectFolder;
            var url = "./module/"+projectFolder+"/index.html";
            var iframe = document.getElementById('testIframe');
            // Déclenche toggleFullscreen quand l'iframe a fini de charger
            iframe.addEventListener('load', function handler() {
                iframe.removeEventListener('load', handler); // ne re-déclencher qu'une fois par clic
                getModule(projectFolder);
                toggleFullscreen();
            });

            iframe.src = url;
            $('#listeProject').html('<img src="loading.gif" />');
          // }
        }, 500);
      });
    }

    async function postAnswer(parentId) {
      let commentDiv = document.querySelector(`.comment[data-id="${parentId}"]`);
      let screenshotBase64 = null;
      if(parentId == 0) {
        commentDiv = document.querySelector(`.answer-form`);
        try {
          screenshotBase64 = await captureScreen();
        } catch (error) {
          console.error("Erreur lors de la capture d'écran :", error);
          alert("Impossible de capturer l'écran. Veuillez réessayer.");
          return;
        }
      }
      const textarea = commentDiv.querySelector('textarea');
      const answerText = textarea.value.trim();

      if (!answerText) { 
        alert('Veuillez écrire une réponse.');
        return;
      }

      // Récupérer le nom de l'utilisateur (ex: depuis localStorage)
      const userNom = localStorage.getItem(SEQ_LOGIN_KEY) || 'Anonyme';

      const formData = new FormData();
      formData.append('func', 'addcomment');
      formData.append('folder', myfolder);
      formData.append('id_module', myLocation);
      formData.append('id_page', myPage);
      formData.append('nom', userNom);
      formData.append('description', answerText);
      formData.append('reply', parentId);
      if (screenshotBase64) {
        // Convertir base64 en Blob et l'ajouter à FormData
        const blob = await fetch(screenshotBase64).then(res => res.blob());
        formData.append('screenshot', blob, `screenshot_${Date.now()}.png`);
      }

      // Envoyer la réponse au serveur
      fetch('getcomment.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          textarea.value = ''; // Vider le champ
          getMyForm(); // Rafraîchir les commentaires
        } else {
          alert('Erreur: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de connexion.');
      });
    }

    async function captureIframeScreen() {
      const win = iframeload.contentWindow;
      const doc = iframeload.contentDocument;
      var body = $('#testIframe').contents().find('body')[0];

      // 1. Capture du DOM de l'iframe (les zones vidéo seront noires)
      const domCanvas = await html2canvas(body, {
        background: "#fff",
        scale: 1,
        useCORS: true,
        logging: false,
        x: win.scrollX,
        y: win.scrollY,
        width: iframeload.clientWidth,
        height: iframeload.clientHeight,
        useCORS: true
      });

      // 2. Redessiner chaque frame vidéo par-dessus sa zone noire
      const ctx = domCanvas.getContext('2d');
      doc.querySelectorAll('video').forEach((video) => {
        if (video.readyState < 2) return; // pas encore assez chargée
        const r = video.getBoundingClientRect(); // coords relatives au viewport de l'iframe
        ctx.drawImage(video, r.left, r.top, r.width, r.height);
      });

      return domCanvas.toDataURL('image/png');
    }
    async function captureScreen() {
      $('#addComment').hide();
      const stream = await navigator.mediaDevices.getDisplayMedia({
        video: { displaySurface: 'browser' },
        preferCurrentTab: true,        // Chrome pré-sélectionne l'onglet courant
        selfBrowserSurface: 'include',
        surfaceSwitching: 'exclude',
        monitorTypeSurfaces: 'exclude',
      });
      const track = stream.getVideoTracks()[0];

      const video = document.createElement('video');
      video.srcObject = stream;
      await video.play();

      const canvas = document.createElement('canvas');
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      canvas.getContext('2d').drawImage(video, 0, 0);

      track.stop();  // libère la capture immédiatement
      $('#addComment').show();
      return canvas.toDataURL('image/png');
    }

    function logout() {
      localStorage.removeItem(SEQ_LOGIN_KEY);
      localStorage.removeItem(TOKEN_KEY);
      localStorage.removeItem(EXPIRY_KEY);
      location.reload();
    }

    function getV2Name(image) {
        const dot = image.lastIndexOf('.');
        const slash = image.lastIndexOf('/');
        if (dot === -1 || dot < slash) return null; // pas d'extension trouvée
        return image.slice(0, dot) + '_2' + image.slice(dot);
    }

    function openPopupAndStartDraw(image) {
        // Le bouton "save" envoie TOUJOURS le nom original :
        // c'est PHP qui ajoutera "_2", donc on écrase toujours la même version annotée
        $('#save').attr('data-img', image);

        const v2 = getV2Name(image);

        // Test d'existence : si "_2" charge -> on dessine dessus, sinon -> originale
        const test = new Image();
        test.onload  = () => startDraw(v2);
        test.onerror = () => startDraw(image);
        test.src = v2;
    }

    function startDraw(image) {
      const img = new Image();
      img.onload = () => {
        const canvas = document.getElementById('myCanvas');
        canvas.width  = img.width;
        canvas.height = img.height;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0);

        ctx.lineWidth = 3;
        ctx.lineCap = 'round';
        ctx.strokeStyle = 'red';

        // Convertit la position de la souris en coordonnées bitmap du canvas
        // en tenant compte de l'échelle ET du letterbox de object-fit: contain
        function getPos(e) {
            const r = canvas.getBoundingClientRect();

            // Échelle réelle du bitmap dans la boîte affichée (object-fit: contain)
            const scale  = Math.min(r.width / canvas.width, r.height / canvas.height);
            const dispW  = canvas.width  * scale;   // taille affichée du bitmap
            const dispH  = canvas.height * scale;
            const offX   = (r.width  - dispW) / 2;  // marges left/right du contain
            const offY   = (r.height - dispH) / 2;  // marges top/bottom du contain

            return {
                x: (e.clientX - r.left - offX) / scale,
                y: (e.clientY - r.top  - offY) / scale
            };
        }

        let drawing = false;

        canvas.addEventListener('mousedown', (e) => {
            drawing = true;
            const p = getPos(e);
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
        });

        canvas.addEventListener('mouseup', () => {
            drawing = false;
            ctx.beginPath();
        });

        canvas.addEventListener('mousemove', (e) => {
            if (!drawing) return;
            const p = getPos(e);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
        });
      };
      img.src = image + '?v=' + Date.now();

      // Envoi vers le serveur
      $('#save').off().on('click', function() {
          const canvas = document.getElementById('myCanvas');
          const myNameFile = $('#save').attr('data-img');
          $('.popupDrawing').addClass('waiting');
          canvas.toBlob((blob) => {
              const fd = new FormData();
              fd.append('image', blob, myNameFile);
              fd.append('folder', myfolder);
              fetch('save_image.php', { method: 'POST', body: fd })
                  .then(r => r.json())
                  .then(d => console.log('Enregistré sous :', d.filename))
                  .then(d =>  $('.popupDrawing').removeClass('on waiting'));
          }, 'image/png');
      });
      $('#closePop').off().on('click', function() {
        $('.popupDrawing').removeClass('on waiting');
      });
    }

    async function closeComment(id) {
        const fd = new FormData();
        fd.append('func', 'closecomment');
        fd.append('id', id);

        try {
            const response = await fetch('getcomment.php', { method: 'POST', body: fd });
            const data = await response.json();
            if (data.success) {
                getMyForm(); // Rafraîchir la liste (le commentaire fermé disparaîtra / changera d'aspect)
            } else {
                alert('Erreur : ' + data.message);
            }
        } catch (error) {
            console.error('Erreur:', error);
            alert('Erreur de connexion.');
        }
    }

  </script>
</body>
</html>