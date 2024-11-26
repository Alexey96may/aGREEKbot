<?php
require_once 'app/classes/User.php';
require_once 'app/classes/Room.php';
require_once 'app/classes/UserAnswer.php';
require_once 'app/classes/Game.php';
require_once 'app/classes/core/logs/Log.php';
use App\Classes\User;
use App\Classes\Room;
use App\Classes\UserAnswer;
use App\Classes\Game;
use App\Classes\Core\Logs\Log;

//Class instances creating
if (class_exists('App\Classes\Core\Logs\Log')) {
	Log::setRootLogDir('./logs');
	$log = new Log('/tgBot.log');
} else {
	die('Class existing Error!');
}

//Token
$secretToken = file_get_contents(__DIR__."/init/token.txt");
define("BOTTOKEN", $secretToken);

//Template file prefix
define("TEMPL_PREFIX", __DIR__."/temp");

//Main data
$data = file_get_contents('php://input');
$dataArray = json_decode($data, true);

//Class instances creating
if (class_exists('App\Classes\User') && class_exists('App\Classes\Room') && class_exists('App\Classes\UserAnswer') && class_exists('App\Classes\Core\Logs\Log')) {
	$user = new User($dataArray['message']['from']);
	$room = new Room($dataArray['message']['chat']);
	$userAnswer = new UserAnswer($dataArray['message']['text']);
} else {
	$log->log('Class existing Error! In the line' . __LINE__);
	die('Class existing Error!');
}

//Array for the question modules
$allTheQuestionFiles = [1,2,3,4];

//Complete file Paths according to this chat
$settingsFilePath = TEMPL_PREFIX.'/settings_' . $room->roomIDPath() . '.txt';
$scoresFilePath = TEMPL_PREFIX.'/scores_' . $room->roomIDPath() . '.txt';
$translTrain_copyFilePath = TEMPL_PREFIX.'/translTraining_copy' . $room->roomIDPath() . '.txt';
$userMessageFilePath = TEMPL_PREFIX.'/message.txt';
$logFilePath = TEMPL_PREFIX.'/log.txt';
$errorsFilePath = TEMPL_PREFIX.'/errors.txt';

//Setting and Score files forming
if (!file_exists($settingsFilePath)) {
	file_put_contents($settingsFilePath, '[]', LOCK_EX);
}
if (!file_exists($scoresFilePath)) {
	file_put_contents($scoresFilePath, '[]', LOCK_EX);
}

//Class instances creating
if (class_exists('App\Classes\Game')) {
	$game = new Game($settingsFilePath);
} else {
	$log->log('Class Game existing Error! In the line' . __LINE__);
	die('Class existing Error!');
}

if (getGameModule() === null || getGameModule() === "") {
	$actualFileQw = TEMPL_PREFIX . "/translTraining" . setNewGameModule($allTheQuestionFiles) . ".txt";
} else {
	$actualFileQw = TEMPL_PREFIX . "/translTraining" . getGameModule() . ".txt";
}

$respText = 'Текст';

$translArr = respArrNow();
$trueResp = trim(mb_strtolower($translArr[0]["translation"]));
$trueQuestion = trim(mb_strtolower($translArr[0]["word"]));

if ($game->getGameMode() === 'toGreek'){
	$templTrueResp = $trueResp;
	$trueResp = $trueQuestion;
	$trueQuestion = $templTrueResp;
}

if (array_key_exists("voice", $dataArray["message"]) || array_key_exists("sticker", $dataArray["message"])){
	$respText = "Просьба отвечать текстом, " . $user->getFirstName() . ".";
} elseif (array_key_exists("photo", $dataArray["message"])) {
	$respText = "Надеюсь, на фото ответ, " . $user->getFirstName() . ". Но я понимаю только текст!";
} elseif (preg_match("/^[Пп]ожелание.+/ui", $userAnswer->getFormatUserAnswer())) {
	$respText = "Пожелание принято. Спасибо, " . $user->getFirstName() . "!";
	$desireLog = new Log('/userDesires/tgBot.log');
	$desireLog->log('--- ' . $user->getFirstName() . ' ' . $user->getLastName() . ' --- ' . $userAnswer->getUserAnswer());
} elseif (preg_match("/^\*.+/i", $userAnswer->getFormatUserAnswer())) {
	exit();
} elseif ($userAnswer->getFormatUserAnswer() === $trueQuestion) {
	$respText = "Это мой вопрос, " . $user->getFirstName() . "!";
} elseif (array_key_exists("new_chat_participant", $dataArray["message"])){
	$respText = "Γεια σας, ".$dataArray["message"]["new_chat_participant"]["first_name"]."! Переведите слово: " . " «<b>" . $trueQuestion. "</b>».";
} elseif (array_key_exists("left_chat_participant", $dataArray["message"])){
	$respText = "Пока, ".$dataArray["message"]["left_chat_participant"]["first_name"].". Всего хорошего!";
} elseif (preg_match("/([Пп]ока)|([Дд]о свидан)/ui", $userAnswer->getFormatUserAnswer())){
	$respText = "Γεια σας, " . $user->getFirstName() . "!";
} elseif (preg_match("/([Пп]ривет)|([Κκ]αλημέρα)|([Κκ]αλησπέρα)/ui", $userAnswer->getFormatUserAnswer())){
	$respText = "Γεια σας, " . $user->getFirstName() . "! Переведите слово: " . " «<b>" . $trueQuestion. "</b>».";
} elseif (preg_match("/game_change/i", $userAnswer->getFormatUserAnswer())){
	$game->changeGameMode($settingsFilePath);
	$translArr = randArr(readTTFile($actualFileQw));
	reWriteTTCopyFile(json_encode($translArr));
	$respText = "Игра началась!\n\nПереведите слово: " . " «<b>" . getTrueQw($game->getGameMode()) . "</b>».";
} elseif (preg_match("/start_game/i", $userAnswer->getFormatUserAnswer())) {
	$actualFileQw = TEMPL_PREFIX . "/translTraining" . setNewGameModule($allTheQuestionFiles) . ".txt";
	$translArr = randArr(readTTFile($actualFileQw));
	reWriteTTCopyFile(json_encode($translArr));
	$respText = "Игра началась!\n\nПереведите слово: " . " «<b>" . getTrueQw($game->getGameMode()) . "</b>».";
} elseif (preg_match("/game_hint/i", $userAnswer->getFormatUserAnswer())) {
	$respText = $user->getFirstName() . " хочет подсказку!\n" . "Это слово произошло от «<b>" . $translArr[0]["base"]. "</b>» — «<i>". $translArr[0]["baseTransl"]. "</i>».";
} elseif (preg_match("/game_dictionary/i", $userAnswer->getFormatUserAnswer()) || preg_match("/[Сс]ловарь/ui", $userAnswer->getFormatUserAnswer())) {
	$respText = getDefHinеText($translArr, $game->getGameMode());
} elseif (preg_match("/game_info/i", $userAnswer->getFormatUserAnswer())) {
	$respText = "<b>Информация об игре!</b>\n\nВы можете:\n— перезапустить игру, если хотите изменить вопрос, командой «<b>start_game</b>».\n— взять подсказку командой «<b>game_hint</b>».\n— изменить режим игры (перевод слов с греческого на русский, или наоборот) командой «<b>game_change</b>».\n— взять помощь словаря командой «<b>game_dictionary</b>» или словом «<b>словарь</b>» в чате.\n— узнать свой счёт в чате, своё место в рейтинге чата или топ игроков чата командами «Мой счёт», «Мой рейтинг» и «Рейтинг чата», соответственно.\n\n— если вы не хотите, чтобы бот вам отвечал, ставьте символ «<b>*</b>» в начало вашего сообщения в чате.\n— оставьте пожелание для развития игры или бота, написав с начала строки: «<b>Пожелание</b>», за которым следует текст вашего пожелания.\n\nВы можете добавить бота в свой чат, наделив его правами администратора.";
} elseif (preg_match("/[Оо]твет/ui", $userAnswer->getFormatUserAnswer())) {
	$respText = $user->getFirstName() . " хочет ответ.\n" . "Но он его не получит! :)";
} elseif (preg_match("/[Нн]е знаю/ui", $userAnswer->getFormatUserAnswer()) || preg_match("/[Сс]даюсь/ui", $userAnswer->getFormatUserAnswer())) {
	$respText = $user->getFirstName() . ", подумайте ещё или смените вопрос командой «start_game».";
} elseif (preg_match("/[Мм]ой рейтинг/ui", $userAnswer->getFormatUserAnswer()) || preg_match("/[Уу] меня рейтинг/ui", $userAnswer->getFormatUserAnswer())) {
	$respText = $user->getUserRatingMessage($scoresFilePath);
} elseif (preg_match("/[Мм]ой сч[её]т/ui", $userAnswer->getFormatUserAnswer())) {
	$respText = 'Ваш счёт = <b>' . $user->getUserScore($scoresFilePath) . '<b>';
} elseif (preg_match("/[Рр]ейтинг чата/ui", $userAnswer->getFormatUserAnswer())) {
	$respText = $room->getRoomRating($scoresFilePath);
} elseif ($userAnswer->isCorrectAnswer($trueResp)) {
	array_shift($translArr);
	reWriteTTCopyFile(json_encode($translArr));
	$translArr = respArrNow();
	$user->setUserScore($scoresFilePath, $userAnswer->getAnswerPrice());
	$respText = "Правильно, " . $user->getFirstName() . "! Ответ был: «{$trueResp}». \nВаш счёт = <b>" . $user->getUserScore($scoresFilePath) . "</b>.\n\nПереведите слово: " . " «<b>" . getTrueQw($game->getGameMode()) . "</b>».";
} else {
	$respText = $userAnswer->wrongAnswerMessage($user->getFirstName(), $trueResp) . " " . infinitiveMessage($userAnswer->getFormatUserAnswer(), $trueQuestion) . decideOnYourAnswer($userAnswer->getFormatUserAnswer()) . "\n\nПереведите слово: " . " «<b>" . $trueQuestion. "</b>».";
}

$getQuery = array(
	'chat_id' 		=> $room->getID(),
	'text'			=> $respText,
	'parse_mode'	=> "HTML",
);

try {
    TG_sendMessage($getQuery);
} catch (Exception $e) {
	$log->log('Exception! In the line' . __LINE__);
}

//Функции

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

/* для проверки пустоты файла и выдачи массива вопросов */
function respArrNow() {
	global $room, $actualFileQw, $translTrain_copyFilePath, $allTheQuestionFiles;

	if (file_get_contents($translTrain_copyFilePath) == "[]") {
		$actualFileQw = TEMPL_PREFIX . "/translTraining" . setNewGameModule($allTheQuestionFiles) . ".txt";
		$translArr = randArr(readTTFile($actualFileQw));
		reWriteTTCopyFile(json_encode($translArr));
		return $translArr;
	} else {
		return readTTFile($translTrain_copyFilePath);
	}
}

//узнать ответ
function getTrueQw($game_mod){
	global $translArr;
	$trueResp = trim(mb_strtolower($translArr[0]["translation"]));
	$trueQuestion = trim(mb_strtolower($translArr[0]["word"]));
	if ($game_mod == "toGreek"){
		$trueQuestion = $trueResp;
	}
	return $trueQuestion;
}

//узнать подсказку определение
function getDefHinеText($arr, $game_mod){
	$defHintText = "К сожалению, для данного слова пока нет определения.";
	if (array_key_exists("hint", $arr[0])) {
			$defHint = $arr[0]["hint"];
		if ($defHint != "") {
			$defHintText = "Определение слова <b>«" . getTrueQw($game_mod) . "»</b> из словаря: \n\n" . $arr[0]["hint"];
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