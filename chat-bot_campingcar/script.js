  // BASE SE CONAISSANCE
  const answers = [
    { keywords: ["bonjour", "salut", "hey"], reply: "Bonjour ! 🌵 Prêt à découvrir les merveilles du désert marocain en camping-car ? Je suis là pour vous aider !" },
    { keywords: ["merci", "thanks"], reply: "Avec plaisir ! N'hésitez pas à me poser d'autres questions sur le désert ou nos services. 🚐" },
    { keywords: ["aide", "support"], reply: "Je suis là pour vous aider sur toutes vos questions concernant la location de camping-car et le voyage au Maroc, particulièrement dans le désert !" },

    // Désert et zones désertiques
    { keywords: ["désert", "sahara", "dunes"], reply: "Le désert du Sahara au Maroc est une expérience magique ! 🌵 Nous proposons des circuits spécialement conçus pour le désert avec camping-cars adaptés. Voulez-vous que je vous parle de nos offres spéciales désert ?" },
    { keywords: ["merzouga", "erg chebbi"], reply: "Merzouga et ses magnifiques dunes d'Erg Chebbi sont incontournables ! 🐪 Nos circuits incluent souvent une nuit en bivouac, un dîner berbère et une balade à dos de chameau au coucher du soleil. Je vous recommande notre 'Expérience Désert Complet' de 7 jours !" },
    { keywords: ["zagora", "mhamid"], reply: "Zagora et M'Hamid sont les portes du désert ! 🚐 Nous avons des itinéraires adaptés pour cette région, avec des camping-cars équipés pour les pistes désertiques. Notre circuit 'Désert Authentique' est parfait pour cette zone." },
    { keywords: ["nuit désert", "bivouac", "campement"], reply: "Passer une nuit dans le désert est une expérience inoubliable ! ⭐ Nous organisons des nuits en bivouac avec tout le confort : tentes berbères, dîner traditionnel, musique et contes autour du feu, et observation des étoiles. Souhaitez-vous plus de détails ?" },
    { keywords: ["chaleur", "été", "température désert"], reply: "En été, le désert peut être très chaud le jour (40-50°C) mais les nuits sont fraîches. 🏜️ Nos camping-cars sont tous équipés de climatisation et de systèmes de ventilation. La meilleure période est d'octobre à avril (20-25°C le jour)." },
    { keywords: ["piste", "4x4", "conduire désert"], reply: "Conduire dans le désert nécessite quelques précautions. 🚙 Nous fournissons une formation de base et nos véhicules 4x4 sont spécialement équipés (pneus sable, équipement de récupération). Pour les zones difficiles, nous recommandons un guide local." },

    // Montagnes de l'Atlas
    { keywords: ["atlas", "montagne", "haut atlas"], reply: "Les montagnes de l'Atlas offrent des paysages à couper le souffle ! 🏔️ Notre circuit 'Désert & Montagnes' combine les dunes du Sahara et les sommets de l'Atlas. Les camping-cars sont équipés pour l'altitude et les routes de montagne." },
    { keywords: ["tizi n", "col", "passage"], reply: "Les cols de l'Atlas comme le Tizi n'Tichka sont spectaculaires mais exigent une conduite prudente. 🚐 Nos véhicules sont parfaitement entretenus pour ces routes et nous fournissons des conseils de conduite détaillés." },
    { keywords: ["neige", "hiver", "froid atlas"], reply: "En hiver, l'Atlas peut être enneigé et très froid la nuit. ❄️ Nos camping-cars ont un chauffage performant et sont isolés pour ces conditions. C'est une période magique pour photographier les paysages !" },

    // Services et réservation
    { keywords: ["réservation", "réserver", "disponibilité"], reply: "Vous pouvez réserver directement en ligne sur notre site ou par téléphone au +212 670 524 652. 📅 Actuellement, il reste quelques véhicules pour les départs désert la semaine prochaine ! Souhaitez-vous que je vous envoie le lien de réservation ?" },
    { keywords: ["prix", "tarif", "coût", "combien"], reply: "Nos tarifs pour une semaine dans le désert commencent à 850€ tout compris (hors vols). 🚐 Cela inclut le camping-car équipé, l'assurance, l'assistance 24/7 et les conseils d'itinéraire. Je peux vous proposer une offre détaillée !" },
    { keywords: ["inclut", "équipement", "services"], reply: "Tous nos camping-cars désert sont équipés de : lit confortable, kitchenette, frigo, douche/toilette, chauffage/clim, panneaux solaires, GPS et kit de sécurité désert. 🛌 De plus, nous fournissons des cartes détaillées et un guide des meilleurs bivouacs !" },
    { keywords: ["assurance", "sécurité", "dépannage"], reply: "Tous nos véhicules sont assurés tous risques avec assistance 24h/24. 🆘 En cas de problème dans le désert, nous avons un réseau de partenaires locaux pour intervenir rapidement. Nous fournissons aussi un téléphone satellite en option." },

    // Itinéraires et conseils
    { keywords: ["itinéraire", "circuit", "route"], reply: "Pour le désert, nous recommandons : Marrakech - Ouarzazate - Zagora - M'Hamid - Erg Chigaga - Foum Zguid - retour. 🗺️ Ou la version nord : Marrakech - Gorges du Todra - Merzouga - Erfoud - Vallée du Ziz - Midelt - Azrou - Fès. Je peux vous envoyer nos itinéraires détaillés !" },
    { keywords: ["meilleure période", "quand"], reply: "La meilleure période pour le désert est d'octobre à avril : températures diurnes agréables (20-28°C) et nuits fraîches. 🌞 Évitez juillet-août si vous supportez mal la chaleur extrême (peut dépasser 45°C)." },
    { keywords: ["enfant", "famille", "enfant désert"], reply: "Le désert est une aventure magique pour les enfants ! 👨‍👩‍👧‍👦 Nos camping-cars familiaux ont assez de place et nous fournissons des équipements sécurité adaptés. Beaucoup de familles reviennent enchantées de leur expérience." },
    { keywords: ["nourriture", "eau", "provisions"], reply: "Prévoyez de l'eau en quantité (5L/personne/jour) et des provisions pour 2-3 jours. 🥫 Dans les villages, vous trouverez des produits frais. Nous fournissons une liste des meilleurs marchés et épiceries sur votre route." },
    { keywords: ["carburant", "essence", "diesel", "station"], reply: "Dans le désert, faites le plein à chaque occasion ! ⛽ Dernières stations importantes : Ouarzazate, Zagora ou Errachidia avant le désert. Prévoyez des jerricans supplémentaires pour les longs trajets isolés." },

    // Autres sujets
    { keywords: ["culture", "berbère", "traditions"], reply: "Rencontrer les populations berbères est un des trésors du voyage ! 🎶 Nous pouvons organiser des rencontres authentiques avec des familles locales et des guides qui partageront leurs traditions et leur mode de vie dans le désert." },
    { keywords: ["photo", "film", "instagram"], reply: "Le désert est un paradis pour photographes ! 📸 Lever/coucher de soleil sur les dunes, nuit étoilée, villages de terre... Nous fournissons des conseils photo et une liste des meilleurs spots. Beaucoup de nos clients ont des photos primées !" },
    { keywords: ["célébrité", "star", "film"], reply: "Beaucoup de films ont été tournés dans le désert marocain : Star Wars, Gladiator, Prince of Persia... 🎬 Nous proposons des circuits 'Tournage de films' qui vous emmènent sur ces lieux mythiques !" },
    { keywords: ["faune", "flore", "animaux désert"], reply: "Le désert abrite une vie surprenante : fennecs, gerboises, lézards, scorpions (inoffensifs), et une variété d'oiseaux. 🌵 La flore s'adapte à l'aridité : cactus, palmiers dattiers, acacias... Nos guides vous aideront à les repérer." },
    { keywords: ["mère ocien", "atlantis", "bivouac plage", "spot sauvage", "wild camping"], reply: "La Plage Mère Ocien Atlantis est un spot de bivouac mythique et gratuit pour camping-cars ! 🏖️ Une immense plage sauvage près de Guelmim, parfaite pour des nuits au son des vagues et des couchers de soleil inoubliables. Consultez nos coordonnées GPS exactes et les avis de la communauté." },
    { keywords: ["circuit", "itinéraire", "route", "étape", "road trip"], reply: "Nous avons conçu des circuits optimisés pour camping-cars de Tanger au Sahara ! 🗺️ Des itinéraires qui mêlent côte Atlantique, montagnes et désert, avec toutes les infos pratiques : routes, durées, et les meilleurs spots de bivouac à chaque étape." },
    { keywords: ["GPS", "coordonnées", "waypoint", "cartographie"], reply: "Tous nos spots et circuits sont disponibles avec des coordonnées GPS précises (format Degrés Décimaux). 📍 Téléchargez nos fiches waypoints pour les intégrer directement dans votre navigateur ou application de cartographie favorite." },
    { keywords: ["marrakech", "fès", "agadir", "essaouira", "ville impériale"], reply: "Explorez les villes incontournables en camping-car ! 🏰 Nous avons des conseils spécifiques pour se stationner et visiter Marrakech, Fès, Essaouira ou Agadir en toute sérénité, souvent avec des aires sécurisées à proximité." },
    { keywords: ["désert", "erg chebbi", "dunes", "merzouga", "sahara"], reply: "Vivre le Sahara est une expérience magique. 🐫 Nous vous guidons pour choisir entre un bivouac sauvage aux portes du désert ou une excursion organisée avec nuit dans un campement au cœur des dunes de l'Erg Chebbi." },
    { keywords: ["pratique", "ravitaillement", "eau", "vidange", "gasoil"], reply: "Notre communauté partage en temps réel toutes les infos pratiques ! ⛽ Points de ravitaillement en eau, stations-service avec gasoil, stations de vidange... Toutes les adresses vérifiées pour voyager l'esprit léger." },
    { keywords: ["communauté", "forum", "entraide", "avis"], reply: "Rejoignez notre communauté de passionnés ! 🤝 Le forum est l'endroit parfait pour poser vos questions, partager votre expérience et lire les avis en temps réel sur les conditions des routes et des spots de bivouac." },
    { keywords: ["sécurité", "conduire", "nuit", "conseils"], reply: "Voyager en sécurité est notre priorité. 🛡️ Nous prodiguons des conseils essentiels pour conduire au Maroc, choisir ses spots de nuit, et respecter les règles locales pour un voyage serein et mémorable." }
  ];


  const chatBox = document.getElementById("chat-box");

  /* --- SIMILARITÉ pour tolérer fautes --- */
  function similarity(a, b) {
    a = a.toLowerCase(); b = b.toLowerCase();
    if (a === b) return 1;
    if (a.length < 2 || b.length < 2) return 0;
    let same = 0;
    for (let i = 0; i < Math.min(a.length, b.length); i++) {
      if (a[i] === b[i]) same++;
    }
    return same / Math.max(a.length, b.length);
  }

  /* --- GÉNÉRATION DES RÉPONSES --- */
  function generateSmartReply(userText) {
    const text = userText.trim().toLowerCase();
    if (!text) return "Veuillez poser une question sur le désert marocain ou nos services.";

    const words = text.split(/\s+/);
    const matchedReplies = [];

    // Bloc spécial marketing désert
    // Circuit 7 jours - Expérience Désert Complet
    if (text.includes("7 jours") || text.includes("expérience") || text.includes("complet")) {
      return "🌍 Voyagez au cœur du Sahara avec notre **Circuit Expérience Désert Complet (7 jours)**. Ce parcours vous emmène à travers l’Atlas, les vallées verdoyantes et jusqu’aux dunes dorées de Merzouga. 🐪 Profitez d’excursions à dos de dromadaire, de soirées berbères autour du feu et de nuits magiques sous les étoiles en bivouac. 💰 Prix : environ 850–950€.\n\n📞 Réservation & contact :\n- Téléphone / WhatsApp : +212 670-524652\n- Email : contact@maroccampingcar.com\n- Réservations en ligne : https://maroccampingcar.com/reservez-votre-camping-car-au-maroc/\n📍 Adresse : Marrakech, Maroc";
    }

    // Circuit 4 jours - Découverte
    if (text.includes("4 jours") || text.includes("court") || text.includes("découverte")) {
      return "🚐 Envie d’une première immersion dans le Sahara ? Optez pour notre **Circuit Désert Découverte (4 jours)**. Au programme : traversée de l’Atlas, découverte de la vallée du Dadès et arrivée aux impressionnantes dunes de Merzouga. 🌅 Idéal pour un court séjour, vous vivrez déjà la magie du désert en seulement 4 jours. 💰 Prix : à partir de 490€.\n\n📞 Réservation & contact :\n- Téléphone / WhatsApp : +212 670-524652\n- Email : contact@maroccampingcar.com\n- Réservations en ligne : https://maroccampingcar.com/reservez-votre-camping-car-au-maroc/\n📍 Adresse : Marrakech, Maroc";
    }

    // Circuit 10 jours - Aventure Totale
    if (text.includes("10 jours") || text.includes("long") || text.includes("aventure")) {
      return "✨ Pour les passionnés de voyage, notre **Circuit Désert Aventure Totale (10 jours)** offre une immersion complète dans le Sahara. Vous explorerez des oasis secrètes, des kasbahs anciennes et des villages berbères authentiques. 🌌 Vivez la culture locale, profitez de bivouacs de luxe et de riads traditionnels. 💰 Prix : jusqu’à 1200€.\n\n📞 Réservation & contact :\n- Téléphone / WhatsApp : +212 670-524652\n- Email : contact@maroccampingcar.com\n- Réservations en ligne : https://maroccampingcar.com/reservez-votre-camping-car-au-maroc/\n📍 Adresse : Marrakech, Maroc";
    }

    // Bloc prix / tarifs
    if (text.includes("prix") || text.includes("tarif") || text.includes("coût")) {
      return "💰 Nos circuits désert commencent à **490€** (4 jours) et vont jusqu’à **1200€** (10 jours). 🔥 Promo spéciale : -15% sur toutes les réservations cette semaine !\n\n📞 Réservation & contact :\n- Téléphone / WhatsApp : +212 670-524652\n- Email : contact@maroccampingcar.com\n- Réservations en ligne : https://maroccampingcar.com/reservez-votre-camping-car-au-maroc/\n📍 Adresse : Marrakech, Maroc";
    }


    // Recherche dans base
    for (let ans of answers) {
      for (let kw of ans.keywords) {
        for (let word of words) {
          if (word.includes(kw) || kw.includes(word) || similarity(word, kw) >= 0.6) {
            if (!matchedReplies.includes(ans.reply)) matchedReplies.push(ans.reply);
          }
        }
      }
    }

    if (matchedReplies.length === 0) {
      return "🐪 Le désert marocain vous tente ? Posez-moi vos questions sur Merzouga, les dunes du Sahara, les circuits ou la location de camping-car ! 🌐 https://maroccampingcar.com/";
    }

    return matchedReplies.map((r, i) => `👉 Réponse ${i + 1}: ${r}`).join("\n\n") 
           + "\n\n🌐 Plus d'infos : https://maroccampingcar.com/";
  }

  /* --- EFFET TYPING --- */
  function typeMessage(message) {
    return new Promise(resolve => {
      const botDiv = document.createElement("div");
      botDiv.className = "bot-message";
      chatBox.appendChild(botDiv);

      let i = 0;
      const interval = setInterval(() => {
        botDiv.textContent += message.charAt(i);
        i++;
        chatBox.scrollTop = chatBox.scrollHeight;
        if (i >= message.length) {
          clearInterval(interval);
          resolve();
        }
      }, 30);
    });
  }

  /* --- ENVOI MESSAGE --- */
  async function sendMessage() {
    const input = document.getElementById("user-input");
    const userText = input.value.trim();
    if (!userText) return;

    const userDiv = document.createElement("div");
    userDiv.className = "user-message";
    userDiv.textContent = userText;
    chatBox.appendChild(userDiv);
    input.value = "";
    chatBox.scrollTop = chatBox.scrollHeight;

    const reply = generateSmartReply(userText);
    await typeMessage(reply);
  }

  /* --- RÉSERVATION --- */
  function showBooking(tourName) {
    const userDiv = document.createElement("div");
    userDiv.className = "user-message";
    userDiv.textContent = "Je veux réserver: " + tourName;
    chatBox.appendChild(userDiv);

    const reply = 
      "🎉 Pour réserver '" + tourName + "':\n\n" +
      "📅 Départs : Dimanche prochain et Mercredi (8 jours)\n" +
      "🚐 Disponibilité : " + (tourName.includes("Complet") ? "2 camping-cars" : "1 van") + "\n\n" +
      "👉 Réservation : https://maroccampingcar.com/reservation\n" +
      "📞 Téléphone : +212 670 524 652";
    typeMessage(reply);
  }

  /* --- INTERACTIONS --- */
  document.getElementById("user-input").addEventListener("keypress", e => {
    if (e.key === "Enter") sendMessage();
  });
  const toggleBtn = document.getElementById("chat-toggle");
  const chatContainer = document.getElementById("chat-container");
  toggleBtn.addEventListener("click", e => {
    e.stopPropagation();
    chatContainer.style.display = (chatContainer.style.display === "flex") ? "none" : "flex";
  });
  document.addEventListener("click", e => {
    if (chatContainer.style.display === "flex" && !chatContainer.contains(e.target) && e.target !== toggleBtn) {
      chatContainer.style.display = "none";
    }
  });

