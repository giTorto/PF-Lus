
showInfo('info_start');

//Setup Speech Synthesis
var curr_trans='1';
var prev_trans='2';


var final_transcript = '';
var recognizing = false;
var ignore_onend;

if (!('webkitSpeechRecognition' in window)) {
  alert('webkitSpeechRecognition not supported');
} else {
  start_button.style.display = 'inline-block';
  var ASR = new webkitSpeechRecognition();
  var TTS = new SpeechSynthesisUtterance();
  var voices = window.speechSynthesis.getVoices();

  ASR.continuous = true;
  ASR.interimResults = true;

  ASR.onstart = function() {
    recognizing = true;
    showInfo('info_speak_now');
    start_img.src = 'mic-animate.gif';
  };

  ASR.onerror = function(event) {
    if (event.error == 'no-speech') {
      start_img.src = 'mic.gif';
      ignore_onend = false;
    }
    if (event.error == 'audio-capture') {
      start_img.src = 'mic.gif';
      ignore_onend = false;
    }
  };

  ASR.onend = function() {
    recognizing = true;
    if (ignore_onend) {
      return;
    }
    start_img.src = 'mic.gif';
    if (!final_transcript) {
      showInfo('info_start');
      return;
    }
    showInfo('');
  };

  ASR.onresult = function(event) {
        console.log(event);
        var best_transcript="";
        var best_confidence=0;
        for (var i = 0; i < event.results.length; ++i) {
            if (event.results[i].isFinal) {
                for (var j = 0; j < event.results[i].length; ++j) {
                    transcript=event.results[i][j].transcript;
                    confidence=event.results[i][j].confidence;
                   //console.log('result:'+transcript+' conf:'+confidence);
                }
                best_transcript=event.results[0][0].transcript;
                best_confidence=event.results[0][0].confidence;
                $("#ASRDiv").html(best_transcript);
                processDialogue(best_transcript, best_confidence);
            }

        }


  };

}

function startButton(event) {
  stopRecognition();
  startRecognition();
}

function startRecognition(){
    console.log('startRecognition');
    final_transcript = '';
    ASR.lang = 'en-US';
    ASR.start();
    ignore_onend = false;
    start_img.src = 'mic-slash.gif';
    showInfo('info_allow');
    $("#interim").empty();
    $("#final_results").empty();
}

function stopRecognition(){
    if (recognizing) {
        ASR.stop();
        recognizing = false;
        return;
    }
}

function showInfo(s) {
  if (s) {
    for (var child = info.firstChild; child; child = child.nextSibling) {
      if (child.style) {
        child.style.display = child.id == s ? 'inline' : 'none';
      }
    }
    info.style.visibility = 'visible';
  } else {
    info.style.visibility = 'hidden';
  }
}

function speakText(textToSpeak){
  voices = window.speechSynthesis.getVoices();
    //for(var i = 0; i < voices.length; i++ ) {
    //    console.log(voices);
    //}
    TTS.lang = 'en-US';
    TTS.pitch = 1; //0 to 2
    TTS.voice = voices[33]; //Not all supported
    TTS.voiceURI = 'native';
    TTS.volume = 1; // 0 to 1
    TTS.rate = 1; // 0.1 to 10
    TTS.text =textToSpeak;
    TTS.onend = function(e) {
        console.log('message over');
        startRecognition();
    };
    window.speechSynthesis.speak(TTS);
}

function processDialogue(asrResult, confidence){
    addRightBubble(asrResult);
    asrResult = asrResult.split(" ").join("_");
    var json_input = {};
    json_input["transcript"] = asrResult;
    json_input["confidence"] = confidence;
    var request_string = 'dialogManager/controller.php?SLUObject='+JSON.stringify(json_input)+'&callback=?';
    console.log(request_string);
    $.getJSON(request_string, function(json_data) {
        console.log(json_data);
        textToSpeak=json_data.results;
        console.log(textToSpeak);
         addLeftBubble(textToSpeak);
        var scr = $('#dialogue_window')[0].scrollHeight;
        $('#dialogue_window').animate({scrollTop: scr});
     });
}

function addRightBubble(asr_out){
    var asr_content='<div class="leftRow"><div class="bubbledLeftContainer"><div class="bubbledLeft">'+asr_out+'</div></div></div>';
    $("#dialogue_window").append(asr_content);
}

function addLeftBubble(textToSpeak){
    stopRecognition();
    var tts_content='<div class="rightRow"><div class="bubbledRightBeforeContainer"></div><div class="bubbledRightContainer"><div class="bubbledRight">'+textToSpeak+'</div></div></div>';
    $("#dialogue_window").append(tts_content);
    speakText(textToSpeak);
}
