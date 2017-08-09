/**
 * Quiz - Game
 *
 * Licensed under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
 *
 * Copyright 2016-2017, A.Heidemann
 *   
 */
    
 const serverCall = "php/server-0.0.5.php";


  function extend( a, b ) {
    for( var key in b ) { 
      if( b.hasOwnProperty( key ) ) {
        a[key] = b[key];
      }
    }
    return a;
  }


  /**
   * Shuffle array function
   *
   */
  function shuffle(o) {
    for(var j, x, i = o.length; 
        i; 
        j = parseInt(Math.random() * i), x = o[--i], o[i] = o[j], o[j] = x
        );
    return o;
  };
  
  
  function getURLParameter(name) {
      return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search) || [null, ''])[1].replace(/\+/g, '%20')) || null;
  }
  
  
  function hasURLParameter(name) {
      return new RegExp('[?|&]' + name + '(=|&|#|;|$)').test(location.search);
  }
  
  /** Prüft, ob die Seite auf einem Mobilgerät dargestellt wird.
   *  Nur dann ist der WhatsApp-Button sinnvoll 
   */
  function isMobile(){
    // User-Agent-String auslesen
    var UserAgent = navigator.userAgent.toLowerCase();

    // User-Agent auf gewisse Schlüsselwörter prüfen
    if(UserAgent.search(/(iphone|ipod|opera mini|fennec|palm|blackberry|android|symbian|series60)/)>-1){
      // mobiles Endgerät
      console.log ("Mobiles Endgerät vermutet");
      return true;
    } else {
      // kein mobiles Endgerät (PC, Tablet, etc.)
      console.log ("Kein mobiles Endgerät vermutet");
      return false;
    }
  }
  
  function getImagePath(path){
    return serverCall + "?method=getimage&image="+path;
  }
  
  
  function showMsg(title, message, timeout, weiter){
    $("#qzi__msgtitle").html(title);
    $("#qzi__msgtext").html(message); 
    
    var timer1 = null;
    if (timeout){
      timer1 = window.setTimeout( function(){  
        $("#msgarea").hide(); 
        if (weiter){
          weiter();   
        }  
      }, timeout );
    } 
    
    $("#qzi__closemsg").on("click", function(){
      if (timer1) {
        window.clearTimeout(timer1);
      }
      $("#msgarea").hide(); 
      if (weiter){
        weiter();   
      }    
    });
    
    $("#msgarea").show();
  }


  /**
   * Quiz constructor
   *
   */

  function Quiz( options ) {
    console.log ("Quiz V "+ serverCall);
    $("#qzi__nogame").show();
    this.options = extend( {}, this.options );
    extend( this.options, options );
       
    this.bindFunctions();  
    
  }

  /**
   * Quiz options
   *
   * Viewer default options. Available options are:
   *
   * wrapperID: the element in which Memory gets built
   * cards: the array of cards
   * onGameStart: callback for when game starts
   * onGameEnd: callback for when game ends
   */

  Quiz.prototype.options = {
    questions : [],
    count: 6,
    name: "unbekanntes Quiz",
    email: "andreas.heidemann@in-howi.de",
    copyright: "Die Bilder k&ouml;nnen urheberrechtlich gesch&uuml;tzt sein.",
  }
  
  Quiz.prototype.questionId = 0;
  
  /**

   * Load Memory images from json config file
   */
  Quiz.prototype.load = function(quizname, onsuccess){
    var pQuiz = getURLParameter("quiz");
    if (pQuiz){
      quizname = pQuiz;
    }
    var jsonUrl = serverCall + "?method=getquiz&quiz=" + quizname ;
    this.options.shortname = quizname;
    console.log("requesting "+ jsonUrl);

    var self = this;
//    var answer = $.getJSON(jsonUrl, '', function(data){
    $.ajax({dataType: 'json',
            url: jsonUrl,
            data: {},
            success: function(data){
                        if (data.questions){  
                          self.prepare(data);
                          onsuccess();
                          showMsg ("Info", "Quiz geladen: "+ data.name, 3000);
                        }  else {
                          showMsg ("Fehler", "Kann Quiz nicht laden: "+ data.name);
                        }
                      },
            error: function (textStatus, errorThrown) {
                      showMsg ("FEHLER", textStatus);
                    }
            });
  };

  // Bereite Daten vor (setze erste Antwort als korrekt)  
  Quiz.prototype.prepare = function (data){
    for (var i=0; i<data.questions.length; i++){
      data.questions[i].id = i+1;
      data.questions[i].solution = data.questions[i].answers[0];
    }
    extend(this.options, data);
  }
  
  /**

   * Load Memory images from json config file
   */
  Quiz.prototype.save = function(success){
  	console.log("save: "+JSON.stringify(this.options));
    var qz = {};
    qz.name = this.options.name;
    qz.email = this.options.email;
    qz.copyright  = this.options.copyright;
    qz.fgcolor = this.options.fgcolor;
    qz.bgcolor = this.options.bgcolor;
    qz.logo = this.options.logo;
    qz.users = this.options.users ? this.options.users : [this.credentials.user];
    
    qz.questions  = this.options.questions.map(function (cur,ix,arr){
      var q = {};
      q.question = arr[ix].question;
      q.desc     = arr[ix].desc;
      q.img      = arr[ix].img;
      q.answers  = arr[ix].answers;
      q.url      = arr[ix].url;
      return q;
    } )
  	this.toServer(qz, this.options.shortname, success);
  };
  
  /**
   * Load Memory images from json config file
   */
  Quiz.prototype.play = function(baseurl){
    // set page title
    document.title = this.options.name; 
    
    // shuffle answers    
    for (var i=0; i<this.options.questions.length; i++){
      this.options.questions[i].answers = shuffle(this.options.questions[i].answers);
    }
           
    this.newGame();
    this.options.questions = shuffle(this.options.questions);
    this.displayQuestion();
    
    $(".area").addClass("invisible");
    $("#gamearea").removeClass("invisible");
    
  };    
  
  // start login
  Quiz.prototype.login = function(){
    // set page title
    document.title = "Login: " + this.options.name; 
    $(".area").addClass("invisible");
    $("#loginarea").removeClass("invisible");
    
  }; 
  
  // start login
  Quiz.prototype.startup = function(){
    this.setLogoAndColor();
    var editmode = hasURLParameter("edit");
    var pin = getURLParameter("pin");
    console.log("editmode = "+editmode);
    if (editmode){
      if (pin){
        $("#qzi__pin").removeClass("invisible");
        $("#edit_pin").val(pin);
      }
      this.login();
    } else {
      this.play();
    }
  }; 
  
  // start login
  Quiz.prototype.setLogoAndColor = function(){
    if (this.options.bgcolor && this.options.bgcolor.length>0) {
      $('body').css('background-color', this.options.bgcolor);
    } ;
    if (this.options.fgcolor && this.options.fgcolor.length>0) {
      $('body').css('color', this.options.fgcolor);
    } ;
    if (this.options.logo && this.options.logo.length>0) {
      // logo
      $("#logoarea").html("");
      var img = new Image();
      $(img).load(function(){
        $(this).hide();
        $("#logoarea")
          .removeClass('loading')
          .append(this);
        $(this).fadeIn();
      })
      .error (function(){
        console.log("kann Bild "+this.options.logo+" nicht laden");
        showMsg("Fehler", "kann Bild nicht laden", 3000);
      })
      .attr("src", getImagePath(this.options.logo));
      $("#logoarea").removeClass("invisible");
      $("#logoarea").addClass("loading");
    } else {
      $("#logoarea").addClass("invisible"); 
      $("#logoarea").html("");
    }

  };
  
  /**
   * Load Verify-Screen
   */
  Quiz.prototype.verify = function(baseurl){  
    // set page title
    document.title = "Verifiziere Quiz-Bearbeiter";
     
    $(".area").addClass("invisible");
    $("#loginarea").removeClass("invisible");
  };
  
  /**
   * Load Register-Screen
   */
  Quiz.prototype.register = function(baseurl){  
    // set page title
    document.title = "Registriere Quiz-Bearbeiter";
     
    $(".area").addClass("invisible");
    $("#registerarea").removeClass("invisible");
  };
  
  /**
   * Load Memory images from json config file
   */
  Quiz.prototype.edit = function(baseurl){  
    // set page title
    document.title = "Edit: "+this.options.name; 
    this.respectUploadImageTypes();
    $("#edit_title").val(this.options.name);
    $("#edit_email").val(this.options.email);
    $("#edit_users").val(this.options.users ? this.options.users.join(','): this.credentials.user);
    $('#edit_fgcolor').val(this.options.fgcolor);
    $('#edit_bgcolor').val(this.options.bgcolor);
    $('#edit_logo').val(this.options.logo);
      
    this.questionId = 0;
  	this.displayQuestionEdit();
     
    $(".area").addClass("invisible");
    $("#editarea").removeClass("invisible"); 
  };
  
  // initialize & display game constants 
  Quiz.prototype.newGame = function() { 
      this.questionId = 0;
      this.gesamtPunkte = 0;
      
      $('#qzi_title').html(this.options.name);
      $('#qzi__score').html(this.gesamtPunkte);
  }
  
  Quiz.prototype.bindFunctions = function() {
    console.log ("binding game functions ...");
    
  	var self = this;
    
    $('#qzi__weiter').on("click", function(e) {
      $(this).addClass("qzc__buttonpressed");
      $("#qzi__answerarea").show();  
      self.next(); 
      $(this).removeClass("qzc__buttonpressed");
    });
    
    $('#qzi__nochmal').on("click", function(e) {  
      $(this).addClass("qzc__buttonpressed");
      self.play();
      self.displayStart(); 
      $(this).removeClass("qzc__buttonpressed");
    });
    
    $('#qzi__answer1').on( "click", function(e) {
      self.displayAnswer(e);
    });    
    $('#qzi__answer2').on( "click", function(e) {
      self.displayAnswer(e);
    });
    $('#qzi__answer3').on( "click", function(e) {
      self.displayAnswer(e);
    });
    $('#qzi__answer4').on( "click", function(e) {
      self.displayAnswer(e);
    });
     
  	$('#qzi__edit').on('click', function(e){
  		console.log("clicked edit ...");
      $(this).addClass("qzc__buttonpressed");
  		self.edit();  
      $(this).removeClass("qzc__buttonpressed");
  	});
    
  	console.log ('binding edit functions ...');
  	$('#qzi__next').on('click', function(e){ 
      $(this).addClass("qzc__buttonpressed");
  		console.log("clicked weiter ...");
  		self.nextEdit();  
      $(this).removeClass("qzc__buttonpressed");
  	});
  	$('#editarea').on('swiperight', function(e){ 
      $(this).addClass("qzc__buttonpressed");
  		console.log("swiped weiter ...");
  		self.nextEdit();
  	});
  	$('#qzi__back').on('click', function(e){ 
      $(this).addClass("qzc__buttonpressed");
      $(this).removeClass("qzc__buttonpressed");
  		console.log("clicked back ...");
  		self.prevEdit();  
      $(this).removeClass("qzc__buttonpressed");
  	});
  	$('#qzi__save').on('click', function(e){ 
      $(this).addClass("qzc__buttonpressed");
  		console.log("clicked save ...");
      self.readEditedQuestion();
      var btn = this;
  		self.save(function (){$(btn).removeClass("qzc__buttonpressed");});      
  	});
  	$('#qzi__showcolors').on('click', function(e){
      $(this).addClass("qzc__buttonpressed");
  		console.log("clicked view colors ...");
  		self.setLogoAndColor();
      $(this).removeClass("qzc__buttonpressed");
  	});
  	$('#qzi__showlogo').on('click', function(e){ 
      $(this).addClass("qzc__buttonpressed");
  		console.log("clicked view image ...");
  		self.editViewLogo(); 
      $(this).removeClass("qzc__buttonpressed");
  	});
  	$('#qzi__showimg').on('click', function(e){  
      $(this).addClass("qzc__buttonpressed");
  		console.log("clicked view image ...");
  		self.editViewImg();  
      $(this).removeClass("qzc__buttonpressed");
  	});
  	$('#qzi__closeimg').on('click', function(e){ 
      $(this).addClass("qzc__buttonpressed");
  		console.log("clicked close image ...");
  		self.editCloseImg();
      $(this).removeClass("qzc__buttonpressed");
  	});
  	$('#qzi__upload').on('click', function(e){ 
      $(this).addClass("qzc__buttonpressed");
  		console.log("clicked upload ...");
      var btn = this;
  		self.upload(function(){$(btn).removeClass("qzc__buttonpressed");});
  	});
  	$('#qzi__uploadlogo').on('click', function(e){  
      $(this).addClass("qzc__buttonpressed");
  		console.log("clicked uploadlogo ...");
  		self.uploadLogo(function(){$(btn).removeClass("qzc__buttonpressed");});
  	});
  	$('#qzi__neu').on('click', function(e){
      $(this).addClass("qzc__buttonpressed");
  		console.log("clicked new ...");
  		self.insertQuestion();  
      $(this).removeClass("qzc__buttonpressed");
  	});
  	$('#qzi__weg').on('click', function(e){
      $(this).addClass("qzc__buttonpressed");
  		console.log("clicked delete ...");
  		self.deleteQuestion();  
      $(this).removeClass("qzc__buttonpressed");
  	});
  	$('#qzi__play').on('click', function(e){ 
      $(this).addClass("qzc__buttonpressed");
  		console.log("clicked spielen ...");
  		self.play();       
      $(this).removeClass("qzc__buttonpressed");
  	});
  	$('#qzi__mail').on('click', function(e){ 
      $(this).addClass("qzc__buttonpressed");
  		console.log("clicked mail ...");
  		self.mailResult();             //TODO
      $(this).removeClass("qzc__buttonpressed");
  	});
  	$('#qzi__verbessern').on('click', function(e){
      $(this).addClass("qzc__buttonpressed");
  		console.log("clicked verbessern ...");
  		self.mailEnhancement(self.options.email); 
      $(this).removeClass("qzc__buttonpressed");
  	});
  	$('#qzi__neufragesenden').on('click', function(e){ 
      $(this).addClass("qzc__buttonpressed");
  		console.log("clicked neufragesenden ...");
  		self.mailNewQuestion(self.options.email); 
      $(this).removeClass("qzc__buttonpressed");
  	});
  	if (!isMobile()){
  	  $('#qzi__whatsapp').hide();
  	} else {
  	  $('#qzi__whatsapp').show();
  	  $('#qzi__whatsapp').on('click', function(e){
        $(this).addClass("qzc__buttonpressed");
  		  console.log("clicked whatsapp ...");
  	  	self.whatsappResult();   
        $(this).removeClass("qzc__buttonpressed");
  	  });
  	}
    
    // register - screen
  	$('#qzi__register').on('click', function(e){ 
      $(this).addClass("qzc__buttonpressed");
  		console.log("clicked register ...");
      if ($('#reg_pwd').val() == $('#reg_pwd2').val()){
        self.serverRegister($('#reg_name').val(),$('#reg_pwd').val(),$('#reg_email').val(),function(){
            self.verify()
          });
      } 
      $(this).removeClass("qzc__buttonpressed");
      
  	});
    
    // login - screen
  	$('#qzi__login').on('click', function(e){ 
      $(this).addClass("qzc__buttonpressed");
  		console.log("clicked login ...");
      self.serverLogin( self.options.shortname,
                        $('#edit_name').val(),
                        $('#edit_pwd').val(),
                        $('#edit_pin').val(),
                        function(){
                          self.edit()
                        });
      $("#qzi__pin").addClass("invisible"); 
      $(this).removeClass("qzc__buttonpressed");                       
  	});
  	$('#edit_pwd').on('keypress', function(e){
      if (e.which == 13){  
    		console.log("finished password ...");
        self.serverLogin(self.options.shortname,
                        $('#edit_name').val(),$('#edit_pwd').val(),
                        $('#edit_pin').val(),function(){self.edit()});
      }
  	});
  	$('#qzi__showregister').on('click', function(e){ 
      $(this).addClass("qzc__buttonpressed");
  		console.log("clicked showregister ...");
  		self.register();  
      $(this).removeClass("qzc__buttonpressed");
  	});
  	$('#qzi__play2').on('click', function(e){
      $(this).addClass("qzc__buttonpressed");
  		console.log("clicked play2 ...");
  		self.play();  
      $(this).removeClass("qzc__buttonpressed");
  	});
    
    
  }
  
  /**
   *  Addiert die score-Punkte zum Gesamtstand
   */     
  Quiz.prototype.addScore = function (score){
    this.gesamtPunkte += (0 + score);
    $("#qzi__score").html(this.gesamtPunkte);
  }
  
  Quiz.prototype.displayAnswer = function(e) {
      if (e.currentTarget.classList.contains("qzc__yes") || e.currentTarget.classList.contains("qzc__no")){
        // Antwort schon probiert
        return;
      }
      
      var elem =  document.getElementById('qzi__result');
      if (e.currentTarget.classList.contains("qzc__correct")){
        e.currentTarget.classList.add("qzc__yes"); 
        $("#qzi__failarea").hide();
        // show result with a second delay
        window.setTimeout( function(){            
          $("#qzi__descarea").show();
          $("#qzi__answerarea").hide();
        }, 1000 );
        this.addScore (this._fragenpunkte);
      } else { 
        $("#qzi__failarea").show();
        e.currentTarget.classList.add("qzc__no");
        this._fragenpunkte --;
      }
      
  };

  Quiz.prototype.editViewImg = function (){
    $("#qzi__editimage").attr("src", getImagePath($("#edit_image").val()));
    $("#imgarea").show();
  }

  Quiz.prototype.editViewLogo = function (){
    $("#qzi__editimage").attr("src", getImagePath($("#edit_logo").val()));
    $("#imgarea").show();
  }

  Quiz.prototype.editCloseImg = function (){
    $("#qzi__editimage").attr("src", "");
    $("#imgarea").hide();
  }

  Quiz.prototype._prepanswer = function (answerNo, question){
      var elemId = "qzi__answer"+(1+answerNo);
      var elem = document.getElementById(elemId);
      elem.innerHTML = "<p>" + question.answers[answerNo] + "</p>";
      if (question.answers[answerNo] === question.solution){
        //append class qzc__correct
        elem.classList.add("qzc__correct");
      } else {
        // remove class qzc__correct   
        elem.classList.remove("qzc__correct");
      } 
      elem.classList.remove("qzc__yes");
      elem.classList.remove("qzc__no");
  }
  
  Quiz.prototype.displayQuestion = function() {
      var q =  this.options.questions[this.questionId]; 
      
      // shuffle answers
      shuffle(q.answers);
      
      // display question text
      $("#qzi__question").html(q.question);
      
      // image
      if (q.img && q.img.length>0){
        $(".qzc__image").html("");
        var img = new Image();
        $(img).load(function(){
          $(this).hide();
          $(".qzc__image")
            .removeClass('loading')
            .append(this);
          $(this).fadeIn();
        })
        .error (function(){
          console.log("kann Bild "+q.img+" nicht laden");
          showMsg("Fehler", "kann Bild nicht laden", 3000);
        })
        .attr("src", getImagePath(q.img));
        $(".qzc__image").removeClass("invisible");
        $(".qzc__image").addClass("loading");
      } else {
        $(".qzc__image").addClass("invisible");
        $("#qzi__image").attr("src", "");
      }
      
      // prepare answers
      this._prepanswer(0, q);
      this._prepanswer(1, q);
      this._prepanswer(2, q);
      this._prepanswer(3, q);
      this._fragenpunkte = 3;
      
      // prepare description
      $("#qzi__desc").html(this.options.questions[this.questionId].desc);
      
      // link optional
      if (q.url && q.url.length>0){
        qzi__link.href=  q.url;
        $(".qzc__link").removeClass("invisible");
      } else {
        $(".qzc__link").addClass("invisible");
      }
      
      // hide description
      $("#qzi__descarea").hide();
  };
  
  
  Quiz.prototype.displayQuestionEdit = function() {
      
      // qzi__question
      var q = this.options.questions[this.questionId];
	    console.log("display for edit #"+this.questionId);	
      edit_frage.value = q.question;	
      edit_image.value = q.img;
      edit_link.value = q.url;
      edit_antwort1.value = q.answers[0];
      edit_antwort2.value = q.answers[1];
      edit_antwort3.value = q.answers[2];
      edit_antwort4.value = q.answers[3];
      edit_erlaeuterung.value = q.desc;
      
      $('#qzi__qcount').html((this.questionId+1) + "/" +this.options.questions.length);
  };
  
  
  
  Quiz.prototype.readEditedQuestion = function() {
      
      // qzi__question
      var q = this.options.questions[this.questionId];
	
      this.options.name=edit_title.value;
      this.options.email=$("#edit_email").val();
      this.options.users=$("#edit_users").val().split(',');
      this.options.fgcolor = $('#edit_fgcolor').val();
      this.options.bgcolor = $('#edit_bgcolor').val();
      this.options.logo = $('#edit_logo').val();
      q.question=edit_frage.value;
      q.answers[0]=edit_antwort1.value;
      q.answers[1]=edit_antwort2.value;
      q.answers[2]=edit_antwort3.value;
      q.answers[3]=edit_antwort4.value;
      q.desc=edit_erlaeuterung.value;
      q.solution = q.answers[0];  	
      q.img = edit_image.value; 
      q.url = edit_link.value;
  };
  
  Quiz.prototype.displayEnd = function() {
      $("#qzi__questionarea").hide();
      $("#qzi__answerarea").hide(); 
      $("#qzi__descarea").hide();
      $("#qzi__finalarea").show();
      var state = [];
      state.push("<p>Fertig. Sie haben ") ;
      state.push(this.gesamtPunkte) ;
      state.push(" Punkte erreicht.</p>") ;
      $("#qzi__finalstate").html(state.join(""));
  };
  
  Quiz.prototype.displayStart = function() {
      $("#qzi__questionarea").show();
      $("#qzi__answerarea").show();
      $("#qzi__descarea").show();
      $("#qzi__finalarea").hide();
      $("#qzi__descarea").hide();
  };

  Quiz.prototype.prev = function() {
      this.questionId --;
      if (this.questionId < 0){
        this.questionId = this.options.questions.length - 1;
      }
      this.displayQuestion();
  };

  Quiz.prototype.next = function() {
      this.questionId ++;
      if (this.questionId >= Math.min(this.options.count, this.options.questions.length)){
        this.displayEnd();
      } else {                   
        this.displayQuestion();
      }
  };

  Quiz.prototype.nextEdit = function() {
      console.log("next edit called at qid "+this.questionId+ " / "+ this.options.questions.length);
      this.readEditedQuestion();
      
      this.questionId ++;
      if (this.questionId >= this.options.questions.length){
       this.questionId = 0;
      }
      this.displayQuestionEdit();
  };

  Quiz.prototype.prevEdit = function() {
	console.log("prev edit called at qid "+this.questionId+ " / "+ this.options.questions.length);
      this.readEditedQuestion();
 	
        this.questionId --;
      if (this.questionId < 0){
	       this.questionId = this.options.questions.length-1;
      }
        this.displayQuestionEdit();
  };

  Quiz.prototype.insertQuestion = function() {
	console.log("insert question called at qid "+this.questionId+ " / "+ this.options.questions.length);
      this.readEditedQuestion();
 	
      var qn = {};
      qn.question = "Frage eingeben";
      qn.answers  = ["korrekt","falsch","falsch","falsch"];
      qn.desc     = "Erlaeuterung zur Loesung eingeben";
      qn.img      = "";
      qn.url      = ""; 
      
      qn.id       = this.options.questions.length+1;
      
      this.options.questions.push(qn);
      this.questionId = this.options.questions.length-1;
      this.displayQuestionEdit();
  };

  Quiz.prototype.deleteQuestion = function() {
	console.log("delete question called at qid "+this.questionId+ " / "+ this.options.questions.length);
      this.readEditedQuestion();
 	
      // letzte Frage darf nicht gelöscht werden
      if (this.options.questions.length > 1){
           this.options.questions.splice(this.questionId,1);
            if (this.questionId >= this.options.questions.length){
	             this.questionId = 0;
            }   
            this.displayQuestionEdit();
      } else {
        showMsg ("FEHLER", "Die letzte Frage darf nicht gelöscht werden!");
      }
  };
  
 Quiz.prototype.toServer = function (obj,shortname, success){ 
    console.log("Quiz speichern auf Server...");
    
    $.ajax({type:"post"
            , data: {method: 'storequiz'
                    , store_me: JSON.stringify(obj)
                    , quiz: shortname
                    , user: this.credentials.user
                    , passwd: this.credentials.passwd}
            , url: serverCall 
            , complete: function (XMLHttpRequest, textStatus) {
                            console.log("Antwort vom Server: "+ XMLHttpRequest.responseText) ;
                            if (XMLHttpRequest.responseText.startsWith("OK")){
                              showMsg ("Info", "Speichern erfolgreich", 3000);
                            } else {                                          
                              showMsg ("FEHLER", "Speichern fehlgeschlagen: "+XMLHttpRequest.responseText);
                            }
                            success();
		}});
  }
  
 // Register  
 Quiz.prototype.serverRegister = function (user, passwd, email, success){ 
    console.log("register anfrage auf server");
    var self = this;
    
    $.ajax({type:"post"
            , data: {method: 'adduser', user: user, passwd: passwd, email: email}
            , url: serverCall 
            , complete: function (XMLHttpRequest, textStatus) {
                            console.log("antwort vom register: "+ XMLHttpRequest.responseText) ;
                            if (XMLHttpRequest.responseText.startsWith("OK")){
                              self.credentials = {user: user, passwd: passwd};
                              success();
                            } else {
                              console.log("Registrierung verweigert fuer: "+ user);
                              showMsg("KEINE Registrierung", "Die Registrierung für Benutzer: "+ user +" wurde verweigert."); 
                            }
                            
		}});
  }
  
 // Login  
 Quiz.prototype.serverLogin = function (quiz, user, passwd, pin, success){ 
    console.log("login anfrage auf server");
    var self = this;
    
    if (pin && pin > ""){ 
      $.ajax({type:"post"
              , data: {method: 'verifyuser', quiz: quiz, user: user, passwd: passwd, pin: pin}
              , url: serverCall 
              , complete: function (XMLHttpRequest, textStatus) {
                              console.log("antwort vom login: "+ XMLHttpRequest.responseText) ;
                              if (XMLHttpRequest.responseText.startsWith("OK")){
                                self.credentials = {user: user, passwd: passwd};
                                showMsg("PIN VERIFIKATION", "PIN-Verifikation erfolgreich für Benutzer: "+ user, 1000); 
                                success();
                              } else {
                                console.log("PIN-Verifikation fehlgeschlagen fuer: "+ user);
                                showMsg("PIN VERIFIKATION", "PIN-Verifikation fehlgeschlagen für Benutzer: "+ user, 2000); 
                                $("#qzi__pin").removeClass("invisible");
                                $("#edit_pin").val("");
                              }
                              
  		}});
    } else {
      $.ajax({type:"post"
              , data: {method: 'login', quiz: quiz, user: user, passwd: passwd}
              , url: serverCall 
              , complete: function (XMLHttpRequest, textStatus) {
                              console.log("antwort vom login: "+ XMLHttpRequest.responseText) ;
                              if (XMLHttpRequest.responseText.endsWith("granted.")){
                                self.credentials = {user: user, passwd: passwd};
                                success();
                              } else if (XMLHttpRequest.responseText.startsWith("PIN")){
                                console.log("PIN verlangt fuer: "+ user);
                                showMsg("PIN VERIFIKATION", "Bitte PIN eingeben für Benutzer: "+ user, 2000); 
                                $("#qzi__pin").removeClass("invisible");
                                $("#edit_pin").val("");
                              }else {
                                console.log("Zugriff verweigert fuer: "+ user);
                                showMsg("KEIN ZUGANG", "Zugriff verweigert für Benutzer: "+ user); 
                              }
                              
  		}});
    }
  }
  
  //getimagetypes
  Quiz.prototype.respectUploadImageTypes = function (){
    $.ajax({type:"post"
            , data: {method: 'getimagetypes'}
            , url: serverCall 
            , complete: function (XMLHttpRequest, textStatus) {
                            console.log("antwort von getimagetypes: "+ XMLHttpRequest.responseText) ;
                            if (XMLHttpRequest.responseText){
                              $('#edit_imageFile').attr("accept", XMLHttpRequest.responseText);
                              $('#edit_logoFile').attr("accept", XMLHttpRequest.responseText);
                            } else {
                              console.log("Keine Einschränkung des Uploads möglich.");
                            }
                            
		}});
  }  
  
/**
 *  Upload von Bilddateien auf den Server.
 */   
 Quiz.prototype.upload = function (success){ 
    console.log("upload auf server, Dateiname: "+$("#edit_imageFile").val() );
    
    var filename = $("#edit_imageFile").val();
    var endung = filename.substr(filename.lastIndexOf('.')+1, filename.length);
    
    var data = new FormData();
    data.append('file', $("#edit_imageFile")[0].files[0]);  
    data.append ('method', "uploadImage"); 
    data.append ('quiz', this.options.shortname);
    data.append ('user', this.credentials.user);
    data.append ('passwd', this.credentials.passwd);
    
    
    $.ajax({type:"POST"
            , data: data
            , url: serverCall 
            , processData: false
            , contentType: false
            , complete: function (XMLHttpRequest, textStatus) {
                            console.log("antwort vom upload: "+ XMLHttpRequest.responseText) ;
                            if (XMLHttpRequest.responseText && XMLHttpRequest.responseText.startsWith("OK ")){
                              // setze Bild-Variable
                              var newName = XMLHttpRequest.responseText.substr(3);
                              console.log ("setze Bild auf img/"+newName);
                              $("#edit_image").val("img/"+newName);
                              showMsg ("Info", "Bild erfolgreich gespeichert.", 3000);
                            } else {
                              showMsg ("FEHLER", "Fehler beim Bild hochladen: "+ XMLHttpRequest.responseText);
                            }
                            success();
                            
		}});
  } 
  
/**
 *  Upload von Bilddateien auf den Server.
 */   
 Quiz.prototype.uploadLogo = function (success){ 
    console.log("upload auf server, Dateiname: "+$("#edit_logoFile").val() );
    
    var filename = $("#edit_logoFile").val();
    
    var data = new FormData();
    data.append('file', $("#edit_logoFile")[0].files[0]);  
    data.append ('method', "uploadLogo"); 
    data.append ('quiz', this.options.shortname);
    data.append ('user', this.credentials.user);
    data.append ('passwd', this.credentials.passwd);
    
    
    $.ajax({type:"POST"
            , data: data
            , url: serverCall 
            , processData: false
            , contentType: false
            , complete: function (XMLHttpRequest, textStatus) {
                            console.log("antwort vom upload: "+ XMLHttpRequest.responseText) ;
                            if (XMLHttpRequest.responseText && XMLHttpRequest.responseText.startsWith("OK ")){
                              // setze Bild-Variable
                              var newName = XMLHttpRequest.responseText.substr(3);
                              console.log ("setze Bild auf img/"+newName);
                              $("#edit_logo").val("img/"+newName);
                              showMsg ("Info", "Bild erfolgreich gespeichert.", 3000);
                            } else {
                              showMsg ("FEHLER", "Fehler beim Bild hochladen: "+ XMLHttpRequest.responseText);
                            }
                            success();
                            
		}});
  }
  
    
  /** entfernt die edit-Option aus der URL */       
  Quiz.prototype.removeEdit = function(url){
    console.log("TODO: removeEdit() implementieren");
    return url;
  }
  
  /**
   * Schickt eine Mail mit der Erfolgsmeldung
   */     
  Quiz.prototype.mailResult = function(){
    var mailUrl = "mailto:?subject="+this.options.name+":%20"+this.gesamtPunkte+"%20Punkte.";
    mailUrl += "&body=Hallo,%0D%0A%0D%0Aich%20hab%20ein%20tolles%20Quiz%20entdeckt:%20";
    mailUrl += this.options.name+".%0D%0ASolltest%20Du%20auch%20probieren!";
    mailUrl += "%0D%0A%0D%0A==>%20"+this.removeEdit(window.location.href)+"%0D%0A%0D%0A";
    mailUrl += "Ich habe mit "+this.options.count+" Fragen "+ this.gesamtPunkte;
    mailUrl += " Punkte erreicht.%0D%0AVersuch es mal besser!%0D%0A%0D%0A"
    window.location.href=mailUrl;
  }
  
  /**
   * Schickt eine Mail mit einer neuen Frage
   */     
  Quiz.prototype.mailNewQuestion = function(address){
    var mailUrl = "mailto:"+address+"?subject=Neue%20Fragen%20für%20"+this.options.name+"...";
    mailUrl += "&body=Hallo,%0D%0A%0D%0Aich%20habe%20eine%20neue%20Frage%20für%20das%20Quiz%20";
    mailUrl += this.options.name+".%0D%0A%0D%0A%0D%0A";
    mailUrl += "Frage:%20%0D%0A%0D%0Arichtige%20Antwort:%20%0D%0Afalsche%20Antwort%201:%20";
    mailUrl += "%0D%0Afalsche%20Antwort%202:%20%0D%0Afalsche%20Antwort%203:%20";
    mailUrl += "%0D%0AErläuterungstext:%20...%0D%0A";
    mailUrl += "%0D%0Aweiterführender%20Link:%20http://...%0D%0A";
    window.location.href=mailUrl;
  }
  
  /**
   * Schickt eine Mail mit einer neuen Frage
   */     
  Quiz.prototype.mailEnhancement = function(address){
    var mailUrl = "mailto:"+address+"?subject=Verbesserungsvorschlag%20für%20"+this.options.name+"...";
    mailUrl += "&body=Hallo,%0D%0A%0D%0Aich%20habe%20einen%20Verbesserungsvorschlag%20für%20das%20Quiz%20";
    mailUrl += this.options.name+",%20bei%20der%20Frage%20"+(this.questionId+1)+":";
    mailUrl += this.options.questions[this.questionId].question;
    mailUrl += ".%0D%0A%0D%0A%0D%0A";
    mailUrl += "Mein Vorschlag:%20%0D%0A%0D%0A";
    window.location.href=mailUrl;
  }
  
  /**
   * Schickt eine WhatsApp-Nachricht mit der Erfolgsmeldung
   */     
  Quiz.prototype.whatsappResult = function(){
    var mailUrl = "whatsapp://send?text=Ich%20habe%20gerade%20das%20Quiz *'";
    mailUrl += this.options.name+"'*%20mit%20*"+ this.gesamtPunkte;
    mailUrl += "%20Punkten*%20beendet.%0D%0AWer%20kann%20es%20es%20besser?%20";
    mailUrl += "%0D%0A%0D%0AHier%20geht%20es%20zum%20Rätsel:%20"+this.removeEdit(window.location.href)+"%0D%0A";
    window.location.href=mailUrl;
  }


  window.onload = function (){
      var myQuiz = new Quiz({ });
      myQuiz.load("test", function(){myQuiz.startup();});
    };