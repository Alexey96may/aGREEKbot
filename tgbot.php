<?php
require 'app/classes/User.php';
require 'app/classes/Room.php';
use App\Classes\User;
use App\Classes\Room;

//Token from another file
$secretToken = file_get_contents(__DIR__."/init/token.txt");

//Array for the question modules
$allTheQuestionFiles = [1,2,3,4];

//Template file prefix
define("TEMPL_PREFIX", __DIR__."/temp");

//Question Price
$scoreToAdd = 1;

//Access keys
define("BOTTOKEN", $secretToken);
define("BOTID", "5687000457");
define("CHATTTID", "-1002205650441");

//Main data
$data = file_get_contents('php://input');
$dataArray = json_decode($data, true);

//Class instances creating
if (class_exists('App\Classes\User')) {
	$user = new User($dataArray['message']['from']);
} else {
	die('Class existing Error! Class User.');
}

if (class_exists('App\Classes\Room')) {
	$room = new Room($dataArray['message']['chat']);
} else {
	die('Class existing Error! Class Room.');
}

$textMessage = trim(mb_strtolower($dataArray['message']['text']));

//определим переменную имени файла
$filePathVar = '';
if ($room->getID() == BOTID) {
	$filePathVar = "user" . $user->getID();
} else {
	$resultChatId = substr($room->getID(), 1);
	$filePathVar = "chat" . $resultChatId;
}

//формируем файл настроек, если его нет, добавление модуля и файла для перемешивания
if (!file_exists(TEMPL_PREFIX."/settings_" . $filePathVar . ".txt")) {
	file_put_contents(TEMPL_PREFIX."/settings_" . $filePathVar . ".txt", '[]', LOCK_EX);
	changeGameMode("toRus");
}
if (!file_exists(TEMPL_PREFIX."/scores_" . $filePathVar . ".txt")) {
	file_put_contents(TEMPL_PREFIX."/scores_" . $filePathVar . ".txt", '[]', LOCK_EX);
}
if (getGameModule() === null || getGameModule() === "") {
	$actualFileQw = "/translTraining" . setNewGameModule($allTheQuestionFiles) . ".txt";
} else {
	$actualFileQw = "/translTraining" . getGameModule() . ".txt";
}

$respText = 'Текст';

$translArr = respArrNow();
$trueResp = trim(mb_strtolower($translArr[0]["translation"]));
$trueQuestion = trim(mb_strtolower($translArr[0]["word"]));

if (getGameMode() == "toGreek"){
	$templTrueResp = $trueResp;
	$trueResp = $trueQuestion;
	$trueQuestion = $templTrueResp;
}
if (array_key_exists("voice", $dataArray["message"]) || array_key_exists("sticker", $dataArray["message"])){
	$respText = "Просьба отвечать текстом, " . $user->getFirstName() . ".";
} elseif (array_key_exists("photo", $dataArray["message"])) {
	$respText = "Надеюсь, на фото ответ, " . $user->getFirstName() . ". Но я понимаю только текст!";
} elseif (preg_match("/^[Пп]ожелание.+/ui", $textMessage)) {
	$respText = "Пожелание принято. Спасибо, " . $user->getFirstName() . "!";
	writeLogFile($user->getFirstName() . ": " . $textMessage, false, "/message.txt");
} elseif (preg_match("/^\*.+/i", $textMessage)) {
	exit();
} elseif ($textMessage === $trueQuestion) {
	$respText = "Это мой вопрос, " . $user->getFirstName() . "!";
} elseif (array_key_exists("new_chat_participant", $dataArray["message"])){
	$respText = "Γεια σας, ".$dataArray["message"]["new_chat_participant"]["first_name"]."! Переведите слово: " . " «<b>" . $trueQuestion. "</b>».";
} elseif (array_key_exists("left_chat_participant", $dataArray["message"])){
	$respText = "Пока, ".$dataArray["message"]["left_chat_participant"]["first_name"].". Всего хорошего!";
} elseif (preg_match("/([Пп]ока)|([Дд]о свидан)/ui", $textMessage)){
	$respText = "Γεια σας, " . $user->getFirstName() . "!";
} elseif (preg_match("/([Пп]ривет)|([Κκ]αλημέρα)|([Κκ]αλησπέρα)/ui", $textMessage)){
	$respText = "Γεια σας, " . $user->getFirstName() . "! Переведите слово: " . " «<b>" . $trueQuestion. "</b>».";
} elseif (preg_match("/game_change/i", $textMessage)){
	changeGameMode();
	$translArr = randArr(readTTFile($actualFileQw));
	reWriteTTCopyFile(json_encode($translArr));
	$respText = "Игра началась!\n\nПереведите слово: " . " «<b>" . getTrueQw() . "</b>».";
} elseif (preg_match("/start_game/i", $textMessage)) {
	$actualFileQw = "/translTraining" . setNewGameModule($allTheQuestionFiles) . ".txt";
	$translArr = randArr(readTTFile($actualFileQw));
	reWriteTTCopyFile(json_encode($translArr));
	$respText = "Игра началась!\n\nПереведите слово: " . " «<b>" . getTrueQw() . "</b>».";
} elseif (preg_match("/game_hint/i", $textMessage)) {
	$respText = $user->getFirstName() . " хочет подсказку!\n" . "Это слово произошло от «<b>" . $translArr[0]["base"]. "</b>» — «<i>". $translArr[0]["baseTransl"]. "</i>».";
} elseif (preg_match("/game_dictionary/i", $textMessage) || preg_match("/[Сс]ловарь/ui", $textMessage)) {
	$respText = getDefHinеText($translArr);
} elseif (preg_match("/game_info/i", $textMessage)) {
	$respText = "<b>Информация об игре!</b>\n\nВы можете:\n— перезапустить игру, если хотите изменить вопрос, командой «<b>start_game</b>».\n— взять подсказку командой «<b>game_hint</b>».\n— изменить режим игры (перевод слов с греческого на русский, или наоборот) командой «<b>game_change</b>».\n— взять помощь словаря командой «<b>game_dictionary</b>» или словом «<b>словарь</b>» в чате.\n— узнать свой счёт в чате, своё место в рейтинге чата или топ игроков чата командами «Мой счёт», «Мой рейтинг» и «Рейтинг чата», соответственно.\n\n— если вы не хотите, чтобы бот вам отвечал, ставьте символ «<b>*</b>» в начало вашего сообщения в чате.\n— оставьте пожелание для развития игры или бота, написав с начала строки: «<b>Пожелание</b>», за которым следует текст вашего пожелания.\n\nВы можете добавить бота в свой чат, наделив его правами администратора.";
} elseif (preg_match("/[Оо]твет/ui", $textMessage)) {
	$respText = $user->getFirstName() . " хочет ответ.\n" . "Но он его не получит! :)";
} elseif (preg_match("/[Нн]е знаю/ui", $textMessage) || preg_match("/[Сс]даюсь/ui", $textMessage)) {
	$respText = $user->getFirstName() . ", подумайте ещё или смените вопрос командой «start_game».";
} elseif (preg_match("/[Мм]ой рейтинг/ui", $textMessage) || preg_match("/[Уу] меня рейтинг/ui", $textMessage)) {
	$respText = getUserRating($user->getFullName() . "__" . $user->getID());
} elseif (preg_match("/[Мм]ой сч[её]т/ui", $textMessage)) {
	$respText = "Ваш счёт = " . getChatScore($user->getFullName() . "__" . $user->getID());
} elseif (preg_match("/[Рр]ейтинг чата/ui", $textMessage)) {
	$respText = getChatRating();
} elseif (isContResp($trueResp, $textMessage)) {
	array_shift($translArr);
	reWriteTTCopyFile(json_encode($translArr));
	$translArr = respArrNow();
	setChatScore($user->getFullName() . "__" . $user->getID(), 1);
	$respText = "Правильно, " . $user->getFirstName() . "! Ответ был: «{$trueResp}». \nВаш счёт = <b>" . getChatScore($user->getFullName() . "__" . $user->getID()) . "</b>.\n\nПереведите слово: " . " «<b>" . getTrueQw() . "</b>».";
} else {
	$respText = wrongAnswerMessage($textMessage, $trueResp, $user->getFirstName()) . " " . infinitiveMessage($textMessage, $trueQuestion) . decideOnYourAnswer($textMessage) . "\n\nПереведите слово: " . " «<b>" . $trueQuestion. "</b>».";
}

$getQuery = array(
	'chat_id' 		=> $room->getID(),
	'text'			=> $respText,
	'parse_mode'	=> "HTML",
);

try {
    TG_sendMessage($getQuery);
} catch (Exception $e) {
	writeLogFile('PHP перехватил исключение: ' . $e->getMessage() . "\n", false, "/errors.txt");
}

//Функции
//для записи логов недоработанных вопросов
function writeLogFile($string, $clear = false, $fileName){
    $log_file_name = TEMPL_PREFIX.$fileName;
    if($clear == false) {
		$now = date("Y-m-d H:i:s");
		file_put_contents($log_file_name, $now." ".print_r($string, true)."\r\n", FILE_APPEND | LOCK_EX);
    }
    else {
		file_put_contents($log_file_name, '', LOCK_EX);
        file_put_contents($log_file_name, $now." ".print_r($string, true)."\r\n", FILE_APPEND | LOCK_EX);
    }
}

//для записи копии файла вопросов
function reWriteTTCopyFile($string){
	global $filePathVar;

	$log_file_name = TEMPL_PREFIX."/translTraining_copy" . $filePathVar . ".txt";
	file_put_contents($log_file_name, '', LOCK_EX);
	file_put_contents($log_file_name, print_r($string, true), FILE_APPEND | LOCK_EX);
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
function respArrNow() {
	global $filePathVar, $actualFileQw;

	$filePath = "/translTraining_copy" . $filePathVar . ".txt";
	if (file_get_contents(TEMPL_PREFIX.$filePath) == "[]") {
		$actualFileQw = "/translTraining" . setNewGameModule($allTheQuestionFiles) . ".txt";
		$translArr = randArr(readTTFile($actualFileQw));
		reWriteTTCopyFile(json_encode($translArr));
		return $translArr;
	} else {
		return readTTFile($filePath);
	}

}

//узнать мод игры
function getGameMode(){
	global $filePathVar;

	$file_ChatSetting = "/settings_" . $filePathVar . ".txt";
	return readTTFile($file_ChatSetting)["gameMod"];
}

//записать мод игры
function changeGameMode(){
	global $filePathVar;

	$file_ChatSetting = "/settings_" . $filePathVar . ".txt";
	$chatSettingArr = readTTFile($file_ChatSetting);
	$actualGameMod = getGameMode();
	if ($actualGameMod == "toRus") {
		$chatSettingArr["gameMod"] = "toGreek";
	} else {
		$chatSettingArr["gameMod"] = "toRus";
	}
	file_put_contents(TEMPL_PREFIX.$file_ChatSetting, '', LOCK_EX);
	file_put_contents(TEMPL_PREFIX.$file_ChatSetting, print_r(json_encode($chatSettingArr), true)."\r\n", FILE_APPEND | LOCK_EX);
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

//узнать подсказку определение
function getDefHinеText($arr){
	$defHintText = "К сожалению, для данного слова пока нет определения.";
	if (array_key_exists("hint", $arr[0])) {
			$defHint = $arr[0]["hint"];
		if ($defHint != "") {
			$defHintText = "Определение слова <b>«" . getTrueQw() . "»</b> из словаря: \n\n" . $arr[0]["hint"];
		}
	}
	
	return $defHintText;
}

//get game module
function getGameModule(){
	global $filePathVar;

	$file_ChatSetting = "/settings_" . $filePathVar . ".txt";
	return readTTFile($file_ChatSetting)["fileGame"];
}
//set game module
function setNewGameModule($arr){
	global $filePathVar;

	$file_ChatSetting = "/settings_" . $filePathVar . ".txt";
	$chatSettingArr = readTTFile($file_ChatSetting);

	$chatSettingArr["fileGame"] = array_rand($arr) + 1;
	file_put_contents(TEMPL_PREFIX.$file_ChatSetting, '', LOCK_EX);
	file_put_contents(TEMPL_PREFIX.$file_ChatSetting, print_r(json_encode($chatSettingArr), true)."\r\n", FILE_APPEND | LOCK_EX);
	return $chatSettingArr["fileGame"];
}

//users chat score
function getChatScore($userName) {
	global $filePathVar;

	$file_ChatScores = "/scores_" . $filePathVar . ".txt";
	$chatScoresArr = readTTFile($file_ChatScores);

	if (array_key_exists($userName, $chatScoresArr)) {
		return (int) $chatScoresArr[$userName];
	} else {
		return setChatScore($userName, 0);
	}
}

function getUserRating($userName) {
	global $filePathVar;

	$file_ChatScores = "/scores_" . $filePathVar . ".txt";
	$chatScoresArr = readTTFile($file_ChatScores);

	if (array_key_exists($userName, $chatScoresArr)) {
		$usesrScoreNumber = (int) array_search($userName, $chatScoresArr) + 1;
		return "Вы на $usesrScoreNumber месте в этом чате!";
	} else {
		return "Вас ещё нет в статистике этого чата! \nОтветьте правильно хотя бы на один вопрос.";
	}
}

function getChatRating() {
	global $filePathVar;

	$file_ChatScores = "/scores_" . $filePathVar . ".txt";
	$chatScoresArr = readTTFile($file_ChatScores);
	$result = "Рейтинг нашего чата: \n\n";
	$countNum = 1;

	if (count($chatScoresArr) !== 0) {

		arsort($chatScoresArr, SORT_NUMERIC);

		foreach ($chatScoresArr as $key => $value) {
			if ($countNum < 10) {
				$symbolToCut = strpos($key, "__");
				$originUserName = substr($key, 0, $symbolToCut);
				$result .= $countNum . ": " . $originUserName . " - " . $value . ".\n";
				$countNum++;
			} else {
				break;
			}
		}

	} else {
		$result = "Рейтинг этого чата пока ещё пуст.";
	}

	return $result;
}

function setChatScore($userName, $scoreToAdd) {
	global $filePathVar;

	$file_ChatScores = "/scores_" . $filePathVar . ".txt";
	$chatScoresArr = readTTFile($file_ChatScores);

	if (array_key_exists($userName, $chatScoresArr)) {
		$chatScoresArr[$userName] = (int) $chatScoresArr[$userName] + $scoreToAdd;
	} else {
		$chatScoresArr[$userName] = 0 + $scoreToAdd;
	}

	file_put_contents(TEMPL_PREFIX.$file_ChatScores, '', LOCK_EX);
	file_put_contents(TEMPL_PREFIX.$file_ChatScores, print_r(json_encode($chatScoresArr), true)."\r\n", FILE_APPEND | LOCK_EX);
	return (int) $chatScoresArr[$userName];
}

function answerRater($userAnswer, $correctAnswer) {
    $userAnswer = mb_strtolower(trim($userAnswer));
    $correctAnswer = explode(",", $correctAnswer);

    $sameLetterCount = 0;
    $rating;
	$akinnestStrings = [$userAnswer, ""];
	$maxStringLength;

	for ($u=0; $u < count($correctAnswer); $u++) {
		$maxCurrentStringLength = max(strlen($correctAnswer[$u]), strlen($userAnswer));
		$correctAnswer[$u] = mb_strtolower(trim($correctAnswer[$u]));
		$currentStringsArr = [$userAnswer, $correctAnswer[$u]];
		$currentLetterCount = 0;
		$currentUserAnswer = $userAnswer;

		for ($i=0; $i < $maxCurrentStringLength; $i++) { 
			if ($correctAnswer[$u][$i] === $currentUserAnswer[$i]) {
				$currentLetterCount++;
			} else {
				if (strlen($correctAnswer[$u]) > strlen($userAnswer)) {
					$currentUserAnswer = mb_substr($currentUserAnswer, 0, $i) . "*" . mb_substr($currentUserAnswer, $i);
				} elseif(strlen($correctAnswer[$u]) < strlen($currentUserAnswer)) {
					$correctAnswer[$u] = mb_substr($correctAnswer[$u], 0, $i) . "*" . mb_substr($correctAnswer[$u], $i);
				}
			}
		}
		if ($sameLetterCount <= $currentLetterCount) {
			$sameLetterCount = $currentLetterCount;
			$akinnestStrings = $currentStringsArr;
		}
	}

    $maxStringLength = max(strlen($akinnestStrings[0]), strlen($akinnestStrings[1]));
    $rating = $sameLetterCount / $maxStringLength;
    return $rating;
}

function wrongAnswerMessage($userAnswer, $correctAnswer, $user_firstName) {
    $answerRating = answerRater($userAnswer, $correctAnswer);
    $message = '';

    if ($answerRating > 0.75) {
        $message = "Очень близко, $user_firstName! Возможно, в вашем ответе опечатка.";
    } else if($answerRating > 0.5) {
        $message = "Неправильно, но близко! Попытайтесь снова, $user_firstName!";
    } else {
        $message = "Попытайтесь снова, $user_firstName!";
    }

    return $message;
}

function infinitiveMessage($userAnswer, $correctAnswer) {
    $userAnswerLastLetter = mb_substr($userAnswer, -1);
    $correctAnswerLastLetter = mb_substr($correctAnswer, -1);
    
    if (($userAnswerLastLetter === "ю" || $userAnswerLastLetter === "у" || mb_substr($userAnswer, -2) === "сь") && ($correctAnswerLastLetter === "ω" || $correctAnswerLastLetter === "ώ" || mb_substr($correctAnswer, -3) === "μαι")) {
        return "Пожалуйста, переводите глаголы инфинитивами...";
    }
    return "";
}

function decideOnYourAnswer($userAnswer) {
    if (mb_strpos($userAnswer, ",") !== false) {
		return "Определитесь с ответом, пожалуйста.";
	}
	return "";
}