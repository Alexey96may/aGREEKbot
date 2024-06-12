<?php

//token from another file
$secretToken = file_get_contents(__DIR__."/init/token.txt");

//template file prefix
define("TEMPL_PREFIX", __DIR__."/temp");

//Ключ доступа сообщества
define("BOTTOKEN", $secretToken);
define("BOTID", "5687000457");
define("CHATTTID", "-1002205650441");

$data = file_get_contents('php://input');
$arrDataAnswer = json_decode($data, true);
$textMessage = trim(mb_strtolower($arrDataAnswer["message"]["text"]));
$chatId = $arrDataAnswer["message"]["chat"]["id"];
$user_Id = $arrDataAnswer["message"]["from"]["id"];
$user_firstName = $arrDataAnswer["message"]["from"]["first_name"];
$user_lastName = $arrDataAnswer["message"]["from"]["last_name"];

if ($chatId == BOTID) {
	if (!file_exists(TEMPL_PREFIX."/settings_"."user".$user_Id.".txt")) {
		file_put_contents(TEMPL_PREFIX."/settings_"."user".$user_Id.".txt", '[]');
		changeGameMode("toRus");
	}
} else {
	if (!file_exists(TEMPL_PREFIX."/settings_"."chat".$resultChatId.".txt")) {
		$resultChatId = substr($chatId, 1);
		file_put_contents(TEMPL_PREFIX."/settings_"."chat".$resultChatId.".txt", '[]');
		changeGameMode("toRus");
	}
}

$respText = 'Текст';

$translArr = respArrNow($chatId, $user_Id);
		
$trueResp = trim(mb_strtolower($translArr[0]["translation"]));
$trueQuestion = trim(mb_strtolower($translArr[0]["word"]));

if (getGameMode() == "toGreek"){
	$templTrueResp = $trueResp;
	$trueResp = $trueQuestion;
	$trueQuestion = $templTrueResp;
}
if (array_key_exists("voice", $arrDataAnswer["message"]) || array_key_exists("sticker", $arrDataAnswer["message"])){
	$respText = "Просьба отвечать текстом, $user_firstName.";
} elseif (array_key_exists("photo", $arrDataAnswer["message"])) {
	$respText = "Надеюсь, на фото ответ, $user_firstName. Но я понимаю только текст!";
} elseif (preg_match("/^[Пп]ожелание.+/i", $textMessage) || preg_match("/^[Пп]редложение.+/i", $textMessage)) {
	$respText = "Пожелание принято! Спасибо, $user_firstName!";
	writeLogFile($user_firstName . ": " . $textMessage, false, "/message.txt");
} elseif (preg_match("/^\*.+/i", $textMessage)) {
	exit();
} elseif (array_key_exists("new_chat_participant", $arrDataAnswer["message"])){
	$respText = "Γεια σας, ".$arrDataAnswer["message"]["new_chat_participant"]["first_name"]."! Переведите слово: " . " «" . $trueQuestion. "».";
} elseif (array_key_exists("left_chat_participant", $arrDataAnswer["message"])){
	$respText = "Пока, ".$arrDataAnswer["message"]["left_chat_participant"]["first_name"].". Всего хорошего!";
} elseif (preg_match("/([Пп]ока)|([Дд]о свидан)/i", $textMessage)){
	$respText = "Γεια σας, $user_firstName!";
} elseif (preg_match("/([Пп]ривет)|([Κκ]αλημέρα)|([Κκ]αλησπέρα)/i", $textMessage)){
	$respText = "Γεια σας, $user_firstName! Переведите слово: " . " «" . $trueQuestion. "».";
} elseif (preg_match("/game_change/i", $textMessage)){
	changeGameMode();
	$translArr = randArr(readTTFile('/translTraining.txt'));
	reWriteTTCopyFile(json_encode($translArr), $chatId, $user_Id);
	$respText = "Игра началась! \nПереведите слово: " . " «" . getTrueQw() . "».";
} elseif (preg_match("/start_game/i", $textMessage)) {
	$translArr = randArr(readTTFile('/translTraining.txt'));
	reWriteTTCopyFile(json_encode($translArr), $chatId, $user_Id);
	$respText = "Игра началась! \nПереведите слово: " . " «" . getTrueQw() . "».";
} elseif (preg_match("/game_hint/i", $textMessage) || preg_match("/[Пп]одсказка/i", $textMessage)) {
	$respText = "$user_firstName хочет подсказку!\n" . "Это слово произошло от «" . $translArr[0]["base"]. "» — «". $translArr[0]["baseTransl"]. "».";
} elseif (preg_match("/bot_info/i", $textMessage)) {
	$respText = "Информация об игре: \nВы можете:\n— перезапустить игру, если хотите изменить вопрос, командой «start_game».\n— взять подсказку команой «game_hint».\n— оставить пожелание для развития игры или бота, написав «пожелание» (или «предложение»), за которым следует текст вашего пожелания.\n—";
} elseif (preg_match("/[Оо]твет/i", $textMessage)) {
	$respText = "$user_firstName хочет ответ.\n" . "Но он его не получит! :)";
} elseif (preg_match("/[Нн]е знаю/i", $textMessage) || preg_match("/[Сс]даюсь/i", $textMessage)) {
	$respText = "$user_firstName, подумайте ещё или смените вопрос командой «start_game».";
} elseif (isContResp($trueResp, $textMessage)) {
	array_shift($translArr);
	reWriteTTCopyFile(json_encode($translArr), $chatId, $user_Id);
	$translArr = respArrNow($chatId, $user_Id);
	$respText = "Ура! \nПереведите слово: " . " «" . getTrueQw() . "».";
} else {
	writeLogFile($textMessage, false, "/log.txt");
	$respText = "Попытайтесь снова, $user_firstName! \nПереведите слово: " . " «" . $trueQuestion. "».";
}

$getQuery = array(
	'chat_id' 		=> $chatId,
	'text'			=> $respText,
	'parse_mode'	=> "html",
);

TG_sendMessage($getQuery);

//Функции
//для записи логов недоработанных вопросов
function writeLogFile($string, $clear = false, $fileName){
    $log_file_name = TEMPL_PREFIX.$fileName;
    if($clear == false) {
		$now = date("Y-m-d H:i:s");
		file_put_contents($log_file_name, $now." ".print_r($string, true)."\r\n", FILE_APPEND);
    }
    else {
		file_put_contents($log_file_name, '');
        file_put_contents($log_file_name, $now." ".print_r($string, true)."\r\n", FILE_APPEND);
    }
}

//для записи копии файла вопросов
function reWriteTTCopyFile($string, $chatId, $user_Id){
	if ($chatId == BOTID) {
		$log_file_name = TEMPL_PREFIX."/translTraining_copy"."user".$user_Id.".txt";
	} else {
		$resultChatId = substr($chatId, 1);
		$log_file_name = TEMPL_PREFIX."/translTraining_copy"."chat".$resultChatId.".txt";
	}
	file_put_contents($log_file_name, '');
	file_put_contents($log_file_name, print_r($string, true), FILE_APPEND);
}

//для чтения файла вопросов в массив
function readTTFile($filename){
	$ttArray = json_decode(file_get_contents(TEMPL_PREFIX.$filename), true);
	return $ttArray;
}

//для рандомного перемешивания массива
function randArr($array){
	shuffle($array);
	return $array;
}

/* для отправки текстовых сообщений */
function TG_sendMessage($getQuery) {
    $ch = curl_init("https://api.telegram.org/bot". BOTTOKEN ."/sendMessage?" . http_build_query($getQuery));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $res = curl_exec($ch);
    curl_close($ch);

    return $res;
}

/* для проверки ответа */
function isContResp($string, $needle) {
    $arrFromStr = explode(",", $string);
	for ($i=0; $i < count($arrFromStr) ; $i++) { 
		$arrFromStr[$i] = trim(mb_strtolower($arrFromStr[$i]));
	}
	return in_array(trim($needle), $arrFromStr);
}

/* для проверки пустоты файла и выдачи массива вопросов */
function respArrNow($chatId, $user_Id) {
	if ($chatId == BOTID) {
		$filePath = "/translTraining_copy"."user".$user_Id.".txt";
	} else {
		$resultChatId = substr($chatId, 1);
		$filePath = "/translTraining_copy"."chat".$resultChatId.".txt";
	}

	if (file_get_contents(TEMPL_PREFIX.$filePath) == "[]") {
		$translArr = randArr(readTTFile('/translTraining.txt'));
		reWriteTTCopyFile(json_encode($translArr));
		return $translArr;
	} else {
		return readTTFile($filePath);
	}

}

//узнать мод игры
function getGameMode(){
	global $chatId, $user_Id;
	if ($chatId == BOTID) {
		$file_ChatSetting = "/settings_"."user".$user_Id.".txt";
	} else {
		$resultChatId = substr($chatId, 1);
		$file_ChatSetting = "/settings_"."chat".$resultChatId.".txt";
	}
	return readTTFile($file_ChatSetting)["gameMod"];
}

//записать мод игры
function changeGameMode(){
	global $chatId, $user_Id;
	if ($chatId == BOTID) {
		$file_ChatSetting = "/settings_"."user".$user_Id.".txt";
	} else {
		$resultChatId = substr($chatId, 1);
		$file_ChatSetting = "/settings_"."chat".$resultChatId.".txt";
	}

	$chatSettingArr = readTTFile($file_ChatSetting);
	$actualGameMod = getGameMode();
	if ($actualGameMod == "toRus") {
		$chatSettingArr["gameMod"] = "toGreek";
	} else {
		$chatSettingArr["gameMod"] = "toRus";
	}
	file_put_contents(TEMPL_PREFIX.$file_ChatSetting, '');
	file_put_contents(TEMPL_PREFIX.$file_ChatSetting, print_r(json_encode($chatSettingArr), true)."\r\n", FILE_APPEND);
	return $chatSettingArr["gameMod"];
}

//узнать ответ
function getTrueQw(){
	global $translArr;
	$trueResp = trim(mb_strtolower($translArr[0]["translation"]));
	$trueQuestion = trim(mb_strtolower($translArr[0]["word"]));
	if (getGameMode() == "toGreek"){
		$trueQuestion = $trueResp;
	}
	return $trueQuestion;
}






















?>