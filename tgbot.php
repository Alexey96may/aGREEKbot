<?php
require 'app/classes/User.php';
require 'app/classes/Room.php';
require 'app/classes/UserAnswer.php';
use App\Classes\User;
use App\Classes\Room;
use App\Classes\UserAnswer;

//Token
$secretToken = file_get_contents(__DIR__."/init/token.txt");
define("BOTTOKEN", $secretToken);

//Template file prefix
define("TEMPL_PREFIX", __DIR__."/temp");

//Main data
$data = file_get_contents('php://input');
$dataArray = json_decode($data, true);

//Class instances creating
if (class_exists('App\Classes\User') && class_exists('App\Classes\Room') && class_exists('App\Classes\UserAnswer')) {
	$user = new User($dataArray['message']['from']);
	$room = new Room($dataArray['message']['chat']);
	$userAnswer = new UserAnswer($dataArray['message']['text']);
} else {
	die('Class existing Error!');
}

//Array for the question modules
$allTheQuestionFiles = [1,2,3,4];

//True answer price
$scoreToAdd = 1;

//Complete file Paths according to this chat
$settingsFilePath = TEMPL_PREFIX.'/settings_' . $room->roomIDPath() . '.txt';
$scoresFilePath = TEMPL_PREFIX.'/scores_' . $room->roomIDPath() . '.txt';
$translTrain_copyFilePath = TEMPL_PREFIX.'/translTraining_copy' . $room->roomIDPath() . '.txt';
$userMessageFilePath = TEMPL_PREFIX.'/message.txt';
$logFilePath = TEMPL_PREFIX.'/log.txt';
$errorsFilePath = TEMPL_PREFIX.'/errors.txt';

if (getGameModule() === null || getGameModule() === "") {
	$actualFileQw = TEMPL_PREFIX . "/translTraining" . setNewGameModule($allTheQuestionFiles) . ".txt";
} else {
	$actualFileQw = TEMPL_PREFIX . "/translTraining" . getGameModule() . ".txt";
}

//формируем файл настроек, если его нет, добавление модуля и файла для перемешивания
if (!file_exists($settingsFilePath)) {
	file_put_contents($settingsFilePath, '[]', LOCK_EX);
	changeGameMode("toRus");
}
if (!file_exists($scoresFilePath)) {
	file_put_contents($scoresFilePath, '[]', LOCK_EX);
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
} elseif (preg_match("/^[Пп]ожелание.+/ui", $userAnswer->getFormatAnswer())) {
	$respText = "Пожелание принято. Спасибо, " . $user->getFirstName() . "!";
	writeLogFile($user->getFirstName() . ": " . $userAnswer->getFormatAnswer(), false, $userMessageFilePath);
} elseif (preg_match("/^\*.+/i", $userAnswer->getFormatAnswer())) {
	exit();
} elseif ($userAnswer->getFormatAnswer() === $trueQuestion) {
	$respText = "Это мой вопрос, " . $user->getFirstName() . "!";
} elseif (array_key_exists("new_chat_participant", $dataArray["message"])){
	$respText = "Γεια σας, ".$dataArray["message"]["new_chat_participant"]["first_name"]."! Переведите слово: " . " «<b>" . $trueQuestion. "</b>».";
} elseif (array_key_exists("left_chat_participant", $dataArray["message"])){
	$respText = "Пока, ".$dataArray["message"]["left_chat_participant"]["first_name"].". Всего хорошего!";
} elseif (preg_match("/([Пп]ока)|([Дд]о свидан)/ui", $userAnswer->getFormatAnswer())){
	$respText = "Γεια σας, " . $user->getFirstName() . "!";
} elseif (preg_match("/([Пп]ривет)|([Κκ]αλημέρα)|([Κκ]αλησπέρα)/ui", $userAnswer->getFormatAnswer())){
	$respText = "Γεια σας, " . $user->getFirstName() . "! Переведите слово: " . " «<b>" . $trueQuestion. "</b>».";
} elseif (preg_match("/game_change/i", $userAnswer->getFormatAnswer())){
	changeGameMode();
	$translArr = randArr(readTTFile($actualFileQw));
	reWriteTTCopyFile(json_encode($translArr));
	$respText = "Игра началась!\n\nПереведите слово: " . " «<b>" . getTrueQw() . "</b>».";
} elseif (preg_match("/start_game/i", $userAnswer->getFormatAnswer())) {
	$actualFileQw = TEMPL_PREFIX . "/translTraining" . setNewGameModule($allTheQuestionFiles) . ".txt";
	$translArr = randArr(readTTFile($actualFileQw));
	reWriteTTCopyFile(json_encode($translArr));
	$respText = "Игра началась!\n\nПереведите слово: " . " «<b>" . getTrueQw() . "</b>».";
} elseif (preg_match("/game_hint/i", $userAnswer->getFormatAnswer())) {
	$respText = $user->getFirstName() . " хочет подсказку!\n" . "Это слово произошло от «<b>" . $translArr[0]["base"]. "</b>» — «<i>". $translArr[0]["baseTransl"]. "</i>».";
} elseif (preg_match("/game_dictionary/i", $userAnswer->getFormatAnswer()) || preg_match("/[Сс]ловарь/ui", $userAnswer->getFormatAnswer())) {
	$respText = getDefHinеText($translArr);
} elseif (preg_match("/game_info/i", $userAnswer->getFormatAnswer())) {
	$respText = "<b>Информация об игре!</b>\n\nВы можете:\n— перезапустить игру, если хотите изменить вопрос, командой «<b>start_game</b>».\n— взять подсказку командой «<b>game_hint</b>».\n— изменить режим игры (перевод слов с греческого на русский, или наоборот) командой «<b>game_change</b>».\n— взять помощь словаря командой «<b>game_dictionary</b>» или словом «<b>словарь</b>» в чате.\n— узнать свой счёт в чате, своё место в рейтинге чата или топ игроков чата командами «Мой счёт», «Мой рейтинг» и «Рейтинг чата», соответственно.\n\n— если вы не хотите, чтобы бот вам отвечал, ставьте символ «<b>*</b>» в начало вашего сообщения в чате.\n— оставьте пожелание для развития игры или бота, написав с начала строки: «<b>Пожелание</b>», за которым следует текст вашего пожелания.\n\nВы можете добавить бота в свой чат, наделив его правами администратора.";
} elseif (preg_match("/[Оо]твет/ui", $userAnswer->getFormatAnswer())) {
	$respText = $user->getFirstName() . " хочет ответ.\n" . "Но он его не получит! :)";
} elseif (preg_match("/[Нн]е знаю/ui", $userAnswer->getFormatAnswer()) || preg_match("/[Сс]даюсь/ui", $userAnswer->getFormatAnswer())) {
	$respText = $user->getFirstName() . ", подумайте ещё или смените вопрос командой «start_game».";
} elseif (preg_match("/[Мм]ой рейтинг/ui", $userAnswer->getFormatAnswer()) || preg_match("/[Уу] меня рейтинг/ui", $userAnswer->getFormatAnswer())) {
	$respText = getUserRating($user->getFullName() . "__" . $user->getID());
} elseif (preg_match("/[Мм]ой сч[её]т/ui", $userAnswer->getFormatAnswer())) {
	$respText = "Ваш счёт = " . getChatScore($user->getFullName() . "__" . $user->getID());
} elseif (preg_match("/[Рр]ейтинг чата/ui", $userAnswer->getFormatAnswer())) {
	$respText = getChatRating();
} elseif (isContResp($trueResp, $userAnswer->getFormatAnswer())) {
	array_shift($translArr);
	reWriteTTCopyFile(json_encode($translArr));
	$translArr = respArrNow();
	setChatScore($user->getFullName() . "__" . $user->getID(), 1);
	$respText = "Правильно, " . $user->getFirstName() . "! Ответ был: «{$trueResp}». \nВаш счёт = <b>" . getChatScore($user->getFullName() . "__" . $user->getID()) . "</b>.\n\nПереведите слово: " . " «<b>" . getTrueQw() . "</b>».";
} else {
	$respText = wrongAnswerMessage($userAnswer->getFormatAnswer(), $trueResp, $user->getFirstName()) . " " . infinitiveMessage($userAnswer->getFormatAnswer(), $trueQuestion) . decideOnYourAnswer($userAnswer->getFormatAnswer()) . "\n\nПереведите слово: " . " «<b>" . $trueQuestion. "</b>».";
}

$getQuery = array(
	'chat_id' 		=> $room->getID(),
	'text'			=> $respText,
	'parse_mode'	=> "HTML",
);

try {
    TG_sendMessage($getQuery);
} catch (Exception $e) {
	writeLogFile('PHP перехватил исключение: ' . $e->getMessage() . "\n", false, $errorsFilePath);
}

//Функции
//для записи логов недоработанных вопросов
function writeLogFile($string, $clear = false, $fileName){
    if($clear == false) {
		$now = date("Y-m-d H:i:s");
		file_put_contents($fileName, $now." ".print_r($string, true)."\r\n", FILE_APPEND | LOCK_EX);
    }
    else {
		file_put_contents($fileName, '', LOCK_EX);
        file_put_contents($fileName, $now." ".print_r($string, true)."\r\n", FILE_APPEND | LOCK_EX);
    }
}

//для записи копии файла вопросов
function reWriteTTCopyFile($string){
	global $room, $translTrain_copyFilePath;

	file_put_contents($translTrain_copyFilePath, '', LOCK_EX);
	file_put_contents($translTrain_copyFilePath, print_r($string, true), FILE_APPEND | LOCK_EX);
}

//для чтения файла вопросов в массив
function readTTFile($filename){
	$ttArray = json_decode(file_get_contents($filename), true);
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
	global $room, $actualFileQw, $translTrain_copyFilePath;

	if (file_get_contents($translTrain_copyFilePath) == "[]") {
		$actualFileQw = TEMPL_PREFIX . "/translTraining" . setNewGameModule($allTheQuestionFiles) . ".txt";
		$translArr = randArr(readTTFile($actualFileQw));
		reWriteTTCopyFile(json_encode($translArr));
		return $translArr;
	} else {
		return readTTFile($translTrain_copyFilePath);
	}
}

//узнать мод игры
function getGameMode(){
	global $room, $settingsFilePath;

	return readTTFile($settingsFilePath)["gameMod"];
}

//записать мод игры
function changeGameMode(){
	global $room, $settingsFilePath;

	$chatSettingArr = readTTFile($settingsFilePath);
	$actualGameMod = getGameMode();
	if ($actualGameMod == "toRus") {
		$chatSettingArr["gameMod"] = "toGreek";
	} else {
		$chatSettingArr["gameMod"] = "toRus";
	}
	file_put_contents($settingsFilePath, '', LOCK_EX);
	file_put_contents($settingsFilePath, print_r(json_encode($chatSettingArr), true)."\r\n", FILE_APPEND | LOCK_EX);
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
	global $room, $settingsFilePath;

	return readTTFile($settingsFilePath)["fileGame"];
}
//set game module
function setNewGameModule($arr){
	global $room, $settingsFilePath;

	$chatSettingArr = readTTFile($settingsFilePath);

	$chatSettingArr["fileGame"] = array_rand($arr) + 1;
	file_put_contents($settingsFilePath, '', LOCK_EX);
	file_put_contents($settingsFilePath, print_r(json_encode($chatSettingArr), true)."\r\n", FILE_APPEND | LOCK_EX);
	return $chatSettingArr["fileGame"];
}

//users chat score
function getChatScore($userName) {
	global $room, $scoresFilePath;

	$chatScoresArr = readTTFile($scoresFilePath);

	if (array_key_exists($userName, $chatScoresArr)) {
		return (int) $chatScoresArr[$userName];
	} else {
		return setChatScore($userName, 0);
	}
}

function getUserRating($userName) {
	global $room, $scoresFilePath;

	$chatScoresArr = readTTFile($scoresFilePath);

	if (array_key_exists($userName, $chatScoresArr)) {
		$usesrScoreNumber = (int) array_search($userName, $chatScoresArr) + 1;
		return "Вы на $usesrScoreNumber месте в этом чате!";
	} else {
		return "Вас ещё нет в статистике этого чата! \nОтветьте правильно хотя бы на один вопрос.";
	}
}

function getChatRating() {
	global $room, $scoresFilePath;

	$chatScoresArr = readTTFile($scoresFilePath);
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
	global $room, $scoresFilePath;

	$chatScoresArr = readTTFile($scoresFilePath);

	if (array_key_exists($userName, $chatScoresArr)) {
		$chatScoresArr[$userName] = (int) $chatScoresArr[$userName] + $scoreToAdd;
	} else {
		$chatScoresArr[$userName] = 0 + $scoreToAdd;
	}

	file_put_contents($scoresFilePath, '', LOCK_EX);
	file_put_contents($scoresFilePath, print_r(json_encode($chatScoresArr), true)."\r\n", FILE_APPEND | LOCK_EX);
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